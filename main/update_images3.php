<?php
require 'includes/functions.php';
$db = getDB();

$updates = [
    'ganh-da-dia-phu-yen' => url('/assets/images/uploads/article_3_waterfall.png'),
    'dam-o-loan-phu-yen' => url('/assets/images/uploads/article_3_waterfall.png'),
    'vinh-vung-ro' => url('/assets/images/uploads/article_3_waterfall.png'),
    'bai-mon-mui-dien' => url('/assets/images/uploads/article_3_waterfall.png')
];

foreach ($updates as $slug => $url) {
    $stmt = $db->prepare("UPDATE destinations SET image_url = ? WHERE slug = ?");
    $stmt->execute([$url, $slug]);
}
echo "Done";
