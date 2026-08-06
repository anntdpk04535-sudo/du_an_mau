<?php
require_once __DIR__ . '/../includes/content_helpers.php';
$pageTitle = __('page_title_home');
$featured = getFeaturedDestinations(6);
if (empty($featured)) {
  $featured = array_slice(getAllDestinations(), 0, 6);
}
$user = currentUser();
$contentDb = getDB();
$featuredFoods = tableExists($contentDb, 'foods') ? $contentDb->query("SELECT f.*, COALESCE(f.image_url, (SELECT image_url FROM food_images fi WHERE fi.food_id=f.id ORDER BY fi.is_primary DESC,fi.sort_order,fi.id LIMIT 1)) AS card_image FROM foods f WHERE f.status='published' ORDER BY f.is_featured DESC, f.id DESC LIMIT 6")->fetchAll() : [];
$featuredStays = tableExists($contentDb, 'accommodations') ? $contentDb->query("SELECT a.*, COALESCE(a.image_url, (SELECT image_url FROM accommodation_images ai WHERE ai.accommodation_id=a.id ORDER BY ai.is_primary DESC,ai.sort_order,ai.id LIMIT 1)) AS card_image FROM accommodations a WHERE a.status='published' ORDER BY a.is_featured DESC, a.id DESC LIMIT 4")->fetchAll() : [];


$myItineraries = [];
if ($user) {
  $db = getDB();
  $stmt = $db->prepare("SELECT * FROM itineraries WHERE user_id = ? ORDER BY created_at DESC LIMIT 3");
  $stmt->execute([$user['id']]);
  $myItineraries = $stmt->fetchAll();
}

include __DIR__ . '/../includes/header.php';
?>

<section class="hero hero-daklak">
  <div class="hero-content-wrapper">
    <span class="hero-eyebrow">🌿 Khám Phá Vùng Đất Tây Nguyên Bùng Nổ Cảm Cảm Xúc</span>
    <?php if ($user): ?>
      <h1>Xin chào, <?= e($user['full_name']) ?>! 👋</h1>
      <p>Bạn muốn khám phá điều gì ở Đắk Lắk hôm nay?</p>
    <?php else: ?>
      <h1>Hành Trình Chạm Vào Huyền Thoại Đắk Lắk</h1>
      <p>Bạn muốn khám phá điều gì ở Đắk Lắk hôm nay?</p>
    <?php endif; ?>

    <!-- Prominent Search Bar -->
    <div class="hero-search-box">
      <form action="<?= url('/public/chatbot.php') ?>" method="get" class="hero-search-form">
        <div class="search-input-wrapper">
          <svg class="search-icon" xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="11" cy="11" r="8"></circle>
            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
          </svg>
          <input type="text" name="ask" placeholder="Nhập câu hỏi... (ví dụ: Thác Dray Nur, quán cà phê ngon, Buôn Đôn)" aria-label="Tra cứu thông tin du lịch Đắk Lắk" autocomplete="off">
        </div>
        <button type="submit" class="hero-search-btn">
          <span>Tìm kiếm</span>
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
        </button>
      </form>

      <!-- Quick Destination Suggestion Pills -->
      <div class="hero-quick-suggestions">
        <span class="sugg-label">Gợi ý nổi bật:</span>
        <a href="<?= url('/public/chatbot.php?ask=Buôn%20Đôn') ?>" class="coffee-bean-pill">Buôn Đôn</a>
        <a href="<?= url('/public/chatbot.php?ask=Bảo%20tàng%20Thế%20giới%20Cà%20phê') ?>" class="coffee-bean-pill">Bảo tàng Cà phê</a>
        <a href="<?= url('/public/chatbot.php?ask=Thác%20Dray%20Nur') ?>" class="coffee-bean-pill">Thác Dray Nur</a>
        <a href="<?= url('/public/chatbot.php?ask=Hồ%20Lắk') ?>" class="coffee-bean-pill">Hồ Lắk</a>
      </div>
    </div>

    <div class="hero-cta-buttons">
      <a href="<?= url('/public/itinerary.php') ?>" class="btn btn-jungle">✨ Lên Lịch Trình AI</a>
      <a href="<?= url('/public/chatbot.php') ?>" class="btn btn-basalt">🐘 Hỏi Voi Bản Đôn</a>
    </div>
  </div>
</section>

<?php if ($user && $myItineraries): ?>
  <h2 class="section-title"><?= __('my_saved_iti') ?></h2>
  <p class="section-sub"><?= __('my_saved_iti_sub') ?></p>
  <div class="grid">
    <?php foreach ($myItineraries as $it):
      $it = translateItineraryDynamic($it);
    ?>
      <a href="<?= url('/public/itinerary_view.php?id=' . $it['id']) ?>" class="card" style="text-decoration:none;">
        <div class="card-body">
          <h3 style="color:var(--jungle-dark);"><?= e($it['title']) ?></h3>
          <p>📅 <?= e((string) $it['days']) ?> <?= __('days') ?><?= $it['preferences'] ? ' · ' . e($it['preferences']) : '' ?></p>
          <span class="badge"><?= e(date('d/m/Y', strtotime($it['created_at']))) ?></span>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<section class="audience-paths" aria-label="Gợi ý theo nhu cầu">
  <a href="<?= url('/diem-den?cat=1') ?>"><span>01</span><strong>Săn thác và hồ</strong><small>Dray Nur · Hồ Lắk · Chu Yang Sin</small></a>
  <a href="<?= url('/am-thuc?type=cafe') ?>"><span>02</span><strong>Ăn ngon, uống cà phê</strong><small>Bún đỏ · cà phê · chợ địa phương</small></a>
  <a href="<?= url('/diem-den?cat=3') ?>"><span>03</span><strong>Chạm vào buôn làng</strong><small>Ê Đê · M’Nông · nhà dài · cồng chiêng</small></a>
  <a href="<?= url('/public/itinerary.php') ?>"><span>04</span><strong>Lên lịch trình riêng</strong><small>Đi cùng gia đình hoặc đi thật xa</small></a>
</section>

<section class="region-routes" aria-label="Hai miền khám phá">
  <div class="region-route region-route-west"><span class="eyebrow">Tuyến phía Tây</span><h2>Rừng, thác và nhịp sống buôn làng</h2><p>Buôn Đôn, Yok Đôn và những cung đường đỏ bazan dành cho người thích thiên nhiên và văn hóa.</p><a href="<?= url('/diem-den?region=west') ?>" class="text-link">Khám phá phía Tây →</a></div>
  <div class="region-route region-route-east"><span class="eyebrow">Tuyến phía Đông</span><h2>Hồ, đồi cà phê và những khoảng chậm</h2><p>Hồ Lắk, Ea Kar và các trải nghiệm cà phê cho chuyến đi nhẹ nhàng hơn.</p><a href="<?= url('/diem-den?region=east') ?>" class="text-link">Khám phá phía Đông →</a></div>
</section>

<section id="weather-widget" class="weather-card-daklak" aria-label="Thời tiết trực tiếp">
  <div class="weather-header">
    <div class="weather-main-info">
      <div id="weather-icon" class="weather-icon-badge">⛅</div>
      <div>
        <div class="weather-location">
          <span class="live-pulse"></span>
          <strong id="weather-location-name">Buôn Ma Thuột, Đắk Lắk</strong>
        </div>
        <div id="weather-desc" class="weather-condition-text">Đang tải dữ liệu thời tiết trực tiếp...</div>
      </div>
    </div>

    <div class="weather-temp-block">
      <div id="weather-temp" class="weather-temp-main">--°C</div>
      <div id="weather-feels-like" class="weather-feels-like">Cảm giác như --°C</div>
    </div>
  </div>

  <div class="weather-details-grid">
    <div class="weather-detail-item">
      <span class="detail-label">Thấp / Cao nhất</span>
      <strong id="weather-range">--°C - --°C</strong>
    </div>
    <div class="weather-detail-item">
      <span class="detail-label">Độ ẩm</span>
      <strong id="weather-humidity">--%</strong>
    </div>
    <div class="weather-detail-item">
      <span class="detail-label">Tốc độ gió</span>
      <strong id="weather-wind">-- km/h</strong>
    </div>
  </div>

  <div id="weather-advice" class="weather-advice-box">
    🌿 <span>Đang cập nhật gợi ý du lịch theo thời tiết...</span>
  </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', async function() {
  try {
    const res = await fetch('<?= url('/api/weather.php') ?>');
    const data = await res.json();
    if (data.success) {
      document.getElementById('weather-icon').textContent = data.icon || '⛅';
      document.getElementById('weather-location-name').textContent = data.location || 'Buôn Ma Thuột, Đắk Lắk';
      document.getElementById('weather-desc').textContent = data.condition_text_vi || 'Mát mẻ';
      document.getElementById('weather-temp').textContent = data.temperature + '°C';
      document.getElementById('weather-feels-like').textContent = 'Cảm giác như ' + data.apparent_temperature + '°C';
      document.getElementById('weather-range').textContent = data.temp_min + '°C - ' + data.temp_max + '°C';
      document.getElementById('weather-humidity').textContent = data.humidity + '%';
      document.getElementById('weather-wind').textContent = data.wind_speed + ' km/h';
      
      const adviceBox = document.getElementById('weather-advice');
      if (data.advice_vi && adviceBox) {
        adviceBox.innerHTML = '🌿 <span>' + data.advice_vi + '</span>';
      }
    }
  } catch (err) {
    console.error('Weather API error:', err);
    document.getElementById('weather-desc').textContent = 'Không thể tải dữ liệu thời tiết';
  }
});
</script>

<?php 
$featuredEvents = getFeaturedEvents(3);
if ($featuredEvents): 
?>
<section class="events-section" style="margin-top: 40px;">
  <div class="section-heading-row">
    <div>
      <h2 class="section-title">🎉 Sự kiện & Lễ hội sắp diễn ra</h2>
      <p class="section-sub">Khám phá các lễ hội văn hóa, đại tiệc nông sản và di sản Tây Nguyên độc đáo.</p>
    </div>
    <a href="<?= url('/public/events.php') ?>" class="btn secondary section-view-all">Xem tất cả lễ hội →</a>
  </div>

  <div class="grid">
    <?php foreach ($featuredEvents as $ev): ?>
      <a href="<?= url('/public/event_detail.php?slug=' . $ev['slug']) ?>" class="card event-card">
        <div class="card-img">
          <?php if (!empty($ev['image_url'])): ?>
            <img src="<?= url($ev['image_url']) ?>" alt="<?= e($ev['title']) ?>" style="width:100%;height:100%;object-fit:cover;" onerror="this.onerror=null;this.src='<?= url('/assets/images/placeholder.svg') ?>';">
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
</section>
<?php endif; ?>

<div class="section-heading-row"><div><h2 class="section-title"><?= __('featured_destinations') ?></h2><p class="section-sub"><?= __('featured_sub') ?></p></div><a href="<?= url('/diem-den') ?>" class="btn secondary section-view-all">Xem tất cả</a></div>

<div class="grid">
  <?php foreach ($featured as $d): ?>
    <a href="<?= url('/diem-den/' . $d['slug']) ?>" class="card">
      <div class="card-img">
        <?php if (!empty($d['image_url'])): ?>
          <img src="<?= url($d['image_url']) ?>" alt="<?= e($d['name']) ?>" style="width:100%;height:100%;object-fit:cover;" onerror="this.onerror=null;this.src='<?= url('/assets/images/placeholder.svg') ?>';">
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

<?php if ($featuredFoods): ?><section style="margin-top:46px"><div class="section-heading-row"><div><h2 class="section-title">🍜 Hương vị địa phương</h2><p class="section-sub">Khám phá món ngon, quán ăn và cà phê được lưu trong dữ liệu địa phương.</p></div><a href="<?= url('/am-thuc') ?>" class="btn secondary section-view-all">Xem tất cả</a></div><div class="grid"><?php foreach($featuredFoods as $food): ?><article class="card"><div class="card-img"><?php if($food['card_image']): ?><img src="<?= e($food['card_image']) ?>" alt="<?= e($food['name']) ?>" loading="lazy" style="width:100%;height:100%;object-fit:cover" onerror="this.onerror=null;this.src='<?= url('/assets/images/placeholder.svg') ?>';"><?php else: ?>🍜<?php endif; ?></div><div class="card-body"><h3><?= e($food['name']) ?></h3><p><?= e($food['address']??'') ?></p><span class="badge"><?= e($food['entity_type']) ?></span></div></article><?php endforeach; ?></div></section><?php endif; ?>
<?php if ($featuredStays): ?><section style="margin-top:46px"><div class="section-heading-row"><div><h2 class="section-title">🛏️ Nơi nghỉ đáng nhớ</h2><p class="section-sub">Chọn nơi lưu trú phù hợp trước khi lên lịch trình.</p></div><a href="<?= url('/luu-tru') ?>" class="btn secondary section-view-all">Xem tất cả</a></div><div class="grid"><?php foreach($featuredStays as $stay): ?><article class="card"><div class="card-img"><?php if($stay['card_image']): ?><img src="<?= e($stay['card_image']) ?>" alt="<?= e($stay['name']) ?>" loading="lazy" style="width:100%;height:100%;object-fit:cover" onerror="this.onerror=null;this.src='<?= url('/assets/images/placeholder.svg') ?>';"><?php else: ?>🛏️<?php endif; ?></div><div class="card-body"><h3><?= e($stay['name']) ?></h3><p><?= e($stay['address']??'') ?></p><span class="badge"><?= e($stay['accommodation_type']) ?></span></div></article><?php endforeach; ?></div></section><?php endif; ?>


<?php include __DIR__ . '/../includes/footer.php'; ?>

