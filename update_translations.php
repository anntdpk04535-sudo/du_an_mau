<?php
require 'config/env.php';
require 'config/db.php';
$pdo = getDB();

$updates = [
    1 => ['Lak Lake View', 'Experience the vast, green surface of Lak Lake.'],
    2 => ['M\'Nong Village', 'Discover the unique indigenous culture of the M\'Nong people around Lak Lake.'],
    3 => ['Dray Nur Waterfall', 'Experience a 360-degree view at Dray Nur Waterfall.'],
    4 => ['Dray Sap Waterfall', 'Experience a 360-degree view at Dray Sap Waterfall.'],
    5 => ['Buon Don', 'Experience a 360-degree view at Buon Don.'],
    6 => ['Yok Don National Park', 'Experience a 360-degree view at Yok Don National Park.'],
    7 => ['Buon Ma Thuot Coffee', 'Experience a 360-degree view at Buon Ma Thuot Coffee.'],
    8 => ['Ako Dhong Village', 'Experience a 360-degree view at Ako Dhong Village.'],
    9 => ['Ea Kao Lake', 'Experience a 360-degree view at Ea Kao Lake.'],
    10 => ['Ganh Da Dia - Panorama', 'Experience a stunning 360-degree view of Ganh Da Dia.'],
    11 => ['Dam O Loan - Panorama', 'Enjoy a 360-degree sunset view of the calm Dam O Loan lagoon.'],
    12 => ['Vung Ro Bay - Panorama', 'Discover the beautiful 360-degree scenery of Vung Ro Bay.'],
    13 => ['Bai Mon - Mui Dien - Panorama', 'Explore Bai Mon beach and Mui Dien lighthouse in 360 degrees.'],
    14 => ['Nghinh Phong Tower - Panorama', 'Admire the modern architecture of Nghinh Phong Tower by the sea.'],
    15 => ['Hon Yen - Panorama', 'View the colorful coral reefs of Hon Yen island from above.'],
    16 => ['Thuy Tien Waterfall - Panorama', 'Experience a multi-tiered waterfall in the lush green jungle.'],
    17 => ['Elephant Rock - Panorama', 'See the massive elephant-shaped granite rock from a 360-degree view.']
];

$stmt = $pdo->prepare("UPDATE virtual_tour_scenes SET title_en = ?, description_en = ? WHERE id = ?");

foreach ($updates as $id => $data) {
    $stmt->execute([$data[0], $data[1], $id]);
}

// Also update the empty Vietnamese descriptions for IDs 10 to 17
$vi_updates = [
    10 => 'Trải nghiệm ngắm nhìn toàn cảnh 360 độ tuyệt đẹp tại Gành Đá Đĩa.',
    11 => 'Chiêm ngưỡng hoàng hôn 360 độ tại Đầm Ô Loan thanh bình.',
    12 => 'Khám phá không gian bao la 360 độ của Vịnh Vũng Rô.',
    13 => 'Ngắm nhìn Bãi Môn và ngọn hải đăng Mũi Điện từ góc nhìn 360 độ.',
    14 => 'Chiêm ngưỡng kiến trúc độc đáo của Tháp Nghinh Phong sát bờ biển.',
    15 => 'Ngắm nhìn rạn san hô tuyệt đẹp tại Hòn Yến từ trên cao.',
    16 => 'Trải nghiệm không gian 360 độ tại Thác Thủy Tiên giữa rừng xanh.',
    17 => 'Chiêm ngưỡng toàn cảnh Núi Đá Voi Mẹ khổng lồ giữa thiên nhiên.'
];

$stmt_vi = $pdo->prepare("UPDATE virtual_tour_scenes SET description = ? WHERE id = ?");
foreach ($vi_updates as $id => $desc) {
    $stmt_vi->execute([$desc, $id]);
}

echo "Database successfully updated with proper English and Vietnamese descriptions!";
