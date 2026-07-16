<?php
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();
$pageTitle = __('admin_articles_title');
$db = getDB();

// Xử lý xoá
if (isset($_GET['delete'])) {
    $stmt = $db->prepare("DELETE FROM articles WHERE id = ?");
    $stmt->execute([(int)$_GET['delete']]);
    header('Location: ' . url('/admin/articles.php'));
    exit;
}

// Xử lý thêm/sửa
$editing = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $slug = trim($_POST['slug'] ?? '') ?: strtolower(preg_replace('/[^a-z0-9]+/i', '-', $title));
    $summary = trim($_POST['summary'] ?? '');
    $content = $_POST['content'] ?? '';
    $imageUrl = trim($_POST['image_url'] ?? '');
    
    if (isset($_FILES['image_upload']) && $_FILES['image_upload']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['image_upload']['name'], PATHINFO_EXTENSION);
        $filename = time() . '_' . uniqid() . '.' . $ext;
        $destPath = __DIR__ . '/../assets/images/uploads/' . $filename;
        if (move_uploaded_file($_FILES['image_upload']['tmp_name'], $destPath)) {
            $imageUrl = url('/assets/images/uploads/' . $filename);
        }
    }

    $status = $_POST['status'] ?? 'published';
    $authorId = $_SESSION['user']['id'];

    if ($id > 0) {
        $stmt = $db->prepare("UPDATE articles SET title=?, slug=?, summary=?, content=?, image_url=?, status=? WHERE id=?");
        $stmt->execute([$title, $slug, $summary, $content, $imageUrl, $status, $id]);
    } else {
        $stmt = $db->prepare("INSERT INTO articles (title, slug, summary, content, image_url, status, author_id) VALUES (?,?,?,?,?,?,?)");
        $stmt->execute([$title, $slug, $summary, $content, $imageUrl, $status, $authorId]);
    }
    header('Location: ' . url('/admin/articles.php'));
    exit;
}

if (isset($_GET['edit'])) {
    $stmt = $db->prepare("SELECT * FROM articles WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $editing = $stmt->fetch();
}

$page = (int)($_GET['page'] ?? 1);
if ($page < 1) $page = 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$totalArticles = $db->query("SELECT COUNT(*) FROM articles")->fetchColumn();
$totalPages = ceil($totalArticles / $limit);

$stmt = $db->prepare("SELECT a.*, u.full_name as author_name FROM articles a LEFT JOIN users u ON a.author_id = u.id ORDER BY a.created_at DESC LIMIT ? OFFSET ?");
$stmt->bindValue(1, $limit, PDO::PARAM_INT);
$stmt->bindValue(2, $offset, PDO::PARAM_INT);
$stmt->execute();
$articles = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<!-- TinyMCE for rich text editing -->
<script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
<script>
  tinymce.init({
    selector: '#content-editor',
    height: 400,
    plugins: 'advlist autolink lists link image charmap preview anchor pagebreak',
    toolbar_mode: 'floating',
    toolbar: 'undo redo | formatselect | bold italic backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat | help',
    content_style: 'body { font-family:Helvetica,Arial,sans-serif; font-size:14px }'
  });
</script>

<h1 class="section-title"><?= __('admin_articles_heading') ?></h1>
<?php include __DIR__ . '/nav.php'; ?>

<div class="form-box" style="max-width: 100%;">
    <h3><?= $editing ? __('admin_articles_edit') : __('admin_articles_add') ?></h3>
    <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="id" value="<?= $editing['id'] ?? '' ?>">
        
        <div class="form-group">
            <label><?= __('admin_articles_form_title') ?></label>
            <input type="text" name="title" required value="<?= e($editing['title'] ?? '') ?>">
        </div>
        
        <div style="display: flex; gap: 20px; flex-wrap: wrap;">
            <div class="form-group" style="flex: 1; min-width: 200px;">
                <label><?= __('admin_articles_slug') ?></label>
                <input type="text" name="slug" value="<?= e($editing['slug'] ?? '') ?>">
            </div>
            <div class="form-group" style="flex: 1; min-width: 200px;">
                <label><?= __('admin_articles_status') ?></label>
                <select name="status">
                    <option value="published" <?= ($editing['status'] ?? '') === 'published' ? 'selected' : '' ?>><?= __('admin_articles_status_pub') ?></option>
                    <option value="draft" <?= ($editing['status'] ?? '') === 'draft' ? 'selected' : '' ?>><?= __('admin_articles_status_draft') ?></option>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label><?= __('admin_articles_summary') ?></label>
            <textarea name="summary" rows="3"><?= e($editing['summary'] ?? '') ?></textarea>
        </div>
        
        <div class="form-group">
            <label><?= __('admin_articles_content') ?></label>
            <textarea id="content-editor" name="content"><?= $editing['content'] ?? '' ?></textarea>
        </div>

        <div class="form-group">
            <label><?= __('admin_articles_image') ?></label>
            <div style="display:flex; gap:10px; margin-bottom:10px;">
                <input type="file" name="image_upload" accept="image/*" style="flex:1;">
                <input type="text" name="image_url" value="<?= e($editing['image_url'] ?? '') ?>" placeholder="<?= __('admin_articles_image_ph') ?>" style="flex:1;">
            </div>
            <?php if (!empty($editing['image_url'])): ?>
                <img src="<?= e($editing['image_url']) ?>" style="margin-top:10px; max-height:150px; border-radius:8px;">
            <?php endif; ?>
        </div>

        <button type="submit" class="btn"><?= $editing ? __('admin_articles_save') : __('admin_articles_submit') ?></button>
        <?php if ($editing): ?>
            <a href="<?= url('/admin/articles.php') ?>" class="btn secondary"><?= __('admin_articles_cancel') ?></a>
        <?php endif; ?>
    </form>
</div>

<h3 class="section-title"><?= __('admin_articles_list') ?></h3>
<table style="width:100%; background:white; border-radius:14px; overflow:hidden; border-collapse:collapse; box-shadow:0 2px 10px rgba(0,0,0,0.05);">
    <tr style="background:#f1f5f9; text-align:left;">
        <th style="padding:15px; color:#475569;"><?= __('admin_articles_th_title') ?></th>
        <th style="padding:15px; color:#475569;"><?= __('admin_articles_th_author') ?></th>
        <th style="padding:15px; color:#475569;"><?= __('admin_articles_th_status') ?></th>
        <th style="padding:15px; color:#475569;"><?= __('admin_articles_th_date') ?></th>
        <th style="padding:15px; color:#475569;"><?= __('admin_articles_th_action') ?></th>
    </tr>
    <?php foreach ($articles as $a): ?>
        <tr style="border-bottom:1px solid #f1f5f9;">
            <td style="padding:15px; font-weight:600; color:var(--text-dark);">
                <?= e($a['title']) ?>
                <br>
                <span style="font-size:12px; color:#94a3b8; font-weight:normal;">/public/article.php?slug=<?= e($a['slug']) ?></span>
            </td>
            <td style="padding:15px;"><?= e($a['author_name'] ?? 'Admin') ?></td>
            <td style="padding:15px;">
                <?php if ($a['status'] === 'published'): ?>
                    <span style="background:#dcfce7; color:#166534; padding:4px 8px; border-radius:12px; font-size:12px; font-weight:600;"><?= __('admin_articles_badge_pub') ?></span>
                <?php else: ?>
                    <span style="background:#f1f5f9; color:#475569; padding:4px 8px; border-radius:12px; font-size:12px; font-weight:600;"><?= __('admin_articles_badge_draft') ?></span>
                <?php endif; ?>
            </td>
            <td style="padding:15px; font-size:13px; color:#64748b;"><?= date('d/m/Y', strtotime($a['created_at'])) ?></td>
            <td style="padding:15px;">
                <a href="<?= url('/admin/articles.php?edit=' . $a['id']) ?>" style="color:#2563eb; text-decoration:none; font-weight:600; margin-right:10px;"><?= __('admin_articles_btn_edit') ?></a>
                <a href="<?= url('/admin/articles.php?delete=' . $a['id']) ?>" onclick="return confirm('<?= __('admin_articles_delete_confirm') ?>')" style="color:#dc2626; text-decoration:none; font-weight:600;"><?= __('admin_articles_btn_delete') ?></a>
            </td>
        </tr>
    <?php endforeach; ?>
</table>

<?php if ($totalPages > 1): ?>
<div style="display:flex; gap:10px; justify-content:center; margin-top:20px;">
    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <a href="?page=<?= $i ?>" class="btn <?= $i === $page ? 'secondary' : '' ?>" style="padding: 6px 12px; font-size:14px;"><?= $i ?></a>
    <?php endfor; ?>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>