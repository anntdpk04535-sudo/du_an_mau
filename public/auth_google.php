<?php
require_once __DIR__ . '/../includes/functions.php';

$clientId = $_ENV['GOOGLE_CLIENT_ID'] ?? '';
$redirectUri = $_ENV['GOOGLE_REDIRECT_URI'] ?? url('/public/auth_google_callback.php');

if (empty($clientId)) {
    die('<div style="padding: 20px; font-family: sans-serif;"><strong>Lỗi Cấu hình:</strong> Vui lòng cung cấp <code>GOOGLE_CLIENT_ID</code> trong file <code>.env</code></div>');
}

$state = bin2hex(random_bytes(16));
$_SESSION['oauth_state'] = $state;

$params = [
    'client_id'     => $clientId,
    'redirect_uri'  => $redirectUri,
    'response_type' => 'code',
    'scope'         => 'email profile',
    'state'         => $state,
    'access_type'   => 'online'
];

$authUrl = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
header('Location: ' . $authUrl);
exit;
