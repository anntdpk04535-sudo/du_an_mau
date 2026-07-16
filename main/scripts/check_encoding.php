<?php
require_once __DIR__ . '/includes/functions.php';
$db = getDB();

// Check connection charset
echo "Connection charset: ";
$r = $db->query("SHOW VARIABLES LIKE 'character_set_connection'")->fetch();
echo $r['Value'] . "\n";

echo "Client charset: ";
$r = $db->query("SHOW VARIABLES LIKE 'character_set_client'")->fetch();
echo $r['Value'] . "\n";

echo "Results charset: ";
$r = $db->query("SHOW VARIABLES LIKE 'character_set_results'")->fetch();
echo $r['Value'] . "\n";

// Check actual data
$r = $db->query("SELECT name, HEX(name) as hex_name FROM destinations WHERE id=1")->fetch();
echo "\nName raw: " . $r['name'] . "\n";
echo "Hex: " . $r['hex_name'] . "\n";
echo "mb_detect: " . mb_detect_encoding($r['name'], 'UTF-8,ISO-8859-1,Windows-1252') . "\n";
echo "strlen: " . strlen($r['name']) . "\n";
echo "mb_strlen: " . mb_strlen($r['name'], 'UTF-8') . "\n";
