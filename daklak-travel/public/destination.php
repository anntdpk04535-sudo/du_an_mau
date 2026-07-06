<?php
require_once __DIR__ . '/../includes/functions.php';

$slug = $_GET['slug'] ?? '';
$d = getDestinationBySlug($slug);

if (!$d) {
  http_response_code(404);
  $pageTitle = 'Không tìm thấy điểm đến';
  include __DIR__ . '/../includes/header.php';
  echo '<h1>404 - Không tìm thấy điểm đến này.</h1>';
  echo '<p><a href="' . url('/public/destinations.php') . '">← Quay lại danh sách điểm đến</a></p>';
  include __DIR__ . '/../includes/footer.php';
  exit;
}

$pageTitle = $d['name'] . ' - Đắk Lắk Travel AI';
include __DIR__ . '/../includes/header.php';
?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<p><a href="<?= url('/public/destinations.php') ?>">← Quay lại danh sách điểm đến</a></p>

<div class="detail-hero">
  <?php if (!empty($d['image_url'])): ?>
    <img src="<?= e($d['image_url']) ?>" alt="<?= e($d['name']) ?>"
      style="width:100%;height:100%;object-fit:cover;border-radius:16px;">
  <?php else: ?>
    🌄
  <?php endif; ?>
</div>
<h1><?= e($d['name']) ?></h1>
<p style="color:#666;"><?= e($d['address']) ?></p>

<?php
// Tạo hiển thị sao thực từ avg_rating
$avgRating    = ($d['avg_rating'] !== null) ? (float)$d['avg_rating'] : null;
$reviewCount  = (int)($d['review_count'] ?? 0);
$fullStars    = $avgRating !== null ? (int) round($avgRating) : 0;
$starsHtml    = $avgRating !== null
    ? str_repeat('★', $fullStars) . str_repeat('☆', 5 - $fullStars)
    : '☆☆☆☆☆';
?>

<div class="meta-row">
  <div class="meta-item meta-rating">
    <?php if ($avgRating !== null): ?>
      <span class="meta-stars"><?= $starsHtml ?></span>
      <strong><?= number_format($avgRating, 1) ?></strong>/5
      <span class="meta-rev-count">(<?= $reviewCount ?> đánh giá)</span>
    <?php else: ?>
      <span class="meta-stars-empty">☆☆☆☆☆</span>
      <span style="color:#aaa;font-size:13px;">Chưa có đánh giá</span>
    <?php endif; ?>
  </div>
  <div class="meta-item">⏱ ~<?= e((string) $d['avg_visit_hours']) ?> giờ tham quan</div>
  <div class="meta-item">💰 Mức chi phí: <?= e(priceLevelVi($d['price_level'])) ?></div>
  <?php if ($d['tags']): ?>
    <div class="meta-item">🏷 <?= e($d['tags']) ?></div>
  <?php endif; ?>
</div>

<style>
.meta-rating {
  display: flex;
  align-items: center;
  gap: 6px;
  flex-wrap: wrap;
}
.meta-stars {
  color: #f59e0b;
  font-size: 18px;
  letter-spacing: 2px;
  line-height: 1;
}
.meta-stars-empty {
  color: #d1d5db;
  font-size: 18px;
  letter-spacing: 2px;
  line-height: 1;
}
.meta-rev-count {
  color: #9ca3af;
  font-size: 13px;
  font-weight: 400;
}
</style>


<div class="form-box">
  <h3>Giới thiệu</h3>
  <p><?= nl2br(e($d['description'])) ?></p>
</div>

<?php if (!empty($d['latitude']) && !empty($d['longitude'])): ?>
<!-- ══════════════════════════════════════
     WEATHER SAFETY WIDGET
══════════════════════════════════════ -->
<div id="weather-safety-widget" class="weather-loading">
  🌤️ Đang tải thông tin thời tiết & cảnh báo an toàn...
</div>

<script>
(function() {
  const lat = <?= (float)$d['latitude'] ?>;
  const lng = <?= (float)$d['longitude'] ?>;
  const hazardType = <?= json_encode($d['hazard_type'] ?? 'none') ?>;
  const safetyInstructions = <?= json_encode($d['safety_instructions'] ?? '') ?>;
  const widgetEl = document.getElementById('weather-safety-widget');

  fetch(`<?= url('/api/weather.php') ?>?lat=${lat}&lng=${lng}&hazard_type=${encodeURIComponent(hazardType)}`)
    .then(r => r.json())
    .then(data => {
      const w = data.weather || {};
      const hasWeatherData = w.source !== 'fallback' && w.temp !== null;

      // Xây dựng phần thời tiết hiện tại
      let weatherHtml = '';
      if (hasWeatherData) {
        const iconUrl = `https://openweathermap.org/img/wn/${w.icon}@2x.png`;
        weatherHtml = `
          <div class="weather-current">
            <img src="${iconUrl}" alt="${w.description}" class="weather-icon-big">
            <div class="weather-info">
              <div class="weather-temp">${w.temp}°C</div>
              <div class="weather-desc">${w.description} · Cảm giác như ${w.feels_like}°C</div>
            </div>
            <div class="weather-details">
              <span>💧 Độ ẩm: ${w.humidity}%</span>
              <span>💨 Gió: ${w.wind_speed} m/s</span>
              ${w.rain_1h > 0 ? `<span>🌧️ Mưa: ${w.rain_1h}mm/h</span>` : ''}
              <span class="season-badge season-${data.season}">${data.season === 'rainy' ? '🌧️ Mùa mưa' : '☀️ Mùa khô'}</span>
            </div>
          </div>`;
      }

      // Xây dựng phần cảnh báo
      const tips = (data.safety_tips || []).map(t => `<li>${t}</li>`).join('');

      // Thêm hướng dẫn từ DB nếu có
      let instructionsHtml = '';
      if (safetyInstructions) {
        instructionsHtml = `
          <div style="margin-top:12px;padding:12px 16px;background:rgba(0,0,0,.03);border-radius:10px;font-size:13px;line-height:1.6;color:#374151;">
            <strong>📋 Hướng dẫn từ chuyên gia bản địa:</strong><br>
            ${safetyInstructions}
          </div>`;
      }

      widgetEl.className = 'weather-widget';
      widgetEl.innerHTML = `
        ${weatherHtml}
        <div class="alert-banner alert-${data.alert_level}">
          <p class="alert-banner-title">${data.alert_title}</p>
          <p class="alert-banner-msg">${data.alert_message}</p>
          ${tips ? `<ul class="alert-tips">${tips}</ul>` : ''}
          ${instructionsHtml}
        </div>`;
    })
    .catch(() => {
      widgetEl.innerHTML = '<div style="padding:16px;color:#94a3b8;font-size:13px;">Không thể tải thông tin thời tiết.</div>';
      widgetEl.className = '';
    });
})();
</script>
<?php endif; ?>

<?php if (!empty($d['latitude']) && !empty($d['longitude'])): ?>
<div class="form-box">
  <h3>📍 Vị trí trên bản đồ</h3>
  <div id="destination-map" style="height:340px; border-radius:var(--radius); border:1px solid #ddd; z-index:1; margin-bottom:12px;"></div>
  <div style="display:flex;gap:10px;flex-wrap:wrap;">
    <a href="https://www.google.com/maps/dir/?api=1&destination=<?= (float)$d['latitude'] ?>,<?= (float)$d['longitude'] ?>" target="_blank"
       style="display:inline-flex;align-items:center;gap:6px;background:#1a56db;color:white;padding:8px 16px;border-radius:8px;text-decoration:none;font-size:13px;font-weight:600;">
      🧭 Chỉ đường Google Maps
    </a>
    <a href="https://www.google.com/maps?q=<?= (float)$d['latitude'] ?>,<?= (float)$d['longitude'] ?>" target="_blank"
       style="display:inline-flex;align-items:center;gap:6px;background:#f1f5f9;color:#334155;padding:8px 16px;border-radius:8px;text-decoration:none;font-size:13px;font-weight:600;">
      🗺️ Xem trên Google Maps
    </a>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const lat  = <?= (float)$d['latitude'] ?>;
  const lng  = <?= (float)$d['longitude'] ?>;
  const name = <?= json_encode($d['name']) ?>;
  const addr = <?= json_encode($d['address'] ?? '') ?>;
  const visitH = <?= (float)($d['avg_visit_hours'] ?? 1) ?>;

  const map = L.map('destination-map').setView([lat, lng], 15);

  // Tile layer đẹp hơn (CartoDB Positron)
  L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
    attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> © <a href="https://carto.com/">CARTO</a>',
    subdomains: 'abcd', maxZoom: 19
  }).addTo(map);

  // Vòng phạm vi khu vực tham quan
  L.circle([lat, lng], {
    radius: visitH * 150,
    color: '#2d6a4f', fillColor: '#d8f3dc', fillOpacity: 0.25, weight: 2, dashArray: '6,4'
  }).addTo(map);

  // Custom pin marker
  const pinIcon = L.divIcon({
    className: '',
    html: `<div style="width:40px;height:40px;border-radius:50% 50% 50% 0;background:#2d6a4f;border:4px solid white;box-shadow:0 4px 12px rgba(0,0,0,.3);transform:rotate(-45deg);"></div>`,
    iconSize: [40,40], iconAnchor: [20,40], popupAnchor: [0,-44], tooltipAnchor: [0,-44]
  });

  const marker = L.marker([lat, lng], { icon: pinIcon }).addTo(map);

  marker.bindTooltip(name, {
    permanent: true, direction: 'top', offset: [0,-44], className: 'map-label'
  });

  marker.bindPopup(`
    <div style="font-family:inherit;font-size:13px;min-width:180px;">
      <div style="background:#2d6a4f;color:white;border-radius:6px 6px 0 0;padding:8px 12px;margin:-1px -1px 10px;font-weight:700;">${name}</div>
      <div style="padding:0 2px;">
        ${addr ? `<div style="color:#666;font-size:12px;margin-bottom:8px;">📍 ${addr}</div>` : ''}
        <a href="https://www.google.com/maps/dir/?api=1&destination=${lat},${lng}" target="_blank"
           style="display:inline-block;background:#1a56db;color:white;padding:4px 10px;border-radius:4px;text-decoration:none;font-size:11px;font-weight:600;">
          🧭 Chỉ đường
        </a>
      </div>
    </div>`, { maxWidth: 240 }).openPopup();
});
</script>
<?php endif; ?>


<div class="cta">
  <a href="<?= url('/public/itinerary.php') ?>?prefill=<?= e($d['slug']) ?>" class="btn">🧭 Đưa vào lịch trình AI</a>
  <a href="<?= url('/public/chatbot.php') ?>?ask=<?= urlencode('Cho tôi biết thêm về ' . $d['name']) ?>"
    class="btn secondary">💬 Hỏi Chatbot AI về nơi này</a>
</div>

<!-- ══════════════════════════════════════
     SECTION: ĐÁNH GIÁ ĐIỂM ĐẾN
══════════════════════════════════════ -->
<style>
.dest-review-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 12px;
  margin: 36px 0 8px;
}
.dest-review-header h2 { margin: 0; font-size: 22px; color: var(--green-900); }
.dest-avg-pill {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: linear-gradient(135deg, #fef3c7, #fde68a);
  border: 1px solid #f59e0b;
  border-radius: 30px;
  padding: 6px 16px;
  font-size: 14px;
  font-weight: 700;
  color: #92400e;
}
.star-rating-input {
  display: flex;
  flex-direction: row-reverse;
  justify-content: flex-end;
  gap: 4px;
  margin: 6px 0 14px;
}
.star-rating-input input { display: none; }
.star-rating-input label {
  font-size: 30px;
  color: #ddd;
  cursor: pointer;
  transition: color .15s, transform .1s;
  line-height: 1;
}
.star-rating-input label:hover,
.star-rating-input label:hover ~ label,
.star-rating-input input:checked ~ label { color: #f59e0b; transform: scale(1.1); }
.dest-review-list { display: flex; flex-direction: column; gap: 14px; margin-top: 20px; }
.dest-review-card {
  background: white;
  border-radius: 14px;
  padding: 18px 22px;
  box-shadow: 0 2px 10px rgba(0,0,0,.06);
  border-left: 4px solid var(--green-500);
  animation: fadeSlideUp .35s ease both;
}
@keyframes fadeSlideUp {
  from { opacity:0; transform:translateY(12px); }
  to   { opacity:1; transform:translateY(0); }
}
.dest-review-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 6px;
  margin-bottom: 8px;
}
.dest-reviewer {
  display: flex;
  align-items: center;
  gap: 10px;
}
.dest-rev-avatar {
  width: 36px; height: 36px;
  border-radius: 50%;
  background: linear-gradient(135deg, var(--green-700), var(--orange-500));
  color: white;
  font-weight: 700;
  font-size: 15px;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}
.dest-rev-name { font-weight: 700; font-size: 14px; }
.dest-rev-date { font-size: 12px; color: #aaa; }
.dest-star-display { color: #f59e0b; font-size: 15px; letter-spacing: 1px; }
.dest-rev-comment { color: #444; font-size: 14px; line-height: 1.6; margin: 0; }
.dest-rev-empty { text-align:center; padding:32px; color:#bbb; font-size:14px; }
.dest-load-more {
  width:100%; padding:10px;
  border:2px dashed #ddd; border-radius:10px;
  background:transparent; color:#888; font-size:13px;
  cursor:pointer; margin-top:12px;
  transition: border-color .2s, color .2s;
}
.dest-load-more:hover { border-color:var(--green-500); color:var(--green-700); }
</style>

<div class="dest-review-header">
  <h2>⭐ Đánh giá địa điểm</h2>
  <div class="dest-avg-pill" id="destAvgPill" style="display:none">
    ⭐ <span id="destAvgScore">0</span>/5 &nbsp;·&nbsp; <span id="destTotalCount">0</span> đánh giá
  </div>
</div>

<?php $user = currentUser(); ?>
<?php if ($user): ?>
<div class="form-box" id="destReviewFormBox">
  <h3 style="margin-top:0; color:var(--green-900);">✍️ Viết đánh giá của bạn</h3>
  <form id="destReviewForm">
    <input type="hidden" name="destination_id" value="<?= (int)$d['id'] ?>">
    <div class="form-group">
      <label>Số sao <span style="color:red">*</span></label>
      <div class="star-rating-input">
        <?php for ($i = 5; $i >= 1; $i--): ?>
        <input type="radio" id="dstar<?= $i ?>" name="rating" value="<?= $i ?>" <?= $i === 5 ? 'checked' : '' ?>>
        <label for="dstar<?= $i ?>" title="<?= $i ?> sao">★</label>
        <?php endfor; ?>
      </div>
    </div>
    <div class="form-group">
      <label for="destComment">Nhận xét <span style="font-weight:400;color:#999;">(không bắt buộc)</span></label>
      <textarea id="destComment" name="comment" rows="3"
        placeholder="Chia sẻ cảm nhận về <?= e($d['name']) ?>..."
        style="resize:vertical;font-family:inherit;"></textarea>
      <div style="text-align:right;font-size:12px;color:#aaa;margin-top:3px;">
        <span id="destCharCount">0</span>/1000
      </div>
    </div>
    <button type="submit" class="btn" id="destSubmitBtn">🚀 Gửi đánh giá</button>
    <div id="destReviewMsg" style="margin-top:12px;font-size:14px;"></div>
  </form>
</div>
<?php else: ?>
<div class="form-box" style="text-align:center;padding:28px;">
  <p style="color:#666;margin:0 0 14px;">🔐 <a href="<?= url('/public/login.php') ?>">Đăng nhập</a> để gửi đánh giá về địa điểm này.</p>
</div>
<?php endif; ?>

<div class="dest-review-list" id="destReviewList">
  <div class="dest-rev-empty">⏳ Đang tải đánh giá...</div>
</div>
<button class="dest-load-more" id="destLoadMoreBtn" style="display:none" onclick="destLoadMore()">Xem thêm...</button>

<!-- ── Edit Review Modal (Destination) ── -->
<style>
.dest-card-actions { display:flex; gap:8px; margin-top:10px; }
.btn-edit-rev, .btn-del-rev {
  display:inline-flex; align-items:center; gap:4px;
  padding:5px 11px; border-radius:8px; font-size:12px; font-weight:700;
  border:none; cursor:pointer; transition:all .15s;
}
.btn-edit-rev { background:#fef3c7; color:#92400e; }
.btn-edit-rev:hover { background:#fde68a; }
.btn-del-rev  { background:#fee2e2; color:#991b1b; }
.btn-del-rev:hover  { background:#fca5a5; }
.btn-admin-del-rev { background:linear-gradient(135deg,#fee2e2,#fecaca); color:#991b1b; border:1px solid #fca5a5; }
.btn-admin-del-rev:hover { background:#fca5a5; }
.dest-admin-badge { background:#fef3c7; color:#92400e; font-size:10px; padding:1px 6px; border-radius:6px; font-weight:700; }
.rev-modal-overlay {
  position:fixed; inset:0; background:rgba(0,0,0,.5);
  z-index:9999; display:none; align-items:center; justify-content:center;
}
.rev-modal-overlay.open { display:flex; }
.rev-modal {
  background:white; border-radius:18px; padding:26px 30px;
  width:100%; max-width:460px; box-shadow:0 20px 60px rgba(0,0,0,.25);
  animation:revModalIn .25s ease;
}
@keyframes revModalIn { from{opacity:0;transform:translateY(-18px)} to{opacity:1;transform:translateY(0)} }
.rev-modal h3 { margin:0 0 4px; color:var(--green-900); font-size:18px; }
.rev-modal .sub { color:#888; font-size:13px; margin-bottom:16px; }
.star-rating-input { display:flex; flex-direction:row-reverse; justify-content:flex-end; gap:4px; margin:6px 0 14px; }
.star-rating-input input { display:none; }
.star-rating-input label { font-size:28px; color:#ddd; cursor:pointer; transition:color .15s,transform .1s; line-height:1; }
.star-rating-input label:hover,
.star-rating-input label:hover ~ label,
.star-rating-input input:checked ~ label { color:#f59e0b; transform:scale(1.1); }
.rev-modal-footer { display:flex; gap:10px; justify-content:flex-end; margin-top:14px; }
.rev-btn-cancel { background:#f3f4f6; color:#374151; border:none; border-radius:8px; padding:9px 16px; font-size:14px; font-weight:600; cursor:pointer; }
.rev-btn-cancel:hover { background:#e5e7eb; }
/* Admin delete reason modal (destination) */
.dest-admin-del-overlay {
  position:fixed; inset:0; background:rgba(0,0,0,.55);
  z-index:10000; display:none; align-items:center; justify-content:center;
}
.dest-admin-del-overlay.open { display:flex; }
.dest-admin-del-modal {
  background:white; border-radius:18px; padding:28px 32px;
  width:100%; max-width:480px; box-shadow:0 20px 60px rgba(0,0,0,.3);
  animation:destAdmDelIn .22s ease;
}
@keyframes destAdmDelIn { from{opacity:0;transform:translateY(-16px) scale(.97)} to{opacity:1;transform:translateY(0) scale(1)} }
.dest-admin-del-modal h3 { margin:0 0 4px; color:#991b1b; font-size:18px; display:flex; align-items:center; gap:8px; }
.dest-admin-del-modal .sub { color:#888; font-size:13px; margin-bottom:16px; }
.dest-admin-del-modal .sub strong { color:#111; }
.dest-admin-del-modal .reason-presets { display:flex; flex-wrap:wrap; gap:6px; margin-bottom:12px; }
.dest-reason-preset-btn {
  background:#f3f4f6; border:1.5px solid #e5e7eb; border-radius:8px;
  padding:5px 12px; font-size:12px; font-weight:600; color:#555;
  cursor:pointer; transition:all .15s;
}
.dest-reason-preset-btn:hover { border-color:#ef4444; color:#dc2626; background:#fef2f2; }
.dest-admin-del-modal textarea {
  width:100%; border:1.5px solid #e5e7eb; border-radius:10px;
  padding:10px 12px; font-size:14px; font-family:inherit;
  resize:vertical; min-height:80px; box-sizing:border-box;
  transition:border-color .2s;
}
.dest-admin-del-modal textarea:focus { outline:none; border-color:#ef4444; }
.dest-admin-del-footer { display:flex; gap:10px; justify-content:flex-end; margin-top:14px; }
.dest-adm-cancel { background:#f3f4f6; color:#374151; border:none; border-radius:8px; padding:9px 18px; font-size:14px; font-weight:600; cursor:pointer; }
.dest-adm-cancel:hover { background:#e5e7eb; }
.dest-adm-confirm {
  background:linear-gradient(135deg,#dc2626,#b91c1c); color:white;
  border:none; border-radius:8px; padding:9px 18px; font-size:14px; font-weight:700; cursor:pointer;
  transition:opacity .15s;
}
.dest-adm-confirm:disabled { opacity:.55; cursor:not-allowed; }
</style>

<div class="rev-modal-overlay" id="destEditOverlay" onclick="closeDRevModal(event)">
  <div class="rev-modal">
    <h3>✏️ Chỉnh sửa đánh giá</h3>
    <p class="sub">Cập nhật đánh giá của bạn về địa điểm này.</p>
    <form id="destEditForm">
      <input type="hidden" id="destEditId" name="id" value="">
      <div class="form-group">
        <label>Số sao <span style="color:red">*</span></label>
        <div class="star-rating-input" id="destEditStars">
          <?php for ($i = 5; $i >= 1; $i--): ?>
          <input type="radio" id="destr<?= $i ?>" name="rating" value="<?= $i ?>">
          <label for="destr<?= $i ?>" title="<?= $i ?> sao">★</label>
          <?php endfor; ?>
        </div>
      </div>
      <div class="form-group">
        <label for="destEditComment">Nhận xét</label>
        <textarea id="destEditComment" name="comment" rows="3"
          style="resize:vertical;font-family:inherit;"
          placeholder="Chia sẻ cảm nhận của bạn..."></textarea>
        <div style="text-align:right;font-size:12px;color:#aaa;margin-top:3px;">
          <span id="destEditCharCnt">0</span>/1000
        </div>
      </div>
      <div id="destEditMsg" style="margin-bottom:10px;font-size:14px;"></div>
      <div class="rev-modal-footer">
        <button type="button" class="rev-btn-cancel" onclick="closeDRevModal()">Hủy</button>
        <button type="submit" class="btn" id="destEditSubmit">💾 Lưu thay đổi</button>
      </div>
    </form>
  </div>
</div>

<!-- Admin Delete Reason Modal (Destination) -->
<div class="dest-admin-del-overlay" id="destAdminDelOverlay" onclick="if(event.target===this) closeDAdmDel()">
  <div class="dest-admin-del-modal">
    <h3>🛡️ Xóa đánh giá (Admin)</h3>
    <p class="sub">Bạn đang xóa đánh giá của <strong id="destAdmDelName"></strong>. Vui lòng nhập lý do xóa — lý do sẽ được lưu vào lịch sử.</p>
    <div class="reason-presets">
      <button type="button" class="dest-reason-preset-btn" onclick="setDAdmReason('Nội dung vi phạm quy định cộng đồng')">🚫 Vi phạm quy định</button>
      <button type="button" class="dest-reason-preset-btn" onclick="setDAdmReason('Nội dung spam hoặc quảng cáo')">📢 Spam/Quảng cáo</button>
      <button type="button" class="dest-reason-preset-btn" onclick="setDAdmReason('Nội dung không phù hợp hoặc xúc phạm')">⚠️ Không phù hợp</button>
      <button type="button" class="dest-reason-preset-btn" onclick="setDAdmReason('Đánh giá sai sự thật, gây hiểu lầm')">❌ Sai sự thật</button>
      <button type="button" class="dest-reason-preset-btn" onclick="setDAdmReason('Trùng lặp, đánh giá nhiều lần')">🔁 Trùng lặp</button>
    </div>
    <textarea id="destAdmDelReason" placeholder="VD: Nội dung vi phạm quy định, spam, không phù hợp..."></textarea>
    <div id="destAdmDelErr" style="color:#dc2626;font-size:13px;margin-top:8px;min-height:18px;"></div>
    <div class="dest-admin-del-footer">
      <button class="dest-adm-cancel" onclick="closeDAdmDel()">Hủy</button>
      <button class="dest-adm-confirm" id="destAdmDelConfirm">🗑️ Xác nhận xóa</button>
    </div>
  </div>
</div>

<script>
(function() {
  const DEST_ID   = <?= (int)$d['id'] ?>;
  const API_BASE  = '<?= url('/api') ?>';
  let offset = 0, limit = 8, totalLoaded = 0, grandTotal = 0;
  let _destIsAdmin = false;
  let _destAdmDelId = null;

  function starsHtml(n) {
    let s = '';
    for (let i = 1; i <= 5; i++) s += i <= n ? '★' : '☆';
    return s;
  }
  function timeSince(str) {
    const diff = (Date.now() - new Date(str).getTime()) / 1000;
    if (diff < 60)    return 'vừa xong';
    if (diff < 3600)  return Math.floor(diff/60) + ' phút trước';
    if (diff < 86400) return Math.floor(diff/3600) + ' giờ trước';
    return Math.floor(diff/86400) + ' ngày trước';
  }

  async function loadReviews(reset = false) {
    if (reset) { offset = 0; totalLoaded = 0; document.getElementById('destReviewList').innerHTML = ''; }
    const res  = await fetch(`${API_BASE}/review_get.php?destination_id=${DEST_ID}&limit=${limit}&offset=${offset}`);
    const data = await res.json();
    if (!data.success) return;

    _destIsAdmin = !!data.is_admin;
    grandTotal = data.total;
    const list = document.getElementById('destReviewList');

    if (data.avg_rating !== null) {
      document.getElementById('destAvgScore').textContent  = data.avg_rating.toFixed(1);
      document.getElementById('destTotalCount').textContent = data.total;
      document.getElementById('destAvgPill').style.display  = 'inline-flex';
    }

    if (data.reviews.length === 0 && offset === 0) {
      list.innerHTML = `<div class="dest-rev-empty">💬 Chưa có đánh giá nào. Hãy là người đầu tiên!</div>`;
      document.getElementById('destLoadMoreBtn').style.display = 'none';
      return;
    }

    data.reviews.forEach((r, idx) => {
      const card = document.createElement('div');
      card.className = 'dest-review-card';
      card.style.animationDelay = (idx * 0.04) + 's';

      const actionsHtml = r.is_mine ? `
        <div class="dest-card-actions">
          <button class="btn-edit-rev" onclick="openDRevEdit(${r.review_id}, ${r.rating}, ${JSON.stringify(r.comment || '')})">✏️ Sửa</button>
          <button class="btn-del-rev" onclick="deleteDRev(${r.review_id})">🗑️ Xóa</button>
        </div>` : (_destIsAdmin ? `
        <div class="dest-card-actions">
          <button class="btn-admin-del-rev" onclick="openDAdmDel(${r.review_id}, '${r.display_name.replace(/'/g, "\\'")}')">🛡️ Xóa <span class="dest-admin-badge">Admin</span></button>
        </div>` : '');

      card.innerHTML = `
        <div class="dest-review-top">
          <div class="dest-reviewer">
            <div class="dest-rev-avatar">${r.display_name.charAt(0).toUpperCase()}</div>
            <div>
              <div class="dest-rev-name">${r.display_name}${r.is_mine ? ' <span style="background:#d8f3dc;color:#1b4332;font-size:10px;padding:1px 6px;border-radius:6px;font-weight:700;">Của bạn</span>' : ''}</div>
              <div class="dest-rev-date">${timeSince(r.created_at)}</div>
            </div>
          </div>
          <div class="dest-star-display">${starsHtml(r.rating)}</div>
        </div>
        ${r.comment
          ? `<p class="dest-rev-comment">"${r.comment}"</p>`
          : `<p class="dest-rev-comment" style="color:#bbb;font-style:italic;">Không có nhận xét.</p>`}
        ${actionsHtml}
      `;
      list.appendChild(card);
    });

    offset      += data.reviews.length;
    totalLoaded += data.reviews.length;
    document.getElementById('destLoadMoreBtn').style.display = totalLoaded < grandTotal ? 'block' : 'none';
  }

  window.destLoadMore = () => loadReviews(false);

  // Submit new review
  const form = document.getElementById('destReviewForm');
  if (form) {
    document.getElementById('destComment')?.addEventListener('input', function() {
      document.getElementById('destCharCount').textContent = this.value.length;
    });
    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      const btn = document.getElementById('destSubmitBtn');
      const msg = document.getElementById('destReviewMsg');
      btn.disabled = true; btn.textContent = '⏳ Đang gửi...';
      const res  = await fetch(`${API_BASE}/review_submit.php`, { method:'POST', body: new FormData(form) });
      const data = await res.json();
      if (data.success) {
        msg.innerHTML = `<div style="background:#d8f3dc;color:#1b4332;padding:12px 16px;border-radius:10px;">✅ ${data.message}</div>`;
        form.reset();
        document.getElementById('destCharCount').textContent = '0';
        // Reset sao về 5
        const star5 = document.querySelector('#destReviewForm input[name="rating"][value="5"]');
        if (star5) star5.checked = true;
        setTimeout(() => {
          msg.innerHTML = '';
          btn.disabled = false;
          btn.textContent = '🚀 Gửi đánh giá';
          loadReviews(true);
        }, 1500);
      } else {
        msg.innerHTML = `<div style="background:#fee2e2;color:#991b1b;padding:12px 16px;border-radius:10px;">❌ ${data.error}</div>`;
        btn.disabled = false; btn.textContent = '🚀 Gửi đánh giá';
      }
    });
  }

  // Edit modal
  window.openDRevEdit = function(reviewId, currentRating, currentComment) {
    document.getElementById('destEditId').value = reviewId;
    document.getElementById('destEditComment').value = currentComment || '';
    document.getElementById('destEditCharCnt').textContent = (currentComment || '').length;
    const radio = document.querySelector(`#destEditStars input[value="${currentRating}"]`);
    if (radio) radio.checked = true;
    document.getElementById('destEditMsg').innerHTML = '';
    document.getElementById('destEditOverlay').classList.add('open');
    document.body.style.overflow = 'hidden';
  };

  window.closeDRevModal = function(e) {
    if (e && e.target !== document.getElementById('destEditOverlay')) return;
    document.getElementById('destEditOverlay').classList.remove('open');
    document.body.style.overflow = '';
  };

  document.getElementById('destEditComment')?.addEventListener('input', function() {
    document.getElementById('destEditCharCnt').textContent = this.value.length;
  });

  document.getElementById('destEditForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = document.getElementById('destEditSubmit');
    const msg = document.getElementById('destEditMsg');
    btn.disabled = true; btn.textContent = '⏳ Đang lưu...';
    const res  = await fetch(`${API_BASE}/review_edit.php`, { method:'POST', body: new FormData(document.getElementById('destEditForm')) });
    const data = await res.json();
    if (data.success) {
      msg.innerHTML = `<div style="background:#d8f3dc;color:#1b4332;padding:10px;border-radius:8px;">✅ ${data.message}</div>`;
      setTimeout(() => {
        document.getElementById('destEditOverlay').classList.remove('open');
        document.body.style.overflow = '';
        loadReviews(true);
      }, 800);
    } else {
      msg.innerHTML = `<div style="background:#fee2e2;color:#991b1b;padding:10px;border-radius:8px;">❌ ${data.error}</div>`;
      btn.disabled = false; btn.textContent = '💾 Lưu thay đổi';
    }
  });

  // Delete review (user's own - simple confirm)
  window.deleteDRev = async function(reviewId) {
    if (!confirm('Bạn có chắc muốn xóa đánh giá này không?')) return;
    const fd = new FormData();
    fd.append('id', reviewId);
    const res  = await fetch(`${API_BASE}/review_delete.php`, { method:'POST', body: fd });
    const data = await res.json();
    if (data.success) {
      loadReviews(true);
    } else {
      alert('❌ ' + (data.error || 'Không thể xóa.'));
    }
  };

  // Admin delete with reason
  window.openDAdmDel = function(reviewId, userName) {
    _destAdmDelId = reviewId;
    document.getElementById('destAdmDelName').textContent = userName;
    document.getElementById('destAdmDelReason').value = '';
    document.getElementById('destAdmDelErr').textContent = '';
    document.getElementById('destAdmDelConfirm').disabled = false;
    document.getElementById('destAdmDelConfirm').textContent = '🗑️ Xác nhận xóa';
    document.getElementById('destAdminDelOverlay').classList.add('open');
    document.body.style.overflow = 'hidden';
    setTimeout(() => document.getElementById('destAdmDelReason').focus(), 100);
  };

  window.closeDAdmDel = function() {
    document.getElementById('destAdminDelOverlay').classList.remove('open');
    document.body.style.overflow = '';
    _destAdmDelId = null;
  };

  window.setDAdmReason = function(text) {
    document.getElementById('destAdmDelReason').value = text;
    document.getElementById('destAdmDelErr').textContent = '';
    document.getElementById('destAdmDelReason').focus();
  };

  document.getElementById('destAdmDelConfirm')?.addEventListener('click', async function() {
    const reason = document.getElementById('destAdmDelReason').value.trim();
    const errEl  = document.getElementById('destAdmDelErr');

    if (!reason) {
      errEl.textContent = '⚠️ Vui lòng nhập lý do xóa.';
      document.getElementById('destAdmDelReason').focus();
      return;
    }
    if (reason.length < 10) {
      errEl.textContent = '⚠️ Lý do phải có ít nhất 10 ký tự.';
      return;
    }

    this.disabled = true;
    this.textContent = '⏳ Đang xóa...';
    errEl.textContent = '';

    const fd = new FormData();
    fd.append('id', _destAdmDelId);
    fd.append('reason', reason);

    try {
      const res  = await fetch(`${API_BASE}/review_delete.php`, { method:'POST', body: fd });
      const data = await res.json();
      if (data.success) {
        closeDAdmDel();
        loadReviews(true);
      } else {
        errEl.textContent = '❌ ' + (data.error || 'Lỗi không xác định');
        this.disabled = false;
        this.textContent = '🗑️ Xác nhận xóa';
      }
    } catch(e) {
      errEl.textContent = '❌ Lỗi kết nối mạng.';
      this.disabled = false;
      this.textContent = '🗑️ Xác nhận xóa';
    }
  });

  // Ctrl+Enter để xác nhận nhanh
  document.getElementById('destAdmDelReason')?.addEventListener('keydown', function(e) {
    if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) {
      document.getElementById('destAdmDelConfirm').click();
    }
  });

  loadReviews(true);
})();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
