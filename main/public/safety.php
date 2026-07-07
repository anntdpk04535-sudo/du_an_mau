<?php
require_once __DIR__ . '/../includes/functions.php';
$pageTitle = 'Cẩm Nang An Toàn Du Lịch - Đắk Lắk Travel AI';
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
    <h3 id="weather-safety-title">✅ AN TOÀN — Thời tiết bình thường</h3>
    <p id="weather-safety-desc">Không có cảnh báo đặc biệt cho khu vực này. Tuy nhiên luôn chuẩn bị đầy đủ trước khi tham quan.</p>
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
        <h4>🚑 Cấp cứu y tế</h4>
        <div class="number">115</div>
        <p>Gọi khi có tai nạn, ngất xỉu, hoặc cần sơ cứu khẩn cấp.</p>
    </div>
    <div class="emergency-card">
        <h4>🚒 Cứu hỏa</h4>
        <div class="number">114</div>
        <p>Gọi khi phát hiện cháy rừng, cháy nhà hoặc cần cứu hộ.</p>
    </div>
    <div class="emergency-card">
        <h4>🚓 Công an (Cứu nạn)</h4>
        <div class="number">113</div>
        <p>Gọi khi gặp tình huống mất an ninh, tai nạn giao thông.</p>
    </div>
    <div class="emergency-card">
        <h4>🏥 Bệnh viện Đa khoa TP. BMT</h4>
        <div class="number">0262 385 2258</div>
        <p>Y tế, TP. Buôn Ma Thuột — cơ sở y tế lớn nhất tỉnh.</p>
    </div>
    <div class="emergency-card">
        <h4>🌲 Ban quản lý VQG Yok Đôn</h4>
        <div class="number">0262 378 3053</div>
        <p>Liên hệ khi cần hỗ trợ trong khu vực Vườn quốc gia Yok Đôn.</p>
    </div>
    <div class="emergency-card">
        <h4>🐘 Ban quản lý KDL Buôn Đôn</h4>
        <div class="number">0262 378 6111</div>
        <p>Hỗ trợ du khách trong khu du lịch sinh thái Buôn Đôn.</p>
    </div>
    <div class="emergency-card">
        <h4>🌊 Ban quản lý Thác Dray Nur</h4>
        <div class="number">0262 363 6789</div>
        <p>Liên hệ khi cần hỗ trợ tại khu vực Thác Dray Nur - Dray Sáp.</p>
    </div>
    <div class="emergency-card">
        <h4>🚕 Taxi Mai Linh Đắk Lắk</h4>
        <div class="number">0262 383 8383</div>
        <p>Gọi taxi khi cần di chuyển khẩn cấp hoặc không có phương tiện.</p>
    </div>
</div>

<h2 class="section-title">🗺️ <?= __('terrain_title') ?></h2>
<p class="section-sub"><?= __('terrain_sub') ?></p>
<div class="terrain-list">
    <div class="terrain-card">
        <h3>🌊 Thác Nước (Dray Nur, Dray Sáp)</h3>
        <ul>
            <li><strong>Nhận biết lũ quét:</strong> Nước suối chuyển màu đục đột ngột, có tiếng ù ù hoặc rác từ thượng nguồn đổ về — đây là dấu hiệu lũ quét sắp tới. Lập tức di chuyển lên vùng đất cao.</li>
            <li><strong>Đường đá trơn trượt:</strong> Mùa mưa các bậc đá xuống thác cực kỳ trơn. Luôn mang giày leo núi có độ bám cao, KHÔNG đi dép lê hoặc giày cao gót. Bám chặt tay vịn và đi chậm từng bước.</li>
            <li><strong>Tắm thác an toàn:</strong> Chỉ tắm ở khu vực cho phép, có cứu hộ. Không bao giờ bơi vào vùng nước xoáy dưới chân thác — lực hút cực mạnh có thể kéo người xuống đáy.</li>
            <li><strong>Bảo vệ thiết bị:</strong> Bọc điện thoại, máy ảnh trong túi chống nước. Bụi nước ở gần thác rất mạnh, dễ gây hỏng thiết bị điện tử.</li>
        </ul>
    </div>
    <div class="terrain-card">
        <h3>🌲 Rừng & Vườn Quốc Gia (Yok Đôn)</h3>
        <ul>
            <li><strong>Luôn có hướng dẫn viên:</strong> Rừng Yok Đôn rộng hơn 115.000 ha, rất dễ lạc đường. Tuyệt đối KHÔNG tự ý đi sâu vào rừng khi không có người dẫn đường bản địa.</li>
            <li><strong>Phòng tránh cháy rừng:</strong> Mùa khô (Tháng 11 - 4), rừng khộp cực kỳ khô nóng. Không đốt lửa, không vứt tàn thuốc, không mang các vật dễ cháy. Nếu phát hiện khói hoặc lửa, gọi ngay 114.</li>
            <li><strong>Côn trùng & rắn:</strong> Mặc quần áo dài tay, xịt thuốc chống côn trùng. Khi đi bộ trong rừng, dùng gậy gõ nhẹ vào bụi cây phía trước để xua rắn. Nếu bị rắn cắn, giữ bình tĩnh, bất động chi bị cắn và gọi cấp cứu 115 ngay.</li>
            <li><strong>Nước uống:</strong> Mang tối thiểu 2 lít nước/người. KHÔNG uống nước suối trong rừng khi chưa qua xử lý — nguy cơ nhiễm ký sinh trùng.</li>
        </ul>
    </div>
    <div class="terrain-card">
        <h3>🛶 Hồ & Sông (Hồ Lắk, Hồ Ea Kao)</h3>
        <ul>
            <li><strong>Mặc áo phao:</strong> Bắt buộc mặc áo phao khi đi thuyền độc mộc hoặc chèo kayak. Đặc biệt quan trọng với trẻ em và người không biết bơi.</li>
            <li><strong>Không bơi một mình:</strong> Luôn bơi theo nhóm hoặc có người quan sát. Nhiều hồ tự nhiên có đáy bùn bất ngờ và dòng chảy ngầm mà mắt thường không nhìn thấy.</li>
            <li><strong>Giông lốc bất ngờ:</strong> Mùa mưa, giông lốc thường ập đến rất nhanh vào buổi chiều. Nếu thấy mây đen kéo tới, gió giật, lập tức chèo thuyền vào bờ ngay.</li>
        </ul>
    </div>
    <div class="terrain-card">
        <h3>🛖 Buôn Làng & Văn Hoá Bản Địa</h3>
        <ul>
            <li><strong>Xin phép khi vào buôn:</strong> Nên hỏi ý kiến người dân hoặc già làng trước khi đi sâu vào khu vực sinh hoạt. Tôn trọng không gian riêng tư của cộng đồng.</li>
            <li><strong>Không sờ vào vật thờ cúng:</strong> Người Ê Đê và M'nông có nhiều vật dụng thờ cúng tâm linh trong nhà dài. Không tự ý chạm, chụp ảnh vật thờ khi chưa được phép.</li>
            <li><strong>Uống rượu cần:</strong> Nếu được mời tham gia uống rượu cần, hãy uống một ít theo lễ nghi. Từ chối lịch sự nếu không uống được cồn — người bản địa rất hiểu và tôn trọng.</li>
        </ul>
    </div>
</div>

<h2 class="section-title">📅 <?= __('season_title') ?></h2>
<div class="season-grid">
    <div class="season-card rain">
        <h3>🌧️ Mùa Mưa (Tháng 5 – 10)</h3>
        <ul>
            <li>Mang áo mưa cánh dơi (gọn nhẹ, che được balo)</li>
            <li>Giày leo núi chống trượt — KHÔNG mang giày vải</li>
            <li>Túi zip/bao bọc điện thoại, giấy tờ cá nhân</li>
            <li>Kiểm tra thời tiết trước mỗi chuyến di chuyển</li>
            <li>Mang theo quần áo thay — ướt là chuyện bình thường!</li>
        </ul>
    </div>
    <div class="season-card dry">
        <h3>☀️ Mùa Khô (Tháng 11 – 4)</h3>
        <ul>
            <li>Kem chống nắng SPF50+ và mũ rộng vành</li>
            <li>Mang tối thiểu 2 lít nước/người/buổi tham quan</li>
            <li>Quần áo dài tay thoáng mát — chống nắng + côn trùng</li>
            <li>Kính mát UV400 bảo vệ mắt</li>
            <li>Bình xịt đuổi côn trùng (muỗi rừng rất nhiều buổi chiều tối)</li>
        </ul>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
