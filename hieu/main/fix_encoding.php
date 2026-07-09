<?php
/**
 * Fix encoding: Re-read SQL file gốc và update lại dữ liệu
 * File SQL gốc chứa dữ liệu đúng UTF-8, chỉ cần đọc đúng encoding
 */
require_once __DIR__ . '/includes/functions.php';
$db = getDB();

echo "=== Fix: Re-import dữ liệu từ SQL gốc ===\n\n";

// Đọc file SQL gốc với UTF-8
$sqlFile = __DIR__ . '/database/daklak_travel.sql';
$content = file_get_contents($sqlFile);

// Detect encoding of SQL file
$encoding = mb_detect_encoding($content, ['UTF-16LE', 'UTF-16BE', 'UTF-8', 'ASCII'], true);
echo "SQL file encoding: $encoding\n";

// Convert nếu cần
if ($encoding === 'UTF-16LE' || $encoding === 'UTF-16BE') {
    $content = mb_convert_encoding($content, 'UTF-8', $encoding);
    echo "Converted to UTF-8\n";
}

// Extract INSERT INTO destinations VALUES 
if (preg_match('/INSERT INTO `destinations` VALUES (.+?);/s', $content, $m)) {
    echo "\nFound destinations INSERT\n";
    
    // Drop và re-insert: quá phức tạp. Thay vào đó parse từng record
    // Cách đơn giản: chạy trực tiếp SQL statement

    // Trước tiên xóa dữ liệu cũ (giữ lại cấu trúc)
    // CẢNH BÁO: phải tắt FK check
    $db->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    // Backup virtual tour data
    $vtScenes = $db->query("SELECT * FROM virtual_tour_scenes")->fetchAll();
    $vtHotspots = $db->query("SELECT * FROM virtual_tour_hotspots")->fetchAll();
    $vtEnabled = $db->query("SELECT id, virtual_tour_enabled, virtual_tour_type FROM destinations")->fetchAll();
    
    echo "Backed up " . count($vtScenes) . " scenes, " . count($vtHotspots) . " hotspots\n";
    
    // Delete và re-insert destinations
    $db->exec("DELETE FROM destinations");
    
    // Re-execute INSERT statement  
    $insertSql = "INSERT INTO `destinations` VALUES " . $m[1];
    try {
        $db->exec($insertSql);
        echo "✅ Re-imported destinations\n";
    } catch (PDOException $e) {
        echo "❌ Error: " . $e->getMessage() . "\n";
        echo "Trying alternative...\n";
        
        // Nếu lỗi do thiếu cột mới, thêm lại bằng cách bỏ qua cột mới
        // Cột gốc: id, category_id, name, name_en, slug, short_desc, short_desc_en, 
        //           description, description_en, address, image_url, avg_visit_hours,
        //           price_level, rating, latitude, longitude, tags, created_at
        $db->exec("INSERT INTO `destinations` (id, category_id, name, name_en, slug, short_desc, short_desc_en, description, description_en, address, image_url, avg_visit_hours, price_level, rating, latitude, longitude, tags, created_at) VALUES " . $m[1]);
        echo "✅ Re-imported destinations (with column list)\n";
    }
    
    // Restore virtual tour flags
    foreach ($vtEnabled as $vt) {
        if ($vt['virtual_tour_enabled']) {
            $db->prepare("UPDATE destinations SET virtual_tour_enabled=?, virtual_tour_type=? WHERE id=?")->execute([
                $vt['virtual_tour_enabled'], $vt['virtual_tour_type'], $vt['id']
            ]);
        }
    }
    echo "✅ Restored virtual tour flags\n";
    
    $db->exec("SET FOREIGN_KEY_CHECKS = 1");
}

// Fix categories
if (preg_match('/INSERT INTO `categories` VALUES (.+?);/s', $content, $m)) {
    $db->exec("SET FOREIGN_KEY_CHECKS = 0");
    $db->exec("DELETE FROM categories");
    $db->exec("INSERT INTO `categories` VALUES " . $m[1]);
    $db->exec("SET FOREIGN_KEY_CHECKS = 1");
    echo "✅ Re-imported categories\n";
}

// Fix articles  
if (preg_match('/INSERT INTO `articles` VALUES (.+?);/s', $content, $m)) {
    $db->exec("DELETE FROM articles");
    $db->exec("INSERT INTO `articles` VALUES " . $m[1]);
    echo "✅ Re-imported articles\n";
}

// Fix itinerary_items
if (preg_match('/INSERT INTO `itinerary_items` VALUES (.+?);/s', $content, $m)) {
    $db->exec("SET FOREIGN_KEY_CHECKS = 0");
    $db->exec("DELETE FROM itinerary_items");
    $db->exec("INSERT INTO `itinerary_items` VALUES " . $m[1]);
    $db->exec("SET FOREIGN_KEY_CHECKS = 1");
    echo "✅ Re-imported itinerary_items\n";
}

// Fix chat_logs
if (preg_match('/INSERT INTO `chat_logs` VALUES (.+?);/s', $content, $m)) {
    $db->exec("DELETE FROM chat_logs");
    $db->exec("INSERT INTO `chat_logs` VALUES " . $m[1]);
    echo "✅ Re-imported chat_logs\n";
}

// Verify
echo "\n=== Verification ===\n";
$names = $db->query("SELECT id, name FROM destinations ORDER BY id")->fetchAll();
foreach ($names as $n) {
    echo "  {$n['id']}: {$n['name']}\n";
}
$cats = $db->query("SELECT id, name FROM categories ORDER BY id")->fetchAll();
foreach ($cats as $c) {
    echo "  cat {$c['id']}: {$c['name']}\n";
}

echo "\n🎉 Done!\n";
