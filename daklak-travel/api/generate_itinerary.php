<?php
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$days = max(1, min(10, (int)($input['days'] ?? 2)));
$prefs = is_array($input['prefs'] ?? null) ? $input['prefs'] : [];
$notes = trim((string)($input['notes'] ?? ''));

// Tạo seed ngẫu nhiên và temperature cao để mỗi lần gợi ý luôn khác nhau
$randomSeed = mt_rand(1000, 9999);
$randomApproach = array_rand([
    'Hãy ưu tiên các trải nghiệm ít người biết, độc đáo và khám phá góc khuất của địa phương.',
    'Hãy tập trung vào những điểm nổi bật nhất, kết hợp khám phá ẩm thực đặc sản vùng miền.',
    'Hãy sắp xếp theo lộ trình hình vòng, tối ưu thời gian di chuyển và nghỉ ngơi hợp lý.',
    'Hãy đề xuất hành trình phong phú, đa dạng phong cách từ thiên nhiên đến văn hoá bản địa.',
    'Hãy ưu tiên các hoạt động buổi sáng sớm và hoàng hôn để có trải nghiệm đẹp nhất.',
    'Hãy kết hợp các điểm tham quan với các quán cà phê địa phương và trải nghiệm chợ truyền thống.',
]);
$approachTexts = [
    'Hãy ưu tiên các trải nghiệm ít người biết, độc đáo và khám phá góc khuất của địa phương.',
    'Hãy tập trung vào những điểm nổi bật nhất, kết hợp khám phá ẩm thực đặc sản vùng miền.',
    'Hãy sắp xếp theo lộ trình hình vòng, tối ưu thời gian di chuyển và nghỉ ngơi hợp lý.',
    'Hãy đề xuất hành trình phong phú, đa dạng phong cách từ thiên nhiên đến văn hoá bản địa.',
    'Hãy ưu tiên các hoạt động buổi sáng sớm và hoàng hôn để có trải nghiệm đẹp nhất.',
    'Hãy kết hợp các điểm tham quan với các quán cà phê địa phương và trải nghiệm chợ truyền thống.',
];
$approachText = $approachTexts[$randomApproach];

$destinationsContext = getDestinationsSummaryForAI();
$prefsText = $prefs ? implode(', ', $prefs) : 'không có yêu cầu cụ thể';

$systemPrompt = <<<SYS
Bạn là chuyên gia du lịch địa phương tại tỉnh Đắk Lắk, Việt Nam.
Bạn CHỈ được gợi ý các điểm đến có trong danh sách dữ liệu được cung cấp dưới đây (không tự bịa thêm địa điểm khác ngoài danh sách, có thể bổ sung gợi ý ăn uống/nghỉ ngơi chung nếu cần).

DANH SÁCH ĐIỂM ĐẾN:
{$destinationsContext}

Luôn trả lời CHỈ bằng JSON hợp lệ (không thêm text, không markdown, không dùng dấu ```), theo đúng cấu trúc:
{
  "itinerary": [
    {
      "day": 1,
      "title": "Tên chủ đề ngắn cho ngày",
      "items": [
        {
          "time": "Sáng",
          "activity": "Nội dung hoạt động, có thể nhắc tên địa điểm",
          "address": "Địa chỉ cụ thể của địa điểm (lấy đúng từ danh sách dữ liệu, để trống nếu không phải 1 địa điểm cụ thể)",
          "transport": "Gợi ý phương tiện và cách di chuyển (ví dụ: 'Nên thuê xe máy hoặc taxi đi khoảng 45 phút (~30km) theo quốc lộ 27', 'Đi bộ tham quan', hoặc 'Di chuyển bằng xe máy/ô tô tự lái khoảng 1 giờ (~45km)'), hãy ước lượng khoảng cách địa lý thực tế từ địa điểm trước đó hoặc từ trung tâm Buôn Ma Thuột.",
          "price": "Ước tính chi phí cho hoạt động này (ví dụ: 'Miễn phí', '20.000 - 50.000 VNĐ/người', '150.000 VNĐ/người', '50.000 - 200.000 VNĐ/người'). Hãy ước tính chi phí thực tế bao gồm vé vào cửa, ăn uống, hoặc dịch vụ liên quan."
        },
        {"time": "Trưa", "activity": "...", "address": "...", "transport": "...", "price": "..."},
        {"time": "Chiều", "activity": "...", "address": "...", "transport": "...", "price": "..."},
        {"time": "Tối", "activity": "...", "address": "...", "transport": "...", "price": "..."}
      ]
    }
  ]
}
Luôn điền trường "address" bằng địa chỉ cụ thể (lấy đúng từ trường "địa chỉ" trong danh sách điểm đến) mỗi khi hoạt động gắn với 1 địa điểm trong danh sách.
Luôn điền trường "price" với mức giá ước tính thực tế phù hợp với từng hoạt động (vé vào cửa, chi phí ăn uống, dịch vụ...).
Gợi ý phương tiện đi lại thực tế (xe máy, ô tô, đi bộ, xe buýt) phù hợp nhất với quãng đường di chuyển và đối tượng khách du lịch.

SYS;

$userPrompt = "[#REQ-{$randomSeed}] Hãy lên lịch trình du lịch Đắk Lắk trong {$days} ngày.\n"
    . "Sở thích của du khách: {$prefsText}.\n"
    . ($notes !== '' ? "Yêu cầu thêm: {$notes}.\n" : '')
    . "{$approachText}\n"
    . "Hãy phân bổ hợp lý các điểm đến theo từng buổi, tránh di chuyển quá xa trong cùng 1 ngày, đảm bảo mỗi hoạt động đều có ước tính chi phí thực tế, và trả lời đúng định dạng JSON yêu cầu.";

// Dùng temperature cao (0.9–1.0) để AI sáng tạo và không lặp lại lịch trình
$temperature = round(mt_rand(85, 100) / 100, 2);
$aiResponse = callGemini(
    [['role' => 'user', 'content' => $userPrompt]],
    $systemPrompt,
    4096,
    $temperature
);

// Cố gắng parse JSON từ AI (loại bỏ markdown fences nếu có, và chỉ lấy phần
// JSON nằm giữa dấu { đầu tiên và } cuối cùng, đề phòng AI thêm chữ thừa).
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

// Đối chiếu và khớp toạ độ điểm đến từ database
try {
    $db = getDB();
    $allDest = $db->query("SELECT id, name, slug, latitude, longitude, address FROM destinations")->fetchAll();
} catch (Exception $e) {
    $allDest = [];
}

foreach ($parsed['itinerary'] as &$day) {
    foreach (($day['items'] ?? []) as &$item) {
        $destId = null;
        $lat = null;
        $lng = null;
        $slug = null;

        $activityText = $item['activity'] ?? '';
        $addressText = $item['address'] ?? '';

        foreach ($allDest as $d) {
            // Khớp theo tên điểm đến trong mô tả hoặc khớp theo địa chỉ
            if (mb_stripos($activityText, $d['name']) !== false || ($addressText !== '' && mb_stripos($d['address'], $addressText) !== false)) {
                $destId = $d['id'];
                $lat = $d['latitude'] ? (float)$d['latitude'] : null;
                $lng = $d['longitude'] ? (float)$d['longitude'] : null;
                $slug = $d['slug'];
                break;
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

// Lưu vào MySQL
$itineraryId = null;
try {
    $user = currentUser();
    $userId = $user['id'] ?? null;

    $stmt = $db->prepare("INSERT INTO itineraries (user_id, title, days, preferences, ai_raw_response) VALUES (?, ?, ?, ?, ?)");
    $title = "Lịch trình {$days} ngày - " . ($prefsText !== 'không có yêu cầu cụ thể' ? $prefsText : 'Đắk Lắk');
    $stmt->execute([$userId, $title, $days, $prefsText, $aiResponse]);
    $itineraryId = (int)$db->lastInsertId();

    $itemStmt = $db->prepare(
        "INSERT INTO itinerary_items (itinerary_id, destination_id, day_number, time_slot, activity, address, transport, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );
    // Lưu ý: trường price chỉ dùng để trả về cho frontend, không lưu DB (có thể bổ sung cột sau)

    $sort = 0;
    foreach ($parsed['itinerary'] as $day) {
        $dayNum = (int)($day['day'] ?? 1);
        foreach (($day['items'] ?? []) as $item) {
            $itemStmt->execute([
                $itineraryId,
                $item['destination_id'] ?? null,
                $dayNum,
                $item['time'] ?? '',
                $item['activity'] ?? '',
                $item['address'] ?? '',
                $item['transport'] ?? '',
                $sort++,
            ]);
        }
    }
} catch (Exception $e) {
    // Nếu lưu DB lỗi (vd chưa setup DB), vẫn trả kết quả AI về cho người dùng xem
    echo json_encode([
        'success' => true,
        'itinerary' => $parsed['itinerary'],
        'warning' => 'Không lưu được vào database: ' . $e->getMessage(),
    ]);
    exit;
}

echo json_encode([
    'success' => true,
    'itinerary_id' => $itineraryId,
    'itinerary' => $parsed['itinerary'],
]);
