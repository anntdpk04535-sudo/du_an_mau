<?php
require_once __DIR__ . '/../includes/functions.php';
header("Content-Type: application/json; charset=utf-8");

$user = currentUser();
if (!$user || $user["role"] !== "admin") {
    echo json_encode(["success" => false, "message" => "Unauthorized"]); exit;
}

$input = json_decode(file_get_contents("php://input"), true) ?: [];
$contactId = (int)($input["contact_id"] ?? 0);
$replyText = trim($input["reply"] ?? "");
if (!$contactId || !$replyText) {
    echo json_encode(["success" => false, "message" => "Thieu du lieu"]); exit;
}

try {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO contact_replies (contact_id, admin_id, reply_text) VALUES (?,?,?)");
    $stmt->execute([$contactId, $user["id"], $replyText]);
    $db->prepare("UPDATE contacts SET status='replied' WHERE id=?")->execute([$contactId]);
    echo json_encode(["success" => true, "reply_id" => (int)$db->lastInsertId()]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
