<?php
require_once __DIR__ . '/../includes/functions.php';

// Hiển thị thông tin để biết redirect_uri chính xác
$autoUri  = url('/public/auth_google_callback.php');
$envUri   = $_ENV['GOOGLE_REDIRECT_URI'] ?? '(chưa set)';
$clientId = $_ENV['GOOGLE_CLIENT_ID'] ?? '(chưa set)';

echo '<pre style="font-family:monospace;font-size:14px;padding:20px;">';
echo "=== DEBUG GOOGLE OAUTH ===\n\n";
echo "URI tự sinh (url()):       " . $autoUri . "\n";
echo "URI trong .env:            " . $envUri . "\n\n";
echo "Client ID:                 " . $clientId . "\n\n";
echo "Hãy copy đúng một trong hai URI trên và dán vào:\n";
echo "Google Cloud Console > APIs & Services > Credentials\n";
echo "> OAuth 2.0 Client IDs > Authorized redirect URIs\n";
echo '</pre>';
