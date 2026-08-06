<?php
require_once __DIR__ . '/../includes/content_helpers.php';
require_once __DIR__ . '/../includes/geo.php';
require_once __DIR__ . '/../includes/weather.php';
require_once __DIR__ . '/../includes/rag.php';

header('Content-Type: application/json; charset=utf-8');

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$days = max(1, min(10, (int)($input['days'] ?? 2)));
$prefs = is_array($input['prefs'] ?? null) ? $input['prefs'] : [];
$notes = trim((string)($input['notes'] ?? ''));
$radiusKm = max(5.0, min(80.0, (float)($input['radius_km'] ?? 30)));
$useWeather = !isset($input['use_weather']) || (bool)$input['use_weather'];

$lang = $_SESSION['lang'] ?? 'vi';

$db = null;
try { $db = getDB(); } catch (Exception $e) { /* fallback: chạy không DB như hành vi cũ */ }

// Điểm xuất phát (tùy chọn): GPS / khách sạn đang ở / địa chỉ tự nhập.
$origin = null;
if ($db && is_array($input['origin'] ?? null)) {
    $origin = geoResolveOriginInput($db, $input['origin']);
}

// Dự báo thời tiết tại khu vực xuất phát (mặc định Buôn Ma Thuột nếu chưa chọn origin).
$forecast = null;
$advisories = [];
if ($db && $useWeather) {
    $forecast = weatherFetchForecast(
        $db,
        $origin['lat'] ?? WEATHER_DEFAULT_LAT,
        $origin['lng'] ?? WEATHER_DEFAULT_LNG,
        min($days, 7)
    );
    $advisories = weatherAdvisories($forecast);
}

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

$prefsText = $prefs ? implode(', ', $prefs) : ($lang === 'en' ? 'no specific preferences' : 'không có yêu cầu cụ thể');

// RAG shortlist thay cho việc dump toàn bộ destinations: truy vấn theo sở thích + ghi chú,
// chấm điểm 0.6 semantic + 0.4 gần origin. Fallback về summary đầy đủ khi RAG trả quá ít.
$ragQuery = trim(implode(' ', $prefs) . ' ' . $notes . ' du lịch điểm đến Đắk Lắk Phú Yên');
$ragResults = [];
if ($db) {
    try {
        $ragResults = ragSearchGeo($ragQuery, $origin['lat'] ?? null, $origin['lng'] ?? null, $radiusKm, 40);
    } catch (Exception $e) {
        $ragResults = [];
    }
}
$ragDestinations = array_values(array_filter($ragResults, fn($r) => $r['entity_type'] === 'destination'));
$ragFoodStay = array_values(array_filter($ragResults, fn($r) => $r['entity_type'] !== 'destination'));

if (count($ragDestinations) >= 8) {
    $destinationsContext = implode("\n", ragContextLines($ragDestinations, 30));
} else {
    $destinationsContext = getDestinationsSummaryForAI();
}

// Khối "ẩm thực & lưu trú" động thay cho danh sách hard-code:
// ưu tiên quanh origin, bù thêm kết quả RAG, cuối cùng là mục nổi bật trong DB.
$foodStayLines = [];
if ($db && $origin) {
    foreach (geoFindNearby($db, 'foods', (float)$origin['lat'], (float)$origin['lng'], $radiusKm, 8) as $r) {
        $foodStayLines[] = sprintf('- Ẩm thực ID %d: %s (cách nơi xuất phát %skm; %s)', $r['id'], $r['name'], $r['distance_km'], $r['address'] ?: 'chưa rõ địa chỉ');
    }
    foreach (geoFindNearby($db, 'accommodations', (float)$origin['lat'], (float)$origin['lng'], $radiusKm, 8) as $r) {
        $foodStayLines[] = sprintf('- Lưu trú ID %d: %s (cách nơi xuất phát %skm; %s)', $r['id'], $r['name'], $r['distance_km'], $r['address'] ?: 'chưa rõ địa chỉ');
    }
}
if (!$foodStayLines && $ragFoodStay) {
    $foodStayLines = ragContextLines($ragFoodStay, 12);
}
if (!$foodStayLines && $db) {
    try {
        if (tableExists($db, 'foods')) {
            foreach ($db->query("SELECT id,name,address FROM foods WHERE status='published' ORDER BY is_featured DESC, id DESC LIMIT 6")->fetchAll() ?: [] as $r) {
                $foodStayLines[] = sprintf('- Ẩm thực ID %d: %s (%s)', $r['id'], $r['name'], $r['address'] ?: 'chưa rõ địa chỉ');
            }
        }
        if (tableExists($db, 'accommodations')) {
            foreach ($db->query("SELECT id,name,address FROM accommodations WHERE status='published' ORDER BY is_featured DESC, id DESC LIMIT 6")->fetchAll() ?: [] as $r) {
                $foodStayLines[] = sprintf('- Lưu trú ID %d: %s (%s)', $r['id'], $r['name'], $r['address'] ?: 'chưa rõ địa chỉ');
            }
        }
    } catch (Exception $e) { /* bỏ qua, prompt vẫn hợp lệ khi thiếu khối này */ }
}
$foodStayContext = $foodStayLines ? implode("\n", $foodStayLines) : '';

// Khối ràng buộc địa lý + thời tiết chèn vào system prompt (dùng chung cho cả vi/en —
// Gemini xử lý tốt chỉ dẫn tiếng Việt trong prompt tiếng Anh).
$originBlock = '';
if ($origin) {
    $originBlock = "\nĐIỂM XUẤT PHÁT CỦA KHÁCH: {$origin['label']} (tọa độ {$origin['lat']}, {$origin['lng']}).\n"
        . "RÀNG BUỘC ĐỊA LÝ (BẮT BUỘC):\n"
        . "- Chỉ chọn điểm đến cách điểm xuất phát tối đa {$radiusKm}km (ưu tiên điểm có ghi khoảng cách trong danh sách).\n"
        . "- Các điểm trong CÙNG MỘT NGÀY phải nằm gần nhau thành cụm địa lý, tránh lịch trình nhảy xa rồi quay lại.\n"
        . "- Sắp thứ tự Sáng→Trưa→Chiều→Tối theo lộ trình di chuyển hợp lý, bắt đầu và kết thúc gần điểm xuất phát.\n"
        . "- Điền \"distance_from_origin_km\" (số km, lấy từ danh sách) cho mỗi item có destination_id; null nếu không rõ.\n";
}

$weatherBlock = '';
if ($forecast && !empty($forecast['available'])) {
    $weatherLines = implode("\n", weatherPromptLines($forecast));
    $weatherBlock = "\nDỰ BÁO THỜI TIẾT TẠI KHU VỰC ({$days} ngày tới):\n{$weatherLines}\n"
        . "RÀNG BUỘC THỜI TIẾT (BẮT BUỘC):\n"
        . "- Ngày có mưa rào hoặc dông: KHÔNG xếp thác nước, hồ, suối, trekking hay điểm 'ngoài trời'; ưu tiên bảo tàng, nhà dài, quán cà phê, chợ, điểm 'trong nhà'.\n"
        . "- Ngày có sương mù/mưa nhỏ: vẫn đi được nhưng nhắc khách mang áo mưa và cẩn thận đèo dốc trong trường \"reason\".\n"
        . "- Điền \"weather_note\" ngắn gọn cho item bị ảnh hưởng bởi thời tiết (ví dụ 'Đổi sang điểm trong nhà vì dự báo mưa'); chuỗi rỗng nếu không ảnh hưởng.\n";
}

$foodStaySection = $foodStayContext !== ''
    ? "\nẨM THỰC & LƯU TRÚ THAM KHẢO (khi điền \"suggestion\" hãy ưu tiên gợi ý đích danh từ danh sách này, kèm địa chỉ):\n{$foodStayContext}\n"
    : '';

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
          "price": "Estimated cost",
          "distance_from_origin_km": "Number of km from the guest origin (taken from the destination list), or null if unknown",
          "weather_note": "Short note when the forecast affected this choice, empty string otherwise"
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
Suggest realistic transport (motorbike, car, walking) suitable for the distance. Motorbike rental is typically 100k-150k VND/day; taxi/Grab is available in cities.
{$foodStaySection}{$originBlock}{$weatherBlock}
SYS;

    $userPrompt = "[#REQ-{$randomSeed}] Please create a Dak Lak travel itinerary for {$days} days.\n"
        . "Tourist preferences: {$prefsText}.\n"
        . ($notes !== '' ? "Extra requests: {$notes}.\n" : '')
        . ($origin ? "The guest starts from \"{$origin['label']}\": keep destinations within {$radiusKm}km and cluster each day geographically around the route.\n" : '')
        . (($forecast && !empty($forecast['available'])) ? "Apply the MANDATORY weather constraints from the system instructions when choosing outdoor spots.\n" : '')
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
          "price": "Ước tính chi phí",
          "distance_from_origin_km": "Số km từ điểm xuất phát của khách (lấy từ danh sách điểm đến), null nếu không rõ",
          "weather_note": "Ghi chú ngắn khi dự báo thời tiết ảnh hưởng lựa chọn này, chuỗi rỗng nếu không"
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
Gợi ý phương tiện đi lại thực tế (xe máy, ô tô, đi bộ, xe buýt) phù hợp nhất với quãng đường di chuyển. Thuê xe máy thường 100k-150k/ngày; taxi/Grab có sẵn ở thành phố.
Gợi ý mua sắm đặc sản khi phù hợp: cà phê, thịt nai khô, mật ong tự nhiên.
{$foodStaySection}{$originBlock}{$weatherBlock}
SYS;

    $userPrompt = "[#REQ-{$randomSeed}] Hãy lên lịch trình du lịch Đắk Lắk trong {$days} ngày.\n"
        . "Sở thích của du khách: {$prefsText}.\n"
        . ($notes !== '' ? "Yêu cầu thêm: {$notes}.\n" : '')
        . ($origin ? "Khách xuất phát từ \"{$origin['label']}\": chỉ chọn điểm trong bán kính {$radiusKm}km và gom cụm địa lý theo từng ngày.\n" : '')
        . (($forecast && !empty($forecast['available'])) ? "Tuân thủ RÀNG BUỘC THỜI TIẾT bắt buộc trong hướng dẫn hệ thống khi chọn điểm ngoài trời.\n" : '')
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

// Hậu kiểm địa lý: sắp lại các item có tọa độ trong ngày theo nearest-neighbour tính từ
// điểm xuất phát, nhưng giữ nguyên nhãn giờ ở từng vị trí (Sáng/Trưa/Chiều/Tối vẫn theo
// thứ tự thời gian) — chỉ hoán vị nội dung giữa các slot có tọa độ, không bỏ item nào.
$geoWarnings = [];
$startPoint = $origin ? ['lat' => (float)$origin['lat'], 'lng' => (float)$origin['lng']] : null;
foreach ($parsed['itinerary'] as &$day) {
    $items = $day['items'] ?? [];
    $geoIdx = [];
    $geoItems = [];
    foreach ($items as $i => $it) {
        if (isset($it['lat'], $it['lng']) && geoIsValidPoint((float)$it['lat'], (float)$it['lng'])) {
            $geoIdx[] = $i;
            $geoItems[] = $it;
        }
    }
    if (count($geoItems) >= 2) {
        $ordered = geoOrderNearestNeighbour($geoItems, $startPoint);
        foreach ($geoIdx as $k => $pos) {
            $slot = $items[$pos]['time'] ?? '';
            $items[$pos] = $ordered[$k];
            $items[$pos]['time'] = $slot;
        }
        $day['items'] = $items;
    }

    $dayNum = (int)($day['day'] ?? 0);
    $compact = geoDayCompactness(array_map(
        fn($it) => ['lat' => $it['lat'] ?? null, 'lng' => $it['lng'] ?? null],
        $day['items'] ?? []
    ));
    if ($compact['max_leg_km'] > 60) {
        $geoWarnings[] = "Ngày {$dayNum}: có chặng di chuyển dài ~{$compact['max_leg_km']}km giữa hai điểm liên tiếp, hãy cân nhắc lại lộ trình.";
    }
    if ($startPoint) {
        foreach ($day['items'] ?? [] as $it) {
            if (!isset($it['lat'], $it['lng']) || !geoIsValidPoint((float)$it['lat'], (float)$it['lng'])) continue;
            $dKm = geoHaversineMeters($startPoint['lat'], $startPoint['lng'], (float)$it['lat'], (float)$it['lng']) / 1000;
            if ($dKm > $radiusKm * 1.5) {
                $label = mb_substr((string)($it['activity'] ?? ''), 0, 60);
                $geoWarnings[] = sprintf('Ngày %d: "%s" cách điểm xuất phát ~%dkm, vượt bán kính đã chọn (%dkm).', $dayNum, $label, (int)round($dKm), (int)$radiusKm);
            }
        }
    }
}
unset($day);
$geoWarnings = array_slice(array_values(array_unique($geoWarnings)), 0, 8);

// Lưu vào MySQL
$itineraryId = null;
try {
    $user = currentUser();
    $userId = $user['id'] ?? null;

    $title = "Lịch trình {$days} ngày - " . ($prefsText !== 'không có yêu cầu cụ thể' ? $prefsText : 'Đắk Lắk');
    $hasOriginCols = columnExists($db, 'itineraries', 'origin_type');
    $stmt = $db->prepare($hasOriginCols
        ? "INSERT INTO itineraries (user_id, title, days, preferences, ai_raw_response, origin_type, origin_label, origin_lat, origin_lng, origin_accommodation_id, radius_km, weather_snapshot) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        : "INSERT INTO itineraries (user_id, title, days, preferences, ai_raw_response) VALUES (?, ?, ?, ?, ?)"
    );
    $insertParams = [$userId, $title, $days, $prefsText, $aiResponse];
    if ($hasOriginCols) {
        $insertParams = array_merge($insertParams, [
            $origin['type'] ?? 'none',
            $origin['label'] ?? null,
            $origin['lat'] ?? null,
            $origin['lng'] ?? null,
            $origin['accommodation_id'] ?? null,
            $origin ? $radiusKm : null,
            ($forecast && !empty($forecast['available'])) ? json_encode($forecast, JSON_UNESCAPED_UNICODE) : null,
        ]);
    }
    $stmt->execute($insertParams);
    $itineraryId = (int)$db->lastInsertId();

    $extendedItems = columnExists($db, 'itinerary_items', 'reason');
    $itemStmt = $db->prepare($extendedItems
        ? "INSERT INTO itinerary_items (itinerary_id, destination_id, day_number, time_slot, activity, address, transport, reason, suggestion, community_impact, price_min, price_max, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        : "INSERT INTO itinerary_items (itinerary_id, destination_id, day_number, time_slot, activity, address, transport, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );
    // Giá dạng chuỗi AI vẫn trả về frontend; các trường price_min/max dành cho dữ liệu có cấu trúc.

    $sort = 0;
    foreach ($parsed['itinerary'] as $day) {
        $dayNum = (int)($day['day'] ?? 1);
        foreach (($day['items'] ?? []) as $item) {
            $itemParams = [
                $itineraryId,
                $item['destination_id'] ?? null,
                $dayNum,
                $item['time'] ?? '',
                $item['activity'] ?? '',
                $item['address'] ?? '',
                $item['transport'] ?? '',
                $sort++,
            ];
            if ($extendedItems) array_splice($itemParams, 7, 0, [$item['reason'] ?? null, $item['suggestion'] ?? null, $item['community_impact'] ?? null, null, null]);
            $itemStmt->execute($itemParams);
        }
    }
} catch (Exception $e) {
    // Nếu lưu DB lỗi (vd chưa setup DB), vẫn trả kết quả AI về cho người dùng xem
    echo json_encode([
        'success' => true,
        'itinerary' => $parsed['itinerary'],
        'origin' => $origin,
        'weather' => $forecast,
        'advisories' => $advisories,
        'geo_warnings' => $geoWarnings,
        'warning' => 'Không lưu được vào database: ' . $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode([
    'success' => true,
    'itinerary_id' => $itineraryId,
    'itinerary' => $parsed['itinerary'],
    'origin' => $origin,
    'weather' => $forecast,
    'advisories' => $advisories,
    'geo_warnings' => $geoWarnings,
], JSON_UNESCAPED_UNICODE);
