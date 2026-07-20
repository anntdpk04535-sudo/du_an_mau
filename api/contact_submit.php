<?php
require_once __DIR__ . "/../includes/functions.php";
header("Content-Type: application/json; charset=utf-8");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["success" => false, "message" => "Method not allowed"]); exit;
}

$input = json_decode(file_get_contents("php://input"), true) ?: $_POST;
$subject = trim($input["subject"] ?? "");
$message = trim($input["message"] ?? "");
if (!$message) { echo json_encode(["success" => false, "message" => "Vui lòng nhập nội dung."]); exit; }

$user = currentUser();
$userId   = $user["id"] ?? null;
$guestName  = $userId ? null : trim($input["name"] ?? "");
$guestEmail = $userId ? null : trim($input["email"] ?? "");
if (!$userId && !$guestEmail) { echo json_encode(["success" => false, "message" => "Vui lòng nhập email."]); exit; }

try {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO contacts (user_id, guest_name, guest_email, subject, message) VALUES (?,?,?,?,?)");
    $stmt->execute([$userId, $guestName, $guestEmail, $subject, $message]);
    $id = (int)$db->lastInsertId();
    echo json_encode(["success" => true, "contact_id" => $id]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Lỗi server: " . $e->getMessage()]);
}
