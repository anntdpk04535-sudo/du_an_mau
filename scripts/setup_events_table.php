<?php
require_once __DIR__ . '/../includes/functions.php';

try {
    $db = getDB();

    // Create table if not exists
    $sql = "CREATE TABLE IF NOT EXISTS `events` (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    
    $db->exec($sql);
    echo "Table 'events' created or verified successfully.\n";

    // Sample events array
    $events = [
        [
            'title' => 'Lễ Hội Cà Phê Buôn Ma Thuột 2026',
            'slug' => 'le-hoi-ca-phe-buon-ma-thuot-2026',
            'category' => 'nong-san',
            'start_date' => '2026-03-10',
            'end_date' => '2026-03-14',
            'location' => 'Quảng trường 10/3 & Các tuyến đường TP. Buôn Ma Thuột',
            'short_desc' => 'Lễ hội tầm quốc gia tôn vinh thương hiệu Cà phê Buôn Ma Thuột với nhiều hoạt động thưởng thức cà phê miễn phí, lễ hội đường phố, hội chợ triển lãm và hội thảo quốc tế.',
            'content' => '<p><strong>Lễ hội Cà phê Buôn Ma Thuột</strong> là sự kiện văn hóa - kinh tế quy mô nhất của tỉnh Đắk Lắk được tổ chức 2 năm một lần.</p><h4>Các hoạt động chính:</h4><ul><li>Lễ khai mạc và chương trình nghệ thuật đặc sắc đậm chất Tây Nguyên.</li><li>Hội chợ triển lãm chuyên ngành cà phê và sản phẩm OCOP.</li><li>Lễ hội đường phố với diễu hành voi, cồng chiêng và các đoàn nghệ thuật.</li><li>Ngày hội thưởng thức Cà phê miễn phí tại tất cả các quán cà phê lớn.</li><li>Cuộc thi pha chế cà phê đặc sản và hành trình du lịch trải nghiệm rẫy cà phê.</li></ul>',
            'image_url' => '/assets/images/uploads/article_2_museum.png',
            'is_featured' => 1
        ],
        [
            'title' => 'Lễ Hội Sầu Riêng Krông Pắc Lần Thứ III',
            'slug' => 'le-hoi-sau-rieng-krong-pac',
            'category' => 'nong-san',
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-03',
            'location' => 'Quảng trường Tân An, Huyện Krông Pắc, Đắk Lắk',
            'short_desc' => 'Sự kiện tôn vinh đặc sản thủ phủ sầu riêng Krông Pắc với hội thi trái sầu riêng ngon nhất, đại tiệc thưởng thức sầu riêng buffet và đêm nhạc hội đường phố.',
            'content' => '<p>Krông Pắc được mệnh danh là thủ phủ sầu riêng xuất khẩu lớn nhất Đắk Lắk. Lễ hội thu hút hàng vạn du khách và thương lái trong và ngoài nước.</p><h4>Điểm nhấn lễ hội:</h4><ul><li>Hội thi vườn sầu riêng mẫu và đấu giá trái sầu riêng nữ hoàng.</li><li>Buffet sầu riêng miễn phí cho du khách tham quan.</li><li>Trải nghiệm hái sầu riêng chín cây tại vườn công nghệ cao.</li><li>Chương trình ca múa nhạc và bắn pháo hoa mừng lễ hội.</li></ul>',
            'image_url' => '/assets/images/uploads/article_1_lake.png',
            'is_featured' => 1
        ],
        [
            'title' => 'Hội Đua Voi & Văn Hóa Dân Gian Buôn Đôn',
            'slug' => 'hoi-dua-voi-buon-don',
            'category' => 'van-hoa',
            'start_date' => '2026-03-12',
            'end_date' => '2026-03-13',
            'location' => 'Buôn Krông Na, Huyện Buôn Đôn, Đắk Lắk',
            'short_desc' => 'Hội đua voi truyền thống độc đáo bên bãi bồi sông Sêrêpôk, thi voi lội sông, voi chạy tốc độ, voi đá bóng và các nghi lễ cúng sức khỏe cho voi.',
            'content' => '<p>Hội đua voi Buôn Đôn là lễ hội truyền thống tôn vinh tài năng săn bắt và thuần dưỡng voi rừng của các dũng sĩ M’Nông và Ê Đê.</p><h4>Hoạt động nổi bật:</h4><ul><li>Lễ cúng sức khỏe cho voi trước ngày hội.</li><li>Cuộc thi voi chạy tốc độ trên mặt bãi bằng.</li><li>Màn trình diễn voi lội vượt sông Sêrêpôk cuồn cuộn.</li><li>Đêm hội cồng chiêng, đốt lửa trại và uống rượu cần cùng dân làng.</li></ul>',
            'image_url' => '/assets/images/uploads/article_3_waterfall.png',
            'is_featured' => 1
        ],
        [
            'title' => 'Liên Hoan Không Gian Văn Hóa Cồng Chiêng Tây Nguyên',
            'slug' => 'lien-hoan-cong-chieng-tay-nguyen',
            'category' => 'van-hoa',
            'start_date' => '2026-11-20',
            'end_date' => '2026-11-22',
            'location' => 'Buôn Jun (Hồ Lắk) & Trung tâm TP. Buôn Ma Thuột',
            'short_desc' => 'Ngày hội quy tụ hàng trăm nghệ nhân cồng chiêng đến từ các buôn làng Ê Đê, M’Nông, Ba Na, Gia Rai trình diễn kiệt tác di sản phi vật thể đại diện của nhân loại.',
            'content' => '<p>Không gian văn hóa Cồng chiêng Tây Nguyên đã được UNESCO công nhận là Kiệt tác di sản văn hóa phi vật thể của nhân loại.</p><h4>Nội dung liên hoan:</h4><ul><li>Trình diễn hòa tấu cồng chiêng theo các giai điệu mừng mùa, mừng nhà mới.</li><li>Tái hiện các nghi lễ truyền thống quanh bếp lửa nhà dài.</li><li>Trưng bày nhạc cụ dân tộc và trang phục thổ cẩm thủ công.</li></ul>',
            'image_url' => '/assets/images/uploads/article_4_guide.png',
            'is_featured' => 1
        ],
        [
            'title' => 'Lễ Cúng Bến Nước Truyền Thống Người Ê Đê',
            'slug' => 'le-cung-ben-nuoc-e-de',
            'category' => 'phong-tuc',
            'start_date' => '2026-01-15',
            'end_date' => '2026-01-16',
            'location' => 'Buôn Ako Dhông, TP. Buôn Ma Thuột',
            'short_desc' => 'Nghi lễ tâm linh thiêng liêng nhất trong năm của người Ê Đê để tạ ơn Thần Nước (Yang Ea), dọn dẹp nguồn nước buôn làng và cầu may mắn.',
            'content' => '<p>Lễ cúng Bến nước diễn ra sau mùa thu hoạch lúa nương, là dịp để cả buôn làng cùng nhau dọn dẹp bến nước và dâng lễ vật tạ ơn thần linh.</p><h4>Quy trình nghi lễ:</h4><ul><li>Thầy cúng thực hiện nghi thức khấn nguyện tại bến nước dòng suối.</li><li>Rước nước mát từ bến nước về nhà cộng đồng buôn làng.</li><li>Mở hội ăn mừng, uống rượu cần và tấu cồng chiêng suốt đêm.</li></ul>',
            'image_url' => '/assets/images/uploads/article_2_museum.png',
            'is_featured' => 1
        ]
    ];

    $stmt = $db->prepare("INSERT INTO `events` (`title`, `slug`, `category`, `start_date`, `end_date`, `location`, `short_desc`, `content`, `image_url`, `is_featured`) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE `title`=VALUES(`title`), `short_desc`=VALUES(`short_desc`), `content`=VALUES(`content`), `image_url`=VALUES(`image_url`)");

    foreach ($events as $ev) {
        $stmt->execute([
            $ev['title'],
            $ev['slug'],
            $ev['category'],
            $ev['start_date'],
            $ev['end_date'],
            $ev['location'],
            $ev['short_desc'],
            $ev['content'],
            $ev['image_url'],
            $ev['is_featured']
        ]);
    }

    echo "Seeded 5 Dak Lak festivals into 'events' table successfully.\n";

} catch (PDOException $e) {
    die("Setup failed: " . $e->getMessage());
}
