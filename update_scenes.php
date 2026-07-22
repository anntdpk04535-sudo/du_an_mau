<?php
require 'config/env.php';
require 'config/db.php';
$pdo = getDB();

$updates = [
    9 => 'ganh_da_dia_360.png',
    10 => 'o_loan_360.png',
    11 => 'vung_ro_360.png',
    12 => 'mui_dien_360.png',
    13 => 'nghinh_phong_360.png',
    14 => 'hon_yen_360.png',
    15 => 'thuy_tien_360.png',
    16 => 'da_voi_me_360.png'
];

$base_url = "http://localhost/travel_daklak/assets/images/360/";

$stmt = $pdo->prepare("UPDATE virtual_tour_scenes SET panorama_url = ?, thumbnail_url = ? WHERE destination_id = ?");

foreach ($updates as $id => $filename) {
    $url = $base_url . $filename;
    $stmt->execute([$url, $url, $id]);
    echo "Updated ID $id with $filename\n";
}
