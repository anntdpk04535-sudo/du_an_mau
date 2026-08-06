<?php
require_once __DIR__ . '/../includes/functions.php';

try {
    $db = getDB();

    echo "===========================================\n";
    echo "  KHO HÌNH ẢNH THỰC TẾ DU LỊCH ĐẮK LẮK AI  \n";
    echo "===========================================\n\n";

    // 1. CHUẨN BỊ BỘ SỰU TẬP HÌNH ẢNH SẮC NÉT TỪ INTERNET (UNSPLASH / DAKLAK TOURISM)
    $destinationImages = [
        'waterfall' => [
            'https://images.unsplash.com/photo-1432405972618-c60b0225b8f9?auto=format&fit=crop&w=1200&q=80', // Thác hùng vĩ
            'https://images.unsplash.com/photo-1546182990-dffeafbe841d?auto=format&fit=crop&w=1200&q=80', // Thác nước thiên nhiên
            'https://images.unsplash.com/photo-1508873696983-2df515122519?auto=format&fit=crop&w=1200&q=80',
            'https://images.unsplash.com/photo-1518709268805-4e9042af9f23?auto=format&fit=crop&w=1200&q=80',
            'https://images.unsplash.com/photo-1470071459604-3b5ec3a7fe05?auto=format&fit=crop&w=1200&q=80'
        ],
        'lake' => [
            'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?auto=format&fit=crop&w=1200&q=80', // Hồ thơ mộng
            'https://images.unsplash.com/photo-1499793983690-e29da59ef1c2?auto=format&fit=crop&w=1200&q=80',
            'https://images.unsplash.com/photo-1501785888041-af3ef285b470?auto=format&fit=crop&w=1200&q=80'
        ],
        'coffee' => [
            'https://images.unsplash.com/photo-1447933601403-0c6688de566e?auto=format&fit=crop&w=1200&q=80', // Vườn cà phê
            'https://images.unsplash.com/photo-1514432324607-a09d9b4aefdd?auto=format&fit=crop&w=1200&q=80', // Ly cà phê Tây Nguyên
            'https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?auto=format&fit=crop&w=1200&q=80'
        ],
        'culture' => [
            'https://images.unsplash.com/photo-1566073771259-6a8506099945?auto=format&fit=crop&w=1200&q=80', // Kiến trúc & Bảo tàng
            'https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?auto=format&fit=crop&w=1200&q=80',
            'https://images.unsplash.com/photo-1513836279014-a89f7a76ae86?auto=format&fit=crop&w=1200&q=80' // Rừng nguyên sinh
        ],
        'mountain' => [
            'https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?auto=format&fit=crop&w=1200&q=80', // Núi cao vút
            'https://images.unsplash.com/photo-1486870591958-9b9d0d1dda99?auto=format&fit=crop&w=1200&q=80'
        ]
    ];

    $foodImages = [
        'dish' => [
            'https://images.unsplash.com/photo-1555126634-323283e090fa?auto=format&fit=crop&w=1200&q=80', // Món ăn đặc sản
            'https://images.unsplash.com/photo-1567620832903-9fc6debc209f?auto=format&fit=crop&w=1200&q=80',
            'https://images.unsplash.com/photo-1540420773420-3366772f4999?auto=format&fit=crop&w=1200&q=80',
            'https://images.unsplash.com/photo-1504674900247-0877df9cc836?auto=format&fit=crop&w=1200&q=80'
        ],
        'cafe' => [
            'https://images.unsplash.com/photo-1501339847302-ac426a4a7cbb?auto=format&fit=crop&w=1200&q=80', // Quán cà phê
            'https://images.unsplash.com/photo-1442512595331-e89e73853f31?auto=format&fit=crop&w=1200&q=80',
            'https://images.unsplash.com/photo-1554118811-1e0d58224f24?auto=format&fit=crop&w=1200&q=80'
        ],
        'restaurant' => [
            'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?auto=format&fit=crop&w=1200&q=80', // Nhà hàng sang trọng
            'https://images.unsplash.com/photo-1552566626-52f8b828add9?auto=format&fit=crop&w=1200&q=80'
        ]
    ];

    $stayImages = [
        'hotel' => [
            'https://images.unsplash.com/photo-1566073771259-6a8506099945?auto=format&fit=crop&w=1200&q=80', // Khách sạn
            'https://images.unsplash.com/photo-1582719508461-905c673771fd?auto=format&fit=crop&w=1200&q=80',
            'https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?auto=format&fit=crop&w=1200&q=80'
        ],
        'homestay' => [
            'https://images.unsplash.com/photo-1587061949409-02df41d5e562?auto=format&fit=crop&w=1200&q=80', // Homestay thiên nhiên
            'https://images.unsplash.com/photo-1520250497591-112f2f40a3f4?auto=format&fit=crop&w=1200&q=80',
            'https://images.unsplash.com/photo-1590490360182-c33d57733427?auto=format&fit=crop&w=1200&q=80'
        ],
        'resort' => [
            'https://images.unsplash.com/photo-1571896349842-33c89424de2d?auto=format&fit=crop&w=1200&q=80', // Resort nghỉ dưỡng
            'https://images.unsplash.com/photo-1540555700478-4be289fbecef?auto=format&fit=crop&w=1200&q=80'
        ]
    ];

    // 2. CẬP NHẬT ĐIỂM ĐẾN (DESTINATIONS)
    $destinations = $db->query("SELECT id, name FROM destinations")->fetchAll(PDO::FETCH_ASSOC);
    $destCount = 0;
    foreach ($destinations as $idx => $d) {
        $name = mb_strtolower($d['name']);
        $img = '';
        if (strpos($name, 'thác') !== false) {
            $img = $destinationImages['waterfall'][$idx % count($destinationImages['waterfall'])];
        } elseif (strpos($name, 'hồ') !== false || strpos($name, 'suối') !== false || strpos($name, 'sông') !== false) {
            $img = $destinationImages['lake'][$idx % count($destinationImages['lake'])];
        } elseif (strpos($name, 'cà phê') !== false || strpos($name, 'làng') !== false) {
            $img = $destinationImages['coffee'][$idx % count($destinationImages['coffee'])];
        } elseif (strpos($name, 'núi') !== false || strpos($name, 'đèo') !== false || strpos($name, 'rừng') !== false || strpos($name, 'vườn') !== false) {
            $img = $destinationImages['mountain'][$idx % count($destinationImages['mountain'])];
        } else {
            $img = $destinationImages['culture'][$idx % count($destinationImages['culture'])];
        }

        // Đánh dấu nổi bật cho khoảng 20% điểm đến tiêu biểu
        $isFeatured = ($idx < 12 || strpos($name, 'bảo tàng') !== false || strpos($name, 'khải đoan') !== false || strpos($name, 'dray nur') !== false || strpos($name, 'buôn đôn') !== false) ? 1 : 0;

        $stmt = $db->prepare("UPDATE destinations SET image_url = ?, is_featured = ? WHERE id = ?");
        $stmt->execute([$img, $isFeatured, $d['id']]);
        $destCount++;
    }
    echo "✓ Đã cập nhật hình ảnh & nổi bật cho {$destCount} Điểm Đến.\n";

    // 3. CẬP NHẬT ẨM THỰC (FOODS)
    $foods = $db->query("SELECT id, name, entity_type FROM foods")->fetchAll(PDO::FETCH_ASSOC);
    $foodCount = 0;
    foreach ($foods as $idx => $f) {
        $type = $f['entity_type'] ?? 'dish';
        $imgGroup = $foodImages[$type] ?? $foodImages['dish'];
        $img = $imgGroup[$idx % count($imgGroup)];
        $isFeatured = ($idx % 5 === 0) ? 1 : 0;

        $stmt = $db->prepare("UPDATE foods SET image_url = ?, is_featured = ? WHERE id = ?");
        $stmt->execute([$img, $isFeatured, $f['id']]);
        $foodCount++;
    }
    echo "✓ Đã cập nhật hình ảnh & nổi bật cho {$foodCount} Ẩm Thực địa phương.\n";

    // 4. CẬP NHẬT LƯU TRÚ (ACCOMMODATIONS)
    $accommodations = $db->query("SELECT id, name, accommodation_type FROM accommodations")->fetchAll(PDO::FETCH_ASSOC);
    $stayCount = 0;
    foreach ($accommodations as $idx => $a) {
        $type = $a['accommodation_type'] ?? 'hotel';
        $imgGroup = $stayImages[$type] ?? $stayImages['hotel'];
        $img = $imgGroup[$idx % count($imgGroup)];
        $isFeatured = ($idx % 6 === 0) ? 1 : 0;

        $stmt = $db->prepare("UPDATE accommodations SET image_url = ?, is_featured = ? WHERE id = ?");
        $stmt->execute([$img, $isFeatured, $a['id']]);

        // Cập nhật hoặc chèn thêm vào bảng accommodation_images để tương thích 100%
        $checkImg = $db->prepare("SELECT id FROM accommodation_images WHERE accommodation_id = ?");
        $checkImg->execute([$a['id']]);
        if (!$checkImg->fetch()) {
            $insImg = $db->prepare("INSERT INTO accommodation_images (accommodation_id, image_url, is_primary, sort_order) VALUES (?, ?, 1, 1)");
            $insImg->execute([$a['id'], $img]);
        } else {
            $updImg = $db->prepare("UPDATE accommodation_images SET image_url = ? WHERE accommodation_id = ? AND is_primary = 1");
            $updImg->execute([$img, $a['id']]);
        }
        $stayCount++;
    }
    echo "✓ Đã cập nhật hình ảnh & nổi bật cho {$stayCount} Nơi Lưu Trú.\n";

    echo "\n===========================================\n";
    echo "  HOÀN THÀNH CẬP NHẬT HÌNH ẢNH TOÀN BỘ DB  \n";
    echo "===========================================\n";

} catch (Exception $e) {
    echo "Lỗi: " . $e->getMessage() . "\n";
}
