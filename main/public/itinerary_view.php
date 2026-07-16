<?php
require_once __DIR__ . '/../includes/functions.php';

$user = currentUser();
if (!$user) {
    header('Location: ' . url('/public/login.php'));
    exit;
}

$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: ' . url('/public/profile.php'));
    exit;
}

$db = getDB();

// Fetch itinerary
$stmt = $db->prepare("SELECT * FROM itineraries WHERE id = ? AND user_id = ?");
$stmt->execute([$id, $user['id']]);
$itinerary = $stmt->fetch();
if ($itinerary) {
    $itinerary = translateItineraryDynamic($itinerary);
}

if (!$itinerary) {
    http_response_code(404);
    die(__('iti_not_found'));
}

// Fetch itinerary items with destination coords
$stmt = $db->prepare("
    SELECT i.*, d.slug, d.latitude, d.longitude 
    FROM itinerary_items i
    LEFT JOIN destinations d ON i.destination_id = d.id
    WHERE i.itinerary_id = ?
    ORDER BY i.day_number ASC, i.sort_order ASC
");
$stmt->execute([$id]);
$items = $stmt->fetchAll();

// Construct data structure for JS
$daysData = [];
foreach ($items as $item) {
    $dayNum = (int)$item['day_number'];
    if (!isset($daysData[$dayNum])) {
        $daysData[$dayNum] = [
            'dayNum' => $dayNum,
            'title' => '', // Assuming title is empty, or can parse from somewhere
            'points' => [],
            'itemsHtml' => []
        ];
    }
    
    // Add to points if has coords
    if (!empty($item['latitude']) && !empty($item['longitude'])) {
        $daysData[$dayNum]['points'][] = [
            'lat' => (float)$item['latitude'],
            'lng' => (float)$item['longitude'],
            'time' => $item['time_slot'] ?? '',
            'activity' => $item['activity'] ?? '',
            'address' => $item['address'] ?? '',
            'slug' => $item['slug'] ?? '',
            'price' => '' // Price not saved in DB currently
        ];
    }
    
    $daysData[$dayNum]['itemsHtml'][] = $item;
}

// Re-index map data for JSON
$mapData = [];
$colors = ['#2d6a4f','#e76f51','#3a86c8','#8338ec','#ff006e'];
$colorIdx = 0;
foreach ($daysData as $dayNum => $dayInfo) {
    if (count($dayInfo['points']) > 0) {
        $mapData[] = [
            'dayNum' => $dayNum,
            'title' => '',
            'color' => $colors[$colorIdx % count($colors)],
            'points' => $dayInfo['points']
        ];
        $colorIdx++;
    }
}

$pageTitle = $itinerary['title'] . ' - Đắk Lắk Travel AI';
include __DIR__ . '/../includes/header.php';
?>
<!-- html2pdf.js for PDF export -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js" integrity="sha512-GsLlZN/3F2ErC5ifS5QtgpiJtWd43JWSuIgh7mbzZ8zBps+dvLusV+eNQATqgA/HdeKFVgA5v3S/cIrLF7QnIg==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<style>
.map-stats-bar { display:flex; gap:12px; flex-wrap:wrap; margin-bottom:16px; }
.map-stat { flex:1; min-width:120px; background:white; border-radius:12px; padding:12px 16px; box-shadow:0 2px 10px rgba(0,0,0,.07); display:flex; align-items:center; gap:10px; }
.map-stat-icon { font-size:22px; line-height:1; }
.map-stat-info .label { font-size:11px; color:#888; font-weight:500; text-transform:uppercase; letter-spacing:.05em; }
.map-stat-info .value { font-size:17px; font-weight:700; color:var(--green-900); line-height:1.2; }
.map-wrap { position:relative; border-radius:var(--radius); overflow:hidden; box-shadow:0 4px 20px rgba(0,0,0,.12); margin-bottom:24px; }
#itinerary-map { height:480px; z-index:1; }
.map-day-legend { position:absolute; bottom:12px; left:12px; z-index:999; background:rgba(255,255,255,.92); backdrop-filter:blur(6px); border-radius:10px; padding:8px 12px; box-shadow:0 2px 10px rgba(0,0,0,.15); font-size:12px; font-weight:600; display:flex; flex-direction:column; gap:5px; }
.map-day-legend-item { display:flex; align-items:center; gap:7px; }
.map-day-legend-dot { width:12px; height:12px; border-radius:50%; flex-shrink:0; }
.route-info-panel { background:white; border-radius:var(--radius); padding:20px 24px; box-shadow:0 2px 10px rgba(0,0,0,.07); margin-bottom:24px; }
.route-info-panel h3 { margin:0 0 14px; color:var(--green-700); font-size:16px; display:flex; align-items:center; gap:8px; }
.route-steps { display:flex; flex-direction:column; gap:0; }
.route-step { display:flex; align-items:flex-start; gap:12px; padding:10px 6px; border-bottom:1px dashed #eee; cursor:pointer; transition:background .15s; border-radius:8px; }
.route-step:last-child { border-bottom:none; }
.route-step:hover { background:#f6fdf8; }
.route-step-num { width:28px; height:28px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:12px; font-weight:800; color:white; flex-shrink:0; margin-top:1px; box-shadow:0 2px 6px rgba(0,0,0,.2); }
.route-step-info .step-time { font-size:11px; font-weight:700; text-transform:uppercase; color:#aaa; letter-spacing:.05em; }
.route-step-info .step-name { font-size:14px; font-weight:600; color:var(--text-dark); line-height:1.3; margin:2px 0; }
.route-step-info .step-addr { font-size:12px; color:#888; }
.route-step-divider { font-size:11px; color:var(--green-700); font-weight:500; padding:3px 6px 3px 46px; }
.day-separator { background:linear-gradient(135deg,var(--green-100),#fff); border-radius:8px; padding:8px 12px; font-size:13px; font-weight:700; color:var(--green-700); margin:8px 0 4px; display:flex; align-items:center; gap:8px; }
</style>

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap:wrap; gap:10px;">
    <div>
        <a href="<?= url('/public/profile.php') ?>" style="text-decoration:none; color:#666;">← <?= __('back_to_profile') ?></a>
        <h1 class="section-title" style="margin-bottom:0; margin-top:10px;"><?= e($itinerary['title']) ?></h1>
    </div>
    <button onclick="exportPDF()" class="btn secondary" style="display:flex; align-items:center; gap:6px;">
        📄 <?= __('export_pdf') ?>
    </button>
</div>

<div id="pdf-content">
    <div style="display:none;" id="pdf-header">
        <h1 style="color: #2d6a4f; margin-bottom: 5px;"><?= e($itinerary['title']) ?></h1>
        <p style="color: #666;"><?= __('iti_created_date') ?>: <?= date('d/m/Y', strtotime($itinerary['created_at'])) ?> | <?= __('iti_duration') ?>: <?= $itinerary['days'] ?> <?= __('days') ?></p>
        <hr style="border: 0; border-top: 1px solid #ddd; margin-bottom: 20px;">
    </div>

    <div id="stats-bar" class="map-stats-bar" style="display:none;">
        <div class="map-stat"><div class="map-stat-icon">📍</div><div class="map-stat-info"><div class="label"><?= __('destinations') ?></div><div class="value" id="stat-points">—</div></div></div>
        <div class="map-stat"><div class="map-stat-icon">🛣️</div><div class="map-stat-info"><div class="label"><?= __('total_distance') ?></div><div class="value" id="stat-distance">—</div></div></div>
        <div class="map-stat"><div class="map-stat-icon">🕐</div><div class="map-stat-info"><div class="label"><?= __('travel_time') ?></div><div class="value" id="stat-duration">—</div></div></div>
        <div class="map-stat"><div class="map-stat-icon">📅</div><div class="map-stat-info"><div class="label"><?= __('how_many_days') ?></div><div class="value" id="stat-days"><?= $itinerary['days'] ?> <?= __('days') ?></div></div></div>
    </div>

    <div id="map-container" class="map-wrap" style="display:none;">
        <div id="itinerary-map"></div>
        <div class="map-day-legend" id="map-legend"></div>
    </div>

    <div id="route-panel" class="route-info-panel" style="display:none;" data-html2canvas-ignore="true">
        <h3>🗺️ <?= __('route_details') ?></h3>
        <div class="route-steps" id="route-steps-list"></div>
    </div>

    <div id="result">
        <?php foreach ($daysData as $dayNum => $day): ?>
            <div class="day-block">
                <h3><?= __('iti_day') ?> <?= $dayNum ?></h3>
                <?php foreach ($day['itemsHtml'] as $item): ?>
                    <div class="time-slot">
                        <strong><?= e($item['time_slot']) ?>:</strong> <?= e($item['activity']) ?>
                        <?php if ($item['address']): ?>
                            <div class="time-slot-address">📍 <?= e($item['address']) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($item['transport'])): ?>
                            <div class="time-slot-transport" style="font-size:13px;color:var(--green-700);margin-top:4px;font-weight:500;">🛵 <?= e($item['transport']) ?></div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
// --- PDF Export ---
function exportPDF() {
    const element = document.getElementById('pdf-content');
    const header = document.getElementById('pdf-header');
    header.style.display = 'block';
    
    const opt = {
        margin:       [10, 10, 10, 10],
        filename:     'Lich_trinh_Dak_Lak_<?= date('Ymd', strtotime($itinerary['created_at'])) ?>.pdf',
        image:        { type: 'jpeg', quality: 0.98 },
        html2canvas:  { scale: 2, useCORS: true },
        jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
    };

    html2pdf().set(opt).from(element).save().then(() => {
        header.style.display = 'none';
    });
}

// --- Map Logic ---
const mapData = <?= json_encode($mapData, JSON_UNESCAPED_UNICODE) ?>;
const DAY_COLORS = ['#2d6a4f','#e76f51','#3a86c8','#8338ec','#ff006e'];

function createNumberedIcon(num, color) {
  return L.divIcon({
    className: '',
    html: `<div style="width:32px;height:32px;border-radius:50% 50% 50% 0;background:${color};border:3px solid white;display:flex;align-items:center;justify-content:center;color:white;font-weight:800;font-size:13px;transform:rotate(-45deg);box-shadow:0 3px 10px rgba(0,0,0,.3);"><span style="transform:rotate(45deg)">${num}</span></div>`,
    iconSize: [32,32], iconAnchor: [16,32], popupAnchor: [0,-36], tooltipAnchor: [0,-36]
  });
}

async function fetchRealRoute(points) {
  if (points.length < 2) return null;
  const coords = points.map(p => `${p.lng},${p.lat}`).join(';');
  try {
    const res = await fetch(
      `https://router.project-osrm.org/route/v1/driving/${coords}?overview=full&geometries=geojson`,
      { signal: AbortSignal.timeout(8000) }
    );
    if (!res.ok) return null;
    const data = await res.json();
    if (data.code !== 'Ok' || !data.routes?.[0]) return null;
    return { geometry: data.routes[0].geometry, distance: data.routes[0].distance, duration: data.routes[0].duration };
  } catch { return null; }
}

function getShortName(activity) {
  const m = activity.match(/[\u201c\u201d"'']([^\u201c\u201d"'']+)[\u201c\u201d"'']/);
  if (m) return m[1];
  const clean = activity.replace(/^(Tham quan|Khám phá|Ghé thăm|Ăn sáng tại|Ăn trưa tại|Ăn tối tại|Dùng bữa tại|Nghỉ ngơi tại)\s*/i, '');
  return clean.length > 28 ? clean.substring(0, 26) + '…' : clean;
}

document.addEventListener('DOMContentLoaded', async function() {
    if (mapData.length === 0) return;

    document.getElementById('map-container').style.display = 'block';
    window.itineraryMap = L.map('itinerary-map', { zoomControl: true });
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
    }).addTo(window.itineraryMap);

    const allMarkers = [];
    let totalDistance = 0, totalDuration = 0, totalPoints = 0;
    const stepsHtml = [], legendHtml = [];

    for (const day of mapData) {
      legendHtml.push(`<div class="map-day-legend-item"><div class="map-day-legend-dot" style="background:${day.color}"></div><?= __('iti_day') ?> ${day.dayNum}${day.title ? ': '+day.title : ''}</div>`);
      stepsHtml.push(`<div class="day-separator">📅 <?= __('iti_day') ?> ${day.dayNum}${day.title ? ': '+day.title : ''}</div>`);

      if (day.points.length > 1) {
        const route = await fetchRealRoute(day.points);
        if (route) {
          totalDistance += route.distance;
          totalDuration += route.duration;
          L.geoJSON(route.geometry, { style: { color: day.color, weight: 5, opacity: 0.85 } }).addTo(window.itineraryMap);
        } else {
          L.polyline(day.points.map(p => [p.lat, p.lng]), { color: day.color, weight: 4, opacity: 0.75, dashArray: '8,12' }).addTo(window.itineraryMap);
        }
      }

      day.points.forEach((pt, i) => {
        totalPoints++;
        const num    = i + 1;
        const marker = L.marker([pt.lat, pt.lng], { icon: createNumberedIcon(num, day.color) }).addTo(window.itineraryMap);
        const sName  = getShortName(pt.activity);
        marker.bindTooltip(sName, { permanent: true, direction: 'top', offset: [0,-36], className: `map-label map-label--day${day.dayNum}` });
        const dirUrl = `https://www.google.com/maps/dir/?api=1&destination=${pt.lat},${pt.lng}`;
        const linkHtml = pt.slug ? `<a href="<?= url('/public/destination.php') ?>?slug=${pt.slug}" target="_blank" style="display:inline-block;color:var(--green-700);font-weight:600;font-size:12px;text-decoration:none;"><?= __('dest_view_details') ?> →</a>` : '';
        marker.bindPopup(`
          <div style="font-family:inherit;font-size:13px;min-width:200px;max-width:240px;">
            <div style="background:${day.color};color:white;border-radius:6px 6px 0 0;padding:8px 12px;margin:-1px -1px 10px;font-weight:700;font-size:12px;">📅 <?= __('iti_day') ?> ${day.dayNum} &nbsp;·&nbsp; ${pt.time}</div>
            <div style="padding:0 2px;">
              <div style="font-weight:600;font-size:14px;line-height:1.4;margin-bottom:6px;">${pt.activity}</div>
              ${pt.address ? `<div style="color:#666;font-size:12px;margin-bottom:4px;">📍 ${pt.address}</div>` : ''}
              <div style="display:flex;gap:8px;margin-top:8px;flex-wrap:wrap;align-items:center;">
                ${linkHtml}
                <a href="${dirUrl}" target="_blank" style="display:inline-block;background:#e8f4fd;color:#1a56db;padding:3px 8px;border-radius:4px;text-decoration:none;font-size:11px;font-weight:600;">🧭 <?= __('directions') ?></a>
              </div>
            </div>
          </div>`, { maxWidth: 260 });

        const markerIdx = allMarkers.length;
        allMarkers.push(marker);

        stepsHtml.push(`
          <div class="route-step" data-idx="${markerIdx}" data-lat="${pt.lat}" data-lng="${pt.lng}">
            <div class="route-step-num" style="background:${day.color};">${num}</div>
            <div class="route-step-info">
              <div class="step-time">${pt.time}</div>
              <div class="step-name">${sName}</div>
              ${pt.address ? `<div class="step-addr">📍 ${pt.address}</div>` : ''}
            </div>
          </div>`);
        if (i < day.points.length - 1) stepsHtml.push(`<div class="route-step-divider">→ <?= __('iti_move_next') ?></div>`);
      });
    }

    window._markers = allMarkers;
    if (allMarkers.length === 1) window.itineraryMap.setView(allMarkers[0].getLatLng(), 13);
    else if (allMarkers.length > 1) window.itineraryMap.fitBounds(new L.featureGroup(allMarkers).getBounds().pad(0.18));

    document.getElementById('map-legend').innerHTML = legendHtml.join('');

    const fmtDist = totalDistance > 0 ? (totalDistance >= 1000 ? (totalDistance/1000).toFixed(1)+' km' : Math.round(totalDistance)+' m') : '—';
    const fmtDur  = totalDuration > 0 ? (() => { const h=Math.floor(totalDuration/3600),m=Math.round((totalDuration%3600)/60); return h>0?`${h}h ${m}m`:`${m} <?= __('iti_minutes') ?>`; })() : '—';
    document.getElementById('stat-points').textContent   = totalPoints + ' <?= __('iti_points_unit') ?>';
    document.getElementById('stat-distance').textContent = fmtDist;
    document.getElementById('stat-duration').textContent = fmtDur;
    document.getElementById('stats-bar').style.display   = 'flex';

    document.getElementById('route-steps-list').innerHTML = stepsHtml.join('');
    document.getElementById('route-panel').style.display  = 'block';

    document.querySelectorAll('.route-step[data-idx]').forEach(el => {
      el.addEventListener('click', () => {
        const idx = +el.dataset.idx;
        const lat = +el.dataset.lat;
        const lng = +el.dataset.lng;
        window.itineraryMap.setView([lat, lng], 15, { animate: true });
        if (window._markers[idx]) window._markers[idx].openPopup();
        el.style.background = '#e8f8ef';
        setTimeout(() => el.style.background = '', 1500);
      });
    });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
