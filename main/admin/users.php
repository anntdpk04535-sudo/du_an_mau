<?php
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();
$pageTitle = __('admin_users_title');
$db = getDB();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $userId = (int)($_POST['user_id'] ?? 0);
    
    // Prevent modifying yourself
    if ($userId !== $_SESSION['user']['id']) {
        if ($action === 'toggle_role') {
            $stmtRole = $db->prepare("SELECT role FROM users WHERE id = ?");
            $stmtRole->execute([$userId]);
            $currentRole = $stmtRole->fetchColumn();
            $newRole = $currentRole === 'admin' ? 'user' : 'admin';
            $stmt = $db->prepare("UPDATE users SET role = ? WHERE id = ?");
            $stmt->execute([$newRole, $userId]);
        } elseif ($action === 'toggle_status') {
            $stmtStatus = $db->prepare("SELECT status FROM users WHERE id = ?");
            $stmtStatus->execute([$userId]);
            $currentStatus = $stmtStatus->fetchColumn();
            $newStatus = $currentStatus === 'active' ? 'banned' : 'active';
            $stmt = $db->prepare("UPDATE users SET status = ? WHERE id = ?");
            $stmt->execute([$newStatus, $userId]);
        }
    }
    header('Location: ' . url('/admin/users.php'));
    exit;
}

$search = trim($_GET['q'] ?? '');
$query = "SELECT * FROM users";
$params = [];

$page = isset($_GET['page']) && (int)$_GET['page'] > 0 ? (int)$_GET['page'] : 1;
$limit = 4;
$offset = ($page - 1) * $limit;

$countQuery = "SELECT COUNT(*) FROM users";
$paramsCount = [];
if ($search !== '') {
    $countQuery .= " WHERE full_name LIKE ? OR email LIKE ?";
    $query .= " WHERE full_name LIKE ? OR email LIKE ?";
    $paramsCount[] = "%$search%";
    $paramsCount[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
$stmtCount = $db->prepare($countQuery);
$stmtCount->execute($paramsCount);
$totalUsers = $stmtCount->fetchColumn();
$totalPages = ceil($totalUsers / $limit);

$query .= " ORDER BY created_at DESC LIMIT $limit OFFSET $offset";

$stmt = $db->prepare($query);
$stmt->execute($params);
$users = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<style>
.user-table {
    width: 100%;
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    border-collapse: collapse;
    margin-top: 20px;
}
.user-table th, .user-table td {
    padding: 15px 20px;
    text-align: left;
    border-bottom: 1px solid #f1f5f9;
}
.user-table th {
    background: #f8fafc;
    color: #64748b;
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.user-table tr:last-child td {
    border-bottom: none;
}
.user-table tr:hover {
    background: #f8fafc;
}
.badge {
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}
.badge.admin { background: #dbeafe; color: #1e40af; }
.badge.user { background: #f1f5f9; color: #475569; }
.badge.active { background: #dcfce7; color: #166534; }
.badge.banned { background: #fee2e2; color: #991b1b; }

.action-btn {
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
    border: none;
    cursor: pointer;
    transition: background 0.2s;
}
.action-btn.role { background: #e0e7ff; color: #3730a3; }
.action-btn.role:hover { background: #c7d2fe; }
.action-btn.ban { background: #fee2e2; color: #991b1b; }
.action-btn.ban:hover { background: #fecaca; }
.action-btn.unban { background: #dcfce7; color: #166534; }
.action-btn.unban:hover { background: #bbf7d0; }
</style>

<h1 class="section-title"><?= __('admin_users_heading') ?></h1>
<?php include __DIR__ . '/nav.php'; ?>

<form method="get" style="display: flex; gap: 10px; margin-bottom: 20px;">
    <input type="text" name="q" value="<?= e($search) ?>" placeholder="<?= __('admin_users_search_ph') ?>" style="padding: 10px; border: 1px solid #ccc; border-radius: 8px; width: 300px;">
    <button type="submit" class="btn"><?= __('admin_btn_search') ?></button>
    <?php if ($search): ?>
        <a href="<?= url('/admin/users.php') ?>" class="btn secondary"><?= __('admin_btn_clear') ?></a>
    <?php endif; ?>
</form>

<table class="user-table">
    <thead>
        <tr>
            <th>ID</th>
            <th><?= __('admin_th_fullname') ?></th>
            <th><?= __('admin_th_email') ?></th>
            <th><?= __('admin_th_role') ?></th>
            <th><?= __('admin_th_status') ?></th>
            <th><?= __('admin_th_registered') ?></th>
            <th><?= __('admin_th_action') ?></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($users as $u): ?>
            <tr>
                <td style="color:#64748b; font-weight:500;">#<?= $u['id'] ?></td>
                <td style="font-weight:600; color:var(--text-dark);"><?= e($u['full_name']) ?></td>
                <td style="color:#64748b;"><?= e($u['email']) ?></td>
                <td><span class="badge <?= $u['role'] ?>"><?= strtoupper($u['role']) ?></span></td>
                <td><span class="badge <?= $u['status'] ?>"><?= strtoupper($u['status']) ?></span></td>
                <td style="color:#64748b; font-size:13px;"><?= date('d/m/Y H:i', strtotime($u['created_at'])) ?></td>
                <td>
                    <?php if ($u['id'] !== $_SESSION['user']['id']): ?>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="action" value="toggle_role">
                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                            <button type="submit" class="action-btn role" onclick="return confirm('<?= __('admin_confirm_role') ?>')"><?= __('admin_btn_role') ?></button>
                        </form>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="action" value="toggle_status">
                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                            <button type="submit" class="action-btn <?= $u['status'] === 'active' ? 'ban' : 'unban' ?>" onclick="return confirm('<?= __('admin_confirm_status') ?>')">
                                <?= $u['status'] === 'active' ? __('admin_btn_ban') : __('admin_btn_unban') ?>
                            </button>
                        </form>
                    <?php else: ?>
                        <span style="color:#94a3b8; font-style:italic; font-size:12px;"><?= __('admin_you') ?></span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php if ($totalPages > 1): ?>
<div class="pagination" style="display: flex; justify-content: center; gap: 10px; margin-top: 20px;">
    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <a href="?q=<?= urlencode($search) ?>&page=<?= $i ?>" class="btn <?= $i === $page ? '' : 'secondary' ?>" style="padding: 5px 12px; font-size: 14px;">
            <?= $i ?>
        </a>
    <?php endfor; ?>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>