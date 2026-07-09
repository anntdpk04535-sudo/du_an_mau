<?php
require_once __DIR__ . '/includes/functions.php';
$db = getDB();

// Categories
$categories = [
    'thac-nuoc' => 'Waterfalls',
    'ho' => 'Lakes',
    'buon-lang-van-hoa' => 'Villages & Culture',
    'vuon-quoc-gia' => 'National Parks',
    'am-thuc' => 'Cuisine'
];

foreach ($categories as $slug => $name_en) {
    $db->prepare("UPDATE categories SET name_en = ? WHERE slug = ?")->execute([$name_en, $slug]);
}

// Destinations
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
    'vuon-quoc-gia-yok-don' => [
        'name' => 'Yok Don National Park',
        'short_desc' => 'The only national park in Vietnam that preserves the dipterocarp forest ecosystem.',
        'desc' => 'A paradise for nature lovers. You can join trekking tours, go bird watching, and see the ethical elephant tours where elephants roam freely.'
    ],
    'ca-phe-buon-ma-thuot' => [
        'name' => 'Buon Ma Thuot Coffee',
        'short_desc' => 'The coffee capital of Vietnam, a great place to experience the culture of roasted coffee.',
        'desc' => 'A must-visit for coffee lovers. You can learn about the history of coffee, admire unique architecture, and taste premium coffee blends.'
    ],
    'buon-ako-dhong' => [
        'name' => 'Ako Dhong Village',
        'short_desc' => 'A peaceful E De village in the heart of Buon Ma Thuot city.',
        'desc' => 'Ako Dhong is known for its beautiful traditional longhouses, peaceful atmosphere, and the rich cultural heritage of the E De people.'
    ],
    'ho-ea-kao' => [
        'name' => 'Ea Kao Lake',
        'short_desc' => 'A serene lake surrounded by green forests, perfect for picnics and sunset watching.',
        'desc' => 'Ea Kao Lake is a great place to relax, enjoy the fresh air, and watch the beautiful sunset over the water.'
    ]
];

foreach ($destinations as $slug => $trans) {
    $db->prepare("UPDATE destinations SET name_en = ?, short_desc_en = ?, description_en = ? WHERE slug = ?")
       ->execute([$trans['name'], $trans['short_desc'], $trans['desc'], $slug]);
}

// Articles
$articles = [
    'top-5-mon-dac-san-dak-lak' => [
        'title' => 'Top 5 Must-Try Specialties in Dak Lak',
        'summary' => 'From grilled chicken with bamboo rice to red noodle soup, explore the unique flavors of the Central Highlands.',
        'content' => 'Dak Lak is not only famous for its majestic nature but also for its rich and unique cuisine. Here are the must-try dishes:<br><br>1. **Grilled Chicken with Bamboo Rice (Cơm lam gà nướng):** A signature dish of the local people.<br>2. **Red Noodle Soup (Bún đỏ):** A popular street food in Buon Ma Thuot.<br>3. **Venison (Thịt nai):** A specialty often served as a gift.<br><br>Don\'t miss the chance to taste these amazing flavors!'
    ],
    'kinh-nghiem-du-lich-bao-tang-the-gioi-ca-phe' => [
        'title' => 'Tips for Visiting The World Coffee Museum',
        'summary' => 'Everything you need to know before visiting this iconic architectural landmark.',
        'content' => 'The World Coffee Museum is a must-visit spot. Here are some tips for your trip:<br><br>- **Best time to visit:** Early morning or late afternoon for the best lighting.<br>- **Tickets:** Buy tickets online or at the counter.<br>- **What to do:** Explore the exhibitions, taste the coffee, and take stunning photos with the unique architecture.<br><br>Enjoy your time in the coffee capital!'
    ],
    'lich-trinh-3-ngay-2-dem-phuot-dak-lak' => [
        'title' => '3 Days 2 Nights Backpacking Itinerary in Dak Lak',
        'summary' => 'A detailed itinerary for a perfect weekend getaway in the Central Highlands.',
        'content' => 'If you only have a weekend, here is the perfect itinerary:<br><br>- **Day 1:** Explore Buon Ma Thuot city, visit the Coffee Museum, and enjoy local food.<br>- **Day 2:** Visit Dray Nur and Dray Sap waterfalls, then head to Lak Lake for sunset.<br>- **Day 3:** Ride elephants in Buon Don and head back home.<br><br>Enjoy your trip!'
    ]
];

foreach ($articles as $slug => $trans) {
    $db->prepare("UPDATE articles SET title_en = ?, summary_en = ?, content_en = ? WHERE slug = ?")
       ->execute([$trans['title'], $trans['summary'], $trans['content'], $slug]);
}

echo "Database translation phase 2 completed.\n";
?>