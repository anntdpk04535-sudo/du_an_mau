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
    echo json_encode(['success' => false, 'error' => 'Bạn cần đăng nhập.']);
    exit;
}

$id      = (int) ($_POST['id'] ?? 0);
$rating  = (int) ($_POST['rating'] ?? 0);
$comment = trim($_POST['comment'] ?? '');

if (!$id) {
    echo json_encode(['success' => false, 'error' => 'Thiếu ID đánh giá.']);
    exit;
}
if ($rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'error' => 'Vui lòng chọn số sao từ 1 đến 5.']);
    exit;
}
if (mb_strlen($comment) > 1000) {
    echo json_encode(['success' => false, 'error' => 'Nhận xét không được vượt quá 1000 ký tự.']);
    exit;
}

try {
    $db = getDB();

    $stmt = $db->prepare("SELECT * FROM reviews WHERE id = ?");
    $stmt->execute([$id]);
    $review = $stmt->fetch();

    if (!$review) {
        echo json_encode(['success' => false, 'error' => 'Không tìm thấy đánh giá.']);
        exit;
    }

    // Chỉ chủ đánh giá mới được sửa (admin không cần sửa - chỉ xóa)
    if ($review['user_id'] != $user['id']) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Bạn không có quyền sửa đánh giá này.']);
        exit;
    }

    $upd = $db->prepare("UPDATE reviews SET rating = ?, comment = ? WHERE id = ?");
    $upd->execute([$rating, $comment ?: null, $id]);

    // Cập nhật lại rating trung bình điểm đến (nếu có)
    $destinationId = $review['destination_id'];
    if ($destinationId) {
        $avg = $db->prepare("SELECT AVG(rating) FROM reviews WHERE destination_id = ?");
        $avg->execute([$destinationId]);
        $newRating = round((float) $avg->fetchColumn(), 1);
        $upd2 = $db->prepare("UPDATE destinations SET rating = ? WHERE id = ?");
        $upd2->execute([$newRating, $destinationId]);
    }

    echo json_encode(['success' => true, 'message' => 'Đã cập nhật đánh giá thành công.']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Lỗi server: ' . $e->getMessage()]);
}
