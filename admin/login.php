<?php
require_once __DIR__ . '/../includes/functions.php';
$pageTitle = __('admin_login_title');
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND role = 'admin'");
    $stmt->execute([$email]);
    $u = $stmt->fetch();

    if ($u && password_verify($password, $u['password_hash'])) {
        if ($u['status'] === 'banned') {
            $error = __('admin_account_banned');
        } else {
            $_SESSION['user'] = ['id' => $u['id'], 'full_name' => $u['full_name'], 'role' => $u['role']];
            header('Location: ' . url('/admin/index.php'));
            exit;
        }
    } else {
        $error = __('admin_wrong_credentials');
    }
}

include __DIR__ . '/../includes/header.php';
?>

<h1 class="section-title"><?= __('admin_login_heading') ?></h1>

<div class="form-box" style="max-width:400px;">
  <?php if ($error): ?><p style="color:red;"><?= e($error) ?></p><?php endif; ?>
  <form method="post">
    <div class="form-group">
      <label><?= __('email_label') ?></label>
      <input type="email" name="email" required>
    </div>
    <div class="form-group">
      <label><?= __('password_label') ?></label>
      <input type="password" name="password" required>
    </div>
    <button type="submit" class="btn"><?= __('login_submit') ?></button>
  </form>
  <p style="margin-top:14px;font-size:13px;color:#777;">
    <?= __('admin_login_hint') ?>
  </p>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
