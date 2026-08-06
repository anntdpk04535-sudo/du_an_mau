<?php
require_once __DIR__ . '/../includes/content_helpers.php';

header('Content-Type: application/json; charset=utf-8');

$destinationId = isset($_GET['destination_id']) ? (int) $_GET['destination_id'] : null;
$limit         = min((int) ($_GET['limit'] ?? 20), 500);
$offset        = max((int) ($_GET['offset'] ?? 0), 0);
$currentUser   = currentUser();
$currentUserId = $currentUser ? (int)$currentUser['id'] : 0;

try {
    $db = getDB();

    if ($destinationId) {
        $stmt = $db->prepare("
            SELECT r.id AS review_id, r.user_id, r.rating, r.comment, r.created_at,
                   u.full_name
            FROM reviews r
            LEFT JOIN users u ON r.user_id = u.id
            WHERE r.destination_id = ?
            ORDER BY r.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$destinationId, $limit, $offset]);

        $countStmt = $db->prepare("SELECT COUNT(*), AVG(rating) FROM reviews WHERE destination_id = ?");
        $countStmt->execute([$destinationId]);
    } else {
        // Đánh giá dịch vụ website tổng thể
        $stmt = $db->prepare("
            SELECT r.id AS review_id, r.user_id, r.rating, r.comment, r.created_at,
                   u.full_name
            FROM reviews r
            LEFT JOIN users u ON r.user_id = u.id
            WHERE r.destination_id IS NULL
            ORDER BY r.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$limit, $offset]);

        $countStmt = $db->prepare("SELECT COUNT(*), AVG(rating) FROM reviews WHERE destination_id IS NULL");
        $countStmt->execute([]);
    }

    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
    [$total, $avgRating] = $countStmt->fetch(PDO::FETCH_NUM);

    // Mask tên người dùng để bảo mật, thêm is_mine
    foreach ($reviews as &$r) {
        $name = $r['full_name'] ?? 'Ẩn danh';
        $parts = explode(' ', $name);
        $last  = array_pop($parts);
        $r['display_name'] = ($parts ? mb_substr(implode(' ', $parts), 0, 1) . '... ' : '') . $last;
        $r['is_mine'] = ($currentUserId > 0 && (int)$r['user_id'] === $currentUserId);
        unset($r['full_name']);
        // Giữ lại user_id và review_id cho frontend
        $r['review_id'] = (int)$r['review_id'];
        $r['user_id']   = (int)$r['user_id'];
        $r['images'] = fetchEntityImages($db, 'review_images', 'review_id', (int)$r['review_id']);
    }

    echo json_encode([
        'success'    => true,
        'reviews'    => $reviews,
        'total'      => (int) $total,
        'avg_rating' => $avgRating ? round((float) $avgRating, 1) : null,
        'is_admin'   => $currentUser && ($currentUser['role'] ?? '') === 'admin',
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
