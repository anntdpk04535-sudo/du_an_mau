<?php
require 'includes/functions.php';
$db = getDB();

$updates = [
    'ganh-da-dia-phu-yen' => [
        'name_en' => 'Ganh Da Dia - Phu Yen',
        'short_desc_en' => 'A natural masterpiece with interlocking basalt columns.',
        'description_en' => 'Ganh Da Dia is a unique geological phenomenon in Vietnam. It looks like a giant black honeycomb rising from the sea, a must-visit destination in Phu Yen.'
    ],
    'dam-o-loan-phu-yen' => [
        'name_en' => 'O Loan Lagoon',
        'short_desc_en' => 'A brackish lagoon famous for its delicious blood cockles.',
        'description_en' => 'O Loan Lagoon offers a peaceful scenery, especially at sunrise or sunset. Don\'t forget to try the famous seafood here, especially the blood cockles.'
    ],
    'vinh-vung-ro' => [
        'name_en' => 'Vung Ro Bay',
        'short_desc_en' => 'A blue bay with a heroic history and abundant seafood.',
        'description_en' => 'Vung Ro Bay is surrounded by high mountains. It was an important site for the Ho Chi Minh trail on the sea during the war, and now a beautiful spot for tourists.'
    ],
    'bai-mon-mui-dien' => [
        'name_en' => 'Bai Mon - Mui Dien',
        'short_desc_en' => 'The easternmost point of Vietnam, welcoming the first sunrise.',
        'description_en' => 'Mui Dien Cape features a historic lighthouse and the beautiful, pristine Bai Mon beach. It is one of the places to catch the first rays of the sun in Vietnam.'
    ],
    'thap-nghinh-phong' => [
        'name_en' => 'Nghinh Phong Tower',
        'short_desc_en' => 'The new tourism symbol of Tuy Hoa city, Phu Yen.',
        'description_en' => 'Inspired by the legend of Lac Long Quan and Au Co, Nghinh Phong Tower features unique architecture and is a must-visit check-in spot when visiting Phu Yen.'
    ],
    'hon-yen-phu-yen' => [
        'name_en' => 'Hon Yen',
        'short_desc_en' => 'A small island with beautiful coral reefs exposed at low tide.',
        'description_en' => 'Hon Yen retains its pristine beauty. During the beginning or middle of the lunar month, the low tide reveals incredibly vibrant coral reefs.'
    ],
    'thac-thuy-tien-dak-lak' => [
        'name_en' => 'Thuy Tien Waterfall',
        'short_desc_en' => 'A natural masterpiece with three levels in Krong Nang forest.',
        'description_en' => 'Thuy Tien Waterfall (also known as Three-Level Waterfall) has a spacious, pristine, and cool environment, perfect for picnics, stream bathing, and forest exploration.'
    ],
    'nui-da-voi-me' => [
        'name_en' => 'Mother Elephant Rock',
        'short_desc_en' => 'Vietnam\'s largest monolithic rock with mysterious legends.',
        'description_en' => 'Mother Elephant Rock is a massive granite block shaped like a lying elephant. Tourists can climb to the top to enjoy a panoramic view of Lak Lake and the primary forest.'
    ]
];

$stmt = $db->prepare("UPDATE destinations SET name_en = ?, short_desc_en = ?, description_en = ? WHERE slug = ?");

foreach ($updates as $slug => $data) {
    try {
        $stmt->execute([$data['name_en'], $data['short_desc_en'], $data['description_en'], $slug]);
        echo "Updated $slug\n";
    } catch (Exception $e) {
        echo "Error updating $slug: " . $e->getMessage() . "\n";
    }
}
echo "Done";
