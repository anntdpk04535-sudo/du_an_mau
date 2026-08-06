<?php
declare(strict_types=1);

/**
 * Backfill tọa độ cho foods / accommodations đang NULL lat/lng.
 * Chuỗi fallback: 1) copy từ destinations qua FK destination_id (geo_source='destination')
 *                 2) geocode địa chỉ qua Nominatim khi bật --geocode (geo_source='geocode')
 *                 3) vẫn thiếu → giữ NULL và liệt kê ra stdout, không bịa tọa độ.
 *
 * Cách dùng: php scripts/backfill_geo.php [--dry-run] [--only=foods|accommodations] [--limit=N] [--geocode]
 */

require_once __DIR__ . '/../includes/content_helpers.php';
require_once __DIR__ . '/../includes/geo.php';

$options = getopt('', ['dry-run', 'only::', 'limit::', 'geocode']);
$dryRun = isset($options['dry-run']);
$useGeocode = isset($options['geocode']);
$limit = isset($options['limit']) ? max(1, (int)$options['limit']) : 0;
$only = $options['only'] ?? null;

$tables = ['foods', 'accommodations'];
if ($only !== null) {
    if (!in_array($only, $tables, true)) {
        fwrite(STDERR, "--only phải là foods hoặc accommodations\n");
        exit(1);
    }
    $tables = [$only];
}

$db = getDB();

function geocodeNominatim(PDO $db, string $address): ?array
{
    $query = trim($address);
    if ($query === '') return null;
    $hash = hash('sha256', mb_strtolower($query));

    if (tableExists($db, 'geocode_cache')) {
        $s = $db->prepare('SELECT latitude, longitude FROM geocode_cache WHERE query_hash = ?');
        $s->execute([$hash]);
        if ($row = $s->fetch()) {
            return $row['latitude'] !== null ? ['lat' => (float)$row['latitude'], 'lng' => (float)$row['longitude']] : null;
        }
    }

    sleep(1); // Nominatim policy: tối đa 1 request/giây
    $url = 'https://nominatim.openstreetmap.org/search?format=json&limit=1&countrycodes=vn&q=' . rawurlencode($query);
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_USERAGENT => 'DakLakTravelAI/1.0 (backfill script)',
    ]);
    $raw = curl_exec($ch);
    curl_close($ch);
    $data = json_decode((string)$raw, true);
    $hit = is_array($data) && isset($data[0]['lat'], $data[0]['lon']) ? $data[0] : null;
    $point = $hit ? ['lat' => (float)$hit['lat'], 'lng' => (float)$hit['lon'], 'label' => (string)($hit['display_name'] ?? '')] : null;
    if ($point && !geoIsValidPoint($point['lat'], $point['lng'])) $point = null;

    if (tableExists($db, 'geocode_cache')) {
        $s = $db->prepare('INSERT INTO geocode_cache(query_hash, query_text, latitude, longitude, display_name, provider) VALUES (?,?,?,?,?,?) ON DUPLICATE KEY UPDATE latitude=VALUES(latitude), longitude=VALUES(longitude), display_name=VALUES(display_name)');
        $s->execute([$hash, mb_substr($query, 0, 500), $point['lat'] ?? null, $point['lng'] ?? null, mb_substr($point['label'] ?? '', 0, 500), 'nominatim']);
    }
    return $point;
}

$totalUpdated = 0;
foreach ($tables as $table) {
    if (!tableExists($db, $table) || !columnExists($db, $table, 'latitude')) {
        echo "Bỏ qua {$table}: bảng/cột chưa tồn tại.\n";
        continue;
    }
    $hasGeoSource = columnExists($db, $table, 'geo_source');
    $sql = "SELECT t.id, t.name, t.address, t.destination_id, d.latitude AS dlat, d.longitude AS dlng
            FROM {$table} t LEFT JOIN destinations d ON d.id = t.destination_id
            WHERE t.latitude IS NULL OR t.longitude IS NULL";
    if ($limit > 0) $sql .= ' LIMIT ' . $limit;
    $rows = $db->query($sql)->fetchAll() ?: [];
    echo "== {$table}: " . count($rows) . " bản ghi thiếu tọa độ ==\n";

    $missing = [];
    foreach ($rows as $row) {
        $point = null;
        $source = null;
        if ($row['dlat'] !== null && $row['dlng'] !== null && geoIsValidPoint((float)$row['dlat'], (float)$row['dlng'])) {
            $point = ['lat' => (float)$row['dlat'], 'lng' => (float)$row['dlng']];
            $source = 'destination';
        } elseif ($useGeocode && trim((string)$row['address']) !== '') {
            $point = geocodeNominatim($db, (string)$row['address']);
            $source = 'geocode';
        }

        if (!$point) {
            $missing[] = "#{$row['id']} {$row['name']}";
            continue;
        }

        printf("%s #%d %s → %.6f, %.6f (%s)%s\n", $table, $row['id'], $row['name'], $point['lat'], $point['lng'], $source, $dryRun ? ' [dry-run]' : '');
        if (!$dryRun) {
            $set = 'latitude = ?, longitude = ?';
            $params = [$point['lat'], $point['lng']];
            if ($hasGeoSource) {
                $set .= ', geo_source = ?, geocoded_at = current_timestamp()';
                $params[] = $source;
            }
            $params[] = $row['id'];
            $db->prepare("UPDATE {$table} SET {$set} WHERE id = ?")->execute($params);
            $totalUpdated++;
        }
    }

    if ($missing) {
        echo "Chưa xác định được tọa độ (giữ NULL):\n  " . implode("\n  ", $missing) . "\n";
        if (!$useGeocode) echo "Gợi ý: chạy lại với --geocode để thử geocode địa chỉ qua Nominatim.\n";
    }
}

echo "Hoàn tất. Đã cập nhật {$totalUpdated} bản ghi" . ($dryRun ? ' (dry-run, không ghi DB)' : '') . ".\n";
