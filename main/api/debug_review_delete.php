<?php
// File debug tạm - XÓA SAU KHI DEBUG XONG
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

$user = currentUser();
echo json_encode([
    'session_status' => session_status(),
    'session_id'     => session_id(),
    'user'           => $user ? [
        'id'       => $user['id'],
        'name'     => $user['full_name'],
        'role'     => $user['role'],
    ] : null,
    'BASE_URL'       => defined('BASE_URL') ? BASE_URL : 'NOT_DEFINED',
    'php_version'    => PHP_VERSION,
]);
