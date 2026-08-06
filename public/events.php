<?php
require_once __DIR__ . '/../includes/content_helpers.php';

$pageTitle = 'Sự kiện & Lễ hội Đắk Lắk 🎪';
$category = $_GET['cat'] ?? 'all';

$events = getAllEvents($category);

include __DIR__ . '/../includes/header.php';
?>

<section class="events-hero hero-daklak" style="min-height: 300px; padding: 48px 32px; background: linear-gradient(135deg, rgba(139, 38, 29, 0.9), rgba(61, 35, 13, 0.92)), url('/assets/images/uploads/article_2_museum.png') center/cover;">
  <div class="hero-content-wrapper" style="max-width: 780px;">
    <span class="hero-eyebrow">🎪 Không Gian Văn Hóa & Lễ Hội Tỉnh Đắk Lắk</span>
    <h1 style="font-size: clamp(30px, 4vw, 48px); margin-bottom: 12px;">Sự Kiện & Lễ Hội Sắp Diễn Ra</h1>
    <p style="font-size: 16px; color: #F4EBE2; margin-bottom: 0;">Khám phá nhịp đập văn hóa rực rỡ sắc màu Tây Nguyên với các đại tiệc Lễ hội Cà phê, Lễ hội Sầu riêng, Hội đua voi và Đêm hội Cồng chiêng.</p>
  </div>
</section>

<!-- Category Filter Pills -->
<div class="catalog-filters" style="margin-bottom: 28px;">
  <a href="<?= url('/public/events.php?cat=all') ?>" class="coffee-bean-pill <?= $category === 'all' ? 'active' : '' ?>">🎉 Tất cả lễ hội</a>
  <a href="<?= url('/public/events.php?cat=nong-san') ?>" class="coffee-bean-pill <?= $category === 'nong-san' ? 'active' : '' ?>">☕ Lễ hội Nông sản & Cà phê</a>
  <a href="<?= url('/public/events.php?cat=van-hoa') ?>" class="coffee-bean-pill <?= $category === 'van-hoa' ? 'active' : '' ?>">🥁 Văn hóa & Cồng chiêng</a>
  <a href="<?= url('/public/events.php?cat=phong-tuc') ?>" class="coffee-bean-pill <?= $category === 'phong-tuc' ? 'active' : '' ?>">🔥 Nghi lễ & Phong tục</a>
</div>

<?php if ($events): ?>
  <div class="grid">
    <?php foreach ($events as $ev): ?>
      <a href="<?= url('/public/event_detail.php?slug=' . $ev['slug']) ?>" class="card event-card">
        <div class="card-img">
          <?php if (!empty($ev['image_url'])): ?>
            <img src="<?= url($ev['image_url']) ?>" alt="<?= e($ev['title']) ?>" onerror="this.onerror=null;this.src='<?= url('/assets/images/placeholder.svg') ?>';" style="width:100%;height:100%;object-fit:cover;">

          <?php else: ?>
            🎪
          <?php endif; ?>
          <span class="event-cat-badge">
            <?= $ev['category'] === 'nong-san' ? '☕ Nông sản' : ($ev['category'] === 'van-hoa' ? '🥁 Văn hóa' : '🔥 Nghi lễ') ?>
          </span>
        </div>
        <div class="card-body">
          <div class="event-date-row">
            📅 <strong><?= date('d/m/Y', strtotime($ev['start_date'])) ?></strong> - <span><?= date('d/m/Y', strtotime($ev['end_date'])) ?></span>
          </div>
          <h3><?= e($ev['title']) ?></h3>
          <p><?= e($ev['short_desc']) ?></p>
          <span class="event-loc-tag">📍 <?= e($ev['location']) ?></span>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
<?php else: ?>
  <div class="form-box" style="text-align: center; padding: 40px 20px;">
    <p style="font-size: 16px; color: var(--text-muted);">Chưa có sự kiện nào thuộc danh mục này.</p>
    <a href="<?= url('/public/events.php') ?>" class="btn btn-basalt">Xem tất cả sự kiện</a>
  </div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
