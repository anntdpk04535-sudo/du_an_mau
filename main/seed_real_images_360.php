<?php
require_once __DIR__ . '/config/env.php';
require_once __DIR__ . '/config/db.php';

try {
    $db = getDB();
    
    // Fetch all destinations with their image_url
    $stmt = $db->query('SELECT id, image_url FROM destinations WHERE image_url IS NOT NULL');
    $dests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($dests as $dest) {
        $id = $dest['id'];
        $img = $dest['image_url'];
        
        // Update the main scene's panorama_url with the destination's image_url
        $updateStmt = $db->prepare("UPDATE `virtual_tour_scenes` SET `panorama_url` = ? WHERE `destination_id` = ?");
        $updateStmt->execute([$img, $id]);
    }

    echo "Đã cập nhật panorama_url bằng hình ảnh gốc của địa điểm thành công!\n";

} catch (Exception $e) {
    die("Lỗi: " . $e->getMessage() . "\n");
}
