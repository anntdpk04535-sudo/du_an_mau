<?php
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();
$pageTitle = __('admin_dashboard_title');
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

<h1 class="section-title"><?= __('admin_dashboard_heading') ?></h1>

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

<div class="chart-container">
    <h3 style="margin-top:0; margin-bottom:20px;"><?= __('admin_top_destinations') ?></h3>
    <div style="position: relative; height: 360px; width: 100%;">
        <canvas id="topDestinationsChart"></canvas>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const ctx = document.getElementById('topDestinationsChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?= json_encode($destNames, JSON_UNESCAPED_UNICODE) ?>,
            datasets: [{
                label: '<?= __('admin_avg_rating_label') ?>',
                data: <?= json_encode($destRatings) ?>,
                backgroundColor: 'rgba(34, 197, 94, 0.65)',
                borderColor: 'rgba(34, 197, 94, 1)',
                borderWidth: 2,
                borderRadius: 8,
                barPercentage: 0.55
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return ' Đánh giá: ' + context.parsed.y + ' / 5.0 ⭐';
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 5,
                    ticks: {
                        stepSize: 1,
                        font: { size: 12, weight: '600' }
                    }
                },
                x: {
                    ticks: {
                        font: { size: 13, weight: '600' },
                        color: '#1e293b',
                        maxRotation: 0,
                        autoSkip: false
                    }
                }
            }
        }
    });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
