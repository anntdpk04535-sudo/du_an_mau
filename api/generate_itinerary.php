<?php
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$days = max(1, min(10, (int)($input['days'] ?? 2)));
$prefs = is_array($input['prefs'] ?? null) ? $input['prefs'] : [];
$notes = trim((string)($input['notes'] ?? ''));

$lang = $_SESSION['lang'] ?? 'vi';

// Tạo seed ngẫu nhiên và temperature cao để mỗi lần gợi ý luôn khác nhau
$randomSeed = mt_rand(1000, 9999);
$approachTexts = [
    'vi' => [
        'Hãy ưu tiên các trải nghiệm ít người biết, độc đáo và khám phá góc khuất của địa phương.',
        'Hãy tập trung vào những điểm nổi bật nhất, kết hợp khám phá ẩm thực đặc sản vùng miền.',
        'Hãy sắp xếp theo lộ trình hình vòng, tối ưu thời gian di chuyển và nghỉ ngơi hợp lý.',
        'Hãy đề xuất hành trình phong phú, đa dạng phong cách từ thiên nhiên đến văn hoá bản địa.',
        'Hãy ưu tiên các hoạt động buổi sáng sớm và hoàng hôn để có trải nghiệm đẹp nhất.',
        'Hãy kết hợp các điểm tham quan với các quán cà phê địa phương và trải nghiệm chợ truyền thống.',
    ],
    'en' => [
        'Prioritize lesser-known, unique experiences and explore hidden local gems.',
        'Focus on the highlights, combined with exploring regional culinary specialties.',
        'Arrange a circular route to optimize travel time and resting periods.',
        'Propose a rich itinerary, diverse in styles from nature to indigenous culture.',
        'Prioritize early morning and sunset activities for the most beautiful experiences.',
        'Combine sightseeing spots with local cafes and traditional market experiences.',
    ]
];
$randomApproach = array_rand($approachTexts[$lang]);
$approachText = $approachTexts[$lang][$randomApproach];

$destinationsContext = getDestinationsSummaryForAI();
$prefsText = $prefs ? implode(', ', $prefs) : ($lang === 'en' ? 'no specific preferences' : 'không có yêu cầu cụ thể');

if ($lang === 'en') {
    $systemPrompt = <<<SYS
You are "DakLak OneTrip AI" - An assistant designing and operating the "Forest - Sea - Culture" itinerary, connecting the Central Highlands (Dak Lak) to the Eastern Sea (Phu Yen).
You MUST ONLY suggest destinations from the provided list below (do not invent other locations).

LIST OF DESTINATIONS (Dak Lak & Phu Yen):
{$destinationsContext}

Always respond ONLY with valid JSON (no extra text, no markdown, no ``` backticks), following this exact structure:
{
  "itinerary": [
    {
      "day": 1,
      "title": "Short theme for the day",
      "items": [
        {
          "time": "Morning",
          "destination_id": "Exact ID (number) from the list above, or null if this activity is not linked to any specific destination in the list",
          "activity": "Main sightseeing/entertainment activity",
          "reason": "Reason for choosing based on preferences, health, weather",
          "community_impact": "Expected money to local community (e.g. 'Expected 200,000 VND to locals')",
          "suggestion": "Suggest specific restaurant, hotel/homestay names. ALWAYS include street address. Empty string if none.",
          "address": "Specific address of the destination",
          "transport": "Suggested transport",
          "price": "Estimated cost"
        },
        {
          "time": "Noon",
          "destination_id": null,
          "activity": "Lunch and rest",
          "reason": "...", "community_impact": "...",
          "suggestion": "Suggest a lunch restaurant. ESPECIALLY: Suggest a Homestay or Hotel for a noon rest. ALWAYS include address.",
          "address": "...", "transport": "...", "price": "..."
        },
        {"time": "Afternoon", "destination_id": "...", "activity": "...", "reason": "...", "community_impact": "...", "suggestion": "...", "address": "...", "transport": "...", "price": "..."},
        {
          "time": "Evening",
          "destination_id": null,
          "activity": "Dinner, walking, or resting",
          "reason": "...", "community_impact": "...",
          "suggestion": "Suggest a dinner restaurant. ESPECIALLY: Suggest a Homestay or Hotel to sleep. ALWAYS include address.",
          "address": "...", "transport": "...", "price": "..."
        }
      ]
    }
  ]
}
Always fill "address" with the specific address (from the destination list) whenever the activity is linked to a listed destination.
Always fill "price" with realistic estimated costs.
Suggest realistic transport (motorbike, car, walking) suitable for the distance.

YOU CAN INCORPORATE THESE SUGGESTIONS WHEN APPROPRIATE:
- Transport: Motorbike rental (100k-150k/day) or taxi/Grab.
- Food: Bun do Co Thu (Phan Dinh Giot - Le Hong Phong), Quan Ca Te (140 Le Thanh Tong), Pho 52 (52 Ngo Quyen). Cafes: Trung Nguyen Coffee Village (163 Ly Thai To), Arul Coffee (17 Tran Nhat Duat).
- Stay: Scenic homestays like Lee's House (55 St. 3, Cu Bur), Zan Homestay (37 Ho Giao), Lak Tented Camp (Lak Lake), Huyen Thoai Homestay (Buon Don), and An Homestay (137/91 Thoi Huu). Central hotels like Muong Thanh (81 Nguyen Tat Thanh), Elephants (142 Hai Ba Trung), Sai Gon - Ban Me (01-03 Phan Chu Trinh), and Hai Ba Trung Hotel (8 Hai Ba Trung). Eco-resorts like KoTam (789 Pham Van Dong).
SYS;

    $userPrompt = "[#REQ-{$randomSeed}] Please create a Dak Lak travel itinerary for {$days} days.\n"
        . "Tourist preferences: {$prefsText}.\n"
        . ($notes !== '' ? "Extra requests: {$notes}.\n" : '')
        . "{$approachText}\n"
        . "Distribute destinations logically, avoid traveling too far in one day, ensure realistic cost estimates, and respond EXACTLY in the requested JSON format.";

} else {
    $systemPrompt = <<<SYS
Bạn là "DakLak OneTrip AI" - Trợ lý thiết kế và điều hành hành trình "Rừng – Biển – Văn hóa", kết nối từ đại ngàn Tây Nguyên (Đắk Lắk) đến biển phía Đông (Phú Yên).
Bạn CHỈ được gợi ý các điểm đến có trong danh sách dữ liệu được cung cấp dưới đây (không tự bịa thêm địa điểm khác ngoài danh sách).

DANH SÁCH ĐIỂM ĐẾN (Đắk Lắk & Phú Yên):
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
          "destination_id": "Điền ID (số) chính xác của điểm đến từ danh sách ở trên. Điền null nếu hoạt động này không gắn với điểm đến cụ thể nào trong danh sách.",
          "activity": "Nội dung hoạt động tham quan/giải trí chính",
          "reason": "Lý do chọn điểm đến này dựa trên sở thích, sức khỏe, thời tiết của khách",
          "community_impact": "Dự kiến số tiền đổ vào cộng đồng (ví dụ: 'Dự kiến 200.000đ cho người dân/Cơ sở OCOP')",
          "suggestion": "Gợi ý đích danh tên quán ăn, tên khách sạn/homestay (nếu có). LUÔN ghi kèm địa chỉ đường cụ thể. Điền chuỗi rỗng nếu không có.",
          "address": "Địa chỉ cụ thể của địa điểm",
          "transport": "Gợi ý phương tiện và cách di chuyển",
          "price": "Ước tính chi phí"
        },
        {
          "time": "Trưa",
          "destination_id": null,
          "activity": "Nội dung ăn trưa và nghỉ ngơi",
          "reason": "...", "community_impact": "...",
          "suggestion": "Gợi ý quán ăn trưa. ĐẶC BIỆT: Gợi ý cụ thể thêm một Homestay hoặc Khách sạn để nghỉ ngơi buổi trưa. LUÔN ghi kèm địa chỉ đường.",
          "address": "...", "transport": "...", "price": "..."
        },
        {"time": "Chiều", "destination_id": "...", "activity": "...", "reason": "...", "community_impact": "...", "suggestion": "...", "address": "...", "transport": "...", "price": "..."},
        {
          "time": "Tối",
          "destination_id": null,
          "activity": "Nội dung ăn tối, dạo phố hoặc nghỉ ngơi",
          "reason": "...", "community_impact": "...",
          "suggestion": "Gợi ý quán ăn tối. ĐẶC BIỆT: Gợi ý cụ thể thêm một Homestay hoặc Khách sạn để ngủ qua đêm. LUÔN ghi kèm địa chỉ đường.",
          "address": "...", "transport": "...", "price": "..."
        }
      ]
    }
  ]
}
Luôn điền trường "address" bằng địa chỉ cụ thể (lấy đúng từ trường "địa chỉ" trong danh sách điểm đến) mỗi khi hoạt động gắn với 1 địa điểm trong danh sách.
Luôn điền trường "price" với mức giá ước tính thực tế phù hợp với từng hoạt động (vé vào cửa, chi phí ăn uống, dịch vụ...).
Gợi ý phương tiện đi lại thực tế (xe máy, ô tô, đi bộ, xe buýt) phù hợp nhất với quãng đường di chuyển và đối tượng khách du lịch.

BẠN CÓ THỂ LỒNG GHÉP CÁC GỢI Ý SAU VÀO LỊCH TRÌNH KHI PHÙ HỢP:
- Phương tiện: Thuê xe máy (100k-150k/ngày) hoặc taxi/Grab.
- Ẩm thực: Bún đỏ cô Thu (Ngã 4 Phan Đình Giót - Lê Hồng Phong), Quán Cà Te (140 Lê Thánh Tông), Phở hai tô 52 (52 Ngô Quyền), Bánh ướt chồng dĩa 45 (45 Lê Thánh Tông). Quán cà phê: Làng cà phê Trung Nguyên (163 Lý Thái Tổ), Arul Coffee (17 Trần Nhật Duật), SOUL Roastery (87 Nguyễn Khuyến).
- Lưu trú: Homestay view đẹp như Lee's House (55 đ.số 3, Cư Bur), Zan Homestay (37 Hồ Giáo), Lak Tented Camp (ven Hồ Lắk), Cư H'Lăm Restaurant & Homestay (Ea Pốk), An Homestay (137/91 Thôi Hữu). Khách sạn trung tâm như Mường Thanh (81 Nguyễn Tất Thành), Elephants (142 Hai Bà Trưng), Sài Gòn - Ban Mê (01-03 Phan Chu Trinh), Khách sạn Hai Bà Trưng (8 Hai Bà Trưng), Đam San Hotel (212 Nguyễn Công Trứ). Khu nghỉ dưỡng sinh thái như KoTam (789 Phạm Văn Đồng).
- Mua sắm: Cà phê, thịt nai khô, mật tự nhiên.
SYS;

    $userPrompt = "[#REQ-{$randomSeed}] Hãy lên lịch trình du lịch Đắk Lắk trong {$days} ngày.\n"
        . "Sở thích của du khách: {$prefsText}.\n"
        . ($notes !== '' ? "Yêu cầu thêm: {$notes}.\n" : '')
        . "{$approachText}\n"
        . "Hãy phân bổ hợp lý các điểm đến theo từng buổi, tránh di chuyển quá xa trong cùng 1 ngày, đảm bảo mỗi hoạt động đều có ước tính chi phí thực tế, và trả lời đúng định dạng JSON yêu cầu.";
}

// Dùng temperature cao (0.9–1.0) để AI sáng tạo và không lặp lại lịch trình
$temperature = round(mt_rand(85, 100) / 100, 2);
$aiResponse = callGemini(
    [['role' => 'user', 'content' => $userPrompt]],
    $systemPrompt,
    8192,
    $temperature,
    'application/json'
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

        // Đảm bảo item nhận đúng ID và tọa độ
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
