<?php
require_once __DIR__ . '/../includes/content_helpers.php';
require_once __DIR__ . '/../includes/geo.php';

header('Content-Type: application/json; charset=utf-8');

$q = trim((string)($_GET['q'] ?? ''));
$type = $_GET['type'] ?? 'all';
$db = getDB();

// Chế độ geo tuỳ chọn: có lat/lng hợp lệ thì prefilter bbox + sắp theo khoảng cách,
// không có thì giữ nguyên hành vi cũ (rating/id DESC).
$lat = isset($_GET['lat']) ? (float)$_GET['lat'] : null;
$lng = isset($_GET['lng']) ? (float)$_GET['lng'] : null;
$radiusKm = max(5.0, min(80.0, (float)($_GET['radius_km'] ?? 30)));
$geoMode = geoIsValidPoint($lat, $lng);
$bbox = $geoMode ? geoBoundingBox($lat, $lng, $radiusKm) : null;

$out = [];

if ($type === 'all' || $type === 'destination') {
    $sql = 'SELECT id,name,address,latitude,longitude,slug FROM destinations WHERE 1=1';
    $p = [];
    if ($q !== '') { $sql .= ' AND (name LIKE ? OR address LIKE ?)'; $p = ["%$q%", "%$q%"]; }
    if ($geoMode) {
        $sql .= ' AND latitude BETWEEN ? AND ? AND longitude BETWEEN ? AND ?';
        array_push($p, $bbox[0], $bbox[1], $bbox[2], $bbox[3]);
    }
    $s = $db->prepare($sql . ' ORDER BY rating DESC LIMIT ' . ($geoMode ? 100 : 30));
    $s->execute($p);
    foreach ($s->fetchAll() as $r) {
        $out[] = ['type' => 'destination', 'id' => (int)$r['id'], 'title' => $r['name'], 'address' => $r['address'], 'lat' => $r['latitude'], 'lng' => $r['longitude'], 'slug' => $r['slug']];
    }
}

foreach (['food' => 'foods', 'accommodation' => 'accommodations'] as $kind => $table) {
    if (!($type === 'all' || $type === $kind) || !tableExists($db, $table)) continue;
    $sql = "SELECT id,name,address,latitude,longitude FROM {$table} WHERE status='published'";
    $p = [];
    if ($q !== '') { $sql .= ' AND (name LIKE ? OR address LIKE ?)'; $p = ["%$q%", "%$q%"]; }
    if ($geoMode) {
        $sql .= ' AND latitude BETWEEN ? AND ? AND longitude BETWEEN ? AND ?';
        array_push($p, $bbox[0], $bbox[1], $bbox[2], $bbox[3]);
    }
    $s = $db->prepare($sql . ' ORDER BY id DESC LIMIT ' . ($geoMode ? 100 : 30));
    $s->execute($p);
    foreach ($s->fetchAll() as $r) {
        $out[] = ['type' => $kind, 'id' => (int)$r['id'], 'title' => $r['name'], 'address' => $r['address'], 'lat' => $r['latitude'], 'lng' => $r['longitude']];
    }
}

if ($geoMode) {
    $out = array_values(array_filter(array_map(function ($r) use ($lat, $lng, $radiusKm) {
        if ($r['lat'] === null || $r['lng'] === null) return null;
        $dKm = geoHaversineMeters($lat, $lng, (float)$r['lat'], (float)$r['lng']) / 1000;
        if ($dKm > $radiusKm) return null;
        $r['distance_km'] = round($dKm, 1);
        return $r;
    }, $out)));
    usort($out, fn($a, $b) => $a['distance_km'] <=> $b['distance_km']);
    $out = array_slice($out, 0, 60);
}

echo json_encode(['success' => true, 'options' => $out], JSON_UNESCAPED_UNICODE);
