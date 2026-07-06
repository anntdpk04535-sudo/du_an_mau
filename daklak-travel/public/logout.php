<?php
require_once __DIR__ . '/../includes/functions.php';
unset($_SESSION['user']);
session_destroy();
header('Location: ' . url('/public/index.php'));
exit;
