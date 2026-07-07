<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/mailer.php';

$pageTitle = 'Quên mật khẩu - Đắk Lắk Travel AI';
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email)) {
        $error = 'Vui lòng nhập địa chỉ email.';
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
                $message = 'Một email hướng dẫn khôi phục mật khẩu đã được gửi đến bạn.';
            } else {
                // If SMTP is not configured, we'll simulate it for local testing if needed,
                // but let's just output an error or the link directly for testing purposes
                if (empty(getenv('SMTP_USER'))) {
                    $message = 'Hệ thống chưa cấu hình SMTP. (Môi trường test) <a href="'.$resetLink.'">Bấm vào đây để reset</a>';
                } else {
                    $error = 'Không thể gửi email. Vui lòng kiểm tra lại cấu hình SMTP.';
                }
            }
        } else {
            // Đừng tiết lộ email có tồn tại hay không vì lý do bảo mật, cứ báo thành công
            $message = 'Nếu email có trong hệ thống, bạn sẽ nhận được hướng dẫn khôi phục.';
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div style="max-width:400px; margin: 40px auto; background:white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,.05);">
    <h2 style="margin-top:0; color:var(--green-900); text-align:center;"><?= __('forgot_password') ?></h2>
    <p style="color:#666; font-size:14px; text-align:center; margin-bottom:20px;">
        <?= __('forgot_password_sub') ?>
    </p>

    <?php if ($message): ?>
        <div style="background:#dcfce7; color:#166534; padding:12px; border-radius:8px; margin-bottom:16px; font-size:14px; text-align:center;">
            ✅ <?= $message ?>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div style="background:#fee2e2; color:#991b1b; padding:12px; border-radius:8px; margin-bottom:16px; font-size:14px; text-align:center;">
            ❌ <?= e($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label><?= __('email_label') ?></label>
            <input type="email" name="email" required placeholder="<?= __('email_label') ?>...">
        </div>
        <button type="submit" class="btn" style="width:100%;">✉️ <?= __('send_btn') ?></button>
    </form>
    
    <div style="text-align:center; margin-top:16px;">
        <a href="<?= url('/public/login.php') ?>" style="color:var(--green-700); text-decoration:none; font-weight:600; font-size:14px;">⬅ <?= __('back_to_login') ?></a>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>