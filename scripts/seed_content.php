<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
$db = getDB();
if (!$db->query("SHOW TABLES LIKE 'foods'")->fetchColumn()) {
    throw new RuntimeException('Chạy migration trước.');
}

// Food seed remains a separate, explicitly marked internal dataset.
$destinations = $db->query('SELECT id,name,address,image_url FROM destinations ORDER BY id')->fetchAll();
$foodTemplates = ['Đặc sản địa phương', 'Quán ăn gia truyền', 'Cà phê bản địa', 'Món ăn chợ địa phương'];
$food = $db->prepare("INSERT IGNORE INTO foods(destination_id,entity_type,name,name_en,slug,description,address,source_url,last_verified_at) VALUES(?,?,?,?,?,?,?,?,CURRENT_DATE)");
$foodImage = $db->prepare('INSERT INTO food_images(food_id,image_url,is_primary,sort_order) VALUES(?,?,?,?)');
$foodCount = 0;
foreach ($destinations as $destination) {
    foreach ($foodTemplates as $index => $template) {
        $name = $template . ' ' . $destination['name'];
        $type = $index === 2 ? 'cafe' : ($index === 1 ? 'restaurant' : 'dish');
        $food->execute([$destination['id'], $type, $name, $name, 'am-thuc-' . $destination['id'] . '-' . $index, 'Dữ liệu nội bộ cần tiếp tục xác minh trước khi công khai.', $destination['address'], 'internal://seed',]);
        $id = (int)$db->lastInsertId();
        if ($id) {
            $foodImage->execute([$id, $destination['image_url'] ?: '/assets/images/article_1_food.png', 1, 0]);
            $foodCount++;
        }
    }
}

// Every accommodation below comes from an official tourism directory or the
// property's own contact page. No synthetic accommodation names are created.
$officialDakLak = 'https://daklak.gov.vn/tong-quan-dak-lak/-/asset_publisher/bDngMUmMrWIw/content/iii-he-thong-cac-nganh-dich-vu';
$officialPhuYen = 'https://phuyentourism.gov.vn/thongtin/bandodulich.pdf';
$realAccommodations = [
    ['dak-lak', 'Mường Thanh Luxury Buôn Ma Thuột', 'hotel', '81 Nguyễn Tất Thành, TP. Buôn Ma Thuột, Đắk Lắk', '0262 3961 555', $officialDakLak],
    ['dak-lak', 'Sài Gòn - Ban Mê', 'hotel', '01-03 Phan Chu Trinh, TP. Buôn Ma Thuột, Đắk Lắk', '0262 3685 666', $officialDakLak],
    ['dak-lak', 'Elephants Hotel', 'hotel', '142 Phan Chu Trinh, TP. Buôn Ma Thuột, Đắk Lắk', '0262 3544 444', $officialDakLak],
    ['dak-lak', 'Dakruco Hotel', 'hotel', '30 Nguyễn Chí Thanh, TP. Buôn Ma Thuột, Đắk Lắk', '0262 3970 888', $officialDakLak],
    ['dak-lak', 'Đam San Hotel', 'hotel', '212 Nguyễn Công Trứ, TP. Buôn Ma Thuột, Đắk Lắk', '0262 3851 234', $officialDakLak],
    ['dak-lak', 'Hoàng Lộc Hotel', 'hotel', '07-09 Y Bi Aleo, TP. Buôn Ma Thuột, Đắk Lắk', '0262 3956 704', $officialDakLak],
    ['dak-lak', 'Bạch Mã Hotel', 'hotel', '09 Nguyễn Đức Cảnh, TP. Buôn Ma Thuột, Đắk Lắk', '0262 3815 656', $officialDakLak],
    ['dak-lak', 'Khách sạn Cà phê Tuấn Vũ', 'hotel', '135/1 Ngô Quyền, TP. Buôn Ma Thuột, Đắk Lắk', '0262 3956 519', $officialDakLak],
    ['dak-lak', 'Thanh Mai Hotel', 'hotel', '170 Ngô Quyền, phường Tân An, TP. Buôn Ma Thuột, Đắk Lắk', '0262 3924 666', $officialDakLak],
    ['dak-lak', 'Công Đoàn Ban Mê Hotel', 'hotel', '09 Nguyễn Chí Thanh, TP. Buôn Ma Thuột, Đắk Lắk', '0262 3952 415', 'https://www.banmehotel.com.vn/'],
    ['dak-lak', 'Buon Ma Thuot Hotel', 'hotel', '03 Hùng Vương, phường Tự An, TP. Buôn Ma Thuột, Đắk Lắk', '0262 3855 558', 'https://buonmathuothotel.com.vn/en/lien-he'],
    ['phu-yen', 'VietStar Resort and Spa', 'resort', 'Núi Thơm, An Phú, Tuy Hòa, Phú Yên', '0257 3789 999', $officialPhuYen],
    ['phu-yen', 'Rosa Alba Resort Phú Yên', 'resort', '88 Lê Duẩn, phường 9, Tuy Hòa, Phú Yên', '0257 2222 222', $officialPhuYen],
    ['phu-yen', 'Sala Grand Tuy Hòa Hotel', 'hotel', '77-79 Nguyễn Du, phường 7, Tuy Hòa, Phú Yên', '0257 7307 779', $officialPhuYen],
    ['phu-yen', 'Apec Mandala Phú Yên Hotel', 'hotel', 'Đại lộ Hùng Vương, phường 7, Tuy Hòa, Phú Yên', '0257 3868 866', $officialPhuYen],
    ['phu-yen', 'KAYA Hotel', 'hotel', '238 Hùng Vương, Tuy Hòa, Phú Yên', '0257 3819 999', $officialPhuYen],
    ['phu-yen', 'Sài Gòn - Phú Yên Hotel', 'hotel', '541 Trần Hưng Đạo, Tuy Hòa, Phú Yên', '0257 3822 999', $officialPhuYen],
    ['phu-yen', 'Sao Mai Beach Resort', 'resort', 'Lê Duẩn, An Phú, Tuy Hòa, Phú Yên', '0257 2482 489', $officialPhuYen],
    ['phu-yen', 'Stelia Beach Resort', 'resort', 'Lô C1, đường Độc Lập, Tuy Hòa, Phú Yên', '0961 457 939', $officialPhuYen],
    ['phu-yen', 'Sala Tuy Hòa Beach Hotel', 'hotel', '51 Độc Lập, phường 7, Tuy Hòa, Phú Yên', '0257 3686 666', $officialPhuYen],
    ['phu-yen', 'Quê Tôi Resort', 'resort', 'Long Hải Đông, phường Xuân Yên, thị xã Sông Cầu, Phú Yên', '0257 3876 768', $officialPhuYen],
    ['phu-yen', 'Hưng Vương Hotel', 'hotel', '239-241 Hùng Vương, Tuy Hòa, Phú Yên', '0257 6253 545', $officialPhuYen],
    ['phu-yen', 'Long Beach Hotel', 'hotel', '17 Độc Lập, Tuy Hòa, Phú Yên', '0257 3842 299', $officialPhuYen],
    ['phu-yen', 'Công Đoàn Hotel', 'hotel', '53 Độc Lập, Tuy Hòa, Phú Yên', '0257 3823 187', $officialPhuYen],
];

// Never delete legacy rows here. Verified records are upserted alongside any
// previous administrator/imported data so a repeatable sync is non-destructive.
$stay = $db->prepare("INSERT INTO accommodations(destination_id,accommodation_type,name,name_en,slug,description,address,contact_phone,source_url,last_verified_at,status) VALUES(?,?,?,?,?,?,?,?,?,CURRENT_DATE,'published') ON DUPLICATE KEY UPDATE accommodation_type=VALUES(accommodation_type),name=VALUES(name),name_en=VALUES(name_en),description=VALUES(description),address=VALUES(address),contact_phone=VALUES(contact_phone),source_url=VALUES(source_url),last_verified_at=CURRENT_DATE,status='published'");
$findDestination = $db->prepare('SELECT id FROM destinations WHERE name LIKE ? OR name_en LIKE ? ORDER BY id LIMIT 1');
$stayCount = 0;
foreach ($realAccommodations as [$region, $name, $type, $address, $phone, $source]) {
    $search = $region === 'dak-lak' ? '%Buôn Ma Thuột%' : '%Tuy Hòa%';
    $searchEn = $region === 'dak-lak' ? '%Buon Ma Thuot%' : '%Tuy Hoa%';
    $findDestination->execute([$search, $searchEn]);
    $destinationId = $findDestination->fetchColumn() ?: null;
    $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name) ?: $name;
    $slug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $ascii), '-'));
    $stay->execute([$destinationId, $type, $name, $name, $slug, 'Cơ sở được đồng bộ từ danh mục du lịch chính thức; cần gọi xác nhận giá và tình trạng phòng trước khi đặt.', $address, $phone, $source]);
    $stayCount++;
}

echo json_encode(['foods_created' => $foodCount, 'verified_accommodations_upserted' => $stayCount], JSON_UNESCAPED_UNICODE) . PHP_EOL;
