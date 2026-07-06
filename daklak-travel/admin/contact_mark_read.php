<?php
require_once __DIR__ . '/../includes/functions.php';
header("Content-Type: application/json; charset=utf-8");
$user = currentUser();
if (!$user || $user["role"] !== "admin") { echo json_encode(["success"=>false]); exit; }
$input = json_decode(file_get_contents("php://input"), true) ?: [];
$id = (int)($input["contact_id"] ?? 0);
if (!$id) { echo json_encode(["success"=>false]); exit; }
$db = getDB();
$db->prepare("UPDATE contacts SET status='read' WHERE id=? AND status='new'")->execute([$id]);
echo json_encode(["success"=>true]);
