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

<div class="meta-row">
  <div class="meta-item">⭐ <?= e((string) $d['rating']) ?>/5</div>
  <div class="meta-item">⏱ ~<?= e((string) $d['avg_visit_hours']) ?> giờ tham quan</div>
  <div class="meta-item">💰 Mức chi phí: <?= e(priceLevelVi($d['price_level'])) ?></div>
  <?php if ($d['tags']): ?>
    <div class="meta-item">🏷 <?= e($d['tags']) ?></div>
  <?php endif; ?>
</div>

<div class="form-box">
  <h3>Giới thiệu</h3>
  <p><?= nl2br(e($d['description'])) ?></p>
</div>

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

<?php include __DIR__ . '/../includes/footer.php'; ?>