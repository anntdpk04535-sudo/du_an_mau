<?php
if (!isset($pageTitle)) $pageTitle = 'Du lịch Đắk Lắk AI';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php if (!empty($metaDescription)): ?>
<meta name="description" content="<?= e($metaDescription) ?>">
<meta property="og:description" content="<?= e($metaDescription) ?>">
<?php else: ?>
<meta name="description" content="Khám phá các điểm đến hấp dẫn tại Đắk Lắk với sự hỗ trợ của AI. Lên lịch trình, tìm hiểu văn hóa và tận hưởng kỳ nghỉ của bạn.">
<?php endif; ?>
<meta property="og:title" content="<?= e($pageTitle) ?>">
<title><?= e($pageTitle) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;0,700;0,800;1,600&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= url('/assets/css/style.css?v=' . time()) ?>">
</head>
<body>
<a class="skip-link" href="#main-content">Bỏ qua đến nội dung</a>
<div class="top-brocade-stripe"></div>
<header class="site-header">
  <div class="container header-inner">
    <a href="<?= url('/public/index.php') ?>" class="logo">🌿 Đắk Lắk <span>Travel AI</span></a>
    
    <nav class="main-nav">
      <a href="<?= url('/public/index.php') ?>"><?= __('home') ?></a>
      <a href="<?= url('/public/forum.php') ?>"><?= __('nav_forum') ?></a>
      <div class="nav-dropdown">
        <button class="dropbtn"><?= __('explore') ?></button>
        <div class="dropdown-content">
          <a href="<?= url('/public/events.php') ?>">🎉 Sự kiện & Lễ hội</a>
          <a href="<?= url('/diem-den') ?>"><?= __('destinations') ?></a>
          <a href="<?= url('/am-thuc') ?>">Ẩm thực địa phương</a>
          <a href="<?= url('/luu-tru') ?>">Nơi lưu trú</a>
          <a href="<?= url('/public/map.php') ?>"><?= __('map') ?></a>
          <a href="<?= url('/cam-nang') ?>"><?= __('articles') ?></a>
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
      <button type="button" data-lang-url="<?= url('/public/change_lang.php?lang=' . ($currentLang === 'vi' ? 'en' : 'vi')) ?>" class="lang-toggle" title="<?= $currentLang === 'vi' ? 'English' : 'Tiếng Việt' ?>" style="display:flex; align-items:center; gap:6px; cursor:pointer; color:inherit; margin-right:15px; font-weight:600; font-size:14px; padding:6px 12px; border-radius:20px; background:rgba(255,255,255,0.15); border:1px solid rgba(255,255,255,0.3); transition:all 0.3s ease; backdrop-filter:blur(5px);">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="12" r="10"></circle>
              <line x1="2" y1="12" x2="22" y2="12"></line>
              <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path>
          </svg>
          <span><?= $currentLang === 'vi' ? 'EN' : 'VI' ?></span>
      </button>

      <?php $__u = currentUser(); ?>
      <?php if ($__u): ?>
        <span class="auth-greeting" style="display:flex; align-items:center; gap:8px;">
            <?php if (!empty($__u['avatar'])): ?>
                <img src="<?= e(get_avatar($__u['avatar'])) ?>" alt="Avatar" referrerpolicy="no-referrer" style="width:28px; height:28px; border-radius:50%; object-fit:cover; border:1px solid rgba(255,255,255,0.3);">
            <?php else: ?>
                <img src="<?= e(get_avatar(null)) ?>" alt="Avatar" referrerpolicy="no-referrer" style="width:28px; height:28px; border-radius:50%; object-fit:cover; border:1px solid rgba(255,255,255,0.3);">
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

    <!-- Mobile Hamburger Toggle Button -->
    <button id="mobile-menu-btn" class="mobile-menu-btn" type="button" aria-label="Mở danh mục menu di động">
      <span class="bar"></span>
      <span class="bar"></span>
      <span class="bar"></span>
    </button>
  </div>
</header>

<!-- Mobile Navigation Drawer -->
<div id="mobile-nav-overlay" class="mobile-nav-overlay"></div>
<aside id="mobile-nav-drawer" class="mobile-nav-drawer" aria-label="Menu di động">
  <div class="mobile-drawer-header">
    <a href="<?= url('/public/index.php') ?>" class="logo">🌿 Đắk Lắk <span>Travel AI</span></a>
    <button id="mobile-drawer-close" class="mobile-drawer-close" type="button" aria-label="Đóng menu">✕</button>
  </div>

  <div class="mobile-drawer-body">
    <div class="mobile-nav-links">
      <a href="<?= url('/public/index.php') ?>" class="mobile-nav-item">🏠 <?= __('home') ?></a>
      
      <div class="mobile-nav-group">
        <span class="group-title">🧭 <?= __('explore') ?></span>
        <a href="<?= url('/public/events.php') ?>">🎉 Sự kiện & Lễ hội</a>
        <a href="<?= url('/diem-den') ?>"><?= __('destinations') ?></a>
        <a href="<?= url('/am-thuc') ?>">Ẩm thực địa phương</a>
        <a href="<?= url('/luu-tru') ?>">Nơi lưu trú</a>
        <a href="<?= url('/public/map.php') ?>"><?= __('map') ?></a>
        <a href="<?= url('/cam-nang') ?>"><?= __('articles') ?></a>
      </div>

      <div class="mobile-nav-group">
        <span class="group-title">🤖 <?= __('ai_tools') ?></span>
        <a href="<?= url('/public/itinerary.php') ?>"><?= __('ai_itinerary') ?></a>
        <a href="<?= url('/public/chatbot.php') ?>"><?= __('ai_chatbot') ?></a>
      </div>

      <div class="mobile-nav-group">
        <span class="group-title">💬 Cộng đồng & Khác</span>
        <a href="<?= url('/public/forum.php') ?>"><?= __('nav_forum') ?></a>
        <a href="<?= url('/public/safety.php') ?>"><?= __('safety') ?></a>
        <a href="<?= url('/public/reviews.php') ?>"><?= __('reviews') ?></a>
        <a href="<?= url('/public/about.php') ?>"><?= __('about') ?></a>
        <a href="<?= url('/public/contact.php') ?>"><?= __('contact') ?></a>
      </div>
    </div>

    <div class="mobile-drawer-footer">
      <?php $currentLang = $_SESSION['lang'] ?? 'vi'; ?>
      <button type="button" data-lang-url="<?= url('/public/change_lang.php?lang=' . ($currentLang === 'vi' ? 'en' : 'vi')) ?>" class="lang-toggle mobile-lang-btn">
        🌐 <?= $currentLang === 'vi' ? 'Chuyển sang Tiếng Anh (EN)' : 'Switch to Vietnamese (VI)' ?>
      </button>

      <div class="mobile-auth-block">
        <?php if ($__u): ?>
          <div class="mobile-user-card">
            <span>👋 <?= __('hello') ?>, <strong><?= e($__u['full_name']) ?></strong></span>
            <div class="mobile-user-btns">
              <a href="<?= url('/public/profile.php') ?>" class="btn secondary">Trang cá nhân</a>
              <?php if ($__u['role'] === 'admin'): ?>
                <a href="<?= url('/admin/index.php') ?>" class="btn secondary"><?= __('admin') ?></a>
              <?php endif; ?>
              <a href="<?= url('/public/logout.php') ?>" class="btn secondary"><?= __('logout') ?></a>
            </div>
          </div>
        <?php else: ?>
          <div class="mobile-guest-btns">
            <a href="<?= url('/public/login.php') ?>" class="btn secondary"><?= __('login') ?></a>
            <a href="<?= url('/public/register.php') ?>" class="btn"><?= __('register') ?></a>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</aside>

<main id="main-content" class="container main-content">
