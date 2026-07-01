<?php
require_once __DIR__ . '/../includes/functions.php';
$pageTitle = 'Đăng nhập Admin';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND role = 'admin'");
    $stmt->execute([$email]);
    $u = $stmt->fetch();

    if ($u && password_verify($password, $u['password_hash'])) {
        $_SESSION['user'] = ['id' => $u['id'], 'full_name' => $u['full_name'], 'role' => $u['role']];
        header('Location: ' . url('/admin/destinations.php'));
        exit;
    }
    $error = 'Email hoặc mật khẩu không đúng.';
}

include __DIR__ . '/../includes/header.php';
?>

<h1 class="section-title">Đăng nhập Quản trị</h1>

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
    Tạo tài khoản admin bằng cách insert vào bảng <code>users</code> với
    <code>role='admin'</code> và <code>password_hash</code> tạo bằng <code>password_hash()</code> của PHP.
  </p>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
