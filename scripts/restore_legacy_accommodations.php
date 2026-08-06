<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
$db = getDB();

// The first migration used two placeholder rows per destination. Preserve
// those records as draft-only legacy data instead of silently losing them;
// they are never presented as verified accommodation recommendations.
$destinations = $db->query('SELECT id,name,address FROM destinations ORDER BY id')->fetchAll();
$insert = $db->prepare("INSERT IGNORE INTO accommodations
    (destination_id,accommodation_type,name,name_en,slug,description,address,source_url,last_verified_at,status)
    VALUES (?,?,?,?,?,?,?,?,NULL,'draft')");
$restored = 0;
foreach ($destinations as $destination) {
    foreach ([['Homestay bản địa', 'homestay'], ['Nhà nghỉ ven điểm đến', 'hotel']] as [$label, $type]) {
        $name = $label . ' ' . $destination['name'];
        $insert->execute([
            (int)$destination['id'], $type, $name, $name,
            'luu-tru-' . (int)$destination['id'] . '-' . ($type === 'homestay' ? '0' : '1'),
            'Dữ liệu legacy được giữ lại để tương thích lịch sử; chưa xác minh và không công khai.',
            $destination['address'], 'internal://legacy-preserved',
        ]);
        $restored += $insert->rowCount();
    }
}
echo json_encode(['legacy_rows_restored' => $restored], JSON_UNESCAPED_UNICODE) . PHP_EOL;
