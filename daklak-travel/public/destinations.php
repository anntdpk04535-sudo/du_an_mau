<?php
require_once __DIR__ . '/../includes/functions.php';
$pageTitle = 'Điểm đến - Đắk Lắk Travel AI';

$categories = getAllCategories();
$catId = isset($_GET['cat']) ? (int) $_GET['cat'] : null;
$destinations = getAllDestinations($catId);

/**
 * Render ★☆ stars HTML từ rating số thực (1–5).
 * @param float|null $rating  Điểm trung bình, NULL = chưa ai đánh giá
 * @param int        $count   Số lượng đánh giá
 */
function renderStarsBadge(?float $rating, int $count): string
{
    if ($rating === null || $count === 0) {
        return '<span class="badge badge-norating">★ Chưa có đánh giá</span>';
    }
    $full  = (int) round($rating);
    $stars = str_repeat('★', $full) . str_repeat('☆', 5 - $full);
    return '<span class="badge badge-rating">' . $stars . ' ' . number_format($rating, 1) . ' <span class="rev-cnt">(' . $count . ')</span></span>';
}

include __DIR__ . '/../includes/header.php';
?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css" />
<script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>
<style>
.badge-rating {
  background: linear-gradient(135deg, #fef3c7, #fde68a);
  color: #92400e;
  border: 1px solid #f59e0b;
  font-weight: 700;
  letter-spacing: .5px;
}
.badge-norating {
  background: #f3f4f6;
  color: #9ca3af;
  font-weight: 500;
}
.rev-cnt {
  font-size: 11px;
  font-weight: 500;
  opacity: .75;
}
</style>

<h1 class="section-title">Điểm đến tại Đắk Lắk</h1>
<p class="section-sub">Lọc theo danh mục để tìm điểm đến phù hợp</p>

<div class="pills">
  <a href="<?= url('/public/destinations.php') ?>" class="pill <?= $catId === null ? 'active' : '' ?>">Tất cả</a>
  <?php foreach ($categories as $c): ?>
    <a href="<?= url('/public/destinations.php') ?>?cat=<?= e((string) $c['id']) ?>"
      class="pill <?= $catId === (int) $c['id'] ? 'active' : '' ?>">
      <?= e($c['name']) ?>
    </a>
  <?php endforeach; ?>
</div>

<div id="destinations-map" style="height: 400px; border-radius: var(--radius); margin-bottom: 24px; box-shadow: 0 2px 10px rgba(0, 0, 0, .06); z-index: 1;"></div>

<?php
$mapDestinations = [];
foreach ($destinations as $d) {
    if (!empty($d['latitude']) && !empty($d['longitude'])) {
        $mapDestinations[] = [
            'name'            => $d['name'],
            'slug'            => $d['slug'],
            'short_desc'      => $d['short_desc'],
            'lat'             => (float)$d['latitude'],
            'lng'             => (float)$d['longitude'],
            'rating'          => $d['avg_rating'] !== null ? round((float)$d['avg_rating'], 1) : null,
            'review_count'    => (int)$d['review_count'],
            'avg_visit_hours' => (float)$d['avg_visit_hours'],
            'price_level'     => $d['price_level']
        ];
    }
}
?>
<script>
document.addEventListener('DOMContentLoaded', function() {
  const destinations = <?= json_encode($mapDestinations, JSON_UNESCAPED_UNICODE) ?>;
  
  if (destinations.length === 0) {
    document.getElementById('destinations-map').style.display = 'none';
    return;
  }

  const map = L.map('destinations-map');

  // CartoDB Voyager tile đẹp, rõ chữ Việt
  L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
    attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> © <a href="https://carto.com/">CARTO</a>',
    subdomains: 'abcd', maxZoom: 19
  }).addTo(map);

  // Màu icon theo mức giá
  const priceColors = { free: '#2d6a4f', low: '#3a86c8', medium: '#e76f51', high: '#8338ec' };
  function getPriceColor(lvl) { return priceColors[lvl] || '#555'; }

  function createDestIcon(color) {
    return L.divIcon({
      className: '',
      html: `<div style="width:28px;height:28px;border-radius:50% 50% 50% 0;background:${color};border:3px solid white;box-shadow:0 3px 8px rgba(0,0,0,.3);transform:rotate(-45deg);"></div>`,
      iconSize: [28,28], iconAnchor: [14,28], popupAnchor: [0,-32], tooltipAnchor: [0,-32]
    });
  }

  // Dịch mức giá sang tiếng Việt
  const priceLabelVi = { free: 'Miễn phí', low: 'Thấp', medium: 'Trung bình', high: 'Cao' };
  function getPriceLabelVi(lvl) { return priceLabelVi[lvl] || lvl; }

  // Cluster để gôm các điểm gần nhau
  const cluster = L.markerClusterGroup({ maxClusterRadius: 60, disableClusteringAtZoom: 13 });

  const allMarkers = [];

  destinations.forEach(d => {
    const color  = getPriceColor(d.price_level);
    const marker = L.marker([d.lat, d.lng], { icon: createDestIcon(color) });
    const dirUrl = `https://www.google.com/maps/dir/?api=1&destination=${d.lat},${d.lng}`;

    let popupContent = `
      <div style="font-family:inherit;min-width:210px;max-width:240px;">
        <div style="background:${color};color:white;border-radius:6px 6px 0 0;padding:8px 12px;margin:-1px -1px 10px;font-weight:700;font-size:13px;">${d.name}</div>
        <div style="padding:0 2px;">
          <p style="margin:0 0 8px;font-size:12px;color:#555;line-height:1.4;">${d.short_desc}</p>
          <div style="font-size:11px;margin-bottom:8px;display:flex;gap:6px;flex-wrap:wrap;">
            ${d.rating !== null
              ? `<span style="background:linear-gradient(135deg,#fef3c7,#fde68a);color:#92400e;border:1px solid #f59e0b;padding:2px 8px;border-radius:10px;font-weight:700;">
                  ${'★'.repeat(Math.round(d.rating))}${'☆'.repeat(5-Math.round(d.rating))} ${d.rating.toFixed(1)} <span style="opacity:.65">(${d.review_count})</span>
                 </span>`
              : `<span style="background:#f3f4f6;color:#9ca3af;padding:2px 8px;border-radius:10px;">★ Chưa có đánh giá</span>`
            }
            <span style="background:#f1f1f1;padding:2px 7px;border-radius:10px;">~${d.avg_visit_hours}h</span>
            <span style="background:#fff3e0;color:#b45309;padding:2px 7px;border-radius:10px;">💰 ${getPriceLabelVi(d.price_level)}</span>
          </div>
          <div style="display:flex;gap:6px;flex-wrap:wrap;">
            <a href="<?= url('/public/destination.php') ?>?slug=${d.slug}" style="background:${color};color:white;padding:4px 10px;border-radius:4px;text-decoration:none;font-size:11px;font-weight:600;">Xem chi tiết →</a>
            <a href="${dirUrl}" target="_blank" style="background:#e8f4fd;color:#1a56db;padding:4px 10px;border-radius:4px;text-decoration:none;font-size:11px;font-weight:600;">🧭 Chỉ đường</a>
          </div>
        </div>
      </div>`;

    marker.bindPopup(popupContent, { maxWidth: 260 });
    marker.bindTooltip(d.name, {
      permanent: true, direction: 'top', offset: [0,-32], className: 'map-label'
    });
    cluster.addLayer(marker);
    allMarkers.push(marker);
  });

  map.addLayer(cluster);

  if (allMarkers.length === 1) {
    map.setView(allMarkers[0].getLatLng(), 13);
  } else if (allMarkers.length > 1) {
    const group = new L.featureGroup(allMarkers);
    map.fitBounds(group.getBounds().pad(0.1));
  }

  // Legend màu theo mức giá
  const legend = L.control({ position: 'bottomright' });
  legend.onAdd = () => {
    const div = L.DomUtil.create('div');
    div.innerHTML = `
      <div style="background:rgba(255,255,255,.92);backdrop-filter:blur(6px);border-radius:10px;padding:8px 12px;font-size:11px;font-weight:600;box-shadow:0 2px 8px rgba(0,0,0,.15);">
        <div style="margin-bottom:5px;font-size:12px;color:#333;">💰 Mức giá</div>
        ${Object.entries({free:'Miễn phí',low:'Thấp',medium:'Trung bình',high:'Cao'}).map(([k,v])=>
          `<div style="display:flex;align-items:center;gap:6px;margin-bottom:4px;">
             <div style="width:10px;height:10px;border-radius:50%;background:${getPriceColor(k)}"></div>${v}
           </div>`
        ).join('')}
      </div>`;
    return div;
  };
  legend.addTo(map);
});
</script>

<div class="grid">
  <?php if (empty($destinations)): ?>
    <p>Chưa có điểm đến nào trong danh mục này.</p>
  <?php endif; ?>
  <?php foreach ($destinations as $d): ?>
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
        <?= renderStarsBadge(
            $d['avg_rating'] !== null ? (float)$d['avg_rating'] : null,
            (int)$d['review_count']
        ) ?>
        <span class="badge">~<?= e((string) $d['avg_visit_hours']) ?>h</span>
        <span class="badge"><?= e(priceLevelVi($d['price_level'])) ?></span>
      </div>
    </a>
  <?php endforeach; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>