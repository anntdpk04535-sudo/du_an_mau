<?php
require_once __DIR__ . '/../includes/functions.php';

$appId = $_ENV['FACEBOOK_APP_ID'] ?? '';
$appSecret = $_ENV['FACEBOOK_APP_SECRET'] ?? '';
$redirectUri = url('/public/auth_facebook_callback.php');

if (empty($_GET['code'])) {
    die('Lỗi: Không nhận được mã xác thực từ Facebook.');
}

if (empty($_GET['state']) || $_GET['state'] !== ($_SESSION['oauth_state'] ?? '')) {
    die('Lỗi: CSRF token không hợp lệ.');
}

// 1. Lấy Access Token
$tokenUrl = "https://graph.facebook.com/v18.0/oauth/access_token?" . http_build_query([
    'client_id'     => $appId,
    'client_secret' => $appSecret,
    'redirect_uri'  => $redirectUri,
    'code'          => $_GET['code']
]);

$ch = curl_init($tokenUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$response = curl_exec($ch);
curl_close($ch);

$tokenData = json_decode($response, true);
if (empty($tokenData['access_token'])) {
    die('Lỗi khi lấy Access Token từ Facebook.');
}

// 2. Lấy thông tin người dùng
$profileUrl = "https://graph.facebook.com/me?" . http_build_query([
    'fields'       => 'id,name,email,picture.type(large)',
    'access_token' => $tokenData['access_token']
]);

$ch = curl_init($profileUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$userInfoResponse = curl_exec($ch);
curl_close($ch);

$userInfo = json_decode($userInfoResponse, true);
if (empty($userInfo['id'])) {
    die('Lỗi khi lấy thông tin người dùng từ Facebook.');
}

$facebookId = $userInfo['id'];
$email = $userInfo['email'] ?? null; // Facebook email is optional based on user privacy
$fullName = $userInfo['name'] ?? 'Facebook User';
$avatar = $userInfo['picture']['data']['url'] ?? null;

$db = getDB();

// 3. Kiểm tra xem user đã tồn tại chưa
$sql = "SELECT * FROM users WHERE facebook_id = ?";
$params = [$facebookId];

if ($email) {
    $sql .= " OR email = ?";
    $params[] = $email;
}
$sql .= " LIMIT 1";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$user = $stmt->fetch();

if ($user) {
    $updateStmt = $db->prepare("UPDATE users SET facebook_id = ?, avatar = COALESCE(?, avatar) WHERE id = ?");
    $updateStmt->execute([$facebookId, $avatar, $user['id']]);
    $userId = $user['id'];
} else {
    // 4. Tạo tài khoản mới. Nếu Facebook không cấp email, ta sinh email ảo để bypass UNIQUE constraint.
    if (!$email) {
        $email = $facebookId . '@facebook.local';
    }
    
    $insertStmt = $db->prepare("INSERT INTO users (facebook_id, full_name, email, avatar, role, status, is_verified) VALUES (?, ?, ?, ?, 'user', 'active', 1)");
    $insertStmt->execute([$facebookId, $fullName, $email, $avatar]);
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
