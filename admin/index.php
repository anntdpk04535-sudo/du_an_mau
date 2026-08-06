<?php
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();
$pageTitle = __('admin_dashboard_title');
$db = getDB();

// Thống kê tổng quan
$totalUsers = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalItineraries = $db->query("SELECT COUNT(*) FROM itineraries")->fetchColumn();
$totalChatSessions = $db->query("SELECT COUNT(DISTINCT session_id) FROM chat_logs")->fetchColumn();
$totalDestinations = $db->query("SELECT COUNT(*) FROM destinations")->fetchColumn();
$totalFoods = $db->query("SELECT COUNT(*) FROM foods")->fetchColumn();
$totalAccommodations = $db->query("SELECT COUNT(*) FROM accommodations WHERE COALESCE(status, 'published') = 'published'")->fetchColumn();
$totalReviews = $db->query("SELECT COUNT(*) FROM reviews")->fetchColumn();

// Lấy top 5 điểm đến được đánh giá cao nhất
$topDestinations = $db->query("
    SELECT name, ROUND(COALESCE(AVG(r.rating), destinations.rating), 1) as avg_rating 
    FROM destinations 
    LEFT JOIN reviews r ON r.destination_id = destinations.id 
    GROUP BY destinations.id 
    ORDER BY avg_rating DESC 
    LIMIT 5
")->fetchAll();

$destNames = [];
$destRatings = [];
foreach ($topDestinations as $d) {
    $destNames[] = $d['name'];
    $destRatings[] = $d['avg_rating'];
}

include __DIR__ . '/../includes/header.php';
$totalEvents = $db->query("SELECT COUNT(*) FROM events")->fetchColumn();
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
.admin-dashboard { padding-bottom: 44px; }
.admin-nav { margin-bottom: 28px; border-radius:3px 18px 3px 18px; border:1px solid var(--line); background:#fbf8f1; box-shadow:0 9px 22px rgba(75,57,35,.08); gap:7px; padding:10px; }
.admin-nav a { border-radius:3px; background:transparent; color:var(--green-900); font-size:12px; padding:9px 11px; }
.admin-nav a:hover, .admin-nav a.active { background:var(--green-900); color:#fffaf0; transform:translateY(-1px); }
.admin-nav a[style] { background:#f4e5d8!important; color:var(--basalt-red)!important; }
.admin-nav a[style]:hover { background:var(--basalt-red)!important; color:#fff!important; }
.admin-kicker { margin:0 0 9px; color:var(--basalt-red); font-size:11px; font-weight:800; letter-spacing:.18em; text-transform:uppercase; }
.admin-heading-row { display:flex; align-items:flex-end; justify-content:space-between; gap:24px; margin:10px 0 24px; }
.admin-heading-row h1 { max-width:680px; margin:0; color:var(--green-900); font:700 clamp(38px,5vw,62px)/.95 Georgia,serif; letter-spacing:-.055em; }
.admin-heading-row p { max-width:350px; margin:0 0 3px; color:#756c5e; font-size:13px; line-height:1.55; }
.admin-status { display:inline-flex; align-items:center; gap:7px; margin-top:16px; color:#586553; font-size:12px; }
.admin-status::before { content:""; width:8px; height:8px; border-radius:50%; background:#5f9b59; box-shadow:0 0 0 4px rgba(95,155,89,.14); }
.dashboard-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:10px; margin-bottom:22px; }
.stat-card { min-height:132px; position:relative; overflow:hidden; display:flex; align-items:flex-end; justify-content:space-between; gap:15px; padding:18px; border:1px solid var(--line); border-radius:3px 16px 3px 16px; background:#fbf8f1; box-shadow:0 10px 22px rgba(75,57,35,.07); }
.stat-card::after { content:""; position:absolute; width:100px; height:100px; right:-45px; top:-48px; border:1px solid rgba(128,47,40,.22); border-radius:50%; }
.stat-card:nth-child(2) { background:var(--green-900); color:#fffaf0; }
.stat-card:nth-child(2) .stat-info p { color:#c1d0be; }
.stat-card:nth-child(2) .stat-info h3 { color:#fffaf0; }
.stat-icon { width:42px; height:42px; display:grid; place-items:center; align-self:flex-start; border:1px solid rgba(128,47,40,.28); border-radius:3px 11px 3px 11px; background:#f4e5d8; font-size:20px; }
.stat-info h3 { margin:0; color:var(--green-900); font:700 38px/1 Georgia,serif; font-variant-numeric:tabular-nums; }
.stat-info p { margin:7px 0 0; color:#756c5e; font-size:12px; font-weight:700; letter-spacing:.04em; text-transform:uppercase; }
.admin-lower-grid { display:grid; grid-template-columns:minmax(0,1.4fr) minmax(250px,.6fr); gap:14px; align-items:stretch; }
.chart-container, .admin-ops-card { min-width:0; border:1px solid var(--line); border-radius:3px 18px 3px 18px; background:#fbf8f1; box-shadow:0 10px 22px rgba(75,57,35,.07); }
.chart-container { padding:22px; }
.chart-heading { display:flex; align-items:flex-start; justify-content:space-between; gap:14px; margin-bottom:15px; }
.chart-heading h2, .admin-ops-card h2 { margin:0; color:var(--green-900); font:700 24px/1.05 Georgia,serif; }
.chart-heading p { margin:5px 0 0; color:#756c5e; font-size:12px; }
.admin-chart-wrap { height:280px; }
.admin-ops-card { padding:22px; background:var(--coffee-brown); color:#f8f1e4; }
.admin-ops-card h2 { color:#fffaf0; }
.admin-ops-card > p { color:#d7c8b5; font-size:13px; line-height:1.55; }
.admin-ops-list { display:grid; gap:8px; margin:18px 0 0; }
.admin-ops-link { display:flex; align-items:center; justify-content:space-between; gap:12px; padding:12px 11px; color:#fffaf0; text-decoration:none; border:1px solid rgba(255,255,255,.2); border-radius:3px; background:rgba(255,255,255,.07); font-size:13px; }
.admin-ops-link:hover { background:rgba(255,255,255,.15); transform:translateX(3px); }
.admin-ops-link strong { color:var(--sun); font-variant-numeric:tabular-nums; }
.admin-ops-foot { margin-top:22px; padding-top:14px; border-top:1px solid rgba(255,255,255,.2); color:#d7c8b5; font-size:11px; }
@media (max-width: 900px) { .admin-heading-row { align-items:flex-start; flex-direction:column; gap:10px; } .admin-heading-row p { max-width:560px; } .admin-lower-grid { grid-template-columns:1fr; } }
@media (max-width: 640px) { .dashboard-grid { grid-template-columns:1fr; } .admin-nav { max-height:190px; overflow:auto; } .admin-nav a[style] { margin-left:0!important; } .admin-heading-row h1 { font-size:clamp(40px,14vw,58px); } .chart-container, .admin-ops-card { padding:18px; } .admin-chart-wrap { height:240px; } }
</style>
<?php include __DIR__ . '/nav.php'; ?>

<div class="admin-dashboard">
<div class="admin-heading-row">
    <div><p class="admin-kicker">TRAVEL OPERATIONS · ĐẮK LẮK</p><h1><?= __('admin_dashboard_heading') ?></h1><span class="admin-status">Catalog đang hoạt động</span></div>
    <p>Không gian điều phối nội dung, cộng đồng và những tuyến khám phá đang được hình thành trên nền dữ liệu địa phương.</p>
</div>

<div class="dashboard-grid">
    <div class="stat-card">
        <div class="stat-icon">👥</div>
        <div class="stat-info">
            <h3><?= $totalUsers ?></h3>
            <p><?= __('admin_total_users') ?></p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">🗺️</div>
        <div class="stat-info">
            <h3><?= $totalItineraries ?></h3>
            <p><?= __('admin_total_itineraries') ?></p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">💬</div>
        <div class="stat-info">
            <h3><?= $totalChatSessions ?></h3>
            <p><?= __('admin_total_chat_sessions') ?></p>
        </div>
    </div>
</div>

<div class="admin-lower-grid">
<div class="chart-container">
    <div class="chart-heading"><div><h2><?= __('admin_top_destinations') ?></h2><p>Những địa danh đang nhận được nhiều tín nhiệm nhất.</p></div><span class="admin-kicker">TOP 5</span></div>
    <div class="admin-chart-wrap"><canvas id="topDestinationsChart"></canvas></div>
</div>
<aside class="admin-ops-card">
    <p class="admin-kicker" style="color:var(--sun);">CATALOG PULSE</p><h2>Nhịp dữ liệu hôm nay</h2>
    <p>Kiểm tra nhanh những phần đang tạo nên bản đồ sống về Đắk Lắk.</p>
    <div class="admin-ops-list">
      <a class="admin-ops-link" href="<?= url('/admin/events.php') ?>"><span>🎪 Sự kiện & Lễ hội</span><strong><?= (int)$totalEvents ?></strong></a>
      <a class="admin-ops-link" href="<?= url('/admin/destinations.php') ?>"><span>Điểm đến</span><strong><?= (int)$totalDestinations ?></strong></a>
      <a class="admin-ops-link" href="<?= url('/admin/foods.php') ?>"><span>Ẩm thực</span><strong><?= (int)$totalFoods ?></strong></a>
      <a class="admin-ops-link" href="<?= url('/admin/accommodations.php') ?>"><span>Lưu trú đã duyệt</span><strong><?= (int)$totalAccommodations ?></strong></a>
      <a class="admin-ops-link" href="<?= url('/admin/reviews.php') ?>"><span>Review đã ghi nhận</span><strong><?= (int)$totalReviews ?></strong></a>
    </div>
    <div class="admin-ops-foot">⌁ Ưu tiên dữ liệu có nguồn và ngày xác minh rõ ràng.</div>
</aside>
</div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const ctx = document.getElementById('topDestinationsChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?= json_encode($destNames) ?>,
            datasets: [{
                label: '<?= __('admin_avg_rating_label') ?>',
                data: <?= json_encode($destRatings) ?>,
                backgroundColor: 'rgba(128, 47, 40, 0.72)',
                borderColor: '#802f28',
                borderWidth: 1,
                borderRadius: 6
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true,
                    max: 5
                }
            },
            plugins: {
                legend: { display: false }
            }
        }
    });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
