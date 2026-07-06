<?php
/**
 * Migration: Bổ sung cột hazard_type & safety_instructions cho bảng destinations.
 * Chạy 1 lần duy nhất:  php migrate_safety.php  (hoặc mở trên trình duyệt).
 */
require_once __DIR__ . '/../config/db.php';

$db = getDB();

echo "=== Migration: Safety columns for destinations ===\n";

// 1. Thêm cột hazard_type
try {
    $db->exec("ALTER TABLE destinations ADD COLUMN hazard_type ENUM('none','waterfall','forest','river','mountain') DEFAULT 'none'");
    echo "✅ Đã thêm cột hazard_type\n";
} catch (PDOException $e) {
    if (str_contains($e->getMessage(), 'Duplicate column')) {
        echo "⏭️ Cột hazard_type đã tồn tại, bỏ qua.\n";
    } else {
        echo "❌ Lỗi: " . $e->getMessage() . "\n";
    }
}

// 2. Thêm cột safety_instructions
try {
    $db->exec("ALTER TABLE destinations ADD COLUMN safety_instructions TEXT DEFAULT NULL");
    echo "✅ Đã thêm cột safety_instructions\n";
} catch (PDOException $e) {
    if (str_contains($e->getMessage(), 'Duplicate column')) {
        echo "⏭️ Cột safety_instructions đã tồn tại, bỏ qua.\n";
    } else {
        echo "❌ Lỗi: " . $e->getMessage() . "\n";
    }
}

// 3. Cập nhật dữ liệu rủi ro cho các điểm đến hiện có
$safetyData = [
    'ho-lak' => [
        'hazard_type' => 'river',
        'safety_instructions' => 'Luôn mặc áo phao khi đi thuyền độc mộc. Không bơi xa bờ, đặc biệt vào mùa mưa khi nước hồ dâng cao và có dòng chảy ngầm. Trẻ em phải có người lớn giám sát khi ở gần mép nước.',
    ],
    'thac-dray-nur' => [
        'hazard_type' => 'waterfall',
        'safety_instructions' => 'Đường đá quanh thác rất trơn trượt khi mưa. Tuyệt đối KHÔNG tắm thác khi có lũ hoặc nước đục. Mang giày leo núi có độ bám tốt, không đi dép lê. Giữ khoảng cách an toàn với mép thác ít nhất 3 mét.',
    ],
    'thac-dray-sap' => [
        'hazard_type' => 'waterfall',
        'safety_instructions' => 'Đường xuống chân thác dốc và ướt, dễ trượt ngã. Mang giày chống trơn, bám chặt lan can. Không leo trèo lên các tảng đá lớn gần dòng nước. Chú ý biển báo cấm của Ban quản lý.',
    ],
    'buon-don' => [
        'hazard_type' => 'none',
        'safety_instructions' => 'Khu vực an toàn. Khi vào buôn làng, nên xin phép trước và tôn trọng phong tục địa phương. Không sờ vào các vật thờ cúng trong nhà dài.',
    ],
    'vuon-quoc-gia-yok-don' => [
        'hazard_type' => 'forest',
        'safety_instructions' => 'Luôn đi theo hướng dẫn viên, KHÔNG tự ý đi sâu vào rừng. Mùa khô (tháng 11-4) có nguy cơ cháy rừng cao — không đốt lửa, không vứt tàn thuốc. Mang đủ nước uống (tối thiểu 2 lít/người). Mặc quần áo dài tay tránh côn trùng, rắn rết. Mang theo thuốc chống dị ứng.',
    ],
    'ca-phe-buon-ma-thuot' => [
        'hazard_type' => 'none',
        'safety_instructions' => 'Khu vực an toàn, nằm trong nội thành.',
    ],
    'buon-ako-dhong' => [
        'hazard_type' => 'none',
        'safety_instructions' => 'Khu vực an toàn. Nên đi bộ nhẹ nhàng, tôn trọng không gian sinh hoạt của cộng đồng Ê Đê.',
    ],
    'ho-ea-kao' => [
        'hazard_type' => 'river',
        'safety_instructions' => 'Không bơi một mình, đặc biệt vào buổi chiều tối. Bờ hồ có nhiều đoạn dốc trơn. Cẩn thận khi cắm trại gần mép nước vào mùa mưa — nước có thể dâng nhanh trong đêm.',
    ],
];

$stmt = $db->prepare("UPDATE destinations SET hazard_type = ?, safety_instructions = ? WHERE slug = ?");

foreach ($safetyData as $slug => $data) {
    $stmt->execute([$data['hazard_type'], $data['safety_instructions'], $slug]);
    $affected = $stmt->rowCount();
    echo $affected > 0
        ? "✅ Cập nhật '{$slug}': hazard={$data['hazard_type']}\n"
        : "⏭️ Không tìm thấy '{$slug}', bỏ qua.\n";
}

echo "\n🎉 Migration hoàn tất!\n";
