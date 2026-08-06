<?php
declare(strict_types=1);

/**
 * Tầng thời tiết dùng chung: fetch Open-Meteo có cache DB + đánh giá rủi ro theo mã WMO.
 * Fallback 3 tầng: live → cache (kể cả hết TTL) → không khả dụng (available=false, KHÔNG bịa số liệu).
 */

require_once __DIR__ . '/content_helpers.php';
require_once __DIR__ . '/geo.php';

const WEATHER_CACHE_TTL = 1800; // 30 phút
const WEATHER_DEFAULT_LAT = 12.6667; // Buôn Ma Thuột
const WEATHER_DEFAULT_LNG = 108.0500;

/**
 * Rủi ro thời tiết theo mã WMO:
 * good = thoải mái ngoài trời, caution = sương/mưa phùn,
 * indoor_preferred = mưa rào (tránh thác/trekking), unsafe = dông sét.
 */
function weatherRiskFromCode(int $code, ?float $precipProbability = null): string
{
    if (in_array($code, [95, 96, 99], true)) return 'unsafe';
    if (in_array($code, [61, 63, 65, 66, 67, 80, 81, 82], true)) return 'indoor_preferred';
    if (in_array($code, [45, 48, 51, 53, 55, 56, 57], true)) return 'caution';
    if ($precipProbability !== null && $precipProbability >= 70) return 'caution';
    return 'good';
}

/** Mô tả vi/en + icon + lời khuyên cho một mã WMO (mapping gốc từ api/weather.php). */
function weatherDescribeCode(int $code): array
{
    switch ($code) {
        case 0:
            return ['vi' => 'Nắng đẹp, trời quang', 'en' => 'Clear sky & sunny', 'icon' => '☀️',
                'advice_vi' => 'Trời nắng đẹp, lý tưởng để tham quan Thác Dray Nur, Hồ Lắk và cắm trại ngoài trời.',
                'advice_en' => 'Beautiful sunny sky, ideal for exploring Dray Nur waterfall and Lak Lake.'];
        case 1:
        case 2:
            return ['vi' => 'Ít mây, mát mẻ', 'en' => 'Partly cloudy', 'icon' => '⛅',
                'advice_vi' => 'Thời tiết cao nguyên tuyệt đẹp, rất thích hợp đi dạo buôn làng và chụp ảnh.',
                'advice_en' => 'Pleasant highland weather, perfect for village walking tours and photography.'];
        case 3:
            return ['vi' => 'Nhiều mây', 'en' => 'Overcast', 'icon' => '☁️',
                'advice_vi' => 'Trời mát mẻ không gắt nắng, rất dễ chịu cho các hành trình trekking.',
                'advice_en' => 'Cool and overcast, very comfortable for outdoor trekking.'];
        case 45:
        case 48:
            return ['vi' => 'Có sương mù', 'en' => 'Foggy', 'icon' => '🌫️',
                'advice_vi' => 'Sương mù sáng sớm thơ mộng, hãy chú ý quan sát khi di chuyển đèo dốc.',
                'advice_en' => 'Poetic morning fog, please drive carefully on mountain passes.'];
        case 51:
        case 53:
        case 55:
            return ['vi' => 'Có mưa nhỏ', 'en' => 'Light drizzle', 'icon' => '🌧️',
                'advice_vi' => 'Có mưa nhỏ, thời điểm tuyệt vời để nhâm nhi ly cà phê Buôn Ma Thuột đậm đà.',
                'advice_en' => 'Light drizzle outside, perfect for cozying up with authentic coffee.'];
        case 61:
        case 63:
        case 65:
        case 80:
        case 81:
        case 82:
            return ['vi' => 'Có mưa rào', 'en' => 'Rain showers', 'icon' => '🌧️',
                'advice_vi' => 'Nên mang theo ô/áo mưa khi ra ngoài và ưu tiên các điểm tham quan trong nhà như Bảo tàng Cà phê.',
                'advice_en' => 'Bring an umbrella/raincoat and consider indoor attractions like the Coffee Museum.'];
        case 95:
        case 96:
        case 99:
            return ['vi' => 'Có dông sét', 'en' => 'Thunderstorm', 'icon' => '⛈️',
                'advice_vi' => 'Đang có dông sét, bạn nên nghỉ ngơi tại khách sạn/homestay hoặc quán cà phê.',
                'advice_en' => 'Thunderstorm expected, please stay safe indoors or in coffee lounges.'];
        default:
            return ['vi' => 'Thời tiết ôn hòa', 'en' => 'Mild weather', 'icon' => '🌤️',
                'advice_vi' => 'Thời tiết Đắk Lắk thích hợp cho mọi chuyến trải nghiệm.',
                'advice_en' => 'Dak Lak weather is great for travel experiences.'];
    }
}

/**
 * Dự báo thời tiết cho một điểm, $days ngày (1..7).
 * Trả về:
 * [
 *   'available' => bool,           // false = cả live lẫn cache đều thất bại
 *   'source'    => 'live'|'cache'|'cache_stale'|'none',
 *   'latitude','longitude',
 *   'current'   => ['temperature','apparent_temperature','humidity','wind_speed','weather_code','risk'] | null,
 *   'daily'     => [ ['date','weather_code','temp_max','temp_min','precipitation_sum','precipitation_probability_max','risk','icon','text_vi','text_en','advice_vi','advice_en'], ... ],
 * ]
 */
function weatherFetchForecast(PDO $db, float $lat, float $lng, int $days = 3): array
{
    $days = max(1, min(7, $days));
    if (!geoIsValidPoint($lat, $lng)) {
        $lat = WEATHER_DEFAULT_LAT;
        $lng = WEATHER_DEFAULT_LNG;
    }
    $gridKey = sprintf('%.2f_%.2f_%d', round($lat, 2), round($lng, 2), $days);
    $hasCache = tableExists($db, 'weather_cache');

    $cachedRow = null;
    if ($hasCache) {
        $s = $db->prepare('SELECT payload, fetched_at FROM weather_cache WHERE grid_key = ?');
        $s->execute([$gridKey]);
        $cachedRow = $s->fetch() ?: null;
        if ($cachedRow && (time() - strtotime((string)$cachedRow['fetched_at'])) < WEATHER_CACHE_TTL) {
            $payload = json_decode((string)$cachedRow['payload'], true);
            if (is_array($payload)) {
                $payload['available'] = true;
                $payload['source'] = 'cache';
                return $payload;
            }
        }
    }

    $url = 'https://api.open-meteo.com/v1/forecast?latitude=' . rawurlencode((string)$lat)
        . '&longitude=' . rawurlencode((string)$lng)
        . '&current=temperature_2m,relative_humidity_2m,apparent_temperature,is_day,precipitation,weather_code,wind_speed_10m'
        . '&daily=weather_code,temperature_2m_max,temperature_2m_min,precipitation_sum,precipitation_probability_max'
        . '&forecast_days=' . $days . '&timezone=Asia%2FHo_Chi_Minh';
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 6,
        CURLOPT_USERAGENT => 'DakLakTravelAI/1.0',
    ]);
    $raw = curl_exec($ch);
    curl_close($ch);
    $data = $raw !== false ? json_decode((string)$raw, true) : null;

    if (is_array($data) && isset($data['daily']['time'])) {
        $result = weatherNormalizePayload($data, $lat, $lng);
        if ($hasCache) {
            $db->prepare('INSERT INTO weather_cache(grid_key, payload, fetched_at) VALUES (?,?,current_timestamp()) ON DUPLICATE KEY UPDATE payload=VALUES(payload), fetched_at=current_timestamp()')
                ->execute([$gridKey, json_encode($result, JSON_UNESCAPED_UNICODE)]);
        }
        $result['available'] = true;
        $result['source'] = 'live';
        return $result;
    }

    // Live thất bại → dùng cache cũ (quá TTL vẫn hơn không có gì), gắn cờ rõ ràng.
    if ($cachedRow) {
        $payload = json_decode((string)$cachedRow['payload'], true);
        if (is_array($payload)) {
            $payload['available'] = true;
            $payload['source'] = 'cache_stale';
            return $payload;
        }
    }

    return ['available' => false, 'source' => 'none', 'latitude' => $lat, 'longitude' => $lng, 'current' => null, 'daily' => []];
}

/** Chuẩn hoá payload Open-Meteo về cấu trúc nội bộ (current + daily kèm risk/advice). */
function weatherNormalizePayload(array $data, float $lat, float $lng): array
{
    $current = $data['current'] ?? [];
    $code = (int)($current['weather_code'] ?? 0);
    $currentOut = [
        'temperature' => isset($current['temperature_2m']) ? round((float)$current['temperature_2m']) : null,
        'apparent_temperature' => isset($current['apparent_temperature']) ? round((float)$current['apparent_temperature']) : null,
        'humidity' => isset($current['relative_humidity_2m']) ? round((float)$current['relative_humidity_2m']) : null,
        'wind_speed' => isset($current['wind_speed_10m']) ? round((float)$current['wind_speed_10m'], 1) : null,
        'weather_code' => $code,
        'risk' => weatherRiskFromCode($code),
    ];

    $daily = [];
    $d = $data['daily'] ?? [];
    foreach (($d['time'] ?? []) as $i => $date) {
        $dayCode = (int)($d['weather_code'][$i] ?? 0);
        $precipProb = isset($d['precipitation_probability_max'][$i]) ? (float)$d['precipitation_probability_max'][$i] : null;
        $info = weatherDescribeCode($dayCode);
        $daily[] = [
            'date' => (string)$date,
            'weather_code' => $dayCode,
            'temp_max' => isset($d['temperature_2m_max'][$i]) ? round((float)$d['temperature_2m_max'][$i]) : null,
            'temp_min' => isset($d['temperature_2m_min'][$i]) ? round((float)$d['temperature_2m_min'][$i]) : null,
            'precipitation_sum' => isset($d['precipitation_sum'][$i]) ? round((float)$d['precipitation_sum'][$i], 1) : null,
            'precipitation_probability_max' => $precipProb !== null ? round($precipProb) : null,
            'risk' => weatherRiskFromCode($dayCode, $precipProb),
            'icon' => $info['icon'],
            'text_vi' => $info['vi'],
            'text_en' => $info['en'],
            'advice_vi' => $info['advice_vi'],
            'advice_en' => $info['advice_en'],
        ];
    }

    return ['latitude' => $lat, 'longitude' => $lng, 'current' => $currentOut, 'daily' => $daily];
}

/** Khuyến cáo hiển thị cho người dùng theo từng ngày rủi ro (vi). */
function weatherAdvisories(array $forecast): array
{
    $advisories = [];
    foreach (($forecast['daily'] ?? []) as $i => $day) {
        if ($day['risk'] === 'unsafe') {
            $advisories[] = sprintf('⛈️ Ngày %d (%s): dự báo dông sét — tránh hoàn toàn thác nước, hồ, trekking; ưu tiên bảo tàng, quán cà phê, nhà dài.', $i + 1, $day['date']);
        } elseif ($day['risk'] === 'indoor_preferred') {
            $advisories[] = sprintf('🌧️ Ngày %d (%s): dự báo mưa rào — hạn chế thác nước và điểm ngoài trời, nên xếp điểm trong nhà.', $i + 1, $day['date']);
        } elseif ($day['risk'] === 'caution') {
            $advisories[] = sprintf('🌫️ Ngày %d (%s): có sương mù/mưa nhỏ — mang áo mưa, cẩn thận khi di chuyển đèo dốc.', $i + 1, $day['date']);
        }
    }
    if (empty($forecast['available'])) {
        $advisories[] = 'ℹ️ Không lấy được dự báo thời tiết lúc này — lịch trình sẽ tạo mà không có ràng buộc thời tiết.';
    }
    return $advisories;
}

/**
 * Tóm tắt dự báo thành các dòng text cho prompt AI (vi).
 * Trả mảng dòng "Ngày 1 (2026-08-08): Có mưa rào, 23–30°C, mưa 12mm (75%) → hạn chế thác/ngoài trời".
 */
function weatherPromptLines(array $forecast): array
{
    if (empty($forecast['available']) || empty($forecast['daily'])) return [];
    $riskLabel = [
        'good' => 'thuận lợi cho hoạt động ngoài trời',
        'caution' => 'nên đề phòng (sương mù/mưa nhỏ)',
        'indoor_preferred' => 'ƯU TIÊN điểm trong nhà, tránh thác/suối/trekking',
        'unsafe' => 'DÔNG SÉT — không xếp hoạt động ngoài trời',
    ];
    $lines = [];
    foreach ($forecast['daily'] as $i => $day) {
        $precip = $day['precipitation_sum'] !== null
            ? sprintf(', mưa %smm (%s%%)', $day['precipitation_sum'], $day['precipitation_probability_max'] ?? '?')
            : '';
        $lines[] = sprintf('Ngày %d (%s): %s, %s–%s°C%s → %s',
            $i + 1, $day['date'], $day['text_vi'], $day['temp_min'] ?? '?', $day['temp_max'] ?? '?', $precip,
            $riskLabel[$day['risk']] ?? $day['risk']);
    }
    return $lines;
}
