<?php
require_once __DIR__ . '/config/env.php';
require_once __DIR__ . '/config/db.php';

try {
    $db = getDB();

    echo "1. Checking destinations table...\n";
    $result = $db->query("SHOW COLUMNS FROM `destinations` LIKE 'virtual_tour_enabled'")->fetch();
    if (!$result) {
        $db->exec("ALTER TABLE `destinations` 
                   ADD `virtual_tour_enabled` TINYINT(1) NOT NULL DEFAULT 0 AFTER `longitude`,
                   ADD `virtual_tour_type` VARCHAR(50) DEFAULT 'pannellum' AFTER `virtual_tour_enabled`,
                   ADD `audio_guide_url` VARCHAR(500) DEFAULT NULL AFTER `virtual_tour_type`");
        echo " - Added virtual tour columns to destinations.\n";
    } else {
        echo " - Columns already exist in destinations.\n";
    }

    echo "2. Creating virtual_tour_scenes table...\n";
    $db->exec("
        CREATE TABLE IF NOT EXISTS `virtual_tour_scenes` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `destination_id` INT(11) NOT NULL,
            `scene_key` VARCHAR(50) NOT NULL,
            `title` VARCHAR(255) NOT NULL,
            `title_en` VARCHAR(255) DEFAULT NULL,
            `description` TEXT DEFAULT NULL,
            `description_en` TEXT DEFAULT NULL,
            `panorama_url` VARCHAR(500) NOT NULL,
            `thumbnail_url` VARCHAR(500) DEFAULT NULL,
            `audio_url` VARCHAR(500) DEFAULT NULL,
            `pitch` DECIMAL(5,2) DEFAULT 0.00,
            `yaw` DECIMAL(5,2) DEFAULT 0.00,
            `hfov` INT(11) DEFAULT 110,
            `sort_order` INT(11) DEFAULT 0,
            `is_default` TINYINT(1) DEFAULT 0,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `destination_id` (`destination_id`),
            CONSTRAINT `vt_scenes_ibfk_1` FOREIGN KEY (`destination_id`) REFERENCES `destinations` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo " - Done.\n";

    echo "3. Creating virtual_tour_hotspots table...\n";
    $db->exec("
        CREATE TABLE IF NOT EXISTS `virtual_tour_hotspots` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `scene_id` INT(11) NOT NULL,
            `type` ENUM('info','scene') NOT NULL DEFAULT 'info',
            `pitch` DECIMAL(5,2) NOT NULL,
            `yaw` DECIMAL(5,2) NOT NULL,
            `text` VARCHAR(255) DEFAULT NULL,
            `text_en` VARCHAR(255) DEFAULT NULL,
            `target_scene_id` INT(11) DEFAULT NULL,
            `url` VARCHAR(500) DEFAULT NULL,
            `icon` VARCHAR(50) DEFAULT NULL,
            `css_class` VARCHAR(50) DEFAULT NULL,
            `sort_order` INT(11) DEFAULT 0,
            PRIMARY KEY (`id`),
            KEY `scene_id` (`scene_id`),
            KEY `target_scene_id` (`target_scene_id`),
            CONSTRAINT `vt_hotspots_ibfk_1` FOREIGN KEY (`scene_id`) REFERENCES `virtual_tour_scenes` (`id`) ON DELETE CASCADE,
            CONSTRAINT `vt_hotspots_ibfk_2` FOREIGN KEY (`target_scene_id`) REFERENCES `virtual_tour_scenes` (`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo " - Done.\n";

    echo "4. Creating virtual_tour_interactions table...\n";
    $db->exec("
        CREATE TABLE IF NOT EXISTS `virtual_tour_interactions` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `user_id` INT(11) DEFAULT NULL,
            `destination_id` INT(11) NOT NULL,
            `scene_id` INT(11) DEFAULT NULL,
            `action` VARCHAR(50) NOT NULL,
            `duration_seconds` INT(11) DEFAULT NULL,
            `device_type` VARCHAR(20) DEFAULT 'desktop',
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `user_id` (`user_id`),
            KEY `destination_id` (`destination_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo " - Done.\n";

    echo "5. Seeding dummy data for destination_id = 1 (Hồ Lắk)...\n";
    
    // Enable virtual tour for destination 1
    $db->exec("UPDATE `destinations` SET `virtual_tour_enabled` = 1 WHERE `id` = 1");
    
    // Clear old scenes for dest 1
    $db->exec("DELETE FROM `virtual_tour_scenes` WHERE `destination_id` = 1");
    
    // Insert Scene 1 (Lake View)
    $stmt = $db->prepare("INSERT INTO `virtual_tour_scenes` 
        (`destination_id`, `scene_key`, `title`, `title_en`, `panorama_url`, `is_default`, `description`) 
        VALUES (1, 'scene_lake', 'Mặt Hồ Lắk', 'Lak Lake View', 'https://pannellum.org/images/alma.jpg', 1, 'Trải nghiệm ngắm nhìn mặt Hồ Lắk rộng lớn, xanh ngát.')");
    $stmt->execute();
    $sceneLakeId = $db->lastInsertId();

    // Insert Scene 2 (M'Nong Village)
    $stmt = $db->prepare("INSERT INTO `virtual_tour_scenes` 
        (`destination_id`, `scene_key`, `title`, `title_en`, `panorama_url`, `is_default`, `description`) 
        VALUES (1, 'scene_village', 'Buôn Làng M\\'Nông', 'M\\'Nong Village', 'https://pannellum.org/images/bma-0.jpg', 0, 'Khám phá văn hóa bản địa độc đáo của người M\\'Nông quanh Hồ Lắk.')");
    $stmt->execute();
    $sceneVillageId = $db->lastInsertId();

    // Insert Hotspot linking Scene 1 to Scene 2
    $db->exec("INSERT INTO `virtual_tour_hotspots` 
        (`scene_id`, `type`, `pitch`, `yaw`, `text`, `target_scene_id`) 
        VALUES ($sceneLakeId, 'scene', -5, 100, 'Đi dạo buôn làng', $sceneVillageId)");
    
    // Insert Info Hotspot in Scene 1
    $db->exec("INSERT INTO `virtual_tour_hotspots` 
        (`scene_id`, `type`, `pitch`, `yaw`, `text`) 
        VALUES ($sceneLakeId, 'info', 10, -50, 'Hồ Lắk là hồ tự nhiên lớn thứ hai Việt Nam')");

    // Insert Hotspot linking Scene 2 back to Scene 1
    $db->exec("INSERT INTO `virtual_tour_hotspots` 
        (`scene_id`, `type`, `pitch`, `yaw`, `text`, `target_scene_id`) 
        VALUES ($sceneVillageId, 'scene', 5, -120, 'Quay lại Hồ Lắk', $sceneLakeId)");

    echo " - Seeding complete.\n";
    echo "MIGRATION SUCCESSFUL!\n";

} catch (Exception $e) {
    die("Error during migration: " . $e->getMessage() . "\n");
}
