<?php
require_once __DIR__ . '/../includes/functions.php';
$pageTitle = __('page_title_itinerary');
$prefill = $_GET['prefill'] ?? '';
include __DIR__ . '/../includes/header.php';
?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js" integrity="sha512-GsLlZN/3F2ErC5ifS5QtgpiJtWd43JWSuIgh7mbzZ8zBps+dvLusV+eNQATqgA/HdeKFVgA5v3S/cIrLF7QnIg==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>

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

<h1 class="section-title">🧭 <?= __('iti_form_title') ?></h1>
<p class="section-sub"><?= __('iti_form_sub') ?></p>

<div class="form-box">
  <form id="itinerary-form">
    <div class="form-group">
      <label><?= __('iti_form_days') ?></label>
      <select name="days" id="days">
        <option value="1">1 <?= __('days') ?></option>
        <option value="2" selected>2 <?= __('days') ?></option>
        <option value="3">3 <?= __('days') ?></option>
        <option value="4">4 <?= __('days') ?></option>
        <option value="5">5 <?= __('days') ?></option>
      </select>
    </div>
    <div class="form-group">
      <label><?= __('iti_form_prefs') ?></label>
      <div class="checkbox-group">
        <label><input type="checkbox" name="prefs[]" value="thiên nhiên"> <?= __('iti_pref_nature') ?></label>
        <label><input type="checkbox" name="prefs[]" value="văn hoá"> <?= __('iti_pref_culture') ?></label>
        <label><input type="checkbox" name="prefs[]" value="ẩm thực"> <?= __('iti_pref_food') ?></label>
        <label><input type="checkbox" name="prefs[]" value="trekking"> <?= __('iti_pref_trekking') ?></label>
        <label><input type="checkbox" name="prefs[]" value="cà phê"> <?= __('iti_pref_coffee') ?></label>
        <label><input type="checkbox" name="prefs[]" value="gia đình"> <?= __('iti_pref_family') ?></label>
        <label><input type="checkbox" name="prefs[]" value="chụp ảnh"> <?= __('iti_pref_photo') ?></label>
      </div>
    </div>
    <div class="form-group">
      <label><?= __('iti_form_extra') ?></label>
      <div style="display:flex; gap:10px;">
        <textarea name="notes" id="notes" rows="3" style="flex:1" placeholder="<?= __('iti_form_extra_ph') ?>"><?= $prefill ? __('iti_prefill_prefix') . ' ' . e($prefill) : '' ?></textarea>
      </div>
    </div>
    <button type="submit" class="btn">✨ <?= __('iti_form_submit') ?></button>
  </form>
</div>

<div id="stats-bar" class="map-stats-bar" style="display:none;">
  <div class="map-stat"><div class="map-stat-icon">📍</div><div class="map-stat-info"><div class="label"><?= __('iti_stat_points') ?></div><div class="value" id="stat-points">—</div></div></div>
  <div class="map-stat"><div class="map-stat-icon">🛣️</div><div class="map-stat-info"><div class="label"><?= __('iti_stat_distance') ?></div><div class="value" id="stat-distance">—</div></div></div>
  <div class="map-stat"><div class="map-stat-icon">🕐</div><div class="map-stat-info"><div class="label"><?= __('iti_stat_duration') ?></div><div class="value" id="stat-duration">—</div></div></div>
  <div class="map-stat"><div class="map-stat-icon">📅</div><div class="map-stat-info"><div class="label"><?= __('iti_stat_days') ?></div><div class="value" id="stat-days">—</div></div></div>
</div>

<div id="map-container" class="map-wrap" style="display:none;">
  <div id="itinerary-map"></div>
  <div class="map-day-legend" id="map-legend"></div>
</div>

<div id="route-panel" class="route-info-panel" style="display:none;">
  <h3>🗺️ <?= __('iti_route_details') ?></h3>
  <div class="route-steps" id="route-steps-list"></div>
</div>

<div id="result"></div>

<script>
if (!window.itineraryEventsAttached) {
    window.itineraryEventsAttached = true;
    document.addEventListener('beforeLangSwitch', function() {
        const form = document.getElementById('itinerary-form');
        if (form) {
            window.savedItiForm = {
                days: document.getElementById('days').value,
                prefs: Array.from(form.querySelectorAll('input[name="prefs[]"]:checked')).map(c => c.value),
                notes: document.getElementById('notes').value
            };
        }
    });
    
    document.addEventListener('afterLangSwitch', function() {
        if (window.savedItiForm) {
            const daysEl = document.getElementById('days');
            if (daysEl) daysEl.value = window.savedItiForm.days;
            
            const noteEl = document.getElementById('notes');
            if (noteEl) noteEl.value = window.savedItiForm.notes;
            
            const prefs = window.savedItiForm.prefs || [];
            document.querySelectorAll('input[name="prefs[]"]').forEach(cb => {
                cb.checked = prefs.includes(cb.value);
            });
        }
        
        if (window.lastItineraryData && window.renderItinerary) {
            const resultBox = document.getElementById('result');
            if (resultBox) {
                resultBox.innerHTML = '<p class="loading-dots">🤖 Translating itinerary...</p>';
                document.getElementById('stats-bar').style.display = 'none';
                document.getElementById('map-container').style.display = 'none';
                document.getElementById('route-panel').style.display = 'none';
            }
            
            fetch('<?= url('/api/translate_json.php') ?>', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({data: window.lastItineraryData})
            }).then(r => r.json()).then(res => {
                if (res.success && res.data) {
                    window.lastItineraryData = res.data;
                    window.renderItinerary(res.data);
                } else {
                    console.error("Translation API failed:", res);
                    window.renderItinerary(window.lastItineraryData);
                }
            }).catch(e => {
                console.error("Translation API fetch error:", e);
                window.renderItinerary(window.lastItineraryData);
            });
        }
    });
}

(function() {
const form = document.getElementById('itinerary-form');
const resultBox = document.getElementById('result');
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

form.addEventListener('submit', async (e) => {
  e.preventDefault();
  const days  = document.getElementById('days').value;
  const prefs = Array.from(form.querySelectorAll('input[name="prefs[]"]:checked')).map(c => c.value);
  const notes = form.querySelector('textarea[name="notes"]').value;

  resultBox.innerHTML = '<p class="loading-dots">🤖 <?= __('iti_loading') ?></p>';
  document.getElementById('stats-bar').style.display     = 'none';
  document.getElementById('map-container').style.display = 'none';
  document.getElementById('route-panel').style.display   = 'none';
  if (window.itineraryMap) { window.itineraryMap.remove(); window.itineraryMap = null; }

  try {
    const res  = await fetch('<?= url('/api/generate_itinerary.php') ?>', {
      method: 'POST', headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ days, prefs, notes })
    });
    const data = await res.json();
    if (!data.success) { resultBox.innerHTML = '<p style="color:red;">❌ ' + (data.message || '<?= __('dest_error_occurred') ?>') + '</p>'; return; }
    
    window.lastItineraryData = data;
    await renderItinerary(data);

  } catch (err) {
    resultBox.innerHTML = '<p style="color:red;">❌ <?= __('dest_network_error') ?></p>';
    console.error(err);
  }
});

window.renderItinerary = async function(data) {
    let html = `
      <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:16px;margin-bottom:24px;">
        <h2 class="section-title" style="margin:0;font-size:2rem;color:#022c22;font-weight:800;"><?= __('iti_suggested_title') ?></h2>
        <div style="display:flex; gap:12px;">
          <button type="button" onclick="simulateRain()" class="btn" style="background:linear-gradient(135deg, #dc2626 0%, #b91c1c 100%) !important; color:#ffffff !important; display:inline-flex; align-items:center; gap:8px; border-radius:40px; padding:12px 24px; font-weight:800; border:none; box-shadow:0 8px 20px rgba(220,38,38,0.35); cursor:pointer;">
            🌧️ <?= __('iti_reroute_btn') ?>
          </button>
          <button type="button" onclick="exportItineraryPDF()" class="btn" style="background:linear-gradient(135deg, #f59e0b 0%, #d97706 100%) !important; color:#ffffff !important; display:inline-flex; align-items:center; gap:8px; border-radius:40px; padding:12px 24px; font-weight:800; border:none; box-shadow:0 8px 20px rgba(245,158,11,0.4); cursor:pointer;">
            📄 <?= __('iti_export_pdf') ?>
          </button>
        </div>
      </div>
      <div id="pdf-export-content" style="margin-top:20px; background:#ffffff; padding:24px; border-radius:24px; box-shadow:0 10px 30px rgba(0,0,0,0.05); border:1px solid #cbd5e1;">
    `;
    (data.itinerary || []).forEach(day => {
      html += `
        <div class="day-block" style="background:#ffffff; border-radius:20px; border:2px solid #047857; overflow:hidden; margin-bottom:32px; box-shadow:0 10px 25px rgba(2,44,34,0.08);">
          <div class="day-block-header" style="background:linear-gradient(135deg, #022c22 0%, #047857 100%); color:#ffffff; padding:20px 28px; font-size:1.4rem; font-weight:800; display:flex; align-items:center; gap:12px; border-bottom:3px solid #f59e0b;">
            <span>📅</span>
            <span style="color:#fef08a !important;"><?= __('iti_day') ?> ${day.day}${day.title ? ': ' + day.title : ''}</span>
          </div>
          <div class="day-block-body" style="padding:28px; display:flex; flex-direction:column; gap:20px; background:#f8fafc;">
      `;
      (day.items || []).forEach(item => {
        const timeBadge = item.time ? `<span style="background:#f59e0b; color:#ffffff; padding:6px 16px; border-radius:20px; font-size:14px; font-weight:800; display:inline-block;">${item.time}</span>` : '';
        const reason = item.reason ? `<div style="margin-top:12px; padding:12px 18px; background:#eff6ff; border-left:5px solid #3b82f6; color:#1e40af; font-size:15px; border-radius:8px; line-height:1.6; font-weight:500;">💡 <strong><?= __('iti_reason') ?></strong> ${item.reason}</div>` : '';
        const impact = item.community_impact ? `<div style="margin-top:10px; padding:12px 18px; background:#ecfdf5; border-left:5px solid #10b981; color:#065f46; font-size:15px; border-radius:8px; line-height:1.6; font-weight:500;">🌱 <strong><?= __('iti_community_impact') ?></strong> ${item.community_impact}</div>` : '';
        const sugg  = item.suggestion ? `<div style="margin-top:10px; padding:12px 18px; background:#fffbeb; border-left:5px solid #f59e0b; color:#92400e; font-size:15px; border-radius:8px; line-height:1.6; font-weight:500;">💡 <strong><?= __('iti_suggestion') ?></strong> ${item.suggestion}</div>` : '';
        
        let metaTags = [];
        if (item.address) metaTags.push(`<span style="background:#f1f5f9; padding:6px 12px; border-radius:8px; border:1px solid #cbd5e1;">📍 ${item.address}</span>`);
        if (item.transport) metaTags.push(`<span style="background:#e0f2fe; color:#0369a1; padding:6px 12px; border-radius:8px; border:1px solid #7dd3fc;">🛵 ${item.transport}</span>`);
        if (item.price) metaTags.push(`<span style="background:#fef3c7; color:#92400e; padding:6px 12px; border-radius:8px; border:1px solid #fde68a;">🎟️ <strong><?= __('iti_cost') ?>:</strong> ${item.price}</span>`);

        const metaHtml = metaTags.length > 0 ? `<div style="display:flex; gap:12px; flex-wrap:wrap; margin-top:16px; padding-top:16px; border-top:1px solid #e2e8f0; font-size:14px; font-weight:700; color:#334155;">${metaTags.join('')}</div>` : '';
        
        html += `
          <div class="time-slot" style="background:#ffffff; border-radius:16px; padding:24px; border:1px solid #cbd5e1; box-shadow:0 6px 18px rgba(0,0,0,0.04);">
            <div style="font-size:1.25rem; font-weight:800; color:#022c22; margin-bottom:12px; display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
              ${timeBadge}
              <span>${item.activity}</span>
            </div>
            ${reason}
            ${impact}
            ${sugg}
            ${metaHtml}
          </div>
        `;
      });
      html += `
          </div>
        </div>
      `;
    });
    html += '</div>'; // End pdf-export-content
    document.getElementById('result').innerHTML = html;

    // Chuẩn bị mapData
    const mapData = [];
    data.itinerary.forEach((day, di) => {
      const pts = [];
      day.items.forEach(item => {
        if (item.lat && item.lng) pts.push({ lat: item.lat, lng: item.lng, time: item.time||'', activity: item.activity||'', address: item.address||'', slug: item.slug||'', price: item.price||'' });
      });
      if (pts.length > 0) mapData.push({ dayNum: day.day, title: day.title||'', color: DAY_COLORS[di % DAY_COLORS.length], points: pts });
    });
    if (mapData.length === 0) return;

    // Khởi tạo bản đồ
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

      // Lộ trình thực OSRM
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
              ${pt.price   ? `<div style="font-size:12px;color:#b45309;font-weight:600;margin-bottom:6px;">💰 ${pt.price}</div>` : ''}
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
              ${pt.price   ? `<div class="step-addr">💰 ${pt.price}</div>` : ''}
            </div>
          </div>`);
        if (i < day.points.length - 1) stepsHtml.push(`<div class="route-step-divider">→ <?= __('iti_move_to_next') ?></div>`);
      });
    }

    window._markers = allMarkers;
    if (allMarkers.length === 1) window.itineraryMap.setView(allMarkers[0].getLatLng(), 13);
    else if (allMarkers.length > 1) window.itineraryMap.fitBounds(new L.featureGroup(allMarkers).getBounds().pad(0.18));

    document.getElementById('map-legend').innerHTML = legendHtml.join('');

    const fmtDist = totalDistance > 0 ? (totalDistance >= 1000 ? (totalDistance/1000).toFixed(1)+' km' : Math.round(totalDistance)+' m') : '—';
    const fmtDur  = totalDuration > 0 ? (() => { const h=Math.floor(totalDuration/3600),m=Math.round((totalDuration%3600)/60); return h>0?`${h}h ${m}m`:`${m} <?= __('iti_minutes') ?>`; })() : '—';
    document.getElementById('stat-points').textContent   = totalPoints + ' <?= __('iti_points') ?>';
    document.getElementById('stat-distance').textContent = fmtDist;
    document.getElementById('stat-duration').textContent = fmtDur;
    document.getElementById('stat-days').textContent     = mapData.length + ' <?= __('days') ?>';
    document.getElementById('stats-bar').style.display   = 'flex';

    document.getElementById('route-steps-list').innerHTML = stepsHtml.join('');
    document.getElementById('route-panel').style.display  = 'block';

    // Click route steps => focus map marker
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

}

window.exportItineraryPDF = function() {
    const element = document.getElementById('pdf-export-content');
    
    const opt = {
        margin:       [10, 10, 10, 10],
        filename:     'Lich_trinh_Dak_Lak_AI.pdf',
        image:        { type: 'jpeg', quality: 0.98 },
        html2canvas:  { scale: 2, useCORS: true },
        jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
    };

    html2pdf().set(opt).from(element).save();
}

// Giả lập mưa lớn
window.simulateRain = async function() {
    const data = window.lastItineraryData;
    if (!data) return alert('<?= __('iti_reroute_need_iti') ?>');
    
    document.getElementById('result').innerHTML = '<p class="loading-dots"><?= __('iti_rerouting_loading') ?></p>';
    
    try {
        const res = await fetch('<?= url('/api/reroute_itinerary.php') ?>', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        const reData = await res.json();
        if(reData.success) {
            window.renderItinerary(reData); // Cần refactor để tái sử dụng
            alert('<?= __('iti_reroute_success') ?>');
        } else {
            alert('Lỗi: ' + reData.message);
        }
    } catch (e) {
        alert('Lỗi kết nối');
    }
}

})();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
