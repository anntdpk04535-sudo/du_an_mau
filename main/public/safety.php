<?php
require_once __DIR__ . '/../includes/functions.php';
$pageTitle = __('page_title_safety');
include __DIR__ . '/../includes/header.php';
?>

<style>
.safety-banner {
    background: linear-gradient(135deg, #f97316, #ea580c);
    color: white;
    border-radius: var(--radius);
    padding: 32px 40px;
    margin-bottom: 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 10px 25px rgba(234, 88, 12, 0.2);
}
.safety-banner-content h1 {
    font-size: 28px;
    margin: 0 0 10px;
    color: white;
}
.safety-banner-content p {
    font-size: 15px;
    margin: 0;
    opacity: 0.9;
    max-width: 600px;
    line-height: 1.5;
}
.safety-banner-icon {
    font-size: 70px;
    opacity: 0.2;
}

.weather-safe-box {
    background: #ecfdf5;
    border: 1px solid #a7f3d0;
    border-left: 6px solid #10b981;
    border-radius: var(--radius);
    padding: 24px;
    margin-bottom: 40px;
}
.weather-safe-box h3 {
    color: #047857;
    margin: 0 0 8px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.weather-safe-box p {
    color: #065f46;
    margin: 0 0 16px;
    font-size: 14px;
}
.weather-badge {
    display: inline-block;
    background: #d1fae5;
    color: #065f46;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 600;
}

.emergency-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 16px;
    margin-bottom: 40px;
}
.emergency-card {
    background: white;
    border: 1px solid #fecaca;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.03);
    transition: transform 0.2s;
}
.emergency-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(220, 38, 38, 0.1);
}
.emergency-card h4 {
    color: #dc2626;
    margin: 0 0 8px;
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 6px;
}
.emergency-card .number {
    font-size: 24px;
    font-weight: 800;
    color: #991b1b;
    margin: 0 0 10px;
    letter-spacing: 1px;
}
.emergency-card p {
    font-size: 12px;
    color: #64748b;
    margin: 0;
    line-height: 1.5;
}

.terrain-list {
    display: flex;
    flex-direction: column;
    gap: 20px;
    margin-bottom: 40px;
}
.terrain-card {
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: var(--radius);
    padding: 24px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.03);
}
.terrain-card h3 {
    margin: 0 0 16px;
    color: var(--green-800);
    display: flex;
    align-items: center;
    gap: 8px;
}
.terrain-card ul {
    margin: 0;
    padding-left: 20px;
    color: #475569;
}
.terrain-card li {
    margin-bottom: 12px;
    line-height: 1.5;
    font-size: 14px;
}
.terrain-card li strong {
    color: #334155;
}
.terrain-card li:last-child {
    margin-bottom: 0;
}

.season-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
    margin-bottom: 40px;
}
@media (max-width: 768px) {
    .season-grid { grid-template-columns: 1fr; }
}
.season-card {
    background: white;
    border-radius: var(--radius);
    padding: 24px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.03);
}
.season-card.rain {
    border: 1px solid #bae6fd;
    background: linear-gradient(to bottom, #f0f9ff, white);
}
.season-card.dry {
    border: 1px solid #fef08a;
    background: linear-gradient(to bottom, #fefce8, white);
}
.season-card h3 {
    margin: 0 0 16px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.season-card.rain h3 { color: #0369a1; }
.season-card.dry h3 { color: #a16207; }
.season-card ul {
    margin: 0;
    padding-left: 20px;
    color: #475569;
}
.season-card li {
    margin-bottom: 10px;
    font-size: 14px;
}
</style>

<div class="safety-banner">
    <div class="safety-banner-content">
        <h1>⚠️ <?= __('safety_title') ?></h1>
        <p><?= __('safety_sub') ?></p>
    </div>
    <div class="safety-banner-icon">🛡️</div>
</div>

<h2 class="section-title">🌤️ <?= __('weather_title') ?></h2>
<p class="section-sub"><?= __('weather_sub') ?></p>
<div class="weather-safe-box">
    <h3 id="weather-safety-title">✅ <?= __('safety_weather_normal') ?></h3>
    <p id="weather-safety-desc"><?= __('safety_weather_desc') ?></p>
    <?php 
    $m = (int)date('m'); 
    $isRainy = ($m >= 5 && $m <= 10);
    ?>
    <div class="weather-badge">
        <?= $isRainy ? '🌧️ ' . __('rainy_season') : '☀️ ' . __('dry_season') ?>
    </div>
</div>

<script>
// (Đã tắt tính năng tự động đổi màu cảnh báo thời tiết để giao diện luôn hiển thị trạng thái AN TOÀN theo đúng thiết kế)
/*
document.addEventListener('DOMContentLoaded', async function() {
    try {
        const res = await fetch('https://api.open-meteo.com/v1/forecast?latitude=12.6667&longitude=108.0500&current_weather=true');
        const data = await res.json();
        if (data.current_weather) {
            const code = data.current_weather.weathercode;
            const title = document.getElementById('weather-safety-title');
            const desc = document.getElementById('weather-safety-desc');
            
            // Xử lý các code thời tiết xấu
            if (code >= 95) { // Dông bão
                title.innerHTML = '⚠️ CẢNH BÁO — Có mưa dông';
                title.style.color = '#b91c1c';
                desc.textContent = 'Thời tiết hiện đang có mưa dông, sấm sét. Hạn chế di chuyển vào rừng, thác nước hay các khu vực hồ rộng. Tìm nơi trú ẩn an toàn.';
                title.parentElement.style.background = '#fef2f2';
                title.parentElement.style.border = '1px solid #fecaca';
                title.parentElement.style.borderLeft = '6px solid #ef4444';
            } else if (code >= 61 && code <= 82) { // Mưa lớn
                title.innerHTML = '☔ LƯU Ý — Có mưa rào';
                title.style.color = '#0369a1';
                desc.textContent = 'Thời tiết hiện đang có mưa. Hãy chuẩn bị áo mưa, ô dù và cẩn thận đường trơn trượt nếu đi xe máy hoặc leo núi.';
                title.parentElement.style.background = '#f0f9ff';
                title.parentElement.style.border = '1px solid #bae6fd';
                title.parentElement.style.borderLeft = '6px solid #3b82f6';
            }
        }
    } catch (e) { console.error('Không thể tải thời tiết:', e); }
});
*/
</script>

<h2 class="section-title">🚨 <?= __('emergency_title') ?></h2>
<p class="section-sub"><?= __('emergency_sub') ?></p>
<div class="emergency-grid">
    <div class="emergency-card">
        <h4>🚑 <?= __('emer_medical') ?></h4>
        <div class="number">115</div>
        <p><?= __('emer_medical_desc') ?></p>
    </div>
    <div class="emergency-card">
        <h4>🚒 <?= __('emer_fire') ?></h4>
        <div class="number">114</div>
        <p><?= __('emer_fire_desc') ?></p>
    </div>
    <div class="emergency-card">
        <h4>🚓 <?= __('emer_police') ?></h4>
        <div class="number">113</div>
        <p><?= __('emer_police_desc') ?></p>
    </div>
    <div class="emergency-card">
        <h4>🏥 <?= __('emer_hospital') ?></h4>
        <div class="number">0262 385 2258</div>
        <p><?= __('emer_hospital_desc') ?></p>
    </div>
    <div class="emergency-card">
        <h4>🌲 <?= __('emer_yokdon') ?></h4>
        <div class="number">0262 378 3053</div>
        <p><?= __('emer_yokdon_desc') ?></p>
    </div>
    <div class="emergency-card">
        <h4>🐘 <?= __('emer_buondon') ?></h4>
        <div class="number">0262 378 6111</div>
        <p><?= __('emer_buondon_desc') ?></p>
    </div>
    <div class="emergency-card">
        <h4>🌊 <?= __('emer_draynur') ?></h4>
        <div class="number">0262 363 6789</div>
        <p><?= __('emer_draynur_desc') ?></p>
    </div>
    <div class="emergency-card">
        <h4>🚕 <?= __('emer_taxi') ?></h4>
        <div class="number">0262 383 8383</div>
        <p><?= __('emer_taxi_desc') ?></p>
    </div>
</div>

<h2 class="section-title">🗺️ <?= __('terrain_title') ?></h2>
<p class="section-sub"><?= __('terrain_sub') ?></p>
<div class="terrain-list">
    <div class="terrain-card">
        <h3>🌊 <?= __('terrain_waterfall') ?></h3>
        <ul>
            <li><strong><?= __('terrain_wf_1_title') ?>:</strong> <?= __('terrain_wf_1_desc') ?></li>
            <li><strong><?= __('terrain_wf_2_title') ?>:</strong> <?= __('terrain_wf_2_desc') ?></li>
            <li><strong><?= __('terrain_wf_3_title') ?>:</strong> <?= __('terrain_wf_3_desc') ?></li>
            <li><strong><?= __('terrain_wf_4_title') ?>:</strong> <?= __('terrain_wf_4_desc') ?></li>
        </ul>
    </div>
    <div class="terrain-card">
        <h3>🌲 <?= __('terrain_forest') ?></h3>
        <ul>
            <li><strong><?= __('terrain_fr_1_title') ?>:</strong> <?= __('terrain_fr_1_desc') ?></li>
            <li><strong><?= __('terrain_fr_2_title') ?>:</strong> <?= __('terrain_fr_2_desc') ?></li>
            <li><strong><?= __('terrain_fr_3_title') ?>:</strong> <?= __('terrain_fr_3_desc') ?></li>
            <li><strong><?= __('terrain_fr_4_title') ?>:</strong> <?= __('terrain_fr_4_desc') ?></li>
        </ul>
    </div>
    <div class="terrain-card">
        <h3>🛶 <?= __('terrain_lake') ?></h3>
        <ul>
            <li><strong><?= __('terrain_lk_1_title') ?>:</strong> <?= __('terrain_lk_1_desc') ?></li>
            <li><strong><?= __('terrain_lk_2_title') ?>:</strong> <?= __('terrain_lk_2_desc') ?></li>
            <li><strong><?= __('terrain_lk_3_title') ?>:</strong> <?= __('terrain_lk_3_desc') ?></li>
        </ul>
    </div>
    <div class="terrain-card">
        <h3>🛖 <?= __('terrain_village') ?></h3>
        <ul>
            <li><strong><?= __('terrain_vl_1_title') ?>:</strong> <?= __('terrain_vl_1_desc') ?></li>
            <li><strong><?= __('terrain_vl_2_title') ?>:</strong> <?= __('terrain_vl_2_desc') ?></li>
            <li><strong><?= __('terrain_vl_3_title') ?>:</strong> <?= __('terrain_vl_3_desc') ?></li>
        </ul>
    </div>
</div>

<h2 class="section-title">📅 <?= __('season_title') ?></h2>
<div class="season-grid">
    <div class="season-card rain">
        <h3>🌧️ <?= __('season_rainy_title') ?></h3>
        <ul>
            <li><?= __('season_rainy_1') ?></li>
            <li><?= __('season_rainy_2') ?></li>
            <li><?= __('season_rainy_3') ?></li>
            <li><?= __('season_rainy_4') ?></li>
            <li><?= __('season_rainy_5') ?></li>
        </ul>
    </div>
    <div class="season-card dry">
        <h3>☀️ <?= __('season_dry_title') ?></h3>
        <ul>
            <li><?= __('season_dry_1') ?></li>
            <li><?= __('season_dry_2') ?></li>
            <li><?= __('season_dry_3') ?></li>
            <li><?= __('season_dry_4') ?></li>
            <li><?= __('season_dry_5') ?></li>
        </ul>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
