<?php
require 'c:/xampp/htdocs/du_an_mau-Hieus/du_an_mau-An/main/config/env.php';
require 'c:/xampp/htdocs/du_an_mau-Hieus/du_an_mau-An/main/config/db.php';
$db = getDB();
$stmt = $db->query('SELECT id, name, image_url FROM destinations');
$dests = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($dests, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
