<?php
// User poll xem admin đã trả lời chưa — hỗ trợ last_id để chỉ lấy reply mới
require_once __DIR__ . '/../includes/functions.php';
header("Content-Type: application/json; charset=utf-8");

$contactId  = (int)($_GET["contact_id"] ?? 0);
$lastId     = (int)($_GET["last_id"]    ?? 0);
if (!$contactId) { echo json_encode(["success" => false]); exit; }

try {
    $db = getDB();
    if ($lastId > 0) {
        // Chỉ lấy reply mới hơn last_id
        $stmt = $db->prepare(
            "SELECT r.id, r.reply_text, r.created_at, u.full_name as admin_name
             FROM contact_replies r
             JOIN users u ON r.admin_id = u.id
             WHERE r.contact_id = ? AND r.id > ?
             ORDER BY r.created_at ASC"
        );
        $stmt->execute([$contactId, $lastId]);
    } else {
        // Lấy toàn bộ
        $stmt = $db->prepare(
            "SELECT r.id, r.reply_text, r.created_at, u.full_name as admin_name
             FROM contact_replies r
             JOIN users u ON r.admin_id = u.id
             WHERE r.contact_id = ?
             ORDER BY r.created_at ASC"
        );
        $stmt->execute([$contactId]);
    }
    $replies = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $maxId   = $replies ? max(array_column($replies, "id")) : $lastId;
    echo json_encode(["success" => true, "replies" => $replies, "last_id" => (int)$maxId]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
