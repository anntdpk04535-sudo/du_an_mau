<?php
declare(strict_types=1);

/**
 * Thư viện geo dùng chung cho lịch trình: khoảng cách, tìm gần đây,
 * phân cụm theo ngày và hậu kiểm độ "gọn" của tuyến.
 */

require_once __DIR__ . '/content_helpers.php';

const GEO_TABLES = ['destinations', 'foods', 'accommodations'];

// Trọng tâm vùng khi entity thiếu tọa độ: west ≈ Buôn Ma Thuột, east ≈ Tuy Hòa.
const GEO_REGION_CENTROIDS = [
    'west' => ['lat' => 12.6667, 'lng' => 108.0500],
    'east' => ['lat' => 13.0955, 'lng' => 109.3200],
];

function geoHaversineMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
{
    $r = 6371000;
    $p = pi() / 180;
    $x = sin(($lat2 - $lat1) * $p / 2) ** 2
        + cos($lat1 * $p) * cos($lat2 * $p) * sin(($lng2 - $lng1) * $p / 2) ** 2;
    return 2 * $r * asin(min(1, sqrt($x)));
}

function geoIsValidPoint(?float $lat, ?float $lng): bool
{
    if ($lat === null || $lng === null) return false;
    if (abs($lat) < 0.0001 && abs($lng) < 0.0001) return false; // 0,0 = dữ liệu rác
    // Bbox Việt Nam mở rộng — chặn tọa độ nhập nhầm/geocode sai nước.
    return $lat >= 8.0 && $lat <= 24.0 && $lng >= 102.0 && $lng <= 110.5;
}

/** Trả [minLat, maxLat, minLng, maxLng] để prefilter SQL bằng index. */
function geoBoundingBox(float $lat, float $lng, float $radiusKm): array
{
    $dLat = $radiusKm / 111.32;
    $dLng = $radiusKm / (111.32 * max(0.1, cos(deg2rad($lat))));
    return [$lat - $dLat, $lat + $dLat, $lng - $dLng, $lng + $dLng];
}

function geoRegionCentroid(?string $region): ?array
{
    return GEO_REGION_CENTROIDS[$region] ?? null;
}

/**
 * Tọa độ của một entity với chuỗi fallback:
 * cột lat/lng riêng → destination liên kết → centroid region → null.
 * Trả ['lat','lng','source'] hoặc null.
 */
function geoResolveEntityPoint(PDO $db, string $type, int $id): ?array
{
    $tableMap = ['destination' => 'destinations', 'food' => 'foods', 'accommodation' => 'accommodations'];
    $table = $tableMap[$type] ?? null;
    if ($table === null || !tableExists($db, $table)) return null;

    $hasDestFk = $table !== 'destinations' && columnExists($db, $table, 'destination_id');
    $hasRegion = columnExists($db, $table, 'region');
    $cols = 'latitude, longitude'
        . ($hasDestFk ? ', destination_id' : '')
        . ($hasRegion ? ', region' : '');
    $s = $db->prepare("SELECT {$cols} FROM {$table} WHERE id = ?");
    $s->execute([$id]);
    $row = $s->fetch();
    if (!$row) return null;

    $lat = $row['latitude'] !== null ? (float)$row['latitude'] : null;
    $lng = $row['longitude'] !== null ? (float)$row['longitude'] : null;
    if (geoIsValidPoint($lat, $lng)) {
        return ['lat' => $lat, 'lng' => $lng, 'source' => 'own'];
    }

    if ($hasDestFk && $row['destination_id']) {
        $d = $db->prepare('SELECT latitude, longitude FROM destinations WHERE id = ?');
        $d->execute([(int)$row['destination_id']]);
        if ($dest = $d->fetch()) {
            $dlat = $dest['latitude'] !== null ? (float)$dest['latitude'] : null;
            $dlng = $dest['longitude'] !== null ? (float)$dest['longitude'] : null;
            if (geoIsValidPoint($dlat, $dlng)) {
                return ['lat' => $dlat, 'lng' => $dlng, 'source' => 'destination'];
            }
        }
    }

    if ($hasRegion && ($centroid = geoRegionCentroid($row['region'] ?? null))) {
        return ['lat' => $centroid['lat'], 'lng' => $centroid['lng'], 'source' => 'region'];
    }
    return null;
}

/**
 * Chuẩn hoá origin từ payload client:
 * accommodation → tra tọa độ DB (không tin client); current/manual → validate lat/lng.
 * GPS làm tròn 3 chữ số (~100m) để không lưu vết chính xác của khách. Trả null nếu không hợp lệ.
 */
function geoResolveOriginInput(PDO $db, array $originIn): ?array
{
    $type = (string)($originIn['type'] ?? 'none');
    if ($type === 'accommodation' && (int)($originIn['accommodation_id'] ?? 0) > 0) {
        $accId = (int)$originIn['accommodation_id'];
        $point = geoResolveEntityPoint($db, 'accommodation', $accId);
        $s = $db->prepare('SELECT name FROM accommodations WHERE id = ?');
        $s->execute([$accId]);
        $name = (string)($s->fetchColumn() ?: '');
        if ($point && $name !== '') {
            return ['type' => 'accommodation', 'lat' => $point['lat'], 'lng' => $point['lng'], 'label' => $name, 'accommodation_id' => $accId, 'coord_source' => $point['source']];
        }
        return null;
    }
    if (in_array($type, ['current', 'manual'], true)) {
        $lat = isset($originIn['lat']) ? (float)$originIn['lat'] : null;
        $lng = isset($originIn['lng']) ? (float)$originIn['lng'] : null;
        if (geoIsValidPoint($lat, $lng)) {
            return [
                'type' => $type,
                'lat' => $type === 'current' ? round($lat, 3) : round($lat, 6),
                'lng' => $type === 'current' ? round($lng, 3) : round($lng, 6),
                'label' => mb_substr(trim((string)($originIn['label'] ?? '')), 0, 255) ?: ($type === 'current' ? 'Vị trí hiện tại' : 'Điểm tự chọn'),
            ];
        }
    }
    return null;
}

/**
 * Tìm entity trong bán kính quanh một điểm, sắp theo khoảng cách tăng dần.
 * Bounding box chạy trong SQL (dùng index), haversine chính xác lọc lại ở PHP.
 * Mỗi phần tử trả về kèm 'distance_km'.
 */
function geoFindNearby(PDO $db, string $entityTable, float $lat, float $lng, float $radiusKm, int $limit = 8): array
{
    if (!in_array($entityTable, GEO_TABLES, true) || !tableExists($db, $entityTable)) return [];
    if (!geoIsValidPoint($lat, $lng)) return [];

    [$minLat, $maxLat, $minLng, $maxLng] = geoBoundingBox($lat, $lng, $radiusKm);
    $hasStatus = columnExists($db, $entityTable, 'status');
    $sql = "SELECT * FROM {$entityTable}
            WHERE latitude IS NOT NULL AND longitude IS NOT NULL
              AND latitude BETWEEN ? AND ? AND longitude BETWEEN ? AND ?"
        . ($hasStatus ? " AND status = 'published'" : '');
    $s = $db->prepare($sql);
    $s->execute([$minLat, $maxLat, $minLng, $maxLng]);

    $out = [];
    foreach ($s->fetchAll() ?: [] as $row) {
        $dKm = geoHaversineMeters($lat, $lng, (float)$row['latitude'], (float)$row['longitude']) / 1000;
        if ($dKm <= $radiusKm) {
            $row['distance_km'] = round($dKm, 1);
            $out[] = $row;
        }
    }
    usort($out, fn($a, $b) => $a['distance_km'] <=> $b['distance_km']);
    return array_slice($out, 0, max(1, $limit));
}

/** Trọng tâm một cụm điểm [['lat','lng'], ...]. */
function geoCentroid(array $points): ?array
{
    $lat = 0.0; $lng = 0.0; $n = 0;
    foreach ($points as $p) {
        if (!isset($p['lat'], $p['lng'])) continue;
        $lat += (float)$p['lat'];
        $lng += (float)$p['lng'];
        $n++;
    }
    return $n > 0 ? ['lat' => $lat / $n, 'lng' => $lng / $n] : null;
}

/**
 * Phân cụm K điểm thành $days nhóm địa lý (greedy k-means-lite):
 * chọn $days hạt giống xa nhau nhất rồi gán mỗi điểm về hạt gần nhất.
 * Trả mảng $days phần tử, mỗi phần tử là mảng điểm (không cụm rỗng nếu đủ điểm).
 */
function geoClusterByDays(array $points, int $days): array
{
    $days = max(1, $days);
    $points = array_values(array_filter($points, fn($p) => isset($p['lat'], $p['lng'])));
    if (!$points) return array_fill(0, $days, []);
    if ($days === 1 || count($points) <= $days) {
        $clusters = array_fill(0, $days, []);
        foreach ($points as $i => $p) $clusters[$i % $days][] = $p;
        return $clusters;
    }

    // Hạt giống: điểm đầu + lặp chọn điểm xa nhất so với các hạt đã có.
    $seeds = [$points[0]];
    while (count($seeds) < $days) {
        $bestIdx = 0; $bestDist = -1.0;
        foreach ($points as $i => $p) {
            $minToSeed = INF;
            foreach ($seeds as $sd) {
                $minToSeed = min($minToSeed, geoHaversineMeters((float)$p['lat'], (float)$p['lng'], (float)$sd['lat'], (float)$sd['lng']));
            }
            if ($minToSeed > $bestDist) { $bestDist = $minToSeed; $bestIdx = $i; }
        }
        $seeds[] = $points[$bestIdx];
    }

    $clusters = array_fill(0, $days, []);
    foreach ($points as $p) {
        $best = 0; $bestDist = INF;
        foreach ($seeds as $k => $sd) {
            $d = geoHaversineMeters((float)$p['lat'], (float)$p['lng'], (float)$sd['lat'], (float)$sd['lng']);
            if ($d < $bestDist) { $bestDist = $d; $best = $k; }
        }
        $clusters[$best][] = $p;
    }
    return $clusters;
}

/**
 * Sắp xếp điểm theo nearest-neighbour, bắt đầu từ $start (nếu có) hoặc điểm đầu.
 * Điểm thiếu tọa độ giữ nguyên vị trí tương đối ở cuối.
 */
function geoOrderNearestNeighbour(array $points, ?array $start = null): array
{
    $movable = [];
    $fixed = [];
    foreach ($points as $p) {
        if (isset($p['lat'], $p['lng']) && geoIsValidPoint((float)$p['lat'], (float)$p['lng'])) $movable[] = $p;
        else $fixed[] = $p;
    }
    $route = [];
    $current = $start;
    while ($movable) {
        $best = 0; $bestDist = INF;
        if ($current !== null) {
            foreach ($movable as $k => $candidate) {
                $d = geoHaversineMeters((float)$current['lat'], (float)$current['lng'], (float)$candidate['lat'], (float)$candidate['lng']);
                if ($d < $bestDist) { $bestDist = $d; $best = $k; }
            }
        }
        $route[] = $movable[$best];
        $current = $movable[$best];
        array_splice($movable, $best, 1);
    }
    return array_merge($route, $fixed);
}

/**
 * Đo độ "gọn" của một ngày: ['max_leg_km','total_km','centroid'].
 * Dùng để hậu kiểm ràng buộc bán kính sau khi AI trả lịch trình.
 */
function geoDayCompactness(array $dayPoints): array
{
    $valid = array_values(array_filter($dayPoints, fn($p) => isset($p['lat'], $p['lng'])));
    $maxLeg = 0.0; $total = 0.0;
    for ($i = 1; $i < count($valid); $i++) {
        $leg = geoHaversineMeters((float)$valid[$i - 1]['lat'], (float)$valid[$i - 1]['lng'], (float)$valid[$i]['lat'], (float)$valid[$i]['lng']) / 1000;
        $total += $leg;
        $maxLeg = max($maxLeg, $leg);
    }
    return ['max_leg_km' => round($maxLeg, 1), 'total_km' => round($total, 1), 'centroid' => geoCentroid($valid)];
}
