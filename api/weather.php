<?php
/**
 * Weather API — wrapper mỏng quanh includes/weather.php.
 * Nhận ?lat=&lng=&days= (mặc định Buôn Ma Thuột, 1 ngày).
 * BC: giữ nguyên mọi key cũ (public/index.php phụ thuộc); chỉ BỔ SUNG
 * available/source/risk/daily. Khi API lỗi vẫn success:true + số liệu mặc định
 * như hành vi cũ, nhưng available=false để consumer mới biết là fallback.
 */
require_once __DIR__ . '/../includes/weather.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: max-age=600');

$lat = isset($_GET['lat']) ? (float)$_GET['lat'] : WEATHER_DEFAULT_LAT;
$lng = isset($_GET['lng']) ? (float)$_GET['lng'] : WEATHER_DEFAULT_LNG;
$days = isset($_GET['days']) ? max(1, min(7, (int)$_GET['days'])) : 1;
if (!geoIsValidPoint($lat, $lng)) {
    $lat = WEATHER_DEFAULT_LAT;
    $lng = WEATHER_DEFAULT_LNG;
}
$isDefaultLocation = abs($lat - WEATHER_DEFAULT_LAT) < 0.0001 && abs($lng - WEATHER_DEFAULT_LNG) < 0.0001;
$location = $isDefaultLocation ? 'Buôn Ma Thuột, Đắk Lắk' : sprintf('%.4f, %.4f', $lat, $lng);

$forecast = weatherFetchForecast(getDB(), $lat, $lng, $days);

if (empty($forecast['available']) || empty($forecast['current'])) {
    // Hành vi cũ: trả dữ liệu "êm dịu" để widget trang chủ không vỡ.
    echo json_encode([
        'success' => true,
        'available' => false,
        'source' => 'none',
        'location' => $location,
        'temperature' => 28,
        'apparent_temperature' => 31,
        'temp_max' => 30,
        'temp_min' => 23,
        'humidity' => 72,
        'wind_speed' => 12.5,
        'weather_code' => 2,
        'risk' => 'good',
        'condition_text_vi' => 'Nhiều mây, mát mẻ',
        'condition_text_en' => 'Partly Cloudy',
        'icon' => '⛅',
        'advice_vi' => 'Thời tiết cao nguyên mát mẻ, thích hợp để đi thác và thưởng thức cà phê.',
        'advice_en' => 'Cool highland weather, ideal for visiting waterfalls and enjoying coffee.',
        'daily' => [],
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$current = $forecast['current'];
$today = $forecast['daily'][0] ?? null;
$info = weatherDescribeCode((int)$current['weather_code']);

echo json_encode([
    'success' => true,
    'available' => true,
    'source' => $forecast['source'],
    'location' => $location,
    'temperature' => $current['temperature'] ?? 28,
    'apparent_temperature' => $current['apparent_temperature'] ?? ($current['temperature'] ?? 28),
    'temp_max' => $today['temp_max'] ?? 30,
    'temp_min' => $today['temp_min'] ?? 23,
    'humidity' => $current['humidity'] ?? 70,
    'wind_speed' => $current['wind_speed'] ?? 10,
    'weather_code' => $current['weather_code'],
    'risk' => $current['risk'],
    'condition_text_vi' => $info['vi'],
    'condition_text_en' => $info['en'],
    'icon' => $info['icon'],
    'advice_vi' => $info['advice_vi'],
    'advice_en' => $info['advice_en'],
    'daily' => $forecast['daily'],
], JSON_UNESCAPED_UNICODE);
