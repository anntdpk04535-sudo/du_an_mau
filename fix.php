<?php
require 'config/db.php';
$db = getDB();
$db->query("UPDATE checkins SET image_url = REPLACE(image_url, '/main/assets/', '/assets/')");
echo "Done";
