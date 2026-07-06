<?php
require_once __DIR__ . '/../includes/functions.php';
header("Content-Type: application/json; charset=utf-8");
$user = currentUser();
if (!$user || $user["role"] !== "admin") { echo json_encode(["success"=>false]); exit; }
$id = (int)($_GET["id"] ?? 0);
if (!$id) { echo json_encode(["success"=>false]); exit; }
$db = getDB();
$stmt = $db->prepare("SELECT c.*, u.full_name as user_full_name, u.email as user_email FROM contacts c LEFT JOIN users u ON c.user_id = u.id WHERE c.id = ?");
$stmt->execute([$id]);
$contact = $stmt->fetch();
echo json_encode(["success" => (bool)$contact, "contact" => $contact]);
