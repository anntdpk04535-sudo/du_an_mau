<?php
// Smoke test cho includes/geo.php — chạy: /Applications/XAMPP/xamppfiles/bin/php scripts/test_geo.php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/geo.php';

$fail = 0;
function ok(bool $cond, string $msg): void
{
    global $fail;
    if ($cond) {
        echo "  ✅ {$msg}\n";
    } else {
        $fail++;
        echo "  ❌ {$msg}\n";
    }
}

echo "== geoHaversineMeters ==\n";
// BMT (12.6667,108.05) ↔ Tuy Hoà (13.0955,109.3209) ≈ 145km (±3%)
$km = geoHaversineMeters(12.6667, 108.05, 13.0955, 109.3209) / 1000;
ok($km > 140 && $km < 151, sprintf('BMT ↔ Tuy Hoà = %.1f km (kỳ vọng ~145km ±3%%)', $km));
ok(abs(geoHaversineMeters(12.68, 108.04, 12.68, 108.04)) < 0.001, 'cùng một điểm → 0m');

echo "== geoIsValidPoint ==\n";
ok(geoIsValidPoint(0.0, 0.0) === false, '(0,0) → false');
ok(geoIsValidPoint(null, null) === false, '(null,null) → false');
ok(geoIsValidPoint(12.68, 108.04) === true, 'BMT (12.68,108.04) → true');
ok(geoIsValidPoint(13.09, 109.32) === true, 'Tuy Hoà → true');
ok(geoIsValidPoint(25.0, 105.0) === false, 'lat 25 (ngoài dải VN 8–24) → false');
ok(geoIsValidPoint(12.0, 100.0) === false, 'lng 100 (ngoài dải 102–110.5) → false');

echo "== geoBoundingBox ==\n";
[$minLat, $maxLat, $minLng, $maxLng] = geoBoundingBox(12.68, 108.04, 30);
ok($minLat < 12.68 && $maxLat > 12.68 && $minLng < 108.04 && $maxLng > 108.04, 'bbox bao quanh tâm');
$dLat = geoHaversineMeters($minLat, 108.04, $maxLat, 108.04) / 1000;
ok($dLat > 55 && $dLat < 65, sprintf('bề cao bbox ≈ 2×radius (%.1f km, kỳ vọng ~60)', $dLat));
$inKm = geoHaversineMeters(12.68, 108.04, 12.68 + 0.2, 108.04) / 1000; // ~22km về phía bắc
ok($inKm < 30 && (12.68 + 0.2) <= $maxLat, 'điểm cách ~22km vẫn nằm trong bbox 30km');

echo "== geoClusterByDays ==\n";
// 12 điểm chia 3 cụm địa lý cách xa nhau: BMT, Buôn Đôn, Tuy Hoà
$mk = fn(float $lat, float $lng) => ['lat' => $lat, 'lng' => $lng];
$points = [
    $mk(12.68, 108.04), $mk(12.69, 108.05), $mk(12.67, 108.03), $mk(12.70, 108.06), // BMT
    $mk(12.90, 107.79), $mk(12.91, 107.80), $mk(12.89, 107.78), $mk(12.92, 107.81), // Buôn Đôn
    $mk(13.09, 109.32), $mk(13.10, 109.33), $mk(13.08, 109.31), $mk(13.11, 109.30), // Tuy Hoà
];
$clusters = geoClusterByDays($points, 3);
ok(count($clusters) === 3, '12 điểm / 3 ngày → 3 cụm');
$total = array_sum(array_map('count', $clusters));
ok($total === 12, "tổng điểm sau phân cụm = 12 (thực tế: {$total})");
ok(count(array_filter($clusters, fn($c) => count($c) === 0)) === 0, 'không có cụm rỗng');
$compactAll = true;
foreach ($clusters as $c) {
    $comp = geoDayCompactness($c);
    if ($comp['max_leg_km'] > 10) $compactAll = false;
}
ok($compactAll, 'mỗi cụm gọn (max_leg_km ≤ 10 trong dữ liệu 3 nhóm cách xa)');

echo "== geoOrderNearestNeighbour ==\n";
$line = [$mk(12.90, 108.04), $mk(12.70, 108.04), $mk(12.80, 108.04)];
$ordered = geoOrderNearestNeighbour($line, $mk(12.60, 108.04));
ok(abs($ordered[0]['lat'] - 12.70) < 1e-9 && abs($ordered[1]['lat'] - 12.80) < 1e-9 && abs($ordered[2]['lat'] - 12.90) < 1e-9,
    'từ điểm xuất phát phía nam → sắp theo thứ tự 12.70 → 12.80 → 12.90');

echo "== geoResolveEntityPoint / geoResolveOriginInput (DB) ==\n";
try {
    $db = getDB();

    $dest = $db->query("SELECT id FROM destinations WHERE latitude IS NOT NULL AND longitude IS NOT NULL LIMIT 1")->fetch();
    if ($dest) {
        $p = geoResolveEntityPoint($db, 'destination', (int)$dest['id']);
        ok($p !== null && $p['source'] === 'own', "destination #{$dest['id']} có toạ độ → source 'own'");
    } else {
        echo "  ⏭️  bỏ qua: không có destination nào có toạ độ\n";
    }

    $origin = geoResolveOriginInput($db, ['type' => 'manual', 'lat' => 12.6797, 'lng' => 108.0382, 'label' => 'Test BMT']);
    ok($origin !== null && $origin['type'] === 'manual' && $origin['label'] === 'Test BMT', 'origin manual hợp lệ → resolve được');

    $cur = geoResolveOriginInput($db, ['type' => 'current', 'lat' => 12.6797123, 'lng' => 108.0382987]);
    ok($cur !== null && $cur['lat'] === round(12.6797123, 3) && $cur['lng'] === round(108.0382987, 3),
        'origin current → làm tròn 3 chữ số thập phân (~100m, bảo vệ riêng tư)');

    $bad = geoResolveOriginInput($db, ['type' => 'manual', 'lat' => 0.0, 'lng' => 0.0]);
    ok($bad === null, 'origin (0,0) → null');
} catch (Exception $e) {
    echo "  ⏭️  bỏ qua test DB: " . $e->getMessage() . "\n";
}

echo $fail === 0 ? "\n🎉 test_geo: TẤT CẢ PASS\n" : "\n💥 test_geo: {$fail} test FAIL\n";
exit($fail === 0 ? 0 : 1);
