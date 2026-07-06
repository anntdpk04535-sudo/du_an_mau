<?php
// Long-polling: Admin lang nghe tin nhan lien he moi
require_once __DIR__ . '/../includes/functions.php';
header("Content-Type: application/json; charset=utf-8");

$user = currentUser();
if (!$user || $user["role"] !== "admin") {
    echo json_encode(["success" => false, "message" => "Unauthorized"]); exit;
}

$lastId  = (int)($_GET["last_id"] ?? 0);
$timeout = 20;
$start   = time();
$db      = getDB();

// Neu last_id = 0, tra ve tat ca (de khoi tao)
if ($lastId === 0) {
    $stmt = $db->query("SELECT c.*, u.full_name as user_full_name, u.email as user_email FROM contacts c LEFT JOIN users u ON c.user_id = u.id ORDER BY c.created_at DESC LIMIT 50");
    echo json_encode(["success" => true, "contacts" => $stmt->fetchAll()]);
    exit;
}

// Long poll cho tin moi hon lastId
while (true) {
    $stmt = $db->prepare("SELECT c.*, u.full_name as user_full_name, u.email as user_email FROM contacts c LEFT JOIN users u ON c.user_id = u.id WHERE c.id > ? ORDER BY c.created_at ASC");
    $stmt->execute([$lastId]);
    $rows = $stmt->fetchAll();
    if ($rows) {
        echo json_encode(["success" => true, "contacts" => $rows]);
        exit;
    }
    if (time() - $start >= $timeout) {
        echo json_encode(["success" => true, "contacts" => []]);
        exit;
    }
    sleep(2);
}
