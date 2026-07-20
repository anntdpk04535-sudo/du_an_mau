<?php
require_once __DIR__ . "/../includes/functions.php";
$pageTitle = __('page_title_contact');
$user = currentUser();
include __DIR__ . "/../includes/header.php";
?>
<style>
/* ===== Layout ===== */
.contact-wrap { display:grid; grid-template-columns:1fr 1.1fr; gap:28px; align-items:start; }
@media(max-width:800px){ .contact-wrap { grid-template-columns:1fr; } }

/* Info panel — bỏ sticky */
.contact-info {
  background:linear-gradient(145deg,#1b4332 0%,#2d6a4f 60%,#40916c 100%);
  color:white; border-radius:16px; padding:32px;
}
.contact-info h2 { margin:0 0 22px; font-size:20px; font-weight:700; }
.contact-info-item { display:flex; align-items:flex-start; gap:14px; margin-bottom:18px; font-size:14px; line-height:1.6; }
.contact-info-icon { font-size:22px; flex-shrink:0; margin-top:1px; }
.contact-info-item small { opacity:.75; display:block; font-size:12px; }
.contact-info-divider { border:none; border-top:1px solid rgba(255,255,255,.18); margin:22px 0; }
.contact-info-note { font-size:13px; opacity:.8; margin:0; line-height:1.7; }

/* Form box */
.contact-panel { display:flex; flex-direction:column; gap:18px; }
.form-box { background:white; border-radius:16px; padding:28px; box-shadow:0 2px 16px rgba(0,0,0,.07); }
.form-box-title { font-size:17px; font-weight:700; color:#1b4332; margin:0 0 18px; display:flex; align-items:center; gap:8px; }
.form-group { margin-bottom:14px; }
.form-group label { display:block; font-size:13px; font-weight:600; color:#374151; margin-bottom:6px; }
.form-group input, .form-group textarea {
  width:100%; border:1.5px solid #e5e7eb; border-radius:10px; padding:10px 13px;
  font-size:14px; font-family:inherit; transition:border-color .2s,box-shadow .2s; box-sizing:border-box;
}
.form-group input:focus, .form-group textarea:focus {
  outline:none; border-color:#4ade80; box-shadow:0 0 0 3px rgba(74,222,128,.15);
}
.form-group textarea { resize:vertical; min-height:110px; }
.btn-submit {
  display:inline-flex; align-items:center; justify-content:center; gap:9px;
  background:linear-gradient(135deg,#1b4332,#2d6a4f 55%,#40916c);
  color:white; padding:12px 28px; border-radius:30px; font-weight:700;
  font-size:15px; border:none; cursor:pointer; width:100%;
  transition:transform .15s,box-shadow .2s,opacity .2s;
  box-shadow:0 4px 16px rgba(27,67,50,.35);
}
.btn-submit:hover:not(:disabled) { transform:translateY(-2px); box-shadow:0 8px 22px rgba(27,67,50,.45); }
.btn-submit:active { transform:translateY(0); }
.btn-submit:disabled { opacity:.6; cursor:not-allowed; transform:none; box-shadow:none; }
.btn-submit .btn-icon { font-size:18px; transition:transform .2s; }
.btn-submit:hover:not(:disabled) .btn-icon { transform:translateX(3px); }
.error-msg { background:#fef2f2; border:1.5px solid #fca5a5; color:#dc2626; border-radius:8px; padding:10px 14px; font-size:13px; margin-top:10px; display:none; }

/* Session restore banner */
.session-banner {
  background:linear-gradient(90deg,#ecfdf5,#d1fae5);
  border:1.5px solid #6ee7b7; border-radius:12px; padding:16px 20px;
  display:flex; align-items:center; justify-content:space-between; gap:14px; flex-wrap:wrap;
}
.session-banner-info strong { display:block; font-size:15px; color:#065f46; margin-bottom:4px; }
.session-banner-info span { font-size:12px; color:#6b7280; }
.btn-new-msg {
  flex-shrink:0; font-size:12px; padding:8px 18px; border-radius:20px;
  border:1.5px solid #059669; background:white; color:#059669;
  cursor:pointer; font-weight:700; transition:all .15s; white-space:nowrap;
}
.btn-new-msg:hover { background:#059669; color:white; }

/* Success state */
.success-state { text-align:center; padding:28px 16px; }
.success-icon { font-size:52px; margin-bottom:12px; }
.success-state h3 { color:#1b4332; margin:0 0 8px; font-size:18px; }
.success-state p { color:#6b7280; font-size:13px; line-height:1.7; margin:0 0 16px; }

/* ===== Phần Phản hồi bên dưới ===== */
.replies-section-wrap {
  margin-top:40px;
  border-top:2px solid #e5e7eb;
  padding-top:32px;
}
.replies-section-wrap .section-heading {
  display:flex; align-items:center; justify-content:space-between; margin-bottom:20px; flex-wrap:wrap; gap:10px;
}
.replies-section-wrap .section-heading h2 { margin:0; font-size:20px; color:#1b4332; display:flex; align-items:center; gap:10px; }
.conn-badge {
  display:inline-flex; align-items:center; gap:6px;
  font-size:11px; font-weight:700; padding:4px 12px; border-radius:20px;
  background:#f0fdf4; color:#166534; border:1px solid #bbf7d0;
}
.live-dot { width:8px;height:8px;border-radius:50%;background:#22c55e;animation:blink 1.4s infinite;flex-shrink:0; }
@keyframes blink{0%,100%{opacity:1;transform:scale(1)}50%{opacity:.4;transform:scale(.75)}}
.reply-new-dot { display:none;width:9px;height:9px;border-radius:50%;background:#f59e0b;animation:blink 1.4s infinite; }

/* No session state */
.no-session-box {
  background:#f9fafb; border:1.5px dashed #d1d5db; border-radius:14px;
  padding:36px; text-align:center; color:#9ca3af;
}
.no-session-box .ns-icon { font-size:44px; margin-bottom:12px; }
.no-session-box p { font-size:14px; margin:0; line-height:1.7; }

/* Conversation card */
.conv-card {
  background:white; border-radius:14px; box-shadow:0 2px 12px rgba(0,0,0,.07);
  overflow:hidden; margin-bottom:24px;
}
.conv-card-header {
  padding:14px 20px; background:#f9fafb; border-bottom:1px solid #f0f0f0;
  display:flex; align-items:center; gap:12px; flex-wrap:wrap;
}
.conv-meta { flex:1; }
.conv-subject { font-weight:700; font-size:14px; color:#111827; }
.conv-sent-at { font-size:12px; color:#9ca3af; margin-top:2px; }
.conv-status { font-size:11px; font-weight:700; padding:3px 10px; border-radius:10px; }
.conv-status.waiting { background:#fff7ed; color:#c2410c; border:1px solid #fed7aa; }
.conv-status.replied { background:#dbeafe; color:#1e40af; border:1px solid #bfdbfe; }

/* Chat bubbles */
.chat-list { padding:16px 20px; display:flex; flex-direction:column; gap:12px; }
.bubble-row { display:flex; gap:10px; }
.bubble-row.admin-row { flex-direction:row-reverse; }
.avatar-circle {
  width:34px; height:34px; border-radius:50%; flex-shrink:0;
  display:flex; align-items:center; justify-content:center;
  font-size:13px; font-weight:800;
}
.avatar-user  { background:#e5e7eb; color:#6b7280; }
.avatar-admin { background:linear-gradient(135deg,#1b4332,#40916c); color:white; }
.bubble-content { max-width:75%; }
.bubble-sender { font-size:11px; font-weight:700; color:#9ca3af; margin-bottom:4px; }
.admin-row .bubble-sender { text-align:right; }
.bubble-text {
  font-size:14px; line-height:1.65; padding:11px 15px; border-radius:14px;
  word-break:break-word; animation:slideUp .25s ease;
}
.bubble-user  { background:#f3f4f6; border:1px solid #e5e7eb; border-radius:0 14px 14px 14px; color:#1f2937; }
.bubble-admin { background:linear-gradient(135deg,#1b4332,#2d6a4f); color:white; border-radius:14px 0 14px 14px; box-shadow:0 2px 8px rgba(27,67,50,.2); }
.bubble-time  { font-size:11px; color:#9ca3af; margin-top:5px; }
.admin-row .bubble-time { text-align:right; }

/* Waiting */
.waiting-state { text-align:center; padding:24px 16px; }
.waiting-badge { display:inline-flex;align-items:center;gap:8px;background:#fff7ed;color:#c2410c;padding:8px 16px;border-radius:20px;font-size:13px;font-weight:600;border:1px solid #fed7aa; }
.waiting-dot { width:7px;height:7px;border-radius:50%;background:#f97316;animation:blink 1.4s infinite; }

@keyframes slideUp{from{opacity:0;transform:translateY(5px)}to{opacity:1;transform:none}}
@keyframes fadeUp{from{opacity:0;transform:translateY(6px)}to{opacity:1;transform:none}}
</style>

<h1 class="section-title">📬 <?= __('contact_us') ?></h1>
<p class="section-sub"><?= __('contact_sub') ?></p>

<!-- ===== Grid form + info ===== -->
<div class="contact-wrap">
  <!-- Info -->
  <div class="contact-info">
    <h2>📍 <?= __('contact_info') ?></h2>
    <div class="contact-info-item">
      <div class="contact-info-icon">🏢</div>
      <div><?= __('contact_org') ?><small><?= __('contact_support_247') ?></small></div>
    </div>
    <div class="contact-info-item">
      <div class="contact-info-icon">📧</div>
      <div>support@daklak-travel.ai</div>
    </div>
    <div class="contact-info-item">
      <div class="contact-info-icon">📞</div>
      <div>0262 3957 555<small><?= __('contact_working_hours') ?></small></div>
    </div>
    <div class="contact-info-item">
      <div class="contact-info-icon">📍</div>
      <div>01 Lê Duẩn, TP. Buôn Ma Thuột<small><?= __('contact_province') ?></small></div>
    </div>
    <hr class="contact-info-divider">
    <p class="contact-info-note">💡 <?= __('contact_response_time') ?></p>
  </div>

  <!-- Form -->
  <div class="contact-panel">
    <div class="form-box" id="contact-form-wrap">
      <div class="form-box-title">✉️ <?= __('send_msg') ?></div>
      <form id="contact-form" autocomplete="off">
        <?php if (!$user): ?>
        <div class="form-group">
          <label><?= __('fullname_label') ?> *</label>
          <input type="text" name="name" required placeholder="<?= __('contact_name_ph') ?>">
        </div>
        <div class="form-group">
          <label><?= __('email_label') ?> *</label>
          <input type="email" name="email" required placeholder="<?= __('contact_email_ph') ?>">
        </div>
        <?php else: ?>
        <p style="font-size:13px;color:#6b7280;margin:0 0 14px;padding:10px 14px;background:#f9fafb;border-radius:8px;">
          👋 <?= __('contact_send_as') ?>: <strong style="color:#1b4332;"><?= e($user["full_name"]) ?></strong>
        </p>
        <?php endif; ?>
        <div class="form-group">
          <label><?= __('subject_label') ?></label>
          <input type="text" name="subject" placeholder="<?= __('subject_label') ?>...">
        </div>
        <div class="form-group">
          <label><?= __('message_label') ?> *</label>
          <textarea name="message" required placeholder="<?= __('message_label') ?>..."></textarea>
        </div>
        <div class="error-msg" id="contact-error"></div>
        <button type="submit" class="btn-submit" id="contact-btn">
          <span class="btn-icon">📤</span> <?= __('send_msg') ?>
        </button>
      </form>
    </div>
  </div>
</div>

<!-- ===== Phần Phản hồi từ Admin — luôn hiển thị bên dưới ===== -->
<div class="replies-section-wrap">
  <div class="section-heading">
    <h2>
      💬 <?= __('contact_admin_reply') ?>
      <span class="reply-new-dot" id="reply-new-dot" title="<?= __('contact_new_reply') ?>"></span>
    </h2>
    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
      <div class="conn-badge">
        <div class="live-dot"></div>
        <span id="conn-status"><?= __('contact_connecting') ?></span>
      </div>
      <button class="btn-new-msg" id="btn-new-msg" style="display:none;" onclick="startNewMessage()">✏️ <?= __('contact_new_msg_btn') ?></button>
    </div>
  </div>

  <!-- Nội dung phản hồi được inject bởi JS -->
  <div id="replies-area">
    <div class="no-session-box">
      <div class="ns-icon">📭</div>
      <p><?= __('contact_no_msg') ?><br>
         <?= __('contact_fill_form') ?></p>
    </div>
  </div>
</div>

<script>
const BASE_URL   = "<?= url("") ?>";
const STORE_KEY  = "daklak_contact_v2";
const PAGE_TITLE = "<?= addslashes($pageTitle) ?>";

let contactId   = null;
let lastReplyId = 0;
let sseSource   = null;
let allReplies  = [];
let sessionData = null;

// ===== LocalStorage — kiểm tra cả key cũ lẫn key mới =====
const Session = {
  save:  (d) => { try { localStorage.setItem(STORE_KEY, JSON.stringify(d)); } catch {} },
  load:  ()  => {
    try {
      // Thử key mới trước
      let d = JSON.parse(localStorage.getItem(STORE_KEY) || "null");
      if (d?.contact_id) return d;
      // Thử key cũ (backward compat)
      d = JSON.parse(localStorage.getItem("daklak_contact_session") || "null");
      if (d?.contact_id) return d;
      return null;
    } catch { return null; }
  },
  clear: ()  => {
    try {
      localStorage.removeItem(STORE_KEY);
      localStorage.removeItem("daklak_contact_session");
    } catch {}
  }
};

// ===== Init khi tải trang =====
document.addEventListener("DOMContentLoaded", async () => {
  setConnStatus("<?= __('contact_checking') ?>");

  // Bước 1: Thử lấy từ server (user đã đăng nhập)
  const serverOk = await tryLoadFromServer();
  if (serverOk) return;

  // Bước 2: Thử localStorage (khách / user chưa đăng nhập)
  const s = Session.load();
  if (s?.contact_id) {
    contactId   = s.contact_id;
    sessionData = s;
    showSessionBanner(s);
    document.getElementById("btn-new-msg").style.display = "inline-flex";
    await loadAllReplies();
    startListening();
    return;
  }

  // Không có session
  setConnStatus("<?= __('contact_no_msg_short') ?>");
});

// ===== Lấy từ server nếu user đã đăng nhập =====
async function tryLoadFromServer() {
  try {
    const res  = await fetch(`${BASE_URL}/api/contact_my.php`);
    const data = await res.json();
    if (!data.success || !data.contact) return false;

    const c   = data.contact;
    contactId   = c.id;
    lastReplyId = data.last_id || 0;
    allReplies  = data.replies || [];

    sessionData = {
      contact_id: c.id,
      subject:    c.subject || c.message?.substring(0, 50) || "<?= __('contact_no_subject') ?>",
      message:    c.message || "",
      sent_at:    c.created_at
    };
    // Đồng bộ vào localStorage để còn dùng cho SSE
    Session.save(sessionData);

    showSessionBanner(sessionData);
    document.getElementById("btn-new-msg").style.display = "inline-flex";
    renderConversation();
    startListening();
    return true;
  } catch { return false; }
}

// ===== Form wrap: banner session =====
function showSessionBanner(s) {
  const sentAt  = s.sent_at ? new Date(s.sent_at).toLocaleString("vi-VN") : "";
  const subject = s.subject || "<?= __('contact_no_subject') ?>";
  document.getElementById("contact-form-wrap").innerHTML = `
    <div class="session-banner">
      <div class="session-banner-info">
        <strong>📩 <?= __('contact_msg_sent') ?></strong>
        <span>"${subject}" · ${sentAt}</span>
      </div>
      <button class="btn-new-msg" onclick="startNewMessage()">✏️ <?= __('contact_new_msg_btn') ?></button>
    </div>`;
}

function showSuccessState(subjectVal, messageVal) {
  document.getElementById("contact-form-wrap").innerHTML = `
    <div class="success-state">
      <div class="success-icon">✅</div>
      <h3><?= __('contact_sent_success') ?></h3>
      <p><?= __('contact_sent_desc') ?></p>
      <button class="btn-new-msg" onclick="startNewMessage()">✏️ <?= __('contact_new_msg_btn') ?></button>
    </div>`;
}

// ===== Gửi tin mới =====
function startNewMessage() {
  if (!confirm("<?= __('contact_confirm_new') ?>")) return;
  if (sseSource) { sseSource.close(); sseSource = null; }
  Session.clear();
  location.reload();
}

// ===== Load toàn bộ replies =====
async function loadAllReplies() {
  if (!contactId) return;
  setConnStatus("<?= __('contact_loading') ?>");
  try {
    const res  = await fetch(`${BASE_URL}/api/contact_check_reply.php?contact_id=${contactId}&last_id=0`);
    const data = await res.json();
    if (data.success && data.replies) {
      allReplies  = data.replies;
      lastReplyId = data.last_id || 0;
      renderConversation();
    }
  } catch { setConnStatus("⚠️ <?= __('contact_load_error') ?>"); }
}

// ===== Submit form =====
document.getElementById("contact-form").addEventListener("submit", async (e) => {
  e.preventDefault();
  const form   = e.target;
  const btn    = document.getElementById("contact-btn");
  const errEl  = document.getElementById("contact-error");
  errEl.style.display = "none";
  btn.disabled = true;
  btn.innerHTML = '<span class="btn-icon">⏳</span> <?= __('dest_sending') ?>';

  const subjectVal = form.querySelector("[name=subject]")?.value || "";
  const messageVal = form.querySelector("[name=message]").value;

  try {
    const res  = await fetch(`${BASE_URL}/api/contact_submit.php`, {
      method:"POST", headers:{"Content-Type":"application/json"},
      body: JSON.stringify({
        name:    form.querySelector("[name=name]")?.value  || "",
        email:   form.querySelector("[name=email]")?.value || "",
        subject: subjectVal, message: messageVal
      })
    });
    const data = await res.json();
    if (!data.success) {
      errEl.textContent   = data.message || "<?= __('contact_send_failed') ?>";
      errEl.style.display = "block";
      btn.disabled = false;
      btn.innerHTML = '<span class="btn-icon">📤</span> <?= __('send_msg') ?>';
      return;
    }
    contactId = data.contact_id;
    const saved = {
      contact_id: contactId,
      subject:    subjectVal || messageVal.substring(0, 50),
      message:    messageVal,
      sent_at:    new Date().toISOString()
    };
    Session.save(saved);
    sessionData = saved;
    allReplies  = [];

    showSuccessState();
    document.getElementById("btn-new-msg").style.display = "inline-flex";
    renderConversation(); // Hiển thị tin nhắn vừa gửi ngay
    startListening();
  } catch {
    errEl.textContent   = "<?= __('contact_network_error') ?>";
    errEl.style.display = "block";
    btn.disabled = false;
    btn.innerHTML = '<span class="btn-icon">📤</span> <?= __('send_msg') ?>';
  }
});

// ===== Render toàn bộ cuộc trò chuyện =====
function renderConversation() {
  const area = document.getElementById("replies-area");
  const s    = sessionData || Session.load();
  if (!s?.contact_id) return;

  const sentAt  = s.sent_at ? new Date(s.sent_at).toLocaleString("vi-VN") : "";
  const subject = s.subject || "<?= __('contact_no_subject') ?>";
  const msg     = s.message  || subject;
  const hasReplies = allReplies.length > 0;

  // Header card
  let html = `
    <div class="conv-card">
      <div class="conv-card-header">
        <div class="conv-meta">
          <div class="conv-subject">${subject}</div>
          <div class="conv-sent-at"><?= __('contact_sent_at') ?> ${sentAt}</div>
        </div>
        <span class="conv-status ${hasReplies ? 'replied' : 'waiting'}">
          ${hasReplies ? '✅ <?= __('contact_replied') ?>' : '⏳ <?= __('contact_waiting') ?>'}
        </span>
      </div>
      <div class="chat-list">
        <!-- Tin nhắn của user -->
        <div class="bubble-row">
          <div class="avatar-circle avatar-user">👤</div>
          <div class="bubble-content">
            <div class="bubble-sender"><?= __('contact_you') ?></div>
            <div class="bubble-text bubble-user">${msg.replace(/\n/g,"<br>")}</div>
            <div class="bubble-time">${sentAt}</div>
          </div>
        </div>`;

  if (hasReplies) {
    allReplies.forEach(r => {
      const initial = (r.admin_name || "A").charAt(0).toUpperCase();
      const rTime   = new Date(r.created_at).toLocaleString("vi-VN");
      html += `
        <div class="bubble-row admin-row">
          <div class="avatar-circle avatar-admin">${initial}</div>
          <div class="bubble-content">
            <div class="bubble-sender">${r.admin_name || "Admin"}</div>
            <div class="bubble-text bubble-admin">${r.reply_text.replace(/\n/g,"<br>")}</div>
            <div class="bubble-time">${rTime}</div>
          </div>
        </div>`;
    });
  } else {
    html += `
      <div class="waiting-state">
        <div class="waiting-badge">
          <div class="waiting-dot"></div>
          <?= __('contact_waiting_admin') ?>
        </div>
        <p style="font-size:12px;color:#9ca3af;margin-top:10px;"><?= __('contact_reply_time') ?></p>
      </div>`;
  }

  html += `</div></div><div id="conv-bottom"></div>`;
  area.innerHTML = html;

  // Scroll nhẹ xuống phần phản hồi khi có reply mới
  if (hasReplies) {
    setTimeout(() => {
      document.getElementById("conv-bottom")?.scrollIntoView({behavior:"smooth", block:"nearest"});
    }, 100);
  }
}

// ===== SSE =====
function startListening() {
  if (typeof EventSource !== "undefined") connectSSE();
  else fastPoll();
}

function connectSSE() {
  if (sseSource) sseSource.close();
  setConnStatus("🟢 Realtime");
  const url = `${BASE_URL}/api/contact_sse.php?contact_id=${contactId}&last_id=${lastReplyId}`;
  sseSource = new EventSource(url);

  sseSource.addEventListener("reply", (e) => {
    const data = JSON.parse(e.data);
    if (data.replies?.length > 0) {
      appendReplies(data.replies);
      lastReplyId = data.last_id;
    }
  });
  sseSource.addEventListener("reconnect", () => { sseSource.close(); setTimeout(connectSSE, 300); });
  sseSource.addEventListener("ping", () => {});
  sseSource.onerror = () => {
    sseSource.close(); sseSource = null;
    setConnStatus("🔄 Polling");
    setTimeout(fastPoll, 1000);
  };
}

async function fastPoll() {
  if (!contactId) return;
  try {
    const res  = await fetch(`${BASE_URL}/api/contact_check_reply.php?contact_id=${contactId}&last_id=${lastReplyId}`);
    const data = await res.json();
    if (data.success && data.replies?.length > 0) {
      appendReplies(data.replies);
      lastReplyId = data.last_id || lastReplyId;
    }
  } catch {}
  setTimeout(fastPoll, 1500);
}

function appendReplies(newList) {
  let hasNew = false;
  newList.forEach(r => {
    if (!allReplies.find(x => +x.id === +r.id)) { allReplies.push(r); hasNew = true; }
  });
  if (!hasNew) return;
  renderConversation();
  if (document.hidden) {
    document.getElementById("reply-new-dot").style.display = "inline-block";
    document.title = "💬 <?= __('contact_new_reply_title') ?>";
  }
}

function setConnStatus(txt) {
  const el = document.getElementById("conn-status");
  if (el) el.textContent = txt;
}

// Focus lại tab
document.addEventListener("visibilitychange", () => {
  if (!document.hidden) {
    document.getElementById("reply-new-dot").style.display = "none";
    document.title = PAGE_TITLE;
  }
});
</script>

<?php include __DIR__ . "/../includes/footer.php"; ?>
