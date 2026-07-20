<?php
require_once __DIR__ . '/config/env.php';
require_once __DIR__ . '/config/db.php';

try {
    $db = getDB();

    // 1. Cập nhật bảng users thêm cột status
    echo "Đang kiểm tra bảng users...\n";
    $result = $db->query("SHOW COLUMNS FROM `users` LIKE 'status'")->fetch();
    if (!$result) {
        $db->exec("ALTER TABLE `users` ADD `status` ENUM('active','banned') NOT NULL DEFAULT 'active' AFTER `role`");
        echo "Đã thêm cột status vào bảng users.\n";
    } else {
        echo "Cột status đã tồn tại trong bảng users.\n";
    }

    // 2. Tạo bảng articles
    echo "Đang kiểm tra bảng articles...\n";
    $db->exec("
        CREATE TABLE IF NOT EXISTS `articles` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `title` VARCHAR(255) NOT NULL,
            `slug` VARCHAR(255) NOT NULL,
            `summary` TEXT DEFAULT NULL,
            `content` LONGTEXT NOT NULL,
            `image_url` VARCHAR(500) DEFAULT NULL,
            `author_id` INT(11) DEFAULT NULL,
            `status` ENUM('draft', 'published') NOT NULL DEFAULT 'published',
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `slug` (`slug`),
            KEY `author_id` (`author_id`),
            CONSTRAINT `articles_ibfk_1` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "Bảng articles đã sẵn sàng.\n";

    echo "Migration hoàn tất thành công.\n";

} catch (Exception $e) {
    die("Lỗi migration: " . $e->getMessage() . "\n");
}
