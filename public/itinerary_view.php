<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/weather.php';

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

// Điểm xuất phát & snapshot thời tiết đã lưu (cột mới — có thể chưa migrate nên đọc mềm)
$originData = null;
if (!empty($itinerary['origin_type']) && $itinerary['origin_type'] !== 'none'
    && isset($itinerary['origin_lat'], $itinerary['origin_lng'])
    && $itinerary['origin_lat'] !== null && $itinerary['origin_lng'] !== null) {
    $originData = [
        'type'      => $itinerary['origin_type'],
        'label'     => $itinerary['origin_label'] ?: __('iti_origin_label'),
        'lat'       => (float)$itinerary['origin_lat'],
        'lng'       => (float)$itinerary['origin_lng'],
        'radius_km' => (isset($itinerary['radius_km']) && $itinerary['radius_km'] !== null) ? (float)$itinerary['radius_km'] : null,
    ];
}

$weatherSnap = null;
$weatherAdvisories = [];
if (!empty($itinerary['weather_snapshot'])) {
    $decoded = json_decode($itinerary['weather_snapshot'], true);
    if (is_array($decoded) && !empty($decoded['available']) && !empty($decoded['daily'])) {
        $weatherSnap = $decoded;
        $weatherAdvisories = weatherAdvisories($decoded);
    }
}
$viewLang = $_SESSION['lang'] ?? 'vi';

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
.edit-item { display:flex; flex-direction:column; gap:7px; }
.edit-item input,.edit-item textarea { border:1px solid #dbe4df; border-radius:7px; padding:7px 9px; font:inherit; width:100%; box-sizing:border-box; }
.drag-handle { cursor:grab; color:var(--green-700); font-weight:800; letter-spacing:2px; }
.remove-item { margin-left:auto; border:0; background:#fee2e2; color:#991b1b; border-radius:6px; cursor:pointer; font-size:18px; width:28px; height:28px; }
.edit-item.dragging { opacity:.55; }
.origin-badge { display:inline-flex; align-items:center; gap:6px; background:#e8f8ef; color:var(--green-700); border-radius:999px; padding:5px 12px; font-size:13px; font-weight:600; margin-top:8px; }
.weather-days { display:flex; gap:10px; flex-wrap:wrap; }
.weather-day { flex:1; min-width:110px; border-radius:10px; padding:10px 12px; text-align:center; }
.weather-day-date { font-size:11px; font-weight:700; opacity:.8; }
.weather-day-icon { font-size:26px; margin:4px 0; }
.weather-day-desc { font-size:12px; font-weight:600; line-height:1.3; }
.weather-day-temp { font-size:12px; margin-top:4px; opacity:.85; }
.wd-good { background:#dcfce7; color:#166534; }
.wd-caution { background:#fef9c3; color:#854d0e; }
.wd-indoor_preferred { background:#ffedd5; color:#9a3412; }
.wd-unsafe { background:#fee2e2; color:#7f1d1d; }
.weather-advisories { margin-top:12px; font-size:13px; }
.weather-advisory { background:#fff7ed; border-left:3px solid #fb923c; border-radius:0 8px 8px 0; padding:7px 10px; margin-top:6px; color:#7c2d12; }
</style>

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap:wrap; gap:10px;">
    <div>
        <a href="<?= url('/public/profile.php') ?>" style="text-decoration:none; color:#666;">← <?= __('back_to_profile') ?></a>
        <h1 class="section-title" style="margin-bottom:0; margin-top:10px;"><?= e($itinerary['title']) ?></h1>
        <?php if ($originData): ?>
            <div class="origin-badge">🏨 <?= __('iti_origin_set') ?> <?= e($originData['label']) ?><?php if ($originData['radius_km']): ?> · <?= rtrim(rtrim(number_format($originData['radius_km'], 1), '0'), '.') ?> km<?php endif; ?></div>
        <?php endif; ?>
    </div>
    <button onclick="exportPDF()" class="btn secondary" style="display:flex; align-items:center; gap:6px;">
        📄 <?= __('export_pdf') ?>
    </button>
    <button id="saveItineraryBtn" class="btn" type="button">💾 Lưu chỉnh sửa</button>
    <button id="optimizeItineraryBtn" class="btn secondary" type="button">🧭 Tối ưu tuyến</button>
</div>

<div id="pdf-content">
    <div style="display:none;" id="pdf-header">
        <h1 style="color: #2d6a4f; margin-bottom: 5px;"><?= e($itinerary['title']) ?></h1>
        <p style="color: #666;"><?= __('iti_created_date') ?>: <?= date('d/m/Y', strtotime($itinerary['created_at'])) ?> | <?= __('iti_duration') ?>: <?= $itinerary['days'] ?> <?= __('days') ?></p>
        <hr style="border: 0; border-top: 1px solid #ddd; margin-bottom: 20px;">
    </div>

    <?php if ($weatherSnap): ?>
    <div class="route-info-panel">
        <h3>⛅ <?= __('iti_weather_title') ?></h3>
        <div class="weather-days">
            <?php foreach (array_slice($weatherSnap['daily'], 0, 7) as $d):
                $risk = in_array($d['risk'] ?? 'good', ['good', 'caution', 'indoor_preferred', 'unsafe'], true) ? $d['risk'] : 'good';
                $desc = $d[$viewLang === 'en' ? 'text_en' : 'text_vi'] ?? '';
                $rain = isset($d['precipitation_probability_max']) && $d['precipitation_probability_max'] !== null ? ' · ☔ ' . (int)$d['precipitation_probability_max'] . '%' : '';
            ?>
            <div class="weather-day wd-<?= $risk ?>">
                <div class="weather-day-date"><?= e($d['date'] ?? '') ?></div>
                <div class="weather-day-icon"><?= e($d['icon'] ?? '') ?></div>
                <div class="weather-day-desc"><?= e($desc) ?></div>
                <div class="weather-day-temp"><?= isset($d['temp_min'], $d['temp_max']) ? round($d['temp_min']) . '–' . round($d['temp_max']) . '°C' : '' ?><?= $rain ?></div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php if ($weatherAdvisories): ?>
        <div class="weather-advisories">
            <strong>⚠️ <?= __('iti_advisory_title') ?></strong>
            <?php foreach (array_slice($weatherAdvisories, 0, 6) as $a): ?>
                <div class="weather-advisory"><?= e($a) ?></div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

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
                    <div class="time-slot edit-item" draggable="true" data-id="<?= (int)$item['id'] ?>" data-entity-type="<?= !empty($item['food_id'])?'food':(!empty($item['accommodation_id'])?'accommodation':'destination') ?>" data-entity-id="<?= (int)($item['food_id'] ?: ($item['accommodation_id'] ?: ($item['destination_id'] ?? 0))) ?>" data-day="<?= (int)$item['day_number'] ?>">
                      <div style="display:flex;gap:8px;align-items:center"><span class="drag-handle" title="Kéo để sắp xếp">⋮⋮</span><input class="edit-time" value="<?= e($item['time_slot']) ?>" aria-label="Thời gian"><button type="button" class="remove-item" title="Xóa item">×</button></div>
                      <textarea class="edit-activity" rows="2" aria-label="Hoạt động"><?= e($item['activity']) ?></textarea>
                      <input class="edit-address" value="<?= e($item['address'] ?? '') ?>" placeholder="Địa chỉ">
                      <input class="edit-transport" value="<?= e($item['transport'] ?? '') ?>" placeholder="Phương tiện">
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
const originData = <?= json_encode($originData, JSON_UNESCAPED_UNICODE) ?>;
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
    if (mapData.length === 0 && !originData) return;

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

    // Điểm xuất phát đã lưu: marker 🏨 + vòng bán kính tìm kiếm
    const fitMarkers = allMarkers.slice();
    if (originData) {
      const oIcon = L.divIcon({
        className: '',
        html: '<div style="width:34px;height:34px;border-radius:50%;background:#fff;border:3px solid #2d6a4f;display:flex;align-items:center;justify-content:center;font-size:17px;box-shadow:0 3px 10px rgba(0,0,0,.3);">🏨</div>',
        iconSize: [34,34], iconAnchor: [17,17], tooltipAnchor: [0,-20]
      });
      const oMarker = L.marker([originData.lat, originData.lng], { icon: oIcon, zIndexOffset: 1000 }).addTo(window.itineraryMap);
      oMarker.bindTooltip('<?= __('iti_origin_set') ?> ' + (originData.label || ''), { direction: 'top' });
      if (originData.radius_km) {
        L.circle([originData.lat, originData.lng], {
          radius: originData.radius_km * 1000,
          color: '#2d6a4f', weight: 1.5, dashArray: '6,8', fillColor: '#2d6a4f', fillOpacity: 0.05
        }).addTo(window.itineraryMap);
      }
      fitMarkers.push(oMarker);
    }

    if (fitMarkers.length === 1) window.itineraryMap.setView(fitMarkers[0].getLatLng(), 13);
    else if (fitMarkers.length > 1) window.itineraryMap.fitBounds(new L.featureGroup(fitMarkers).getBounds().pad(0.18));

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

<script>
(function(){
  const list=document.getElementById('result'); if(!list) return;
  let dragged=null;
  document.querySelectorAll('.edit-item').forEach(el=>{
    el.addEventListener('dragstart',()=>{dragged=el;el.classList.add('dragging');});
    el.addEventListener('dragend',()=>{el.classList.remove('dragging');dragged=null;});
    el.addEventListener('dragover',e=>{e.preventDefault();if(dragged&&dragged!==el)el.parentNode.insertBefore(dragged,el);});
    el.querySelector('.remove-item')?.addEventListener('click',()=>el.remove());
  });
  document.getElementById('saveItineraryBtn')?.addEventListener('click',async()=>{
    const btn=document.getElementById('saveItineraryBtn');btn.disabled=true;btn.textContent='⏳ Đang lưu...';
    const items=[...document.querySelectorAll('.edit-item')].map((el,i)=>({entity_type:el.dataset.entityType,entity_id:+el.dataset.entityId||null,day_number:+el.dataset.day||1,time_slot:el.querySelector('.edit-time')?.value||'',activity:el.querySelector('.edit-activity')?.value||'',address:el.querySelector('.edit-address')?.value||'',transport:el.querySelector('.edit-transport')?.value||'',sort_order:i}));
    try{const res=await fetch('<?= url('/api/itinerary_update.php') ?>',{method:'PATCH',headers:{'Content-Type':'application/json'},body:JSON.stringify({itinerary_id:<?= (int)$itinerary['id'] ?>,version:<?= (int)($itinerary['version']??1) ?>,items})});const data=await res.json();if(!data.success)throw new Error(data.error||'Không thể lưu');alert('Đã lưu chỉnh sửa.');location.reload();}catch(e){alert(e.message);}finally{btn.disabled=false;btn.textContent='💾 Lưu chỉnh sửa';}
  });
  document.getElementById('optimizeItineraryBtn')?.addEventListener('click',async()=>{
    const btn=document.getElementById('optimizeItineraryBtn');btn.disabled=true;btn.textContent='⏳ Đang tính...';
    try{const res=await fetch('<?= url('/api/itinerary_optimize.php') ?>',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({itinerary_id:<?= (int)$itinerary['id'] ?>})});const data=await res.json();if(!data.success)throw new Error(data.error||'Không thể tối ưu');const edit=[...document.querySelectorAll('.edit-item')];const byId=new Map(edit.map(el=>[+el.dataset.id,el]));data.items.forEach(item=>{const el=byId.get(+item.id);if(el)el.parentNode.appendChild(el);});alert(data.warning||'Đã sắp xếp lại tuyến. Hãy kiểm tra và lưu.');}catch(e){alert(e.message);}finally{btn.disabled=false;btn.textContent='🧭 Tối ưu tuyến';}
  });
})();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
