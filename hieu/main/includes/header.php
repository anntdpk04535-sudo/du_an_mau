<?php
if (!isset($pageTitle)) $pageTitle = 'Du lịch Đắk Lắk AI';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle) ?></title>
<link rel="stylesheet" href="<?= url('/assets/css/style.css?v=' . time()) ?>">
</head>
<body>
<header class="site-header">
  <div class="container header-inner">
    <a href="<?= url('/public/index.php') ?>" class="logo">🌿 Đắk Lắk<span>Travel AI</span></a>
    <nav class="main-nav">
      <a href="<?= url('/public/index.php') ?>"><?= __('home') ?></a>
      <div class="nav-dropdown">
        <button class="dropbtn"><?= __('explore') ?></button>
        <div class="dropdown-content">
          <a href="<?= url('/public/destinations.php') ?>"><?= __('destinations') ?></a>
          <a href="<?= url('/public/map.php') ?>"><?= __('map') ?></a>
          <a href="<?= url('/public/articles.php') ?>"><?= __('articles') ?></a>
        </div>
      </div>
      <div class="nav-dropdown">
        <button class="dropbtn"><?= __('ai_tools') ?></button>
        <div class="dropdown-content">
          <a href="<?= url('/public/itinerary.php') ?>"><?= __('ai_itinerary') ?></a>
          <a href="<?= url('/public/chatbot.php') ?>"><?= __('ai_chatbot') ?></a>
        </div>
      </div>
      <div class="nav-dropdown">
        <button class="dropbtn"><?= __('others') ?></button>
        <div class="dropdown-content">
          <a href="<?= url('/public/safety.php') ?>"><?= __('safety') ?></a>
          <a href="<?= url('/public/reviews.php') ?>"><?= __('reviews') ?></a>
          <a href="<?= url('/public/about.php') ?>"><?= __('about') ?></a>
          <a href="<?= url('/public/contact.php') ?>"><?= __('contact') ?></a>
        </div>
      </div>
    </nav>
    <div class="auth-area">
      <?php $currentLang = $_SESSION['lang'] ?? 'vi'; ?>
      <?php if ($currentLang === 'vi'): ?>
          <a href="<?= url('/public/change_lang.php?lang=en') ?>" title="English" style="text-decoration:none;font-size:20px;margin-right:10px;">🇬🇧</a>
      <?php else: ?>
          <a href="<?= url('/public/change_lang.php?lang=vi') ?>" title="Tiếng Việt" style="text-decoration:none;font-size:20px;margin-right:10px;">🇻🇳</a>
      <?php endif; ?>

      <?php $__u = currentUser(); ?>
      <?php if ($__u): ?>
        <span class="auth-greeting" style="display:flex; align-items:center; gap:8px;">
            <?php if (!empty($__u['avatar'])): ?>
                <img src="<?= e($__u['avatar']) ?>" alt="Avatar" style="width:28px; height:28px; border-radius:50%; object-fit:cover; border:1px solid rgba(255,255,255,0.3);">
            <?php else: ?>
                👋
            <?php endif; ?>
            <?= __('hello') ?>, <a href="<?= url('/public/profile.php') ?>" style="color:#fde68a;text-decoration:none;border-bottom:1px dashed #fde68a;" title="Profile"><strong><?= e($__u['full_name']) ?></strong></a>
        </span>
        <?php if ($__u['role'] === 'admin'): ?>
          <a href="<?= url('/admin/index.php') ?>" class="btn secondary"><?= __('admin') ?></a>
        <?php endif; ?>
        <a href="<?= url('/public/logout.php') ?>" class="btn secondary"><?= __('logout') ?></a>
      <?php else: ?>
        <a href="<?= url('/public/login.php') ?>" class="btn secondary"><?= __('login') ?></a>
        <a href="<?= url('/public/register.php') ?>" class="btn"><?= __('register') ?></a>
      <?php endif; ?>
    </div>
  </div>
</header>
<main class="container main-content">
