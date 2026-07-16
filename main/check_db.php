<?php
require 'includes/functions.php';
$db = getDB();
$r = $db->query("SELECT name, image_url FROM destinations WHERE slug LIKE '%phu-yen%' OR slug LIKE '%vinh-vung-ro%' OR slug LIKE '%bai-mon%'")->fetchAll();
print_r($r);
