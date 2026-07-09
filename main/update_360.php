<?php
require_once __DIR__ . '/config/env.php';
require_once __DIR__ . '/config/db.php';

try {
    $db = getDB();

    // Add image_360_url column
    $result = $db->query("SHOW COLUMNS FROM `destinations` LIKE 'image_360_url'")->fetch();
    if (!$result) {
        $db->exec("ALTER TABLE `destinations` ADD `image_360_url` VARCHAR(500) NULL DEFAULT NULL AFTER `image_url`");
        echo "Added image_360_url column.\n";
    } else {
        echo "image_360_url column already exists.\n";
    }

    // Add some sample equirectangular 360 images
    // I will use some free sample equirectangular images for demonstration.
    $sample360_1 = 'https://pannellum.org/images/alma.jpg'; // Sample 1
    $sample360_2 = 'https://pannellum.org/images/cerro-toco-0.jpg'; // Sample 2
    $sample360_3 = 'https://pannellum.org/images/bma-0.jpg'; // Sample 3

    $db->exec("UPDATE destinations SET image_360_url = '$sample360_1' WHERE id = 1"); // Hồ Lắk
    $db->exec("UPDATE destinations SET image_360_url = '$sample360_2' WHERE id = 2"); // Thác Dray Nur
    $db->exec("UPDATE destinations SET image_360_url = '$sample360_3' WHERE id = 6"); // Cà phê Buôn Ma Thuột
    
    echo "Updated sample 360 images.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
