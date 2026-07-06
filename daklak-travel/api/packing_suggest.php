<?php
/**
 * API: Gọi Gemini AI gợi ý hành lý thông minh theo thời tiết + lịch trình.
 *
 * POST body (JSON):
 *   - destinations: [{ name, hazard_type, address }]
 *   - weather: { temp, humidity, rain_1h, description, ... }
 *   - days: số ngày du lịch
 *   - season: "rainy" | "dry"
 */
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

$input = json_decode(file_get_contents('php://input'), true) ?: [];

$destinations = $input['destinations'] ?? [];
$weather      = $input['weather'] ?? [];
$days         = max(1, min(10, (int) ($input['days'] ?? 2)));
$season       = $input['season'] ?? 'unknown';

if (empty($destinations)) {
    echo json_encode(['success' => false, 'error' => 'Không có điểm đến nào trong lịch trình.']);
    exit;
}

// Tạo mô tả ngữ cảnh
$destList = [];
foreach ($destinations as $d) {
    $hazardVi = match ($d['hazard_type'] ?? 'none') {
        'waterfall' => 'thác nước (nguy cơ trơn trượt, lũ quét)',
        'forest'    => 'rừng (nguy cơ cháy rừng, côn trùng)',
        'river'     => 'hồ/sông (nguy cơ đuối nước)',
        'mountain'  => 'núi (nguy cơ sạt lở, gió mạnh)',
        default     => 'khu vực an toàn',
    };
    $destList[] = "- {$d['name']} ({$d['address']}): loại địa hình {$hazardVi}";
}
$destText = implode("\n", $destList);

$seasonVi = $season === 'rainy' ? 'mùa mưa (tháng 5–10)' : ($season === 'dry' ? 'mùa khô (tháng 11–4)' : 'không rõ');

$weatherText = '';
if (!empty($weather['temp'])) {
    $weatherText = "Thời tiết hiện tại: {$weather['description']}, nhiệt độ {$weather['temp']}°C, "
        . "độ ẩm {$weather['humidity']}%, gió {$weather['wind_speed']} m/s";
    if (($weather['rain_1h'] ?? 0) > 0) {
        $weatherText .= ", lượng mưa {$weather['rain_1h']}mm/h";
    }
    $weatherText .= '.';
} else {
    $weatherText = 'Không có dữ liệu thời tiết chính xác.';
}

$systemPrompt = <<<SYS
Bạn là chuyên gia chuẩn bị hành lý du lịch Tây Nguyên, Việt Nam.
Hãy gợi ý danh sách đồ dùng cần mang theo cho chuyến đi du lịch Đắk Lắk dựa trên thời tiết và các địa điểm tham quan.

Trả lời bằng tiếng Việt, ngắn gọn, thực tế. Chia thành các nhóm:
1. 👕 Trang phục
2. 🎒 Phụ kiện & bảo hộ
3. 💊 Y tế & sức khỏe
4. 📱 Thiết bị điện tử
5. 🍽️ Đồ ăn & nước uống (nếu cần)

Mỗi nhóm liệt kê 2-4 món đồ quan trọng nhất. Tổng không quá 20 mục.
Giải thích ngắn gọn lý do cho những món đồ đặc biệt (ví dụ: tại sao cần giày bám đá).
Không dùng markdown phức tạp, chỉ dùng text và emoji.
SYS;

$userPrompt = <<<MSG
Tôi sắp đi du lịch Đắk Lắk {$days} ngày. Hiện đang là {$seasonVi}.

{$weatherText}

Các điểm đến trong lịch trình:
{$destText}

Hãy gợi ý danh sách đồ dùng cần mang theo, ưu tiên các vật dụng an toàn phù hợp với địa hình và thời tiết thực tế.
MSG;

$reply = callGemini(
    [['role' => 'user', 'content' => $userPrompt]],
    $systemPrompt,
    1024,
    0.7
);

echo json_encode([
    'success'    => true,
    'suggestion' => $reply,
], JSON_UNESCAPED_UNICODE);
