<?php
require 'config/env.php';
require 'config/db.php';
$pdo = getDB();

$stmt = $pdo->query("SELECT id, title, description, title_en, description_en FROM virtual_tour_scenes");
$scenes = $stmt->fetchAll(PDO::FETCH_ASSOC);

print_r($scenes);
