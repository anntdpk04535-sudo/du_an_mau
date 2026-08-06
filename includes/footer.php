</main>
<footer class="site-footer">
  <div class="container">
    <div class="footer-grid">
      <div><p class="footer-kicker">Đắk Lắk Travel AI</p><p><?= __('footer_text') ?></p></div>
      <nav aria-label="Liên kết nhanh" class="footer-links"><a href="<?= url('/diem-den') ?>">Điểm đến</a><a href="<?= url('/am-thuc') ?>">Ẩm thực</a><a href="<?= url('/luu-tru') ?>">Lưu trú</a><a href="<?= url('/public/chatbot.php') ?>">Trợ lý tra cứu</a></nav>
    </div>
    <p class="footer-copy">© <?= date('Y') ?> · Dữ liệu du lịch Đắk Lắk có nguồn tham chiếu</p>
  </div>
</footer>

<!-- Floating Voi Bản Đôn Chatbot Widget -->
<div id="voi-chatbot-widget" class="voi-chatbot-widget">
  <button id="voi-chatbot-toggle" class="voi-chatbot-toggle" type="button" aria-label="Trợ lý Voi Bản Đôn">
    <div class="voi-avatar-badge">
      <img src="<?= url('/assets/images/voi_ban_don_avatar.png') ?>" alt="Voi Bản Đôn Avatar" width="56" height="56">
      <span class="voi-online-dot"></span>
    </div>
    <div class="voi-toggle-tooltip">
      <strong>Voi Bản Đôn</strong>
      <small>Trợ lý du lịch AI</small>
    </div>
  </button>

  <!-- Chat Drawer Popup -->
  <div id="voi-chat-drawer" class="voi-chat-drawer" style="display: none;">
    <div class="voi-drawer-header">
      <div class="voi-header-user-info">
        <img src="<?= url('/assets/images/voi_ban_don_avatar.png') ?>" alt="Voi Bản Đôn" width="42" height="42" class="header-avatar">
        <div>
          <h3>Voi Bản Đôn <span class="badge-guide">Ama Guide</span></h3>
          <p class="status-text">🟢 Sẵn sàng tư vấn Đắk Lắk 24/7</p>
        </div>
      </div>
      <button id="voi-drawer-close" type="button" class="drawer-close-btn" aria-label="Đóng chat">✕</button>
    </div>

    <div class="voi-drawer-body" id="voi-chat-messages">
      <div class="msg-row bot-row">
        <div class="msg-avatar">
          <img src="<?= url('/assets/images/voi_ban_don_avatar.png') ?>" alt="Voi Bản Đôn" width="30" height="30">
        </div>
        <div class="msg bot">
          <strong>Xin chào! Mình là Voi Bản Đôn</strong> 🐘<br>
          Bạn cần tìm quán cà phê, điểm cắm trại hay homestay nào ở Đắk Lắk?
        </div>
      </div>
    </div>

    <div class="voi-quick-replies">
      <span class="quick-title">☕ Gợi ý nhanh:</span>
      <div class="quick-pills-row">
        <button type="button" class="coffee-bean-pill voi-quick-btn" data-msg="Gợi ý quán cà phê đẹp ở Buôn Ma Thuột">Cà phê BMT</button>
        <button type="button" class="coffee-bean-pill voi-quick-btn" data-msg="Kinh nghiệm du lịch Buôn Đôn">Buôn Đôn</button>
        <button type="button" class="coffee-bean-pill voi-quick-btn" data-msg="Điểm cắm trại đẹp tại Hồ Lắk">Cắm trại Hồ Lắk</button>
        <button type="button" class="coffee-bean-pill voi-quick-btn" data-msg="Bảo tàng Thế giới Cà phê có gì hot?">Bảo tàng Cà phê</button>
      </div>
    </div>

    <form id="voi-chat-form" class="voi-drawer-input-row">
      <input type="text" id="voi-chat-input" placeholder="Nhập thắc mắc về Đắk Lắk..." autocomplete="off">
      <button type="submit" class="voi-send-btn">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
      </button>
    </form>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var toggleBtn = document.getElementById('voi-chatbot-toggle');
    var drawer = document.getElementById('voi-chat-drawer');
    var closeBtn = document.getElementById('voi-drawer-close');
    var chatForm = document.getElementById('voi-chat-form');
    var chatInput = document.getElementById('voi-chat-input');
    var chatMessages = document.getElementById('voi-chat-messages');

    if (toggleBtn && drawer) {
        toggleBtn.addEventListener('click', function() {
            var isHidden = drawer.style.display === 'none' || !drawer.style.display;
            drawer.style.display = isHidden ? 'flex' : 'none';
            if (isHidden) chatInput.focus();
        });
        closeBtn.addEventListener('click', function() {
            drawer.style.display = 'none';
        });
    }

    // Quick replies click handler
    document.addEventListener('click', function(e) {
        var quickBtn = e.target.closest('.voi-quick-btn');
        if (quickBtn) {
            var msg = quickBtn.getAttribute('data-msg');
            if (msg) {
                if (drawer && drawer.style.display === 'none') {
                    drawer.style.display = 'flex';
                }
                sendVoiMessage(msg);
            }
        }
    });

    if (chatForm) {
        chatForm.addEventListener('submit', function(e) {
            e.preventDefault();
            var text = chatInput.value.trim();
            if (text) {
                sendVoiMessage(text);
                chatInput.value = '';
            }
        });
    }

    function sendVoiMessage(text) {
        // Append user message
        var userRow = document.createElement('div');
        userRow.className = 'msg-row user-row';
        userRow.innerHTML = '<div class="msg user">' + escapeHtml(text) + '</div>';
        chatMessages.appendChild(userRow);
        chatMessages.scrollTop = chatMessages.scrollHeight;

        // Append loading
        var loadingRow = document.createElement('div');
        loadingRow.className = 'msg-row bot-row voi-loading-row';
        loadingRow.innerHTML = '<div class="msg-avatar"><img src="<?= url('/assets/images/voi_ban_don_avatar.png') ?>" width="30" height="30"></div><div class="msg bot loading-dots">🐘 Voi Bản Đôn đang suy nghĩ...</div>';
        chatMessages.appendChild(loadingRow);
        chatMessages.scrollTop = chatMessages.scrollHeight;

        fetch('<?= url('/api/chat.php') ?>', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({message: text})
        })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            var loadEl = chatMessages.querySelector('.voi-loading-row');
            if (loadEl) loadEl.remove();

            var reply = (data && data.reply) ? data.reply : 'Rất tiếc, Voi Bản Đôn chưa lấy được thông tin. Bạn thử lại nhé!';
            var botRow = document.createElement('div');
            botRow.className = 'msg-row bot-row';
            botRow.innerHTML = '<div class="msg-avatar"><img src="<?= url('/assets/images/voi_ban_don_avatar.png') ?>" width="30" height="30" alt="Voi Bản Đôn"></div>';
            var botBubble = document.createElement('div');
            botBubble.className = 'msg bot';
            renderBotMessage(botBubble, reply);
            botRow.appendChild(botBubble);
            chatMessages.appendChild(botRow);
            chatMessages.scrollTop = chatMessages.scrollHeight;
        })
        .catch(function() {
            var loadEl = chatMessages.querySelector('.voi-loading-row');
            if (loadEl) loadEl.remove();

            var errRow = document.createElement('div');
            errRow.className = 'msg-row bot-row';
            errRow.innerHTML = '<div class="msg-avatar"><img src="<?= url('/assets/images/voi_ban_don_avatar.png') ?>" width="30" height="30"></div><div class="msg bot">⚠️ Có lỗi kết nối. Hãy kiểm tra kết nối mạng và thử lại nhé!</div>';
            chatMessages.appendChild(errRow);
            chatMessages.scrollTop = chatMessages.scrollHeight;
        });
    }

    function escapeHtml(str) {
        return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }
    function renderBotMessage(container, rawText) {
        var fragment = document.createDocumentFragment();
        var lines = String(rawText || '').replace(/\r\n?/g, '\n').split('\n');
        var list = null;
        var orderedList = null;

        function closeLists() { list = null; orderedList = null; }
        function addParagraph(text) {
            var p = document.createElement('p');
            p.className = 'assistant-paragraph';
            appendInlineText(p, text);
            fragment.appendChild(p);
        }

        lines.forEach(function(line) {
            var value = line.trim();
            if (!value) { closeLists(); return; }

            var heading = value.match(/^#{2,3}\s*(?:\*\*)?(.+?)(?:\*\*)?$/);
            if (heading) {
                closeLists();
                var h = document.createElement('h4');
                h.className = 'assistant-heading';
                appendInlineText(h, heading[1]);
                fragment.appendChild(h);
                return;
            }

            var bullet = value.match(/^(?:[-*•])\s+(.+)$/);
            if (bullet) {
                if (!list) { list = document.createElement('ul'); list.className = 'assistant-list'; fragment.appendChild(list); }
                var li = document.createElement('li');
                appendInlineText(li, bullet[1]);
                list.appendChild(li);
                return;
            }

            var numbered = value.match(/^\d+[.)]\s+(.+)$/);
            if (numbered) {
                if (!orderedList) { orderedList = document.createElement('ol'); orderedList.className = 'assistant-list assistant-list-ordered'; fragment.appendChild(orderedList); }
                var numberedItem = document.createElement('li');
                appendInlineText(numberedItem, numbered[1]);
                orderedList.appendChild(numberedItem);
                return;
            }

            closeLists();
            addParagraph(value);
        });
        container.appendChild(fragment);
    }

    function appendInlineText(parent, text) {
        var parts = String(text).split(/(\*\*[^*]+\*\*)/g);
        parts.forEach(function(part) {
            if (!part) return;
            var strongMatch = part.match(/^\*\*(.+)\*\*$/);
            if (strongMatch) {
                var strong = document.createElement('strong');
                strong.textContent = strongMatch[1];
                parent.appendChild(strong);
            } else {
                parent.appendChild(document.createTextNode(part.replace(/\*([^*]+)\*/g, '$1')));
            }
        });
    }
});

// Mobile Nav Drawer Toggle Handler
document.addEventListener('DOMContentLoaded', function() {
    var menuBtn = document.getElementById('mobile-menu-btn');
    var drawer = document.getElementById('mobile-nav-drawer');
    var overlay = document.getElementById('mobile-nav-overlay');
    var closeBtn = document.getElementById('mobile-drawer-close');

    function openMobileMenu() {
        if (drawer && overlay) {
            drawer.classList.add('open');
            overlay.classList.add('open');
            document.body.classList.add('mobile-menu-open');
        }
    }

    function closeMobileMenu() {
        if (drawer && overlay) {
            drawer.classList.remove('open');
            overlay.classList.remove('open');
            document.body.classList.remove('mobile-menu-open');
        }
    }

    if (menuBtn) menuBtn.addEventListener('click', openMobileMenu);
    if (closeBtn) closeBtn.addEventListener('click', closeMobileMenu);
    if (overlay) overlay.addEventListener('click', closeMobileMenu);

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeMobileMenu();
    });
});

// Global Image 404 Error Fallback Handler
window.addEventListener('error', function(e) {
    if (e.target && e.target.tagName === 'IMG') {
        var img = e.target;
        var placeholderUrl = '<?= url('/assets/images/placeholder.svg') ?>';
        if (img.src !== placeholderUrl && !img.getAttribute('data-fallback-handled')) {
            img.setAttribute('data-fallback-handled', 'true');
            img.src = placeholderUrl;
            img.onerror = null;
        }
    }
}, true);

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
        document.dispatchEvent(new Event('afterLangSwitch'));
    }).catch(function(err) {
        console.error('Lang switch error:', err);
    });
});
</script>

</body>
</html>
