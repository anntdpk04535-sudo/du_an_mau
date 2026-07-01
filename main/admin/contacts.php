<?php
require_once __DIR__ . "/../includes/functions.php";
requireAdmin();
$pageTitle = "Quản lý Liên hệ - Admin";
$db = getDB();
$contacts = $db->query(
  "SELECT c.*, u.full_name as user_full_name, u.email as user_email
   FROM contacts c LEFT JOIN users u ON c.user_id = u.id
   ORDER BY c.created_at DESC"
)->fetchAll();
include __DIR__ . "/../includes/header.php";
?>
<style>
/* ===== Page header ===== */
.page-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:14px; flex-wrap:wrap; gap:10px; }
.page-header h1 { margin:0; }

/* ===== Realtime bar ===== */
.realtime-bar {
  background:linear-gradient(90deg,#1b4332,#2d6a4f 70%,#40916c);
  color:white; padding:10px 20px; font-size:12px;
  display:flex; align-items:center; gap:10px;
  border-radius:12px 12px 0 0; flex-wrap:wrap;
}
.live-dot { width:9px;height:9px;border-radius:50%;background:#4ade80;flex-shrink:0;animation:blink 1.4s infinite; }
@keyframes blink{0%,100%{opacity:1;transform:scale(1)}50%{opacity:.4;transform:scale(.75)}}
.realtime-clock { margin-left:auto; font-size:14px; font-weight:800; font-variant-numeric:tabular-nums; letter-spacing:.8px; }
#last-update { font-size:11px; opacity:.65; }

/* ===== Main layout ===== */
.admin-layout {
  display:grid; grid-template-columns:300px 1fr; gap:0;
  min-height:76vh; border-radius:0 0 12px 12px;
  overflow:hidden; box-shadow:0 6px 28px rgba(0,0,0,.13); background:#fff;
}

/* ===== Contact list ===== */
.contact-list { border-right:1px solid #e5e7eb; display:flex; flex-direction:column; }
.list-header {
  padding:14px 18px; background:#1b4332; color:white;
  display:flex; align-items:center; justify-content:space-between;
  flex-shrink:0; gap:8px;
}
.list-header h3 { margin:0; font-size:14px; font-weight:700; }
.badge-count { background:#ef4444; color:white; font-size:10px; font-weight:800; padding:2px 7px; border-radius:10px; min-width:18px; text-align:center; }
.list-search { padding:10px 12px; border-bottom:1px solid #f0f0f0; flex-shrink:0; }
.list-search input {
  width:100%; border:1px solid #e5e7eb; border-radius:8px; padding:7px 10px;
  font-size:12px; font-family:inherit; box-sizing:border-box;
  transition:border-color .2s;
}
.list-search input:focus { outline:none; border-color:#4ade80; }
.list-body { overflow-y:auto; flex:1; }
.contact-item { padding:12px 16px; border-bottom:1px solid #f5f5f5; cursor:pointer; transition:background .12s; position:relative; }
.contact-item:hover { background:#f0fdf4; }
.contact-item.active { background:#dcfce7; border-left:3px solid #22c55e; }
.contact-item.status-new::after { content:""; width:8px;height:8px;border-radius:50%;background:#22c55e;position:absolute;top:13px;right:12px; }
.item-name { font-weight:700; font-size:13px; color:#111827; }
.item-subj { font-size:12px; color:#9ca3af; margin-top:2px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.item-footer { display:flex; justify-content:space-between; margin-top:5px; }
.item-time { font-size:10px; color:#d1d5db; }
.item-reltime { font-size:10px; color:#94a3b8; }

/* ===== Detail panel ===== */
.detail-panel {
  display:flex; flex-direction:column; overflow:hidden; height:76vh;
}
.detail-empty { flex:1; display:flex; flex-direction:column; align-items:center; justify-content:center; gap:14px; color:#d1d5db; }
.detail-empty .icon { font-size:56px; }
.detail-empty p { font-size:14px; margin:0; }

/* Chat area */
.chat-area { display:flex; flex-direction:column; height:100%; overflow:hidden; }
.chat-header {
  padding:14px 22px; border-bottom:1px solid #f0f0f0; flex-shrink:0;
  display:flex; align-items:center; gap:14px; background:#fafafa;
}
.chat-header-avatar {
  width:40px;height:40px;border-radius:50%;
  background:linear-gradient(135deg,#1b4332,#40916c);
  color:white;display:flex;align-items:center;justify-content:center;
  font-size:18px;font-weight:800;flex-shrink:0;
}
.chat-header-info h4 { margin:0;font-size:15px;font-weight:700;color:#111827; }
.chat-header-meta { font-size:12px;color:#9ca3af;margin-top:2px;display:flex;gap:8px;flex-wrap:wrap; }
.status-pill { display:inline-flex;align-items:center;gap:4px;padding:2px 9px;border-radius:10px;font-size:11px;font-weight:700; }
.status-pill.new     { background:#dcfce7;color:#166534; }
.status-pill.read    { background:#fef9c3;color:#854d0e; }
.status-pill.replied { background:#dbeafe;color:#1e40af; }

/* Messages scroll area */
.chat-messages {
  flex:1; overflow-y:auto; padding:20px 22px; display:flex;
  flex-direction:column; gap:14px; background:#fafafa;
}

/* User message bubble */
.msg-user { display:flex; gap:12px; }
.msg-avatar {
  width:34px;height:34px;border-radius:50%;background:#e5e7eb;
  color:#6b7280;display:flex;align-items:center;justify-content:center;
  font-size:14px;font-weight:700;flex-shrink:0;
}
.msg-content { flex:1; }
.msg-sender { font-size:11px;font-weight:700;color:#6b7280;margin-bottom:4px; }
.msg-bubble-user {
  background:white;border:1px solid #e5e7eb;border-radius:0 12px 12px 12px;
  padding:12px 16px;font-size:14px;line-height:1.7;color:#1f2937;
  box-shadow:0 1px 4px rgba(0,0,0,.05);
}

/* Admin reply bubble */
.msg-admin { display:flex; flex-direction:row-reverse; gap:12px; }
.msg-avatar-admin {
  width:34px;height:34px;border-radius:50%;
  background:linear-gradient(135deg,#1b4332,#40916c);
  color:white;display:flex;align-items:center;justify-content:center;
  font-size:14px;font-weight:700;flex-shrink:0;
}
.msg-bubble-admin {
  background:linear-gradient(135deg,#1b4332,#2d6a4f);
  color:white;border-radius:12px 0 12px 12px;
  padding:12px 16px;font-size:14px;line-height:1.7;
  box-shadow:0 2px 8px rgba(27,67,50,.25);
  animation:slideUp .25s ease;
}
@keyframes slideUp{from{opacity:0;transform:translateY(5px)}to{opacity:1;transform:none}}
.msg-meta { font-size:11px;opacity:.65;margin-top:5px;text-align:right; }

/* Replies header */
.replies-divider { text-align:center; padding:6px 0; }
.replies-divider span {
  font-size:11px;color:#9ca3af;background:#fafafa;
  padding:3px 12px;border-radius:10px;border:1px solid #e5e7eb;
}

/* Reply form */
.chat-input-area {
  padding:14px 18px; border-top:1px solid #e5e7eb; flex-shrink:0; background:white;
}
.chat-input-box {
  display:flex; gap:10px; align-items:flex-end;
}
.chat-input-box textarea {
  flex:1; border:1.5px solid #e5e7eb; border-radius:12px; padding:10px 14px;
  font-size:14px;font-family:inherit;resize:none;min-height:44px;max-height:120px;
  transition:border-color .2s,box-shadow .2s; line-height:1.5;
}
.chat-input-box textarea:focus { outline:none;border-color:#4ade80;box-shadow:0 0 0 3px rgba(74,222,128,.15); }
.btn-send {
  display:inline-flex;align-items:center;justify-content:center;gap:6px;
  background:linear-gradient(135deg,#1b4332,#2d6a4f 60%,#40916c);
  color:white;padding:10px 18px;border-radius:12px;font-size:13px;font-weight:700;
  border:none;cursor:pointer;flex-shrink:0;height:44px;white-space:nowrap;
  transition:transform .15s,box-shadow .2s,opacity .15s;
  box-shadow:0 3px 12px rgba(27,67,50,.3);
}
.btn-send:hover:not(:disabled) { transform:translateY(-1px);box-shadow:0 6px 18px rgba(27,67,50,.4); }
.btn-send:active { transform:translateY(0); }
.btn-send:disabled { opacity:.55;cursor:not-allowed;transform:none;box-shadow:none; }
.btn-send .s-icon { font-size:16px;transition:transform .2s; }
.btn-send:hover:not(:disabled) .s-icon { transform:translateX(2px); }
.send-status { font-size:12px;font-weight:600;margin-top:6px;min-height:18px; }

/* ===== Toast ===== */
.toast-container { position:fixed;top:18px;right:18px;z-index:9999;display:flex;flex-direction:column;gap:10px;pointer-events:none; }
.toast {
  background:white;border-radius:12px;padding:13px 16px;
  box-shadow:0 8px 28px rgba(0,0,0,.15);
  display:flex;align-items:flex-start;gap:12px;max-width:300px;
  pointer-events:auto;border-left:4px solid #22c55e;
  animation:toastIn .28s ease;
}
.toast.out { animation:toastOut .22s ease forwards; }
@keyframes toastIn  { from{opacity:0;transform:translateX(40px)} to{opacity:1;transform:none} }
@keyframes toastOut { from{opacity:1;transform:none}              to{opacity:0;transform:translateX(40px)} }
.toast-icon { font-size:20px;flex-shrink:0; }
.toast-body { flex:1;min-width:0; }
.toast-title { font-weight:700;font-size:13px;color:#1b4332; }
.toast-msg { font-size:12px;color:#6b7280;margin-top:2px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap; }
.toast-time { font-size:11px;color:#9ca3af;margin-top:4px; }
.toast-x { cursor:pointer;color:#9ca3af;font-size:15px;line-height:1;padding:1px 3px; }
.toast-x:hover { color:#374151; }

/* ===== Mobile tabs ===== */
.mobile-tabs { display:none; }

/* ===== Responsive ===== */
@media(max-width:900px) { .admin-layout { grid-template-columns:260px 1fr; } }
@media(max-width:680px) {
  .admin-layout { grid-template-columns:1fr;height:auto;min-height:unset; }
  .detail-panel { height:auto;min-height:500px; }
  .chat-messages { max-height:320px; }
  .mobile-tabs { display:flex;background:#f8fafc;border-bottom:1px solid #e2e8f0; }
  .m-tab { flex:1;padding:11px;text-align:center;font-size:13px;font-weight:600;color:#6b7280;cursor:pointer;border-bottom:3px solid transparent;transition:.2s; }
  .m-tab.active { color:#1b4332;border-bottom-color:#22c55e;background:white; }
  .contact-list.m-hidden, .detail-panel.m-hidden { display:none; }
  .realtime-bar { border-radius:0;gap:6px; }
  .realtime-clock { font-size:12px; }
}
@media(max-width:480px) {
  .btn-send { padding:10px 13px; }
  .btn-send span:not(.s-icon) { display:none; }
}
</style>

<!-- Toast container -->
<div class="toast-container" id="toast-container"></div>

<div class="page-header">
  <h1 class="section-title">📬 Quản lý Liên hệ</h1>
  <a href="<?= url("/admin/destinations.php") ?>" class="btn secondary" style="font-size:13px;">← Quản lý điểm đến</a>
</div>

<!-- Realtime bar -->
<div class="realtime-bar">
  <div class="live-dot"></div>
  <span>Lắng nghe liên hệ mới theo thời gian thực</span>
  <span id="last-update">—</span>
  <span class="realtime-clock" id="rt-clock">--:--:--</span>
</div>

<!-- Mobile tabs -->
<div class="mobile-tabs" id="m-tabs">
  <div class="m-tab active" id="tab-list" onclick="switchTab('list')">
    📋 Tin nhắn <span class="badge-count" id="new-count-tab">0</span>
  </div>
  <div class="m-tab" id="tab-detail" onclick="switchTab('detail')">💬 Chi tiết</div>
</div>

<div class="admin-layout">
  <!-- Left: contact list -->
  <div class="contact-list" id="panel-list">
    <div class="list-header">
      <h3>📋 Tin nhắn</h3>
      <span class="badge-count" id="new-count"><?= count(array_filter($contacts, fn($c) => $c["status"] === "new")) ?></span>
    </div>
    <div class="list-search">
      <input type="text" id="search-input" placeholder="🔍 Tìm kiếm..." oninput="filterContacts(this.value)">
    </div>
    <div class="list-body" id="contact-list-body">
      <?php foreach ($contacts as $c):
        $name = $c["user_full_name"] ?: $c["guest_name"] ?: "Khách ẩn danh";
        $ts   = strtotime($c["created_at"]);
      ?>
      <div class="contact-item status-<?= $c["status"] ?>"
           data-id="<?= $c["id"] ?>" data-ts="<?= $ts ?>"
           data-name="<?= strtolower(e($name)) ?>"
           onclick="openContact(<?= $c["id"] ?>)">
        <div class="item-name"><?= e($name) ?></div>
        <div class="item-subj"><?= e($c["subject"] ?: $c["message"]) ?></div>
        <div class="item-footer">
          <span class="item-time"><?= date("d/m H:i", $ts) ?></span>
          <span class="item-reltime" data-ts="<?= $ts ?>"></span>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Right: detail panel -->
  <div class="detail-panel" id="panel-detail">
    <div class="detail-empty" id="contact-detail">
      <div class="icon">📭</div>
      <p>Chọn một tin nhắn để bắt đầu trò chuyện</p>
    </div>
  </div>
</div>

<script>
const BASE = "<?= url("") ?>";
let curId   = null;
let pollTm  = null;
let knownIds = new Set([<?= implode(",", array_column($contacts, "id")) ?>]);
let lastMax  = Math.max(0, ...Array.from(knownIds));
const isMobile = () => window.innerWidth <= 680;

// ===== Real-time clock =====
(function clock() {
  const el = document.getElementById("rt-clock");
  const tick = () => el.textContent = new Date().toLocaleTimeString("vi-VN", {hour:"2-digit",minute:"2-digit",second:"2-digit"});
  tick(); setInterval(tick, 1000);
})();

// ===== Relative time =====
function rel(ts) {
  const d = Math.floor(Date.now()/1000) - ts;
  if (d < 60)     return "Vừa xong";
  if (d < 3600)   return Math.floor(d/60) + " phút trước";
  if (d < 86400)  return Math.floor(d/3600) + " giờ trước";
  if (d < 604800) return Math.floor(d/86400) + " ngày trước";
  return new Date(ts*1000).toLocaleDateString("vi-VN");
}
function updateRel() {
  document.querySelectorAll(".item-reltime[data-ts]").forEach(el => { if(+el.dataset.ts) el.textContent = rel(+el.dataset.ts); });
}
updateRel(); setInterval(updateRel, 30000);

// ===== Search =====
function filterContacts(q) {
  q = q.trim().toLowerCase();
  document.querySelectorAll(".contact-item").forEach(el => {
    el.style.display = (!q || (el.dataset.name||"").includes(q)) ? "" : "none";
  });
}

// ===== Mobile tabs =====
function switchTab(tab) {
  const lp = document.getElementById("panel-list");
  const dp = document.getElementById("panel-detail");
  const tl = document.getElementById("tab-list");
  const td = document.getElementById("tab-detail");
  if (tab === "list") {
    lp.classList.remove("m-hidden"); dp.classList.add("m-hidden");
    tl.classList.add("active");      td.classList.remove("active");
  } else {
    lp.classList.add("m-hidden");    dp.classList.remove("m-hidden");
    td.classList.add("active");      tl.classList.remove("active");
  }
}

// ===== Open contact =====
async function openContact(id) {
  curId = id;
  document.querySelectorAll(".contact-item").forEach(el => el.classList.toggle("active", +el.dataset.id === id));

  // Mark read
  fetch(`${BASE}/api/contact_mark_read.php`, {
    method:"POST", headers:{"Content-Type":"application/json"},
    body: JSON.stringify({contact_id: id})
  });
  document.querySelector(`.contact-item[data-id="${id}"]`)?.classList.remove("status-new");

  // Render loading skeleton
  const item = document.querySelector(`.contact-item[data-id="${id}"]`);
  const name = item?.querySelector(".item-name")?.textContent || "Khách";
  const ts   = +(item?.dataset.ts || 0);
  renderChatSkeleton(id, name, ts);

  // Fetch replies + message
  const [repliesData, msgData] = await Promise.all([
    fetch(`${BASE}/api/contact_check_reply.php?contact_id=${id}&last_id=0`).then(r=>r.json()).catch(()=>({replies:[]})),
    fetch(`${BASE}/api/contact_get.php?id=${id}`).then(r=>r.json()).catch(()=>({success:false}))
  ]);

  fillChatContent(id, name, ts, msgData, repliesData.replies || []);
  if (isMobile()) switchTab("detail");

  clearTimeout(pollTm);
  autoRefresh(id);
}

function renderChatSkeleton(id, name, ts) {
  const initial = name.charAt(0).toUpperCase();
  const timeStr = ts ? new Date(ts*1000).toLocaleString("vi-VN") : "";
  document.getElementById("contact-detail").outerHTML = `
    <div class="chat-area" id="contact-detail">
      <div class="chat-header">
        <div class="chat-header-avatar">${initial}</div>
        <div class="chat-header-info">
          <h4>${name}</h4>
          <div class="chat-header-meta">
            <span>⏰ ${timeStr}</span>
            <span style="color:#94a3b8;">${ts ? rel(ts) : ""}</span>
          </div>
        </div>
      </div>
      <div class="chat-messages" id="chat-messages">
        <div style="text-align:center;color:#d1d5db;padding:20px;font-size:13px;">⏳ Đang tải...</div>
      </div>
      <div class="chat-input-area">
        <div class="chat-input-box">
          <textarea id="reply-text" placeholder="Nhập nội dung trả lời..." rows="1"
            oninput="autoGrow(this)" onkeydown="handleEnter(event,${id})"></textarea>
          <button class="btn-send" id="btn-send" onclick="sendReply(${id})">
            <span class="s-icon">📤</span> <span>Gửi</span>
          </button>
        </div>
        <div class="send-status" id="send-status"></div>
      </div>
    </div>`;
}

function fillChatContent(id, name, ts, msgData, replies) {
  const msgs = document.getElementById("chat-messages");
  if (!msgs) return;

  const initial  = name.charAt(0).toUpperCase();
  const msg      = msgData.success ? (msgData.contact?.message || "") : "";
  const msgTime  = ts ? new Date(ts*1000).toLocaleString("vi-VN") : "";

  let html = `
    <div class="msg-user">
      <div class="msg-avatar">${initial}</div>
      <div class="msg-content">
        <div class="msg-sender">${name} · ${msgTime}</div>
        <div class="msg-bubble-user">${msg ? msg.replace(/\n/g,"<br>") : '<em style="color:#d1d5db">Đang tải...</em>'}</div>
      </div>
    </div>`;

  if (replies.length) {
    html += `<div class="replies-divider"><span>── Phản hồi của Admin ──</span></div>`;
    html += renderReplies(replies);
  } else {
    html += `<div style="text-align:center;color:#9ca3af;font-size:13px;padding:10px;">Chưa có phản hồi nào.</div>`;
  }
  msgs.innerHTML = html + '<div id="chat-bottom"></div>';
  scrollBottom();
}

function renderReplies(replies) {
  return replies.map(r => {
    const a   = (r.admin_name || "Admin").charAt(0).toUpperCase();
    const t   = new Date(r.created_at).toLocaleString("vi-VN");
    const rts = Math.floor(new Date(r.created_at).getTime()/1000);
    return `
    <div class="msg-admin">
      <div class="msg-avatar-admin">${a}</div>
      <div class="msg-content">
        <div class="msg-bubble-admin">
          ${r.reply_text.replace(/\n/g,"<br>")}
          <div class="msg-meta">${r.admin_name||"Admin"} · ${t} (${rel(rts)})</div>
        </div>
      </div>
    </div>`;
  }).join("");
}

function scrollBottom() {
  const el = document.getElementById("chat-bottom");
  if (el) el.scrollIntoView({behavior:"smooth"});
}

// ===== Auto-grow textarea =====
function autoGrow(el) {
  el.style.height = "44px";
  el.style.height = Math.min(el.scrollHeight, 120) + "px";
}

// ===== Send on Ctrl+Enter =====
function handleEnter(e, id) {
  if (e.key === "Enter" && (e.ctrlKey || e.metaKey)) {
    e.preventDefault();
    sendReply(id);
  }
}

// ===== Send reply =====
async function sendReply(id) {
  const text   = document.getElementById("reply-text")?.value.trim();
  const status = document.getElementById("send-status");
  const btn    = document.getElementById("btn-send");
  if (!text) {
    status.style.color = "#ef4444";
    status.textContent = "⚠️ Vui lòng nhập nội dung!";
    setTimeout(() => { if(status) status.textContent = ""; }, 2000);
    return;
  }
  if (btn) { btn.disabled = true; btn.innerHTML = '<span class="s-icon">⏳</span> <span>Đang gửi...</span>'; }
  status.style.color = "#9ca3af"; status.textContent = "";

  try {
    const res  = await fetch(`${BASE}/api/contact_reply.php`, {
      method:"POST", headers:{"Content-Type":"application/json"},
      body: JSON.stringify({contact_id: id, reply: text})
    });
    const data = await res.json();
    if (data.success) {
      document.getElementById("reply-text").value = "";
      document.getElementById("reply-text").style.height = "44px";
      status.style.color = "#16a34a";
      status.textContent = "✅ Đã gửi!";
      setTimeout(() => { if(status) status.textContent = ""; }, 2500);
      // Refresh ngay
      const rRes = await fetch(`${BASE}/api/contact_check_reply.php?contact_id=${id}&last_id=0`);
      const rData= await rRes.json();
      refreshRepliesInUI(rData.replies || []);
    } else {
      status.style.color = "#ef4444";
      status.textContent = "❌ " + (data.message || "Lỗi");
    }
  } catch {
    status.style.color = "#ef4444";
    status.textContent = "❌ Lỗi kết nối";
  } finally {
    if (btn) { btn.disabled = false; btn.innerHTML = '<span class="s-icon">📤</span> <span>Gửi</span>'; }
  }
}

function refreshRepliesInUI(replies) {
  const msgs = document.getElementById("chat-messages");
  if (!msgs || curId === null) return;
  // Keep first message, replace from divider onward
  const item   = document.querySelector(`.contact-item[data-id="${curId}"]`);
  const name   = item?.querySelector(".item-name")?.textContent || "Khách";
  const ts     = +(item?.dataset.ts || 0);
  const initial = name.charAt(0).toUpperCase();
  const msg     = msgs.querySelector(".msg-bubble-user")?.innerHTML || "";
  const msgTime = ts ? new Date(ts*1000).toLocaleString("vi-VN") : "";

  let html = `
    <div class="msg-user">
      <div class="msg-avatar">${initial}</div>
      <div class="msg-content">
        <div class="msg-sender">${name} · ${msgTime}</div>
        <div class="msg-bubble-user">${msg}</div>
      </div>
    </div>
    <div class="replies-divider"><span>── Phản hồi của Admin ──</span></div>
    ${renderReplies(replies)}
    <div id="chat-bottom"></div>`;
  msgs.innerHTML = html;
  scrollBottom();
}

// ===== Auto refresh replies (5s) =====
async function autoRefresh(id) {
  if (curId !== id) return;
  try {
    const res  = await fetch(`${BASE}/api/contact_check_reply.php?contact_id=${id}&last_id=0`);
    const data = await res.json();
    if (data.replies) refreshRepliesInUI(data.replies);
  } catch {}
  pollTm = setTimeout(() => autoRefresh(id), 5000);
}

// ===== Toast =====
function showToast(c) {
  const name = c.user_full_name || c.guest_name || "Khách";
  const tid  = "t" + Date.now();
  const div  = document.createElement("div");
  div.className = "toast"; div.id = tid;
  div.innerHTML = `
    <span class="toast-icon">📩</span>
    <div class="toast-body">
      <div class="toast-title">Tin nhắn mới từ ${name}</div>
      <div class="toast-msg">${(c.subject||c.message||"").substring(0,55)}</div>
      <div class="toast-time">${new Date().toLocaleTimeString("vi-VN")}</div>
    </div>
    <span class="toast-x" onclick="rmToast('${tid}')">✕</span>`;
  document.getElementById("toast-container").appendChild(div);
  setTimeout(() => rmToast(tid), 6000);
}
function rmToast(id) {
  const el = document.getElementById(id);
  if (!el) return; el.classList.add("out");
  setTimeout(() => el?.remove(), 250);
}

// ===== Poll new contacts (3s) =====
async function pollNewContacts() {
  try {
    const res  = await fetch(`${BASE}/api/contact_poll.php?last_id=${lastMax}&_=${Date.now()}`);
    const data = await res.json();
    if (data.success && data.contacts?.length) {
      const fresh = data.contacts.filter(c => !knownIds.has(c.id));
      if (fresh.length) {
        fresh.forEach(c => { knownIds.add(c.id); if (c.id > lastMax) lastMax = c.id; });
        prependItems(fresh);
        const cnt = document.getElementById("new-count");
        const cntT= document.getElementById("new-count-tab");
        cnt.textContent = +cnt.textContent + fresh.length;
        cntT.textContent = cnt.textContent;
        showToast(fresh[0]);
        if (Notification.permission === "granted") {
          const n = fresh[0].user_full_name || fresh[0].guest_name || "Khách";
          new Notification("📩 Liên hệ mới!", {
            body: n + ": " + (fresh[0].subject || fresh[0].message||"").substring(0,60),
            icon: "/favicon.ico"
          });
        }
      }
    }
    document.getElementById("last-update").textContent = "Cập nhật: " + new Date().toLocaleTimeString("vi-VN");
  } catch {}
  setTimeout(pollNewContacts, 3000);
}

function prependItems(contacts) {
  const body = document.getElementById("contact-list-body");
  contacts.forEach(c => {
    const name = c.user_full_name || c.guest_name || "Khách ẩn danh";
    const ts   = Math.floor(new Date(c.created_at).getTime()/1000);
    const div  = document.createElement("div");
    div.className = "contact-item status-new";
    div.dataset.id = c.id; div.dataset.ts = ts;
    div.dataset.name = name.toLowerCase();
    div.onclick = () => openContact(c.id);
    div.innerHTML = `
      <div class="item-name">${name}</div>
      <div class="item-subj">${c.subject||(c.message||"").substring(0,55)}</div>
      <div class="item-footer">
        <span class="item-time">${new Date(c.created_at).toLocaleString("vi-VN")}</span>
        <span class="item-reltime" data-ts="${ts}">${rel(ts)}</span>
      </div>`;
    body.prepend(div);
  });
}

if (Notification.permission === "default") Notification.requestPermission();
pollNewContacts();
</script>

<?php include __DIR__ . "/../includes/footer.php"; ?>
