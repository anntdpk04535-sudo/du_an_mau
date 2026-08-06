<?php
require_once __DIR__ . '/../includes/content_helpers.php';

$slug = $_GET['slug'] ?? '';
$event = getEventBySlug($slug);

if (!$event) {
    header("Location: " . url('/public/events.php'));
    exit;
}

$pageTitle = $event['title'] . ' - Sự kiện Đắk Lắk';
$metaDescription = $event['short_desc'];

include __DIR__ . '/../includes/header.php';
?>

<div class="event-detail-container" style="max-width: 960px; margin: 24px auto;">
  <a href="<?= url('/public/events.php') ?>" class="text-link" style="display: inline-block; margin-bottom: 16px; color: var(--jungle-green); font-weight: 700;">← Quay lại danh sách sự kiện</a>

  <div class="event-hero-banner" style="position: relative; height: 380px; border-radius: 20px; overflow: hidden; margin-bottom: 28px; box-shadow: 0 14px 34px rgba(61, 35, 13, 0.2);">
    <img src="<?= url($event['image_url'] ?: '/assets/images/placeholder.svg') ?>" alt="<?= e($event['title']) ?>" onerror="this.onerror=null;this.src='<?= url('/assets/images/placeholder.svg') ?>';" style="width: 100%; height: 100%; object-fit: cover;">

    <div style="position: absolute; inset: 0; background: linear-gradient(180deg, rgba(0,0,0,0.1) 0%, rgba(33,27,21,0.85) 100%);"></div>
    <div style="position: absolute; bottom: 28px; left: 28px; right: 28px; color: #FFFFFF;">
      <span class="event-cat-badge" style="position: static; display: inline-block; margin-bottom: 10px;">
        <?= $event['category'] === 'nong-san' ? '☕ Lễ hội Nông sản' : ($event['category'] === 'van-hoa' ? '🥁 Văn hóa Tây Nguyên' : '🔥 Nghi lễ truyền thống') ?>
      </span>
      <h1 style="font-size: clamp(26px, 4vw, 40px); margin: 0 0 10px; text-shadow: 0 2px 10px rgba(0,0,0,0.5);"><?= e($event['title']) ?></h1>
      <p style="margin: 0; font-size: 15px; color: #F4EBE2; font-weight: 500;">📍 <?= e($event['location']) ?></p>
    </div>
  </div>

  <!-- Event Quick Metadata Card -->
  <div class="form-box" style="display: flex; gap: 20px; flex-wrap: wrap; justify-content: space-between; align-items: center; background: #FAF6F0; border-left: 5px solid var(--basalt-red); margin-bottom: 30px;">
    <div>
      <div style="font-size: 13px; color: var(--text-muted); font-weight: 600;">THỜI GIAN DIỄN RA</div>
      <div style="font-size: 18px; font-weight: 800; color: var(--coffee-brown); margin-top: 2px;">
        📅 <?= date('d/m/Y', strtotime($event['start_date'])) ?> - <?= date('d/m/Y', strtotime($event['end_date'])) ?>
      </div>
    </div>

    <div>
      <div style="font-size: 13px; color: var(--text-muted); font-weight: 600;">ĐỊA ĐIỂM TỔ CHỨC</div>
      <div style="font-size: 15px; font-weight: 700; color: var(--jungle-dark); margin-top: 2px;">
        📍 <?= e($event['location']) ?>
      </div>
    </div>

    <a href="<?= url('/public/itinerary.php') ?>" class="btn btn-jungle" style="display: inline-flex; align-items: center; gap: 8px;">
      <span>✨ Lên Lịch Trình Tham Dự AI</span>
    </a>
  </div>

  <!-- Main Article / Event Details -->
  <article class="form-box" style="padding: 32px; font-size: 16px; line-height: 1.8; color: var(--text-primary);">
    <h2 style="color: var(--basalt-red); font-size: 24px; margin-top: 0; border-bottom: 2px dashed var(--line); padding-bottom: 12px;">Giới Thiệu Lễ Hội & Sự Kiện</h2>
    <p style="font-size: 17px; font-weight: 600; color: var(--coffee-mid);"><?= e($event['short_desc']) ?></p>
    
    <div class="event-body-content" style="margin-top: 24px;">
      <?= $event['content'] ?>
    </div>
  </article>

  <!-- CTA Box -->
  <div class="hero-daklak" style="min-height: 200px; padding: 36px; text-align: center; margin-top: 36px;">
    <div class="hero-content-wrapper">
      <h3 style="font-size: 24px; margin-bottom: 8px;">Bạn Muốn Ghé Thăm Lễ Hội Này?</h3>
      <p style="font-size: 15px; margin-bottom: 20px;">Để AI thiết kế lịch trình tour tự động tối ưu phương tiện, ăn uống và nơi ở quanh khu vực lễ hội cho bạn.</p>
      <div style="display: flex; gap: 12px; justify-content: center;">
        <a href="<?= url('/public/itinerary.php') ?>" class="btn btn-jungle">✨ Tạo Lịch Trình AI Ngay</a>
        <a href="<?= url('/public/chatbot.php?ask=' . urlencode('Hỏi về ' . $event['title'])) ?>" class="btn btn-basalt">🐘 Hỏi Voi Bản Đôn</a>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
