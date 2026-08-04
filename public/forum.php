<?php
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = __('forum_title');
$db = getDB();
$user = currentUser();

// Lấy danh sách địa điểm để hiển thị trong select
$destinations = getAllDestinations();

// Lấy danh sách bài viết
$page = isset($_GET['page']) && (int)$_GET['page'] > 0 ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$stmt = $db->prepare("
    SELECT c.*, u.full_name, u.avatar, d.name AS dest_name, d.name_en AS dest_name_en 
    FROM checkins c 
    JOIN users u ON c.user_id = u.id 
    LEFT JOIN destinations d ON c.destination_id = d.id 
    WHERE c.status = 'published'
    ORDER BY c.created_at DESC 
    LIMIT ? OFFSET ?
");
$stmt->bindValue(1, $limit, PDO::PARAM_INT);
$stmt->bindValue(2, $offset, PDO::PARAM_INT);
$stmt->execute();
$checkins = $stmt->fetchAll();

// Lấy lượt thích của user hiện tại
$userLikes = [];
if ($user) {
    $stmtLike = $db->prepare("SELECT checkin_id FROM checkin_likes WHERE user_id = ?");
    $stmtLike->execute([$user['id']]);
    $likes = $stmtLike->fetchAll(PDO::FETCH_COLUMN);
    $userLikes = array_fill_keys($likes, true);
}

include __DIR__ . '/../includes/header.php';
?>
<style>
.forum-container { max-width: 1040px; margin: 40px auto; }
.forum-header { text-align: center; margin-bottom: 30px; }
.forum-header h1 { color: var(--green-900); font-size: 32px; margin-bottom: 10px; }
.forum-header p { color: #64748b; font-size: 16px; }

/* Tạo bài viết */
.create-post { background: white; border-radius: 12px; padding: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.06); margin-bottom: 30px; }
.create-post textarea { width: 100%; border: 1px solid #e5e7eb; border-radius: 8px; padding: 15px; font-family: inherit; font-size: 15px; resize: vertical; margin-bottom: 15px; transition: border-color 0.2s; }
.create-post textarea:focus { outline: none; border-color: var(--green-500); }
.post-actions { display: flex; gap: 15px; align-items: center; justify-content: space-between; flex-wrap: wrap; }
.action-left { display: flex; gap: 10px; align-items: center; flex: 1; }
.action-left select, .action-left input[type="file"] { border: 1px solid #e5e7eb; border-radius: 8px; padding: 8px 12px; font-size: 13px; color: #475569; max-width: 200px; }
.btn-post { background: var(--green-700); color: white; border: none; padding: 10px 24px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: background 0.2s; }
.btn-post:hover { background: var(--green-900); }

/* Bài viết */
.post-card { background: white; border-radius: 12px; padding: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.06); margin-bottom: 25px; }
.post-header { display: flex; align-items: center; gap: 12px; margin-bottom: 15px; }
.post-avatar { width: 44px; height: 44px; border-radius: 50%; object-fit: cover; background: #e2e8f0; }
.post-user-info { flex: 1; }
.post-author { font-weight: 700; color: #1e293b; font-size: 15px; margin-bottom: 2px; display: block; }
.post-time { font-size: 12px; color: #94a3b8; }
.post-dest { display: inline-block; font-size: 11px; font-weight: 600; color: var(--green-700); background: #dcfce7; padding: 2px 8px; border-radius: 12px; margin-left: 8px; vertical-align: middle; }
.post-content { font-size: 15px; color: #334155; line-height: 1.6; margin-bottom: 15px; white-space: pre-wrap; }
.post-img-item { width: 100%; height: 100%; max-height: 300px; object-fit: cover; border-radius: 8px; background: #f8fafc; }
.post-images-grid { display: grid; gap: 8px; margin-bottom: 15px; }
.post-images-grid[data-count="1"] { grid-template-columns: 1fr; }
.post-images-grid[data-count="1"] .post-img-item { max-height: 500px; object-fit: contain; }
.post-images-grid[data-count="2"] { grid-template-columns: 1fr 1fr; }
.post-images-grid[data-count="3"] { grid-template-columns: 1fr 1fr; }
.post-images-grid[data-count="3"] .post-img-item:first-child { grid-column: 1 / -1; max-height: 350px; object-fit: cover; }
.post-images-grid[data-count="4"] { grid-template-columns: 1fr 1fr; }
.post-images-grid[data-count="4"] .post-img-item { max-height: 250px; }
.post-stats { display: flex; gap: 20px; border-top: 1px solid #f1f5f9; padding-top: 15px; }
.btn-stat { background: none; border: none; font-size: 14px; font-weight: 600; color: #64748b; cursor: pointer; display: flex; align-items: center; gap: 6px; padding: 5px 10px; border-radius: 6px; transition: background 0.2s; }
.btn-stat:hover { background: #f1f5f9; }
.btn-stat.liked { color: #ef4444; }

/* Bình luận */
.comments-section { margin-top: 15px; display: none; }
.comment-list { max-height: 300px; overflow-y: auto; margin-bottom: 15px; padding-right: 5px; }
.comment-item { display: flex; gap: 10px; margin-bottom: 12px; }
.comment-avatar { width: 32px; height: 32px; border-radius: 50%; object-fit: cover; }
.comment-bubble { background: #f1f5f9; padding: 10px 14px; border-radius: 14px; flex: 1; }
.comment-author { font-weight: 700; font-size: 13px; color: #1e293b; margin-bottom: 2px; }
.comment-text { font-size: 14px; color: #475569; }
.comment-time { font-size: 11px; color: #94a3b8; margin-top: 4px; }
.comment-form { display: flex; gap: 10px; }
.comment-input { flex: 1; border: 1px solid #e5e7eb; border-radius: 20px; padding: 8px 15px; font-size: 14px; }
.btn-comment { background: var(--green-700); color: white; border: none; padding: 8px 16px; border-radius: 20px; font-weight: 600; cursor: pointer; }

/* Trạng thái */
.empty-state { text-align: center; padding: 40px; color: #94a3b8; background: white; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.06); }
.login-prompt { background: #fffbeb; color: #b45309; padding: 15px; border-radius: 8px; text-align: center; margin-bottom: 30px; border: 1px solid #fde68a; font-weight: 600; }
</style>

<div class="forum-container">
    <div class="forum-header">
        <h1><?= __('forum_title') ?></h1>
        <p><?= __('forum_desc') ?></p>
    </div>

    <?php if ($user): ?>
    <div class="create-post">
        <form id="postForm" onsubmit="submitPost(event)">
            <textarea name="content" id="postContentEditor" rows="3" placeholder="<?= __('forum_post_content_ph') ?>"></textarea>
            <div class="post-actions">
                <div class="action-left">
                    <label style="font-size: 13px; font-weight: 600; color: #64748b;"><?= __('forum_post_image') ?></label>
                    <input type="file" name="images[]" multiple accept="image/*">
                    <select name="destination_id">
                        <option value=""><?= __('forum_post_dest_ph') ?></option>
                        <?php foreach ($destinations as $d): ?>
                            <option value="<?= $d['id'] ?>"><?= e($d['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn-post" id="btnSubmitPost"><?= __('forum_post_btn') ?></button>
            </div>
        </form>
    </div>
    <?php else: ?>
    <div class="login-prompt">
        <?= __('forum_login_prompt') ?> <a href="<?= url('/public/login.php') ?>" style="color: #b45309; text-decoration: underline;">Đăng nhập</a>
    </div>
    <?php endif; ?>

    <div id="postsList">
        <?php if (empty($checkins)): ?>
            <div class="empty-state">
                <div style="font-size: 40px; margin-bottom: 15px;">🏜️</div>
                <?= __('forum_no_posts') ?>
            </div>
        <?php else: ?>
            <?php foreach ($checkins as $c): ?>
                <div class="post-card" id="post-<?= $c['id'] ?>">
                    <div class="post-header">
                        <img src="<?= e(get_avatar($c['avatar'])) ?>" alt="Avatar" class="post-avatar">
                        <div class="post-user-info">
                            <span class="post-author">
                                <?= e($c['full_name']) ?>
                                <?php if ($c['destination_id']): ?>
                                    <?php $dn = (($_SESSION['lang'] ?? 'vi') === 'en' && !empty($c['dest_name_en'])) ? $c['dest_name_en'] : $c['dest_name']; ?>
                                    <span class="post-dest">📍 <?= e($dn) ?></span>
                                <?php endif; ?>
                            </span>
                            <span class="post-time"><?= date('d/m/Y H:i', strtotime($c['created_at'])) ?></span>
                        </div>
                        <?php if ($user && (int)$user['id'] === (int)$c['user_id']): ?>
                        <div style="display:flex; gap:10px;">
                            <button onclick="editPost(<?= $c['id'] ?>)" style="background:none;border:none;color:#64748b;cursor:pointer;font-size:13px;padding:5px;">✏️</button>
                            <button onclick="deletePost(<?= $c['id'] ?>)" style="background:none;border:none;color:#ef4444;cursor:pointer;font-size:13px;padding:5px;">🗑️</button>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="post-content ck-content" id="post-content-<?= $c['id'] ?>" style="word-wrap: break-word; overflow-wrap: break-word;"><?= strip_tags($c['content']) !== $c['content'] ? $c['content'] : nl2br(e($c['content'])) ?></div>
                    
                    <?php
                    $postImages = [];
                    if ($c['image_url']) {
                        $decoded = json_decode($c['image_url'], true);
                        if (is_array($decoded)) {
                            $postImages = $decoded;
                        } else {
                            $postImages = [$c['image_url']];
                        }
                    }
                    ?>
                    <div id="post-edit-<?= $c['id'] ?>" style="display:none; margin-bottom:15px;">
                        <textarea id="edit-text-<?= $c['id'] ?>" style="width:100%; border:1px solid #e5e7eb; border-radius:8px; padding:10px; font-family:inherit; font-size:15px; resize:vertical; margin-bottom:10px;"><?= e($c['content']) ?></textarea>
                        
                        <div style="margin-bottom:10px; padding:10px; background:#f8fafc; border-radius:8px;">
                            <label style="font-size: 13px; font-weight: 600; color: #64748b; display:block; margin-bottom:5px;">Cập nhật ảnh:</label>
                            
                            <?php if (!empty($postImages)): ?>
                                <div style="display:flex; gap:10px; flex-wrap:wrap; margin-bottom:10px;">
                                    <?php foreach ($postImages as $imgUrl): ?>
                                        <div style="position:relative; width:80px; height:80px;">
                                            <img src="<?= e($imgUrl) ?>" style="width:100%; height:100%; object-fit:cover; border-radius:6px;">
                                            <label style="position:absolute; top:2px; right:2px; background:rgba(255,255,255,0.9); padding:2px; border-radius:4px; font-size:10px; cursor:pointer;">
                                                <input type="checkbox" value="<?= e($imgUrl) ?>" class="remove-img-cb-<?= $c['id'] ?>"> Xóa
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            
                            <input type="file" id="edit-image-<?= $c['id'] ?>" name="images[]" multiple accept="image/*" style="border: 1px solid #e5e7eb; border-radius: 8px; padding: 5px; font-size: 13px; background:white; width:100%; max-width:300px; margin-bottom:8px;">
                        </div>

                        <div style="text-align:right;">
                            <button onclick="cancelEdit(<?= $c['id'] ?>)" style="background:#f1f5f9; color:#475569; border:none; padding:6px 12px; border-radius:6px; font-size:13px; font-weight:600; cursor:pointer;">Hủy</button>
                            <button onclick="saveEdit(<?= $c['id'] ?>)" id="btnSaveEdit-<?= $c['id'] ?>" style="background:var(--green-700); color:white; border:none; padding:6px 12px; border-radius:6px; font-size:13px; font-weight:600; cursor:pointer; margin-left:5px;">Lưu</button>
                        </div>
                    </div>
                    <?php if (!empty($postImages)): ?>
                        <?php $imgCount = count($postImages); ?>
                        <div class="post-images-grid" data-count="<?= $imgCount > 4 ? 4 : $imgCount ?>">
                            <?php foreach (array_slice($postImages, 0, 4) as $imgUrl): ?>
                                <img src="<?= e($imgUrl) ?>" alt="Photo" class="post-img-item">
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="post-stats">
                        <button class="btn-stat <?= isset($userLikes[$c['id']]) ? 'liked' : '' ?>" onclick="toggleLike(<?= $c['id'] ?>, this)">
                            <?= isset($userLikes[$c['id']]) ? '❤️' : '🤍' ?> <span class="like-count"><?= $c['likes_count'] ?></span> <?= __('forum_likes') ?>
                        </button>
                        <button class="btn-stat" onclick="toggleComments(<?= $c['id'] ?>)">
                            💬 <?= __('forum_comments') ?>
                        </button>
                    </div>

                    <div class="comments-section" id="comments-<?= $c['id'] ?>">
                        <div class="comment-list" id="comment-list-<?= $c['id'] ?>">
                            <!-- Comments will be loaded here via PHP or JS. For simplicity, fetch all published comments for this post now -->
                            <?php
                            $stmtC = $db->prepare("SELECT cc.*, u.full_name, u.avatar FROM checkin_comments cc JOIN users u ON cc.user_id = u.id WHERE cc.checkin_id = ? AND cc.status = 'published' ORDER BY cc.created_at ASC");
                            $stmtC->execute([$c['id']]);
                            $comments = $stmtC->fetchAll();
                            foreach ($comments as $cm):
                            ?>
                                <div class="comment-item">
                                    <img src="<?= e(get_avatar($cm['avatar'])) ?>" class="comment-avatar">
                                    <div class="comment-bubble">
                                        <div class="comment-author"><?= e($cm['full_name']) ?></div>
                                        <div class="comment-text"><?= nl2br(e($cm['content'])) ?></div>
                                        <div class="comment-time"><?= date('d/m H:i', strtotime($cm['created_at'])) ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <?php if ($user): ?>
                        <form class="comment-form" onsubmit="submitComment(event, <?= $c['id'] ?>)">
                            <input type="text" class="comment-input" id="comment-input-<?= $c['id'] ?>" placeholder="<?= __('forum_write_comment') ?>" required>
                            <button type="submit" class="btn-comment"><?= __('forum_send_comment') ?></button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<style>
.ck-editor__editable { min-height: 120px; }
.ck-content { font-family: inherit; font-size: 15px; }
</style>
<script src="https://cdn.ckeditor.com/ckeditor5/39.0.1/classic/ckeditor.js"></script>
<script>
let postEditor;
document.addEventListener("DOMContentLoaded", function() {
    if (document.querySelector('#postContentEditor')) {
        ClassicEditor
            .create( document.querySelector( '#postContentEditor' ), {
                toolbar: [ 'undo', 'redo', '|', 'heading', '|', 'bold', 'italic', 'link', 'blockQuote', 'insertTable', 'mediaEmbed', '|', 'bulletedList', 'numberedList', 'outdent', 'indent' ]
            } )
            .then( editor => { window.postEditor = editor; } )
            .catch( error => { console.error( error ); } );
    }
});

async function submitPost(e) {
    e.preventDefault();
    <?php if (!$user): ?>
    alert('<?= __('forum_login_prompt') ?>');
    return;
    <?php endif; ?>

    const form = e.target;
    const btn = document.getElementById('btnSubmitPost');
    const formData = new FormData(form);
    if (window.postEditor) {
        formData.set('content', window.postEditor.getData());
    }
    formData.append('action', 'post');

    btn.disabled = true;
    btn.textContent = '...';

    try {
        const res = await fetch('<?= url("/api/forum_action.php") ?>', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Error');
        }
    } catch(err) {
        alert('Lỗi kết nối mạng.');
    } finally {
        btn.disabled = false;
        btn.textContent = '<?= __('forum_post_btn') ?>';
    }
}

async function toggleLike(checkinId, btnEl) {
    <?php if (!$user): ?>
    alert('<?= __('forum_login_prompt') ?>');
    return;
    <?php endif; ?>

    const formData = new FormData();
    formData.append('action', 'like');
    formData.append('checkin_id', checkinId);

    try {
        const res = await fetch('<?= url("/api/forum_action.php") ?>', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        if (data.success) {
            const countEl = btnEl.querySelector('.like-count');
            let count = parseInt(countEl.textContent);
            if (data.liked) {
                btnEl.classList.add('liked');
                btnEl.innerHTML = `❤️ <span class="like-count">${count + 1}</span> <?= __('forum_likes') ?>`;
            } else {
                btnEl.classList.remove('liked');
                btnEl.innerHTML = `🤍 <span class="like-count">${count - 1}</span> <?= __('forum_likes') ?>`;
            }
        }
    } catch(err) {}
}

function toggleComments(checkinId) {
    const el = document.getElementById('comments-' + checkinId);
    if (el.style.display === 'block') {
        el.style.display = 'none';
    } else {
        el.style.display = 'block';
    }
}

async function submitComment(e, checkinId) {
    e.preventDefault();
    const input = document.getElementById('comment-input-' + checkinId);
    const content = input.value.trim();
    if (!content) return;

    const btn = e.target.querySelector('button');
    btn.disabled = true;

    const formData = new FormData();
    formData.append('action', 'comment');
    formData.append('checkin_id', checkinId);
    formData.append('content', content);

    try {
        const res = await fetch('<?= url("/api/forum_action.php") ?>', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        if (data.success) {
            // Append locally for quick feedback
            const list = document.getElementById('comment-list-' + checkinId);
            const div = document.createElement('div');
            div.className = 'comment-item';
            div.innerHTML = `
                <img src="<?= e(get_avatar($user['avatar'] ?? null)) ?>" class="comment-avatar">
                <div class="comment-bubble">
                    <div class="comment-author"><?= e($user['full_name'] ?? 'Tôi') ?></div>
                    <div class="comment-text">${content.replace(/\n/g, '<br>')}</div>
                    <div class="comment-time">Vừa xong</div>
                </div>
            `;
            list.appendChild(div);
            input.value = '';
            list.scrollTop = list.scrollHeight;
        } else {
            alert(data.message || 'Error');
        }
    } catch(err) {
        alert('Lỗi kết nối mạng.');
    } finally {
        btn.disabled = false;
    }
}

let editEditors = {};
function editPost(checkinId) {
    document.getElementById('post-content-' + checkinId).style.display = 'none';
    document.getElementById('post-edit-' + checkinId).style.display = 'block';
    
    if (!editEditors[checkinId]) {
        ClassicEditor
            .create( document.querySelector( '#edit-text-' + checkinId ), {
                toolbar: [ 'undo', 'redo', '|', 'heading', '|', 'bold', 'italic', 'link', 'blockQuote', 'insertTable', 'mediaEmbed', '|', 'bulletedList', 'numberedList', 'outdent', 'indent' ]
            } )
            .then( editor => { editEditors[checkinId] = editor; } )
            .catch( error => { console.error( error ); } );
    }
}

function cancelEdit(checkinId) {
    document.getElementById('post-content-' + checkinId).style.display = 'block';
    document.getElementById('post-edit-' + checkinId).style.display = 'none';
}

async function saveEdit(checkinId) {
    let content = '';
    if (editEditors[checkinId]) {
        content = editEditors[checkinId].getData().trim();
    } else {
        content = document.getElementById('edit-text-' + checkinId).value.trim();
    }
    if (!content) {
        alert('Nội dung không được để trống!');
        return;
    }

    const btn = document.getElementById('btnSaveEdit-' + checkinId);
    btn.disabled = true;
    btn.textContent = '...';

    const formData = new FormData();
    formData.append('action', 'edit_post');
    formData.append('checkin_id', checkinId);
    formData.append('content', content);

    const imageInput = document.getElementById('edit-image-' + checkinId);
    if (imageInput && imageInput.files.length > 0) {
        for (let i = 0; i < imageInput.files.length; i++) {
            formData.append('images[]', imageInput.files[i]);
        }
    }
    const removeCbs = document.querySelectorAll('.remove-img-cb-' + checkinId);
    removeCbs.forEach(cb => {
        if (cb.checked) {
            formData.append('remove_images[]', cb.value);
        }
    });

    try {
        const res = await fetch('<?= url("/api/forum_action.php") ?>', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Error');
            btn.disabled = false;
            btn.textContent = 'Lưu';
        }
    } catch(err) {
        alert('Lỗi kết nối mạng.');
        btn.disabled = false;
        btn.textContent = 'Lưu';
    }
}

async function deletePost(checkinId) {
    if (!confirm('Bạn có chắc chắn muốn xóa bài viết này không?')) return;

    const formData = new FormData();
    formData.append('action', 'delete_post');
    formData.append('checkin_id', checkinId);

    try {
        const res = await fetch('<?= url("/api/forum_action.php") ?>', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Error');
        }
    } catch(err) {
        alert('Lỗi kết nối mạng.');
    }
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
