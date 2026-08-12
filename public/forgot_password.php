<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/mailer.php';

$pageTitle = __('page_title_forgot');
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email)) {
        $error = __('forgot_enter_email');
    } else {
        $db = getDB();
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user) {
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', time() + 3600); // 1 hour
            
            $update = $db->prepare("UPDATE users SET reset_token = ?, reset_token_expires = ? WHERE email = ?");
            $update->execute([$token, $expires, $email]);
            
            $resetLink = url("/public/reset_password.php?token={$token}");
            
            $body = "
                <h3>Khôi phục mật khẩu</h3>
                <p>Bạn đã yêu cầu đặt lại mật khẩu cho tài khoản tại Đắk Lắk Travel AI.</p>
                <p>Vui lòng click vào link bên dưới để đặt lại mật khẩu (link này có hiệu lực trong 1 giờ):</p>
                <p><a href='{$resetLink}'>{$resetLink}</a></p>
                <p>Nếu bạn không yêu cầu, vui lòng bỏ qua email này.</p>
            ";
            
            if (sendEmail($email, "Khôi phục mật khẩu - Đắk Lắk Travel AI", $body)) {
                $message = __('forgot_email_sent');
            } else {
                $error = 'Chưa gửi được email thực tế do Mật khẩu ứng dụng (App Password) Gmail trong file .env chưa hợp lệ hoặc đã bị hủy.<div style="margin-top:12px; padding:12px; background:#ffffff; border:1px dashed #f43f5e; border-radius:10px; font-size:13px; color:#be123c;"><strong>🛠️ Thử nghiệm Môi trường Dev/Demo:</strong><br><a href="'.$resetLink.'" style="color:#e11d48; font-weight:bold; text-decoration:underline; display:inline-block; margin-top:4px;">Click vào đây để đặt lại mật khẩu ngay ➔</a></div>';
            }
        } else {
            // Đừng tiết lộ email có tồn tại hay không vì lý do bảo mật, cứ báo thành công
            $message = __('forgot_email_sent');
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
    background: linear-gradient(135deg, rgba(45, 106, 79, 0.8), rgba(27, 67, 50, 0.9)), url('https://images.unsplash.com/photo-1528127269322-539801943592?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80') center/cover;
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
        <h2><?= __('forgot_password') ?></h2>
        <p>Lấy lại quyền truy cập để tiếp tục hành trình khám phá và trải nghiệm cùng Đắk Lắk Travel AI.</p>
      </div>
    </div>
    
    <div class="auth-form-container">
      <h2 class="section-title"><?= __('forgot_password') ?></h2>
      <p class="section-sub"><?= __('forgot_password_sub') ?></p>

      <?php if ($message): ?>
          <div style="background:#dcfce7; color:#166534; padding:14px; border-radius:12px; margin-bottom:20px; font-size:14px; text-align:center; border:1px solid #86efac;">
              ✅ <?= $message ?>
          </div>
      <?php endif; ?>
      
      <?php if ($error): ?>
          <div style="background:#fee2e2; color:#991b1b; padding:14px; border-radius:12px; margin-bottom:20px; font-size:14px; text-align:left; border:1px solid #fca5a5;">
              ❌ <?= $error ?>
          </div>
      <?php endif; ?>

      <form method="POST">
          <div class="form-group">
              <label><?= __('email_label') ?></label>
              <input type="email" name="email" required placeholder="<?= __('email_label') ?>...">
          </div>
          <button type="submit" class="btn">✉️ <?= __('send_btn') ?></button>
      </form>
      
      <div style="text-align:center; margin-top:24px;">
          <a href="<?= url('/public/login.php') ?>" style="color:var(--green-700); text-decoration:none; font-weight:600; font-size:14px;">⬅ <?= __('back_to_login') ?></a>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>