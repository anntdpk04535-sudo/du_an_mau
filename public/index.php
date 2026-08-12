<?php
require_once __DIR__ . '/../includes/functions.php';
$pageTitle = __('page_title_home');
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

<!-- ── 1. HERO LANDING SECTION (ROYAL EMERALD) ── -->
<section class="hero-landing">
  <div class="hero-landing-bg"></div>
  <div class="hero-overlay"></div>
  <div class="hero-content">
    <div class="hero-badge">
      <span class="hero-badge-icon">🏆</span>
      <span>Top 1 Điểm Đến 2026 · Đại Ngàn Tây Nguyên</span>
    </div>

    <h1 class="hero-title">
      Khám Phá Đắk Lắk <br>
      <span class="highlight-gold">Huyền Thoại Đại Ngàn</span>
    </h1>

    <p class="hero-desc">
      Trải nghiệm thủ phủ cà phê Buôn Ma Thuột, hòa mình vào tiếng thác chảy cuộn cuộn, du lịch thân thiện cùng Voi Buôn Đôn và khám phá văn hóa Cồng Chiêng huyền thoại với sự hỗ trợ của trí tuệ nhân tạo.
    </p>

    <!-- Form Tìm Kiếm Nhanh -->
    <form action="<?= url('/diem-den') ?>" method="get" class="hero-search-box">
      <span style="font-size: 22px; margin-left: 5px;">🔍</span>
      <input type="text" name="q" class="hero-search-input" placeholder="Tìm thác nước, buôn làng, quán cà phê, bảo tàng..." aria-label="Tìm kiếm điểm đến">
      <button type="submit" class="hero-search-btn">
        <span>Tìm Kiếm</span>
      </button>
    </form>

    <!-- Search Pills -->
    <div class="search-pills">
      <a href="<?= url('/diem-den?q=Thác') ?>" class="search-pill-btn">🏔️ Thác nước</a>
      <a href="<?= url('/diem-den?q=Cà+phê') ?>" class="search-pill-btn">☕ Cà phê Ban Mê</a>
      <a href="<?= url('/diem-den?q=Buôn+Đôn') ?>" class="search-pill-btn">🐘 Voi Buôn Đôn</a>
      <a href="<?= url('/diem-den?q=Hồ+Lắk') ?>" class="search-pill-btn">🚣‍♂️ Hồ Lắk</a>
      <a href="<?= url('/diem-den?q=Bảo+tàng') ?>" class="search-pill-btn">🏛️ Bảo tàng</a>
      <a href="<?= url('/diem-den?q=Yok+Đôn') ?>" class="search-pill-btn">🏕️ Vườn Quốc Gia</a>
    </div>

    <!-- Hero CTA Buttons -->
    <div class="hero-cta-group">
      <a href="<?= url('/public/itinerary.php') ?>" class="btn btn-hero-primary">
        <span>✨ Lập Lịch Trình AI</span>
      </a>
      <a href="<?= url('/public/chatbot.php') ?>" class="btn btn-hero-secondary">
        <span>💬 Trợ Lý Chatbot 24/7</span>
      </a>
    </div>
  </div>
</section>

<!-- ── 2. WEATHER WIDGET & STATS COUNTER ── -->
<div class="container" style="margin-top: -40px; position: relative; z-index: 10;">
  <div class="weather-bar-glass">
    <div class="weather-info-group">
      <div id="weather-icon" class="weather-icon-anim">⛅</div>
      <div>
        <h3 class="weather-title"><?= __('weather_title') ?></h3>
        <p id="weather-desc" class="weather-sub"><?= __('loading') ?></p>
      </div>
    </div>
    <div class="weather-temp-wrap">
      <div id="weather-temp" class="weather-temp-value">--°C</div>
    </div>
  </div>

  <div class="stats-grid">
    <div class="stat-card">
      <div class="stat-num">50+</div>
      <div class="stat-label">Điểm đến hấp dẫn</div>
    </div>
    <div class="stat-card">
      <div class="stat-num">4.9★</div>
      <div class="stat-label">Đánh giá du khách</div>
    </div>
    <div class="stat-card">
      <div class="stat-num">10.000+</div>
      <div class="stat-label">Lịch trình AI tạo</div>
    </div>
    <div class="stat-card">
      <div class="stat-num">24/7</div>
      <div class="stat-label">Hỗ trợ thông minh</div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', async function() {
  try {
    const res = await fetch('<?= url('/api/weather.php') ?>');
    const data = await res.json();
    if (data.current_weather) {
      const temp = Math.round(data.current_weather.temperature);
      const code = data.current_weather.weathercode;
      document.getElementById('weather-temp').textContent = temp + '°C';
      
      let icon = '⛅';
      let desc = '<?= __('weather_cloudy') ?>';
      if (code === 0) { icon = '☀️'; desc = '<?= __('weather_clear') ?>'; }
      else if (code >= 1 && code <= 3) { icon = '⛅'; desc = '<?= __('weather_mostly_cloudy') ?>'; }
      else if (code >= 51 && code <= 67) { icon = '🌧️'; desc = '<?= __('weather_light_rain') ?>'; }
      else if (code >= 71 && code <= 82) { icon = '🌧️'; desc = '<?= __('weather_showers') ?>'; }
      else if (code >= 95) { icon = '⛈️'; desc = '<?= __('weather_thunderstorm') ?>'; }
      
      document.getElementById('weather-icon').textContent = icon;
      document.getElementById('weather-desc').textContent = desc;
    }
  } catch (err) {
    document.getElementById('weather-desc').textContent = '<?= __('weather_error') ?>';
  }
});
</script>

<!-- ── 3. FEATURED DESTINATIONS ── -->
<section class="section-container">
  <div class="section-header-center">
    <span class="section-badge">DIỄN HỌA ĐIỂM ĐẾN</span>
    <h2 class="section-title-large"><?= __('featured_destinations') ?></h2>
    <p class="section-desc"><?= __('featured_sub') ?></p>
  </div>

  <div class="grid">
    <?php foreach ($featured as $d): ?>
      <a href="<?= url('/diem-den/' . $d['slug']) ?>" class="card">
        <div class="card-img">
          <?php if (!empty($d['image_url'])): ?>
            <img src="<?= e($d['image_url']) ?>" alt="<?= e($d['name']) ?>" loading="lazy" onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1542323631-0d3ab5d496a8?q=80&w=1000&auto=format&fit=crop';">
          <?php else: ?>
            🌄
          <?php endif; ?>
        </div>
        <div class="card-body">
          <h3><?= e($d['name']) ?></h3>
          <p><?= e($d['short_desc']) ?></p>
          <div style="display:flex; justify-content:space-between; align-items:center; margin-top: auto;">
            <span class="badge badge-rating">★ <?= $d['avg_rating'] !== null ? round((float)$d['avg_rating'], 1) : 'Mới' ?></span>
            <span class="badge">~<?= e((string) $d['avg_visit_hours']) ?>h</span>
          </div>
        </div>
      </a>
    <?php endforeach; ?>
  </div>

  <div style="text-align: center; margin-top: 40px;">
    <a href="<?= url('/diem-den') ?>" class="btn" style="padding: 14px 36px; border-radius: 40px; font-weight: 800;">
      <?= __('view_all_dest') ?>
    </a>
  </div>
</section>

<!-- ── 4. AI FEATURE HIGHLIGHT SECTION ── -->
<section class="section-container" style="background: linear-gradient(135deg, #022c22 0%, #064e3b 100%); color: #ffffff; border-radius: 32px; padding: 60px 40px; margin-bottom: 70px; box-shadow: 0 20px 50px rgba(2,44,34,0.3);">
  <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 40px; align-items: center;">
    <div>
      <span style="background: rgba(254, 240, 138, 0.2); color: #fef08a; padding: 6px 16px; border-radius: 30px; font-weight: 700; font-size: 13px;">TRÍ TUỆ NHÂN TẠO 4.0</span>
      <h2 style="font-size: 2.4rem; font-weight: 800; color: #ffffff; margin: 16px 0 20px; line-height: 1.2;">Lập Lịch Trình Du Lịch Thông Minh Trong 3 Giây</h2>
      <p style="color: #a7f3d0; font-size: 1.05rem; line-height: 1.7; margin-bottom: 24px;">
        Thuật toán AI độc quyền tự động tính toán thời gian di chuyển, tối ưu tuyến đường, phân bổ ngân sách và gợi ý các điểm dừng chân chuẩn mực theo đúng sở thích của bạn.
      </p>
      <div style="display: flex; flex-direction: column; gap: 12px; margin-bottom: 30px;">
        <div style="display: flex; align-items: center; gap: 10px; font-weight: 600; color: #fef08a;">
          <span>✓</span> <span>Tự động linh hoạt reroute khi thời tiết mưa dông</span>
        </div>
        <div style="display: flex; align-items: center; gap: 10px; font-weight: 600; color: #fef08a;">
          <span>✓</span> <span>Tích hợp bản đồ Leaflet chỉ đường trực tiếp</span>
        </div>
        <div style="display: flex; align-items: center; gap: 10px; font-weight: 600; color: #fef08a;">
          <span>✓</span> <span>Xuất tệp PDF lịch trình du lịch chuyên nghiệp</span>
        </div>
      </div>
      <a href="<?= url('/public/itinerary.php') ?>" class="btn" style="background: linear-gradient(135deg, #f59e0b, #d97706); color: #ffffff !important; border: none; padding: 14px 32px; border-radius: 40px; font-weight: 800;">
        Trải Nghiệm AI Ngay ➔
      </a>
    </div>

    <div style="background: rgba(255, 255, 255, 0.08); backdrop-filter: blur(20px); border: 1px solid rgba(254, 240, 138, 0.3); border-radius: 24px; padding: 30px;">
      <h3 style="color: #fef08a; font-size: 1.3rem; font-weight: 800; margin-top: 0;">💡 Lịch Trình Mẫu Được Gợi Ý</h3>
      <div style="display: flex; flex-direction: column; gap: 16px; margin-top: 20px;">
        <div style="background: rgba(2, 44, 34, 0.6); padding: 14px; border-radius: 14px; border-left: 4px solid #f59e0b;">
          <div style="font-weight: 800; color: #ffffff;">🌅 Sáng: Thác Dray Nur & Cà Phê Mộc</div>
          <div style="font-size: 13px; color: #a7f3d0; margin-top: 4px;">Ngắm tháp nước hùng vĩ và dùng cà phê bản địa.</div>
        </div>
        <div style="background: rgba(2, 244, 34, 0.6); padding: 14px; border-radius: 14px; border-left: 4px solid #10b981;">
          <div style="font-weight: 800; color: #ffffff;">☀️ Trưa: Ăn Cơm Lam Gà Sa Lửa Buôn Đôn</div>
          <div style="font-size: 13px; color: #a7f3d0; margin-top: 4px;">Thưởng thức đặc sản truyền thống đồng bào Ê Đê.</div>
        </div>
        <div style="background: rgba(2, 44, 34, 0.6); padding: 14px; border-radius: 14px; border-left: 4px solid #3b82f6;">
          <div style="font-weight: 800; color: #ffffff;">🚣‍♂️ Chiều: Thuyền Độc Mộc Hồ Lắc & Voi Buôn Đôn</div>
          <div style="font-size: 13px; color: #a7f3d0; margin-top: 4px;">Trải nghiệm du lịch sinh thái thân thiện bảo tồn voi.</div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ── 5. UNIQUE EXPERIENCES ── -->
<section class="section-container">
  <div class="section-header-center">
    <span class="section-badge">TRẢI NGHIỆM ĐỘC BẢN</span>
    <h2 class="section-title-large">5 Trải Nghiệm Không Thể Bỏ Qua Tại Đắk Lắk</h2>
    <p class="section-desc">Những dấu ấn văn hóa và thiên nhiên đặc sắc làm nên tên tuổi của đại ngàn Tây Nguyên.</p>
  </div>

  <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 24px;">
    <div style="background: #ffffff; padding: 28px; border-radius: 20px; border: 1px solid #e2e8f0; box-shadow: 0 10px 25px rgba(0,0,0,0.03);">
      <div style="font-size: 36px; margin-bottom: 12px;">☕</div>
      <h3 style="font-size: 1.2rem; font-weight: 800; color: #022c22; margin: 0 0 8px;">Thưởng Thức Cà Phê Ban Mê</h3>
      <p style="color: #64748b; font-size: 14px; line-height: 1.6; margin: 0;">Ghé thăm Làng cà phê Trung Nguyên và các quán cà phê specialty đậm đà hương vị mộc mạc.</p>
    </div>

    <div style="background: #ffffff; padding: 28px; border-radius: 20px; border: 1px solid #e2e8f0; box-shadow: 0 10px 25px rgba(0,0,0,0.03);">
      <div style="font-size: 36px; margin-bottom: 12px;">🐘</div>
      <h3 style="font-size: 1.2rem; font-weight: 800; color: #022c22; margin: 0 0 8px;">Du Lịch Thân Thiện Với Voi</h3>
      <p style="color: #64748b; font-size: 14px; line-height: 1.6; margin: 0;">Trải nghiệm mô hình ngắm voi hoang dã tại Vườn quốc gia Yok Đôn không cưỡi voi nhân văn.</p>
    </div>

    <div style="background: #ffffff; padding: 28px; border-radius: 20px; border: 1px solid #e2e8f0; box-shadow: 0 10px 25px rgba(0,0,0,0.03);">
      <div style="font-size: 36px; margin-bottom: 12px;">🌊</div>
      <h3 style="font-size: 1.2rem; font-weight: 800; color: #022c22; margin: 0 0 8px;">Chinh Phục Thác Dray Nur</h3>
      <p style="color: #64748b; font-size: 14px; line-height: 1.6; margin: 0;">Chiêm ngưỡng dòng thác bọt tung trắng xóa kết nối hai tỉnh Đắk Lắk và Đắk Nông.</p>
    </div>

    <div style="background: #ffffff; padding: 28px; border-radius: 20px; border: 1px solid #e2e8f0; box-shadow: 0 10px 25px rgba(0,0,0,0.03);">
      <div style="font-size: 36px; margin-bottom: 12px;">🚣‍♂️</div>
      <h3 style="font-size: 1.2rem; font-weight: 800; color: #022c22; margin: 0 0 8px;">Đi Thuyền Độc Mộc Hồ Lắk</h3>
      <p style="color: #64748b; font-size: 14px; line-height: 1.6; margin: 0;">Lướt nhẹ trên hồ nước ngọt tự nhiên rộng thứ hai Việt Nam và ngắm bình minh buôn Jun.</p>
    </div>
  </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>