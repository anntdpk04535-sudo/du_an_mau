<?php
require_once __DIR__ . '/../includes/functions.php';
$pageTitle = __('page_title_chatbot');

if (empty($_SESSION['chat_session_id'])) {
  $_SESSION['chat_session_id'] = bin2hex(random_bytes(16));
}
$askPrefill = $_GET['ask'] ?? '';

include __DIR__ . '/../includes/header.php';
?>

<!-- ── 1. CHATBOT HERO BANNER ── -->
<section class="chat-hero">
  <div class="chat-hero-text">
    <div style="display:inline-flex; align-items:center; gap:8px; background:rgba(254, 240, 138, 0.2); color:#fef08a; padding:6px 16px; border-radius:30px; font-weight:800; font-size:13px; margin-bottom:16px; border:1px solid rgba(254,240,138,0.3);">
      <span>✨ TRÍ TUỆ NHÂN TẠO AI 4.0</span>
    </div>
    <h1 style="font-size: 2.5rem; font-weight: 800; color: #ffffff; margin-bottom: 12px; line-height: 1.2;">
      <?= __('chatbot_title') ?>
    </h1>
    <p style="color: #a7f3d0; font-size: 1.1rem; line-height: 1.6; max-width: 600px; margin-bottom: 24px;">
      <?= __('chatbot_sub') ?>
    </p>
    <a href="#chat-box" class="btn" style="background: linear-gradient(135deg, #f59e0b, #d97706); color: #ffffff !important; padding: 12px 28px; border-radius: 40px; font-weight: 800; border: none; box-shadow: 0 8px 20px rgba(245,158,11,0.35);">
      <?= __('chat_start_btn') ?> ➔
    </a>
  </div>
  
  <div class="chat-hero-art">
    <div style="background: rgba(255,255,255,0.08); backdrop-filter: blur(20px); border: 1px solid rgba(254,240,138,0.25); border-radius: 50%; padding: 20px; box-shadow: 0 20px 50px rgba(0,0,0,0.3);">
      <svg viewBox="0 0 220 220" width="200" height="200">
        <circle cx="110" cy="110" r="105" fill="rgba(255,255,255,0.05)" />
        <circle cx="110" cy="110" r="80" fill="rgba(255,255,255,0.08)" />
        <line x1="110" y1="40" x2="110" y2="58" stroke="#fef08a" stroke-width="4" stroke-linecap="round" />
        <circle cx="110" cy="34" r="7" fill="#f59e0b" />
        <rect x="62" y="58" width="96" height="78" rx="22" fill="#ffffff" />
        <circle cx="90" cy="96" r="9" fill="#022c22" />
        <circle cx="130" cy="96" r="9" fill="#022c22" />
        <path d="M85 114 Q110 130 135 114" stroke="#022c22" stroke-width="5" fill="none" stroke-linecap="round" />
        <rect x="74" y="142" width="72" height="50" rx="16" fill="#ecfdf5" />
        <circle cx="110" cy="167" r="10" fill="#f59e0b" />
        <circle cx="58" cy="160" r="10" fill="#ffffff" />
        <circle cx="162" cy="160" r="10" fill="#ffffff" />
      </svg>
    </div>
  </div>
</section>

<!-- ── 2. GLASSMORPHIC CHAT CONTAINER ── -->
<div id="chat-box" style="margin-top: -30px; position: relative; z-index: 10;">
  
  <!-- Header Bar của Khung Chat -->
  <div style="background: linear-gradient(135deg, #022c22 0%, #047857 100%); padding: 18px 24px; border-radius: 24px 24px 0 0; display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #f59e0b;">
    <div style="display: flex; align-items: center; gap: 12px;">
      <div style="width: 42px; height: 42px; background: linear-gradient(135deg, #f59e0b, #d97706); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 22px; box-shadow: 0 4px 12px rgba(245,158,11,0.4);">
        🤖
      </div>
      <div>
        <h3 style="margin: 0; color: #ffffff; font-size: 16px; font-weight: 800;">Trợ Lý AI Du Lịch Đắk Lắk</h3>
        <p style="margin: 2px 0 0; color: #fef08a; font-size: 12.5px; font-weight: 600; display: flex; align-items: center; gap: 6px;">
          <span style="width: 8px; height: 8px; background: #10b981; border-radius: 50%; display: inline-block;"></span> Trực tuyến 24/7 · Sẵn sàng tư vấn
        </p>
      </div>
    </div>
    
    <button type="button" onclick="restartChat()" style="background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.25); color: #ffffff; padding: 8px 16px; border-radius: 20px; font-size: 13px; font-weight: 700; cursor: pointer; transition: all 0.2s;">
      🔄 Làm mới chat
    </button>
  </div>

  <!-- Cửa sổ tin nhắn Chat -->
  <div class="chat-window" id="chat-window">
    <div class="msg-row bot-row">
      <div class="msg-avatar">🤖</div>
      <div class="msg bot" id="chat-greeting-bubble">
        <?= __('chat_greeting') ?>
      </div>
    </div>
  </div>

  <!-- Gợi ý câu hỏi nhanh (Suggestion Chips) -->
  <div class="chat-suggestions" id="chat-suggestions">
    <button class="sugg-btn" onclick="sendSuggestion('<?= e(__('chat_sugg_1')) ?>')">📍 <?= __('chat_sugg_1') ?></button>
    <button class="sugg-btn" onclick="sendSuggestion('<?= e(__('chat_sugg_2')) ?>')">☕ <?= __('chat_sugg_2') ?></button>
    <button class="sugg-btn" onclick="sendSuggestion('<?= e(__('chat_sugg_3')) ?>')">☀️ <?= __('chat_sugg_3') ?></button>
    <button class="sugg-btn" onclick="sendSuggestion('<?= e(__('chat_sugg_4')) ?>')">🏛️ <?= __('chat_sugg_4') ?></button>
    <button class="sugg-btn" onclick="sendSuggestion('<?= e(__('chat_sugg_5')) ?>')">🛵 <?= __('chat_sugg_5') ?></button>
    <button class="sugg-btn" onclick="sendSuggestion('<?= e(__('chat_sugg_6')) ?>')">🎁 <?= __('chat_sugg_6') ?></button>
    <button class="sugg-btn" onclick="sendSuggestion('<?= e(__('chat_sugg_7')) ?>')">🏕️ <?= __('chat_sugg_7') ?></button>
    <button class="sugg-btn" onclick="sendSuggestion('<?= e(__('chat_sugg_8')) ?>')">🌧️ <?= __('chat_sugg_8') ?></button>
    <button class="sugg-btn" onclick="sendSuggestion('<?= e(__('chat_sugg_9')) ?>')">🏡 <?= __('chat_sugg_9') ?></button>
  </div>

  <!-- Ô Nhập Tin Nhắn -->
  <form id="chat-form" class="chat-input-row">
    <input type="text" id="chat-input" placeholder="<?= __('type_msg') ?>" autocomplete="off" value="<?= e($askPrefill) ?>">
    <button type="submit" class="btn" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: #ffffff !important; border: none; padding: 14px 28px; border-radius: 40px; font-weight: 800; cursor: pointer; box-shadow: 0 8px 20px rgba(245,158,11,0.35);">
      ✨ <?= __('send_btn') ?>
    </button>
  </form>
</div>

<!-- Lightbox xem ảnh trong chat -->
<div id="img-lightbox" style="display:none;">
  <span onclick="closeLightbox()">✕</span>
  <img id="lightbox-img" src="" alt="Xem ảnh phóng to">
</div>

<script>
function formatMarkdown(text) {
    if (!text) return '';
    if (typeof text !== 'string') return text;
    if (text.includes('class="loading-dots"')) return text;

    let html = text
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;");

    // Clean up multiple asterisks artifacts like ***** or ****
    html = html.replace(/\*{3,5}/g, '\n');

    // Parse bold markdown: **text**
    html = html.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');

    // Parse italic markdown: *text*
    html = html.replace(/\*(.*?)\*/g, '<em>$1</em>');

    // Parse Headers: ### Header / ## Header
    html = html.replace(/^### (.*$)/gim, '<strong style="display:block; font-size:1.1em; color:#047857; margin-top:8px;">$1</strong>');
    html = html.replace(/^## (.*$)/gim, '<strong style="display:block; font-size:1.2em; color:#047857; margin-top:10px;">$1</strong>');

    // Convert bullet points: - item or * item
    html = html.replace(/^\s*[-•]\s+(.*$)/gim, '<div style="margin-left:12px; position:relative; padding-left:12px;"><span style="position:absolute; left:0;">•</span> $1</div>');

    // Convert newlines to <br>
    html = html.replace(/\n/g, '<br>');

    // Clean up excessive <br>
    html = html.replace(/(<br>\s*){3,}/g, '<br><br>');

    return html;
}

function restartChat() {
  if (confirm('Bạn có muốn làm mới cuộc trò chuyện?')) {
    const chatWindow = document.getElementById('chat-window');
    chatWindow.innerHTML = `
      <div class="msg-row bot-row">
        <div class="msg-avatar">🤖</div>
        <div class="msg bot" id="chat-greeting-bubble">${formatMarkdown(<?= json_encode(__('chat_greeting')) ?>)}</div>
      </div>
    `;
  }
}

if (!window.chatbotEventsAttached) {
    window.chatbotEventsAttached = true;
    let savedChatHtml = '';
    
    document.addEventListener('beforeLangSwitch', function() {
        const chatWin = document.getElementById('chat-window');
        if (chatWin) {
            savedChatHtml = chatWin.innerHTML;
        }
    });

    document.addEventListener('afterLangSwitch', function() {
        const chatWin = document.getElementById('chat-window');
        if (chatWin && savedChatHtml) {
            chatWin.innerHTML = savedChatHtml;
        }
        
        const chatForm = document.getElementById('chat-form');
        if (chatForm) {
            chatForm.addEventListener('submit', handleChatSubmit);
        }
    });

    function handleChatSubmit(e) {
        e.preventDefault();
        const input = document.getElementById('chat-input');
        const text  = input.value.trim();
        if (!text) return;

        appendMsg('user', text);
        input.value = '';

        const typingId = appendMsg('bot', '<span class="loading-dots">🤖 <?= __('bot_thinking') ?></span>', true);

        fetch('<?= url('/api/chat.php') ?>', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ message: text })
        })
        .then(res => res.json())
        .then(data => {
            const typEl = document.getElementById(typingId);
            if (data.reply) {
                let html = formatMarkdown(data.reply);
                if (data.images && data.images.length > 0) {
                    html += '<div class="chat-images" style="margin-top:12px; display:flex; gap:10px; flex-wrap:wrap;">';
                    data.images.forEach(img => {
                        html += `<img src="${img.url}" alt="${img.name}" onclick="openLightbox('${img.url}')" style="width:100px; height:80px; object-fit:cover; border-radius:10px; cursor:pointer; border:1px solid #e2e8f0;">`;
                    });
                    html += '</div>';
                }
                if (typEl) typEl.innerHTML = html;
            } else {
                if (typEl) typEl.innerHTML = '❌ ' + (data.message || '<?= __('bot_error') ?>');
            }
            scrollToBottom();
        })
        .catch(err => {
            const typEl = document.getElementById(typingId);
            if (typEl) typEl.innerHTML = '❌ <?= __('bot_connect_error') ?>';
            console.error(err);
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        const greetingEl = document.getElementById('chat-greeting-bubble');
        if (greetingEl) {
            greetingEl.innerHTML = formatMarkdown(greetingEl.textContent || greetingEl.innerText);
        }
        const chatForm = document.getElementById('chat-form');
        if (chatForm) {
            chatForm.addEventListener('submit', handleChatSubmit);
        }
    });
}

let msgCount = 0;
function appendMsg(role, content, isRaw = false) {
    msgCount++;
    const id = 'msg-' + msgCount;
    const chatWin = document.getElementById('chat-window');
    const isBot   = role === 'bot';
    
    const row = document.createElement('div');
    row.className = 'msg-row ' + (isBot ? 'bot-row' : 'user-row');

    const avatar = document.createElement('div');
    avatar.className = 'msg-avatar';
    avatar.textContent = isBot ? '🤖' : '👤';

    const msg = document.createElement('div');
    msg.className = 'msg ' + role;
    msg.id = id;
    
    if (isBot && !isRaw) {
        msg.innerHTML = formatMarkdown(content);
    } else {
        msg.innerHTML = content;
    }

    if (isBot) {
        row.appendChild(avatar);
        row.appendChild(msg);
    } else {
        row.appendChild(msg);
        row.appendChild(avatar);
    }

    chatWin.appendChild(row);
    scrollToBottom();
    return id;
}

function sendSuggestion(text) {
    const input = document.getElementById('chat-input');
    input.value = text;
    document.getElementById('chat-form').dispatchEvent(new Event('submit'));
}

function scrollToBottom() {
    const chatWin = document.getElementById('chat-window');
    chatWin.scrollTop = chatWin.scrollHeight;
}

function openLightbox(src) {
    document.getElementById('lightbox-img').src = src;
    document.getElementById('img-lightbox').classList.add('open');
}

function closeLightbox() {
    document.getElementById('img-lightbox').classList.remove('open');
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>