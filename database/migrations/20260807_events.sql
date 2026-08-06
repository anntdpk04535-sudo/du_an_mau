-- Tách DDL bảng events khỏi scripts/setup_events_table.php để lược đồ dựng lại được.
CREATE TABLE IF NOT EXISTS `events` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL UNIQUE,
  `category` VARCHAR(50) NOT NULL DEFAULT 'van-hoa',
  `start_date` DATE NOT NULL,
  `end_date` DATE NOT NULL,
  `location` VARCHAR(255) NOT NULL,
  `short_desc` TEXT NOT NULL,
  `content` LONGTEXT NULL,
  `image_url` VARCHAR(500) NULL,
  `is_featured` TINYINT(1) DEFAULT 1,
  `status` VARCHAR(20) DEFAULT 'published',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
