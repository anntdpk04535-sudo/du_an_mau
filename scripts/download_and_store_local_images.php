<?php
require_once __DIR__ . '/../includes/functions.php';

try {
    $db = getDB();
    $uploadDir = __DIR__ . '/../assets/images/uploads';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    echo "=========================================================\n";
    echo "  TẢI & LƯU HÌNH ẢNH CỤ THỂ VỀ THƯ MỤC CỤC BỘ (UPLOADS) \n";
    echo "=========================================================\n\n";

    function downloadImageToLocal(string $url, string $filename, string $uploadDir): ?string {
        $ext = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'jpg';
        if (!in_array(strtolower($ext), ['jpg', 'jpeg', 'png', 'webp'])) {
            $ext = 'jpg';
        }
        $targetFile = $uploadDir . '/' . $filename . '.' . $ext;
        $relativeUrl = url('/assets/images/uploads/' . $filename . '.' . $ext);

        if (file_exists($targetFile) && filesize($targetFile) > 1000) {
            return $relativeUrl;
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $data = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($httpCode === 200 && !empty($data) && strlen($data) > 2000) {
            file_put_contents($targetFile, $data);
            return $relativeUrl;
        }
        return null;
    }

    // 1. TẢI VÀ GÁN ẢNH CỤC BỘ CHO TẤT CẢ ĐIỂM ĐẾN NỔI BẬT
    $dests = $db->query("SELECT id, name, image_url FROM destinations WHERE image_url LIKE 'http%'")->fetchAll(PDO::FETCH_ASSOC);
    $destCount = 0;
    foreach ($dests as $d) {
        $filename = 'dest_' . $d['id'] . '_' . preg_replace('/[^a-z0-9]+/i', '_', $d['name']);
        $localPath = downloadImageToLocal($d['image_url'], $filename, $uploadDir);
        if ($localPath) {
            $db->prepare("UPDATE destinations SET image_url = ? WHERE id = ?")->execute([$localPath, $d['id']]);
            $destCount++;
        }
    }
    echo "✓ Đã tải và lưu thành công {$destCount} ảnh Điểm Đến vào assets/images/uploads/\n";

    // 2. TẢI VÀ GÁN ẢNH CỤC BỘ CHO FOODS
    $foods = $db->query("SELECT id, name, image_url FROM foods WHERE image_url LIKE 'http%'")->fetchAll(PDO::FETCH_ASSOC);
    $foodCount = 0;
    foreach ($foods as $f) {
        $filename = 'food_' . $f['id'] . '_' . preg_replace('/[^a-z0-9]+/i', '_', substr($f['name'], 0, 30));
        $localPath = downloadImageToLocal($f['image_url'], $filename, $uploadDir);
        if ($localPath) {
            $db->prepare("UPDATE foods SET image_url = ? WHERE id = ?")->execute([$localPath, $f['id']]);
            $foodCount++;
        }
    }
    echo "✓ Đã tải và lưu thành công {$foodCount} ảnh Ẩm Thực vào assets/images/uploads/\n";

    // 3. TẢI VÀ GÁN ẢNH CỤC BỘ CHO ACCOMMODATIONS
    $stays = $db->query("SELECT id, name, image_url FROM accommodations WHERE image_url LIKE 'http%'")->fetchAll(PDO::FETCH_ASSOC);
    $stayCount = 0;
    foreach ($stays as $s) {
        $filename = 'stay_' . $s['id'] . '_' . preg_replace('/[^a-z0-9]+/i', '_', substr($s['name'], 0, 30));
        $localPath = downloadImageToLocal($s['image_url'], $filename, $uploadDir);
        if ($localPath) {
            $db->prepare("UPDATE accommodations SET image_url = ? WHERE id = ?")->execute([$localPath, $s['id']]);
            $stayCount++;
        }
    }
    echo "✓ Đã tải và lưu thành công {$stayCount} ảnh Nơi Lưu Trú vào assets/images/uploads/\n";

    echo "\n=========================================================\n";
    echo "  HOÀN THÀNH TẢI & TÍCH HỢP HÌNH ẢNH CỤC BỘ (LOCAL ASSETS)\n";
    echo "=========================================================\n";

} catch (Exception $e) {
    echo "Lỗi: " . $e->getMessage() . "\n";
}
