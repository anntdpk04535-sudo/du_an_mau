<?php
require_once __DIR__ . '/../includes/functions.php';

$clientId = $_ENV['GOOGLE_CLIENT_ID'] ?? '';
$clientSecret = $_ENV['GOOGLE_CLIENT_SECRET'] ?? '';
$redirectUri = $_ENV['GOOGLE_REDIRECT_URI'] ?? url('/public/auth_google_callback.php');

if (empty($_GET['code'])) {
    die('Lỗi: Không nhận được mã xác thực từ Google.');
}

if (empty($_GET['state']) || $_GET['state'] !== ($_SESSION['oauth_state'] ?? '')) {
    die('Lỗi: CSRF token không hợp lệ.');
}

// 1. Đổi code lấy Access Token
$ch = curl_init('https://oauth2.googleapis.com/token');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'client_id'     => $clientId,
    'client_secret' => $clientSecret,
    'redirect_uri'  => $redirectUri,
    'grant_type'    => 'authorization_code',
    'code'          => $_GET['code']
]));
$response = curl_exec($ch);
curl_close($ch);

$tokenData = json_decode($response, true);
if (empty($tokenData['access_token'])) {
    die('Lỗi khi lấy Access Token từ Google.');
}

// 2. Lấy thông tin người dùng
$ch = curl_init('https://www.googleapis.com/oauth2/v2/userinfo');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $tokenData['access_token']]);
$userInfoResponse = curl_exec($ch);
curl_close($ch);

$userInfo = json_decode($userInfoResponse, true);
if (empty($userInfo['id']) || empty($userInfo['email'])) {
    die('Lỗi khi lấy thông tin người dùng từ Google.');
}

$googleId = $userInfo['id'];
$email = $userInfo['email'];
$fullName = $userInfo['name'] ?? 'Google User';
$avatar = $userInfo['picture'] ?? null;

$db = getDB();

// 3. Kiểm tra xem user đã tồn tại chưa (qua google_id hoặc email)
$stmt = $db->prepare("SELECT * FROM users WHERE google_id = ? OR email = ? LIMIT 1");
$stmt->execute([$googleId, $email]);
$user = $stmt->fetch();

if ($user) {
    // Nếu có email nhưng chưa có google_id -> Cập nhật google_id
    if (empty($user['google_id'])) {
        $updateStmt = $db->prepare("UPDATE users SET google_id = ?, avatar = IFNULL(avatar, ?) WHERE id = ?");
        $updateStmt->execute([$googleId, $avatar, $user['id']]);
    }
    $userId = $user['id'];
} else {
    // 4. Tạo tài khoản mới
    $insertStmt = $db->prepare("INSERT INTO users (google_id, full_name, email, avatar, role, status, is_verified) VALUES (?, ?, ?, ?, 'user', 'active', 1)");
    $insertStmt->execute([$googleId, $fullName, $email, $avatar]);
    $userId = $db->lastInsertId();
}

// Lấy thông tin user mới nhất để lưu session
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();


if ($user['status'] === 'banned') {
    die('Tài khoản của bạn đã bị khóa.');
}

// 5. Thiết lập Session và Redirect
session_regenerate_id(true);
$_SESSION['user'] = [
    'id' => $user['id'],
    'full_name' => $user['full_name'],
    'role' => $user['role'],
    'avatar' => $user['avatar'] ?? null
];

header('Location: ' . url('/public/index.php'));
exit;
