<?php
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$currentItinerary = $input['itinerary'] ?? null;

if (!$currentItinerary) {
    echo json_encode(['success' => false, 'message' => 'Dữ liệu lịch trình không hợp lệ.']);
    exit;
}

$lang = $_SESSION['lang'] ?? 'vi';

$destinationsContext = getDestinationsSummaryForAI();
$currentItineraryJson = json_encode(['itinerary' => $currentItinerary], JSON_UNESCAPED_UNICODE);

if ($lang === 'en') {
    $systemPrompt = <<<SYS
You are "DakLak OneTrip AI" - An assistant designing and operating the "Forest - Sea - Culture" itinerary.
Currently, there is HEAVY RAIN in the sightseeing area.
Your task:
1. Receive the current itinerary of the tourist.
2. Modify the itinerary: replace natural, outdoor destinations (waterfalls, lakes, trekking) with INDOOR destinations (World Coffee Museum, cafes, local specialty shopping, cultural spaces...).
3. Keep the number of days and main meals intact (if not affected by the weather).
4. Keep the original JSON structure.
5. Update the "reason" field to explain the change (e.g. "Due to heavy rain, changed to an indoor museum visit for safety").

LIST OF DESTINATIONS (Dak Lak & Phu Yen):
{$destinationsContext}

Always respond ONLY with valid JSON, following the old structure:
{
  "itinerary": [
    {
      "day": 1,
      "title": "Short theme for the day",
      "items": [
        {
          "time": "Morning",
          "destination_id": "Exact ID (number) from the list above, or null",
          "activity": "...",
          "reason": "...",
          "community_impact": "...",
          "suggestion": "...",
          "address": "...",
          "transport": "...",
          "price": "..."
        }
      ]
    }
  ]
}
SYS;
    $userPrompt = "Here is my current itinerary:\n" . $currentItineraryJson . "\n\nPlease adjust this itinerary because it is raining heavily, and outdoor spots are not possible.";
} else {
    $systemPrompt = <<<SYS
Bạn là "DakLak OneTrip AI" - Trợ lý thiết kế và điều hành hành trình "Rừng – Biển – Văn hóa".
Hiện tại, hệ thống ghi nhận có sự cố MƯA LỚN ở khu vực tham quan.
Nhiệm vụ của bạn:
1. Nhận vào Lịch trình hiện tại của khách.
2. Sửa đổi lịch trình đó: thay thế các địa điểm thiên nhiên, ngoài trời (thác nước, hồ, trekking) bằng các địa điểm TRONG NHÀ (Bảo tàng Thế giới Cà phê, quán cà phê, mua sắm đặc sản, không gian văn hóa...).
3. Giữ nguyên số ngày và các bữa ăn chính (nếu không ảnh hưởng bởi thời tiết).
4. Giữ nguyên cấu trúc JSON ban đầu.
5. Cập nhật trường "reason" để giải thích lý do thay đổi (ví dụ: "Do trời mưa lớn, đổi sang tham quan Bảo tàng trong nhà để đảm bảo an toàn").

DANH SÁCH ĐIỂM ĐẾN (Đắk Lắk & Phú Yên):
{$destinationsContext}

Luôn trả lời CHỈ bằng JSON hợp lệ, theo cấu trúc cũ:
{
  "itinerary": [
    {
      "day": 1,
      "title": "Tên chủ đề ngắn cho ngày",
      "items": [
        {
          "time": "Sáng",
          "destination_id": "Điền ID (số) chính xác của điểm đến từ danh sách ở trên. Điền null nếu không có.",
          "activity": "...",
          "reason": "...",
          "community_impact": "...",
          "suggestion": "...",
          "address": "...",
          "transport": "...",
          "price": "..."
        }
      ]
    }
  ]
}
SYS;
    $userPrompt = "Đây là lịch trình hiện tại của tôi:\n" . $currentItineraryJson . "\n\nHãy điều chỉnh lại lịch trình này vì trời đang mưa rất to, không thể đi các điểm ngoài trời.";
}

$temperature = 0.6; // Ít sáng tạo hơn so với tạo mới, chỉ tập trung sửa
$aiResponse = callGemini(
    [['role' => 'user', 'content' => $userPrompt]],
    $systemPrompt,
    8192,
    $temperature,
    'application/json'
);

$clean = trim($aiResponse);
$clean = preg_replace('/^```json\s*|\s*```$/m', '', $clean);
$clean = trim($clean, "` \n");

$firstBrace = strpos($clean, '{');
$lastBrace  = strrpos($clean, '}');
if ($firstBrace !== false && $lastBrace !== false && $lastBrace > $firstBrace) {
    $clean = substr($clean, $firstBrace, $lastBrace - $firstBrace + 1);
}

$parsed = json_decode($clean, true);

if (!$parsed || empty($parsed['itinerary'])) {
    echo json_encode([
        'success' => false,
        'message' => 'AI chưa trả về dữ liệu hợp lệ. Phản hồi gốc: ' . substr($aiResponse, 0, 300),
    ]);
    exit;
}

// Đối chiếu toạ độ (copy từ generate_itinerary)
try {
    $db = getDB();
    $allDest = $db->query("SELECT id, name, slug, latitude, longitude, address FROM destinations")->fetchAll();
} catch (Exception $e) {
    $allDest = [];
}

foreach ($parsed['itinerary'] as &$day) {
    foreach (($day['items'] ?? []) as &$item) {
        $destId = $item['destination_id'] ?? null;
        if (!is_numeric($destId)) {
            $destId = null;
        }

        $lat = null;
        $lng = null;
        $slug = null;

        if ($destId) {
            foreach ($allDest as $d) {
                if ($d['id'] == $destId) {
                    $lat = $d['latitude'] ? (float)$d['latitude'] : null;
                    $lng = $d['longitude'] ? (float)$d['longitude'] : null;
                    $slug = $d['slug'];
                    break;
                }
            }
        }

        $item['destination_id'] = $destId;
        $item['lat'] = $lat;
        $item['lng'] = $lng;
        $item['slug'] = $slug;
    }
}
unset($day);
unset($item);

echo json_encode([
    'success' => true,
    'itinerary' => $parsed['itinerary'],
]);
