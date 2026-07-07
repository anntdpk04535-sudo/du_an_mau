<?php
require_once __DIR__ . '/../includes/functions.php';
$pageTitle = 'Lịch trình AI - Đắk Lắk Travel AI';
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

<h1 class="section-title">🧭 Lên lịch trình du lịch Đắk Lắk bằng AI</h1>
<p class="section-sub">Chọn số ngày và sở thích, AI sẽ gợi ý lịch trình chi tiết theo từng ngày, từng buổi.</p>

<div class="form-box">
  <form id="itinerary-form">
    <div class="form-group">
      <label>Số ngày du lịch</label>
      <select name="days" id="days">
        <option value="1">1 ngày</option>
        <option value="2" selected>2 ngày</option>
        <option value="3">3 ngày</option>
        <option value="4">4 ngày</option>
        <option value="5">5 ngày</option>
      </select>
    </div>
    <div class="form-group">
      <label>Sở thích / phong cách du lịch</label>
      <div class="checkbox-group">
        <label><input type="checkbox" name="prefs[]" value="thiên nhiên"> Thiên nhiên</label>
        <label><input type="checkbox" name="prefs[]" value="văn hoá"> Văn hoá - bản địa</label>
        <label><input type="checkbox" name="prefs[]" value="ẩm thực"> Ẩm thực</label>
        <label><input type="checkbox" name="prefs[]" value="trekking"> Trekking/mạo hiểm</label>
        <label><input type="checkbox" name="prefs[]" value="cà phê"> Cà phê</label>
        <label><input type="checkbox" name="prefs[]" value="gia đình"> Gia đình có trẻ nhỏ</label>
        <label><input type="checkbox" name="prefs[]" value="chụp ảnh"> Chụp ảnh</label>
      </div>
    </div>
    <div class="form-group">
      <label>Yêu cầu thêm (tuỳ chọn)</label>
      <textarea name="notes" rows="3" placeholder="Ví dụ: đi cùng người lớn tuổi, ngân sách thấp, muốn nghỉ trưa dài..."><?= $prefill ? 'Muốn ghé: ' . e($prefill) : '' ?></textarea>
    </div>
    <button type="submit" class="btn">✨ Tạo lịch trình bằng AI</button>
  </form>
</div>

<div id="stats-bar" class="map-stats-bar" style="display:none;">
  <div class="map-stat"><div class="map-stat-icon">📍</div><div class="map-stat-info"><div class="label">Điểm tham quan</div><div class="value" id="stat-points">—</div></div></div>
  <div class="map-stat"><div class="map-stat-icon">🛣️</div><div class="map-stat-info"><div class="label">Tổng quãng đường</div><div class="value" id="stat-distance">—</div></div></div>
  <div class="map-stat"><div class="map-stat-icon">🕐</div><div class="map-stat-info"><div class="label">Thời gian di chuyển</div><div class="value" id="stat-duration">—</div></div></div>
  <div class="map-stat"><div class="map-stat-icon">📅</div><div class="map-stat-info"><div class="label">Số ngày</div><div class="value" id="stat-days">—</div></div></div>
</div>

<div id="map-container" class="map-wrap" style="display:none;">
  <div id="itinerary-map"></div>
  <div class="map-day-legend" id="map-legend"></div>
</div>

<div id="route-panel" class="route-info-panel" style="display:none;">
  <h3>🗺️ Chi tiết lộ trình</h3>
  <div class="route-steps" id="route-steps-list"></div>
</div>

<div id="result"></div>

<script>
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

  resultBox.innerHTML = '<p class="loading-dots">🤖 AI đang lên lịch trình cho bạn, vui lòng đợi giây lát...</p>';
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
    if (!data.success) { resultBox.innerHTML = '<p style="color:red;">❌ ' + (data.message || 'Có lỗi xảy ra.') + '</p>'; return; }

    // Render lịch trình text
    let html = `
      <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;">
        <h2 class="section-title" style="margin:0;">Lịch trình gợi ý của bạn</h2>
        <button onclick="exportItineraryPDF()" class="btn secondary" style="display:flex; align-items:center; gap:6px;">
          📄 Xuất PDF
        </button>
      </div>
      <div id="pdf-export-content" style="margin-top:20px;">
    `;
    data.itinerary.forEach(day => {
      html += `<div class="day-block"><h3>Ngày ${day.day}${day.title ? ': ' + day.title : ''}</h3>`;
      day.items.forEach(item => {
        const addr  = item.address  ? `<div class="time-slot-address">📍 ${item.address}</div>` : '';
        const trans = item.transport? `<div class="time-slot-transport" style="font-size:13px;color:var(--green-700);margin-top:4px;font-weight:500;">🛵 ${item.transport}</div>` : '';
        const price = item.price    ? `<div class="time-slot-price">💰 <strong>Chi phí:</strong> ${item.price}</div>` : '';
        html += `<div class="time-slot"><strong>${item.time || ''}:</strong> ${item.activity}${addr}${trans}${price}</div>`;
      });
      html += '</div>';
    });
    html += '</div>'; // End pdf-export-content
    resultBox.innerHTML = html;

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
      legendHtml.push(`<div class="map-day-legend-item"><div class="map-day-legend-dot" style="background:${day.color}"></div>Ngày ${day.dayNum}${day.title ? ': '+day.title : ''}</div>`);
      stepsHtml.push(`<div class="day-separator">📅 Ngày ${day.dayNum}${day.title ? ': '+day.title : ''}</div>`);

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
        const linkHtml = pt.slug ? `<a href="<?= url('/public/destination.php') ?>?slug=${pt.slug}" target="_blank" style="display:inline-block;color:var(--green-700);font-weight:600;font-size:12px;text-decoration:none;">Xem chi tiết →</a>` : '';
        marker.bindPopup(`
          <div style="font-family:inherit;font-size:13px;min-width:200px;max-width:240px;">
            <div style="background:${day.color};color:white;border-radius:6px 6px 0 0;padding:8px 12px;margin:-1px -1px 10px;font-weight:700;font-size:12px;">📅 Ngày ${day.dayNum} &nbsp;·&nbsp; ${pt.time}</div>
            <div style="padding:0 2px;">
              <div style="font-weight:600;font-size:14px;line-height:1.4;margin-bottom:6px;">${pt.activity}</div>
              ${pt.address ? `<div style="color:#666;font-size:12px;margin-bottom:4px;">📍 ${pt.address}</div>` : ''}
              ${pt.price   ? `<div style="font-size:12px;color:#b45309;font-weight:600;margin-bottom:6px;">💰 ${pt.price}</div>` : ''}
              <div style="display:flex;gap:8px;margin-top:8px;flex-wrap:wrap;align-items:center;">
                ${linkHtml}
                <a href="${dirUrl}" target="_blank" style="display:inline-block;background:#e8f4fd;color:#1a56db;padding:3px 8px;border-radius:4px;text-decoration:none;font-size:11px;font-weight:600;">🧭 Chỉ đường</a>
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
        if (i < day.points.length - 1) stepsHtml.push(`<div class="route-step-divider">→ Di chuyển đến điểm tiếp theo</div>`);
      });
    }

    window._markers = allMarkers;
    if (allMarkers.length === 1) window.itineraryMap.setView(allMarkers[0].getLatLng(), 13);
    else if (allMarkers.length > 1) window.itineraryMap.fitBounds(new L.featureGroup(allMarkers).getBounds().pad(0.18));

    document.getElementById('map-legend').innerHTML = legendHtml.join('');

    const fmtDist = totalDistance > 0 ? (totalDistance >= 1000 ? (totalDistance/1000).toFixed(1)+' km' : Math.round(totalDistance)+' m') : '—';
    const fmtDur  = totalDuration > 0 ? (() => { const h=Math.floor(totalDuration/3600),m=Math.round((totalDuration%3600)/60); return h>0?`${h}h ${m}m`:`${m} phút`; })() : '—';
    document.getElementById('stat-points').textContent   = totalPoints + ' điểm';
    document.getElementById('stat-distance').textContent = fmtDist;
    document.getElementById('stat-duration').textContent = fmtDur;
    document.getElementById('stat-days').textContent     = mapData.length + ' ngày';
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

  } catch (err) {
    resultBox.innerHTML = '<p style="color:red;">❌ Lỗi kết nối tới server.</p>';
    console.error(err);
  }
});

function exportItineraryPDF() {
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
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
