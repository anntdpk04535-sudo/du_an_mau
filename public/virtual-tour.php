<?php
/**
 * Virtual Tour 360° — Trang xem tour toàn diện
 * URL: /public/virtual-tour.php?id=1
 * Thiết kế cho Người cao tuổi & Trẻ em
 */
require_once __DIR__ . '/../includes/functions.php';

$destId = (int)($_GET['id'] ?? 0);

if ($destId <= 0) {
    header('Location: ' . url('/public/destinations.php'));
    exit;
}

$db = getDB();
$stmt = $db->prepare("SELECT * FROM destinations WHERE id = ? AND virtual_tour_enabled = 1");
$stmt->execute([$destId]);
$dest = $stmt->fetch();

if (!$dest) {
    http_response_code(404);
    $pageTitle = __('vt_not_found');
    include __DIR__ . '/../includes/header.php';
    echo '<div class="vt-empty"><div class="vt-empty-icon">🔍</div>';
    echo '<h2>' . __('vt_not_found') . '</h2>';
    echo '<p>' . __('vt_not_found_desc') . '</p>';
    echo '<a href="' . url('/public/destinations.php') . '" class="btn" style="margin-top:16px;">← ' . __('vt_back_list') . '</a></div>';
    include __DIR__ . '/../includes/footer.php';
    exit;
}

$dest = translateDbRow($dest, ['name', 'short_desc', 'description']);
$pageTitle = __('vt_title_prefix') . ' ' . $dest['name'];

// Fix encoding: đảm bảo trình duyệt hiểu đúng UTF-8
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="<?= $_SESSION['lang'] ?? 'vi' ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle) ?></title>
  <meta name="description" content="<?= e(__('vt_meta_desc') . ' ' . $dest['name']) ?>">

  <!-- Pannellum CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/pannellum@2.5.6/build/pannellum.css">

  <!-- Virtual Tour CSS -->
  <link rel="stylesheet" href="<?= url('/assets/css/style.css?v=' . time()) ?>">
  <link rel="stylesheet" href="<?= url('/assets/css/virtual-tour.css?v=' . time()) ?>">
</head>
<body class="vt-page">

<!-- Hidden inputs cho JS -->
<input type="hidden" id="vt-api-base" value="<?= url('') ?>">
<input type="hidden" id="vt-dest-id" value="<?= (int)$dest['id'] ?>">

<div class="container">
  <!-- ── Header ── -->
  <div class="vt-header">
    <a href="<?= url('/public/destination.php?slug=' . e($dest['slug'])) ?>" class="vt-back-btn">
      ← <?= __('vt_back_dest') ?>
    </a>

    <div class="vt-title-area">
      <h1>
        <?= e($dest['name']) ?>
        <span class="vt-badge-360">360°</span>
      </h1>
    </div>

    <div class="vt-accessibility-btns">
      <button onclick="vtFontDecrease()" title="<?= __('vt_font_smaller') ?>">A-</button>
      <button onclick="vtFontIncrease()" title="<?= __('vt_font_larger') ?>">A+</button>
    </div>
  </div> 

  <!-- ── Viewer ── -->
  <div class="vt-viewer-wrapper">
    <div class="vt-viewer-container" id="vt-panorama"></div>

    <!-- Scene counter overlay -->
    <div class="vt-scene-counter" id="vt-scene-counter">📍 1 / 1</div>

    <!-- Fullscreen button -->
    <button class="vt-fullscreen-btn" id="vt-fullscreen-btn" onclick="vtToggleFullscreen()" title="<?= __('vt_fullscreen') ?>">⛶</button>

    <!-- Loading overlay -->
    <div class="vt-loading" id="vt-loading">
      <div class="vt-loading-spinner"></div>
      <div class="vt-loading-text"><?= __('vt_loading') ?></div>
    </div>

    <!-- Onboarding overlay -->
    <div class="vt-onboarding hidden" id="vt-onboarding" onclick="vtDismissOnboarding()">
      <div class="vt-onboarding-icon">👆</div>
      <h3><?= __('vt_onboard_title') ?></h3>
      <p><?= __('vt_onboard_desc') ?></p>
      <button class="vt-onboarding-dismiss" onclick="vtDismissOnboarding()"><?= __('vt_onboard_start') ?></button>
    </div>
  </div>

  <!-- ── Scene Info Panel ── -->
  <div class="vt-scene-info-panel">
    <h2 id="vt-scene-title">...</h2>
    <p id="vt-scene-desc"></p>
  </div>

  <!-- ── Controls Bar ── -->
  <div class="vt-controls">
    <div class="vt-nav-group">
      <button class="vt-nav-btn" id="vt-prev-btn" onclick="vtPrevScene()" disabled>
        ◀ <?= __('vt_prev') ?>
      </button>
      <div class="vt-progress">
        <span class="vt-progress-text" id="vt-progress-text">1/1</span>
        <div class="vt-progress-bar">
          <div class="vt-progress-fill" id="vt-progress-fill" style="width: 100%"></div>
        </div>
      </div>
      <button class="vt-nav-btn primary" id="vt-next-btn" onclick="vtNextScene()">
        <?= __('vt_next') ?> ▶
      </button>
    </div>

    <div class="vt-audio-group" style="display:flex; gap:8px;">
      <select id="vt-audio-lang" class="vt-audio-lang-select" onchange="vtChangeAudioLang()" title="Chọn ngôn ngữ thuyết minh" style="padding:4px 8px; border-radius:6px; border:1px solid #ccc; background:#fff;">
        <option value="vi">🇻🇳 Tiếng Việt</option>
        <option value="en">🇬🇧 English</option>
      </select>
      <button class="vt-audio-btn" id="vt-audio-btn" onclick="vtToggleAudio()"
              data-label-play="<?= __('vt_listen') ?>"
              data-label-stop="<?= __('vt_stop') ?>">
        🔊 <?= __('vt_listen') ?>
      </button>
    </div>
  </div>

  <!-- ── Scene Thumbnails ── -->
  <div class="vt-scenes-list" id="vt-scenes-list"></div>

  <!-- ── Empty state (hidden) ── -->
  <div class="vt-empty" id="vt-empty" style="display:none;">
    <div class="vt-empty-icon">🌐</div>
    <h2><?= __('vt_no_scenes') ?></h2>
    <p><?= __('vt_no_scenes_desc') ?></p>
  </div>
</div>

<!-- Pannellum JS -->
<script src="https://cdn.jsdelivr.net/npm/pannellum@2.5.6/build/pannellum.js"></script>

<!-- Virtual Tour JS -->
<script src="<?= url('/assets/js/virtual-tour.js?v=' . time()) ?>"></script>

</body>
</html>
