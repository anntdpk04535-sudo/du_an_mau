<?php
/**
 * API: Lấy thời tiết thực tế + đánh giá cấp độ cảnh báo an toàn.
 *
 * Query params:
 *   - lat, lng   : toạ độ địa điểm
 *   - hazard_type: none | waterfall | forest | river | mountain
 *
 * Trả về JSON { weather, alert_level, alert_title, alert_message, safety_tips, season }
 */
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

$lat        = (float) ($_GET['lat'] ?? 12.6667);
$lng        = (float) ($_GET['lng'] ?? 108.05);
$hazardType = $_GET['hazard_type'] ?? 'none';

// ── Xác định mùa (Đắk Lắk: mùa mưa tháng 5-10, mùa khô tháng 11-4) ──
$month  = (int) date('n');
$season = ($month >= 5 && $month <= 10) ? 'rainy' : 'dry';
$seasonVi = $season === 'rainy' ? 'Mùa mưa' : 'Mùa khô';

// ── Lấy thời tiết từ OpenWeatherMap (có cache 15 phút) ──
$weather = fetchWeatherData($lat, $lng);

// ── Đánh giá cấp độ cảnh báo ──
$alert = evaluateAlert($weather, $hazardType, $season);

echo json_encode([
    'weather'       => $weather,
    'alert_level'   => $alert['level'],
    'alert_title'   => $alert['title'],
    'alert_message' => $alert['message'],
    'safety_tips'   => $alert['tips'],
    'season'        => $season,
    'season_vi'     => $seasonVi,
], JSON_UNESCAPED_UNICODE);

// ═══════════════════════════════════════════════════════════════════════
// FUNCTIONS
// ═══════════════════════════════════════════════════════════════════════

/**
 * Gọi OpenWeatherMap Current Weather API.
 * Cache kết quả 15 phút trong thư mục tạm để giảm request.
 */
function fetchWeatherData(float $lat, float $lng): array
{
    $apiKey = getenv('OPENWEATHER_API_KEY') ?: '';

    // Fallback mặc định khi không có API key
    $default = [
        'temp'        => null,
        'feels_like'  => null,
        'humidity'    => null,
        'wind_speed'  => null,
        'rain_1h'     => 0,
        'rain_3h'     => 0,
        'description' => 'Không có dữ liệu',
        'icon'        => '01d',
        'source'      => 'fallback',
    ];

    if ($apiKey === '') {
        return $default;
    }

    // Cache key dựa trên toạ độ (làm tròn 2 chữ số)
    $cacheKey  = 'weather_' . round($lat, 2) . '_' . round($lng, 2);
    $cacheFile = sys_get_temp_dir() . '/' . $cacheKey . '.json';
    $cacheTTL  = 900; // 15 phút

    if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $cacheTTL) {
        $cached = json_decode(file_get_contents($cacheFile), true);
        if ($cached) {
            $cached['source'] = 'cache';
            return $cached;
        }
    }

    // Gọi API
    $url = 'https://api.openweathermap.org/data/2.5/weather?' . http_build_query([
        'lat'   => $lat,
        'lon'   => $lng,
        'appid' => $apiKey,
        'units' => 'metric',
        'lang'  => 'vi',
    ]);

    $ctx = stream_context_create(['http' => ['timeout' => 8]]);
    $response = @file_get_contents($url, false, $ctx);

    if ($response === false) {
        return $default;
    }

    $data = json_decode($response, true);
    if (!$data || ($data['cod'] ?? 0) != 200) {
        return $default;
    }

    $result = [
        'temp'        => round($data['main']['temp'] ?? 0, 1),
        'feels_like'  => round($data['main']['feels_like'] ?? 0, 1),
        'humidity'    => (int) ($data['main']['humidity'] ?? 0),
        'wind_speed'  => round($data['wind']['speed'] ?? 0, 1),
        'rain_1h'     => round($data['rain']['1h'] ?? 0, 1),
        'rain_3h'     => round($data['rain']['3h'] ?? 0, 1),
        'description' => ucfirst($data['weather'][0]['description'] ?? 'Không rõ'),
        'icon'        => $data['weather'][0]['icon'] ?? '01d',
        'source'      => 'api',
    ];

    // Lưu cache
    @file_put_contents($cacheFile, json_encode($result, JSON_UNESCAPED_UNICODE));

    return $result;
}

/**
 * Đánh giá cấp độ cảnh báo dựa trên thời tiết + loại hình rủi ro.
 */
function evaluateAlert(array $weather, string $hazardType, string $season): array
{
    $rain1h   = $weather['rain_1h'] ?? 0;
    $rain3h   = $weather['rain_3h'] ?? 0;
    $temp     = $weather['temp'] ?? null;
    $wind     = $weather['wind_speed'] ?? 0;
    $noData   = ($weather['source'] ?? '') === 'fallback';

    // ── Nếu không có dữ liệu thời tiết, cảnh báo theo mùa ──
    if ($noData) {
        return evaluateBySeasonOnly($hazardType, $season);
    }

    // ── Cấp ĐỎ (Danger) ──
    if ($hazardType === 'waterfall' && ($rain1h > 20 || $rain3h > 40)) {
        return [
            'level'   => 'danger',
            'title'   => '🔴 NGUY HIỂM — Nguy cơ lũ quét!',
            'message' => "Lượng mưa rất lớn ({$rain1h}mm/h). KHUYẾN CÁO KHÔNG tắm thác, không di chuyển sâu vào lòng suối. Nước có thể dâng đột ngột và cuốn trôi mọi thứ.",
            'tips'    => [
                'Tuyệt đối không xuống chân thác',
                'Di chuyển lên vùng đất cao ngay lập tức',
                'Gọi cứu hộ nếu mắc kẹt: 113 hoặc 115',
                'Theo dõi tin cảnh báo thời tiết liên tục',
            ],
        ];
    }

    if ($hazardType === 'river' && ($rain1h > 20 || $rain3h > 40)) {
        return [
            'level'   => 'danger',
            'title'   => '🔴 NGUY HIỂM — Nước dâng cao!',
            'message' => "Mưa lớn kéo dài ({$rain1h}mm/h). Mực nước hồ/sông có thể dâng nhanh. KHÔNG bơi, KHÔNG đi thuyền. Tránh xa mép nước.",
            'tips'    => [
                'Không bơi hoặc đi thuyền trong điều kiện mưa lớn',
                'Rời khỏi khu vực ven bờ',
                'Đợi ít nhất 2 giờ sau khi tạnh mưa trước khi hoạt động gần nước',
            ],
        ];
    }

    if ($hazardType === 'forest' && $season === 'dry' && $temp !== null && $temp > 40 && $wind > 8) {
        return [
            'level'   => 'danger',
            'title'   => '🔴 NGUY HIỂM — Nguy cơ cháy rừng cực cao!',
            'message' => "Nhiệt độ {$temp}°C, gió mạnh {$wind} m/s. Nguy cơ cháy rừng cực kỳ cao. Không vào rừng hôm nay.",
            'tips'    => [
                'Tuyệt đối không đốt lửa, không hút thuốc',
                'Không đi vào khu vực rừng sâu',
                'Liên hệ Ban quản lý Vườn quốc gia trước khi tham quan',
            ],
        ];
    }

    // ── Cấp VÀNG (Warning) ──
    if ($hazardType === 'waterfall' && ($rain1h > 5 || $rain3h > 15)) {
        return [
            'level'   => 'warning',
            'title'   => '⚠️ CẨN TRỌNG — Đường trơn trượt',
            'message' => "Đang có mưa ({$rain1h}mm/h). Đường đá quanh thác rất trơn. Hãy đi giày chống trượt và cẩn thận từng bước.",
            'tips'    => [
                'Mang giày bám đá, tránh dép lê hoặc giày vải',
                'Không tự ý bơi ở khu vực dưới chân thác',
                'Giữ điện thoại trong túi chống nước',
                'Đi theo đường mòn có lan can, không leo trèo',
            ],
        ];
    }

    if ($hazardType === 'river' && ($rain1h > 5 || $rain3h > 15)) {
        return [
            'level'   => 'warning',
            'title'   => '⚠️ CẨN TRỌNG — Nước hồ/sông dâng',
            'message' => "Mưa đang làm mực nước dâng dần. Hạn chế bơi và chèo thuyền. Mặc áo phao nếu lên thuyền.",
            'tips'    => [
                'Luôn mặc áo phao khi đi thuyền',
                'Không bơi xa bờ',
                'Trẻ em phải có người lớn giám sát',
                'Tránh khu vực bờ dốc, đất mềm',
            ],
        ];
    }

    if ($hazardType === 'forest' && $season === 'dry' && $temp !== null && $temp > 38) {
        return [
            'level'   => 'warning',
            'title'   => '⚠️ CẨN TRỌNG — Nắng nóng gay gắt',
            'message' => "Nhiệt độ hiện tại {$temp}°C. Nguy cơ cháy rừng và say nắng. Mang đủ nước, mặc đồ bảo vệ.",
            'tips'    => [
                'Mang tối thiểu 2 lít nước mỗi người',
                'Kem chống nắng SPF50+ và mũ rộng vành',
                'Không đốt lửa, không vứt tàn thuốc',
                'Mang bình xịt đuổi côn trùng',
                'Nghỉ ngơi dưới bóng mát mỗi 30 phút',
            ],
        ];
    }

    // Cảnh báo gió mạnh cho mọi địa hình
    if ($wind > 10) {
        return [
            'level'   => 'warning',
            'title'   => '⚠️ CẨN TRỌNG — Gió mạnh',
            'message' => "Tốc độ gió {$wind} m/s. Cẩn thận khi di chuyển ngoài trời, giữ chặt đồ đạc cá nhân.",
            'tips'    => [
                'Tránh đứng gần cây cao và cột điện',
                'Cất dọn đồ cắm trại cho chắc chắn',
                'Hạn chế hoạt động trên mặt nước',
            ],
        ];
    }

    // ── Cấp XANH (Safe) ──
    $safeTips = getSafeTipsByHazard($hazardType);
    $descWeather = $weather['description'] ?? '';
    $tempStr = $temp !== null ? "{$temp}°C" : '';

    return [
        'level'   => 'safe',
        'title'   => '🟢 AN TOÀN — Thời tiết thuận lợi',
        'message' => "Thời tiết hiện tại: {$descWeather}" . ($tempStr ? ", {$tempStr}" : '') . ". Điều kiện lý tưởng để tham quan và trải nghiệm.",
        'tips'    => $safeTips,
    ];
}

/**
 * Đánh giá theo mùa (fallback khi không có Weather API key).
 */
function evaluateBySeasonOnly(string $hazardType, string $season): array
{
    if ($season === 'rainy') {
        if ($hazardType === 'waterfall') {
            return [
                'level'   => 'warning',
                'title'   => '⚠️ CẨN TRỌNG — Đang là mùa mưa',
                'message' => 'Tháng này là mùa mưa tại Đắk Lắk. Đường đá quanh thác có thể rất trơn, nước chảy xiết. Cẩn thận và theo dõi thời tiết trước khi đi.',
                'tips'    => [
                    'Mang giày bám đá và áo mưa',
                    'Kiểm tra thời tiết trước khi khởi hành',
                    'Không tắm thác sau những cơn mưa lớn',
                    'Mang túi chống nước cho điện thoại',
                ],
            ];
        }
        if ($hazardType === 'river') {
            return [
                'level'   => 'warning',
                'title'   => '⚠️ CẨN TRỌNG — Mùa mưa, nước có thể dâng',
                'message' => 'Mùa mưa Đắk Lắk (tháng 5-10). Mực nước hồ/sông thay đổi liên tục. Cẩn thận khi hoạt động gần nước.',
                'tips'    => [
                    'Luôn mặc áo phao khi đi thuyền',
                    'Không bơi khi trời mưa hoặc vừa tạnh mưa',
                    'Giám sát trẻ em khi gần mép nước',
                ],
            ];
        }
    }

    if ($season === 'dry') {
        if ($hazardType === 'forest') {
            return [
                'level'   => 'warning',
                'title'   => '⚠️ CẨN TRỌNG — Mùa khô, cẩn thận cháy rừng',
                'message' => 'Đang là mùa khô tại Đắk Lắk. Rừng khộp khô nóng, nguy cơ cháy rừng cao. Mang đủ nước và trang phục phù hợp.',
                'tips'    => [
                    'Không đốt lửa trong rừng',
                    'Mang tối thiểu 2 lít nước mỗi người',
                    'Mặc quần áo dài tay tránh côn trùng',
                    'Mang kem chống nắng SPF50+',
                ],
            ];
        }
    }

    // Mặc định: an toàn
    return [
        'level'   => 'safe',
        'title'   => '🟢 AN TOÀN — Thời tiết bình thường',
        'message' => 'Không có cảnh báo đặc biệt cho khu vực này. Tuy nhiên luôn chuẩn bị đầy đủ trước khi tham quan.',
        'tips'    => getSafeTipsByHazard($hazardType),
    ];
}

/**
 * Mẹo an toàn mặc định theo loại địa hình.
 */
function getSafeTipsByHazard(string $type): array
{
    return match ($type) {
        'waterfall' => [
            'Mang giày bám đá để leo trèo an toàn',
            'Giữ khoảng cách với mép thác',
            'Mang theo túi chống nước cho thiết bị điện tử',
        ],
        'river' => [
            'Mặc áo phao khi đi thuyền',
            'Không bơi một mình',
            'Tránh khu vực bờ dốc, đất mềm',
        ],
        'forest' => [
            'Đi theo hướng dẫn viên, không tự ý đi sâu',
            'Mang đủ nước và đồ ăn nhẹ',
            'Mặc quần áo dài tay tránh côn trùng',
        ],
        'mountain' => [
            'Mang giày leo núi chuyên dụng',
            'Kiểm tra dự báo thời tiết trước khi đi',
            'Không leo núi khi trời tối hoặc mưa lớn',
        ],
        default => [
            'Mang theo nước uống đầy đủ',
            'Kem chống nắng và mũ nón',
            'Tôn trọng phong tục địa phương',
        ],
    };
}
