<?php
require_once __DIR__ . '/../includes/functions.php';

$slug = $_GET['slug'] ?? '';
$d = getDestinationBySlug($slug);

if (!$d) {
  http_response_code(404);
  $pageTitle = __('dest_404_title');
  include __DIR__ . '/../includes/header.php';
  echo '<h1>' . __('dest_404_msg') . '</h1>';
  echo '<p><a href="' . url('/public/destinations.php') . '">' . __('dest_back_list') . '</a></p>';
  include __DIR__ . '/../includes/footer.php';
  exit;
}

$pageTitle = $d['name'] . ' - Đắk Lắk Travel AI';
$metaDescription = $d['short_desc'] ?? '';
include __DIR__ . '/../includes/header.php';
?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<p><a href="<?= url('/public/destinations.php') ?>"><?= __('dest_back_list') ?></a></p>

<div class="detail-hero">
  <?php if (!empty($d['image_url'])): ?>
    <img src="<?= e($d['image_url']) ?>" alt="<?= e($d['name']) ?>"
      style="width:100%;height:100%;object-fit:cover;border-radius:16px;" onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1542323631-0d3ab5d496a8?q=80&w=1000&auto=format&fit=crop';">
  <?php else: ?>
    🌄
  <?php endif; ?>
</div>
<?php
$user = currentUser();
$isSaved = false;
if ($user) {
    $stmt = getDB()->prepare("SELECT id FROM wishlists WHERE user_id = ? AND destination_id = ?");
    $stmt->execute([$user['id'], $d['id']]);
    $isSaved = (bool)$stmt->fetch();
}
?>
<div style="display:flex; justify-content:space-between; align-items:flex-start; gap:20px;">
  <h1 style="margin:0;"><?= e($d['name']) ?></h1>
  <button id="wishlistBtn" onclick="toggleWishlist(<?= (int)$d['id'] ?>)" 
          style="background:none; border:none; cursor:pointer; font-size:28px; padding:0; line-height:1.2; transition: transform 0.2s, color 0.2s; color: <?= $isSaved ? '#ef4444' : '#ccc' ?>;"
          title="<?= $isSaved ? __('remove_wishlist') : __('save_wishlist') ?>">
    <?= $isSaved ? '❤️' : '🤍' ?>
  </button>
</div>
<p style="color:#666; margin-top:8px;"><?= e($d['address']) ?></p>

<script>
async function toggleWishlist(destId) {
    <?php if (!$user): ?>
    alert('<?= __('dest_login_save') ?>');
    window.location.href = '<?= url('/public/login.php') ?>';
    return;
    <?php endif; ?>
    
    const btn = document.getElementById('wishlistBtn');
    btn.style.transform = 'scale(0.8)';
    
    const fd = new FormData();
    fd.append('destination_id', destId);
    
    try {
        const res = await fetch('<?= url('/api/toggle_wishlist.php') ?>', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            if (data.action === 'added') {
                btn.innerHTML = '❤️';
                btn.style.color = '#ef4444';
                btn.title = '<?= __('remove_wishlist') ?>';
            } else {
                btn.innerHTML = '🤍';
                btn.style.color = '#ccc';
                btn.title = '<?= __('save_wishlist') ?>';
            }
        } else {
            alert(data.error || '<?= __('dest_error_occurred') ?>');
        }
    } catch (e) {
        alert('<?= __('dest_network_error') ?>');
    }
    
    setTimeout(() => { btn.style.transform = 'scale(1)'; }, 200);
}
</script>

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
      <span class="meta-rev-count">(<?= $reviewCount ?> <?= __('dest_reviews_unit') ?>)</span>
    <?php else: ?>
      <span class="meta-stars-empty">☆☆☆☆☆</span>
      <span style="color:#aaa;font-size:13px;"><?= __('dest_no_reviews_yet') ?></span>
    <?php endif; ?>
  </div>
  <div class="meta-item">⏱ ~<?= e((string) $d['avg_visit_hours']) ?>h <?= __('avg_visit_time') ?></div>
  <div class="meta-item">💰 <?= __('price_level') ?>: <?= e(priceLevelVi($d['price_level'])) ?></div>
  <?php if ($d['tags']): ?>
    <div class="meta-item">🏷 <?= e($d['tags']) ?></div>
  <?php endif; ?>
</div>

<?php
// Hiển thị cảnh báo an toàn theo loại địa hình (category_id)
$warningHtml = '';
switch ((int)$d['category_id']) {
    case 1: // Thác nước
        $warningHtml = '<div style="background:#fef2f2; border:1px solid #fecaca; border-left:4px solid #ef4444; padding:16px; border-radius:8px; margin:20px 0; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
            <strong style="color:#b91c1c; display:flex; align-items:center; gap:6px;">' . __('warning_waterfall_title') . '</strong>
            <ul style="margin:10px 0 0; padding-left:22px; color:#991b1b; font-size:14px; line-height:1.6;">
                <li>' . __('warning_waterfall_1') . '</li>
                <li>' . __('warning_waterfall_2') . '</li>
                <li>' . __('warning_waterfall_3') . '</li>
            </ul>
        </div>';
        break;
    case 2: // Hồ
        $warningHtml = '<div style="background:#f0f9ff; border:1px solid #bae6fd; border-left:4px solid #3b82f6; padding:16px; border-radius:8px; margin:20px 0; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
            <strong style="color:#1d4ed8; display:flex; align-items:center; gap:6px;">' . __('warning_lake_title') . '</strong>
            <ul style="margin:10px 0 0; padding-left:22px; color:#1e40af; font-size:14px; line-height:1.6;">
                <li>' . __('warning_lake_1') . '</li>
                <li>' . __('warning_lake_2') . '</li>
                <li>' . __('warning_lake_3') . '</li>
            </ul>
        </div>';
        break;
    case 3: // Buôn làng
        $warningHtml = '<div style="background:#fdf4ff; border:1px solid #f5d0fe; border-left:4px solid #d946ef; padding:16px; border-radius:8px; margin:20px 0; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
            <strong style="color:#a21caf; display:flex; align-items:center; gap:6px;">' . __('warning_village_title') . '</strong>
            <ul style="margin:10px 0 0; padding-left:22px; color:#86198f; font-size:14px; line-height:1.6;">
                <li>' . __('warning_village_1') . '</li>
                <li>' . __('warning_village_2') . '</li>
                <li>' . __('warning_village_3') . '</li>
            </ul>
        </div>';
        break;
    case 4: // Vườn quốc gia
        $warningHtml = '<div style="background:#f0fdf4; border:1px solid #bbf7d0; border-left:4px solid #22c55e; padding:16px; border-radius:8px; margin:20px 0; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
            <strong style="color:#15803d; display:flex; align-items:center; gap:6px;">' . __('warning_forest_title') . '</strong>
            <ul style="margin:10px 0 0; padding-left:22px; color:#166534; font-size:14px; line-height:1.6;">
                <li>' . __('warning_forest_1') . '</li>
                <li>' . __('warning_forest_2') . '</li>
                <li>' . __('warning_forest_3') . '</li>
            </ul>
        </div>';
        break;
}
echo $warningHtml;
?>

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
  <h3><?= __('about_dest') ?></h3>
  <p><?= nl2br(e($d['description'])) ?></p>
</div>

<?php
// ── VIRTUAL TOUR 360° CTA ──
if (!empty($d['virtual_tour_enabled'])):
    $vtStmt = getDB()->prepare("SELECT COUNT(*) FROM virtual_tour_scenes WHERE destination_id = ?");
    $vtStmt->execute([$d['id']]);
    $vtSceneCount = (int)$vtStmt->fetchColumn();
    if ($vtSceneCount > 0):
?>
<a href="<?= url('/public/virtual-tour.php?id=' . (int)$d['id']) ?>" class="vt-tour-cta" id="vt-tour-cta">
  <div class="vt-tour-cta-icon">🌐</div>
  <div class="vt-tour-cta-content">
    <h3><?= __('vt_cta_title') ?></h3>
    <p><?= __('vt_cta_desc') ?> (<?= $vtSceneCount ?> <?= __('vt_scenes_count') ?>)</p>
  </div>
  <div class="vt-tour-cta-arrow">→</div>
</a>
<style>
/* Virtual Tour CTA inline styles */
.vt-tour-cta {
  display: flex; align-items: center; gap: 12px;
  background: linear-gradient(135deg, #1e1b4b, #312e81);
  border: 1px solid rgba(99,102,241,0.3); border-radius: 16px;
  padding: 20px 24px; margin: 20px 0;
  cursor: pointer; text-decoration: none; color: #fff;
  transition: all 0.3s; position: relative; overflow: hidden;
}
.vt-tour-cta::before {
  content: ''; position: absolute; top: -50%; left: -50%;
  width: 200%; height: 200%;
  background: radial-gradient(circle, rgba(99,102,241,0.15) 0%, transparent 60%);
  animation: vtCtaGlow 4s ease-in-out infinite;
}
@keyframes vtCtaGlow {
  0%, 100% { transform: translate(0, 0); }
  50% { transform: translate(10%, 10%); }
}
.vt-tour-cta:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 30px rgba(99,102,241,0.3);
  border-color: rgba(99,102,241,0.5);
}
.vt-tour-cta-icon { font-size: 36px; flex-shrink: 0; position: relative; z-index: 1; }
.vt-tour-cta-content { position: relative; z-index: 1; }
.vt-tour-cta-content h3 { margin: 0 0 4px; font-size: 18px; font-weight: 700; }
.vt-tour-cta-content p { margin: 0; font-size: 13px; color: #a5b4fc; }
.vt-tour-cta-arrow {
  margin-left: auto; font-size: 22px; position: relative; z-index: 1;
  transition: transform 0.2s;
}
.vt-tour-cta:hover .vt-tour-cta-arrow { transform: translateX(4px); }
</style>
<?php
    endif;
endif;
?>

<?php if (!empty($d['latitude']) && !empty($d['longitude'])): ?>
<div class="form-box" id="dest-weather-box" style="display:none; background: linear-gradient(135deg, #f0f9ff, #e0f2fe); border: 1px solid #bae6fd; box-shadow: 0 4px 15px rgba(56, 189, 248, 0.1);">
  <h3 style="margin-top:0; color:#0369a1; display:flex; align-items:center; gap:8px;"><?= __('dest_current_weather') ?></h3>
  <div style="display:flex; align-items:center; gap:24px; margin-top:12px;">
      <div id="dw-temp" style="font-size:38px; font-weight:800; color:#0284c7; line-height:1;">--°C</div>
      <div style="border-left: 2px solid #bae6fd; padding-left: 20px;">
          <div id="dw-status" style="font-size:16px; font-weight:700; color:#0369a1; margin-bottom:6px;"><?= __('dest_weather_loading') ?></div>
          <div id="dw-wind" style="font-size:13px; color:#0c4a6e; background: #fff; padding: 2px 8px; border-radius: 12px; display:inline-block;"><?= __('dest_wind') ?>: -- km/h</div>
      </div>
  </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', async function() {
    const lat = <?= (float)$d['latitude'] ?>;
    const lng = <?= (float)$d['longitude'] ?>;
    try {
        const res = await fetch(`https://api.open-meteo.com/v1/forecast?latitude=${lat}&longitude=${lng}&current_weather=true`);
        const data = await res.json();
        if (data.current_weather) {
            document.getElementById('dest-weather-box').style.display = 'block';
            document.getElementById('dw-temp').textContent = data.current_weather.temperature + '°C';
            document.getElementById('dw-wind').textContent = '💨 <?= __('dest_wind') ?>: ' + data.current_weather.windspeed + ' km/h';
            
            let code = data.current_weather.weathercode;
            let status = '<?= __('dest_weather_clear_sky') ?>';
            let icon = '☀️';
            if (code >= 95) { status = '<?= __('dest_weather_has_storm') ?>'; icon = '⛈️'; }
            else if (code >= 61) { status = '<?= __('dest_weather_has_rain') ?>'; icon = '🌧️'; }
            else if (code >= 51) { status = '<?= __('dest_weather_drizzle') ?>'; icon = '🌦️'; }
            else if (code >= 45) { status = '<?= __('dest_weather_fog') ?>'; icon = '🌫️'; }
            else if (code >= 1) { status = '<?= __('dest_weather_has_cloud') ?>'; icon = '⛅'; }
            
            document.getElementById('dw-status').innerHTML = icon + ' ' + status;
        }
    } catch(e) {
        console.error("Lỗi tải thời tiết", e);
    }
});
</script>
<?php endif; ?>

<?php if (!empty($d['latitude']) && !empty($d['longitude'])): ?>
<div class="form-box">
  <h3>📍 <?= __('location') ?></h3>
  <div id="destination-map" style="height:340px; border-radius:var(--radius); border:1px solid #ddd; z-index:1; margin-bottom:12px;"></div>
  <div style="display:flex;gap:10px;flex-wrap:wrap;">
    <a href="https://www.google.com/maps/dir/?api=1&destination=<?= (float)$d['latitude'] ?>,<?= (float)$d['longitude'] ?>" target="_blank"
       style="display:inline-flex;align-items:center;gap:6px;background:#1a56db;color:white;padding:8px 16px;border-radius:8px;text-decoration:none;font-size:13px;font-weight:600;">
      <?= __('dest_google_directions') ?>
    </a>
    <a href="https://www.google.com/maps?q=<?= (float)$d['latitude'] ?>,<?= (float)$d['longitude'] ?>" target="_blank"
       style="display:inline-flex;align-items:center;gap:6px;background:#f1f5f9;color:#334155;padding:8px 16px;border-radius:8px;text-decoration:none;font-size:13px;font-weight:600;">
      <?= __('dest_google_view') ?>
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
          🧭 <?= __('directions') ?>
        </a>
      </div>
    </div>`, { maxWidth: 240 }).openPopup();
});
</script>
<?php endif; ?>


<div class="cta">
  <a href="<?= url('/public/itinerary.php') ?>?prefill=<?= e($d['slug']) ?>" class="btn"><?= __('dest_add_to_itinerary') ?></a>
  <a href="<?= url('/public/chatbot.php') ?>?ask=<?= urlencode(__('dest_ask_chatbot_prefix') . ' ' . $d['name']) ?>"
    class="btn secondary"><?= __('dest_ask_chatbot_about') ?></a>
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
  <h2>⭐ <?= __('reviews_tab') ?></h2>
  <div class="dest-avg-pill" id="destAvgPill" style="display:none">
    ⭐ <span id="destAvgScore">0</span>/5 &nbsp;·&nbsp; <span id="destTotalCount">0</span> <?= __('dest_reviews_unit') ?>
  </div>
</div>

<?php $user = currentUser(); ?>
<?php if ($user): ?>
<div class="form-box" id="destReviewFormBox">
  <h3 style="margin-top:0; color:var(--green-900);">✍️ <?= __('write_review') ?></h3>
  <form id="destReviewForm">
    <input type="hidden" name="destination_id" value="<?= (int)$d['id'] ?>">
    <div class="form-group">
      <label><?= __('dest_star_label') ?> <span style="color:red">*</span></label>
      <div class="star-rating-input">
        <?php for ($i = 5; $i >= 1; $i--): ?>
        <input type="radio" id="dstar<?= $i ?>" name="rating" value="<?= $i ?>" <?= $i === 5 ? 'checked' : '' ?>>
        <label for="dstar<?= $i ?>" title="<?= $i ?> <?= __('star_label') ?>">★</label>
        <?php endfor; ?>
      </div>
    </div>
    <div class="form-group">
      <label for="destComment"><?= __('dest_comment_label') ?> <span style="font-weight:400;color:#999;"><?= __('dest_comment_optional') ?></span></label>
      <textarea id="destComment" name="comment" rows="3"
        placeholder="<?= __('dest_share_feeling') ?> <?= e($d['name']) ?>..."
        style="resize:vertical;font-family:inherit;"></textarea>
      <div style="text-align:right;font-size:12px;color:#aaa;margin-top:3px;">
        <span id="destCharCount">0</span>/1000
      </div>
    </div>
    <button type="submit" class="btn" id="destSubmitBtn">🚀 <?= __('send_review') ?></button>
    <div id="destReviewMsg" style="margin-top:12px;font-size:14px;"></div>
  </form>
</div>
<?php else: ?>
<div class="form-box" style="text-align:center;padding:28px;">
  <p style="color:#666;margin:0 0 14px;">🔐 <a href="<?= url('/public/login.php') ?>"><?= __('login') ?></a> <?= mb_strtolower(__('login_to_review')) ?>.</p>
</div>
<?php endif; ?>

<div class="dest-review-list" id="destReviewList">
  <div class="dest-rev-empty"><?= __('dest_loading_reviews') ?></div>
</div>
<button class="dest-load-more" id="destLoadMoreBtn" style="display:none" onclick="destLoadMore()"><?= __('dest_load_more') ?></button>

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
    <h3><?= __('dest_edit_review') ?></h3>
    <p class="sub"><?= __('dest_edit_review_sub') ?></p>
    <form id="destEditForm">
      <input type="hidden" id="destEditId" name="id" value="">
      <div class="form-group">
        <label><?= __('dest_star_label') ?> <span style="color:red">*</span></label>
        <div class="star-rating-input" id="destEditStars">
          <?php for ($i = 5; $i >= 1; $i--): ?>
          <input type="radio" id="destr<?= $i ?>" name="rating" value="<?= $i ?>">
          <label for="destr<?= $i ?>" title="<?= $i ?> <?= __('star_label') ?>">★</label>
          <?php endfor; ?>
        </div>
      </div>
      <div class="form-group">
        <label for="destEditComment"><?= __('dest_comment_label') ?></label>
        <textarea id="destEditComment" name="comment" rows="3"
          style="resize:vertical;font-family:inherit;"
          placeholder="<?= __('dest_share_feeling') ?>..."></textarea>
        <div style="text-align:right;font-size:12px;color:#aaa;margin-top:3px;">
          <span id="destEditCharCnt">0</span>/1000
        </div>
      </div>
      <div id="destEditMsg" style="margin-bottom:10px;font-size:14px;"></div>
      <div class="rev-modal-footer">
        <button type="button" class="rev-btn-cancel" onclick="closeDRevModal()"><?= __('dest_cancel') ?></button>
        <button type="submit" class="btn" id="destEditSubmit"><?= __('dest_save_changes') ?></button>
      </div>
    </form>
  </div>
</div>

<!-- Admin Delete Reason Modal (Destination) -->
<div class="dest-admin-del-overlay" id="destAdminDelOverlay" onclick="if(event.target===this) closeDAdmDel()">
  <div class="dest-admin-del-modal">
    <h3><?= __('dest_admin_delete_title') ?></h3>
    <p class="sub"><?= __('dest_admin_delete_desc_prefix') ?> <strong id="destAdmDelName"></strong><?= __('dest_admin_delete_desc_suffix') ?></p>
    <div class="reason-presets">
      <button type="button" class="dest-reason-preset-btn" onclick="setDAdmReason('<?= __('dest_admin_reason_violation') ?>')"><?= __('dest_admin_preset_violation') ?></button>
      <button type="button" class="dest-reason-preset-btn" onclick="setDAdmReason('<?= __('dest_admin_reason_spam') ?>')"><?= __('dest_admin_preset_spam') ?></button>
      <button type="button" class="dest-reason-preset-btn" onclick="setDAdmReason('<?= __('dest_admin_reason_inappropriate') ?>')"><?= __('dest_admin_preset_inappropriate') ?></button>
      <button type="button" class="dest-reason-preset-btn" onclick="setDAdmReason('<?= __('dest_admin_reason_false') ?>')"><?= __('dest_admin_preset_false') ?></button>
      <button type="button" class="dest-reason-preset-btn" onclick="setDAdmReason('<?= __('dest_admin_reason_duplicate') ?>')"><?= __('dest_admin_preset_duplicate') ?></button>
    </div>
    <textarea id="destAdmDelReason" placeholder="<?= __('dest_admin_reason_placeholder') ?>"></textarea>
    <div id="destAdmDelErr" style="color:#dc2626;font-size:13px;margin-top:8px;min-height:18px;"></div>
    <div class="dest-admin-del-footer">
      <button class="dest-adm-cancel" onclick="closeDAdmDel()"><?= __('dest_cancel') ?></button>
      <button class="dest-adm-confirm" id="destAdmDelConfirm"><?= __('dest_admin_confirm_delete') ?></button>
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
    if (diff < 60)    return '<?= __('dest_just_now') ?>';
    if (diff < 3600)  return Math.floor(diff/60) + ' ' + '<?= __('dest_minutes_ago') ?>'.replace('<?= __('dest_ago') ?>', '').trim();
    if (diff < 86400) return Math.floor(diff/3600) + ' ' + '<?= __('dest_hours_ago') ?>'.replace('<?= __('dest_ago') ?>', '').trim();
    return Math.floor(diff/86400) + ' ' + '<?= __('dest_days_ago') ?>'.replace('<?= __('dest_ago') ?>', '').trim();
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
      list.innerHTML = `<div class="dest-rev-empty"><?= __('dest_no_reviews_first') ?></div>`;
      document.getElementById('destLoadMoreBtn').style.display = 'none';
      return;
    }

    data.reviews.forEach((r, idx) => {
      const card = document.createElement('div');
      card.className = 'dest-review-card';
      card.style.animationDelay = (idx * 0.04) + 's';

      const actionsHtml = r.is_mine ? `
        <div class="dest-card-actions">
          <button class="btn-edit-rev" onclick="openDRevEdit(${r.review_id}, ${r.rating}, ${JSON.stringify(r.comment || '')})"><?= __('dest_edit_btn') ?></button>
          <button class="btn-del-rev" onclick="deleteDRev(${r.review_id})"><?= __('dest_delete_btn') ?></button>
        </div>` : (_destIsAdmin ? `
        <div class="dest-card-actions">
          <button class="btn-admin-del-rev" onclick="openDAdmDel(${r.review_id}, '${r.display_name.replace(/'/g, "\\'")}')">🛡️ <?= __('dest_delete_btn') ?> <span class="dest-admin-badge"><?= __('dest_admin_badge') ?></span></button>
        </div>` : '');

      card.innerHTML = `
        <div class="dest-review-top">
          <div class="dest-reviewer">
            <div class="dest-rev-avatar">${r.display_name.charAt(0).toUpperCase()}</div>
            <div>
              <div class="dest-rev-name">${r.display_name}${r.is_mine ? ' <span style="background:#d8f3dc;color:#1b4332;font-size:10px;padding:1px 6px;border-radius:6px;font-weight:700;"><?= __('dest_yours_badge') ?></span>' : ''}</div>
              <div class="dest-rev-date">${timeSince(r.created_at)}</div>
            </div>
          </div>
          <div class="dest-star-display">${starsHtml(r.rating)}</div>
        </div>
        ${r.comment
          ? `<p class="dest-rev-comment">"${r.comment}"</p>`
          : `<p class="dest-rev-comment" style="color:#bbb;font-style:italic;"><?= __('dest_no_comment') ?></p>`}
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
      btn.disabled = true; btn.textContent = '<?= __('dest_sending') ?>';
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
          btn.textContent = '🚀 <?= __('send_review') ?>';
          loadReviews(true);
        }, 1500);
      } else {
        msg.innerHTML = `<div style="background:#fee2e2;color:#991b1b;padding:12px 16px;border-radius:10px;">❌ ${data.error}</div>`;
        btn.disabled = false; btn.textContent = '🚀 <?= __('send_review') ?>';
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
    btn.disabled = true; btn.textContent = '<?= __('dest_saving') ?>';
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
      btn.disabled = false; btn.textContent = '<?= __('dest_save_changes') ?>';
    }
  });

  // Delete review (user's own - simple confirm)
  window.deleteDRev = async function(reviewId) {
    if (!confirm('<?= __('dest_confirm_delete') ?>')) return;
    const fd = new FormData();
    fd.append('id', reviewId);
    const res  = await fetch(`${API_BASE}/review_delete.php`, { method:'POST', body: fd });
    const data = await res.json();
    if (data.success) {
      loadReviews(true);
    } else {
      alert('❌ ' + (data.error || '<?= __('dest_cannot_delete') ?>'));
    }
  };

  // Admin delete with reason
  window.openDAdmDel = function(reviewId, userName) {
    _destAdmDelId = reviewId;
    document.getElementById('destAdmDelName').textContent = userName;
    document.getElementById('destAdmDelReason').value = '';
    document.getElementById('destAdmDelErr').textContent = '';
    document.getElementById('destAdmDelConfirm').disabled = false;
    document.getElementById('destAdmDelConfirm').textContent = '<?= __('dest_admin_confirm_delete') ?>';
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
      errEl.textContent = '<?= __('dest_admin_reason_required') ?>';
      document.getElementById('destAdmDelReason').focus();
      return;
    }
    if (reason.length < 10) {
      errEl.textContent = '<?= __('dest_admin_reason_min') ?>';
      return;
    }

    this.disabled = true;
    this.textContent = '<?= __('dest_admin_deleting') ?>';
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
        errEl.textContent = '❌ ' + (data.error || '<?= __('dest_admin_error') ?>');
        this.disabled = false;
        this.textContent = '<?= __('dest_admin_confirm_delete') ?>';
      }
    } catch(e) {
      errEl.textContent = '❌ <?= __('dest_network_error') ?>';
      this.disabled = false;
      this.textContent = '<?= __('dest_admin_confirm_delete') ?>';
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