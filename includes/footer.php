</main>
<footer class="site-footer">
  <div class="container">
    <p>© <?= date('Y') ?> <?= __('footer_text') ?></p>
  </div>
</footer>

<script>
document.addEventListener('click', function(e) {
    var btn = e.target.closest('.lang-toggle');
    if (!btn) return;
    
    var langUrl = btn.getAttribute('data-lang-url');
    if (!langUrl) return;
    
    btn.disabled = true;
    
    // Bước 1: Gọi change_lang.php để cập nhật session
    // Bước 2: Fetch lại trang hiện tại với ngôn ngữ mới
    // fetch() tự động follow redirect 302 về trang hiện tại
    fetch(langUrl).then(function(res) {
        return res.text();
    }).then(function(html) {
        var parser = new DOMParser();
        var doc = parser.parseFromString(html, 'text/html');
        
        document.title = doc.title;
        
        // Thay nội dung từng vùng
        ['header.site-header', 'main.main-content', 'footer.site-footer'].forEach(function(sel) {
            var newEl = doc.querySelector(sel);
            var oldEl = document.querySelector(sel);
            if (newEl && oldEl) {
                oldEl.innerHTML = newEl.innerHTML;
            }
        });
        
        // Chạy lại các inline script trong main-content (ví dụ bản đồ, biểu đồ)
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
    }).catch(function(err) {
        console.error('Lang switch error:', err);
    });
});
</script>

</body>
</html>
