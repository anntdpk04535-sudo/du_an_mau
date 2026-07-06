<?php
// Lấy contact mới nhất của user hiện tại (đã đăng nhập hoặc theo session localStorage)
require_once __DIR__ . '/../includes/functions.php';
header("Content-Type: application/json; charset=utf-8");

$db   = getDB();
$user = currentUser();

$contactId = (int)($_GET["contact_id"] ?? 0);

try {
    if ($contactId > 0) {
        // Lấy theo contact_id cụ thể (guest hoặc user)
        $stmt = $db->prepare(
            "SELECT c.id, c.guest_name, c.guest_email, c.subject, c.message, c.status, c.created_at,
                    u.full_name as user_full_name
             FROM contacts c LEFT JOIN users u ON c.user_id = u.id
             WHERE c.id = ?
             LIMIT 1"
        );
        $stmt->execute([$contactId]);
    } elseif ($user) {
        // Lấy contact mới nhất của user đã đăng nhập
        $stmt = $db->prepare(
            "SELECT c.id, c.guest_name, c.guest_email, c.subject, c.message, c.status, c.created_at,
                    u.full_name as user_full_name
             FROM contacts c LEFT JOIN users u ON c.user_id = u.id
             WHERE c.user_id = ?
             ORDER BY c.created_at DESC LIMIT 1"
        );
        $stmt->execute([$user["id"]]);
    } else {
        echo json_encode(["success" => false, "reason" => "no_session"]);
        exit;
    }

    $contact = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$contact) {
        echo json_encode(["success" => false, "reason" => "not_found"]);
        exit;
    }

    // Lấy replies
    $rStmt = $db->prepare(
        "SELECT r.id, r.reply_text, r.created_at, u.full_name as admin_name
         FROM contact_replies r JOIN users u ON r.admin_id = u.id
         WHERE r.contact_id = ? ORDER BY r.created_at ASC"
    );
    $rStmt->execute([$contact["id"]]);
    $replies = $rStmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success"  => true,
        "contact"  => $contact,
        "replies"  => $replies,
        "last_id"  => $replies ? (int)max(array_column($replies, "id")) : 0
    ]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
