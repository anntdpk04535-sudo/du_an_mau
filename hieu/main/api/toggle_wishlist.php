<?php
require_once __DIR__ . '/../includes/functions.php';
header('Content-Type: application/json');

$user = currentUser();
if (!$user) {
    echo json_encode(['success' => false, 'error' => 'Vui lòng đăng nhập để lưu điểm đến.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$destId = (int)($_POST['destination_id'] ?? 0);
if ($destId <= 0) {
    echo json_encode(['success' => false, 'error' => 'Điểm đến không hợp lệ.']);
    exit;
}

$db = getDB();
$userId = $user['id'];

// Check if already in wishlist
$stmt = $db->prepare("SELECT id FROM wishlists WHERE user_id = ? AND destination_id = ?");
$stmt->execute([$userId, $destId]);
$exists = $stmt->fetch();

if ($exists) {
    // Remove
    $stmt = $db->prepare("DELETE FROM wishlists WHERE user_id = ? AND destination_id = ?");
    $stmt->execute([$userId, $destId]);
    echo json_encode(['success' => true, 'action' => 'removed']);
} else {
    // Add
    $stmt = $db->prepare("INSERT INTO wishlists (user_id, destination_id) VALUES (?, ?)");
    $stmt->execute([$userId, $destId]);
    echo json_encode(['success' => true, 'action' => 'added']);
}
