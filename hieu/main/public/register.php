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
            
            $stmt = $db->prepare("INSERT INTO users (full_name, email, password_hash, role, is_verified, verification_token) VALUES (?, ?, ?, 'user', 1, ?)");
            $stmt->execute([$fullName, $email, $hash, $token]);

            $success = 'Đăng ký thành công! Bạn có thể đăng nhập ngay.';
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<style>
  .auth-wrapper {
    display: flex;
    min-height: calc(100vh - 200px); /* Adjust based on header/footer */
    align-items: center;
    justify-content: center;
    padding: 40px 0;
  }
  .auth-card {
    display: flex;
    max-width: 900px;
    width: 100%;
    background: white;
    border-radius: 24px;
    box-shadow: 0 20px 40px rgba(0,0,0,0.08);
    overflow: hidden;
  }
  .auth-image {
    flex: 1;
    background: linear-gradient(135deg, rgba(45, 106, 79, 0.8), rgba(27, 67, 50, 0.9)), url('https://images.unsplash.com/photo-1542323631-0d3ab5d496a8?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80') center/cover;
    display: flex;
    flex-direction: column;
    justify-content: center;
    padding: 50px;
    color: white;
    position: relative;
  }
  .auth-image::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0; bottom: 0;
    background: url('data:image/svg+xml;utf8,<svg width="20" height="20" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><circle cx="2" cy="2" r="2" fill="rgba(255,255,255,0.05)"/></svg>') repeat;
  }
  .auth-image-content {
    position: relative;
    z-index: 1;
  }
  .auth-image h2 {
    font-size: 36px;
    margin-bottom: 16px;
    line-height: 1.2;
    font-weight: 700;
  }
  .auth-image p {
    font-size: 16px;
    opacity: 0.9;
    line-height: 1.6;
  }
  .auth-form-container {
    flex: 1;
    padding: 60px 50px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    background: #fff;
  }
  .auth-form-container .section-title {
    margin-top: 0;
    font-size: 28px;
    color: var(--green-900);
    margin-bottom: 8px;
  }
  .auth-form-container .section-sub {
    font-size: 15px;
    margin-bottom: 30px;
    color: #666;
  }
  .auth-form-container .form-group {
    margin-bottom: 20px;
  }
  .auth-form-container .form-group label {
    font-weight: 600;
    color: #333;
    display: block;
    margin-bottom: 8px;
    font-size: 14px;
  }
  .auth-form-container .form-group input {
    padding: 14px 16px;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    background: #f8fafc;
    transition: all 0.3s ease;
    width: 100%;
    font-size: 15px;
  }
  .auth-form-container .form-group input:focus {
    border-color: var(--green-500);
    background: #fff;
    outline: none;
    box-shadow: 0 0 0 4px rgba(64, 145, 108, 0.1);
  }
  .auth-form-container .btn {
    width: 100%;
    padding: 14px;
    font-size: 16px;
    border-radius: 12px;
    margin-top: 10px;
    transition: transform 0.2s, background 0.3s;
    font-weight: 600;
  }
  .auth-form-container .btn:hover {
    transform: translateY(-2px);
  }
  @media (max-width: 768px) {
    .auth-card {
      flex-direction: column;
    }
    .auth-image {
      padding: 40px 30px;
    }
    .auth-form-container {
      padding: 40px 30px;
    }
  }
</style>

<div class="auth-wrapper">
  <div class="auth-card">
    <div class="auth-image">
      <div class="auth-image-content">
        <h2>Bắt đầu hành trình<br>của bạn</h2>
        <p>Tạo tài khoản miễn phí để lưu lại những điểm đến yêu thích và nhận gợi ý lịch trình AI cá nhân hóa.</p>
      </div>
    </div>
    <div class="auth-form-container">
      <h1 class="section-title"><?= __('register_title') ?></h1>
      <p class="section-sub"><?= __('register_sub') ?></p>

      <?php if ($error): ?>
        <div style="background: #fee2e2; color: #dc2626; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; border: 1px solid #f87171;">
          <svg style="width: 16px; height: 16px; display: inline-block; vertical-align: middle; margin-right: 6px; margin-top: -2px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
          <?= e($error) ?>
        </div>
      <?php endif; ?>
      <?php if ($success): ?>
        <div style="background: #dcfce7; color: #166534; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; border: 1px solid #4ade80;">
          <svg style="width: 16px; height: 16px; display: inline-block; vertical-align: middle; margin-right: 6px; margin-top: -2px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
          <?= $success ?>
        </div>
      <?php else: ?>
      <form method="post">
        <div class="form-group">
          <label><?= __('fullname_label') ?></label>
          <input type="text" name="full_name" value="<?= e($_POST['full_name'] ?? '') ?>" required placeholder="Nhập họ và tên">
        </div>
        <div class="form-group">
          <label><?= __('email_label') ?></label>
          <input type="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" required placeholder="Nhập địa chỉ email">
        </div>
        <div class="form-group">
          <label><?= __('password_label') ?></label>
          <input type="password" name="password" required placeholder="Nhập mật khẩu (tối thiểu 6 ký tự)">
        </div>
        <div class="form-group">
          <label><?= __('repassword_label') ?></label>
          <input type="password" name="password_confirm" required placeholder="Nhập lại mật khẩu">
        </div>
        <button type="submit" class="btn"><?= __('register') ?></button>
      </form>

      <div class="auth-divider">Hoặc đăng ký bằng</div>
      <a href="<?= url('/public/auth_google.php') ?>" class="btn-oauth btn-google">
        <svg width="20" height="20" viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
        Google
      </a>
      <a href="<?= url('/public/auth_facebook.php') ?>" class="btn-oauth btn-facebook">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="white"><path d="M12 2.04c-5.5 0-10 4.49-10 10.02 0 5 3.66 9.15 8.44 9.9v-7h-2.54V12.06h2.54V9.67c0-2.51 1.49-3.89 3.78-3.89 1.09 0 2.23.19 2.23.19v2.46h-1.26c-1.24 0-1.63.77-1.63 1.56v1.88h2.78l-.45 2.9h-2.33v7a10 10 0 0 0 8.44-9.9c0-5.53-4.5-10.02-10-10.02z"/></svg>
        Facebook
      </a>
      <?php endif; ?>
      <p style="margin-top:24px; text-align: center; font-size:14px; color:#666;">
        <?= __('has_account') ?> <a href="<?= url('/public/login.php') ?>" style="color: var(--green-700); font-weight: 600; text-decoration: none;"><?= __('login_now') ?></a>
      </p>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
