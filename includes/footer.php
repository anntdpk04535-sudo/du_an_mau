</main>

<!-- ── 7. FOOTER CHUYÊN NGHIỆP (ROYAL EMERALD) ── -->
<footer class="site-footer">
  <div class="container footer-inner">
    <!-- Cột 1: Giới thiệu thương hiệu -->
    <div class="footer-col">
      <a href="<?= url('/public/index.php') ?>" class="logo" style="color:#ffffff !important; margin-bottom: 16px;">
        🌿 Đắk Lắk <span style="color:#fef08a !important; font-family:'Playfair Display', serif; font-style:italic;">Travel AI</span>
      </a>
      <p style="color: #a7f3d0; font-size: 14px; line-height: 1.6; margin-bottom: 20px;">
        Nền tảng du lịch thông minh Tây Nguyên hàng đầu Việt Nam. Tích hợp AI tự động thiết kế lịch trình và hỗ trợ du khách 24/7.
      </p>
      <div style="display: flex; gap: 12px; font-size: 18px;">
        <a href="#" style="background: rgba(255,255,255,0.1); width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #ffffff;">🌐</a>
        <a href="#" style="background: rgba(255,255,255,0.1); width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #ffffff;">📘</a>
        <a href="#" style="background: rgba(255,255,255,0.1); width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #ffffff;">▶️</a>
      </div>
    </div>

    <!-- Cột 2: Khám phá -->
    <div class="footer-col">
      <h3>Khám Phá Đắk Lắk</h3>
      <a href="<?= url('/diem-den') ?>">🏞️ Danh Thắng Nổi Bật</a>
      <a href="<?= url('/public/map.php') ?>">🗺️ Bản Đồ Du Lịch</a>
      <a href="<?= url('/cam-nang') ?>">📖 Cẩm Nang Du Lịch</a>
      <a href="<?= url('/diem-den?cat=1') ?>">☕ Cà Phê & Ẩm Thực</a>
    </div>

    <!-- Cột 3: Công nghệ AI -->
    <div class="footer-col">
      <h3>Công Nghệ AI</h3>
      <a href="<?= url('/public/itinerary.php') ?>">✨ Lập Kế Hoạch Lịch Trình</a>
      <a href="<?= url('/public/chatbot.php') ?>">💬 Trợ Lý Chatbot AI</a>
      <a href="<?= url('/public/safety.php') ?>">🛡️ An Toàn & Hỗ Trợ</a>
      <a href="<?= url('/public/reviews.php') ?>">⭐ Đánh Giá Du Khách</a>
    </div>

    <!-- Cột 4: Liên hệ -->
    <div class="footer-col">
      <h3>Kết Nối Với Chúng Tôi</h3>
      <p style="color: #d1fae5; font-size: 14px; margin-bottom: 10px;">📍 Buôn Ma Thuột, Đắk Lắk, Việt Nam</p>
      <p style="color: #d1fae5; font-size: 14px; margin-bottom: 10px;">📧 hotro@traveldaklak.vn</p>
      <p style="color: #d1fae5; font-size: 14px; margin-bottom: 10px;">📞 1900 6868 (8:00 - 20:00)</p>
    </div>
  </div>

  <div class="footer-bottom">
    <div class="container">
      <p>© <?= date('Y') ?> Biệt đội Báo Đốm</p>
    </div>
  </div>
</footer>

<script>
document.addEventListener('click', function(e) {
    var btn = e.target.closest('.lang-toggle');
    if (!btn) return;
    
    var langUrl = btn.getAttribute('data-lang-url');
    if (!langUrl) return;
    
    langUrl += '&return_to=' + encodeURIComponent(location.href);
    btn.disabled = true;
    document.dispatchEvent(new Event('beforeLangSwitch'));
    
    fetch(langUrl).then(function(res) {
        return res.text();
    }).then(function(html) {
        var parser = new DOMParser();
        var doc = parser.parseFromString(html, 'text/html');
        document.title = doc.title;
        
        ['header.site-header', 'main.main-content', 'footer.site-footer'].forEach(function(sel) {
            var newEl = doc.querySelector(sel);
            var oldEl = document.querySelector(sel);
            if (newEl && oldEl) {
                oldEl.innerHTML = newEl.innerHTML;
            }
        });
        
        var mainEl = document.querySelector('main.main-content');
        if (mainEl) {
            var scripts = mainEl.querySelectorAll('script');
            for (var i = 0; i < scripts.length; i++) {
                var oldScript = scripts[i];
                var newScript = document.createElement('script');
                for (var j = 0; j < oldScript.attributes.length; j++) {
                    newScript.setAttribute(oldScript.attributes[j].name, oldScript.attributes[j].value);
                }
                newScript.textContent = oldScript.textContent;
                oldScript.parentNode.replaceChild(newScript, oldScript);
            }
        }
        
        document.dispatchEvent(new Event('afterLangSwitch'));
    }).catch(function(err) {
        console.error('Lang switch error:', err);
    });
});
</script>

</body>
</html>
