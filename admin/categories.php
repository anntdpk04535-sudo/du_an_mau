<?php
require_once __DIR__ . '/../includes/functions.php';
$admin = requireAdmin();

$db = getDB();
$message = '';
$error = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'add') {
            $name = trim($_POST['name'] ?? '');
            $slug = slugify($name);
            if (!$name) throw new Exception(__('admin_categories_err_empty'));

            $stmt = $db->prepare("INSERT INTO categories (name, slug) VALUES (?, ?)");
            $stmt->execute([$name, $slug]);
            $message = __('admin_categories_msg_added');

        } elseif ($action === 'edit') {
            $id = (int)$_POST['id'];
            $name = trim($_POST['name'] ?? '');
            $slug = slugify($name);
            if (!$name) throw new Exception(__('admin_categories_err_empty'));

            $stmt = $db->prepare("UPDATE categories SET name = ?, slug = ? WHERE id = ?");
            $stmt->execute([$name, $slug, $id]);
            $message = __('admin_categories_msg_updated');

        } elseif ($action === 'delete') {
            $id = (int)$_POST['id'];
            // Update destinations to NULL category before deleting
            $db->prepare("UPDATE destinations SET category_id = NULL WHERE category_id = ?")->execute([$id]);
            $stmt = $db->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->execute([$id]);
            $message = __('admin_categories_msg_deleted');
        }
    } catch (Exception $e) {
        $error = __('admin_categories_err_prefix') . $e->getMessage();
    }
}

// Fetch categories
$page = isset($_GET['page']) && (int)$_GET['page'] > 0 ? (int)$_GET['page'] : 1;
$limit = 5;
$offset = ($page - 1) * $limit;

$totalCategories = $db->query("SELECT COUNT(*) FROM categories")->fetchColumn();
$totalPages = ceil($totalCategories / $limit);

$stmt = $db->query("SELECT * FROM categories ORDER BY id ASC LIMIT $limit OFFSET $offset");
$categories = $stmt->fetchAll();

$pageTitle = __('admin_categories_title');
include __DIR__ . '/../includes/header.php';
?>

<h1 class="section-title">🏷️ <?= __('admin_categories_heading') ?></h1>
<?php include __DIR__ . '/nav.php'; ?>

<style>
.card-table { background:white; border-radius:12px; box-shadow:0 2px 10px rgba(0,0,0,.05); overflow:hidden; margin-top:20px; }
table { width:100%; border-collapse:collapse; }
th, td { padding:14px 20px; text-align:left; border-bottom:1px solid #e2e8f0; }
th { background:#f8fafc; font-weight:600; color:#475569; font-size:14px; }
td { font-size:14px; color:#334155; }
tr:hover { background:#f1f5f9; }
.btn-sm { padding:6px 12px; font-size:13px; border-radius:6px; background:white; border:1px solid #cbd5e1; cursor:pointer; color:#475569; font-weight:600; }
.btn-sm:hover { background:#f8fafc; border-color:#94a3b8; }
.btn-sm.delete { color:#ef4444; border-color:#fecaca; background:#fef2f2; }
.btn-sm.delete:hover { background:#fee2e2; border-color:#fca5a5; }

.modal { display:none; position:fixed; inset:0; background:rgba(0,0,0,.5); align-items:center; justify-content:center; z-index:1000; }
.modal.open { display:flex; }
.modal-content { background:white; padding:24px; border-radius:12px; width:400px; max-width:90%; box-shadow:0 10px 30px rgba(0,0,0,.2); }
.modal-content h3 { margin-top:0; color:var(--green-900); margin-bottom:16px; }
.form-group { margin-bottom:14px; }
.form-group label { display:block; margin-bottom:6px; font-size:14px; font-weight:600; color:#334155; }
.form-group input { width:100%; padding:10px; border:1px solid #cbd5e1; border-radius:6px; font-size:14px; }
.modal-actions { display:flex; justify-content:flex-end; gap:10px; margin-top:20px; }
</style>

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
    <p style="margin:0; color:#666;"><?= __('admin_categories_desc') ?></p>
    <button class="btn" onclick="openAddModal()"><?= __('admin_categories_add_btn') ?></button>
</div>

<?php if ($message): ?>
    <div style="background:#dcfce7; color:#166534; padding:12px 16px; border-radius:8px; margin-bottom:20px;">✅ <?= e($message) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div style="background:#fee2e2; color:#991b1b; padding:12px 16px; border-radius:8px; margin-bottom:20px;">❌ <?= e($error) ?></div>
<?php endif; ?>

<div class="card-table">
    <table>
        <thead>
            <tr>
                <th style="width:60px;">ID</th>
                <th><?= __('admin_categories_th_name') ?></th>
                <th><?= __('admin_categories_th_slug') ?></th>
                <th style="text-align:right;"><?= __('admin_categories_th_action') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($categories as $c): ?>
            <tr>
                <td><strong>#<?= $c['id'] ?></strong></td>
                <td><span style="font-weight:600; color:var(--green-900);"><?= e($c['name']) ?></span></td>
                <td><code style="color:#64748b; background:#f1f5f9; padding:2px 6px; border-radius:4px;"><?= e($c['slug']) ?></code></td>
                <td style="text-align:right;">
                    <button class="btn-sm" onclick="openEditModal(<?= $c['id'] ?>, '<?= e($c['name'], true) ?>')">✏️ <?= __('admin_categories_edit_btn') ?></button>
                    <form method="POST" style="display:inline-block;" onsubmit="return confirm('<?= __('admin_categories_delete_confirm') ?>');">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $c['id'] ?>">
                        <button type="submit" class="btn-sm delete">🗑️ <?= __('admin_categories_delete_btn') ?></button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($categories)): ?>
            <tr><td colspan="4" style="text-align:center; padding:30px; color:#94a3b8;"><?= __('admin_categories_no_data') ?></td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php if (isset($totalPages) && $totalPages > 1): ?>
<div class="pagination-wrapper">
    <?php if ($page > 1): ?>
        <a href="?page=<?= $page - 1 ?>" class="page-link">«</a>
    <?php endif; ?>
    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <a href="?page=<?= $i ?>" class="page-link <?= $i === $page ? 'active' : '' ?>">
            <?= $i ?>
        </a>
    <?php endfor; ?>
    <?php if ($page < $totalPages): ?>
        <a href="?page=<?= $page + 1 ?>" class="page-link">»</a>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- Modal Thêm/Sửa -->
<div class="modal" id="catModal">
    <div class="modal-content">
        <h3 id="modalTitle"><?= __('admin_categories_add_modal_title') ?></h3>
        <form method="POST">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="id" id="catId" value="">
            <div class="form-group">
                <label><?= __('admin_categories_th_name') ?></label>
                <input type="text" name="name" id="catName" required placeholder="<?= __('admin_categories_ph_name') ?>">
            </div>
            <div class="modal-actions">
                <button type="button" class="btn-sm" onclick="closeModal()" style="padding:10px 16px;"><?= __('admin_categories_cancel_btn') ?></button>
                <button type="submit" class="btn" style="padding:10px 16px;">💾 <?= __('admin_categories_save_btn') ?></button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddModal() {
    document.getElementById('modalTitle').textContent = '<?= __('admin_categories_add_modal_new') ?>';
    document.getElementById('formAction').value = 'add';
    document.getElementById('catId').value = '';
    document.getElementById('catName').value = '';
    document.getElementById('catModal').classList.add('open');
    setTimeout(() => document.getElementById('catName').focus(), 100);
}

function openEditModal(id, name) {
    document.getElementById('modalTitle').textContent = '<?= __('admin_categories_edit_modal_title') ?>';
    document.getElementById('formAction').value = 'edit';
    document.getElementById('catId').value = id;
    document.getElementById('catName').value = name;
    document.getElementById('catModal').classList.add('open');
    setTimeout(() => document.getElementById('catName').focus(), 100);
}

function closeModal() {
    document.getElementById('catModal').classList.remove('open');
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
