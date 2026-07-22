<?php
require 'config/env.php';
require 'config/db.php';
$db = new PDO("mysql:host=localhost;dbname=travel_daklak;charset=utf8", "root", "");
$stmt = $db->query("SELECT id, full_name, avatar FROM users WHERE avatar IS NOT NULL");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
