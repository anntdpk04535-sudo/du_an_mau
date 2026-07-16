<?php
require_once __DIR__ . '/includes/functions.php';

try {
    $db = getDB();
    
    // Add google_id
    $db->exec("ALTER TABLE users ADD COLUMN google_id VARCHAR(100) UNIQUE DEFAULT NULL AFTER email");
    echo "Added google_id column.\n";
    
    // Add facebook_id
    $db->exec("ALTER TABLE users ADD COLUMN facebook_id VARCHAR(100) UNIQUE DEFAULT NULL AFTER google_id");
    echo "Added facebook_id column.\n";
    
    // Since OAuth users don't have password, we should allow password_hash to be NULL
    $db->exec("ALTER TABLE users MODIFY password_hash VARCHAR(255) NULL");
    echo "Modified password_hash to allow NULL.\n";

    echo "Database updated successfully.\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
