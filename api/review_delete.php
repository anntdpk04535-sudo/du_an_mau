<?php
// Đảm bảo session đã được khởi động trước khi require functions
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/content_helpers.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$user = currentUser();
if (!$user) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Bạn cần đăng nhập.']);
    exit;
}

$input  = json_decode(file_get_contents('php://input'), true);
$id     = (int) ($input['id'] ?? $_POST['id'] ?? 0);
$reason = trim($input['reason'] ?? $_POST['reason'] ?? '');

if (!$id) {
    echo json_encode(['success' => false, 'error' => 'Thiếu ID đánh giá.']);
    exit;
}

try {
    $db = getDB();

    // Tự tạo bảng log nếu chưa có
    $db->exec("
        CREATE TABLE IF NOT EXISTS review_deletion_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            review_id INT NOT NULL,
            reviewer_user_id INT NULL,
            reviewer_name VARCHAR(255) NULL,
            rating TINYINT NULL,
            comment TEXT NULL,
            deleted_by INT NOT NULL,
            deleted_by_name VARCHAR(255) NULL,
            reason TEXT NULL,
            deleted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_deleted_by (deleted_by),
            INDEX idx_deleted_at (deleted_at)
        ) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
    ");

    // Lấy thông tin đánh giá trước
    $stmt = $db->prepare("
        SELECT r.*, u.full_name AS reviewer_name
        FROM reviews r
        LEFT JOIN users u ON r.user_id = u.id
        WHERE r.id = ?
    ");
    $stmt->execute([$id]);
    $review = $stmt->fetch();

    if (!$review) {
        echo json_encode(['success' => false, 'error' => 'Không tìm thấy đánh giá.']);
        exit;
    }

    $isAdmin = ($user['role'] === 'admin');
    $isOwner = ($review['user_id'] == $user['id']);

    // Kiểm tra quyền: phải là admin HOẶC chủ đánh giá
    if (!$isAdmin && !$isOwner) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Bạn không có quyền xóa đánh giá này.']);
        exit;
    }

    // Admin xóa đánh giá của người khác thì phải nhập lý do
    if ($isAdmin && !$isOwner && $reason === '') {
        echo json_encode(['success' => false, 'error' => 'Vui lòng nhập lý do xóa.']);
        exit;
    }
    if ($isAdmin && !$isOwner && mb_strlen($reason) < 10) {
        echo json_encode(['success' => false, 'error' => 'Lý do xóa phải có ít nhất 10 ký tự.']);
        exit;
    }

    $destinationId = $review['destination_id'];

    // Ghi log mỗi khi admin xóa đánh giá (của người khác)
    if ($isAdmin && !$isOwner) {
        $log = $db->prepare("
            INSERT INTO review_deletion_logs
                (review_id, reviewer_user_id, reviewer_name, rating, comment, deleted_by, deleted_by_name, reason)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $log->execute([
            $review['id'],
            $review['user_id'],
            $review['reviewer_name'],
            $review['rating'],
            $review['comment'],
            $user['id'],
            $user['full_name'],
            $reason,
        ]);
    }

    if (tableExists($db, 'review_images')) {
        $imgs = $db->prepare('SELECT image_url FROM review_images WHERE review_id=?');
        $imgs->execute([$id]);
        foreach ($imgs->fetchAll(PDO::FETCH_COLUMN) as $url) {
            $path = parse_url((string)$url, PHP_URL_PATH);
            if ($path && str_contains($path, '/assets/images/uploads/')) @unlink(__DIR__ . '/..' . $path);
        }
    }
    // Xóa đánh giá
    $db->prepare("DELETE FROM reviews WHERE id = ?")->execute([$id]);

    // Cập nhật lại rating trung bình điểm đến (nếu có)
    if ($destinationId) {
        $avg = $db->prepare("SELECT AVG(rating) FROM reviews WHERE destination_id = ?");
        $avg->execute([$destinationId]);
        $newRating = round((float) $avg->fetchColumn(), 1);
        $db->prepare("UPDATE destinations SET rating = ? WHERE id = ?")
           ->execute([$newRating ?: 0, $destinationId]);
    }

    echo json_encode(['success' => true, 'message' => 'Đã xóa đánh giá thành công.']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Lỗi server: ' . $e->getMessage()]);
}
