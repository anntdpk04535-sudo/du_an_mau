<?php
require_once __DIR__ . '/../includes/functions.php';
$pageTitle = __('page_title_chatbot');

if (empty($_SESSION['chat_session_id'])) {
  $_SESSION['chat_session_id'] = bin2hex(random_bytes(16));
}
$askPrefill = $_GET['ask'] ?? '';

include __DIR__ . '/../includes/header.php';
?>

<section class="chat-hero">
  <div class="chat-hero-text">
    <h1>Hỏi Đáp Cùng Voi Bản Đôn 🐘</h1>
    <p>Trợ lý du lịch AI am hiểu từng buôn làng, thác nước, quán cà phê và điểm lưu trú tại Đắk Lắk.</p>
    <a href="#chat-box" class="btn btn-jungle">Bắt đầu trò chuyện</a>
  </div>
  <div class="chat-hero-art">
    <img src="<?= url('/assets/images/voi_ban_don_avatar.png') ?>" alt="Voi Bản Đôn AI" width="180" height="180" style="border-radius: 50%; border: 4px solid rgba(255,255,255,0.3); box-shadow: 0 10px 30px rgba(0,0,0,0.2);">
  </div>
</section>

<div id="chat-box" class="chat-research-shell">
  <aside class="chat-sidebar" aria-label="Gợi ý tra cứu">
    <span class="eyebrow">Ama Guide - Voi Bản Đôn</span>
    <h2>Hỏi như đang trò chuyện với người bản địa.</h2>
    <p><?= __('chat_desc') ?></p>
    <button type="button" class="focus-mode-btn" id="focus-mode-btn">Tập trung khi tra cứu</button>
    <div class="chat-sidebar-links">
      <a href="<?= url('/diem-den?region=west') ?>">Phía Tây · Rừng & Thác</a>
      <a href="<?= url('/diem-den?region=east') ?>">Phía Đông · Hồ & Đồi</a>
      <a href="<?= url('/am-thuc') ?>">Ẩm thực & Cà phê BMT</a>
      <a href="<?= url('/luu-tru') ?>">Homestay & Nơi ở</a>
    </div>
  </aside>
  <div class="chat-main-panel">
  <div class="chat-window" id="chat-window">
    <div class="msg-row bot-row">
      <div class="msg-avatar">
        <img src="<?= url('/assets/images/voi_ban_don_avatar.png') ?>" alt="Voi Bản Đôn" width="34" height="34" style="border-radius:50%;">
      </div>
      <div class="msg bot" id="chat-greeting-bubble">
        <strong>Xin chào! Mình là Voi Bản Đôn 🐘</strong><br>
        Bạn cần tìm quán cà phê, điểm cắm trại hay homestay nào ở Đắk Lắk hôm nay?
      </div>
    </div>
  </div>

  <div class="chat-suggestions" id="chat-suggestions">
    <button class="coffee-bean-pill" onclick="sendSuggestion('Gợi ý top quán cà phê đẹp Buôn Ma Thuột')">☕ Cà phê BMT</button>
    <button class="coffee-bean-pill" onclick="sendSuggestion('Kinh nghiệm du lịch Thác Dray Nur')">💧 Thác Dray Nur</button>
    <button class="coffee-bean-pill" onclick="sendSuggestion('Trải nghiệm chèo sub cắm trại Hồ Lắk')">🏕️ Hồ Lắk</button>
    <button class="coffee-bean-pill" onclick="sendSuggestion('Ăn gì ngon ở Đắk Lắk?')">🍜 Ẩm thực địa phương</button>
    <button class="coffee-bean-pill" onclick="sendSuggestion('Buôn Đôn có hoạt động gì hấp dẫn?')">🐘 Buôn Đôn</button>
    <button class="coffee-bean-pill" onclick="sendSuggestion('Tìm homestay gần trung tâm giá tốt')">🏡 Homestay đẹp</button>
  </div>

  <form id="chat-form" class="chat-input-row">
    <input type="text" id="chat-input" placeholder="<?= __('type_msg') ?>" autocomplete="off"
      value="<?= e($askPrefill) ?>">
    <button type="submit" class="btn"><?= __('send_btn') ?></button>
  </form>
  </div>
</div>

<style>
  .chat-suggestions {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin: 10px 0;
    padding: 0 16px;
  }
  .sugg-btn {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    color: #334155;
    padding: 8px 14px;
    border-radius: 20px;
    font-size: 13.5px;
    cursor: pointer;
    transition: all 0.2s;
  }
  .sugg-btn:hover {
    background: #f1f5f9;
    border-color: #cbd5e1;
    transform: translateY(-1px);
  }

  .chat-images {
    display: flex;
    gap: 8px;
    margin-top: 10px;
    flex-wrap: wrap;
  }

  .chat-images img {
    width: 120px;
    height: 85px;
    object-fit: cover;
    border-radius: 10px;
    cursor: pointer;
    transition: transform 0.2s;
    border: 2px solid #e0e0e0;
  }

  .chat-images img:hover {
    transform: scale(1.05);
  }

  #img-lightbox {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.85);
    z-index: 9999;
    align-items: center;
    justify-content: center;
  }

  #img-lightbox.open {
    display: flex;
  }

  #img-lightbox img {
    max-width: 90vw;
    max-height: 85vh;
    border-radius: 12px;
    box-shadow: 0 8px 40px rgba(0, 0, 0, 0.6);
  }

  #img-lightbox span {
    position: absolute;
    top: 20px;
    right: 30px;
    color: white;
    font-size: 2rem;
    cursor: pointer;
  }
</style>

<div id="img-lightbox">
  <span onclick="closeLightbox()">✕</span>
  <img id="lightbox-img" src="" alt="">
</div>

<script>
if (!window.chatbotEventsAttached) {
    window.chatbotEventsAttached = true;
    const focusModeButton = document.getElementById('focus-mode-btn');
    if (focusModeButton) focusModeButton.addEventListener('click', function() {
        document.body.classList.toggle('chat-focus-mode');
        focusModeButton.textContent = document.body.classList.contains('chat-focus-mode') ? 'Thoát chế độ tập trung' : 'Tập trung khi tra cứu';
    });
    let savedChatHtml = '';
    let savedInputValue = '';
    document.addEventListener('beforeLangSwitch', function() {
        const cw = document.getElementById('chat-window');
        const ci = document.getElementById('chat-input');
        if (cw) savedChatHtml = cw.innerHTML;
        if (ci) savedInputValue = ci.value;
    });
    document.addEventListener('afterLangSwitch', function() {
        const cw = document.getElementById('chat-window');
        const ci = document.getElementById('chat-input');
        
        // Lấy câu chào mới đã được dịch từ DOM mới trước khi ghi đè
        let newGreeting = '';
        const newGreetingBubble = document.getElementById('chat-greeting-bubble');
        if (newGreetingBubble) {
            newGreeting = newGreetingBubble.innerHTML;
        }
        
        if (cw && savedChatHtml) {
            cw.innerHTML = savedChatHtml;
            
            // Cập nhật lại câu chào với ngôn ngữ mới
            const restoredGreetingBubble = document.getElementById('chat-greeting-bubble');
            if (restoredGreetingBubble && newGreeting) {
                restoredGreetingBubble.innerHTML = newGreeting;
            }
            cw.scrollTop = cw.scrollHeight;

            // Tự động dịch phần lịch sử chat
            if (cw.querySelectorAll('.msg-row').length > 1) {
                const translateDiv = document.createElement('div');
                translateDiv.className = 'msg-row bot-row';
                translateDiv.innerHTML = '<div class="msg-avatar">🤖</div><div class="msg bot loading-dots">Translating history...</div>';
                cw.appendChild(translateDiv);
                cw.scrollTop = cw.scrollHeight;

                fetch('<?= url('/api/translate_html.php') ?>', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({html: savedChatHtml})
                }).then(r => r.json()).then(data => {
                    if (data.success && data.html) {
                        cw.innerHTML = data.html;
                        // Phục hồi lại câu chào để đảm bảo khớp 100%
                        const updatedGreeting = document.getElementById('chat-greeting-bubble');
                        if (updatedGreeting && newGreeting) {
                            updatedGreeting.innerHTML = newGreeting;
                        }
                        cw.scrollTop = cw.scrollHeight;
                    } else {
                        cw.removeChild(translateDiv);
                    }
                }).catch(() => {
                    if(translateDiv.parentNode) cw.removeChild(translateDiv);
                });
            }
        }
        if (ci && savedInputValue) {
            ci.value = savedInputValue;
        }
    });
}

(function() {
  const chatWindow = document.getElementById('chat-window');
  const chatForm = document.getElementById('chat-form');
  const chatInput = document.getElementById('chat-input');

  window.sendSuggestion = function(text) {
    sendMessage(text);
  };

  window.openLightbox = function(src) {
    document.getElementById('lightbox-img').src = src;
    document.getElementById('img-lightbox').classList.add('open');
  };
  window.closeLightbox = function() {
    document.getElementById('img-lightbox').classList.remove('open');
  };
  document.getElementById('img-lightbox').addEventListener('click', function (e) {
    if (e.target === this) closeLightbox();
  });

  function addMessage(text, role, images = []) {
    const row = document.createElement('div');
    row.className = 'msg-row ' + (role === 'user' ? 'user-row' : 'bot-row');

    const avatar = document.createElement('div');
    avatar.className = 'msg-avatar';
    avatar.textContent = role === 'user' ? '🧑' : '🤖';

    const wrap = document.createElement('div');

    const bubble = document.createElement('div');
    bubble.className = 'msg ' + role;
    if (role === 'bot') renderAssistantText(bubble, text);
    else bubble.textContent = text;
    wrap.appendChild(bubble);

    if (images && images.length > 0) {
      const imgRow = document.createElement('div');
      imgRow.className = 'chat-images';
      images.forEach(img => {
        const el = document.createElement('img');
        el.src = img.url;
        el.alt = img.title;
        el.title = img.title;
        el.onerror = function () { this.style.display = 'none'; };
        el.onclick = () => openLightbox(img.url);
        imgRow.appendChild(el);
      });
      wrap.appendChild(imgRow);
    }

    if (role === 'user') {
      row.appendChild(wrap);
      row.appendChild(avatar);
    } else {
      row.appendChild(avatar);
      row.appendChild(wrap);
    }

    chatWindow.appendChild(row);
    chatWindow.scrollTop = chatWindow.scrollHeight;
    return bubble;
  }

  function renderAssistantText(container, rawText) {
    container.replaceChildren();
    const normalized = String(rawText || '')
      .replace(/\r/g, '')
      .replace(/\s*(#{2,4}\s*Ngày\s*\d+\s*:)/gi, '\n$1')
      .replace(/\s+(Sáng|Trưa|Chiều|Tối|Buổi sáng|Buổi trưa|Buổi chiều|Buổi tối)\s*:/g, '\n$1:');
    const lines = normalized.split(/\n+/).map(line => line.trim()).filter(Boolean);
    const fragment = document.createDocumentFragment();
    let list = null;
    const closeList = () => { if (list) { fragment.appendChild(list); list = null; } };
    lines.forEach(line => {
      const heading = line.match(/^#{2,4}\s*(.+)$/);
      const bullet = line.match(/^[-•]\s+(.+)$/);
      if (heading) {
        closeList();
        const el = document.createElement('h3');
        el.className = 'assistant-heading';
        el.textContent = heading[1];
        fragment.appendChild(el);
        return;
      }
      if (bullet) {
        if (!list) { list = document.createElement('ul'); list.className = 'assistant-list'; }
        const item = document.createElement('li');
        appendInlineText(item, bullet[1]);
        list.appendChild(item);
        return;
      }
      closeList();
      const paragraph = document.createElement('p');
      paragraph.className = 'assistant-paragraph';
      appendInlineText(paragraph, line);
      fragment.appendChild(paragraph);
    });
    closeList();
    container.appendChild(fragment);
  }

  function appendInlineText(parent, text) {
    const parts = String(text).split(/(\*\*[^*]+\*\*)/g);
    parts.forEach(part => {
      if (!part) return;
      if (/^\*\*[^*]+\*\*$/.test(part)) {
        const strong = document.createElement('strong');
        strong.textContent = part.slice(2, -2);
        parent.appendChild(strong);
      } else {
        parent.appendChild(document.createTextNode(part.replace(/^\*|\*$/g, '')));
      }
    });
  }

  async function sendMessage(text) {
    if (!text.trim()) return;
    addMessage(text, 'user');
    chatInput.value = '';
    const loadingDiv = addMessage('<?= __('chat_answering') ?>', 'bot');
    loadingDiv.classList.add('loading-dots');

    try {
      const res = await fetch('<?= url('/api/chat.php') ?>', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ message: text })
      });
      const data = await res.json();
      loadingDiv.classList.remove('loading-dots');
      let replyText = data.reply || '<?= __('chat_error') ?>';
      renderAssistantText(loadingDiv, replyText);

      if (data.images && data.images.length > 0) {
        const imgRow = document.createElement('div');
        imgRow.className = 'chat-images';
        data.images.forEach(img => {
          const el = document.createElement('img');
          el.src = img.url;
          el.alt = img.title;
          el.title = img.title;
          el.onerror = function () { this.style.display = 'none'; };
          el.onclick = () => openLightbox(img.url);
          imgRow.appendChild(el);
        });
        loadingDiv.parentNode.appendChild(imgRow);
        chatWindow.scrollTop = chatWindow.scrollHeight;
      }
      if (Array.isArray(data.results) && data.results.length) {
        const cards = document.createElement('div'); cards.className='chat-result-cards';
        data.results.slice(0,6).forEach(result=>{
          const card=document.createElement('a'); card.href=result.url||'#'; card.target='_blank'; card.rel='noopener'; card.style.cssText='display:block;padding:10px;margin-top:8px;border:1px solid #dbe4df;border-radius:10px;text-decoration:none;color:inherit;background:#fff';
          const title=document.createElement('strong'); title.textContent=result.title||''; card.appendChild(title);
          if(result.address){const addr=document.createElement('div');addr.textContent='📍 '+result.address;addr.style.cssText='font-size:12px;color:#777;margin-top:3px';card.appendChild(addr);}
          cards.appendChild(card);
        });
        loadingDiv.parentNode.appendChild(cards);
      }
    } catch (err) {
      loadingDiv.classList.remove('loading-dots');
      loadingDiv.textContent = '<?= __('chat_connection_error') ?>';
    }
  }

  chatForm.addEventListener('submit', (e) => {
    e.preventDefault();
    sendMessage(chatInput.value);
  });

  window.addEventListener('DOMContentLoaded', () => {
    const prefill = chatInput.value;
    if (prefill) sendMessage(prefill);
  });
})();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
