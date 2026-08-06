<?php
declare(strict_types=1);

/**
 * Suy luận destinations.indoor_type + weather_sensitivity từ tags/tên.
 * Chỉ ghi khi indoor_type đang NULL (admin nhập tay luôn được giữ nguyên).
 *
 * Cách dùng: php scripts/backfill_indoor_type.php [--dry-run] [--force]
 */

require_once __DIR__ . '/../includes/content_helpers.php';

$options = getopt('', ['dry-run', 'force']);
$dryRun = isset($options['dry-run']);
$force = isset($options['force']);

// outdoor: nhạy mưa cao (3) — thác, hồ, biển, trekking...
$outdoorKeywords = ['thác', 'hồ ', 'hồ,', 'trekking', 'rừng', 'vườn quốc gia', 'đồi', 'biển', 'gành', 'ghềnh', 'cắm trại', 'leo núi', 'đảo', 'suối', 'vịnh', 'bãi', 'mũi ', 'đèo', 'waterfall', 'lake', 'beach'];
// indoor: không ảnh hưởng mưa (0)
$indoorKeywords = ['bảo tàng', 'nhà dài', 'nhà thờ', 'chùa', 'chợ', 'khu trưng bày', 'trung tâm', 'nhà đày', 'museum', 'gallery', 'tòa giám mục'];
// cà phê thường có không gian trong nhà nhưng sân vườn là điểm nhấn → mixed
$mixedKeywords = ['cà phê', 'coffee', 'buôn', 'làng', 'quảng trường', 'tháp'];

$db = getDB();
if (!columnExists($db, 'destinations', 'indoor_type')) {
    fwrite(STDERR, "Cột indoor_type chưa tồn tại — chạy scripts/migrate_itinerary_geo.php trước.\n");
    exit(1);
}

$where = $force ? '' : ' WHERE indoor_type IS NULL';
$rows = $db->query('SELECT id, name, tags FROM destinations' . $where)->fetchAll() ?: [];
$updated = 0;

foreach ($rows as $row) {
    $hay = mb_strtolower($row['name'] . ' ' . (string)$row['tags']);
    $matches = static function (array $keywords) use ($hay): bool {
        foreach ($keywords as $kw) {
            if (mb_strpos($hay, $kw) !== false) return true;
        }
        return false;
    };

    if ($matches($outdoorKeywords)) {
        $type = 'outdoor';
        $sensitivity = 3;
    } elseif ($matches($indoorKeywords)) {
        $type = 'indoor';
        $sensitivity = 0;
    } elseif ($matches($mixedKeywords)) {
        $type = 'mixed';
        $sensitivity = 1;
    } else {
        $type = 'mixed';
        $sensitivity = 1;
    }

    printf("#%d %s → %s / sensitivity %d%s\n", $row['id'], $row['name'], $type, $sensitivity, $dryRun ? ' [dry-run]' : '');
    if (!$dryRun) {
        $db->prepare('UPDATE destinations SET indoor_type = ?, weather_sensitivity = ? WHERE id = ?')
            ->execute([$type, $sensitivity, $row['id']]);
        $updated++;
    }
}

echo "Hoàn tất. Đã phân loại {$updated}/" . count($rows) . " điểm đến.\n";
