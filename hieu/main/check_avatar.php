<?php
require 'c:/xampp/htdocs/travel_daklak/hieu/main/config/db.php';
$db = getDB();
$u = $db->query('SELECT id, full_name, avatar FROM users ORDER BY id DESC LIMIT 1')->fetch();
var_dump($u);
