<?php
/**
 * Migration: Thêm bảng và cột hỗ trợ Virtual Tour 360°
 * Chạy 1 lần: php migrate_virtual_tour.php
 */
require_once __DIR__ . '/includes/functions.php';

$db = getDB();

echo "=== Migration: Virtual Tour 360° ===\n\n";

// 1. ALTER bảng destinations — thêm cột virtual tour
echo "[1/4] ALTER TABLE destinations...\n";
try {
    $db->exec("
        ALTER TABLE `destinations`
            ADD COLUMN `virtual_tour_enabled` TINYINT(1) NOT NULL DEFAULT 0
                COMMENT 'Có hỗ trợ tour 360 không',
            ADD COLUMN `virtual_tour_type` ENUM('pannellum','iframe','youtube360') DEFAULT 'pannellum'
                COMMENT 'Loại tour: pannellum=self-hosted, iframe=embed bên thứ 3',
            ADD COLUMN `audio_guide_url` VARCHAR(500) DEFAULT NULL
                COMMENT 'Link file MP3 thuyết minh tổng quan'
    ");
    echo "  ✅ OK\n";
} catch (PDOException $e) {
    if (str_contains($e->getMessage(), 'Duplicate column')) {
        echo "  ⏭️ Đã tồn tại, bỏ qua.\n";
    } else {
        echo "  ❌ Lỗi: " . $e->getMessage() . "\n";
    }
}

// 2. Tạo bảng virtual_tour_scenes
echo "[2/4] CREATE TABLE virtual_tour_scenes...\n";
try {
    $db->exec("
        CREATE TABLE IF NOT EXISTS `virtual_tour_scenes` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `destination_id` INT(11) NOT NULL,
            `scene_key` VARCHAR(50) NOT NULL COMMENT 'Key duy nhất VD: ho_lak_1',
            `title` VARCHAR(200) NOT NULL COMMENT 'Tên cảnh VD: Bến thuyền Hồ Lắk',
            `title_en` VARCHAR(200) DEFAULT NULL,
            `panorama_url` VARCHAR(500) NOT NULL COMMENT 'Đường dẫn ảnh equirectangular 360 hoặc embed URL',
            `thumbnail_url` VARCHAR(500) DEFAULT NULL COMMENT 'Ảnh nhỏ đại diện cho cảnh',
            `audio_url` VARCHAR(500) DEFAULT NULL COMMENT 'File thuyết minh riêng cho cảnh',
            `audio_url_en` VARCHAR(500) DEFAULT NULL,
            `description` TEXT DEFAULT NULL COMMENT 'Mô tả về cảnh quan',
            `description_en` TEXT DEFAULT NULL,
            `pitch` DECIMAL(6,2) DEFAULT 0 COMMENT 'Góc nhìn mặc định (trục dọc)',
            `yaw` DECIMAL(6,2) DEFAULT 0 COMMENT 'Góc nhìn mặc định (trục ngang)',
            `hfov` INT DEFAULT 110 COMMENT 'Tầm nhìn ngang mặc định (độ)',
            `sort_order` INT DEFAULT 0 COMMENT 'Thứ tự cảnh trong tour',
            `is_default` TINYINT(1) DEFAULT 0 COMMENT 'Cảnh mặc định khi mở tour',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `scene_key` (`scene_key`),
            KEY `idx_destination` (`destination_id`),
            CONSTRAINT `fk_scene_destination`
                FOREIGN KEY (`destination_id`) REFERENCES `destinations`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "  ✅ OK\n";
} catch (PDOException $e) {
    echo "  ❌ Lỗi: " . $e->getMessage() . "\n";
}

// 3. Tạo bảng virtual_tour_hotspots
echo "[3/4] CREATE TABLE virtual_tour_hotspots...\n";
try {
    $db->exec("
        CREATE TABLE IF NOT EXISTS `virtual_tour_hotspots` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `scene_id` INT(11) NOT NULL COMMENT 'Thuộc cảnh nào',
            `type` ENUM('scene','info','audio','link') NOT NULL DEFAULT 'info'
                COMMENT 'scene=chuyển cảnh, info=thông tin, audio=phát âm thanh, link=liên kết ngoài',
            `pitch` DECIMAL(6,2) NOT NULL COMMENT 'Tọa độ trục dọc trên ảnh 360',
            `yaw` DECIMAL(6,2) NOT NULL COMMENT 'Tọa độ trục ngang trên ảnh 360',
            `text` VARCHAR(500) DEFAULT NULL COMMENT 'Nội dung hiển thị khi click',
            `text_en` VARCHAR(500) DEFAULT NULL,
            `target_scene_id` INT(11) DEFAULT NULL COMMENT 'ID cảnh đích (nếu type=scene)',
            `url` VARCHAR(500) DEFAULT NULL COMMENT 'URL liên kết (nếu type=link)',
            `icon` VARCHAR(50) DEFAULT 'fas fa-info-circle' COMMENT 'Icon cho hotspot',
            `css_class` VARCHAR(100) DEFAULT NULL COMMENT 'CSS class tùy chỉnh',
            `sort_order` INT DEFAULT 0,
            PRIMARY KEY (`id`),
            KEY `idx_scene` (`scene_id`),
            KEY `idx_target_scene` (`target_scene_id`),
            CONSTRAINT `fk_hotspot_scene`
                FOREIGN KEY (`scene_id`) REFERENCES `virtual_tour_scenes`(`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_hotspot_target`
                FOREIGN KEY (`target_scene_id`) REFERENCES `virtual_tour_scenes`(`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "  ✅ OK\n";
} catch (PDOException $e) {
    echo "  ❌ Lỗi: " . $e->getMessage() . "\n";
}

// 4. Tạo bảng virtual_tour_interactions (Analytics)
echo "[4/4] CREATE TABLE virtual_tour_interactions...\n";
try {
    $db->exec("
        CREATE TABLE IF NOT EXISTS `virtual_tour_interactions` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `user_id` INT(11) DEFAULT NULL COMMENT 'NULL = khách vãng lai',
            `destination_id` INT(11) NOT NULL,
            `scene_id` INT(11) DEFAULT NULL,
            `action` ENUM('view_tour','change_scene','click_hotspot','play_audio','complete_tour') NOT NULL,
            `duration_seconds` INT DEFAULT NULL COMMENT 'Thời gian xem (giây)',
            `device_type` ENUM('desktop','mobile','tablet','vr') DEFAULT 'desktop',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_dest` (`destination_id`),
            KEY `idx_user` (`user_id`),
            CONSTRAINT `fk_interaction_user`
                FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
            CONSTRAINT `fk_interaction_dest`
                FOREIGN KEY (`destination_id`) REFERENCES `destinations`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "  ✅ OK\n";
} catch (PDOException $e) {
    echo "  ❌ Lỗi: " . $e->getMessage() . "\n";
}

// 5. Seed data: Bật Virtual Tour cho 3 địa điểm mẫu + thêm scenes
echo "\n=== Seed Data: Virtual Tour mẫu ===\n";

// Bật virtual tour cho Hồ Lắk (id=1), Thác Dray Nur (id=2), Buôn Đôn (id=4)
$db->exec("UPDATE destinations SET virtual_tour_enabled = 1, virtual_tour_type = 'pannellum' WHERE id IN (1, 2, 4)");
echo "✅ Bật virtual_tour_enabled cho Hồ Lắk, Dray Nur, Buôn Đôn\n";

// Insert scenes mẫu
$scenes = [
    // Hồ Lắk
    [1, 'ho_lak_1', 'Toàn cảnh Hồ Lắk', 'Lak Lake Panorama',
     'https://lh5.googleusercontent.com/p/AF1QipNq8n5g_VxGFb3kE2fEsCemxy4lPBvjJqbh4fQb=w900-h600-k-no',
     null, 'Toàn cảnh Hồ Lắk nhìn từ bến thuyền, hồ nước ngọt tự nhiên lớn thứ 2 Việt Nam.',
     'Panoramic view of Lak Lake from the boat dock, the 2nd largest natural freshwater lake in Vietnam.',
     -5, 120, 110, 1, 1],
    [1, 'ho_lak_2', 'Buôn Jun - Hồ Lắk', 'Buon Jun - Lak Lake',
     'https://lh5.googleusercontent.com/p/AF1QipMvPzjTlFOvJRMcqPgj_U0qLgbH7yBQC9tQcz6j=w900-h600-k-no',
     null, 'Buôn Jun nằm bên bờ hồ Lắk, nơi sinh sống của đồng bào M\'nông.',
     'Buon Jun village by Lak Lake, home of the M\'nong ethnic people.',
     0, 0, 110, 2, 0],

    // Thác Dray Nur
    [2, 'dray_nur_1', 'Chân Thác Dray Nur', 'Base of Dray Nur Waterfall',
     'https://lh5.googleusercontent.com/p/AF1QipPJk8Fzp9apxSFbeL7Z2qbNKN9bMR_53h9bM0mM=w900-h600-k-no',
     null, 'Đứng dưới chân thác Dray Nur hùng vĩ, cảm nhận sức mạnh thiên nhiên.',
     'Standing at the base of majestic Dray Nur Waterfall, feeling the power of nature.',
     -10, 90, 100, 1, 1],
    [2, 'dray_nur_2', 'Hang Đá sau Thác', 'Cave behind the Waterfall',
     'https://lh5.googleusercontent.com/p/AF1QipN8kGYKDP9PZrcEJKVj_zFqxlRn3yJE6QLaVF2c=w900-h600-k-no',
     null, 'Khám phá hang đá bí ẩn nằm phía sau màn nước thác Dray Nur.',
     'Explore the mysterious cave hidden behind the curtain of Dray Nur waterfall.',
     0, -45, 100, 2, 0],

    // Buôn Đôn
    [4, 'buon_don_1', 'Cầu Treo Buôn Đôn', 'Buon Don Suspension Bridge',
     'https://lh5.googleusercontent.com/p/AF1QipNRGP3dZ5MH6qk0Gfm6KmC0l6YqRLvlSxlIR9FN=w900-h600-k-no',
     null, 'Cầu treo bắc qua sông Sêrêpốk, biểu tượng nổi tiếng của Buôn Đôn.',
     'Suspension bridge over the Serepok River, a famous symbol of Buon Don.',
     0, 45, 110, 1, 1],
    [4, 'buon_don_2', 'Nhà Dài truyền thống', 'Traditional Longhouse',
     'https://lh5.googleusercontent.com/p/AF1QipN_JfQPjuRhFpYYF1P9kTkE8bOdZH1wJm0cA7yF=w900-h600-k-no',
     null, 'Nhà Dài truyền thống của người Ê Đê tại Buôn Đôn, kiến trúc độc đáo vùng Tây Nguyên.',
     'Traditional Ede longhouse in Buon Don, unique architecture of the Central Highlands.',
     0, 180, 100, 2, 0],
];

$insertScene = $db->prepare("
    INSERT IGNORE INTO virtual_tour_scenes
        (destination_id, scene_key, title, title_en, panorama_url, thumbnail_url,
         description, description_en, pitch, yaw, hfov, sort_order, is_default)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

foreach ($scenes as $s) {
    $insertScene->execute($s);
}
echo "✅ Thêm 6 cảnh mẫu (2 cảnh/điểm × 3 điểm)\n";

// Insert hotspots mẫu — liên kết giữa các cảnh
$sceneIds = $db->query("SELECT id, scene_key FROM virtual_tour_scenes ORDER BY id")->fetchAll(PDO::FETCH_KEY_PAIR);
$keyToId = array_flip($sceneIds);

$hotspots = [
    // Hồ Lắk scene 1 → scene 2
    ['ho_lak_1', 'scene', -2, -30, 'Đi đến Buôn Jun →', 'Go to Buon Jun →', 'ho_lak_2'],
    ['ho_lak_1', 'info', 10, 60, 'Hồ Lắk rộng 6.2 km², sâu trung bình 5-6m. Đây là hồ nước ngọt lớn thứ 2 Việt Nam, sau hồ Ba Bể.', 'Lak Lake covers 6.2 km², with an average depth of 5-6m. It is the 2nd largest freshwater lake in Vietnam.', null],
    // Hồ Lắk scene 2 → scene 1
    ['ho_lak_2', 'scene', 0, 150, '← Quay lại bến thuyền', '← Back to boat dock', 'ho_lak_1'],

    // Dray Nur scene 1 → scene 2
    ['dray_nur_1', 'scene', 5, -60, 'Vào hang đá bí ẩn →', 'Enter the mysterious cave →', 'dray_nur_2'],
    ['dray_nur_1', 'info', -15, 30, 'Thác Dray Nur cao khoảng 30m, rộng hơn 250m, là một trong những thác nước đẹp nhất Tây Nguyên.', 'Dray Nur waterfall is about 30m high and 250m wide, one of the most beautiful waterfalls in the Central Highlands.', null],
    // Dray Nur scene 2 → scene 1
    ['dray_nur_2', 'scene', 0, 90, '← Quay lại chân thác', '← Back to waterfall base', 'dray_nur_1'],

    // Buôn Đôn scene 1 → scene 2
    ['buon_don_1', 'scene', -5, -90, 'Đi xem Nhà Dài →', 'Go to Longhouse →', 'buon_don_2'],
    ['buon_don_1', 'info', 8, 120, 'Cầu treo Buôn Đôn dài khoảng 100m, bắc qua sông Sêrêpốk. Đây là cây cầu treo nổi tiếng nhất Đắk Lắk.', 'Buon Don suspension bridge is about 100m long, crossing the Serepok River. It is the most famous suspension bridge in Dak Lak.', null],
    // Buôn Đôn scene 2 → scene 1
    ['buon_don_2', 'scene', 0, -45, '← Quay lại cầu treo', '← Back to suspension bridge', 'buon_don_1'],
];

$insertHotspot = $db->prepare("
    INSERT IGNORE INTO virtual_tour_hotspots
        (scene_id, type, pitch, yaw, text, text_en, target_scene_id)
    VALUES (?, ?, ?, ?, ?, ?, ?)
");

foreach ($hotspots as $h) {
    $sceneId = $keyToId[$h[0]] ?? null;
    $targetId = $h[6] ? ($keyToId[$h[6]] ?? null) : null;
    if ($sceneId) {
        $insertHotspot->execute([$sceneId, $h[1], $h[2], $h[3], $h[4], $h[5], $targetId]);
    }
}
echo "✅ Thêm hotspots mẫu (chuyển cảnh + thông tin)\n";

echo "\n🎉 Migration hoàn tất! Virtual Tour 360° đã sẵn sàng.\n";
