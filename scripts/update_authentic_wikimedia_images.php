<?php
require_once __DIR__ . '/../includes/functions.php';

try {
    $db = getDB();
    echo "=========================================================\n";
    echo "  CẬP NHẬT HÌNH ẢNH THỰC TẾ VIỆT NAM (WIKIMEDIA & REAL SOURCES)\n";
    echo "=========================================================\n\n";

    // BẢNG ÁNH XẠ HÌNH ẢNH THỰC TẾ CHO ĐIỂM ĐẾN NỔI BẬT
    $realDestinationImages = [
        'gành đá đĩa' => 'https://upload.wikimedia.org/wikipedia/commons/7/7b/G%C3%A0nh_%C4%90%C3%A1_%C4%90%C4%A9a_-_Ph%C3%BA_Y%C3%AAn.jpg',
        'thác dray nur' => 'https://upload.wikimedia.org/wikipedia/commons/e/e0/Dray_nur_waterfall.jpg',
        'thác dray sáp' => 'https://upload.wikimedia.org/wikipedia/commons/9/90/Draysap01.JPG',
        'hồ lắk' => 'https://upload.wikimedia.org/wikipedia/commons/c/ca/H%E1%BB%93_L%E1%BA%AFk.jpg',
        'tháp nghinh phong' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/c/c5/Th%C3%A1p_Nghinh_Phong_Tuy_H%C3%B2a.jpg/1200px-Th%C3%A1p_Nghinh_Phong_Tuy_H%C3%B2a.jpg',
        'mũi điện' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/4/4b/M%C5%A9i_C%C6%B0%E1%BB%9Dng_Ph%C3%BA_Y%C3%AAn.jpg/1200px-M%C5%A9i_C%C6%B0%E1%BB%9Dng_Ph%C3%BA_Y%C3%AAn.jpg',
        'tháp nhạn' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/f/f9/Th%C3%A1p_Nh%E1%BA%A1n_-_Tuy_H%C3%B2a.jpg/1200px-Th%C3%A1p_Nh%E1%BA%A1n_-_Tuy_H%C3%B2a.jpg',
        'nhà thờ mằng lăng' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/a/a7/Nh%C3%A0_th%E1%BB%9D_M%E1%BA%B5ng_L%C4%83ng_-_Ph%C3%BA_Y%C3%AAn.jpg/1200px-Nh%C3%A0_th%E1%BB%9D_M%E1%BA%B5ng_L%C4%83ng_-_Ph%C3%BA_Y%C3%AAn.jpg',
        'đầm ô loan' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/9/9d/%C4%90%E1%BA%A7m_%C3%94_Loan.jpg/1200px-%C4%90%E1%BA%A7m_%C3%94_Loan.jpg',
        'bảo tàng thế giới cà phê' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/d/d1/B%E1%BA%A3o_t%C3%A0ng_Th%E1%BA%BF_gi%E1%BB%9Bi_C%C3%A0_ph%C3%AA_BMT.jpg/1200px-B%E1%BA%A3o_t%C3%A0ng_Th%E1%BA%BF_gi%E1%BB%9Bi_C%C3%A0_ph%C3%AA_BMT.jpg',
        'chùa khải đoan' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/3/36/Ch%C3%B9a_Kh%E1%BA%A3i_%C4%90oan_-_Bu%C3%B4n_Ma_Thu%E1%BB%99t.jpg/1200px-Ch%C3%B9a_Kh%E1%BA%A3i_%C4%90oan_-_Bu%C3%B4n_Ma_Thu%E1%BB%99t.jpg',
        'bảo tàng đắk lắk' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/6/64/B%E1%BA%A3o_t%C3%A0ng_%C4%90%E1%BA%AFk_L%E1%BA%AFk.jpg/1200px-B%E1%BA%A3o_t%C3%A0ng_%C4%90%E1%BA%AFk_L%E1%BA%AFk.jpg',
        'buôn đôn' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/b/b3/C%E1%BA%A7u_treo_Bu%C3%B4n_%C4%90%C3%B4n.jpg/1200px-C%E1%BA%A7u_treo_Bu%C3%B4n_%C4%90%C3%B4n.jpg',
        'vườn quốc gia yok đôn' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/8/87/Yok_Don_National_Park.jpg/1200px-Yok_Don_National_Park.jpg',
        'hòn yến' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/e/e4/H%C3%B2n_Y%E1%BA%BFn_Ph%C3%BA_Y%C3%AAn.jpg/1200px-H%C3%B2n_Y%E1%BA%BFn_Ph%C3%BA_Y%C3%AAn.jpg',
        'vịnh vũng rô' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/7/7b/V%E1%BB%8Bnh_V%C5%A9ng_R%C3%B4.jpg/1200px-V%E1%BB%8Bnh_V%C5%A9ng_R%C3%B4.jpg'
    ];

    // CỬA HÀNG / KHO HÌNH ẢNH ẨM THỰC THỰC TẾ VIỆT NAM
    $realFoodImages = [
        'cà phê' => 'https://images.pexels.com/photos/302899/pexels-photo-302899.jpeg?auto=compress&cs=tinysrgb&w=1200',
        'bún' => 'https://images.pexels.com/photos/1907228/pexels-photo-1907228.jpeg?auto=compress&cs=tinysrgb&w=1200',
        'gà' => 'https://images.pexels.com/photos/2338407/pexels-photo-2338407.jpeg?auto=compress&cs=tinysrgb&w=1200',
        'mắt cá' => 'https://images.pexels.com/photos/262959/pexels-photo-262959.jpeg?auto=compress&cs=tinysrgb&w=1200',
        'bánh hỏi' => 'https://images.unsplash.com/photo-1555126634-323283e090fa?auto=format&fit=crop&w=1200&q=80',
        'cơm gà' => 'https://images.pexels.com/photos/1624487/pexels-photo-1624487.jpeg?auto=compress&cs=tinysrgb&w=1200',
        'lẩu' => 'https://images.pexels.com/photos/674574/pexels-photo-674574.jpeg?auto=compress&cs=tinysrgb&w=1200'
    ];

    // 1. CẬP NHẬT DESTINATIONS
    $destinations = $db->query("SELECT id, name FROM destinations")->fetchAll(PDO::FETCH_ASSOC);
    $destCount = 0;

    foreach ($destinations as $d) {
        $nameLower = mb_strtolower($d['name']);
        $matchedImg = null;

        foreach ($realDestinationImages as $key => $imgUrl) {
            if (strpos($nameLower, $key) !== false) {
                $matchedImg = $imgUrl;
                break;
            }
        }

        if ($matchedImg) {
            $stmt = $db->prepare("UPDATE destinations SET image_url = ? WHERE id = ?");
            $stmt->execute([$matchedImg, $d['id']]);
            $destCount++;
        }
    }
    echo "✓ Đã cập nhật ảnh Wikimedia thực tế cho {$destCount} Điểm Đến.\n";

    // 2. CẬP NHẬT EVENTS
    $events = $db->query("SELECT id, title FROM events")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($events as $ev) {
        $titleLower = mb_strtolower($ev['title']);
        $img = 'https://upload.wikimedia.org/wikipedia/commons/thumb/d/d1/B%E1%BA%A3o_t%C3%A0ng_Th%E1%BA%BF_gi%E1%BB%9Bi_C%C3%A0_ph%C3%AA_BMT.jpg/1200px-B%E1%BA%A3o_t%C3%A0ng_Th%E1%BA%BF_gi%E1%BB%9Bi_C%C3%A0_ph%C3%AA_BMT.jpg';
        if (strpos($titleLower, 'sầu riêng') !== false) {
            $img = 'https://images.pexels.com/photos/102104/pexels-photo-102104.jpeg?auto=compress&cs=tinysrgb&w=1200';
        } elseif (strpos($titleLower, 'voi') !== false) {
            $img = 'https://upload.wikimedia.org/wikipedia/commons/thumb/b/b3/C%E1%BA%A7u_treo_Bu%C3%B4n_%C4%90%C3%B4n.jpg/1200px-C%E1%BA%A7u_treo_Bu%C3%B4n_%C4%90%C3%B4n.jpg';
        } elseif (strpos($titleLower, 'cồng chiêng') !== false) {
            $img = 'https://upload.wikimedia.org/wikipedia/commons/thumb/6/64/B%E1%BA%A3o_t%C3%A0ng_%C4%90%E1%BA%AFk_L%E1%BA%AFk.jpg/1200px-B%E1%BA%A3o_t%C3%A0ng_%C4%90%E1%BA%AFk_L%E1%BA%AFk.jpg';
        }
        $db->prepare("UPDATE events SET image_url = ? WHERE id = ?")->execute([$img, $ev['id']]);
    }
    echo "✓ Đã cập nhật ảnh thực tế cho các Lễ Hội & Sự Kiện.\n";

    echo "\n=========================================================\n";
    echo "  HOÀN THÀNH CẬP NHẬT HÌNH ẢNH THỰC TẾ BẢN ĐỊA  \n";
    echo "=========================================================\n";

} catch (Exception $e) {
    echo "Lỗi: " . $e->getMessage() . "\n";
}
