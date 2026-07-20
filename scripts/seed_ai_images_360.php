<?php
require_once __DIR__ . '/config/env.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

try {
    $db = getDB();

    $dests = [
        1 => url('/assets/images/360/lak_lake_360.png'),
        2 => url('/assets/images/360/dray_nur_360.png'),
        3 => url('/assets/images/360/dray_nur_360.png'), // Fallback
        4 => url('/assets/images/360/buon_don_360.png'),
        5 => url('/assets/images/360/yok_don_360.png'),
        6 => url('/assets/images/360/buon_don_360.png'), // Fallback
        7 => url('/assets/images/360/buon_don_360.png'), // Fallback
        8 => url('/assets/images/360/lak_lake_360.png')  // Fallback
    ];

    foreach ($dests as $id => $img) {
        $updateStmt = $db->prepare("UPDATE `virtual_tour_scenes` SET `panorama_url` = ? WHERE `destination_id` = ?");
        $updateStmt->execute([$img, $id]);
    }

    echo "Đã cập nhật tất cả ảnh 360 bằng hình ảnh panorama chuẩn 2:1!\n";

} catch (Exception $e) {
    die("Lỗi: " . $e->getMessage() . "\n");
}
