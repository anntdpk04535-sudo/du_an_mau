<?php
require_once __DIR__ . '/../includes/functions.php';
$pageTitle = 'Đánh giá dịch vụ - Đắk Lắk Travel AI';
$user = currentUser();
include __DIR__ . '/../includes/header.php';
?>

<style>
/* ── Review Page Styles ── */
.review-hero {
  background: linear-gradient(135deg, #1b4332 0%, #2d6a4f 50%, #40916c 100%);
  border-radius: 20px;
  padding: 52px 40px;
  text-align: center;
  color: white;
  margin: 24px 0 36px;
  position: relative;
  overflow: hidden;
}
.review-hero::before {
  content: '';
  position: absolute;
  top: -60px; right: -60px;
  width: 240px; height: 240px;
  border-radius: 50%;
  background: rgba(255,255,255,.06);
}
.review-hero::after {
  content: '';
  position: absolute;
  bottom: -80px; left: -40px;
  width: 200px; height: 200px;
  border-radius: 50%;
  background: rgba(255,255,255,.04);
}
.review-hero h1 { font-size: 32px; margin: 0 0 10px; }
.review-hero p  { font-size: 16px; opacity: .88; margin: 0; }

.review-summary-bar {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 32px;
  flex-wrap: wrap;
  background: white;
  border-radius: 16px;
  padding: 28px 32px;
  box-shadow: 0 4px 20px rgba(0,0,0,.07);
  margin-bottom: 32px;
}
.review-big-score {
  text-align: center;
}
.review-big-score .score-num {
  font-size: 64px;
  font-weight: 800;
  color: var(--green-900);
  line-height: 1;
}
.review-big-score .score-label {
  font-size: 13px;
  color: #888;
  margin-top: 4px;
}
.review-dist {
  flex: 1;
  min-width: 200px;
  max-width: 340px;
}
.dist-row {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 7px;
  font-size: 13px;
}
.dist-label { width: 44px; flex-shrink: 0; color: #555; text-align: right; }
.dist-bar-wrap { flex: 1; background: #f0f0f0; border-radius: 20px; height: 10px; }
.dist-bar { height: 10px; border-radius: 20px; background: #f59e0b; transition: width .6s ease; }
.dist-count { width: 28px; color: #888; font-size: 12px; }

/* Star Rating Input */
.star-rating-input {
  display: flex;
  flex-direction: row-reverse;
  justify-content: flex-end;
  gap: 4px;
  margin: 8px 0 16px;
}
.star-rating-input input { display: none; }
.star-rating-input label {
  font-size: 34px;
  color: #ddd;
  cursor: pointer;
  transition: color .15s, transform .1s;
  line-height: 1;
}
.star-rating-input label:hover,
.star-rating-input label:hover ~ label,
.star-rating-input input:checked ~ label {
  color: #f59e0b;
  transform: scale(1.1);
}

/* Review Cards */
.reviews-list { display: flex; flex-direction: column; gap: 16px; margin-top: 24px; }
.review-card {
  background: white;
  border-radius: 14px;
  padding: 20px 24px;
  box-shadow: 0 2px 12px rgba(0,0,0,.06);
  border-left: 4px solid var(--green-500);
  animation: fadeSlideUp .4s ease both;
}
@keyframes fadeSlideUp {
  from { opacity: 0; transform: translateY(14px); }
  to   { opacity: 1; transform: translateY(0); }
}
.review-card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 10px;
  flex-wrap: wrap;
  gap: 8px;
}
.reviewer-info {
  display: flex;
  align-items: center;
  gap: 10px;
}
.reviewer-avatar {
  width: 40px; height: 40px;
  border-radius: 50%;
  background: linear-gradient(135deg, var(--green-700), var(--orange-500));
  display: flex; align-items: center; justify-content: center;
  color: white; font-weight: 700; font-size: 16px;
  flex-shrink: 0;
}
.reviewer-name { font-weight: 700; font-size: 15px; color: var(--text-dark); }
.reviewer-date { font-size: 12px; color: #aaa; margin-top: 2px; }
.star-display { color: #f59e0b; font-size: 17px; letter-spacing: 1px; }
.review-comment { color: #444; font-size: 14px; line-height: 1.65; margin: 0; }
.review-empty {
  text-align: center;
  padding: 48px 20px;
  color: #aaa;
  font-size: 15px;
}
.review-empty .review-empty-icon { font-size: 48px; margin-bottom: 12px; }

/* Submit box */
.review-form-box {
  background: white;
  border-radius: 16px;
  padding: 28px 32px;
  box-shadow: 0 4px 20px rgba(0,0,0,.07);
  margin-bottom: 32px;
}
.review-form-box h2 { margin: 0 0 6px; color: var(--green-900); font-size: 20px; }
.review-form-box .sub { color: #888; font-size: 13px; margin-bottom: 20px; }

.load-more-btn {
  display: block;
  width: 100%;
  padding: 12px;
  border: 2px dashed #ddd;
  border-radius: 12px;
  background: transparent;
  color: #888;
  font-size: 14px;
  cursor: pointer;
  margin-top: 16px;
  transition: border-color .2s, color .2s;
}
.load-more-btn:hover { border-color: var(--green-500); color: var(--green-700); }
.section-tabs {
  display: flex; gap: 0; margin-bottom: 28px;
  border-bottom: 2px solid #e8e8e8;
}
.section-tab {
  padding: 10px 22px;
  font-size: 15px;
  font-weight: 600;
  color: #888;
  cursor: pointer;
  border-bottom: 3px solid transparent;
  margin-bottom: -2px;
  transition: color .15s, border-color .15s;
  background: none; border-top: none; border-left: none; border-right: none;
}
.section-tab.active {
  color: var(--green-700);
  border-bottom-color: var(--green-700);
}
.tab-content { display: none; }
.tab-content.active { display: block; }
.review-card-actions {
  display:flex; gap:8px; margin-top:10px;
}
.btn-edit-review, .btn-del-review, .btn-admin-del-review {
  display:inline-flex; align-items:center; gap:4px;
  padding:5px 12px; border-radius:8px; font-size:12px; font-weight:700;
  border:none; cursor:pointer; transition:all .15s;
}
.btn-edit-review { background:#fef3c7; color:#92400e; }
.btn-edit-review:hover { background:#fde68a; }
.btn-del-review  { background:#fee2e2; color:#991b1b; }
.btn-del-review:hover  { background:#fca5a5; }
.btn-admin-del-review { background:linear-gradient(135deg,#fee2e2,#fecaca); color:#991b1b; border:1px solid #fca5a5; }
.btn-admin-del-review:hover { background:#fca5a5; }
.admin-badge-sm { background:#fef3c7; color:#92400e; font-size:10px; padding:1px 6px; border-radius:6px; font-weight:700; }

/* Edit modal */
.review-modal-overlay {
  position:fixed; inset:0; background:rgba(0,0,0,.5);
  z-index:9999;
  display:flex;
  align-items:center; justify-content:center;
  opacity:0; visibility:hidden; pointer-events:none;
  transition:opacity .2s, visibility .2s;
}
.review-modal-overlay.open {
  opacity:1; visibility:visible; pointer-events:auto;
}
.review-modal {
  background:white; border-radius:18px; padding:28px 32px;
  width:100%; max-width:480px; box-shadow:0 20px 60px rgba(0,0,0,.25);
  animation:modalIn .25s ease;
}
@keyframes modalIn { from{opacity:0;transform:translateY(-20px)} to{opacity:1;transform:translateY(0)} }
.review-modal h3 { margin:0 0 6px; color:var(--green-900); }
.review-modal .sub { color:#888; font-size:13px; margin-bottom:18px; }
.modal-footer { display:flex; gap:10px; justify-content:flex-end; margin-top:16px; }
.btn-cancel { background:#f3f4f6; color:#374151; border:none; border-radius:8px; padding:9px 18px; font-size:14px; font-weight:600; cursor:pointer; }
.btn-cancel:hover { background:#e5e7eb; }

/* Admin delete reason modal */
.admin-del-modal-overlay {
  position:fixed; inset:0; background:rgba(0,0,0,.55);
  z-index:10000; display:flex; align-items:center; justify-content:center;
  opacity:0; visibility:hidden; pointer-events:none;
  transition:opacity .2s, visibility .2s;
}
.admin-del-modal-overlay.open { opacity:1; visibility:visible; pointer-events:auto; }
.admin-del-modal {
  background:white; border-radius:18px; padding:28px 32px;
  width:100%; max-width:480px; box-shadow:0 20px 60px rgba(0,0,0,.3);
  animation:adminDelIn .22s ease;
}
@keyframes adminDelIn { from{opacity:0;transform:translateY(-16px) scale(.97)} to{opacity:1;transform:translateY(0) scale(1)} }
.admin-del-modal h3 { margin:0 0 4px; color:#991b1b; font-size:18px; display:flex; align-items:center; gap:8px; }
.admin-del-modal .sub { color:#888; font-size:13px; margin-bottom:16px; }
.admin-del-modal .sub strong { color:#111; }
.admin-del-modal .reason-presets { display:flex; flex-wrap:wrap; gap:6px; margin-bottom:12px; }
.reason-preset-btn {
  background:#f3f4f6; border:1.5px solid #e5e7eb; border-radius:8px;
  padding:5px 12px; font-size:12px; font-weight:600; color:#555;
  cursor:pointer; transition:all .15s;
}
.reason-preset-btn:hover { border-color:#ef4444; color:#dc2626; background:#fef2f2; }
.admin-del-modal textarea {
  width:100%; border:1.5px solid #e5e7eb; border-radius:10px;
  padding:10px 12px; font-size:14px; font-family:inherit;
  resize:vertical; min-height:80px; box-sizing:border-box;
  transition:border-color .2s;
}
.admin-del-modal textarea:focus { outline:none; border-color:#ef4444; }
.admin-del-footer { display:flex; gap:10px; justify-content:flex-end; margin-top:14px; }
.admin-del-cancel { background:#f3f4f6; color:#374151; border:none; border-radius:8px; padding:9px 18px; font-size:14px; font-weight:600; cursor:pointer; transition:background .15s; }
.admin-del-cancel:hover { background:#e5e7eb; }
.admin-del-confirm {
  background:linear-gradient(135deg,#dc2626,#b91c1c); color:white;
  border:none; border-radius:8px; padding:9px 18px; font-size:14px; font-weight:700; cursor:pointer;
  transition:opacity .15s;
}
.admin-del-confirm:disabled { opacity:.55; cursor:not-allowed; }
</style>

<div class="review-hero">
  <h1>⭐ Đánh giá dịch vụ</h1>
  <p>Chia sẻ trải nghiệm của bạn về website Du lịch Đắk Lắk AI</p>
</div>

<!-- Summary Bar -->
<div class="review-summary-bar" id="summaryBar">
  <div class="review-big-score">
    <div class="score-num" id="avgScore">—</div>
    <div class="star-display" id="avgStars">☆☆☆☆☆</div>
    <div class="score-label">Đánh giá trung bình (<span id="totalCount">0</span> lượt)</div>
  </div>
  <div class="review-dist" id="distChart">
    <?php foreach ([5,4,3,2,1] as $s): ?>
    <div class="dist-row" id="dist-row-<?= $s ?>">
      <span class="dist-label"><?= $s ?>⭐</span>
      <div class="dist-bar-wrap"><div class="dist-bar" id="dist-bar-<?= $s ?>" style="width:0%"></div></div>
      <span class="dist-count" id="dist-cnt-<?= $s ?>">0</span>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- Tabs -->
<div class="section-tabs">
  <button class="section-tab active" onclick="switchTab('list')">📋 Danh sách đánh giá</button>
  <button class="section-tab" onclick="switchTab('write')" id="writeTabBtn">✏️ Viết đánh giá</button>
</div>

<!-- Tab: Danh sách -->
<div class="tab-content active" id="tab-list">
  <div class="reviews-list" id="reviewsList">
    <div class="review-empty"><div class="review-empty-icon">⏳</div>Đang tải đánh giá...</div>
  </div>
  <button class="load-more-btn" id="loadMoreBtn" style="display:none" onclick="loadMore()">Xem thêm đánh giá...</button>
</div>

<!-- Tab: Viết đánh giá -->
<div class="tab-content" id="tab-write">
  <?php if ($user): ?>
  <div class="review-form-box">
    <h2>✍️ Chia sẻ trải nghiệm của bạn</h2>
    <p class="sub">Bạn đang đánh giá <strong>dịch vụ website Đắk Lắk Travel AI</strong> (không phải điểm đến cụ thể)</p>
    <form id="reviewForm">
      <div class="form-group">
        <label>Số sao đánh giá <span style="color:red">*</span></label>
        <div class="star-rating-input">
          <?php for ($i = 5; $i >= 1; $i--): ?>
          <input type="radio" id="star<?= $i ?>" name="rating" value="<?= $i ?>" <?= $i === 5 ? 'checked' : '' ?>>
          <label for="star<?= $i ?>" title="<?= $i ?> sao">★</label>
          <?php endfor; ?>
        </div>
      </div>
      <div class="form-group">
        <label for="reviewComment">Nhận xét của bạn</label>
        <textarea id="reviewComment" name="comment" rows="4"
          placeholder="Chia sẻ trải nghiệm, góp ý về tính năng, AI chatbot, giao diện... (không bắt buộc)"
          style="resize:vertical; font-family:inherit;"></textarea>
        <div style="text-align:right; font-size:12px; color:#aaa; margin-top:4px;">
          <span id="charCount">0</span>/1000 ký tự
        </div>
      </div>
      <button type="submit" class="btn" id="submitReviewBtn">🚀 Gửi đánh giá</button>
      <div id="reviewMsg" style="margin-top:14px;font-size:14px;"></div>
    </form>
  </div>
  <?php else: ?>
  <div class="form-box" style="text-align:center; padding:48px 24px;">
    <div style="font-size:48px; margin-bottom:16px;">🔐</div>
    <h3 style="margin-bottom:8px;">Bạn cần đăng nhập để đánh giá</h3>
    <p style="color:#888; margin-bottom:24px;">Hãy đăng nhập để chia sẻ trải nghiệm của bạn về dịch vụ.</p>
    <div style="display:flex; gap:12px; justify-content:center; flex-wrap:wrap;">
      <a href="<?= url('/public/login.php') ?>" class="btn">Đăng nhập</a>
      <a href="<?= url('/public/register.php') ?>" class="btn secondary">Đăng ký tài khoản</a>
    </div>
  </div>
  <?php endif; ?>
</div>

<!-- Edit Review Modal -->
<div class="review-modal-overlay" id="editModalOverlay">
  <div class="review-modal" id="editModalBox">
    <h3>✏️ Chỉnh sửa đánh giá</h3>
    <p class="sub">Bạn có thể thay đổi số sao và nội dung nhận xét.</p>
    <form id="editReviewForm">
      <input type="hidden" id="editReviewId" name="id" value="">
      <div class="form-group">
        <label>Số sao <span style="color:red">*</span></label>
        <div class="star-rating-input" id="editStarInput">
          <?php for ($i = 5; $i >= 1; $i--): ?>
          <input type="radio" id="estr<?= $i ?>" name="rating" value="<?= $i ?>">
          <label for="estr<?= $i ?>" title="<?= $i ?> sao">★</label>
          <?php endfor; ?>
        </div>
      </div>
      <div class="form-group">
        <label for="editComment">Nhận xét</label>
        <textarea id="editComment" name="comment" rows="4"
          placeholder="Chia sẻ trải nghiệm của bạn..."
          style="resize:vertical;font-family:inherit;"></textarea>
        <div style="text-align:right;font-size:12px;color:#aaa;margin-top:4px;">
          <span id="editCharCount">0</span>/1000 ký tự
        </div>
      </div>
      <div id="editModalMsg" style="margin-bottom:10px;font-size:14px;"></div>
      <div class="modal-footer">
        <button type="button" class="btn-cancel" id="editCancelBtn">Hủy</button>
        <button type="submit" class="btn" id="editSubmitBtn">💾 Lưu thay đổi</button>
      </div>
    </form>
  </div>
</div>

<!-- Admin Delete Reason Modal -->
<div class="admin-del-modal-overlay" id="adminDelOverlay">
  <div class="admin-del-modal">
    <h3>🛡️ Xóa đánh giá (Admin)</h3>
    <p class="sub">Bạn đang xóa đánh giá của <strong id="adminDelTargetName"></strong>. Vui lòng nhập lý do xóa — lý do sẽ được lưu vào lịch sử.</p>
    <div class="reason-presets">
      <button type="button" class="reason-preset-btn" onclick="setDelReason('Nội dung vi phạm quy định cộng đồng')">🚫 Vi phạm quy định</button>
      <button type="button" class="reason-preset-btn" onclick="setDelReason('Nội dung spam hoặc quảng cáo')">📢 Spam/Quảng cáo</button>
      <button type="button" class="reason-preset-btn" onclick="setDelReason('Nội dung không phù hợp hoặc xúc phạm')">⚠️ Không phù hợp</button>
      <button type="button" class="reason-preset-btn" onclick="setDelReason('Đánh giá sai sự thật, gây hiểu lầm')">❌ Sai sự thật</button>
      <button type="button" class="reason-preset-btn" onclick="setDelReason('Trùng lặp, đánh giá nhiều lần')">🔁 Trùng lặp</button>
    </div>
    <textarea id="adminDelReason" placeholder="VD: Nội dung vi phạm quy định, spam, không phù hợp..."></textarea>
    <div id="adminDelErr" style="color:#dc2626;font-size:13px;margin-top:8px;min-height:18px;"></div>
    <div class="admin-del-footer">
      <button class="admin-del-cancel" id="adminDelCancelBtn">Hủy</button>
      <button class="admin-del-confirm" id="adminDelConfirmBtn">🗑️ Xác nhận xóa</button>
    </div>
  </div>
</div>

<script>
const API_BASE = '<?= url('/api') ?>';
let offset = 0;
const limit = 10;
let totalLoaded = 0;
let grandTotal  = 0;
let _isAdmin = false;
let _adminDelReviewId = null;

function switchTab(name) {
  document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
  document.querySelectorAll('.section-tab').forEach(t => t.classList.remove('active'));
  document.getElementById('tab-' + name).classList.add('active');
  event.currentTarget.classList.add('active');
}

function starsHtml(rating, size = '17px') {
  let s = '';
  for (let i = 1; i <= 5; i++) s += i <= rating ? '★' : '☆';
  return `<span class="star-display" style="font-size:${size}">${s}</span>`;
}

function avatarLetter(name) {
  return name ? name.charAt(0).toUpperCase() : '?';
}

function timeSince(dateStr) {
  const d = new Date(dateStr);
  const diff = (Date.now() - d.getTime()) / 1000;
  if (diff < 60)   return 'vừa xong';
  if (diff < 3600) return Math.floor(diff/60) + ' phút trước';
  if (diff < 86400) return Math.floor(diff/3600) + ' giờ trước';
  return Math.floor(diff/86400) + ' ngày trước';
}

async function loadReviews(reset = false) {
  if (reset) { offset = 0; totalLoaded = 0; document.getElementById('reviewsList').innerHTML = ''; }

  const res  = await fetch(`${API_BASE}/review_get.php?limit=${limit}&offset=${offset}`);
  const data = await res.json();
  if (!data.success) return;

  _isAdmin   = !!data.is_admin;
  grandTotal = data.total;
  const list = document.getElementById('reviewsList');

  // Summary bar
  if (data.avg_rating !== null) {
    document.getElementById('avgScore').textContent = data.avg_rating.toFixed(1);
    document.getElementById('avgStars').innerHTML  = starsHtml(Math.round(data.avg_rating), '22px');
  }
  document.getElementById('totalCount').textContent = data.total;

  if (data.reviews.length === 0 && offset === 0) {
    list.innerHTML = `<div class="review-empty"><div class="review-empty-icon">💬</div>Chưa có đánh giá nào. Hãy là người đầu tiên!</div>`;
    document.getElementById('loadMoreBtn').style.display = 'none';
    return;
  }

  data.reviews.forEach((r, idx) => {
    const card = document.createElement('div');
    card.className = 'review-card';
    card.style.animationDelay = (idx * 0.05) + 's';

    // Dùng DOM trực tiếp thay vì innerHTML để tránh lỗi ký tự đặc biệt
    const headerDiv = document.createElement('div');
    headerDiv.className = 'review-card-header';
    headerDiv.innerHTML = `
      <div class="reviewer-info">
        <div class="reviewer-avatar">${avatarLetter(r.display_name)}</div>
        <div>
          <div class="reviewer-name">${r.display_name}${r.is_mine ? ' <span style="background:#d8f3dc;color:#1b4332;font-size:10px;padding:1px 6px;border-radius:6px;font-weight:700;">Của bạn</span>' : ''}</div>
          <div class="reviewer-date">${timeSince(r.created_at)}</div>
        </div>
      </div>
      ${starsHtml(r.rating)}
    `;
    card.appendChild(headerDiv);

    const commentP = document.createElement('p');
    commentP.className = 'review-comment';
    if (r.comment) {
      commentP.textContent = '\u201c' + r.comment + '\u201d';
    } else {
      commentP.style.cssText = 'color:#bbb;font-style:italic;';
      commentP.textContent = 'Không có nhận xét.';
    }
    card.appendChild(commentP);

    // Show actions: own review (edit+delete) or admin delete with reason
    const showOwnerActions = r.is_mine;
    const showAdminDel     = _isAdmin && !r.is_mine;

    if (showOwnerActions || showAdminDel) {
      const actionsDiv = document.createElement('div');
      actionsDiv.className = 'review-card-actions';

      if (showOwnerActions) {
        const editBtn = document.createElement('button');
        editBtn.className = 'btn-edit-review';
        editBtn.textContent = '✏️ Sửa';
        editBtn.addEventListener('click', function() {
          openEditModal(r.review_id, r.rating, r.comment || '');
        });

        const delBtn = document.createElement('button');
        delBtn.className = 'btn-del-review';
        delBtn.textContent = '🗑️ Xóa';
        delBtn.addEventListener('click', function() {
          deleteReview(r.review_id, this);
        });

        actionsDiv.appendChild(editBtn);
        actionsDiv.appendChild(delBtn);
      }

      if (showAdminDel) {
        const adminDelBtn = document.createElement('button');
        adminDelBtn.className = 'btn-admin-del-review';
        adminDelBtn.innerHTML = '🛡️ Xóa <span class="admin-badge-sm">Admin</span>';
        adminDelBtn.addEventListener('click', function() {
          openAdminDelModal(r.review_id, r.display_name);
        });
        actionsDiv.appendChild(adminDelBtn);
      }

      card.appendChild(actionsDiv);
    }

    list.appendChild(card);
  });

  offset += data.reviews.length;
  totalLoaded += data.reviews.length;
  document.getElementById('loadMoreBtn').style.display = totalLoaded < grandTotal ? 'block' : 'none';
}

async function loadDistribution() {
  const res  = await fetch(`${API_BASE}/review_get.php?limit=200`);
  const data = await res.json();
  if (!data.success || !data.reviews.length) return;

  const counts = {1:0, 2:0, 3:0, 4:0, 5:0};
  data.reviews.forEach(r => counts[r.rating] = (counts[r.rating] || 0) + 1);
  const max = Math.max(...Object.values(counts)) || 1;
  [1,2,3,4,5].forEach(s => {
    document.getElementById(`dist-bar-${s}`).style.width = ((counts[s] / max) * 100) + '%';
    document.getElementById(`dist-cnt-${s}`).textContent = counts[s];
  });
}

function loadMore() { loadReviews(false); }

// Submit form
const form = document.getElementById('reviewForm');
if (form) {
  const textarea = document.getElementById('reviewComment');
  textarea?.addEventListener('input', () => {
    document.getElementById('charCount').textContent = textarea.value.length;
  });

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = document.getElementById('submitReviewBtn');
    const msg = document.getElementById('reviewMsg');
    btn.disabled = true;
    btn.textContent = '⏳ Đang gửi...';

    const fd = new FormData(form);
    const res  = await fetch(`${API_BASE}/review_submit.php`, { method: 'POST', body: fd });
    const data = await res.json();

    if (data.success) {
      msg.innerHTML = `<div style="background:#d8f3dc;color:#1b4332;padding:12px 16px;border-radius:10px;">✅ ${data.message}</div>`;
      form.reset();
      document.getElementById('charCount').textContent = '0';
      // Reset sao về 5
      const star5 = document.querySelector('#reviewForm input[name="rating"][value="5"]');
      if (star5) star5.checked = true;
      // Reload danh sách sau 1.5s, reset button
      setTimeout(() => {
        msg.innerHTML = '';
        btn.disabled = false;
        btn.textContent = '🚀 Gửi đánh giá';
        loadReviews(true);
        loadDistribution();
        // Switch to list tab
        document.querySelectorAll('.section-tab')[0].click();
      }, 1500);
    } else {
      msg.innerHTML = `<div style="background:#fee2e2;color:#991b1b;padding:12px 16px;border-radius:10px;">❌ ${data.error}</div>`;
      btn.disabled = false;
      btn.textContent = '🚀 Gửi đánh giá';
    }
  });
}

// ── Edit modal ──
function openEditModal(reviewId, currentRating, currentComment) {
  document.getElementById('editReviewId').value = reviewId;
  document.getElementById('editComment').value = currentComment || '';
  document.getElementById('editCharCount').textContent = (currentComment || '').length;
  const radio = document.querySelector('#editStarInput input[value="' + currentRating + '"]');
  if (radio) radio.checked = true;
  document.getElementById('editModalMsg').innerHTML = '';
  document.getElementById('editSubmitBtn').disabled = false;
  document.getElementById('editSubmitBtn').textContent = '💾 Lưu thay đổi';
  document.getElementById('editModalOverlay').classList.add('open');
  document.body.style.overflow = 'hidden';
}

function closeEditModal() {
  document.getElementById('editModalOverlay').classList.remove('open');
  document.body.style.overflow = '';
}

// Click overlay để đóng (click bên trong modal không đóng)
document.getElementById('editModalOverlay').addEventListener('click', function(e) {
  if (e.target === this) closeEditModal();
});

// Nút Hủy
document.getElementById('editCancelBtn').addEventListener('click', closeEditModal);

// Đếm ký tự
document.getElementById('editComment').addEventListener('input', function() {
  document.getElementById('editCharCount').textContent = this.value.length;
});

// Submit sửa đánh giá
document.getElementById('editReviewForm').addEventListener('submit', async function(e) {
  e.preventDefault();
  const btn = document.getElementById('editSubmitBtn');
  const msg = document.getElementById('editModalMsg');
  btn.disabled = true;
  btn.textContent = '⏳ Đang lưu...';

  const fd = new FormData(this);
  try {
    const res  = await fetch(API_BASE + '/review_edit.php', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.success) {
      msg.innerHTML = '<div style="background:#d8f3dc;color:#1b4332;padding:10px 14px;border-radius:8px;">✅ ' + data.message + '</div>';
      setTimeout(function() {
        closeEditModal();
        loadReviews(true);
        loadDistribution();
      }, 800);
    } else {
      msg.innerHTML = '<div style="background:#fee2e2;color:#991b1b;padding:10px 14px;border-radius:8px;">❌ ' + (data.error || 'Lỗi không xác định') + '</div>';
      btn.disabled = false;
      btn.textContent = '💾 Lưu thay đổi';
    }
  } catch(err) {
    msg.innerHTML = '<div style="background:#fee2e2;color:#991b1b;padding:10px 14px;border-radius:8px;">❌ Lỗi kết nối mạng.</div>';
    btn.disabled = false;
    btn.textContent = '💾 Lưu thay đổi';
  }
});

// ── Delete review (user's own - simple confirm) ──
async function deleteReview(reviewId, buttonEl) {
  if (!reviewId) {
    console.error('deleteReview: reviewId is invalid', reviewId);
    return;
  }
  if (!confirm('Bạn có chắc muốn xóa đánh giá này không?')) return;

  if (buttonEl) {
    buttonEl.disabled = true;
    buttonEl.textContent = '⏳';
  }

  const fd = new FormData();
  fd.append('id', reviewId);
  try {
    const res  = await fetch(API_BASE + '/review_delete.php', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.success) {
      loadReviews(true);
      loadDistribution();
    } else {
      if (buttonEl) { buttonEl.disabled = false; buttonEl.textContent = '🗑️ Xóa'; }
      // Hiện lỗi bên cạnh nút thay vì alert
      const errSpan = document.createElement('span');
      errSpan.style.cssText = 'color:#dc2626;font-size:12px;margin-left:8px;';
      errSpan.textContent = data.error || 'Không thể xóa.';
      if (buttonEl && buttonEl.parentNode) {
        buttonEl.parentNode.appendChild(errSpan);
        setTimeout(() => errSpan.remove(), 4000);
      }
    }
  } catch(err) {
    if (buttonEl) { buttonEl.disabled = false; buttonEl.textContent = '🗑️ Xóa'; }
    console.error('deleteReview error:', err);
  }
}

// ── Admin delete with reason modal ──
function openAdminDelModal(reviewId, userName) {
  _adminDelReviewId = reviewId;
  document.getElementById('adminDelTargetName').textContent = userName;
  document.getElementById('adminDelReason').value = '';
  document.getElementById('adminDelErr').textContent = '';
  document.getElementById('adminDelConfirmBtn').disabled = false;
  document.getElementById('adminDelConfirmBtn').textContent = '🗑️ Xác nhận xóa';
  document.getElementById('adminDelOverlay').classList.add('open');
  document.body.style.overflow = 'hidden';
  setTimeout(() => document.getElementById('adminDelReason').focus(), 100);
}

function closeAdminDelModal() {
  document.getElementById('adminDelOverlay').classList.remove('open');
  document.body.style.overflow = '';
  _adminDelReviewId = null;
}

function setDelReason(text) {
  document.getElementById('adminDelReason').value = text;
  document.getElementById('adminDelErr').textContent = '';
  document.getElementById('adminDelReason').focus();
}

document.getElementById('adminDelOverlay').addEventListener('click', function(e) {
  if (e.target === this) closeAdminDelModal();
});
document.getElementById('adminDelCancelBtn').addEventListener('click', closeAdminDelModal);

document.getElementById('adminDelConfirmBtn').addEventListener('click', async function() {
  const reason = document.getElementById('adminDelReason').value.trim();
  const errEl  = document.getElementById('adminDelErr');

  if (!reason) {
    errEl.textContent = '⚠️ Vui lòng nhập lý do xóa.';
    document.getElementById('adminDelReason').focus();
    return;
  }
  if (reason.length < 10) {
    errEl.textContent = '⚠️ Lý do phải có ít nhất 10 ký tự.';
    return;
  }

  this.disabled = true;
  this.textContent = '⏳ Đang xóa...';
  errEl.textContent = '';

  const fd = new FormData();
  fd.append('id', _adminDelReviewId);
  fd.append('reason', reason);

  try {
    const res  = await fetch(API_BASE + '/review_delete.php', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.success) {
      closeAdminDelModal();
      loadReviews(true);
      loadDistribution();
    } else {
      errEl.textContent = '❌ ' + (data.error || 'Lỗi không xác định');
      this.disabled = false;
      this.textContent = '🗑️ Xác nhận xóa';
    }
  } catch(e) {
    errEl.textContent = '❌ Lỗi kết nối mạng.';
    this.disabled = false;
    this.textContent = '🗑️ Xác nhận xóa';
  }
});

// Ctrl+Enter để xác nhận nhanh
document.getElementById('adminDelReason').addEventListener('keydown', function(e) {
  if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) {
    document.getElementById('adminDelConfirmBtn').click();
  }
});

// Init
loadReviews(true);
loadDistribution();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
