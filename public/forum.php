<?php
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = __('forum_title');
$db = getDB();
$user = currentUser();

$destinations = getAllDestinations();

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

$userLikes = [];
if ($user) {
    $likes = $db->query("SELECT checkin_id FROM checkin_likes WHERE user_id = {$user['id']}")->fetchAll(PDO::FETCH_COLUMN);
    $userLikes = array_fill_keys($likes, true);
}

include __DIR__ . '/../includes/header.php';
?>
<style>
/* ── Forum News Feed Design ── */
.feed-layout {
  display: grid;
  grid-template-columns: 1fr 320px;
  gap: 28px;
  max-width: 1100px;
  margin: 32px auto 60px;
  align-items: start;
}

/* ── Compose Box ── */
.compose-box {
  background: #fff;
  border-radius: 20px;
  padding: 20px;
  border: 1px solid var(--line);
  box-shadow: 0 4px 20px rgba(61,35,13,.06);
  margin-bottom: 20px;
}
.compose-trigger {
  display: flex;
  align-items: center;
  gap: 12px;
  cursor: pointer;
}
.compose-avatar {
  width: 44px; height: 44px;
  border-radius: 50%;
  object-fit: cover;
  border: 2px solid var(--line);
  flex-shrink: 0;
}
.compose-placeholder {
  flex: 1;
  background: #FAF6F0;
  border: 1px solid var(--line);
  border-radius: 24px;
  padding: 11px 18px;
  font-size: 14px;
  color: var(--coffee-light);
  font-family: 'Plus Jakarta Sans', sans-serif;
  cursor: pointer;
  transition: border-color .2s, background .2s;
}
.compose-placeholder:hover { border-color: var(--basalt-red); background: #fff; }

.compose-expanded { display: none; margin-top: 14px; }
.compose-textarea {
  width: 100%;
  min-height: 100px;
  border: 1px solid var(--line);
  border-radius: 14px;
  padding: 14px 16px;
  font-family: 'Plus Jakarta Sans', sans-serif;
  font-size: 15px;
  resize: none;
  background: #FAF6F0;
  color: var(--text-dark);
  outline: none;
  transition: border-color .2s;
}
.compose-textarea:focus { border-color: var(--basalt-red); background: #fff; }
.compose-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 10px;
  margin-top: 12px;
}
.compose-tools {
  display: flex;
  gap: 8px;
  align-items: center;
}
.compose-tool-btn {
  background: #FAF6F0;
  border: 1px solid var(--line);
  border-radius: 10px;
  padding: 7px 14px;
  font-size: 13px;
  font-weight: 600;
  color: var(--coffee-mid);
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 5px;
}
.compose-tool-btn:hover { border-color: var(--basalt-red); color: var(--basalt-red); }
.compose-dest-select {
  border: 1px solid var(--line);
  border-radius: 10px;
  padding: 7px 12px;
  font-size: 13px;
  font-family: 'Plus Jakarta Sans', sans-serif;
  background: #FAF6F0;
  color: var(--coffee-mid);
  outline: none;
  max-width: 180px;
}
.compose-submit-btn {
  background: var(--basalt-red);
  color: #fff;
  border: none;
  border-radius: 24px;
  padding: 9px 24px;
  font-size: 14px;
  font-weight: 700;
  cursor: pointer;
  font-family: 'Plus Jakarta Sans', sans-serif;
  transition: background .2s, transform .15s;
}
.compose-submit-btn:hover { background: var(--basalt-dark); transform: translateY(-1px); }
.compose-submit-btn:disabled { opacity: .6; cursor: not-allowed; transform: none; }

/* ── Feed Posts ── */
.feed-post {
  background: #fff;
  border-radius: 20px;
  border: 1px solid var(--line);
  box-shadow: 0 4px 20px rgba(61,35,13,.05);
  margin-bottom: 20px;
  overflow: hidden;
  transition: box-shadow .2s;
}
.feed-post:hover { box-shadow: 0 8px 32px rgba(61,35,13,.1); }

.post-meta-bar {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 18px 20px 12px;
}
.post-avatar-wrap {
  position: relative;
  flex-shrink: 0;
}
.post-avatar {
  width: 46px; height: 46px;
  border-radius: 50%;
  object-fit: cover;
  border: 2px solid var(--line);
}
.post-meta-info { flex: 1; min-width: 0; }
.post-author-name {
  font-size: 15px;
  font-weight: 700;
  color: var(--coffee-brown);
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}
.post-dest-chip {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  font-size: 12px;
  font-weight: 600;
  color: var(--jungle-dark);
  background: rgba(30,86,49,.1);
  border: 1px solid rgba(30,86,49,.2);
  border-radius: 20px;
  padding: 2px 8px;
}
.post-time-str {
  font-size: 12px;
  color: #94a3b8;
  margin-top: 2px;
}
.post-menu-btn {
  background: none;
  border: none;
  width: 34px; height: 34px;
  border-radius: 50%;
  cursor: pointer;
  font-size: 18px;
  display: flex; align-items: center; justify-content: center;
  color: #94a3b8;
  transition: background .15s;
}
.post-menu-btn:hover { background: #f1f5f9; color: var(--coffee-brown); }

.post-body {
  padding: 0 20px 14px;
  font-size: 15px;
  color: #334155;
  line-height: 1.7;
  word-wrap: break-word;
}
.post-body p { margin: 0 0 8px; }
.post-body p:last-child { margin-bottom: 0; }

/* Post image grids */
.post-img-grid {
  display: grid;
  gap: 3px;
  overflow: hidden;
  cursor: pointer;
}
.post-img-grid[data-count="1"] { grid-template-columns: 1fr; }
.post-img-grid[data-count="1"] img { height: 440px; object-fit: cover; width: 100%; }
.post-img-grid[data-count="2"] { grid-template-columns: 1fr 1fr; }
.post-img-grid[data-count="2"] img { height: 280px; object-fit: cover; width: 100%; }
.post-img-grid[data-count="3"] { grid-template-columns: 1fr 1fr; }
.post-img-grid[data-count="3"] img:first-child { grid-column: 1 / -1; height: 280px; object-fit: cover; width: 100%; }
.post-img-grid[data-count="3"] img:not(:first-child) { height: 180px; object-fit: cover; width: 100%; }
.post-img-grid[data-count="4"] { grid-template-columns: 1fr 1fr; }
.post-img-grid[data-count="4"] img { height: 200px; object-fit: cover; width: 100%; }
.post-img-grid img { transition: opacity .2s; }
.post-img-grid img:hover { opacity: .9; }

/* Reactions bar */
.post-reactions-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 4px 20px 0;
  border-top: 1px solid #f1f5f9;
}
.post-reaction-count {
  font-size: 13px;
  color: #94a3b8;
  padding: 8px 0;
}
.post-actions-row {
  display: flex;
  border-top: 1px solid #f1f5f9;
  margin: 0 20px;
}
.post-action-btn {
  flex: 1;
  background: none;
  border: none;
  padding: 10px;
  font-size: 13px;
  font-weight: 700;
  color: #64748b;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  border-radius: 8px;
  margin: 6px 2px;
  transition: background .15s, color .15s;
}
.post-action-btn:hover { background: #f8fafc; color: var(--coffee-brown); }
.post-action-btn.liked { color: var(--basalt-red); }
.post-action-btn.liked svg, .post-action-btn.liked .like-icon { transform: scale(1.15); }

/* Comments section */
.comments-panel {
  display: none;
  padding: 0 20px 16px;
  border-top: 1px solid #f1f5f9;
  margin-top: 2px;
}
.comment-list { padding-top: 12px; }
.comment-row {
  display: flex;
  gap: 10px;
  margin-bottom: 12px;
}
.comment-avatar {
  width: 32px; height: 32px;
  border-radius: 50%;
  object-fit: cover;
  flex-shrink: 0;
}
.comment-bubble {
  background: #f1f5f9;
  border-radius: 16px;
  padding: 10px 14px;
  flex: 1;
}
.comment-bubble .author { font-size: 13px; font-weight: 700; color: var(--coffee-brown); }
.comment-bubble .text { font-size: 14px; color: #475569; margin-top: 2px; }
.comment-bubble .ctime { font-size: 11px; color: #94a3b8; margin-top: 4px; }

.comment-compose {
  display: flex;
  gap: 10px;
  margin-top: 12px;
  align-items: center;
}
.comment-compose img { width: 32px; height: 32px; border-radius: 50%; object-fit: cover; }
.comment-input-wrap { flex: 1; position: relative; }
.comment-input {
  width: 100%;
  border: 1px solid var(--line);
  border-radius: 20px;
  padding: 9px 44px 9px 16px;
  font-size: 14px;
  font-family: 'Plus Jakarta Sans', sans-serif;
  background: #f8fafc;
  outline: none;
  transition: border-color .2s, background .2s;
}
.comment-input:focus { border-color: var(--basalt-red); background: #fff; }
.comment-send-btn {
  position: absolute;
  right: 6px; top: 50%;
  transform: translateY(-50%);
  background: var(--basalt-red);
  border: none;
  width: 30px; height: 30px;
  border-radius: 50%;
  color: #fff;
  cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  font-size: 14px;
}
.comment-send-btn:hover { background: var(--basalt-dark); }

/* Inline edit form */
.inline-edit-form {
  display: none;
  padding: 0 20px 16px;
}
.inline-edit-form textarea {
  width: 100%;
  border: 1px solid var(--line);
  border-radius: 12px;
  padding: 12px;
  font-family: 'Plus Jakarta Sans', sans-serif;
  font-size: 15px;
  resize: vertical;
  background: #FAF6F0;
  outline: none;
}
.inline-edit-form textarea:focus { border-color: var(--basalt-red); background: #fff; }
.inline-edit-actions {
  display: flex; gap: 8px; justify-content: flex-end;
  margin-top: 10px;
}

/* ── Right Sidebar ── */
.feed-sidebar {}
.sidebar-widget {
  background: #fff;
  border-radius: 20px;
  border: 1px solid var(--line);
  box-shadow: 0 4px 20px rgba(61,35,13,.05);
  padding: 20px;
  margin-bottom: 20px;
}
.sidebar-widget h3 {
  font-size: 15px;
  font-weight: 800;
  color: var(--coffee-brown);
  margin: 0 0 14px;
  padding-bottom: 10px;
  border-bottom: 2px dashed var(--line);
}
.sidebar-dest-list { display: flex; flex-direction: column; gap: 8px; }
.sidebar-dest-item {
  display: flex; align-items: center; gap: 10px;
  padding: 8px 10px;
  border-radius: 10px;
  text-decoration: none;
  color: var(--text-dark);
  font-size: 13px;
  font-weight: 600;
  transition: background .15s;
}
.sidebar-dest-item:hover { background: #FAF6F0; color: var(--basalt-red); }
.sidebar-dest-icon { font-size: 18px; }

.login-cta {
  background: linear-gradient(135deg, var(--coffee-brown) 0%, var(--basalt-red) 100%);
  border-radius: 20px;
  padding: 24px 20px;
  color: #fff;
  text-align: center;
  margin-bottom: 20px;
}
.login-cta h3 { font-size: 16px; margin: 0 0 8px; color: #fff; font-family: 'Playfair Display', serif; }
.login-cta p { font-size: 13px; color: rgba(255,255,255,.75); margin: 0 0 16px; }
.login-cta .cta-btns { display: flex; gap: 8px; justify-content: center; flex-wrap: wrap; }
.login-cta .cta-btn { padding: 8px 18px; border-radius: 20px; font-size: 13px; font-weight: 700; text-decoration: none; }
.login-cta .cta-btn.primary { background: var(--brocade-gold); color: var(--coffee-brown); }
.login-cta .cta-btn.ghost { background: rgba(255,255,255,.15); color: #fff; border: 1px solid rgba(255,255,255,.3); }
.login-cta .cta-btn:hover { opacity: .9; transform: translateY(-1px); }

/* Empty feed state */
.feed-empty {
  background: #fff;
  border-radius: 20px;
  border: 1px solid var(--line);
  padding: 60px 20px;
  text-align: center;
}
.feed-empty-icon { font-size: 52px; margin-bottom: 16px; }
.feed-empty h3 { font-size: 20px; color: var(--coffee-brown); margin: 0 0 8px; }
.feed-empty p { color: #94a3b8; font-size: 14px; margin: 0; }

/* Mobile responsive */
@media (max-width: 860px) {
  .feed-layout { grid-template-columns: 1fr; }
  .feed-sidebar { order: -1; }
  .sidebar-widget.destinations-widget { display: none; }
}
@media (max-width: 600px) {
  .feed-layout { margin: 16px auto 40px; gap: 16px; }
  .post-img-grid[data-count="1"] img { height: 280px; }
}
</style>

<div class="feed-layout">
  <!-- ── MAIN FEED ── -->
  <main id="main-feed">

    <?php if ($user): ?>
    <!-- Compose Box -->
    <div class="compose-box">
      <div class="compose-trigger" id="composeTrigger">
        <img src="<?= e(get_avatar($user['avatar'])) ?>" class="compose-avatar" alt="Avatar">
        <div class="compose-placeholder" id="composePlaceholder"><?= __('forum_post_content_ph') ?></div>
      </div>
      <div class="compose-expanded" id="composeExpanded">
        <form id="postForm" onsubmit="submitPost(event)" enctype="multipart/form-data">
          <textarea id="postContentInput" name="content" class="compose-textarea" placeholder="<?= __('forum_post_content_ph') ?>" rows="3"></textarea>
          <div id="imagePreview" style="display:none; gap:8px; flex-wrap:wrap; margin-top:10px; display:none;"></div>
          <div class="compose-footer">
            <div class="compose-tools">
              <label class="compose-tool-btn" style="cursor:pointer;">
                <span>📷</span> Thêm ảnh
                <input type="file" name="images[]" multiple accept="image/*" id="imgInput" style="display:none" onchange="previewImages(this)">
              </label>
              <select name="destination_id" class="compose-dest-select">
                <option value=""><?= __('forum_post_dest_ph') ?></option>
                <?php foreach ($destinations as $d): ?>
                  <option value="<?= $d['id'] ?>"><?= e($d['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <button type="submit" class="compose-submit-btn" id="btnSubmitPost"><?= __('forum_post_btn') ?></button>
          </div>
        </form>
      </div>
    </div>
    <?php else: ?>
    <!-- Not logged in CTA -->
    <div class="login-cta">
      <h3>Chia sẻ trải nghiệm của bạn tại Đắk Lắk</h3>
      <p>Đăng nhập để đăng bài, gửi ảnh và nhận phản hồi từ cộng đồng du lịch.</p>
      <div class="cta-btns">
        <a href="<?= url('/public/login.php') ?>" class="cta-btn primary">Đăng nhập</a>
        <a href="<?= url('/public/register.php') ?>" class="cta-btn ghost">Đăng ký</a>
      </div>
    </div>
    <?php endif; ?>

    <!-- Feed Posts -->
    <div id="postsList">
      <?php if (empty($checkins)): ?>
        <div class="feed-empty">
          <div class="feed-empty-icon">🌄</div>
          <h3>Chưa có bài viết nào</h3>
          <p><?= __('forum_no_posts') ?></p>
        </div>
      <?php else: ?>
        <?php foreach ($checkins as $c): ?>
          <?php
          $postImages = [];
          if ($c['image_url']) {
            $decoded = json_decode($c['image_url'], true);
            $postImages = is_array($decoded) ? $decoded : [$c['image_url']];
          }
          $destName = (($_SESSION['lang'] ?? 'vi') === 'en' && !empty($c['dest_name_en'])) ? $c['dest_name_en'] : ($c['dest_name'] ?? '');
          $isOwner = $user && (int)$user['id'] === (int)$c['user_id'];
          $isLiked = isset($userLikes[$c['id']]);
          ?>
          <article class="feed-post" id="post-<?= $c['id'] ?>">
            <!-- Meta bar -->
            <div class="post-meta-bar">
              <div class="post-avatar-wrap">
                <img src="<?= e(get_avatar($c['avatar'])) ?>" class="post-avatar" alt="<?= e($c['full_name']) ?>" onerror="this.onerror=null;this.src='<?= url('/assets/images/placeholder.svg') ?>';">
              </div>
              <div class="post-meta-info">
                <div class="post-author-name">
                  <?= e($c['full_name']) ?>
                  <?php if ($destName): ?>
                    <span class="post-dest-chip">📍 <?= e($destName) ?></span>
                  <?php endif; ?>
                </div>
                <div class="post-time-str"><?= date('H:i · d/m/Y', strtotime($c['created_at'])) ?></div>
              </div>
              <?php if ($isOwner): ?>
              <div style="position:relative;">
                <button class="post-menu-btn" onclick="togglePostMenu(<?= $c['id'] ?>)" title="Tùy chọn">⋯</button>
                <div id="post-menu-<?= $c['id'] ?>" style="display:none; position:absolute; right:0; top:38px; background:#fff; border:1px solid var(--line); border-radius:12px; padding:6px; min-width:140px; box-shadow:0 8px 24px rgba(0,0,0,.1); z-index:10;">
                  <button onclick="editPost(<?= $c['id'] ?>)" style="display:flex; align-items:center; gap:8px; width:100%; padding:8px 12px; border:none; background:none; cursor:pointer; border-radius:8px; font-size:13px; font-weight:600; color:var(--coffee-brown);">✏️ Chỉnh sửa</button>
                  <button onclick="deletePost(<?= $c['id'] ?>)" style="display:flex; align-items:center; gap:8px; width:100%; padding:8px 12px; border:none; background:none; cursor:pointer; border-radius:8px; font-size:13px; font-weight:600; color:#ef4444;">🗑️ Xóa bài</button>
                </div>
              </div>
              <?php endif; ?>
            </div>

            <!-- Post content -->
            <div class="post-body" id="post-content-<?= $c['id'] ?>">
              <?= strip_tags($c['content']) !== $c['content'] ? $c['content'] : nl2br(e($c['content'])) ?>
            </div>

            <!-- Inline edit -->
            <div class="inline-edit-form" id="post-edit-<?= $c['id'] ?>">
              <textarea id="edit-text-<?= $c['id'] ?>" rows="4"><?= e($c['content']) ?></textarea>
              <?php if (!empty($postImages)): ?>
                <div style="display:flex; gap:8px; flex-wrap:wrap; margin:10px 0;">
                  <?php foreach ($postImages as $imgUrl): ?>
                    <div style="position:relative; width:72px; height:72px;">
                      <img src="<?= e($imgUrl) ?>" style="width:100%; height:100%; object-fit:cover; border-radius:8px;" onerror="this.onerror=null;this.src='<?= url('/assets/images/placeholder.svg') ?>';">
                      <label style="position:absolute; top:2px; right:2px; background:rgba(255,255,255,.9); padding:1px 4px; border-radius:4px; font-size:10px; cursor:pointer; font-weight:700;">
                        <input type="checkbox" value="<?= e($imgUrl) ?>" class="remove-img-cb-<?= $c['id'] ?>"> ✕
                      </label>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
              <input type="file" id="edit-image-<?= $c['id'] ?>" multiple accept="image/*" style="font-size:13px; margin-bottom:8px;">
              <div class="inline-edit-actions">
                <button onclick="cancelEdit(<?= $c['id'] ?>)" style="padding:7px 16px; border-radius:20px; border:1px solid var(--line); background:#f1f5f9; font-size:13px; font-weight:600; cursor:pointer; color:#475569;">Hủy</button>
                <button onclick="saveEdit(<?= $c['id'] ?>)" id="btnSaveEdit-<?= $c['id'] ?>" style="padding:7px 16px; border-radius:20px; border:none; background:var(--basalt-red); color:#fff; font-size:13px; font-weight:700; cursor:pointer;">Lưu</button>
              </div>
            </div>

            <!-- Images -->
            <?php if (!empty($postImages)): ?>
              <?php $imgCount = min(count($postImages), 4); ?>
              <div class="post-img-grid" data-count="<?= $imgCount ?>">
                <?php foreach (array_slice($postImages, 0, 4) as $imgUrl): ?>
                  <img src="<?= e($imgUrl) ?>" alt="Ảnh bài viết" loading="lazy" onerror="this.onerror=null;this.src='<?= url('/assets/images/placeholder.svg') ?>';">
                <?php endforeach; ?>
              </div>
            <?php endif; ?>

            <!-- Reaction count -->
            <div class="post-reactions-bar">
              <span class="post-reaction-count">
                <?php if ($c['likes_count'] > 0): ?><?= $c['likes_count'] ?> lượt thích<?php endif; ?>
              </span>
            </div>

            <!-- Action buttons -->
            <div class="post-actions-row">
              <button class="post-action-btn <?= $isLiked ? 'liked' : '' ?>" id="like-btn-<?= $c['id'] ?>" onclick="toggleLike(<?= $c['id'] ?>, this)">
                <span class="like-icon"><?= $isLiked ? '❤️' : '🤍' ?></span>
                <span class="like-count"><?= (int)$c['likes_count'] ?></span> <?= __('forum_likes') ?>
              </button>
              <button class="post-action-btn" onclick="toggleComments(<?= $c['id'] ?>)">
                💬 <?= __('forum_comments') ?>
              </button>
            </div>

            <!-- Comments panel -->
            <div class="comments-panel" id="comments-<?= $c['id'] ?>">
              <div class="comment-list" id="comment-list-<?= $c['id'] ?>">
                <?php
                $stmtC = $db->prepare("SELECT cc.*, u.full_name, u.avatar FROM checkin_comments cc JOIN users u ON cc.user_id = u.id WHERE cc.checkin_id = ? AND cc.status = 'published' ORDER BY cc.created_at ASC");
                $stmtC->execute([$c['id']]);
                $comments = $stmtC->fetchAll();
                foreach ($comments as $cm):
                ?>
                  <div class="comment-row">
                    <img src="<?= e(get_avatar($cm['avatar'])) ?>" class="comment-avatar" alt="<?= e($cm['full_name']) ?>" onerror="this.onerror=null;this.src='<?= url('/assets/images/placeholder.svg') ?>';">
                    <div class="comment-bubble">
                      <div class="author"><?= e($cm['full_name']) ?></div>
                      <div class="text"><?= nl2br(e($cm['content'])) ?></div>
                      <div class="ctime"><?= date('d/m H:i', strtotime($cm['created_at'])) ?></div>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>

              <?php if ($user): ?>
              <form class="comment-compose" onsubmit="submitComment(event, <?= $c['id'] ?>)">
                <img src="<?= e(get_avatar($user['avatar'])) ?>" alt="Avatar" onerror="this.onerror=null;this.src='<?= url('/assets/images/placeholder.svg') ?>';">
                <div class="comment-input-wrap">
                  <input type="text" class="comment-input" id="comment-input-<?= $c['id'] ?>" placeholder="<?= __('forum_write_comment') ?>" required>
                  <button type="submit" class="comment-send-btn">➤</button>
                </div>
              </form>
              <?php endif; ?>
            </div>
          </article>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </main>

  <!-- ── RIGHT SIDEBAR ── -->
  <aside class="feed-sidebar">
    <?php if (!$user): ?>
    <div class="login-cta">
      <h3>Tham gia cộng đồng</h3>
      <p>Chia sẻ trải nghiệm du lịch và kết nối với mọi người.</p>
      <div class="cta-btns">
        <a href="<?= url('/public/login.php') ?>" class="cta-btn primary">Đăng nhập</a>
        <a href="<?= url('/public/register.php') ?>" class="cta-btn ghost">Đăng ký</a>
      </div>
    </div>
    <?php endif; ?>

    <div class="sidebar-widget destinations-widget">
      <h3>🗺️ Khám phá điểm đến</h3>
      <div class="sidebar-dest-list">
        <?php foreach (array_slice($destinations, 0, 8) as $d): ?>
          <a href="<?= url('/diem-den/' . $d['slug']) ?>" class="sidebar-dest-item">
            <span class="sidebar-dest-icon">📍</span>
            <span><?= e($d['name']) ?></span>
          </a>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="sidebar-widget">
      <h3>🐘 Trợ lý Voi Bản Đôn</h3>
      <p style="font-size:13px; color:#64748b; margin:0 0 12px;">Hỏi về địa điểm, ăn uống, lịch trình — Voi Bản Đôn trả lời ngay.</p>
      <a href="<?= url('/public/chatbot.php') ?>" class="compose-submit-btn" style="display:block; text-align:center; text-decoration:none; padding:10px;">💬 Hỏi Ama Guide</a>
    </div>
  </aside>
</div>

<script>
// Compose expand/collapse
const composeTrigger = document.getElementById('composeTrigger');
const composeExpanded = document.getElementById('composeExpanded');
const composePlaceholder = document.getElementById('composePlaceholder');

if (composeTrigger) {
  composePlaceholder.addEventListener('click', function() {
    composeExpanded.style.display = 'block';
    composePlaceholder.style.display = 'none';
    document.getElementById('postContentInput').focus();
  });

  document.addEventListener('click', function(e) {
    const composeBox = document.querySelector('.compose-box');
    if (composeBox && !composeBox.contains(e.target) && composeExpanded.style.display === 'block') {
      // only collapse if textarea is empty
      if (!document.getElementById('postContentInput').value.trim()) {
        composeExpanded.style.display = 'none';
        composePlaceholder.style.display = '';
      }
    }
  });
}

function previewImages(input) {
  const preview = document.getElementById('imagePreview');
  if (!preview) return;
  preview.innerHTML = '';
  if (input.files.length > 0) {
    preview.style.display = 'flex';
    Array.from(input.files).forEach(function(file) {
      const url = URL.createObjectURL(file);
      const img = document.createElement('img');
      img.src = url;
      img.style.cssText = 'width:80px; height:80px; object-fit:cover; border-radius:8px; border:1px solid var(--line);';
      preview.appendChild(img);
    });
  } else {
    preview.style.display = 'none';
  }
}

async function submitPost(e) {
  e.preventDefault();
  <?php if (!$user): ?>
  window.location = '<?= url('/public/login.php') ?>';
  return;
  <?php endif; ?>

  const form = e.target;
  const btn = document.getElementById('btnSubmitPost');
  const formData = new FormData(form);
  formData.append('action', 'post');

  btn.disabled = true;
  btn.textContent = '...';

  try {
    const res = await fetch('<?= url("/api/forum_action.php") ?>', { method: 'POST', body: formData });
    const data = await res.json();
    if (data.success) { location.reload(); }
    else { alert(data.message || 'Có lỗi xảy ra.'); }
  } catch(err) { alert('Lỗi kết nối mạng.'); }
  finally { btn.disabled = false; btn.textContent = '<?= __('forum_post_btn') ?>'; }
}

async function toggleLike(checkinId, btnEl) {
  <?php if (!$user): ?>
  window.location = '<?= url('/public/login.php') ?>';
  return;
  <?php endif; ?>
  const fd = new FormData();
  fd.append('action', 'like');
  fd.append('checkin_id', checkinId);
  try {
    const res = await fetch('<?= url("/api/forum_action.php") ?>', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.success) {
      const countEl = btnEl.querySelector('.like-count');
      let count = parseInt(countEl.textContent) || 0;
      if (data.liked) {
        btnEl.classList.add('liked');
        btnEl.querySelector('.like-icon').textContent = '❤️';
        countEl.textContent = count + 1;
      } else {
        btnEl.classList.remove('liked');
        btnEl.querySelector('.like-icon').textContent = '🤍';
        countEl.textContent = Math.max(0, count - 1);
      }
    }
  } catch(err) {}
}

function toggleComments(id) {
  const panel = document.getElementById('comments-' + id);
  if (!panel) return;
  panel.style.display = panel.style.display === 'block' ? 'none' : 'block';
  if (panel.style.display === 'block') {
    const input = document.getElementById('comment-input-' + id);
    if (input) input.focus();
  }
}

async function submitComment(e, checkinId) {
  e.preventDefault();
  const input = document.getElementById('comment-input-' + checkinId);
  const content = input.value.trim();
  if (!content) return;
  const btn = e.target.querySelector('button[type="submit"]');
  if (btn) btn.disabled = true;
  const fd = new FormData();
  fd.append('action', 'comment');
  fd.append('checkin_id', checkinId);
  fd.append('content', content);
  try {
    const res = await fetch('<?= url("/api/forum_action.php") ?>', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.success) {
      const list = document.getElementById('comment-list-' + checkinId);
      const div = document.createElement('div');
      div.className = 'comment-row';
      div.innerHTML = `
        <img src="<?= e(get_avatar($user['avatar'] ?? null)) ?>" class="comment-avatar" onerror="this.onerror=null;this.src='<?= url('/assets/images/placeholder.svg') ?>';">
        <div class="comment-bubble">
          <div class="author"><?= e($user['full_name'] ?? '') ?></div>
          <div class="text">${content.replace(/\n/g, '<br>')}</div>
          <div class="ctime">Vừa xong</div>
        </div>`;
      list.appendChild(div);
      input.value = '';
    } else { alert(data.message || 'Lỗi.'); }
  } catch(err) { alert('Lỗi kết nối.'); }
  finally { if (btn) btn.disabled = false; }
}

function togglePostMenu(id) {
  const menu = document.getElementById('post-menu-' + id);
  if (!menu) return;
  const isOpen = menu.style.display === 'block';
  document.querySelectorAll('[id^="post-menu-"]').forEach(m => m.style.display = 'none');
  menu.style.display = isOpen ? 'none' : 'block';
}
document.addEventListener('click', function(e) {
  if (!e.target.closest('.post-menu-btn') && !e.target.closest('[id^="post-menu-"]')) {
    document.querySelectorAll('[id^="post-menu-"]').forEach(m => m.style.display = 'none');
  }
});

function editPost(id) {
  document.getElementById('post-content-' + id).style.display = 'none';
  document.getElementById('post-edit-' + id).style.display = 'block';
  document.getElementById('post-menu-' + id).style.display = 'none';
}
function cancelEdit(id) {
  document.getElementById('post-content-' + id).style.display = '';
  document.getElementById('post-edit-' + id).style.display = 'none';
}
async function saveEdit(id) {
  const content = document.getElementById('edit-text-' + id).value.trim();
  if (!content) { alert('Nội dung không được để trống.'); return; }
  const btn = document.getElementById('btnSaveEdit-' + id);
  btn.disabled = true; btn.textContent = '...';
  const fd = new FormData();
  fd.append('action', 'edit_post');
  fd.append('checkin_id', id);
  fd.append('content', content);
  const imgInput = document.getElementById('edit-image-' + id);
  if (imgInput) { for (let f of imgInput.files) fd.append('images[]', f); }
  document.querySelectorAll('.remove-img-cb-' + id).forEach(cb => { if (cb.checked) fd.append('remove_images[]', cb.value); });
  try {
    const res = await fetch('<?= url("/api/forum_action.php") ?>', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.success) { location.reload(); }
    else { alert(data.message || 'Lỗi.'); btn.disabled = false; btn.textContent = 'Lưu'; }
  } catch(err) { alert('Lỗi kết nối.'); btn.disabled = false; btn.textContent = 'Lưu'; }
}
async function deletePost(id) {
  if (!confirm('Bạn có chắc chắn muốn xóa bài viết này?')) return;
  const fd = new FormData();
  fd.append('action', 'delete_post');
  fd.append('checkin_id', id);
  try {
    const res = await fetch('<?= url("/api/forum_action.php") ?>', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.success) { document.getElementById('post-' + id).remove(); }
    else { alert(data.message || 'Lỗi.'); }
  } catch(err) { alert('Lỗi kết nối.'); }
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
