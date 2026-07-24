<?php
require_once __DIR__ . '/config/env.php';
require_once __DIR__ . '/config/db.php';

try {
    $db = getDB();

    $dests = [
        2 => ['Thác Dray Nur', 'Dray Nur Waterfall', 'https://pannellum.org/images/cerro-toco-0.jpg'],
        3 => ['Thác Dray Sáp', 'Dray Sap Waterfall', 'https://pannellum.org/images/cerro-toco-0.jpg'],
        4 => ['Buôn Đôn', 'Buon Don', 'https://pannellum.org/images/bma-0.jpg'],
        5 => ['Vườn quốc gia Yok Đôn', 'Yok Don National Park', 'https://pannellum.org/images/bma-0.jpg'],
        6 => ['Cà phê Buôn Ma Thuột', 'Buon Ma Thuot Coffee', 'https://pannellum.org/images/alma.jpg'],
        7 => ['Buôn Akô Dhông', 'Ako Dhong Village', 'https://pannellum.org/images/bma-0.jpg'],
        8 => ['Hồ Ea Kao', 'Ea Kao Lake', 'https://pannellum.org/images/alma.jpg']
    ];

    foreach ($dests as $id => $data) {
        $name_vi = $data[0];
        $name_en = $data[1];
        $img = $data[2];

        // Bật 360 cho địa điểm
        $db->exec("UPDATE `destinations` SET `virtual_tour_enabled` = 1 WHERE `id` = $id");

        // Xóa scene cũ (nếu có)
        $db->exec("DELETE FROM `virtual_tour_scenes` WHERE `destination_id` = $id");

        // Thêm scene mới
        $stmt = $db->prepare("INSERT INTO `virtual_tour_scenes` 
            (`destination_id`, `scene_key`, `title`, `title_en`, `panorama_url`, `is_default`, `description`) 
            VALUES (?, ?, ?, ?, ?, 1, ?)");
        
        $scene_key = 'scene_main_' . $id;
        $desc = "Trải nghiệm không gian 360 độ tại " . $name_vi . " (Ảnh mẫu demo)";
        $stmt->execute([$id, $scene_key, $name_vi, $name_en, $img, $desc]);
    }

    echo "Đã thêm dữ liệu 360 mẫu cho tất cả các điểm đến!\n";

} catch (Exception $e) {
    die("Lỗi: " . $e->getMessage() . "\n");
}
