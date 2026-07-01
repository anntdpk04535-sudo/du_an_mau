<?php
if (!isset($pageTitle)) $pageTitle = 'Du lịch Đắk Lắk AI';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle) ?></title>
<link rel="stylesheet" href="<?= url('/assets/css/style.css') ?>">
</head>
<body>
<header class="site-header">
  <div class="container header-inner">
    <a href="<?= url('/public/index.php') ?>" class="logo">🌿 Đắk Lắk<span>Travel AI</span></a>
    <nav class="main-nav">
      <a href="<?= url('/public/index.php') ?>">Trang chủ</a>
      <a href="<?= url('/public/destinations.php') ?>">Điểm đến</a>
      <a href="<?= url('/public/itinerary.php') ?>">Lịch trình AI</a>
      <a href="<?= url('/public/chatbot.php') ?>">Chatbot AI</a>
      <a href="<?= url('/public/about.php') ?>">Giới thiệu</a>
      <a href="<?= url('/public/contact.php') ?>">Liên hệ</a>
    </nav>
    <div class="auth-area">
      <?php $__u = currentUser(); ?>
      <?php if ($__u): ?>
        <span class="auth-greeting">👋 Xin chào, <strong><?= e($__u['full_name']) ?></strong></span>
        <?php if ($__u['role'] === 'admin'): ?>
          <a href="<?= url('/admin/destinations.php') ?>" class="btn secondary">Quản trị</a>
          <a href="<?= url('/admin/contacts.php') ?>" class="btn secondary" style="position:relative;">
            📬 Liên hệ
          </a>
        <?php endif; ?>
        <a href="<?= url('/public/logout.php') ?>" class="btn secondary">Đăng xuất</a>
      <?php else: ?>
        <a href="<?= url('/public/login.php') ?>" class="btn secondary">Đăng nhập</a>
        <a href="<?= url('/public/register.php') ?>" class="btn">Đăng ký</a>
      <?php endif; ?>
    </div>
  </div>
</header>
<main class="container main-content">
