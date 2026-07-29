<?php
require_once __DIR__ . '/../includes/functions.php';

$lang = $_GET['lang'] ?? 'vi';
if (in_array($lang, ['vi', 'en'])) {
    $_SESSION['lang'] = $lang;
}

$referer = $_GET['return_to'] ?? $_SERVER['HTTP_REFERER'] ?? url('/public/index.php');
header("Location: $referer");
exit;