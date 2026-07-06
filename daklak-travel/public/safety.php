<?php
require_once __DIR__ . '/../includes/functions.php';
$pageTitle = 'Cẩm nang An toàn - Đắk Lắk Travel AI';
include __DIR__ . '/../includes/header.php';
?>

<section class="safety-hero">
  <div class="safety-hero-text">
    <h1>⚠️ Cẩm Nang An Toàn Du Lịch Đắk Lắk</h1>
    <p>Thông tin khẩn cấp, hướng dẫn an toàn theo loại địa hình và cảnh báo thời tiết thời gian thực — giúp chuyến đi Tây Nguyên của bạn luôn an toàn.</p>
  </div>
  <div class="safety-hero-icon">🛡️</div>
</section>

<!-- ══════════════════════════════════════
     THỜI TIẾT HIỆN TẠI TẠI ĐẮK LẮK
══════════════════════════════════════ -->
<h2 class="section-title">🌦️ Thời tiết hiện tại tại Đắk Lắk</h2>
<p class="section-sub">Dữ liệu thời tiết khu vực trung tâm TP. Buôn Ma Thuột, cập nhật mỗi 15 phút.</p>

<div id="safety-weather-widget" class="weather-loading">
  🌤️ Đang tải thông tin thời tiết...
</div>

<script>
(function() {
  // Toạ độ trung tâm Buôn Ma Thuột
  const lat = 12.6667, lng = 108.05;
  const el = document.getElementById('safety-weather-widget');

  fetch(`<?= url('/api/weather.php') ?>?lat=${lat}&lng=${lng}&hazard_type=none`)
    .then(r => r.json())
    .then(data => {
      const w = data.weather || {};
      const hasData = w.source !== 'fallback' && w.temp !== null;

      if (!hasData) {
        el.className = 'weather-widget';
        el.innerHTML = `
          <div class="alert-banner alert-${data.alert_level}">
            <p class="alert-banner-title">${data.alert_title}</p>
            <p class="alert-banner-msg">${data.alert_message}</p>
            <div style="margin-top:10px;">
              <span class="season-badge season-${data.season}">${data.season === 'rainy' ? '🌧️ Đang là mùa mưa (Tháng 5–10)' : '☀️ Đang là mùa khô (Tháng 11–4)'}</span>
            </div>
          </div>`;
        return;
      }

      const iconUrl = `https://openweathermap.org/img/wn/${w.icon}@2x.png`;
      el.className = 'weather-widget';
      el.innerHTML = `
        <div class="weather-current">
          <img src="${iconUrl}" alt="${w.description}" class="weather-icon-big">
          <div class="weather-info">
            <div class="weather-temp">${w.temp}°C</div>
            <div class="weather-desc">${w.description} · Cảm giác như ${w.feels_like}°C</div>
          </div>
          <div class="weather-details">
            <span>💧 Độ ẩm: ${w.humidity}%</span>
            <span>💨 Gió: ${w.wind_speed} m/s</span>
            ${w.rain_1h > 0 ? `<span>🌧️ Mưa: ${w.rain_1h}mm/h</span>` : ''}
            <span class="season-badge season-${data.season}">${data.season === 'rainy' ? '🌧️ Mùa mưa' : '☀️ Mùa khô'}</span>
          </div>
        </div>
        <div class="alert-banner alert-${data.alert_level}">
          <p class="alert-banner-title">${data.alert_title}</p>
          <p class="alert-banner-msg">${data.alert_message}</p>
        </div>`;
    })
    .catch(() => {
      el.innerHTML = '<div style="padding:16px;color:#94a3b8;">Không thể tải dữ liệu thời tiết.</div>';
      el.className = '';
    });
})();
</script>

<!-- ══════════════════════════════════════
     DANH BẠ KHẨN CẤP
══════════════════════════════════════ -->
<h2 class="section-title">🚨 Danh Bạ Khẩn Cấp</h2>
<p class="section-sub">Các số điện thoại quan trọng cần lưu sẵn khi đi du lịch Đắk Lắk.</p>

<div class="emergency-grid">
  <div class="emergency-card">
    <div class="emergency-card-title">🚑 Cấp cứu y tế</div>
    <div class="emergency-card-phone"><a href="tel:115">115</a></div>
    <p class="emergency-card-desc">Gọi khi có tai nạn, ngộ độc, hoặc cần xe cấp cứu khẩn cấp.</p>
  </div>
  <div class="emergency-card">
    <div class="emergency-card-title">🚒 Cứu hoả</div>
    <div class="emergency-card-phone"><a href="tel:114">114</a></div>
    <p class="emergency-card-desc">Gọi khi phát hiện cháy rừng, cháy nhà hoặc cần cứu hộ.</p>
  </div>
  <div class="emergency-card">
    <div class="emergency-card-title">👮 Công an (Cứu nạn)</div>
    <div class="emergency-card-phone"><a href="tel:113">113</a></div>
    <p class="emergency-card-desc">Gọi khi gặp tình huống mất an ninh, tai nạn giao thông.</p>
  </div>
  <div class="emergency-card">
    <div class="emergency-card-title">🏥 Bệnh viện Đa khoa TP. Buôn Ma Thuột</div>
    <div class="emergency-card-phone"><a href="tel:02623852258">0262 385 2258</a></div>
    <p class="emergency-card-desc">Y Jut, TP. Buôn Ma Thuột — cơ sở y tế lớn nhất tỉnh.</p>
  </div>
  <div class="emergency-card">
    <div class="emergency-card-title">🌲 Ban quản lý VQG Yok Đôn</div>
    <div class="emergency-card-phone"><a href="tel:02623783053">0262 378 3053</a></div>
    <p class="emergency-card-desc">Liên hệ khi cần hỗ trợ trong khu vực Vườn quốc gia Yok Đôn.</p>
  </div>
  <div class="emergency-card">
    <div class="emergency-card-title">🐘 Ban quản lý KDL Buôn Đôn</div>
    <div class="emergency-card-phone"><a href="tel:02623786111">0262 378 6111</a></div>
    <p class="emergency-card-desc">Hỗ trợ du khách trong khu du lịch sinh thái Buôn Đôn.</p>
  </div>
  <div class="emergency-card">
    <div class="emergency-card-title">🌊 Ban quản lý Thác Dray Nur</div>
    <div class="emergency-card-phone"><a href="tel:02623636789">0262 363 6789</a></div>
    <p class="emergency-card-desc">Liên hệ khi cần hỗ trợ tại khu vực Thác Dray Nur — Dray Sáp.</p>
  </div>
  <div class="emergency-card">
    <div class="emergency-card-title">🚗 Taxi Mai Linh Đắk Lắk</div>
    <div class="emergency-card-phone"><a href="tel:02623838383">0262 383 8383</a></div>
    <p class="emergency-card-desc">Gọi taxi khi cần di chuyển khẩn cấp hoặc không có phương tiện.</p>
  </div>
</div>

<!-- ══════════════════════════════════════
     HƯỚNG DẪN AN TOÀN THEO ĐỊA HÌNH
══════════════════════════════════════ -->
<h2 class="section-title">🗺️ Hướng Dẫn An Toàn Theo Địa Hình</h2>
<p class="section-sub">Mỗi loại địa hình ở Đắk Lắk có các rủi ro riêng. Hãy đọc kỹ trước khi khởi hành.</p>

<div class="hazard-section">
  <h3>🌊 Thác Nước (Dray Nur, Dray Sáp)</h3>
  <ul class="hazard-list">
    <li><strong>Nhận biết lũ quét:</strong> Nước suối chuyển màu đục đột ngột, có tiếng ù ù hoặc rền từ thượng nguồn, mực nước dâng nhanh bất thường — đây là dấu hiệu lũ quét sắp về. Lập tức di chuyển lên vùng đất cao.</li>
    <li><strong>Đường đá trơn trượt:</strong> Mùa mưa các bậc đá xuống chân thác cực kỳ trơn. Luôn mang giày leo núi có đế bám cao su, KHÔNG đi dép lê hoặc giày vải. Bám chặt lan can và đi chậm từng bước.</li>
    <li><strong>Tắm thác an toàn:</strong> Chỉ tắm ở khu vực được phép, có cứu hộ. Không bao giờ bơi vào vùng nước xoáy dưới chân thác — lực hút cực mạnh có thể kéo người xuống đáy.</li>
    <li><strong>Bảo vệ thiết bị:</strong> Bọc điện thoại, máy ảnh trong túi chống nước. Bụi nước ở gần thác rất mạnh, dễ gây hỏng thiết bị điện tử.</li>
  </ul>
</div>

<div class="hazard-section">
  <h3>🌳 Rừng & Vườn Quốc Gia (Yok Đôn)</h3>
  <ul class="hazard-list">
    <li><strong>Luôn có hướng dẫn viên:</strong> Rừng Yok Đôn rộng hơn 115.000 ha, dễ lạc đường. Tuyệt đối KHÔNG tự ý đi sâu vào rừng khi không có người dẫn đường bản địa.</li>
    <li><strong>Phòng tránh cháy rừng:</strong> Mùa khô (tháng 11–4), rừng khộp cực kỳ khô nóng. Không đốt lửa, không vứt tàn thuốc, không mang các vật dễ cháy. Nếu phát hiện khói hoặc lửa, gọi ngay 114.</li>
    <li><strong>Côn trùng & rắn:</strong> Mặc quần áo dài tay, xịt thuốc chống côn trùng. Khi đi bộ trong rừng, dùng gậy gõ nhẹ vào bụi cây phía trước để xua rắn. Nếu bị rắn cắn: giữ bình tĩnh, bất động chi bị cắn, và gọi cấp cứu 115 ngay.</li>
    <li><strong>Nước uống:</strong> Mang tối thiểu 2 lít nước/người. KHÔNG uống nước suối trong rừng khi chưa qua xử lý — nguy cơ nhiễm ký sinh trùng.</li>
  </ul>
</div>

<div class="hazard-section">
  <h3>🏞️ Hồ & Sông (Hồ Lắk, Hồ Ea Kao)</h3>
  <ul class="hazard-list">
    <li><strong>Mặc áo phao:</strong> Bắt buộc mặc áo phao khi đi thuyền độc mộc hoặc chèo kayak. Đặc biệt quan trọng với trẻ em và người không biết bơi.</li>
    <li><strong>Không bơi một mình:</strong> Luôn bơi theo nhóm hoặc có người giám sát. Nhiều hồ tự nhiên có đáy sâu bất ngờ và dòng chảy ngầm mà mắt thường không nhìn thấy.</li>
    <li><strong>Bờ hồ dốc trơn:</strong> Đặc biệt sau mưa, đất bờ hồ mềm và trơn. Không đứng sát mép bờ dốc. Khi cắm trại bên hồ, chọn vị trí cao hơn mực nước ít nhất 3 mét (nước có thể dâng nhanh qua đêm vào mùa mưa).</li>
  </ul>
</div>

<div class="hazard-section">
  <h3>🏡 Buôn Làng & Văn Hoá Bản Địa</h3>
  <ul class="hazard-list">
    <li><strong>Xin phép khi vào buôn:</strong> Nên hỏi ý kiến người dân hoặc già làng trước khi đi sâu vào khu vực sinh hoạt. Tôn trọng không gian riêng tư của cộng đồng.</li>
    <li><strong>Không sờ vào vật thờ cúng:</strong> Người Ê Đê và M'nông có nhiều vật dụng thờ cúng tâm linh trong nhà dài. Không tự ý chạm, chụp ảnh vật thờ khi chưa được phép.</li>
    <li><strong>Uống rượu cần:</strong> Nếu được mời tham gia uống rượu cần, hãy uống một ít theo lễ nghi. Từ chối lịch sự nếu không uống được cồn — người bản địa rất hiểu và tôn trọng.</li>
  </ul>
</div>

<!-- ══════════════════════════════════════
     CHUẨN BỊ THEO MÙA
══════════════════════════════════════ -->
<h2 class="section-title">📅 Mẹo Chuẩn Bị Theo Mùa</h2>

<div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap:20px; margin:20px 0;">
  <div class="hazard-section" style="border-top:4px solid #3b82f6;">
    <h3>🌧️ Mùa Mưa (Tháng 5 – 10)</h3>
    <ul class="hazard-list">
      <li>Mang áo mưa cánh dơi (gọn, nhẹ, che được ba lô)</li>
      <li>Giày leo núi chống trượt — KHÔNG mang giày vải</li>
      <li>Túi zip-lock bọc điện thoại, giấy tờ và tiền</li>
      <li>Kiểm tra thời tiết trước mỗi chặng di chuyển</li>
      <li>Mang theo quần áo thay — ướt là chuyện bình thường!</li>
    </ul>
  </div>
  <div class="hazard-section" style="border-top:4px solid #f59e0b;">
    <h3>☀️ Mùa Khô (Tháng 11 – 4)</h3>
    <ul class="hazard-list">
      <li>Kem chống nắng SPF50+ và mũ rộng vành</li>
      <li>Mang tối thiểu 2 lít nước/người/buổi tham quan</li>
      <li>Quần áo dài tay thoáng mát — chống nắng + côn trùng</li>
      <li>Bình xịt đuổi côn trùng (muỗi rừng rất nhiều buổi chiều tối)</li>
      <li>Kính râm UV400 bảo vệ mắt</li>
    </ul>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
