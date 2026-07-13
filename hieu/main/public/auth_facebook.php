<?php
require_once __DIR__ . '/../includes/functions.php';

$appId = $_ENV['FACEBOOK_APP_ID'] ?? '';
$redirectUri = url('/public/auth_facebook_callback.php');

if (empty($appId)) {
    die('<div style="padding: 20px; font-family: sans-serif;"><strong>Lỗi Cấu hình:</strong> Vui lòng cung cấp <code>FACEBOOK_APP_ID</code> trong file <code>.env</code></div>');
}

$state = bin2hex(random_bytes(16));
$_SESSION['oauth_state'] = $state;

$params = [
    'client_id'     => $appId,
    'redirect_uri'  => $redirectUri,
    'state'         => $state,
    'scope'         => 'public_profile'
];

$authUrl = 'https://www.facebook.com/v18.0/dialog/oauth?' . http_build_query($params);
header('Location: ' . $authUrl);
exit;
