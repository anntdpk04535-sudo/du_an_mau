<?php
require 'config/db.php';
$db = getDB();
$stmt = $db->query('SELECT content, image_url FROM checkins ORDER BY id DESC LIMIT 5');
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
