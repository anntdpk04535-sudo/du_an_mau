<?php
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = 'Đặt lại mật khẩu - Đắk Lắk Travel AI';
$message = '';
$error = '';
$tokenValid = false;
$token = $_GET['token'] ?? '';
$db = getDB();

if ($token) {
    $stmt = $db->prepare("SELECT id, reset_token_expires FROM users WHERE reset_token = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    
    if ($user) {
        if (strtotime($user['reset_token_expires']) > time()) {
            $tokenValid = true;
        } else {
            $error = 'Link đặt lại mật khẩu đã hết hạn. Vui lòng yêu cầu link mới.';
        }
    } else {
        $error = 'Link không hợp lệ.';
    }
} else {
    $error = 'Không tìm thấy token.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tokenValid) {
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';
    
    if (strlen($password) < 6) {
        $error = 'Mật khẩu cần tối thiểu 6 ký tự.';
    } elseif ($password !== $passwordConfirm) {
        $error = 'Mật khẩu nhập lại không khớp.';
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $update = $db->prepare("UPDATE users SET password_hash = ?, reset_token = NULL, reset_token_expires = NULL WHERE id = ?");
        $update->execute([$hash, $user['id']]);
        
        $message = 'Mật khẩu đã được đặt lại thành công. Bạn có thể đăng nhập bằng mật khẩu mới.';
        $tokenValid = false; // Ẩn form
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div style="max-width:400px; margin: 40px auto; background:white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,.05);">
    <h2 style="margin-top:0; color:var(--green-900); text-align:center;"><?= __('reset_password') ?></h2>
    
    <?php if ($message): ?>
        <div style="background:#dcfce7; color:#166534; padding:12px; border-radius:8px; margin-bottom:16px; font-size:14px; text-align:center;">
            ✅ <?= $message ?>
        </div>
        <div style="text-align:center;">
            <a href="<?= url('/public/login.php') ?>" class="btn"><?= __('go_to_login') ?></a>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div style="background:#fee2e2; color:#991b1b; padding:12px; border-radius:8px; margin-bottom:16px; font-size:14px; text-align:center;">
            ❌ <?= e($error) ?>
        </div>
        <?php if (!$tokenValid && !$message): ?>
            <div style="text-align:center; margin-top:16px;">
                <a href="<?= url('/public/forgot_password.php') ?>" style="color:var(--green-700); font-weight:600; text-decoration:none;">Quay lại Quên mật khẩu</a>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <?php if ($tokenValid): ?>
        <form method="POST">
            <div class="form-group">
                <label><?= __('new_password_label') ?></label>
                <input type="password" name="password" required minlength="6">
            </div>
            <div class="form-group">
                <label><?= __('repassword_label') ?></label>
                <input type="password" name="password_confirm" required minlength="6">
            </div>
            <button type="submit" class="btn" style="width:100%;">💾 <?= __('update_password') ?></button>
        </form>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>