<?php
require 'includes/functions.php';
$db = getDB();

$updates = [
    'ganh-da-dia-phu-yen' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/c/ca/Ghenh-da-dia_Phu-Yen_110609.jpg/800px-Ghenh-da-dia_Phu-Yen_110609.jpg',
    'dam-o-loan-phu-yen' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/6/60/Dam_O_Loan.jpg/800px-Dam_O_Loan.jpg',
    'vinh-vung-ro' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/c/cc/Vung_Ro_Bay.jpg/800px-Vung_Ro_Bay.jpg',
    'bai-mon-mui-dien' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/1/1a/Hai_Dang_Mui_Dien_%28Dai_Lanh%29_-_Phu_Yen.jpg/800px-Hai_Dang_Mui_Dien_%28Dai_Lanh%29_-_Phu_Yen.jpg'
];

foreach ($updates as $slug => $url) {
    $stmt = $db->prepare("UPDATE destinations SET image_url = ? WHERE slug = ?");
    $stmt->execute([$url, $slug]);
    echo "Updated $slug\n";
}
echo "Done";
