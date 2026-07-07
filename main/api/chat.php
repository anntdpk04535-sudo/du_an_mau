<?php
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

$testKey = getenv('UNSPLASH_ACCESS_KEY');
if (!$testKey) {
    echo json_encode(['reply' => 'Lỗi: Không đọc được UNSPLASH_ACCESS_KEY', 'images' => []]);
    exit;
}
$input = json_decode(file_get_contents('php://input'), true) ?: [];
$userMessage = trim((string) ($input['message'] ?? ''));

if ($userMessage === '') {
    echo json_encode(['reply' => 'Bạn hãy nhập câu hỏi nhé!', 'images' => []]);
    exit;
}

if (empty($_SESSION['chat_session_id'])) {
    $_SESSION['chat_session_id'] = bin2hex(random_bytes(16));
}
$sessionId = $_SESSION['chat_session_id'];
$user = currentUser();
$userId = $user['id'] ?? null;

$destinationsContext = getDestinationsSummaryForAI();

$systemPrompt = <<<SYS
Bạn là trợ lý AI tư vấn du lịch thân thiện, am hiểu sâu về tỉnh Đắk Lắk, Việt Nam (Tây Nguyên).
Hãy trả lời ngắn gọn, tự nhiên, hữu ích, bằng tiếng Việt. Ưu tiên nhắc tới các điểm đến có trong danh sách dữ liệu sau nếu liên quan:

{$destinationsContext}

Nếu câu hỏi không liên quan đến du lịch Đắk Lắk, vẫn trả lời lịch sự nhưng khéo léo hướng người dùng quay lại chủ đề du lịch Đắk Lắk.
Không trả lời bằng JSON hay markdown phức tạp, chỉ trả lời bằng văn bản thường, súc tích (tối đa khoảng 150 từ).
Không đề cập việc bạn không thể gửi hình ảnh — hệ thống sẽ tự xử lý phần đó.
SYS;

try {
    $db = getDB();
    $histStmt = $db->prepare(
        "SELECT role, message FROM chat_logs WHERE session_id = ? ORDER BY id DESC LIMIT 10"
    );
    $histStmt->execute([$sessionId]);
    $history = array_reverse($histStmt->fetchAll());
} catch (Exception $e) {
    $history = [];
}

$messages = [];
foreach ($history as $h) {
    $messages[] = ['role' => $h['role'], 'content' => $h['message']];
}
$messages[] = ['role' => 'user', 'content' => $userMessage];

$reply = callGemini($messages, $systemPrompt, 600);

// Danh sách địa điểm để detect
$keywords = [
    'Hồ Lắk' => 'Ho Lak lake Dak Lak Vietnam',
    'Ho Lak' => 'Ho Lak lake Dak Lak Vietnam',
    'Thác Dray Nur' => 'Dray Nur waterfall Dak Lak Vietnam',
    'Dray Nur' => 'Dray Nur waterfall Dak Lak Vietnam',
    'Thác Dray Sáp' => 'Dray Sap waterfall Dak Lak Vietnam',
    'Dray Sap' => 'Dray Sap waterfall Dak Lak Vietnam',
    'Buôn Đôn' => 'Buon Don village elephant Dak Lak Vietnam',
    'Buon Don' => 'Buon Don village elephant Dak Lak Vietnam',
    'Yok Đôn' => 'Yok Don national park Dak Lak Vietnam',
    'Yok Don' => 'Yok Don national park Dak Lak Vietnam',
    'cà phê' => 'coffee plantation Buon Ma Thuot Dak Lak Vietnam',
    'Buôn Ma Thuột' => 'Buon Ma Thuot city Dak Lak Vietnam',
    'Buon Ma Thuot' => 'Buon Ma Thuot city Dak Lak Vietnam',
    'Buôn Akô Dhông' => 'Ako Dhong village Dak Lak Vietnam',
    'Ako Dhong' => 'Ako Dhong village Dak Lak Vietnam',
    'Hồ Ea Kao' => 'Ea Kao lake Dak Lak Vietnam',
    'Ea Kao' => 'Ea Kao lake Dak Lak Vietnam',
];

$searchQuery = null;
foreach ($keywords as $kw => $query) {
    if (mb_stripos($userMessage, $kw) !== false) {
        $searchQuery = $query;
        break;
    }
}

// Nếu không tìm thấy trong userMessage, tìm trong reply
if (!$searchQuery) {
    foreach ($keywords as $kw => $query) {
        if (mb_stripos($reply, $kw) !== false) {
            $searchQuery = $query;
            break;
        }
    }
}

// Lấy ảnh từ Unsplash
$images = [];
if ($searchQuery) {
    $accessKey = getenv('UNSPLASH_ACCESS_KEY');
    if ($accessKey) {
        $url = 'https://api.unsplash.com/search/photos?' . http_build_query([
            'query' => $searchQuery,
            'per_page' => 3,
            'orientation' => 'landscape',
        ]);

        $ctx = stream_context_create([
            'http' => [
                'timeout' => 8,
                'header' => "Authorization: Client-ID {$accessKey}\r\n",
            ]
        ]);
        $response = @file_get_contents($url, false, $ctx);

        if ($response !== false) {
            $data = json_decode($response, true);
            foreach ($data['results'] ?? [] as $item) {
                $images[] = [
                    'url' => $item['urls']['regular'],
                    'title' => $item['alt_description'] ?? $searchQuery,
                ];
            }
        }
    }
}

// Lưu lịch sử chat
try {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO chat_logs (session_id, user_id, role, message) VALUES (?, ?, 'user', ?)");
    $stmt->execute([$sessionId, $userId, $userMessage]);

    $stmt = $db->prepare("INSERT INTO chat_logs (session_id, user_id, role, message) VALUES (?, ?, 'assistant', ?)");
    $stmt->execute([$sessionId, $userId, $reply]);
} catch (Exception $e) {
    // Bỏ qua lỗi lưu DB
}

error_log("searchQuery: " . ($searchQuery ?? 'NULL'));
error_log("images count: " . count($images));
error_log("UNSPLASH_KEY: " . (getenv('UNSPLASH_ACCESS_KEY') ? 'CÓ' : 'KHÔNG CÓ'));
echo json_encode(['reply' => $reply, 'images' => $images]);