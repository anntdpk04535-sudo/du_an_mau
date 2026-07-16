<?php
require_once __DIR__ . '/../includes/functions.php';
$pageTitle = __('page_title_about');
include __DIR__ . '/../includes/header.php';
?>

<h1 class="section-title"><?= __('about_us') ?></h1>
<p class="section-sub"><?= __('about_us_sub') ?></p>

<div class="about-content">
  <p>
    <?= __('about_p1') ?>
  </p>
  <p>
    <?= __('about_p2') ?>
  </p>

  <div class="grid" style="margin-top:24px;">
    <div class="card">
      <div class="card-body">
        <h3>🧭 <?= __('feature_iti') ?></h3>
        <p><?= __('feature_iti_desc') ?></p>
      </div>
    </div>
    <div class="card">
      <div class="card-body">
        <h3>💬 <?= __('feature_chat') ?></h3>
        <p><?= __('feature_chat_desc') ?></p>
      </div>
    </div>
    <div class="card">
      <div class="card-body">
        <h3>🌿 <?= __('feature_local') ?></h3>
        <p><?= __('feature_local_desc') ?></p>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
