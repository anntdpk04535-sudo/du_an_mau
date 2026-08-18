<?php
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$pageTitle = __('forum_admin_title');
$db = getDB();

// Handle Hide / Publish / Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = (int)$_POST['id'];
    
    if ($action === 'hide') {
        $db->prepare("UPDATE checkins SET status = 'hidden' WHERE id = ?")->execute([$id]);
    } elseif ($action === 'publish') {
        $db->prepare("UPDATE checkins SET status = 'published' WHERE id = ?")->execute([$id]);
    } elseif ($action === 'delete') {
        $db->prepare("DELETE FROM checkins WHERE id = ?")->execute([$id]);
    }
    header("Location: " . url('/admin/forum.php'));
    exit;
}

$page = isset($_GET['page']) && (int)$_GET['page'] > 0 ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$stmt = $db->prepare("
    SELECT c.*, u.full_name, d.name AS dest_name, d.name_en AS dest_name_en, 
           (SELECT COUNT(*) FROM checkin_comments cc WHERE cc.checkin_id = c.id) as comment_count
    FROM checkins c 
    JOIN users u ON c.user_id = u.id 
    LEFT JOIN destinations d ON c.destination_id = d.id 
    ORDER BY c.created_at DESC 
    LIMIT ? OFFSET ?
");
$stmt->bindValue(1, $limit, PDO::PARAM_INT);
$stmt->bindValue(2, $offset, PDO::PARAM_INT);
$stmt->execute();
$checkins = $stmt->fetchAll();

$totalCount = $db->query("SELECT COUNT(*) FROM checkins")->fetchColumn();
$totalPages = ceil($totalCount / $limit);

include __DIR__ . '/../includes/header.php';
?>

<h1 class="section-title"><?= __('forum_admin_heading') ?></h1>
<?php include __DIR__ . '/nav.php'; ?>

<div class="form-box" style="max-width: 100%;">
    <table style="width:100%; border-collapse:collapse; background:white; border-radius:12px; overflow:hidden; box-shadow:0 2px 10px rgba(0,0,0,0.05);">
        <thead style="background:#f1f5f9; text-align:left;">
            <tr>
                <th style="padding:15px; color:#475569; width:15%;"><?= __('forum_admin_th_user') ?></th>
                <th style="padding:15px; color:#475569; width:45%;"><?= __('forum_admin_th_content') ?></th>
                <th style="padding:15px; color:#475569; width:15%;"><?= __('forum_admin_th_stats') ?></th>
                <th style="padding:15px; color:#475569; width:10%;"><?= __('forum_admin_th_status') ?></th>
                <th style="padding:15px; color:#475569; width:15%; text-align:right;"><?= __('forum_admin_th_action') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($checkins as $c): ?>
                <tr style="border-bottom:1px solid #f1f5f9;">
                    <td style="padding:15px;">
                        <strong style="color:#1e293b;"><?= e($c['full_name']) ?></strong><br>
                        <span style="font-size:12px; color:#94a3b8;"><?= date('d/m/Y H:i', strtotime($c['created_at'])) ?></span>
                    </td>
                    <td style="padding:15px;">
                        <?php if ($c['destination_id']): ?>
                            <?php $dn = (($_SESSION['lang'] ?? 'vi') === 'en' && !empty($c['dest_name_en'])) ? $c['dest_name_en'] : $c['dest_name']; ?>
                            <span style="font-size:11px; background:#dcfce7; color:#166534; padding:2px 8px; border-radius:12px; font-weight:600; margin-bottom:5px; display:inline-block;">📍 <?= e($dn) ?></span><br>
                        <?php endif; ?>
                        <div style="font-size:14px; color:#334155; margin-bottom:8px;"><?= nl2br(e($c['content'])) ?></div>
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
                        <?php if (!empty($postImages)): ?>
                            <div style="display:flex; gap:5px; overflow-x:auto; padding-bottom:5px;">
                                <?php foreach ($postImages as $imgUrl): ?>
                                    <img src="<?= e($imgUrl) ?>" style="max-height:80px; border-radius:6px; border:1px solid #e2e8f0; flex:0 0 auto;">
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td style="padding:15px; font-size:13px; color:#64748b;">
                        ❤️ <?= $c['likes_count'] ?><br>
                        💬 <?= $c['comment_count'] ?>
                    </td>
                    <td style="padding:15px;">
                        <?php if ($c['status'] === 'published'): ?>
                            <span style="background:#dcfce7; color:#166534; padding:4px 8px; border-radius:12px; font-size:12px; font-weight:600;"><?= __('forum_admin_status_pub') ?></span>
                        <?php else: ?>
                            <span style="background:#f1f5f9; color:#475569; padding:4px 8px; border-radius:12px; font-size:12px; font-weight:600;"><?= __('forum_admin_status_hidden') ?></span>
                        <?php endif; ?>
                    </td>
                    <td style="padding:15px; text-align:right;">
                        <form method="POST" style="display:inline-block;">
                            <input type="hidden" name="id" value="<?= $c['id'] ?>">
                            <?php if ($c['status'] === 'published'): ?>
                                <input type="hidden" name="action" value="hide">
                                <button type="submit" style="background:#f59e0b; color:white; border:none; padding:5px 10px; border-radius:6px; font-size:12px; font-weight:600; cursor:pointer;"><?= __('forum_admin_btn_hide') ?></button>
                            <?php else: ?>
                                <input type="hidden" name="action" value="publish">
                                <button type="submit" style="background:#22c55e; color:white; border:none; padding:5px 10px; border-radius:6px; font-size:12px; font-weight:600; cursor:pointer;"><?= __('forum_admin_btn_pub') ?></button>
                            <?php endif; ?>
                        </form>
                        <form method="POST" style="display:inline-block; margin-left:5px;" onsubmit="return confirm('<?= __('forum_admin_del_confirm') ?>')">
                            <input type="hidden" name="id" value="<?= $c['id'] ?>">
                            <input type="hidden" name="action" value="delete">
                            <button type="submit" style="background:#ef4444; color:white; border:none; padding:5px 10px; border-radius:6px; font-size:12px; font-weight:600; cursor:pointer;"><?= __('forum_admin_btn_del') ?></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($checkins)): ?>
                <tr>
                    <td colspan="5" style="padding:30px; text-align:center; color:#94a3b8;"><?= __('forum_no_posts') ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <?php if ($totalPages > 1): ?>
    <div class="pagination-wrapper">
        <?php if ($page > 1): ?>
            <a href="?page=<?= $page - 1 ?>" class="page-link">«</a>
        <?php endif; ?>
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="?page=<?= $i ?>" class="page-link <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
        <?php endfor; ?>
        <?php if ($page < $totalPages): ?>
            <a href="?page=<?= $page + 1 ?>" class="page-link">»</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
