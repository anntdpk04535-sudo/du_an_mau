<?php
require_once __DIR__ . '/../includes/functions.php';
$pageTitle = 'Cẩm nang du lịch - Đắk Lắk Travel AI';
$db = getDB();

$search = trim($_GET['q'] ?? '');
$page = isset($_GET['page']) && (int)$_GET['page'] > 0 ? (int)$_GET['page'] : 1;
$limit = 4;
$offset = ($page - 1) * $limit;

$query = "SELECT a.*, u.full_name as author_name FROM articles a LEFT JOIN users u ON a.author_id = u.id WHERE a.status = 'published'";
$countQuery = "SELECT COUNT(*) FROM articles a WHERE a.status = 'published'";

$params = [];
if ($search !== '') {
    $searchCond = " AND (a.title LIKE ? OR a.summary LIKE ?)";
    $query .= $searchCond;
    $countQuery .= $searchCond;
    $params[] = "%$search%";
    $params[] = "%$search%";
}
$query .= " ORDER BY a.created_at DESC LIMIT ? OFFSET ?";

// Get total count
$stmtCount = $db->prepare($countQuery);
$stmtCount->execute($params);
$totalArticles = $stmtCount->fetchColumn();
$totalPages = ceil($totalArticles / $limit);

// Get articles for current page
$stmt = $db->prepare($query);
// Bind params
$paramIndex = 1;
foreach ($params as $param) {
    $stmt->bindValue($paramIndex++, $param, PDO::PARAM_STR);
}
$stmt->bindValue($paramIndex++, $limit, PDO::PARAM_INT);
$stmt->bindValue($paramIndex++, $offset, PDO::PARAM_INT);
$stmt->execute();
$articles = $stmt->fetchAll();
foreach ($articles as &$a) {
    $a = translateDbRow($a, ['title', 'summary', 'content']);
}
unset($a);

include __DIR__ . '/../includes/header.php';
?>

<style>
.article-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 30px;
    margin-top: 30px;
}
.article-card {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(0,0,0,0.06);
    transition: transform 0.2s, box-shadow 0.2s;
    text-decoration: none;
    color: var(--text-dark);
    display: flex;
    flex-direction: column;
}
.article-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
}
.article-img {
    height: 200px;
    width: 100%;
    object-fit: cover;
}
.article-content {
    padding: 20px;
    display: flex;
    flex-direction: column;
    flex-grow: 1;
}
.article-title {
    font-size: 18px;
    margin: 0 0 10px;
    color: var(--green-900);
    line-height: 1.4;
}
.article-summary {
    font-size: 14px;
    color: #64748b;
    margin: 0 0 15px;
    line-height: 1.6;
    flex-grow: 1;
}
.article-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 12px;
    color: #94a3b8;
    border-top: 1px solid #f1f5f9;
    padding-top: 12px;
    margin-top: auto;
}
</style>

<div style="text-align: center; margin-bottom: 40px;">
    <h1 class="section-title"><?= __('articles_title') ?></h1>
    <p class="section-sub"><?= __('articles_sub') ?></p>
</div>

<form method="get" action="<?= url('/cam-nang') ?>" style="display: flex; gap: 15px; justify-content: center; margin-bottom: 40px;">
    <input type="text" name="q" value="<?= e($search) ?>" placeholder="<?= __('search_articles') ?>" style="width: 400px; max-width: 100%; padding: 12px 20px; border: 1px solid #ddd; border-radius: 30px; font-size: 15px; outline: none;">
    <button type="submit" class="btn" style="border-radius: 30px; padding: 10px 25px;"><?= __('search_btn') ?></button>
</form>

<?php if (empty($articles)): ?>
    <div style="text-align: center; padding: 50px; background: white; border-radius: 12px;">
        <div style="font-size: 50px; margin-bottom: 15px;">📝</div>
        <h3 style="margin: 0 0 10px;"><?= __('no_articles') ?></h3>
        <p style="color: #64748b; margin: 0;"><?= __('no_articles_sub') ?></p>
        <?php if ($search): ?>
            <a href="<?= url('/cam-nang') ?>" class="btn secondary" style="margin-top: 15px; display: inline-block;"><?= __('clear_search') ?></a>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="article-grid">
        <?php foreach ($articles as $a): ?>
            <a href="<?= url('/cam-nang/' . urlencode($a['slug'])) ?>" class="article-card">
                <?php if ($a['image_url']): ?>
                    <img src="<?= e($a['image_url']) ?>" alt="<?= e($a['title']) ?>" class="article-img">
                <?php else: ?>
                    <div class="article-img" style="background: linear-gradient(135deg, var(--green-600), var(--green-400)); display: flex; align-items: center; justify-content: center; color: white; font-size: 40px;">📝</div>
                <?php endif; ?>
                <div class="article-content">
                    <h3 class="article-title"><?= e($a['title']) ?></h3>
                    <p class="article-summary"><?= e($a['summary']) ?></p>
                    <div class="article-meta">
                        <span>✍️ <?= e($a['author_name'] ?? 'Admin') ?></span>
                        <span>📅 <?= date('d/m/Y', strtotime($a['created_at'])) ?></span>
                    </div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if ($totalPages > 1): ?>
<div style="display:flex; gap:10px; justify-content:center; margin-top:30px; margin-bottom: 20px;">
    <?php
    $queryParams = $_GET;
    for ($i = 1; $i <= $totalPages; $i++): 
        $queryParams['page'] = $i;
        $queryString = http_build_query($queryParams);
    ?>
        <a href="?<?= $queryString ?>" class="btn <?= $i === $page ? 'secondary' : '' ?>" style="padding: 8px 16px; font-size:15px; border-radius:8px;"><?= $i ?></a>
    <?php endfor; ?>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>