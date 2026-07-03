<?php
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$user = currentUser();
if (!$user) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Bạn cần đăng nhập để gửi đánh giá.']);
    exit;
}

$rating        = (int) ($_POST['rating'] ?? 0);
$comment       = trim($_POST['comment'] ?? '');
$destinationId = !empty($_POST['destination_id']) ? (int) $_POST['destination_id'] : null;

if ($rating < 1 || $rating > 5) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Vui lòng chọn số sao từ 1 đến 5.']);
    exit;
}

if (mb_strlen($comment) > 1000) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Nhận xét không được vượt quá 1000 ký tự.']);
    exit;
}

try {
    $db = getDB();

    $stmt = $db->prepare("INSERT INTO reviews (user_id, destination_id, rating, comment) VALUES (?, ?, ?, ?)");
    $stmt->execute([$user['id'], $destinationId, $rating, $comment ?: null]);

    // Nếu là đánh giá điểm đến → cập nhật lại rating trung bình
    if ($destinationId) {
        $avg = $db->prepare("SELECT AVG(rating) FROM reviews WHERE destination_id = ?");
        $avg->execute([$destinationId]);
        $newRating = round((float) $avg->fetchColumn(), 1);
        $upd = $db->prepare("UPDATE destinations SET rating = ? WHERE id = ?");
        $upd->execute([$newRating, $destinationId]);
    }

    echo json_encode(['success' => true, 'message' => 'Cảm ơn bạn đã đánh giá!']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Lỗi server: ' . $e->getMessage()]);
}
