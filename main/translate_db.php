<?php
require_once __DIR__ . '/includes/functions.php';
$db = getDB();

try {
    $db->exec('ALTER TABLE categories ADD COLUMN name_en VARCHAR(255) AFTER name');
    $db->exec('ALTER TABLE destinations ADD COLUMN name_en VARCHAR(255) AFTER name, ADD COLUMN short_desc_en TEXT AFTER short_desc, ADD COLUMN description_en TEXT AFTER description');
    $db->exec('ALTER TABLE articles ADD COLUMN title_en VARCHAR(255) AFTER title, ADD COLUMN summary_en TEXT AFTER summary, ADD COLUMN content_en TEXT AFTER content');
    echo "Columns added.\n";
} catch (Exception $e) {
    echo "Columns already exist.\n";
}

// Translate categories
$db->exec("UPDATE categories SET name_en = 'Nature & Exploration' WHERE name = 'Thiên nhiên & Khám phá'");
$db->exec("UPDATE categories SET name_en = 'Culture & History' WHERE name = 'Văn hoá & Lịch sử'");
$db->exec("UPDATE categories SET name_en = 'Cuisine & Coffee' WHERE name = 'Ẩm thực & Cà phê'");
$db->exec("UPDATE categories SET name_en = 'Relaxation & Resort' WHERE name = 'Nghỉ dưỡng & Check-in'");

// Translate destinations
$destinations = [
    'ho-lak' => [
        'name' => 'Lak Lake',
        'short_desc' => 'The 2nd largest natural freshwater lake in Vietnam, deeply connected to M\'nong culture.',
        'desc' => 'Lak Lake is a famous eco-tourism destination in Dak Lak. Visitors can experience dugout canoe rides, admire the sunset on the lake, and explore the traditional longhouses of the M\'nong people.'
    ],
    'thac-dray-nur' => [
        'name' => 'Dray Nur Waterfall',
        'short_desc' => 'One of the most majestic waterfalls in the Central Highlands.',
        'desc' => 'Dray Nur waterfall is breathtakingly beautiful with its massive curtain of water. You can explore the mysterious cave behind the waterfall and enjoy the pristine nature.'
    ],
    'lang-ca-phe-trung-nguyen' => [
        'name' => 'Trung Nguyen Coffee Village',
        'short_desc' => 'The coffee capital of Vietnam, a great place to experience the culture of roasted coffee.',
        'desc' => 'A must-visit for coffee lovers. You can learn about the history of coffee, admire unique architecture, and taste premium coffee blends.'
    ],
    'thac-dray-sap' => [
        'name' => 'Dray Sap Waterfall',
        'short_desc' => 'The famous \'smoke waterfall\' near Buon Ma Thuot.',
        'desc' => 'Located near Dray Nur, Dray Sap is known for the mist that rises from the falling water, creating a mystical atmosphere in the middle of the jungle.'
    ],
    'buon-don' => [
        'name' => 'Buon Don',
        'short_desc' => 'Famous for its elephant taming tradition and the suspension bridge over Serepok river.',
        'desc' => 'Experience the wild nature, walk on the long suspension bridge, and learn about the unique culture of the local ethnic minorities.'
    ],
    'bao-tang-the-gioi-ca-phe' => [
        'name' => 'The World Coffee Museum',
        'short_desc' => 'Unique architecture inspired by the local longhouses, exhibiting global coffee culture.',
        'desc' => 'A modern museum with interactive exhibitions about the history and culture of coffee around the world, housed in a stunning architectural masterpiece.'
    ],
    'vuon-quoc-gia-yok-don' => [
        'name' => 'Yok Don National Park',
        'short_desc' => 'The only national park in Vietnam that preserves the dipterocarp forest ecosystem.',
        'desc' => 'A paradise for nature lovers. You can join trekking tours, go bird watching, and see the ethical elephant tours where elephants roam freely.'
    ],
    'chu-yang-sin' => [
        'name' => 'Chu Yang Sin National Park',
        'short_desc' => 'The roof of Dak Lak with diverse flora and fauna.',
        'desc' => 'Ideal for adventurous trekkers. The park is home to many rare and endemic species and offers challenging trails leading to the peak of Chu Yang Sin.'
    ]
];

foreach ($destinations as $slug => $trans) {
    $stmt = $db->prepare("UPDATE destinations SET name_en = ?, short_desc_en = ?, description_en = ? WHERE slug = ?");
    $stmt->execute([$trans['name'], $trans['short_desc'], $trans['desc'], $slug]);
}

// Translate Articles
$articles = [
    'kham-pha-am-thuc-dak-lak' => [
        'title' => 'Discovering Dak Lak Cuisine',
        'summary' => 'From grilled chicken with bamboo rice to red noodle soup, explore the unique flavors of the Central Highlands.',
        'content' => 'Dak Lak is not only famous for its majestic nature but also for its rich and unique cuisine. Here are the must-try dishes:\n\n1. **Grilled Chicken with Bamboo Rice (Cơm lam gà nướng):** A signature dish of the local people.\n2. **Red Noodle Soup (Bún đỏ):** A popular street food in Buon Ma Thuot.\n3. **Venison (Thịt nai):** A specialty often served as a gift.\n\nDon\'t miss the chance to taste these amazing flavors!'
    ],
    'kinh-nghiem-tham-quan-bao-tang-ca-phe' => [
        'title' => 'Tips for Visiting The World Coffee Museum',
        'summary' => 'Everything you need to know before visiting this iconic architectural landmark.',
        'content' => 'The World Coffee Museum is a must-visit spot. Here are some tips for your trip:\n\n- **Best time to visit:** Early morning or late afternoon for the best lighting.\n- **Tickets:** Buy tickets online or at the counter.\n- **What to do:** Explore the exhibitions, taste the coffee, and take stunning photos with the unique architecture.\n\nEnjoy your time in the coffee capital!'
    ],
    'thoi-diem-ly-tuong-du-lich-thac-nuoc' => [
        'title' => 'Best Time to Visit Waterfalls in Dak Lak',
        'summary' => 'When should you visit Dray Nur and Dray Sap for the most spectacular views?',
        'content' => 'The Central Highlands has two distinct seasons: the rainy season and the dry season.\n\n- **Rainy season (May - Oct):** The waterfalls are most majestic with huge volumes of water.\n- **Dry season (Nov - Apr):** The water is clearer and calmer, perfect for swimming and picnics.\n\nChoose the best time based on your preferences!'
    ]
];

foreach ($articles as $slug => $trans) {
    $stmt = $db->prepare("UPDATE articles SET title_en = ?, summary_en = ?, content_en = ? WHERE slug = ?");
    $stmt->execute([$trans['title'], $trans['summary'], $trans['content'], $slug]);
}

echo "Database translation completed.\n";
?>