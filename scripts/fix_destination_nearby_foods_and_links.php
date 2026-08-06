<?php
require_once __DIR__ . '/../includes/functions.php';

try {
    $db = getDB();
    echo "=========================================================\n";
    echo "  LIÊN KẾT ẨM THỰC THỰC TẾ VÀO ĐIỂM ĐẾN TƯƠNG ỨNG\n";
    echo "=========================================================\n\n";

    // 1. GÁN DESTINATION_ID CHO CÁC MÓN ĂN THỰC TẾ THEO ĐỊA DANH KÍCH HOẠT
    $destinations = $db->query("SELECT id, name, region, province, address FROM destinations")->fetchAll(PDO::FETCH_ASSOC);

    // Xóa các món ăn generic placeholder trùng lặp dạng "Món ăn chợ địa phương..."
    $deletedGen = $db->exec("DELETE FROM foods WHERE name LIKE 'Món ăn chợ địa phương%' OR name LIKE 'Quán ăn gia truyền%' OR name LIKE 'Đặc sản địa phương%'");
    echo "✓ Đã xóa {$deletedGen} món ăn rác placeholder lặp lại.\n";

    // Liên kết món ăn theo từ khóa điểm đến
    $linkedCount = 0;
    foreach ($destinations as $d) {
        $destId = (int)$d['id'];
        $destNameLower = mb_strtolower($d['name']);
        
        // Trích xuất các từ khóa đặc trưng (ví dụ: hồ lắk -> lắk, buôn đôn -> buôn đôn)
        $keywords = [];
        if (strpos($destNameLower, 'hồ lắk') !== false || strpos($destNameLower, 'hồ lắc') !== false) {
            $keywords[] = 'lắk'; $keywords[] = 'lắc';
        } elseif (strpos($destNameLower, 'buôn đôn') !== false) {
            $keywords[] = 'buôn đôn';
        } elseif (strpos($destNameLower, 'buôn ma thuột') !== false) {
            $keywords[] = 'buôn ma thuột'; $keywords[] = 'bmt';
        } elseif (strpos($destNameLower, 'ô loan') !== false) {
            $keywords[] = 'ô loan';
        } elseif (strpos($destNameLower, 'tuy hòa') !== false || strpos($destNameLower, 'phú yên') !== false) {
            $keywords[] = 'tuy hòa'; $keywords[] = 'phú yên';
        } elseif (strpos($destNameLower, 'vũng rô') !== false) {
            $keywords[] = 'vũng rô';
        } elseif (strpos($destNameLower, 'mũi điện') !== false || strpos($destNameLower, 'bãi môn') !== false) {
            $keywords[] = 'mũi điện'; $keywords[] = 'bãi môn';
        }

        foreach ($keywords as $kw) {
            $stmt = $db->prepare("UPDATE foods SET destination_id = ?, region = ? WHERE LOWER(name) LIKE ? OR LOWER(address) LIKE ?");
            $stmt->execute([$destId, $d['region'], '%' . $kw . '%', '%' . $kw . '%']);
            $linkedCount += $stmt->rowCount();
        }
    }
    echo "✓ Đã liên kết {$linkedCount} món ăn thực tế vào điểm đến cụ thể.\n";

    // 2. THÊM CÁC MÓN ĐẶC SẢN NỔI TIẾNG THEO ĐIỂM ĐẾN NẾU THIẾU
    $realFoodsToInsert = [
        // Hồ Lắk (destination_id = 1)
        ['name' => 'Chả cá thát lát Hồ Lắk', 'dest_id' => 1, 'type' => 'dish', 'region' => 'west', 'addr' => 'Thị trấn Liên Sơn, Huyện Lắk', 'img' => '/assets/images/uploads/food_cat_fish.jpg'],
        ['name' => 'Cá lăng nướng than hồng Hồ Lắk', 'dest_id' => 1, 'type' => 'dish', 'region' => 'west', 'addr' => 'Bờ Hồ Lắk, Huyện Lắk', 'img' => '/assets/images/uploads/food_cat_fish.jpg'],
        ['name' => 'Cà phê View Hồ Lắk Sunset', 'dest_id' => 1, 'type' => 'cafe', 'region' => 'west', 'addr' => 'Đường Âu Cơ, TT. Liên Sơn, Huyện Lắk', 'img' => '/assets/images/uploads/food_cat_cafe.jpg'],
        ['name' => 'Gà nướng lá é Hồ Lắk', 'dest_id' => 1, 'type' => 'restaurant', 'region' => 'west', 'addr' => 'Buôn Jun, Huyện Lắk', 'img' => '/assets/images/uploads/food_cat_chicken.jpg'],

        // Buôn Đôn (destination_id = 4)
        ['name' => 'Gà nướng cơm lam Buôn Đôn', 'dest_id' => 4, 'type' => 'dish', 'region' => 'west', 'addr' => 'Khu du lịch Buôn Đôn', 'img' => '/assets/images/uploads/food_cat_chicken.jpg'],
        ['name' => 'Lẩu cá lăng sông Sêrêpốk', 'dest_id' => 4, 'type' => 'dish', 'region' => 'west', 'addr' => 'Sông Sêrêpốk, Buôn Đôn', 'img' => '/assets/images/uploads/food_cat_fish.jpg'],
        ['name' => 'Rượu cần Êđê Buôn Đôn', 'dest_id' => 4, 'type' => 'drink', 'region' => 'west', 'addr' => 'Buôn Krông Na, Buôn Đôn', 'img' => '/assets/images/uploads/food_cat_drink.jpg'],

        // Tuy Hòa / Tháp Nghinh Phong (destination_id = 13)
        ['name' => 'Mắt cá ngừ đại dương Tuy Hòa', 'dest_id' => 13, 'type' => 'dish', 'region' => 'east', 'addr' => 'Lê Duẩn, TP. Tuy Hòa', 'img' => '/assets/images/uploads/food_cat_fish.jpg'],
        ['name' => 'Cơm gà Tuy Hòa trứ danh', 'dest_id' => 13, 'type' => 'restaurant', 'region' => 'east', 'addr' => 'Lê Thánh Tôn, TP. Tuy Hòa', 'img' => '/assets/images/uploads/food_cat_chicken.jpg'],
        ['name' => 'Cà phê biển Nghinh Phong Sunset', 'dest_id' => 13, 'type' => 'cafe', 'region' => 'east', 'addr' => 'Quảng trường Nghinh Phong, Tuy Hòa', 'img' => '/assets/images/uploads/food_cat_cafe.jpg']
    ];

    foreach ($realFoodsToInsert as $rf) {
        $slug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', iconv('UTF-8', 'ASCII//TRANSLIT', $rf['name'])), '-'));
        $chk = $db->prepare("SELECT id FROM foods WHERE name = ? OR slug = ?");
        $chk->execute([$rf['name'], $slug]);
        if (!$chk->fetch()) {
            $ins = $db->prepare("INSERT INTO foods (name, slug, destination_id, entity_type, region, address, image_url, is_featured, status) VALUES (?, ?, ?, ?, ?, ?, ?, 1, 'published')");
            $ins->execute([$rf['name'], $slug, $rf['dest_id'], $rf['type'], $rf['region'], $rf['addr'], $rf['img']]);
        } else {
            $upd = $db->prepare("UPDATE foods SET destination_id = ?, region = ?, image_url = ? WHERE name = ?");
            $upd->execute([$rf['dest_id'], $rf['region'], $rf['img'], $rf['name']]);
        }
    }
    echo "✓ Đã bổ sung danh sách món đặc sản địa phương tiêu biểu thực tế.\n";

    echo "\n=========================================================\n";
    echo "  HOÀN THÀNH LIÊN KẾT ẨM THỰC THỰC TẾ VÀO ĐIỂM ĐẾN  \n";
    echo "=========================================================\n";

} catch (Exception $e) {
    echo "Lỗi: " . $e->getMessage() . "\n";
}
