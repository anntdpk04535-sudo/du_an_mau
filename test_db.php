<?php
require 'includes/db.php';
$stmt = $db->query('SELECT id, full_name, avatar FROM users WHERE avatar IS NOT NULL');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
