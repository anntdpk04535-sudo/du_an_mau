<?php
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();
$pageTitle = __('admin_reviews_title');
$db = getDB();

// Lấy lịch sử xóa gần đây (nếu bảng tồn tại)
$deletionLogs = [];
try {
    $deletionLogs = $db->query("
        SELECT * FROM review_deletion_logs
        ORDER BY deleted_at DESC LIMIT 50
    ")->fetchAll();
} catch (Exception $e) { /* bảng chưa tồn tại */ }

// Lọc theo loại
$filter = $_GET['filter'] ?? 'all'; // all | website | destination
$search = trim($_GET['q'] ?? '');

$whereClause = "WHERE 1=1";
$params = [];

if ($filter === 'website') {
    $whereClause .= " AND r.destination_id IS NULL";
} elseif ($filter === 'destination') {
    $whereClause .= " AND r.destination_id IS NOT NULL";
}
if ($search !== '') {
    $whereClause .= " AND (u.full_name LIKE ? OR r.comment LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$page = isset($_GET['page']) && (int)$_GET['page'] > 0 ? (int)$_GET['page'] : 1;
$limit = 4;
$offset = ($page - 1) * $limit;

$countStmt = $db->prepare("
    SELECT COUNT(*) 
    FROM reviews r
    LEFT JOIN users u ON r.user_id = u.id
    LEFT JOIN destinations d ON r.destination_id = d.id
    $whereClause
");
$countStmt->execute($params);
$totalReviews = $countStmt->fetchColumn();
$totalPages = ceil($totalReviews / $limit);

$stmt = $db->prepare("
    SELECT r.id, r.rating, r.comment, r.created_at, r.destination_id,
           u.full_name, u.email,
           d.name AS dest_name, d.slug AS dest_slug
    FROM reviews r
    LEFT JOIN users u ON r.user_id = u.id
    LEFT JOIN destinations d ON r.destination_id = d.id
    $whereClause
    ORDER BY r.created_at DESC
    LIMIT $limit OFFSET $offset
");
$stmt->execute($params);
$reviews = $stmt->fetchAll();

// Thống kê tổng quan
$stats = $db->query("
    SELECT
        COUNT(*) AS total,
        ROUND(AVG(rating),1) AS avg_all,
        SUM(CASE WHEN destination_id IS NULL THEN 1 ELSE 0 END) AS cnt_website,
        SUM(CASE WHEN destination_id IS NOT NULL THEN 1 ELSE 0 END) AS cnt_dest,
        SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) AS cnt5,
        SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) AS cnt4,
        SUM(CASE WHEN rating <= 3 THEN 1 ELSE 0 END) AS cnt_low
    FROM reviews
")->fetch();

include __DIR__ . '/../includes/header.php';
?>
<style>
/* ── Admin Reviews Styles ── */
.page-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:18px; flex-wrap:wrap; gap:10px; }
.page-header h1 { margin:0; }

/* Stats bar */
.stat-cards { display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:14px; margin-bottom:28px; }
.stat-card {
  background:white; border-radius:14px; padding:18px 20px;
  box-shadow:0 2px 12px rgba(0,0,0,.07);
  display:flex; flex-direction:column; align-items:flex-start; gap:4px;
}
.stat-card .stat-icon { font-size:26px; }
.stat-card .stat-num { font-size:28px; font-weight:800; color:var(--green-900); line-height:1; }
.stat-card .stat-label { font-size:12px; color:#888; }
.stat-card.highlight { background:linear-gradient(135deg,#1b4332,#2d6a4f); color:white; }
.stat-card.highlight .stat-num, .stat-card.highlight .stat-label { color:white; }

/* Filter toolbar */
.filter-bar {
  display:flex; gap:10px; flex-wrap:wrap; align-items:center; margin-bottom:18px;
  background:white; padding:14px 18px; border-radius:14px; box-shadow:0 2px 10px rgba(0,0,0,.06);
}
.filter-btn {
  padding:7px 16px; border-radius:8px; border:1.5px solid #e5e7eb;
  background:white; color:#555; font-size:13px; font-weight:600; cursor:pointer;
  transition:all .15s; text-decoration:none; display:inline-flex; align-items:center; gap:5px;
}
.filter-btn:hover { border-color:var(--green-500); color:var(--green-700); }
.filter-btn.active { background:var(--green-700); border-color:var(--green-700); color:white; }
.filter-search {
  flex:1; min-width:180px; border:1.5px solid #e5e7eb; border-radius:8px;
  padding:7px 12px; font-size:13px; font-family:inherit;
  transition:border-color .2s;
}
.filter-search:focus { outline:none; border-color:var(--green-500); }

/* Table */
.reviews-table-wrap {
  background:white; border-radius:14px; overflow:hidden;
  box-shadow:0 2px 12px rgba(0,0,0,.07);
}
.reviews-table { width:100%; border-collapse:collapse; }
.reviews-table thead tr { background:#1b4332; color:white; }
.reviews-table th { padding:11px 14px; font-size:12px; font-weight:700; text-align:left; letter-spacing:.4px; text-transform:uppercase; }
.reviews-table tbody tr { border-bottom:1px solid #f0f0f0; transition:background .12s; }
.reviews-table tbody tr:hover { background:#f0fdf4; }
.reviews-table td { padding:12px 14px; font-size:13px; vertical-align:top; }
.reviewer-cell { display:flex; align-items:center; gap:9px; }
.rev-avatar {
  width:34px; height:34px; border-radius:50%;
  background:linear-gradient(135deg,var(--green-700),var(--orange-500));
  color:white; font-weight:700; font-size:14px;
  display:flex; align-items:center; justify-content:center; flex-shrink:0;
}
.rev-name { font-weight:700; color:#111827; }
.rev-email { font-size:11px; color:#9ca3af; }
.stars-display { color:#f59e0b; letter-spacing:1px; font-size:16px; }
.rating-num { font-size:11px; color:#888; margin-top:2px; }
.badge-dest { background:#dbeafe; color:#1e40af; padding:2px 8px; border-radius:10px; font-size:11px; font-weight:700; }
.badge-web  { background:#d8f3dc; color:#1b4332; padding:2px 8px; border-radius:10px; font-size:11px; font-weight:700; }
.comment-cell { color:#444; line-height:1.5; max-width:280px; }
.comment-cell em { color:#bbb; font-style:italic; }
.time-cell { color:#aaa; font-size:12px; white-space:nowrap; }
.btn-del {
  display:inline-flex; align-items:center; gap:4px;
  background:#fee2e2; color:#991b1b; border:none; border-radius:7px;
  padding:5px 11px; font-size:12px; font-weight:700; cursor:pointer;
  transition:background .15s;
}
.btn-del:hover { background:#fca5a5; }
.empty-state { text-align:center; padding:48px; color:#aaa; }
.empty-state .icon { font-size:48px; margin-bottom:12px; }

/* Alert success */
.alert-success {
  background:#d8f3dc; color:#1b4332; padding:12px 18px; border-radius:10px;
  margin-bottom:16px; font-size:14px; font-weight:600;
  display:flex; align-items:center; gap:8px;
}

/* Delete reason modal */
.del-modal-overlay {
  position:fixed; inset:0; background:rgba(0,0,0,.6);
  backdrop-filter:blur(4px);
  z-index:9999; display:flex; align-items:center; justify-content:center;
  opacity:0; visibility:hidden; pointer-events:none;
  transition:opacity .25s, visibility .25s;
}
.del-modal-overlay.open { opacity:1; visibility:visible; pointer-events:auto; }
.del-modal {
  background:white; border-radius:20px; padding:0;
  width:100%; max-width:500px; box-shadow:0 24px 80px rgba(0,0,0,.35);
  animation:delModalIn .25s cubic-bezier(.34,1.56,.64,1);
  overflow:hidden;
}
@keyframes delModalIn { from{opacity:0;transform:scale(.92) translateY(-12px)} to{opacity:1;transform:scale(1) translateY(0)} }
/* Modal header */
.del-modal-header {
  background:linear-gradient(135deg,#7f1d1d,#dc2626);
  padding:20px 24px 16px;
  color:white;
}
.del-modal-header h3 { margin:0 0 4px; font-size:19px; font-weight:800; display:flex; align-items:center; gap:8px; }
.del-modal-header p { margin:0; font-size:13px; opacity:.85; }
/* Review preview strip */
.del-preview {
  background:#fff7ed; border-left:4px solid #f97316;
  margin:16px 24px 0; border-radius:10px;
  padding:12px 14px; display:flex; flex-direction:column; gap:4px;
}
.del-preview-name { font-weight:700; font-size:13px; color:#1b4332; }
.del-preview-stars { color:#f59e0b; font-size:15px; }
.del-preview-comment { font-size:12px; color:#6b7280; font-style:italic; line-height:1.45; }
/* Modal body */
.del-modal-body { padding:16px 24px 0; }
.del-modal-body label { font-size:13px; font-weight:700; color:#374151; display:block; margin-bottom:8px; }
/* Quick reason chips */
.quick-reasons { display:flex; flex-wrap:wrap; gap:6px; margin-bottom:10px; }
.quick-reason-chip {
  background:#fef2f2; color:#dc2626; border:1.5px solid #fecaca;
  border-radius:20px; padding:4px 12px; font-size:12px; font-weight:600;
  cursor:pointer; transition:all .15s; white-space:nowrap;
}
.quick-reason-chip:hover, .quick-reason-chip.selected {
  background:#dc2626; color:white; border-color:#dc2626;
}
.del-modal textarea {
  width:100%; border:1.5px solid #e5e7eb; border-radius:10px;
  padding:10px 12px; font-size:13.5px; font-family:inherit;
  resize:vertical; min-height:85px; box-sizing:border-box;
  transition:border-color .2s; color:#111827;
}
.del-modal textarea:focus { outline:none; border-color:#ef4444; box-shadow:0 0 0 3px rgba(239,68,68,.1); }
.del-char-count { text-align:right; font-size:11px; color:#9ca3af; margin-top:4px; }
.del-modal-footer {
  display:flex; gap:10px; justify-content:flex-end;
  padding:16px 24px 22px; margin-top:4px;
  border-top:1px solid #f3f4f6;
}
.del-modal-cancel {
  background:#f3f4f6; color:#374151; border:none; border-radius:10px;
  padding:10px 20px; font-size:14px; font-weight:600; cursor:pointer;
  transition:background .15s;
}
.del-modal-cancel:hover { background:#e5e7eb; }
.del-modal-confirm {
  background:linear-gradient(135deg,#dc2626,#b91c1c); color:white;
  border:none; border-radius:10px; padding:10px 20px; font-size:14px;
  font-weight:700; cursor:pointer; transition:opacity .15s;
  display:flex; align-items:center; gap:6px;
}
.del-modal-confirm:hover:not(:disabled) { opacity:.88; }
.del-modal-confirm:disabled { opacity:.5; cursor:not-allowed; }

/* Log table */
.log-table-wrap { background:white; border-radius:14px; overflow:hidden; box-shadow:0 2px 12px rgba(0,0,0,.07); margin-top:28px; }
.log-table { width:100%; border-collapse:collapse; }
.log-table thead tr { background:#7f1d1d; color:white; }
.log-table th { padding:10px 14px; font-size:12px; font-weight:700; text-align:left; text-transform:uppercase; letter-spacing:.4px; }
.log-table tbody tr { border-bottom:1px solid #f0f0f0; font-size:13px; }
.log-table tbody tr:hover { background:#fff7f7; }
.log-table td { padding:11px 14px; vertical-align:top; }
.reason-text { color:#374151; line-height:1.5; }
.reason-none { color:#bbb; font-style:italic; }

/* Responsive */
@media(max-width:768px) {
  .reviews-table th:nth-child(4), .reviews-table td:nth-child(4) { display:none; }
  .comment-cell { max-width:160px; }
}
@media(max-width:540px) {
  .reviews-table th:nth-child(5), .reviews-table td:nth-child(5) { display:none; }
}
</style>

<?php if (isset($_GET['deleted'])): ?>
<div class="alert-success"><?= __('admin_reviews_deleted_success') ?></div>
<?php endif; ?>

<h1 class="section-title"><?= __('admin_reviews_heading') ?></h1>
<?php include __DIR__ . '/nav.php'; ?>

<!-- Stats -->
<div class="stat-cards">
  <div class="stat-card highlight">
    <div class="stat-icon">⭐</div>
    <div class="stat-num"><?= $stats['total'] ?></div>
    <div class="stat-label"><?= __('admin_reviews_total') ?></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon">📊</div>
    <div class="stat-num"><?= $stats['avg_all'] ?? '—' ?></div>
    <div class="stat-label"><?= __('admin_reviews_avg') ?></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon">🌐</div>
    <div class="stat-num"><?= $stats['cnt_website'] ?></div>
    <div class="stat-label"><?= __('admin_reviews_web') ?></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon">🗺️</div>
    <div class="stat-num"><?= $stats['cnt_dest'] ?></div>
    <div class="stat-label"><?= __('admin_reviews_dest') ?></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon">😍</div>
    <div class="stat-num"><?= $stats['cnt5'] ?></div>
    <div class="stat-label"><?= __('admin_reviews_5star') ?></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon">⚠️</div>
    <div class="stat-num"><?= $stats['cnt_low'] ?></div>
    <div class="stat-label"><?= __('admin_reviews_low') ?></div>
  </div>
</div>

<!-- Filter bar -->
<form method="get" action="<?= url('/admin/reviews.php') ?>">
  <div class="filter-bar">
    <a href="<?= url('/admin/reviews.php') ?>?filter=all&q=<?= urlencode($search) ?>"
       class="filter-btn <?= $filter === 'all' ? 'active' : '' ?>"><?= __('admin_reviews_filter_all') ?></a>
    <a href="<?= url('/admin/reviews.php') ?>?filter=website&q=<?= urlencode($search) ?>"
       class="filter-btn <?= $filter === 'website' ? 'active' : '' ?>"><?= __('admin_reviews_filter_web') ?></a>
    <a href="<?= url('/admin/reviews.php') ?>?filter=destination&q=<?= urlencode($search) ?>"
       class="filter-btn <?= $filter === 'destination' ? 'active' : '' ?>"><?= __('admin_reviews_filter_dest') ?></a>
    <input type="hidden" name="filter" value="<?= e($filter) ?>">
    <input class="filter-search" type="text" name="q" value="<?= e($search) ?>"
           placeholder="<?= __('admin_reviews_search_ph') ?>">
    <button type="submit" class="filter-btn" style="background:var(--green-700);color:white;border-color:var(--green-700);"><?= __('admin_reviews_btn_search') ?></button>
    <?php if ($search): ?>
      <a href="<?= url('/admin/reviews.php') ?>?filter=<?= e($filter) ?>" class="filter-btn"><?= __('admin_reviews_btn_clear') ?></a>
    <?php endif; ?>
  </div>
</form>

<!-- Table -->
<div class="reviews-table-wrap">
  <?php if (empty($reviews)): ?>
    <div class="empty-state">
      <div class="icon">💬</div>
      <p><?= __('admin_reviews_no_data') ?><?= $search ? __('admin_reviews_match_search') : '' ?>.</p>
    </div>
  <?php else: ?>
  <table class="reviews-table">
    <thead>
      <tr>
        <th style="width:40px">#</th>
        <th><?= __('admin_reviews_th_user') ?></th>
        <th><?= __('admin_reviews_th_type') ?></th>
        <th><?= __('admin_reviews_th_rating') ?></th>
        <th><?= __('admin_reviews_th_comment') ?></th>
        <th><?= __('admin_reviews_th_time') ?></th>
        <th style="width:80px"><?= __('admin_reviews_th_action') ?></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($reviews as $i => $r):
        $name = $r['full_name'] ?? __('admin_reviews_anonymous');
        $initial = mb_strtoupper(mb_substr($name, 0, 1));
        $stars = str_repeat('★', (int)$r['rating']) . str_repeat('☆', 5 - (int)$r['rating']);
        $ts = strtotime($r['created_at']);
        $commentPreview = $r['comment'] ? mb_substr($r['comment'], 0, 80) . (mb_strlen($r['comment']) > 80 ? '…' : '') : '';
      ?>
      <tr id="row-<?= $r['id'] ?>">
        <td style="color:#bbb;font-size:12px;"><?= $i + 1 ?></td>
        <td>
          <div class="reviewer-cell">
            <div class="rev-avatar"><?= e($initial) ?></div>
            <div>
              <div class="rev-name"><?= e($name) ?></div>
              <?php if ($r['email']): ?>
                <div class="rev-email"><?= e($r['email']) ?></div>
              <?php endif; ?>
            </div>
          </div>
        </td>
        <td>
          <?php if ($r['destination_id']): ?>
            <span class="badge-dest">🗺️ <?= e($r['dest_name'] ?? 'Điểm đến') ?></span>
          <?php else: ?>
            <span class="badge-web"><?= __('admin_reviews_filter_web') ?></span>
          <?php endif; ?>
        </td>
        <td>
          <div class="stars-display"><?= $stars ?></div>
          <div class="rating-num"><?= (int)$r['rating'] ?><?= __('admin_reviews_rating_out_of') ?></div>
        </td>
        <td class="comment-cell">
          <?php if ($r['comment']): ?>
            "<?= e(mb_substr($r['comment'], 0, 120)) ?><?= mb_strlen($r['comment']) > 120 ? '…' : '' ?>"
          <?php else: ?>
            <em><?= __('admin_reviews_no_comment') ?></em>
          <?php endif; ?>
        </td>
        <td class="time-cell">
          <?= date('d/m/Y', $ts) ?><br>
          <span style="color:#d1d5db;"><?= date('H:i', $ts) ?></span>
        </td>
        <td>
          <button class="btn-del admin-del-btn"
            data-id="<?= $r['id'] ?>"
            data-name="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>"
            data-stars="<?= htmlspecialchars($stars, ENT_QUOTES, 'UTF-8') ?>"
            data-comment="<?= htmlspecialchars($commentPreview, ENT_QUOTES, 'UTF-8') ?>"
          >🗑️ <?= __('admin_reviews_btn_del') ?></button>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <div style="padding:12px 16px; font-size:12px; color:#aaa; border-top:1px solid #f0f0f0;">
    <?= __('admin_reviews_showing') ?><?= count($reviews) ?><?= __('admin_reviews_total_reviews') ?><?= $totalReviews ?>
  </div>
  <?php endif; ?>
</div>

<?php if (isset($totalPages) && $totalPages > 1): ?>
<div class="pagination" style="display: flex; justify-content: center; gap: 10px; margin-top: 20px;">
    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <a href="?filter=<?= urlencode($filter) ?>&q=<?= urlencode($search) ?>&page=<?= $i ?>" class="filter-btn <?= $i === $page ? 'active' : '' ?>" style="padding: 5px 12px; font-size: 14px;">
            <?= $i ?>
        </a>
    <?php endfor; ?>
</div>
<?php endif; ?>

<?php if (!empty($deletionLogs)): ?>
<div class="log-table-wrap">
  <table class="log-table">
    <thead>
      <tr>
        <th>#</th>
        <th><?= __('admin_reviews_th_deleted_review') ?></th>
        <th><?= __('admin_reviews_th_deleted_by_user') ?></th>
        <th><?= __('admin_reviews_th_deleted_reason') ?></th>
        <th><?= __('admin_reviews_th_deleted_by') ?></th>
        <th><?= __('admin_reviews_th_time') ?></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($deletionLogs as $i => $log): ?>
      <tr>
        <td style="color:#bbb;font-size:12px;"><?= $i + 1 ?></td>
        <td>
          <div style="color:#f59e0b;"><?= str_repeat('★', (int)$log['rating']) ?><?= str_repeat('☆', 5 - (int)$log['rating']) ?></div>
          <?php if ($log['comment']): ?>
            <div style="color:#444;font-size:12px;margin-top:3px;">"<?= e(mb_substr($log['comment'], 0, 80)) ?><?= mb_strlen($log['comment']) > 80 ? '…' : '' ?>"</div>
          <?php else: ?>
            <em style="color:#bbb;font-size:12px;"><?= __('admin_reviews_no_comment') ?></em>
          <?php endif; ?>
        </td>
        <td>
          <div style="font-weight:700;font-size:13px;"><?= e($log['reviewer_name'] ?? __('admin_reviews_anonymous')) ?></div>
          <div style="font-size:11px;color:#9ca3af;">ID: <?= (int)$log['reviewer_user_id'] ?></div>
        </td>
        <td>
          <?php if ($log['reason']): ?>
            <span class="reason-text">🔖 <?= e($log['reason']) ?></span>
          <?php else: ?>
            <span class="reason-none">—</span>
          <?php endif; ?>
        </td>
        <td style="font-weight:600;font-size:13px;color:#1b4332;"><?= e($log['deleted_by_name'] ?? 'Admin') ?></td>
        <td style="font-size:12px;color:#9ca3af;white-space:nowrap;">
          <?= date('d/m/Y H:i', strtotime($log['deleted_at'])) ?>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <div style="padding:10px 16px;font-size:12px;color:#aaa;border-top:1px solid #f0f0f0;">
    <?= count($deletionLogs) ?><?= __('admin_reviews_last_logs') ?>
  </div>
</div>
<?php endif; ?>

<!-- Delete with reason modal -->
<div class="del-modal-overlay" id="delModalOverlay">
  <div class="del-modal" id="delModalBox">
    <!-- Header -->
    <div class="del-modal-header">
      <h3>🗑️ <?= __('admin_reviews_modal_title') ?></h3>
      <p><?= __('admin_reviews_modal_desc') ?></p>
    </div>
    <!-- Review preview -->
    <div class="del-preview" id="delPreview">
      <div class="del-preview-name" id="delTargetName"></div>
      <div class="del-preview-stars" id="delPreviewStars"></div>
      <div class="del-preview-comment" id="delPreviewComment"></div>
    </div>
    <!-- Body -->
    <div class="del-modal-body">
      <label>💬 <?= __('admin_reviews_modal_reason') ?> <span style="color:#dc2626">*</span></label>
      <!-- Quick reason chips -->
      <div class="quick-reasons">
        <span class="quick-reason-chip" data-text="<?= __('admin_reviews_reason_violation') ?>"><?= __('admin_reviews_reason_violation') ?></span>
        <span class="quick-reason-chip" data-text="<?= __('admin_reviews_reason_spam') ?>"><?= __('admin_reviews_reason_spam') ?></span>
        <span class="quick-reason-chip" data-text="<?= __('admin_reviews_reason_offensive') ?>"><?= __('admin_reviews_reason_offensive') ?></span>
        <span class="quick-reason-chip" data-text="<?= __('admin_reviews_reason_fake') ?>"><?= __('admin_reviews_reason_fake') ?></span>
        <span class="quick-reason-chip" data-text="<?= __('admin_reviews_reason_irrelevant') ?>"><?= __('admin_reviews_reason_irrelevant') ?></span>
      </div>
      <textarea id="delReasonInput" placeholder="<?= __('admin_reviews_reason_ph') ?>"
                oninput="updateCharCount()"></textarea>
      <div class="del-char-count"><span id="delCharCount">0</span><?= __('admin_reviews_char_count') ?></div>
      <div id="delModalErr" style="color:#dc2626;font-size:13px;margin-top:6px;min-height:18px;"></div>
    </div>
    <div class="del-modal-footer">
      <button class="del-modal-cancel" id="delCancelBtn">↩ <?= __('admin_reviews_btn_cancel') ?></button>
      <button class="del-modal-confirm" id="delConfirmBtn">🗑️ <?= __('admin_reviews_btn_confirm') ?></button>
    </div>
  </div>
</div>

<script>
const API_BASE = '<?= url('/api') ?>';
let _delReviewId = null;

// Dùng event delegation — tránh dùng onclick inline
document.addEventListener('click', function(e) {
  const btn = e.target.closest('.admin-del-btn');
  if (!btn) return;
  openDelModal(
    btn.dataset.id,
    btn.dataset.name,
    btn.dataset.stars,
    btn.dataset.comment
  );
});

function openDelModal(reviewId, userName, stars, comment) {
  _delReviewId = reviewId;
  // Điền thông tin preview
  document.getElementById('delTargetName').textContent = '👤 ' + userName;
  document.getElementById('delPreviewStars').textContent = stars || '';
  const commentEl = document.getElementById('delPreviewComment');
  commentEl.textContent = comment ? '"' + comment + '"' : '';
  commentEl.style.display = comment ? '' : 'none';
  // Reset form
  document.getElementById('delReasonInput').value = '';
  document.getElementById('delCharCount').textContent = '0';
  document.getElementById('delCharCount').style.color = '#9ca3af';
  document.getElementById('delModalErr').textContent = '';
  document.getElementById('delConfirmBtn').disabled = false;
  document.getElementById('delConfirmBtn').innerHTML = '🗑️ ' + <?= json_encode(__('admin_reviews_btn_confirm')) ?>;
  // Bỏ chọn tất cả chip
  document.querySelectorAll('.quick-reason-chip').forEach(c => c.classList.remove('selected'));
  // Mở modal
  document.getElementById('delModalOverlay').classList.add('open');
  document.body.style.overflow = 'hidden';
  setTimeout(() => document.getElementById('delReasonInput').focus(), 150);
}

function closeDelModal() {
  document.getElementById('delModalOverlay').classList.remove('open');
  document.body.style.overflow = '';
  _delReviewId = null;
}

// Xử lý click chip gợi ý lý do
document.querySelectorAll('.quick-reason-chip').forEach(chip => {
  chip.addEventListener('click', function() {
    const text = this.dataset.text;
    const wasSelected = this.classList.contains('selected');
    document.querySelectorAll('.quick-reason-chip').forEach(c => c.classList.remove('selected'));
    if (!wasSelected) {
      this.classList.add('selected');
      document.getElementById('delReasonInput').value = text;
      document.getElementById('delModalErr').textContent = '';
    } else {
      document.getElementById('delReasonInput').value = '';
    }
    updateCharCount();
  });
});

function updateCharCount() {
  const len = document.getElementById('delReasonInput').value.trim().length;
  const el = document.getElementById('delCharCount');
  el.textContent = len;
  el.style.color = len === 0 ? '#9ca3af' : len < 10 ? '#ef4444' : '#22c55e';
}

// Bỏ chọn chip khi user tự nhập tay
document.getElementById('delReasonInput').addEventListener('input', function() {
  updateCharCount();
  const val = this.value;
  document.querySelectorAll('.quick-reason-chip').forEach(c => {
    if (c.classList.contains('selected') && val !== c.dataset.text) {
      c.classList.remove('selected');
    }
  });
});

document.getElementById('delModalOverlay').addEventListener('click', function(e) {
  if (e.target === this) closeDelModal();
});
document.getElementById('delCancelBtn').addEventListener('click', closeDelModal);

document.getElementById('delConfirmBtn').addEventListener('click', async function() {
  const reason = document.getElementById('delReasonInput').value.trim();
  const errEl  = document.getElementById('delModalErr');

  if (!reason) {
    errEl.textContent = <?= json_encode(__('admin_reviews_err_reason_empty')) ?>;
    document.getElementById('delReasonInput').focus();
    return;
  }
  if (reason.length < 10) {
    errEl.textContent = <?= json_encode(__('admin_reviews_err_reason_length')) ?>;
    document.getElementById('delReasonInput').focus();
    return;
  }

  this.disabled = true;
  this.innerHTML = '<span style="animation:spin .7s linear infinite;display:inline-block">⏳</span> ' + <?= json_encode(__('admin_reviews_deleting')) ?>;
  errEl.textContent = '';

  const fd = new FormData();
  fd.append('id', _delReviewId);
  fd.append('reason', reason);

  try {
    const res  = await fetch(API_BASE + '/review_delete.php', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.success) {
      const deletedId = _delReviewId;
      closeDelModal();
      const row = document.getElementById('row-' + deletedId);
      if (row) {
        row.style.transition = 'opacity .35s, transform .35s';
        row.style.opacity = '0';
        row.style.transform = 'translateX(20px)';
        setTimeout(() => { row.remove(); }, 380);
        // Cập nhật số đếm sau khi xóa
        const countEl = document.querySelector('.reviews-table-wrap [style*="padding:12px 16px"]');
        if (countEl) {
          const m = countEl.textContent.match(/(\d+)/);
          if (m) countEl.textContent = countEl.textContent.replace(m[1], parseInt(m[1]) - 1);
        }
      } else {
        location.reload();
      }
    } else {
      errEl.textContent = '❌ ' + (data.error || <?= json_encode(__('admin_reviews_err_unknown')) ?>);
      this.disabled = false;
      this.innerHTML = '🗑️ ' + <?= json_encode(__('admin_reviews_btn_confirm')) ?>;
    }
  } catch(ex) {
    errEl.textContent = <?= json_encode(__('admin_reviews_err_network')) ?>;
    this.disabled = false;
    this.innerHTML = '🗑️ ' + <?= json_encode(__('admin_reviews_btn_confirm')) ?>;
  }
});

// Ctrl+Enter để xác nhận nhanh
document.getElementById('delReasonInput').addEventListener('keydown', function(e) {
  if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) {
    document.getElementById('delConfirmBtn').click();
  }
});

// CSS animation cho spinner
const style = document.createElement('style');
style.textContent = '@keyframes spin { to { transform: rotate(360deg); } }';
document.head.appendChild(style);
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
