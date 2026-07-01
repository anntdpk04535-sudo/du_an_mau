<?php
require_once __DIR__ . '/../includes/functions.php';
$pageTitle = 'Đăng nhập - Đắk Lắk Travel AI';
$error = '';

if (currentUser()) {
    header('Location: ' . url('/public/index.php'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $u = $stmt->fetch();

    if ($u && password_verify($password, $u['password_hash'])) {
        $_SESSION['user'] = ['id' => $u['id'], 'full_name' => $u['full_name'], 'role' => $u['role']];
        if ($u['role'] === 'admin') {
            header('Location: ' . url('/admin/destinations.php'));
        } else {
            header('Location: ' . url('/public/index.php'));
        }
        exit;
    }
    $error = 'Email hoặc mật khẩu không đúng.';
}

include __DIR__ . '/../includes/header.php';
?>

<h1 class="section-title">Đăng nhập</h1>
<p class="section-sub">Đăng nhập để xem giao diện cá nhân hoá và lịch trình của bạn.</p>

<div class="form-box" style="max-width:400px;">
  <?php if ($error): ?><p style="color:red;"><?= e($error) ?></p><?php endif; ?>
  <form method="post">
    <div class="form-group">
      <label>Email</label>
      <input type="email" name="email" required>
    </div>
    <div class="form-group">
      <label>Mật khẩu</label>
      <input type="password" name="password" required>
    </div>
    <button type="submit" class="btn">Đăng nhập</button>
  </form>
  <p style="margin-top:14px;font-size:13px;color:#777;">
    Chưa có tài khoản? <a href="<?= url('/public/register.php') ?>">Đăng ký ngay</a>
  </p>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
