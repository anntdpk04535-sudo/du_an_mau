<?php
require_once __DIR__ . '/../includes/functions.php';

try {
    $db = getDB();
    echo "=========================================================\n";
    echo "  LIÊN KẾT ẨM THỰC VÀO ĐÚNG ĐIỂM ĐẾN CHÍNH (DESTINATION_ID)\n";
    echo "=========================================================\n\n";

    // Bảng ánh xạ từ khóa tên món ăn -> ID điểm đến chính
    $exactFoodDestinationMapping = [
        1 => ['lắk', 'lắc', 'ho lak'], // Hồ Lắk
        2 => ['dray nur'], // Thác Dray Nur
        3 => ['dray sáp', 'dray sap'], // Thác Dray Sáp
        4 => ['buôn đôn', 'ban don'], // Buôn Đôn
        5 => ['yok đôn', 'yok don'], // VQG Yok Đôn
        6 => ['buôn ma thuột', 'bmt', 'ban me thuot'], // TP. Buôn Ma Thuột
        7 => ['akô dhông', 'ako dhong'], // Buôn Akô Dhông
        8 => ['ea kao'], // Hồ Ea Kao
        9 => ['đá đĩa', 'da dia'], // Gành Đá Đĩa
        10 => ['ô loan', 'o loan'], // Đầm Ô Loan
        11 => ['vũng rô', 'vung ro'], // Vịnh Vũng Rô
        12 => ['mũi điện', 'bãi môn', 'mui dien', 'bai mon'], // Mũi Điện - Bãi Môn
        13 => ['nghinh phong'], // Tháp Nghinh Phong
        14 => ['hòn yến', 'hon yen'], // Hòn Yến
        15 => ['thủy tiên', 'thuy tien'], // Thác Thủy Tiên
        16 => ['voi mẹ', 'voi me'], // Núi Đá Voi Mẹ
        17 => ['bảo tàng thế giới cà phê', 'bảo tàng cà phê'], // Bảo tàng Thế giới Cà phê
        19 => ['bảo tàng đắk lắk'], // Bảo tàng Đắk Lắk
        20 => ['khải đoan'], // Chùa Khải Đoan
        113 => ['tháp nhạn', 'thap nhan'], // Tháp Nhạn
        114 => ['mằng lăng', 'mang lang'] // Nhà thờ Mằng Lăng
    ];

    $totalUpdated = 0;
    foreach ($exactFoodDestinationMapping as $destId => $keywords) {
        $destStmt = $db->prepare("SELECT region FROM destinations WHERE id = ?");
        $destStmt->execute([$destId]);
        $destRegion = $destStmt->fetchColumn() ?: 'west';

        foreach ($keywords as $kw) {
            $stmt = $db->prepare("UPDATE foods SET destination_id = ?, region = ? WHERE LOWER(name) LIKE ? OR LOWER(address) LIKE ?");
            $stmt->execute([$destId, $destRegion, '%' . $kw . '%', '%' . $kw . '%']);
            $totalUpdated += $stmt->rowCount();
        }
    }
    echo "✓ Đã cập nhật destination_id chính xác cho {$totalUpdated} món ăn.\n";

    // Đơn giản hóa các tên món rác dạng "Cà phê bản địa..."
    $db->exec("UPDATE foods SET name = 'Cà phê đặc sản Hồ Lắk', image_url = '/assets/images/uploads/food_cat_coffee.jpg' WHERE name LIKE '%Cà phê bản địa Hồ Lắk%'");
    $db->exec("UPDATE foods SET name = 'Cá nướng sông Sêrêpốk', image_url = '/assets/images/uploads/food_cat_fish.jpg' WHERE name LIKE '%Cá lăng sông Serepok%' OR name LIKE '%Cá nướng hồ Lắk%'");
    $db->exec("UPDATE foods SET name = 'Gà nướng cơm lam Buôn Đôn', image_url = '/assets/images/uploads/food_cat_chicken.jpg' WHERE name LIKE '%Gà nướng Buôn Đôn%'");

    echo "\n=========================================================\n";
    echo "  HOÀN THÀNH CẬP NHẬT LIÊN KẾT ẨM THỰC VÀO ĐIỂM ĐẾN CHÍNH  \n";
    echo "=========================================================\n";

} catch (Exception $e) {
    echo "Lỗi: " . $e->getMessage() . "\n";
}
