<?php
require_once __DIR__ . '/../includes/functions.php';

$token = $_GET['token'] ?? '';
$error = '';
$success = '';

if (empty($token)) {
    $error = __('invalid_verify_link');
} else {
    $db = getDB();
    $stmt = $db->prepare("SELECT id FROM users WHERE verification_token = ? AND is_verified = 0");
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    
    if ($user) {
        $updateStmt = $db->prepare("UPDATE users SET is_verified = 1, verification_token = NULL WHERE id = ?");
        $updateStmt->execute([$user['id']]);
        $success = __('verify_success');
    } else {
        $error = __('invalid_verify_link');
    }
}

$pageTitle = __('page_title_verify');
include __DIR__ . '/../includes/header.php';
?>

<div style="text-align: center; padding: 100px 20px;">
    <?php if ($error): ?>
        <div style="font-size: 50px; margin-bottom: 20px;">❌</div>
        <h2 style="color: #991b1b;"><?= e($error) ?></h2>
    <?php elseif ($success): ?>
        <div style="font-size: 50px; margin-bottom: 20px;">✅</div>
        <h2 style="color: #166534;"><?= e($success) ?></h2>
    <?php endif; ?>
    
    <div style="margin-top: 30px;">
        <a href="<?= url('/public/login.php') ?>" class="btn"><?= __('go_to_login') ?></a>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>