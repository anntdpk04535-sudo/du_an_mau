<?php
declare(strict_types=1);

/**
 * Geocode địa chỉ người dùng nhập (mode "nhập tay" ở form lịch trình).
 * GET ?q=<địa chỉ> → {success, lat, lng, display_name} | {success:false, error}.
 * Rate limit theo session 10 lượt/phút; cache DB dùng chung với backfill.
 * Provider: Google (khi có GOOGLE_MAPS_API_KEY) → Nominatim.
 */

require_once __DIR__ . '/../includes/content_helpers.php';
require_once __DIR__ . '/../includes/geo.php';

header('Content-Type: application/json; charset=utf-8');

$q = trim((string)($_GET['q'] ?? ''));
if (mb_strlen($q) < 3 || mb_strlen($q) > 200) {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => 'Địa chỉ cần từ 3 đến 200 ký tự.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Rate limit: 10 request / 60 giây / session.
$now = time();
$_SESSION['geocode_hits'] = array_values(array_filter($_SESSION['geocode_hits'] ?? [], fn($t) => $now - $t < 60));
if (count($_SESSION['geocode_hits']) >= 10) {
    http_response_code(429);
    echo json_encode(['success' => false, 'error' => 'Bạn thao tác quá nhanh, vui lòng thử lại sau 1 phút.'], JSON_UNESCAPED_UNICODE);
    exit;
}
$_SESSION['geocode_hits'][] = $now;

$db = getDB();
$hash = hash('sha256', mb_strtolower($q));

if (tableExists($db, 'geocode_cache')) {
    $s = $db->prepare('SELECT latitude, longitude, display_name FROM geocode_cache WHERE query_hash = ?');
    $s->execute([$hash]);
    if ($row = $s->fetch()) {
        if ($row['latitude'] === null) {
            echo json_encode(['success' => false, 'error' => 'Không tìm thấy địa chỉ này.'], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(['success' => true, 'lat' => (float)$row['latitude'], 'lng' => (float)$row['longitude'], 'display_name' => (string)$row['display_name'], 'cached' => true], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }
}

$point = null;
$provider = 'nominatim';
$googleKey = getenv('GOOGLE_MAPS_API_KEY') ?: '';

if ($googleKey !== '') {
    $url = 'https://maps.googleapis.com/maps/api/geocode/json?region=vn&key=' . rawurlencode($googleKey) . '&address=' . rawurlencode($q);
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 8]);
    $data = json_decode((string)curl_exec($ch), true);
    curl_close($ch);
    $hit = $data['results'][0] ?? null;
    if ($hit && isset($hit['geometry']['location']['lat'])) {
        $provider = 'google';
        $point = ['lat' => (float)$hit['geometry']['location']['lat'], 'lng' => (float)$hit['geometry']['location']['lng'], 'label' => (string)($hit['formatted_address'] ?? $q)];
    }
}

// Google không có key / bị từ chối / không ra kết quả → luôn thử tiếp Nominatim,
// tránh cache âm oan cho địa chỉ hợp lệ chỉ vì key Google hỏng.
if (!$point) {
    $url = 'https://nominatim.openstreetmap.org/search?format=json&limit=1&countrycodes=vn&q=' . rawurlencode($q);
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 8, CURLOPT_USERAGENT => 'DakLakTravelAI/1.0 (itinerary origin)']);
    $data = json_decode((string)curl_exec($ch), true);
    curl_close($ch);
    $hit = is_array($data) && isset($data[0]['lat'], $data[0]['lon']) ? $data[0] : null;
    if ($hit) {
        $provider = 'nominatim';
        $point = ['lat' => (float)$hit['lat'], 'lng' => (float)$hit['lon'], 'label' => (string)($hit['display_name'] ?? $q)];
    }
}

if ($point && !geoIsValidPoint($point['lat'], $point['lng'])) $point = null;

if (tableExists($db, 'geocode_cache')) {
    $db->prepare('INSERT INTO geocode_cache(query_hash, query_text, latitude, longitude, display_name, provider) VALUES (?,?,?,?,?,?) ON DUPLICATE KEY UPDATE latitude=VALUES(latitude), longitude=VALUES(longitude), display_name=VALUES(display_name), provider=VALUES(provider)')
        ->execute([$hash, mb_substr($q, 0, 500), $point['lat'] ?? null, $point['lng'] ?? null, mb_substr($point['label'] ?? '', 0, 500), $provider]);
}

if (!$point) {
    echo json_encode(['success' => false, 'error' => 'Không tìm thấy địa chỉ này. Hãy thử thêm tên tỉnh/thành phố.'], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode(['success' => true, 'lat' => $point['lat'], 'lng' => $point['lng'], 'display_name' => $point['label'], 'cached' => false], JSON_UNESCAPED_UNICODE);
