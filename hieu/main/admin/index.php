<?php
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();
$pageTitle = 'Tổng quan hệ thống - Admin';
$db = getDB();

// Thống kê tổng quan
$totalUsers = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalItineraries = $db->query("SELECT COUNT(*) FROM itineraries")->fetchColumn();
$totalChatSessions = $db->query("SELECT COUNT(DISTINCT session_id) FROM chat_logs")->fetchColumn();

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
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
.dashboard-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}
.stat-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    display: flex;
    align-items: center;
    gap: 20px;
}
.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    background: #f0fdf4;
    color: var(--green-600);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
}
.stat-info h3 {
    margin: 0;
    font-size: 32px;
    color: var(--text-dark);
}
.stat-info p {
    margin: 0;
    color: #64748b;
    font-size: 14px;
    font-weight: 500;
}
.chart-container {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    margin-bottom: 30px;
}
</style>
<?php include __DIR__ . '/nav.php'; ?>

<h1 class="section-title">Dashboard Tổng Quan</h1>

<div class="dashboard-grid">
    <div class="stat-card">
        <div class="stat-icon">👥</div>
        <div class="stat-info">
            <h3><?= $totalUsers ?></h3>
            <p>Tổng Người Dùng</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">🗺️</div>
        <div class="stat-info">
            <h3><?= $totalItineraries ?></h3>
            <p>Lượt tạo Lịch trình AI</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">💬</div>
        <div class="stat-info">
            <h3><?= $totalChatSessions ?></h3>
            <p>Phiên Chat AI</p>
        </div>
    </div>
</div>

<div class="chart-container">
    <h3 style="margin-top:0;">Top 5 Điểm đến được đánh giá cao nhất</h3>
    <canvas id="topDestinationsChart" height="100"></canvas>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const ctx = document.getElementById('topDestinationsChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?= json_encode($destNames) ?>,
            datasets: [{
                label: 'Điểm đánh giá trung bình',
                data: <?= json_encode($destRatings) ?>,
                backgroundColor: 'rgba(34, 197, 94, 0.6)',
                borderColor: 'rgba(34, 197, 94, 1)',
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
