<?php
require_once __DIR__ . '/../includes/functions.php';
$pageTitle = 'Du lịch Đắk Lắk AI - Trang chủ';
$featured = array_slice(getAllDestinations(), 0, 6);
$user = currentUser();

$myItineraries = [];
if ($user) {
  $db = getDB();
  $stmt = $db->prepare("SELECT * FROM itineraries WHERE user_id = ? ORDER BY created_at DESC LIMIT 3");
  $stmt->execute([$user['id']]);
  $myItineraries = $stmt->fetchAll();
}

include __DIR__ . '/../includes/header.php';
?>

<?php if ($user): ?>
  <section class="hero">
    <h1>Chào mừng trở lại, <?= e($user['full_name']) ?> 👋</h1>
    <p>Sẵn sàng khám phá Đắk Lắk? Tạo lịch trình mới hoặc tiếp tục hành trình của bạn.</p>
    <div class="cta">
      <a href="<?= url('/public/itinerary.php') ?>" class="btn">🧭 Tạo lịch trình AI</a>
      <a href="<?= url('/public/chatbot.php') ?>" class="btn secondary">💬 Hỏi Chatbot AI</a>
    </div>
  </section>

  <h2 class="section-title">Lịch trình của bạn</h2>
  <?php if ($myItineraries): ?>
    <p class="section-sub">Các lịch trình bạn đã tạo gần đây</p>
    <div class="grid">
      <?php foreach ($myItineraries as $it): ?>
        <div class="card">
          <div class="card-body">
            <h3><?= e($it['title']) ?></h3>
            <p>📅 <?= e((string) $it['days']) ?> ngày<?= $it['preferences'] ? ' · ' . e($it['preferences']) : '' ?></p>
            <span class="badge"><?= e(date('d/m/Y', strtotime($it['created_at']))) ?></span>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <p class="section-sub">Bạn chưa có lịch trình nào. <a href="<?= url('/public/itinerary.php') ?>">Tạo lịch trình đầu tiên
        ngay!</a></p>
  <?php endif; ?>
<?php else: ?>
  <section class="hero">
    <h1>Khám phá Đắk Lắk cùng Trợ lý AI</h1>
    <p>Hồ Lắk, thác Dray Nur, Buôn Đôn, cà phê Buôn Ma Thuột... để AI gợi ý lịch trình phù hợp với bạn.</p>
    <div class="cta">
      <a href="<?= url('/public/itinerary.php') ?>" class="btn">🧭 Tạo lịch trình AI</a>
      <a href="<?= url('/public/chatbot.php') ?>" class="btn secondary">💬 Hỏi Chatbot AI</a>
      <a href="<?= url('/public/register.php') ?>" class="btn secondary">📝 Đăng ký để lưu lịch trình</a>
    </div>
  </section>
<?php endif; ?>

<h2 class="section-title">Điểm đến nổi bật</h2>
<p class="section-sub">Những địa danh không thể bỏ qua khi đến Đắk Lắk</p>

<div class="grid">
  <?php foreach ($featured as $d): ?>
    <a href="<?= url('/public/destination.php') ?>?slug=<?= e($d['slug']) ?>" class="card">
      <div class="card-img">
        <?php if (!empty($d['image_url'])): ?>
          <img src="<?= e($d['image_url']) ?>" alt="<?= e($d['name']) ?>" style="width:100%;height:100%;object-fit:cover;">
        <?php else: ?>
          🌄
        <?php endif; ?>
      </div>
      <div class="card-body">
        <h3><?= e($d['name']) ?></h3>
        <p><?= e($d['short_desc']) ?></p>
        <span class="badge">⭐ <?= e((string) $d['rating']) ?></span>
        <span class="badge">~<?= e((string) $d['avg_visit_hours']) ?>h</span>
      </div>
    </a>
  <?php endforeach; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>