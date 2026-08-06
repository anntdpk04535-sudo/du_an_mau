<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
$db = getDB();
$statement = $db->prepare("INSERT INTO destination_images(destination_id,image_url,alt_text,is_primary,sort_order)
    SELECT d.id,d.image_url,d.name,1,0 FROM destinations d
    WHERE d.image_url IS NOT NULL AND d.image_url <> ''
      AND NOT EXISTS (SELECT 1 FROM destination_images i WHERE i.destination_id=d.id)");
$statement->execute();
echo json_encode(['gallery_rows_added' => $statement->rowCount()], JSON_UNESCAPED_UNICODE) . PHP_EOL;
