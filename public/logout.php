<?php
require_once __DIR__ . '/../includes/functions.php';
$user = currentUser();
if ($user) {
    if (isset($_COOKIE['remember_token'])) {
        $db = getDB();
        $stmt = $db->prepare("UPDATE users SET remember_token = NULL WHERE id = ?");
        $stmt->execute([$user['id']]);
        setcookie('remember_token', '', time() - 3600, '/');
    }
}
unset($_SESSION['user']);
session_destroy();
header('Location: ' . url('/public/index.php'));
exit;
