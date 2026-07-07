<?php
require_once __DIR__ . '/../includes/functions.php';

$slug = $_GET['slug'] ?? '';
if (!$slug) {
    header('Location: ' . url('/public/articles.php'));
    exit;
}

$db = getDB();
$stmt = $db->prepare("SELECT a.*, u.full_name as author_name FROM articles a LEFT JOIN users u ON a.author_id = u.id WHERE a.slug = ? AND a.status = 'published'");
$stmt->execute([$slug]);
$article = $stmt->fetch();
if ($article) {
    $article = translateDbRow($article, ['title', 'summary', 'content']);
}

if (!$article) {
    http_response_code(404);
    $pageTitle = 'Không tìm thấy bài viết';
    include __DIR__ . '/../includes/header.php';
    echo '<div style="text-align:center; padding:100px 20px;"><h2>Không tìm thấy bài viết</h2><a href="'.url('/public/articles.php').'" class="btn">Quay lại cẩm nang</a></div>';
    include __DIR__ . '/../includes/footer.php';
    exit;
}

$user = currentUser();
$error = '';
$success = '';

// Handle comment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment_content'])) {
    if (!$user) {
        $error = 'Bạn cần đăng nhập để bình luận.';
    } else {
        $content = trim($_POST['comment_content']);
        if (empty($content)) {
            $error = 'Nội dung bình luận không được để trống.';
        } else {
            $stmt = $db->prepare("INSERT INTO article_comments (article_id, user_id, content) VALUES (?, ?, ?)");
            $stmt->execute([$article['id'], $user['id'], $content]);
            $success = 'Đã gửi bình luận thành công.';
        }
    }
}

// Pagination for comments
$page = isset($_GET['page']) && (int)$_GET['page'] > 0 ? (int)$_GET['page'] : 1;
$limit = 5;
$offset = ($page - 1) * $limit;

$stmtCount = $db->prepare("SELECT COUNT(*) FROM article_comments WHERE article_id = ?");
$stmtCount->execute([$article['id']]);
$totalComments = $stmtCount->fetchColumn();
$totalPages = ceil($totalComments / $limit);

// Fetch comments
$stmt = $db->prepare("SELECT c.*, u.full_name, u.avatar FROM article_comments c JOIN users u ON c.user_id = u.id WHERE c.article_id = ? ORDER BY c.created_at DESC LIMIT $limit OFFSET $offset");
$stmt->execute([$article['id']]);
$comments = $stmt->fetchAll();

$pageTitle = $article['title'] . ' - Đắk Lắk Travel AI';
include __DIR__ . '/../includes/header.php';
?>

<style>
.article-header {
    text-align: center;
    max-width: 800px;
    margin: 0 auto 30px;
}
.article-header h1 {
    font-size: 36px;
    color: var(--green-900);
    margin-bottom: 15px;
    line-height: 1.3;
}
.article-meta {
    display: flex;
    justify-content: center;
    gap: 20px;
    color: #64748b;
    font-size: 14px;
    margin-bottom: 30px;
}
.article-hero {
    width: 100%;
    height: 400px;
    object-fit: cover;
    border-radius: 16px;
    margin-bottom: 40px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
}
.article-body {
    max-width: 800px;
    margin: 0 auto;
    font-size: 16px;
    line-height: 1.8;
    color: #334155;
    background: white;
    padding: 40px;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.05);
}
.article-body h2, .article-body h3 {
    color: var(--green-900);
    margin-top: 30px;
}
.article-body img {
    max-width: 100%;
    height: auto;
    border-radius: 8px;
    margin: 20px 0;
}
.article-body a {
    color: var(--green-600);
    text-decoration: none;
}
.article-body a:hover {
    text-decoration: underline;
}
.article-body ul, .article-body ol {
    margin-bottom: 20px;
    padding-left: 20px;
}
.article-body li {
    margin-bottom: 10px;
}
.article-nav {
    max-width: 800px;
    margin: 40px auto 0;
    display: flex;
    justify-content: space-between;
}
@media (max-width: 768px) {
    .article-header h1 { font-size: 28px; }
    .article-hero { height: 250px; }
    .article-body { padding: 20px; }
}

/* Comment section */
.comments-section {
    max-width: 800px;
    margin: 40px auto 0;
    background: white;
    padding: 30px;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.05);
}
.comment-form textarea {
    width: 100%;
    min-height: 100px;
    padding: 15px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    margin-bottom: 10px;
    font-family: inherit;
    resize: vertical;
}
.comment-list {
    margin-top: 30px;
}
.comment-item {
    display: flex;
    gap: 15px;
    margin-bottom: 20px;
    padding-bottom: 20px;
    border-bottom: 1px solid #f1f5f9;
}
.comment-item:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}
.comment-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: var(--green-200);
    color: var(--green-800);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    flex-shrink: 0;
    object-fit: cover;
}
.comment-content h4 {
    margin: 0 0 5px;
    font-size: 15px;
    color: #1e293b;
}
.comment-content p {
    margin: 0;
    color: #475569;
    font-size: 14px;
    line-height: 1.5;
}
.comment-date {
    font-size: 12px;
    color: #94a3b8;
    margin-top: 5px;
}
</style>

<article>
    <div class="article-header">
        <h1><?= e($article['title']) ?></h1>
        <div class="article-meta">
            <span>✍️ <?= e($article['author_name'] ?? 'Admin') ?></span>
            <span>📅 <?= date('d/m/Y', strtotime($article['created_at'])) ?></span>
        </div>
    </div>
    
    <?php if ($article['image_url']): ?>
        <img src="<?= e($article['image_url']) ?>" alt="<?= e($article['title']) ?>" class="article-hero">
    <?php endif; ?>

    <div class="article-body">
        <?php if ($article['summary']): ?>
            <p style="font-size: 18px; font-style: italic; color: #475569; margin-bottom: 30px; border-left: 4px solid var(--green-500); padding-left: 15px;">
                <?= e($article['summary']) ?>
            </p>
        <?php endif; ?>
        
        <div class="content">
            <?= $article['content'] // Content is HTML, outputting directly ?>
        </div>
    </div>
</article>

<div class="comments-section" id="comments">
    <h3 style="margin-top: 0; color: var(--green-900);"><?= __('comments_count') ?> (<?= $totalComments ?>)</h3>
    
    <?php if ($error): ?>
        <div style="background:#fee2e2;color:#991b1b;padding:10px;border-radius:8px;margin-bottom:15px;font-size:14px;">❌ <?= e($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div style="background:#dcfce7;color:#166534;padding:10px;border-radius:8px;margin-bottom:15px;font-size:14px;">✅ <?= e($success) ?></div>
    <?php endif; ?>

    <?php if ($user): ?>
        <form class="comment-form" method="POST">
            <textarea name="comment_content" placeholder="<?= __('share_thought') ?>" required></textarea>
            <button type="submit" class="btn"><?= __('send_comment') ?></button>
        </form>
    <?php else: ?>
        <div style="background:#f8fafc; padding:20px; border-radius:8px; text-align:center; border: 1px dashed #cbd5e1;">
            <p style="margin:0 0 10px; color:#64748b;"><?= __('login_to_comment') ?></p>
            <a href="<?= url('/public/login.php') ?>" class="btn"><?= __('login') ?></a>
        </div>
    <?php endif; ?>

    <div class="comment-list">
        <?php foreach ($comments as $c): ?>
            <div class="comment-item">
                <?php if ($c['avatar']): ?>
                    <img src="<?= e($c['avatar']) ?>" class="comment-avatar" alt="Avatar">
                <?php else: ?>
                    <div class="comment-avatar">
                        <?= mb_strtoupper(mb_substr($c['full_name'], 0, 1)) ?>
                    </div>
                <?php endif; ?>
                <div class="comment-content">
                    <h4><?= e($c['full_name']) ?></h4>
                    <p><?= nl2br(e($c['content'])) ?></p>
                    <div class="comment-date"><?= date('d/m/Y H:i', strtotime($c['created_at'])) ?></div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <?php if (isset($totalPages) && $totalPages > 1): ?>
    <div class="pagination" style="display: flex; justify-content: center; gap: 10px; margin-top: 20px;">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="?slug=<?= urlencode($slug) ?>&page=<?= $i ?>#comments" class="btn <?= $i === $page ? '' : 'secondary' ?>" style="padding: 5px 12px; font-size: 14px;">
                <?= $i ?>
            </a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</div>

<div class="article-nav">
    <a href="<?= url('/public/articles.php') ?>" class="btn secondary"><?= __('view_all_articles') ?></a>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>