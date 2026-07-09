<?php
require_once __DIR__ . '/config/env.php';
require_once __DIR__ . '/config/db.php';

try {
    $db = getDB();

    $base = 'http://localhost/du_an_mau-Hieus/du_an_mau-An/main/assets/images/360/';
    $dests = [
        1 => $base . 'lak_lake_360.png',
        2 => $base . 'dray_nur_360.png',
        3 => $base . 'dray_nur_360.png', 
        4 => $base . 'buon_don_360.png',
        5 => $base . 'yok_don_360.png',
        6 => $base . 'buon_don_360.png', 
        7 => $base . 'buon_don_360.png', 
        8 => $base . 'lak_lake_360.png'  
    ];

    foreach ($dests as $id => $img) {
        $updateStmt = $db->prepare("UPDATE `virtual_tour_scenes` SET `panorama_url` = ? WHERE `destination_id` = ?");
        $updateStmt->execute([$img, $id]);
    }
    
    // Also fix the initial seeded scene for destination 1 which was scene_id 1 and 2
    $updateStmt = $db->prepare("UPDATE `virtual_tour_scenes` SET `panorama_url` = ? WHERE `id` = 1");
    $updateStmt->execute([$base . 'lak_lake_360.png']);
    $updateStmt = $db->prepare("UPDATE `virtual_tour_scenes` SET `panorama_url` = ? WHERE `id` = 2");
    $updateStmt->execute([$base . 'buon_don_360.png']);

    echo "Đã sửa lỗi đường dẫn ảnh 360!\n";

} catch (Exception $e) {
    die("Lỗi: " . $e->getMessage() . "\n");
}
