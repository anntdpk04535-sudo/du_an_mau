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
.map-stat { flex:1; min-width:120px; background:white; border-radius:14px; padding:14px 18px; box-shadow:0 4px 14px rgba(0,0,0,.06); display:flex; align-items:center; gap:12px; border:1px solid #e2e8f0; }
.map-stat-icon { font-size:24px; line-height:1; }
.map-stat-info .label { font-size:11px; color:#64748b; font-weight:600; text-transform:uppercase; letter-spacing:.05em; }
.map-stat-info .value { font-size:18px; font-weight:800; color:var(--green-900); line-height:1.2; }
.map-wrap { position:relative; border-radius:16px; overflow:hidden; box-shadow:0 6px 24px rgba(0,0,0,.12); margin-bottom:24px; border:1px solid #e2e8f0; }
#itinerary-map { height:480px; z-index:1; }
.map-day-legend { position:absolute; bottom:14px; left:14px; z-index:999; background:rgba(255,255,255,.94); backdrop-filter:blur(8px); border-radius:12px; padding:10px 14px; box-shadow:0 4px 16px rgba(0,0,0,.15); font-size:12px; font-weight:700; display:flex; flex-direction:column; gap:6px; border:1px solid rgba(255,255,255,0.8); }
.map-day-legend-item { display:flex; align-items:center; gap:8px; }
.map-day-legend-dot { width:12px; height:12px; border-radius:50%; flex-shrink:0; box-shadow:0 0 0 2px rgba(255,255,255,0.8); }
.route-info-panel { background:white; border-radius:16px; padding:22px 26px; box-shadow:0 4px 18px rgba(0,0,0,.06); margin-bottom:24px; border:1px solid #e2e8f0; }
.route-info-panel h3 { margin:0 0 16px; color:var(--green-700); font-size:17px; font-weight:700; display:flex; align-items:center; gap:8px; }
.route-steps { display:flex; flex-direction:column; gap:0; }
.route-step { display:flex; align-items:flex-start; gap:12px; padding:12px 8px; border-bottom:1px dashed #e2e8f0; cursor:pointer; transition:all .2s ease; border-radius:10px; }
.route-step:last-child { border-bottom:none; }
.route-step:hover { background:#f0fdf4; transform:translateX(4px); }
.route-step-num { width:28px; height:28px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:12px; font-weight:800; color:white; flex-shrink:0; margin-top:1px; box-shadow:0 3px 8px rgba(0,0,0,.2); }
.route-step-info .step-time { font-size:11px; font-weight:700; text-transform:uppercase; color:#94a3b8; letter-spacing:.05em; }
.route-step-info .step-name { font-size:15px; font-weight:700; color:var(--text-dark); line-height:1.3; margin:2px 0; }
.route-step-info .step-addr { font-size:12px; color:#64748b; }
.route-step-divider { font-size:11px; color:var(--green-700); font-weight:600; padding:4px 6px 4px 46px; }
.day-separator { background:linear-gradient(135deg,#e8f8ef,#fff); border-radius:10px; padding:10px 14px; font-size:14px; font-weight:700; color:var(--green-800); margin:10px 0 6px; display:flex; align-items:center; gap:8px; border:1px solid #d7ecd9; }
.iti-origin-group { border-top:1px dashed #cbd5e1; padding-top:18px; margin-top:18px; }
.iti-origin-hint { font-size:13px; color:#64748b; margin:2px 0 12px; }

/* ── MODERN ORIGIN SELECTION CARDS ── */
.iti-origin-modes { display:grid; grid-template-columns:repeat(auto-fit, minmax(130px, 1fr)); gap:10px; margin-bottom:14px; }
.iti-origin-card { position:relative; display:flex; align-items:center; gap:8px; padding:11px 14px; border-radius:12px; background:#ffffff; border:1.5px solid #e2e8f0; cursor:pointer; font-size:13px; font-weight:600; color:#334155; transition:all .2s cubic-bezier(0.4, 0, 0.2, 1); box-shadow:0 2px 5px rgba(0,0,0,0.02); user-select:none; }
.iti-origin-card:hover { border-color:var(--green-700,#1E5631); background:#f0fdf4; transform:translateY(-2px); box-shadow:0 6px 16px rgba(30, 86, 49, 0.08); }
.iti-origin-card input[type="radio"] { appearance:none; -webkit-appearance:none; width:18px; height:18px; border:2px solid #cbd5e1; border-radius:50%; outline:none; transition:all 0.2s ease; margin:0; flex-shrink:0; cursor:pointer; }
.iti-origin-card input[type="radio"]:checked { border-color:var(--green-700,#1E5631); background-color:var(--green-700,#1E5631); box-shadow:inset 0 0 0 3px #fff; }
.iti-origin-card:has(input[type="radio"]:checked) { border-color:var(--green-700,#1E5631); background:linear-gradient(135deg, #f0fdf4, #dcfce7); color:#14532d; box-shadow:0 4px 14px rgba(30, 86, 49, 0.14); font-weight:700; }

.iti-origin-manual-row { display:flex; gap:8px; margin-top:10px; }
.iti-origin-manual-row input { flex:1; border-radius:10px; border:1px solid #cbd5e1; padding:10px 14px; }
.iti-origin-status { font-size:13px; margin-top:8px; color:var(--green-700); font-weight:600; }
.iti-origin-clear { background:none; border:none; color:#dc2626; font-size:12px; cursor:pointer; text-decoration:underline; padding:0 0 0 6px; }

/* ── MODERN RANGE SLIDER & PRESETS ── */
.iti-radius-container { background:#f8fafc; border-radius:14px; padding:14px 16px; border:1px solid #e2e8f0; margin-top:14px; }
.iti-radius-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:8px; }
.iti-radius-header label { font-size:13px; font-weight:700; color:#334155; }
.iti-radius-badge { background:linear-gradient(135deg, var(--green-700,#1E5631), #2d6a4f); color:#ffffff; padding:4px 12px; border-radius:20px; font-size:13px; font-weight:700; box-shadow:0 2px 8px rgba(30,86,49,0.2); }
.iti-range-input { -webkit-appearance:none; appearance:none; width:100%; height:8px; border-radius:4px; background:linear-gradient(90deg, #1E5631, #E5A93C, #8B261D); outline:none; margin:8px 0; }
.iti-range-input::-webkit-slider-thumb { -webkit-appearance:none; appearance:none; width:22px; height:22px; border-radius:50%; background:#ffffff; border:3px solid #1E5631; cursor:pointer; box-shadow:0 3px 8px rgba(0,0,0,0.25); transition:transform 0.15s ease; }
.iti-range-input::-webkit-slider-thumb:hover { transform:scale(1.15); }
.iti-preset-pills { display:flex; gap:8px; margin-top:10px; }
.iti-preset-btn { flex:1; background:#ffffff; border:1px solid #cbd5e1; border-radius:8px; padding:6px 0; font-size:12px; font-weight:700; color:#475569; cursor:pointer; text-align:center; transition:all 0.2s; }
.iti-preset-btn:hover, .iti-preset-btn.active { background:#1E5631; color:#ffffff; border-color:#1E5631; box-shadow:0 2px 8px rgba(30,86,49,0.2); }

/* ── WEATHER TOGGLE ── */
.iti-weather-card { display:flex; align-items:center; justify-content:space-between; background:#fff; border:1.5px solid #e2e8f0; border-radius:12px; padding:12px 16px; margin-top:14px; cursor:pointer; transition:all 0.2s; }
.iti-weather-card:hover { border-color:#1E5631; background:#f0fdf4; }
.iti-weather-info { display:flex; align-items:center; gap:8px; font-size:13px; font-weight:600; color:#334155; }
.iti-switch { position:relative; display:inline-block; width:44px; height:24px; }
.iti-switch input { opacity:0; width:0; height:0; }
.iti-switch-slider { position:absolute; cursor:pointer; top:0; left:0; right:0; bottom:0; background-color:#cbd5e1; transition:.3s; border-radius:24px; }
.iti-switch-slider:before { position:absolute; content:""; height:18px; width:18px; left:3px; bottom:3px; background-color:white; transition:.3s; border-radius:50%; box-shadow:0 2px 4px rgba(0,0,0,0.2); }
.iti-switch input:checked + .iti-switch-slider { background-color:#1E5631; }
.iti-switch input:checked + .iti-switch-slider:before { transform:translateX(20px); }

.weather-days { display:flex; gap:10px; flex-wrap:wrap; }
.weather-day { flex:1; min-width:110px; border-radius:12px; padding:12px 14px; text-align:center; background:#fff; border:1px solid #e2e8f0; box-shadow:0 2px 8px rgba(0,0,0,0.04); }
.weather-day-date { font-size:11px; font-weight:700; opacity:.8; }
.weather-day-icon { font-size:28px; margin:6px 0; }
.weather-day-desc { font-size:12px; font-weight:600; line-height:1.3; }
.weather-day-temp { font-size:12px; margin-top:4px; opacity:.85; }
.weather-advisories { margin-top:14px; font-size:13px; }
.weather-advisory { background:#fff7ed; border-left:4px solid #f59e0b; color:#92400e; border-radius:8px; padding:9px 12px; margin-top:8px; font-weight:600; }
.geo-warnings { background:#fef2f2; border-left:4px solid #ef4444; color:#991b1b; border-radius:10px; padding:12px 16px; margin:14px 0; font-size:13px; font-weight:600; }
.geo-warnings div { margin-top:4px; }
.nearby-group { margin-top:12px; }
.nearby-group-title { font-size:12px; font-weight:700; text-transform:uppercase; color:#64748b; letter-spacing:.05em; margin-bottom:8px; }
.nearby-chips { display:flex; flex-wrap:wrap; gap:8px; }
.nearby-chip { background:#f0fdf4; border:1px solid #bbf7d0; border-radius:999px; padding:6px 14px; font-size:13px; color:#14532d; display:inline-flex; align-items:center; gap:6px; text-decoration:none; font-weight:600; transition:all 0.2s; }
.nearby-chip:hover { background:#dcfce7; border-color:#86efac; transform:translateY(-1px); }
.nearby-chip em { font-style:normal; font-size:11px; font-weight:800; color:var(--green-700); background:#fff; padding:2px 6px; border-radius:10px; }

/* ── STUNNING AI LOADING ANIMATION ── */
.iti-loading-box { background:#ffffff; border:1.5px solid #e2e8f0; border-radius:20px; padding:38px 24px; text-align:center; margin:32px auto; max-width:520px; box-shadow:0 12px 36px rgba(0,0,0,0.06); animation:fadeIn 0.4s ease; }
.iti-loading-pulse { position:relative; width:80px; height:80px; margin:0 auto 20px; display:flex; align-items:center; justify-content:center; }
.iti-loading-avatar { font-size:44px; z-index:2; animation:floatMascot 2s ease-in-out infinite; }
.iti-loading-ripple { position:absolute; inset:-8px; border-radius:50%; border:3px solid #1E5631; opacity:0; animation:ripplePulse 2s cubic-bezier(0.1, 0.8, 0.3, 1) infinite; }
@keyframes floatMascot { 0%, 100% { transform:translateY(0); } 50% { transform:translateY(-8px); } }
@keyframes ripplePulse { 0% { transform:scale(0.6); opacity:0.8; } 100% { transform:scale(1.4); opacity:0; } }
.iti-loading-title { font-size:18px; font-weight:800; color:var(--green-900,#14532d); margin-bottom:6px; }
.iti-loading-sub { font-size:13px; color:#64748b; margin-bottom:20px; font-weight:500; }
.iti-loading-progress-bar { width:100%; height:6px; background:#e2e8f0; border-radius:10px; overflow:hidden; position:relative; }
.iti-loading-progress-fill { width:100%; height:100%; background:linear-gradient(90deg, #1E5631, #E5A93C, #8B261D); border-radius:10px; animation:progressIndeterminate 1.8s ease-in-out infinite; transform-origin:left; }
@keyframes progressIndeterminate { 0% { transform:scaleX(0) translateX(0); } 50% { transform:scaleX(0.6) translateX(50%); } 100% { transform:scaleX(1) translateX(100%); } }
</style>



<section class="iti-hero-intro" aria-labelledby="iti-page-title">
  <div class="iti-hero-copy">
    <p class="iti-eyebrow">ĐẤT ĐỎ · CÀ PHÊ · RỪNG XANH</p>
    <h1 id="iti-page-title"><?= __('iti_form_title') ?></h1>
    <p class="iti-hero-sub"><?= __('iti_form_sub') ?></p>
    <div class="iti-route-tags" aria-label="Dak Lak travel themes">
      <span>Hồ Lắk</span><span>Buôn Ma Thuột</span><span>Dray Nur</span><span>Buôn làng</span>
    </div>
  </div>
  <div class="iti-hero-note">
    <span class="iti-note-mark">✦</span>
    <div><strong>Dựng chuyến đi có căn cứ</strong><p>AI kết hợp dữ liệu điểm đến, ẩm thực và lưu trú đã có trong catalog Đắk Lắk.</p></div>
  </div>
</section>

<div class="iti-builder">
  <aside class="iti-builder-aside">
    <p class="iti-eyebrow">TRIP BRIEF</p>
    <h2>Chuyến đi của bạn bắt đầu từ một nhịp riêng.</h2>
    <ol class="iti-steps">
      <li><span>01</span><div><strong>Chọn nhịp đi</strong><small>Số ngày và khoảng nghỉ phù hợp.</small></div></li>
      <li><span>02</span><div><strong>Nói điều bạn thích</strong><small>Thiên nhiên, văn hóa, cà phê hay ẩm thực.</small></div></li>
      <li><span>03</span><div><strong>Nhận tuyến gợi ý</strong><small>Bản đồ và chặng đi rõ ràng cho từng ngày.</small></div></li>
    </ol>
    <div class="iti-builder-aside-footer">⌁ Dữ liệu được trình bày theo khu vực Đông · Tây Đắk Lắk</div>
  </aside>

<div class="form-box iti-form-card">
  <div class="iti-form-card-head"><div><p class="iti-eyebrow">BƯỚC 1 / 3</p><h2>Đặt nhịp cho hành trình</h2></div><span class="iti-form-stamp">AI<br>TRIP</span></div>
  <form id="itinerary-form">
    <div class="form-group iti-days-field">
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
      <div class="checkbox-group iti-choice-grid">
        <label class="iti-choice"><input type="checkbox" name="prefs[]" value="thiên nhiên"><span class="iti-choice-icon">◌</span><span><?= __('iti_pref_nature') ?></span></label>
        <label class="iti-choice"><input type="checkbox" name="prefs[]" value="văn hoá"><span class="iti-choice-icon">⌂</span><span><?= __('iti_pref_culture') ?></span></label>
        <label class="iti-choice"><input type="checkbox" name="prefs[]" value="ẩm thực"><span class="iti-choice-icon">✦</span><span><?= __('iti_pref_food') ?></span></label>
        <label class="iti-choice"><input type="checkbox" name="prefs[]" value="trekking"><span class="iti-choice-icon">↗</span><span><?= __('iti_pref_trekking') ?></span></label>
        <label class="iti-choice"><input type="checkbox" name="prefs[]" value="cà phê"><span class="iti-choice-icon">◒</span><span><?= __('iti_pref_coffee') ?></span></label>
        <label class="iti-choice"><input type="checkbox" name="prefs[]" value="gia đình"><span class="iti-choice-icon">♡</span><span><?= __('iti_pref_family') ?></span></label>
        <label class="iti-choice"><input type="checkbox" name="prefs[]" value="chụp ảnh"><span class="iti-choice-icon">▣</span><span><?= __('iti_pref_photo') ?></span></label>
      </div>
    </div>
    <div class="form-group">
      <label><?= __('iti_form_extra') ?></label>
      <div class="iti-notes-field">
        <textarea name="notes" id="notes" rows="3" placeholder="<?= __('iti_form_extra_ph') ?>"><?= $prefill ? __('iti_prefill_prefix') . ' ' . e($prefill) : '' ?></textarea>
        <button type="button" id="mic-btn" class="iti-mic-btn" title="<?= __('iti_mic_title') ?>">🎤</button>
      </div>
    </div>
    <div class="form-group iti-origin-group">
      <label><?= __('iti_origin_label') ?></label>
      <p class="iti-origin-hint"><?= __('iti_origin_hint') ?></p>
      <div class="iti-origin-modes">
        <label class="iti-origin-card"><input type="radio" name="origin_mode" value="none" checked> <span>⚪ <?= __('iti_origin_none') ?></span></label>
        <label class="iti-origin-card"><input type="radio" name="origin_mode" value="current"> <span>📍 <?= __('iti_origin_current') ?></span></label>
        <label class="iti-origin-card"><input type="radio" name="origin_mode" value="accommodation"> <span>🏨 <?= __('iti_origin_accommodation') ?></span></label>
        <label class="iti-origin-card"><input type="radio" name="origin_mode" value="manual"> <span>✏️ <?= __('iti_origin_manual') ?></span></label>
      </div>
      <div id="origin-acc-box" style="display:none; margin-top:10px;">
        <select id="origin-acc-select" style="width:100%; border-radius:10px; border:1px solid #cbd5e1; padding:10px 14px; font-weight:600;"><option value=""><?= __('iti_origin_acc_ph') ?></option></select>
      </div>
      <div id="origin-manual-box" class="iti-origin-manual-row" style="display:none; margin-top:10px;">
        <input type="text" id="origin-address" placeholder="<?= __('iti_origin_manual_ph') ?>">
        <button type="button" class="btn secondary" id="origin-find-btn" style="border-radius:10px; font-weight:700;"><?= __('iti_origin_find_btn') ?></button>
      </div>
      <div id="origin-status" class="iti-origin-status" aria-live="polite"></div>
      
      <div class="iti-radius-container">
        <div class="iti-radius-header">
          <label for="radius-km">🎯 <?= __('iti_radius_label') ?></label>
          <span class="iti-radius-badge"><span id="radius-km-val">30</span> km</span>
        </div>
        <input type="range" id="radius-km" class="iti-range-input" min="5" max="80" step="5" value="30">
        <div class="iti-preset-pills">
          <button type="button" class="iti-preset-btn" onclick="setRadiusPreset(15, this)">15 km</button>
          <button type="button" class="iti-preset-btn active" onclick="setRadiusPreset(30, this)">30 km</button>
          <button type="button" class="iti-preset-btn" onclick="setRadiusPreset(50, this)">50 km</button>
          <button type="button" class="iti-preset-btn" onclick="setRadiusPreset(80, this)">80 km</button>
        </div>
      </div>

      <label class="iti-weather-card">
        <div class="iti-weather-info">
          <span style="font-size:20px;">🌦️</span>
          <span><?= __('iti_use_weather') ?></span>
        </div>
        <div class="iti-switch">
          <input type="checkbox" id="use-weather" checked>
          <span class="iti-switch-slider"></span>
        </div>
      </label>
    </div>
    <div class="iti-form-submit-row"><span>⌁ <?= __('iti_form_extra') ?></span><button type="submit" class="btn iti-submit-btn">✨ <?= __('iti_form_submit') ?></button></div>
  </form>
</div>

</div>

<div id="weather-panel" class="route-info-panel" style="display:none;" aria-live="polite"></div>

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

<div id="nearby-panel" class="route-info-panel" style="display:none;" aria-live="polite"></div>

<div id="result" aria-live="polite" aria-atomic="false"></div>

<script>
window.setRadiusPreset = function(val, btn) {
    const radiusEl = document.getElementById('radius-km');
    const radiusValEl = document.getElementById('radius-km-val');
    if (radiusEl) {
        radiusEl.value = val;
        if (radiusValEl) radiusValEl.textContent = val;
    }
    document.querySelectorAll('.iti-preset-btn').forEach(b => b.classList.remove('active'));
    if (btn) btn.classList.add('active');
};

if (!window.itineraryEventsAttached) {

    window.itineraryEventsAttached = true;
    document.addEventListener('beforeLangSwitch', function() {
        const form = document.getElementById('itinerary-form');
        if (form) {
            window.savedItiForm = {
                days: document.getElementById('days').value,
                prefs: Array.from(form.querySelectorAll('input[name="prefs[]"]:checked')).map(c => c.value),
                notes: document.getElementById('notes').value,
                radius: document.getElementById('radius-km') ? document.getElementById('radius-km').value : '30',
                useWeather: document.getElementById('use-weather') ? document.getElementById('use-weather').checked : true
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

            const radiusEl = document.getElementById('radius-km');
            if (radiusEl && window.savedItiForm.radius) {
                radiusEl.value = window.savedItiForm.radius;
                const rv = document.getElementById('radius-km-val');
                if (rv) rv.textContent = window.savedItiForm.radius;
            }
            const weatherEl = document.getElementById('use-weather');
            if (weatherEl && typeof window.savedItiForm.useWeather === 'boolean') {
                weatherEl.checked = window.savedItiForm.useWeather;
            }
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
const ITI_LANG = '<?= ($_SESSION['lang'] ?? 'vi') === 'en' ? 'en' : 'vi' ?>';
const ORIGIN_LS_KEY = 'daklak_iti_origin';
const RISK_STYLE = {
  good:             { bg:'#dcfce7', fg:'#166534' },
  caution:          { bg:'#fef9c3', fg:'#854d0e' },
  indoor_preferred: { bg:'#ffedd5', fg:'#9a3412' },
  unsafe:           { bg:'#fee2e2', fg:'#7f1d1d' }
};

function escapeHtml(s) { const d = document.createElement('div'); d.textContent = s ?? ''; return d.innerHTML; }

// ---------- Điểm xuất phát + context (thời tiết & gần đây) ----------
let itiOrigin = null;
window.itiOrigin = null;
let contextSeq = 0;
const originStatus = document.getElementById('origin-status');
const radiusInput = document.getElementById('radius-km');
const radiusVal = document.getElementById('radius-km-val');

function toggleOriginBoxes(mode) {
  document.getElementById('origin-acc-box').style.display = mode === 'accommodation' ? 'block' : 'none';
  document.getElementById('origin-manual-box').style.display = mode === 'manual' ? 'flex' : 'none';
}

function setOrigin(origin) {
  itiOrigin = origin;
  window.itiOrigin = origin;
  if (origin) {
    localStorage.setItem(ORIGIN_LS_KEY, JSON.stringify(origin));
    originStatus.innerHTML = '✅ <?= __('iti_origin_set') ?> <strong>' + escapeHtml(origin.label || '') + '</strong>'
      + '<button type="button" id="origin-clear-btn" class="iti-origin-clear"><?= __('iti_origin_clear') ?></button>';
    document.getElementById('origin-clear-btn').addEventListener('click', clearOrigin);
    loadContext();
  } else {
    localStorage.removeItem(ORIGIN_LS_KEY);
    originStatus.innerHTML = '';
    document.getElementById('weather-panel').style.display = 'none';
    document.getElementById('nearby-panel').style.display = 'none';
  }
}

function clearOrigin() {
  const noneRadio = document.querySelector('input[name="origin_mode"][value="none"]');
  if (noneRadio) noneRadio.checked = true;
  toggleOriginBoxes('none');
  setOrigin(null);
}

let accLoaded = false;
async function loadAccommodations() {
  if (accLoaded) return;
  try {
    const res = await fetch('<?= url('/api/itinerary_options.php') ?>?type=accommodation');
    const data = await res.json();
    const sel = document.getElementById('origin-acc-select');
    (data.options || []).forEach(o => {
      if (!o.lat || !o.lng) return;
      const opt = document.createElement('option');
      opt.value = o.id;
      opt.textContent = o.title + (o.address ? ' — ' + o.address : '');
      opt.dataset.label = o.title;
      sel.appendChild(opt);
    });
    accLoaded = true;
  } catch (e) { console.error(e); }
}

document.querySelectorAll('input[name="origin_mode"]').forEach(r => r.addEventListener('change', () => {
  const mode = r.value;
  toggleOriginBoxes(mode);
  if (mode === 'none') { setOrigin(null); return; }
  if (mode === 'current') {
    if (!navigator.geolocation) { originStatus.textContent = '⚠️ <?= __('iti_origin_geo_denied') ?>'; return; }
    originStatus.textContent = '⏳ <?= __('iti_origin_locating') ?>';
    navigator.geolocation.getCurrentPosition(
      pos => setOrigin({ type: 'current', lat: +pos.coords.latitude.toFixed(3), lng: +pos.coords.longitude.toFixed(3), label: '<?= __('iti_origin_current') ?>' }),
      () => { originStatus.textContent = '⚠️ <?= __('iti_origin_geo_denied') ?>'; },
      { timeout: 10000 }
    );
  }
  if (mode === 'accommodation') loadAccommodations();
}));

document.getElementById('origin-acc-select').addEventListener('change', (e) => {
  const sel = e.target;
  const id = parseInt(sel.value, 10);
  if (!id) { setOrigin(null); return; }
  setOrigin({ type: 'accommodation', accommodation_id: id, label: sel.options[sel.selectedIndex].dataset.label || '' });
});

async function geocodeManual() {
  const q = document.getElementById('origin-address').value.trim();
  if (q.length < 3) return;
  originStatus.textContent = '⏳ …';
  try {
    const res = await fetch('<?= url('/api/geocode.php') ?>?q=' + encodeURIComponent(q));
    const data = await res.json();
    if (data.success) setOrigin({ type: 'manual', lat: data.lat, lng: data.lng, label: data.display_name || q });
    else originStatus.textContent = '⚠️ ' + escapeHtml(data.error || '<?= __('iti_origin_not_found') ?>');
  } catch { originStatus.textContent = '⚠️ <?= __('iti_origin_not_found') ?>'; }
}
document.getElementById('origin-find-btn').addEventListener('click', geocodeManual);
document.getElementById('origin-address').addEventListener('keydown', (e) => {
  if (e.key === 'Enter') { e.preventDefault(); geocodeManual(); }
});

radiusInput.addEventListener('input', () => { radiusVal.textContent = radiusInput.value; });
radiusInput.addEventListener('change', () => { if (itiOrigin) loadContext(); });
document.getElementById('days').addEventListener('change', () => { if (itiOrigin) loadContext(); });

async function loadContext() {
  if (!itiOrigin) return;
  const seq = ++contextSeq;
  const wp = document.getElementById('weather-panel');
  wp.style.display = 'block';
  wp.innerHTML = '<p class="loading-dots">🌦️ <?= __('iti_context_loading') ?></p>';
  try {
    const res = await fetch('<?= url('/api/itinerary_context.php') ?>', {
      method: 'POST', headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ origin: itiOrigin, days: +document.getElementById('days').value, radius_km: +radiusInput.value })
    });
    const ctx = await res.json();
    if (seq !== contextSeq) return;
    if (!ctx.success) { wp.innerHTML = '<p style="color:#b45309;">⚠️ ' + escapeHtml(ctx.error || '') + '</p>'; return; }
    if (ctx.origin && ctx.origin.lat) {
      itiOrigin = Object.assign({}, itiOrigin, { lat: ctx.origin.lat, lng: ctx.origin.lng });
      window.itiOrigin = itiOrigin;
      localStorage.setItem(ORIGIN_LS_KEY, JSON.stringify(itiOrigin));
    }
    renderWeatherPanel(ctx);
    renderNearbyPanel(ctx);
  } catch (e) {
    if (seq === contextSeq) wp.innerHTML = '<p style="color:#888;">ℹ️ <?= __('iti_weather_unavailable') ?></p>';
  }
}

window.renderWeatherPanel = function(ctx) {
  const wp = document.getElementById('weather-panel');
  const w = ctx.weather || {};
  let html = '<h3>🌤️ <?= __('iti_weather_title') ?></h3>';
  if (!w.available) {
    html += '<p style="color:#888;">ℹ️ <?= __('iti_weather_unavailable') ?></p>';
  } else {
    html += '<div class="weather-days">';
    (w.daily || []).forEach(d => {
      const st = RISK_STYLE[d.risk] || RISK_STYLE.good;
      const temp = (d.temp_min != null && d.temp_max != null) ? Math.round(d.temp_min) + '–' + Math.round(d.temp_max) + '°C' : '';
      const rain = d.precipitation_probability_max != null ? ' · ☔ ' + Math.round(d.precipitation_probability_max) + '%' : '';
      html += '<div class="weather-day" style="background:' + st.bg + ';color:' + st.fg + ';">'
        + '<div class="weather-day-date">' + escapeHtml(d.date || '') + '</div>'
        + '<div class="weather-day-icon">' + (d.icon || '') + '</div>'
        + '<div class="weather-day-desc">' + escapeHtml(d['text_' + ITI_LANG] || '') + '</div>'
        + '<div class="weather-day-temp">' + temp + rain + '</div>'
        + '</div>';
    });
    html += '</div>';
  }
  const adv = ctx.advisories || [];
  if (adv.length) {
    html += '<div class="weather-advisories"><strong>⚠️ <?= __('iti_advisory_title') ?></strong>';
    adv.forEach(a => { html += '<div class="weather-advisory">' + escapeHtml(a) + '</div>'; });
    html += '</div>';
  }
  wp.innerHTML = html;
  wp.style.display = 'block';
}

function renderNearbyPanel(ctx) {
  const np = document.getElementById('nearby-panel');
  const nb = ctx.nearby || {};
  const groups = [
    ['🏞️ <?= __('iti_nearby_dest') ?>', nb.destinations || []],
    ['🍜 <?= __('iti_nearby_food') ?>', nb.foods || []],
    ['🏨 <?= __('iti_nearby_acc') ?>', nb.accommodations || []]
  ];
  let any = false;
  let html = '<h3>📍 <?= __('iti_nearby_title') ?></h3>';
  groups.forEach(([label, rows]) => {
    if (!rows.length) return;
    any = true;
    html += '<div class="nearby-group"><div class="nearby-group-title">' + label + '</div><div class="nearby-chips">';
    rows.forEach(rw => {
      const chip = escapeHtml(rw.name) + ' <em>' + rw.distance_km + 'km</em>';
      html += rw.slug
        ? '<a class="nearby-chip" href="<?= url('/public/destination.php') ?>?slug=' + encodeURIComponent(rw.slug) + '" title="' + escapeHtml(rw.address || '') + '">' + chip + '</a>'
        : '<span class="nearby-chip" title="' + escapeHtml(rw.address || '') + '">' + chip + '</span>';
    });
    html += '</div></div>';
  });
  if (!any) html += '<p style="color:#888;"><?= __('iti_nearby_empty') ?></p>';
  np.innerHTML = html;
  np.style.display = 'block';
}

// Khôi phục origin đã lưu từ lần trước
try {
  const saved = JSON.parse(localStorage.getItem(ORIGIN_LS_KEY) || 'null');
  if (saved && saved.type) {
    const radio = document.querySelector('input[name="origin_mode"][value="' + saved.type + '"]');
    if (radio) { radio.checked = true; toggleOriginBoxes(saved.type); }
    if (saved.type === 'manual') document.getElementById('origin-address').value = saved.label || '';
    if (saved.type === 'accommodation') {
      loadAccommodations().then(() => {
        const sel = document.getElementById('origin-acc-select');
        sel.value = String(saved.accommodation_id || '');
      });
    }
    setOrigin(saved);
  }
} catch (e) { /* localStorage hỏng thì bỏ qua */ }

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

  resultBox.innerHTML = `
    <div class="iti-loading-box">
      <div class="iti-loading-pulse">
        <div class="iti-loading-avatar">🤖</div>
        <div class="iti-loading-ripple"></div>
      </div>
      <h3 class="iti-loading-title">🤖 <?= __('iti_loading') ?></h3>
      <p class="iti-loading-sub">AI đang tính toán địa điểm, kiểm tra thời tiết & tối ưu hóa tuyến đường cho bạn...</p>
      <div class="iti-loading-progress-bar">
        <div class="iti-loading-progress-fill"></div>
      </div>
    </div>
  `;
  resultBox.scrollIntoView({ behavior: 'smooth', block: 'start' });

  document.getElementById('stats-bar').style.display     = 'none';
  document.getElementById('map-container').style.display = 'none';
  document.getElementById('route-panel').style.display   = 'none';
  if (window.itineraryMap) { window.itineraryMap.remove(); window.itineraryMap = null; }

  try {
    const res  = await fetch('<?= url('/api/generate_itinerary.php') ?>', {
      method: 'POST', headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        days, prefs, notes,
        origin: itiOrigin || undefined,
        radius_km: +radiusInput.value,
        use_weather: document.getElementById('use-weather').checked
      })
    });
    if (!res.ok) {
      throw new Error('HTTP ' + res.status);
    }
    const data = await res.json();
    if (!data.success) {
      resultBox.innerHTML = '<div class="geo-warnings" style="margin:20px auto; max-width:600px;">❌ ' + escapeHtml(data.message || '<?= __('dest_error_occurred') ?>') + '</div>';
      return;
    }
    
    window.lastItineraryData = data;
    await renderItinerary(data);

  } catch (err) {
    resultBox.innerHTML = `
      <div class="geo-warnings" style="margin:20px auto; max-width:540px; text-align:center; padding:20px;">
        <p style="font-weight:700; font-size:15px; margin-bottom:8px;">⚠️ Không thể kết nối hoặc phản hồi quá thời gian cho phép.</p>
        <p style="font-size:13px; color:#7f1d1d; margin-bottom:14px;">Hệ thống AI đang xử lý lượng dữ liệu lớn. Bạn hãy nhấn thử lại nhé!</p>
        <button type="button" class="btn" onclick="document.getElementById('itinerary-form').dispatchEvent(new Event('submit'))" style="background:#dc2626; color:#fff; border-radius:10px; font-weight:700;">🔄 Thử lại ngay</button>
      </div>
    `;
    console.error("Itinerary generation error:", err);
  }
});


window.renderItinerary = async function(data) {
    let html = `
      <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;">
        <h2 class="section-title" style="margin:0;"><?= __('iti_suggested_title') ?></h2>
        <div style="display:flex; gap:10px;">
          <button type="button" onclick="simulateRain()" class="btn" style="background:#dc2626; display:flex; align-items:center; gap:6px;">
            <?= __('iti_reroute_btn') ?>
          </button>
          <button type="button" onclick="exportItineraryPDF()" class="btn secondary" style="display:flex; align-items:center; gap:6px;">
            📄 <?= __('iti_export_pdf') ?>
          </button>
        </div>
      </div>
      <div id="pdf-export-content" style="margin-top:20px;">
    `;
    if (data.geo_warnings && data.geo_warnings.length) {
      html += '<div class="geo-warnings"><strong>🧭 <?= __('iti_geo_warn_title') ?></strong>'
        + data.geo_warnings.map(w => '<div>' + escapeHtml(w) + '</div>').join('') + '</div>';
    }
    data.itinerary.forEach(day => {
      html += `<div class="day-block"><h3><?= __('iti_day') ?> ${day.day}${day.title ? ': ' + day.title : ''}</h3>`;
      day.items.forEach(item => {
        const addr  = item.address  ? `<div class="time-slot-address">📍 ${item.address}</div>` : '';
        const trans = item.transport? `<div class="time-slot-transport" style="font-size:13px;color:var(--green-700);margin-top:4px;font-weight:500;">🛵 ${item.transport}</div>` : '';
        const price = item.price    ? `<div class="time-slot-price">🎟️ <strong><?= __('iti_cost') ?>:</strong> ${item.price}</div>` : '';
        const sugg  = item.suggestion ? `<div class="time-slot-suggestion" style="margin-top:8px; padding:8px 12px; background-color:#fffbea; border-left:4px solid #f59e0b; color:#92400e; font-size:13px; border-radius:6px;"><?= __('iti_suggestion') ?>${item.suggestion}</div>` : '';
        const reason = item.reason ? `<div class="time-slot-reason" style="margin-top:8px; padding:6px 10px; background-color:#e8f4fd; border-left:4px solid #3b82f6; color:#1e40af; font-size:13px; border-radius:6px;"><?= __('iti_reason') ?>${item.reason}</div>` : '';
        const impact = item.community_impact ? `<div class="time-slot-impact" style="margin-top:6px; padding:6px 10px; background-color:#dcfce7; border-left:4px solid #22c55e; color:#166534; font-size:13px; border-radius:6px;"><?= __('iti_community_impact') ?>${item.community_impact}</div>` : '';
        const wnote = item.weather_note ? `<div class="time-slot-weather" style="margin-top:6px; padding:6px 10px; background-color:#e0f2fe; border-left:4px solid #0ea5e9; color:#075985; font-size:13px; border-radius:6px;">🌦️ <strong><?= __('iti_weather_note') ?></strong> ${item.weather_note}</div>` : '';
        const dist = (item.distance_from_origin_km != null && item.distance_from_origin_km !== '') ? `<span style="font-size:12px;color:var(--green-700);font-weight:600;"> · 📏 ${item.distance_from_origin_km}km</span>` : '';

        html += `<div class="time-slot"><strong>${item.time || ''}:</strong> ${item.activity}${dist}${wnote}${reason}${impact}${sugg}${addr}${trans}${price}</div>`;
      });
      html += '</div>';
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

    // Marker điểm xuất phát + vòng tròn bán kính tìm kiếm
    const fitMarkers = allMarkers.slice();
    const originData = (data.origin && data.origin.lat) ? data.origin : (window.itiOrigin && window.itiOrigin.lat ? window.itiOrigin : null);
    if (originData) {
      const originIcon = L.divIcon({
        className: '',
        html: '<div style="font-size:28px;line-height:1;filter:drop-shadow(0 2px 4px rgba(0,0,0,.4));">🏨</div>',
        iconSize: [28, 28], iconAnchor: [14, 26], tooltipAnchor: [0, -26]
      });
      const om = L.marker([originData.lat, originData.lng], { icon: originIcon, zIndexOffset: 1000 }).addTo(window.itineraryMap);
      om.bindTooltip('<?= __('iti_origin_set') ?> ' + escapeHtml(originData.label || ''), { direction: 'top' });
      const rKm = data.radius_km || +radiusInput.value || 30;
      L.circle([originData.lat, originData.lng], { radius: rKm * 1000, color: '#2d6a4f', weight: 1.5, dashArray: '6,8', fillColor: '#2d6a4f', fillOpacity: 0.05 }).addTo(window.itineraryMap);
      fitMarkers.push(om);
    }

    if (fitMarkers.length === 1) window.itineraryMap.setView(fitMarkers[0].getLatLng(), 13);
    else if (fitMarkers.length > 1) window.itineraryMap.fitBounds(new L.featureGroup(fitMarkers).getBounds().pad(0.18));

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

    // Cập nhật panel thời tiết theo dữ liệu trả về cùng lịch trình (nếu có)
    if (data.weather) window.renderWeatherPanel({ weather: data.weather, advisories: data.advisories || [] });

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

// Voice input
const micBtn = document.getElementById('mic-btn');
const notesEl = document.getElementById('notes');
if (window.SpeechRecognition || window.webkitSpeechRecognition) {
  const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
  const recognition = new SpeechRecognition();
  recognition.lang = 'vi-VN';
  recognition.interimResults = false;
  
  micBtn.addEventListener('click', () => {
    if (micBtn.textContent === '🔴') {
        recognition.stop();
        return;
    }
    micBtn.textContent = '🔴';
    recognition.start();
  });
  
  recognition.onresult = (event) => {
    const transcript = event.results[0][0].transcript;
    notesEl.value += (notesEl.value ? ' ' : '') + transcript;
  };
  
  recognition.onerror = (event) => {
    console.error('Speech recognition error detected: ' + event.error);
    if (event.error === 'not-allowed') {
        alert('<?= __('iti_mic_blocked') ?>');
    } else if (event.error === 'network') {
        alert('<?= __('iti_mic_network') ?>');
    } else {
        alert('<?= __('iti_mic_error') ?>' + event.error + '<?= __('iti_mic_try_again') ?>');
    }
    micBtn.textContent = '🎤';
  };
  
  recognition.onend = () => {
    micBtn.textContent = '🎤';
  };
} else {
  micBtn.style.display = 'none';
}
})();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
