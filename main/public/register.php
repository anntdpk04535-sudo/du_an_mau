<?php
require_once __DIR__ . '/../includes/functions.php';
$pageTitle = 'Đăng ký - Đắk Lắk Travel AI';
$error = '';
$success = '';

if (currentUser()) {
    header('Location: ' . url('/public/index.php'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';

    if ($fullName === '' || $email === '' || $password === '') {
        $error = 'Vui lòng điền đầy đủ thông tin.';
    } elseif ($password !== $passwordConfirm) {
        $error = 'Mật khẩu nhập lại không khớp.';
    } elseif (strlen($password) < 6) {
        $error = 'Mật khẩu cần tối thiểu 6 ký tự.';
    } else {
        $db = getDB();
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'Email này đã được đăng ký.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO users (full_name, email, password_hash, role) VALUES (?, ?, ?, 'user')");
            $stmt->execute([$fullName, $email, $hash]);

            $newId = (int)$db->lastInsertId();
            $_SESSION['user'] = ['id' => $newId, 'full_name' => $fullName, 'role' => 'user'];
            header('Location: ' . url('/public/index.php'));
            exit;
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<h1 class="section-title">Tạo tài khoản</h1>
<p class="section-sub">Đăng ký để lưu lịch trình và nhận gợi ý cá nhân hoá từ AI.</p>

<div class="form-box" style="max-width:420px;">
  <?php if ($error): ?><p style="color:red;"><?= e($error) ?></p><?php endif; ?>
  <form method="post">
    <div class="form-group">
      <label>Họ và tên</label>
      <input type="text" name="full_name" value="<?= e($_POST['full_name'] ?? '') ?>" required>
    </div>
    <div class="form-group">
      <label>Email</label>
      <input type="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" required>
    </div>
    <div class="form-group">
      <label>Mật khẩu</label>
      <input type="password" name="password" required>
    </div>
    <div class="form-group">
      <label>Nhập lại mật khẩu</label>
      <input type="password" name="password_confirm" required>
    </div>
    <button type="submit" class="btn">Đăng ký</button>
  </form>
  <p style="margin-top:14px;font-size:13px;color:#777;">
    Đã có tài khoản? <a href="<?= url('/public/login.php') ?>">Đăng nhập ngay</a>
  </p>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
