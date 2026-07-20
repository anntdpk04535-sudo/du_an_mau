<?php
require_once __DIR__ . '/includes/functions.php';
try {
    $db = getDB();

    // Tạo bảng reviews nếu chưa có
    $db->exec("
        CREATE TABLE IF NOT EXISTS reviews (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NULL,
            destination_id INT NULL COMMENT 'NULL = đánh giá dịch vụ website tổng thể',
            rating TINYINT NOT NULL DEFAULT 5 COMMENT '1-5 sao',
            comment TEXT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
            FOREIGN KEY (destination_id) REFERENCES destinations(id) ON DELETE CASCADE,
            INDEX idx_destination (destination_id),
            INDEX idx_user (user_id)
        ) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
    ");
    echo "✅ Đã tạo bảng 'reviews' thành công.\n";

} catch (Exception $e) {
    echo "❌ Lỗi: " . $e->getMessage() . "\n";
}
