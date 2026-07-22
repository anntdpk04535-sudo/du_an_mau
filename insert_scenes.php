<?php
require 'config/env.php';
require 'config/db.php';
$pdo = getDB();

$stmt = $pdo->query("SELECT id, name FROM destinations WHERE id >= 9 AND id <= 16");
$destinations = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pano_url = "http://localhost/travel_daklak/assets/images/360/lak_lake_360.png";

foreach ($destinations as $dest) {
    $id = $dest['id'];
    $name = $dest['name'];
    
    // Check if it already has a scene
    $check = $pdo->prepare("SELECT id FROM virtual_tour_scenes WHERE destination_id = ?");
    $check->execute([$id]);
    if ($check->rowCount() == 0) {
        $insert = $pdo->prepare("
            INSERT INTO virtual_tour_scenes 
            (destination_id, scene_key, title, panorama_url, thumbnail_url, pitch, yaw, hfov, sort_order, is_default)
            VALUES (?, ?, ?, ?, ?, 0, 0, 110, 1, 1)
        ");
        $insert->execute([
            $id,
            'scene_' . $id . '_1',
            $name . ' - Toàn cảnh',
            $pano_url,
            $pano_url
        ]);
        echo "Inserted scene for destination ID $id ($name)\n";
    } else {
        echo "Destination ID $id ($name) already has scenes.\n";
    }
}
