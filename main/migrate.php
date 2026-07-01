<?php
require_once __DIR__ . '/includes/functions.php';
try {
    $db = getDB();
    
    // Check and add 'address'
    $q = $db->query("SHOW COLUMNS FROM itinerary_items LIKE 'address'");
    if (!$q->fetch()) {
        $db->exec("ALTER TABLE itinerary_items ADD COLUMN address VARCHAR(255) DEFAULT NULL AFTER activity");
        echo "Successfully added 'address' column to 'itinerary_items' table.\n";
    } else {
        echo "Column 'address' already exists in 'itinerary_items' table.\n";
    }

    // Check and add 'transport'
    $q2 = $db->query("SHOW COLUMNS FROM itinerary_items LIKE 'transport'");
    if (!$q2->fetch()) {
        $db->exec("ALTER TABLE itinerary_items ADD COLUMN transport VARCHAR(255) DEFAULT NULL AFTER address");
        echo "Successfully added 'transport' column to 'itinerary_items' table.\n";
    } else {
        echo "Column 'transport' already exists in 'itinerary_items' table.\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
unlink(__FILE__); // Tự động xoá file
