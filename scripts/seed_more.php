<?php
require 'includes/functions.php';
$db = getDB();

$newDestinations = [
    [
        'Tháp Nghinh Phong',
        'thap-nghinh-phong',
        2, // Văn hoá
        'Biểu tượng du lịch mới của thành phố Tuy Hòa, Phú Yên.',
        'Tháp Nghinh Phong được thiết kế lấy cảm hứng từ truyền thuyết Lạc Long Quân - Âu Cơ, mang kiến trúc độc đáo, là điểm check-in không thể bỏ qua khi đến Phú Yên.',
        'Tuy Hòa, Phú Yên',
        13.0847, 109.3245,
        url('/assets/images/uploads/article_2_museum.png'),
        1.5,
        'free'
    ],
    [
        'Hòn Yến',
        'hon-yen-phu-yen',
        1, // Thiên nhiên
        'Hòn đảo nhỏ với rạn san hô tuyệt đẹp lộ trên mặt nước lúc thủy triều rút.',
        'Hòn Yến mang vẻ đẹp hoang sơ. Vào những ngày đầu hoặc giữa tháng âm lịch, khi thủy triều rút xuống, nơi đây để lộ ra những rạn san hô nhiều màu sắc vô cùng rực rỡ.',
        'Tuy An, Phú Yên',
        13.2384, 109.3056,
        url('/assets/images/uploads/article_3_waterfall.png'),
        2.5,
        'low'
    ],
    [
        'Thác Thủy Tiên',
        'thac-thuy-tien-dak-lak',
        1, // Thiên nhiên
        'Tuyệt tác thiên nhiên ba tầng giữa chốn đại ngàn Krông Năng.',
        'Thác Thủy Tiên (hay còn gọi là thác Ba Tầng) có không gian rộng lớn, hoang sơ và mát mẻ, phù hợp cho các chuyến dã ngoại, tắm suối và khám phá rừng núi.',
        'Krông Năng, Đắk Lắk',
        12.9691, 108.3371,
        url('/assets/images/uploads/article_3_waterfall.png'),
        3.0,
        'low'
    ],
    [
        'Núi Đá Voi Mẹ',
        'nui-da-voi-me',
        1, // Thiên nhiên
        'Tảng đá nguyên khối lớn nhất Việt Nam mang truyền thuyết kỳ bí.',
        'Núi Đá Voi Mẹ là một khối đá granite khổng lồ hình dáng như con voi đang nằm. Du khách có thể leo lên đỉnh để ngắm nhìn toàn cảnh hồ Lắk và rừng nguyên sinh.',
        'Lắk, Đắk Lắk',
        12.4411, 108.1991,
        url('/assets/images/uploads/article_2_museum.png'),
        1.5,
        'free'
    ]
];

$stmt = $db->prepare("INSERT INTO destinations (name, slug, category_id, short_desc, description, address, latitude, longitude, image_url, avg_visit_hours, price_level) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

foreach ($newDestinations as $d) {
    try {
        $stmt->execute($d);
        echo "Inserted {$d[0]}\n";
    } catch (Exception $e) {
        echo "Error inserting {$d[0]}: " . $e->getMessage() . "\n";
    }
}
echo "Done";
