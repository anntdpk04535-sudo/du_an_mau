<?php
declare(strict_types=1);

/**
 * Context trước khi tạo lịch trình (không gọi Gemini — rẻ và nhanh):
 * POST {origin:{type,lat,lng,label,accommodation_id}, days, radius_km}
 * → {success, origin, weather, advisories[], nearby:{destinations,foods,accommodations}}.
 * UI gọi ngay khi khách chọn điểm xuất phát để hiển thị dự báo + gợi ý quanh nơi ở.
 */

require_once __DIR__ . '/../includes/content_helpers.php';
require_once __DIR__ . '/../includes/geo.php';
require_once __DIR__ . '/../includes/weather.php';

header('Content-Type: application/json; charset=utf-8');

$in = jsonRequest();
$days = max(1, min(7, (int)($in['days'] ?? 3)));
$radiusKm = max(5.0, min(80.0, (float)($in['radius_km'] ?? 25)));
$originIn = is_array($in['origin'] ?? null) ? $in['origin'] : [];
$db = getDB();

$origin = geoResolveOriginInput($db, $originIn);

if ($origin === null) {
    echo json_encode(['success' => false, 'error' => 'Điểm xuất phát không hợp lệ hoặc thiếu tọa độ.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$forecast = weatherFetchForecast($db, (float)$origin['lat'], (float)$origin['lng'], $days);

$advisories = weatherAdvisories($forecast);

$pick = fn(array $rows, array $cols) => array_map(fn($r) => array_intersect_key($r, array_flip($cols)), $rows);
$nearby = [
    'destinations' => $pick(
        geoFindNearby($db, 'destinations', (float)$origin['lat'], (float)$origin['lng'], $radiusKm, 8),
        ['id', 'name', 'slug', 'address', 'latitude', 'longitude', 'image_url', 'indoor_type', 'distance_km', 'rating']
    ),
    'foods' => $pick(
        geoFindNearby($db, 'foods', (float)$origin['lat'], (float)$origin['lng'], $radiusKm, 8),
        ['id', 'name', 'slug', 'address', 'latitude', 'longitude', 'image_url', 'entity_type', 'price_min', 'price_max', 'distance_km']
    ),
    'accommodations' => $pick(
        geoFindNearby($db, 'accommodations', (float)$origin['lat'], (float)$origin['lng'], $radiusKm, 8),
        ['id', 'name', 'slug', 'address', 'latitude', 'longitude', 'image_url', 'accommodation_type', 'price_min', 'price_max', 'distance_km']
    ),
];

echo json_encode([
    'success' => true,
    'origin' => $origin,
    'radius_km' => $radiusKm,
    'weather' => $forecast,
    'advisories' => $advisories,
    'nearby' => $nearby,
], JSON_UNESCAPED_UNICODE);
