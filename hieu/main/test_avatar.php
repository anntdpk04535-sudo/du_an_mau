<?php
$url = 'https://platform-lookaside.fbsbx.com/platform/profilepic/?asid=994464123365378&height=200&width=200&ext=1786514124&hash=AftEStFqTX_hUT4BR0ftCSo9';

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_NOBODY, true);
$res = curl_exec($ch);
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Status: $status\n";
