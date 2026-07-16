<?php
require 'includes/functions.php';
$db = getDB();

$updates = [
    'ganh-da-dia-phu-yen' => 'https://vcdn1-dulich.vnecdn.net/2021/05/18/ghenh-da-dia-10-1621327110.jpg?w=1200&h=0&q=100&dpr=1&fit=crop&s=5HqQ_hXgB6rA2Mh_jN6U_w',
    'dam-o-loan-phu-yen' => 'https://ik.imagekit.io/tvlk/blog/2022/10/dam-o-loan-phu-yen-1.jpg',
    'vinh-vung-ro' => 'https://ik.imagekit.io/tvlk/blog/2022/10/vinh-vung-ro-1.jpg',
    'bai-mon-mui-dien' => 'https://ik.imagekit.io/tvlk/blog/2022/10/mui-dien-phu-yen-1.jpg'
];

foreach ($updates as $slug => $url) {
    $stmt = $db->prepare("UPDATE destinations SET image_url = ? WHERE slug = ?");
    $stmt->execute([$url, $slug]);
    echo "Updated $slug to $url\n";
}
echo "Done";
