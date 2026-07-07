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
            $token = bin2hex(random_bytes(32));
            
            $stmt = $db->prepare("INSERT INTO users (full_name, email, password_hash, role, is_verified, verification_token) VALUES (?, ?, ?, 'user', 0, ?)");
            $stmt->execute([$fullName, $email, $hash, $token]);

            $verifyLink = url('/public/verify_email.php?token=' . $token);
            
            // Send verification email
            require_once __DIR__ . '/../includes/mailer.php';
            $subject = "Xác minh tài khoản Đắk Lắk Travel AI";
            $body = "Chào $fullName,<br><br>Cảm ơn bạn đã đăng ký tài khoản. Vui lòng click vào link bên dưới để xác minh email của bạn:<br><br><a href='$verifyLink'>$verifyLink</a><br><br>Trân trọng,<br>Đắk Lắk Travel AI";
            
            $sent = sendMail($email, $fullName, $subject, $body);
            if ($sent) {
                $success = 'Đăng ký thành công! Vui lòng kiểm tra email để xác minh tài khoản.';
            } else {
                // If mail fails (e.g. no SMTP configured), provide link for testing
                $success = 'Đăng ký thành công! (Test mode: <a href="'.$verifyLink.'">Bấm vào đây để xác minh</a>)';
            }
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<h1 class="section-title"><?= __('register_title') ?></h1>
<p class="section-sub"><?= __('register_sub') ?></p>

<div class="form-box" style="max-width:420px;">
  <?php if ($error): ?>
    <div style="background:#fee2e2;color:#991b1b;padding:12px;border-radius:8px;margin-bottom:16px;"><?= e($error) ?></div>
  <?php endif; ?>
  <?php if ($success): ?>
    <div style="background:#dcfce7;color:#166534;padding:12px;border-radius:8px;margin-bottom:16px;"><?= $success ?></div>
  <?php else: ?>
  <form method="post">
    <div class="form-group">
      <label><?= __('fullname_label') ?></label>
      <input type="text" name="full_name" value="<?= e($_POST['full_name'] ?? '') ?>" required>
    </div>
    <div class="form-group">
      <label><?= __('email_label') ?></label>
      <input type="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" required>
    </div>
    <div class="form-group">
      <label><?= __('password_label') ?></label>
      <input type="password" name="password" required>
    </div>
    <div class="form-group">
      <label><?= __('repassword_label') ?></label>
      <input type="password" name="password_confirm" required>
    </div>
    <button type="submit" class="btn" style="width:100%;"><?= __('register') ?></button>
  </form>
  <?php endif; ?>
  <p style="margin-top:14px;font-size:13px;color:#777;text-align:center;">
    <?= __('has_account') ?> <a href="<?= url('/public/login.php') ?>"><?= __('login_now') ?></a>
  </p>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
