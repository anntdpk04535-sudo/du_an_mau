<?php
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$pageTitle = __('dashboard_ai_title');
include __DIR__ . '/../includes/header.php';
?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
.dashboard-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-top: 20px;
}
.card {
    background: white;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.05);
}
.card-title {
    font-size: 16px;
    color: var(--text-dark);
    margin-bottom: 15px;
    font-weight: 600;
    display: flex;
    justify-content: space-between;
}
.stat-value {
    font-size: 32px;
    font-weight: 700;
    color: var(--green-700);
}
.stat-sub {
    font-size: 13px;
    color: #888;
    margin-top: 5px;
}
</style>

<h1 class="section-title"><?= __('dashboard_ai_title') ?></h1>
<?php include __DIR__ . '/nav.php'; ?>

<div class="dashboard-grid">
    <div class="card">
        <div class="card-title">💰 <?= __('dashboard_ai_revenue') ?> <span style="color:#22c55e">↑ 24%</span></div>
        <div class="stat-value">1,452,000,000 VNĐ</div>
        <div class="stat-sub"><?= __('dashboard_ai_revenue_sub') ?></div>
    </div>
    <div class="card">
        <div class="card-title">👨‍👩‍👧‍👦 <?= __('dashboard_ai_tourists') ?> <span style="color:#3b82f6">↑ 12%</span></div>
        <div class="stat-value">3,240</div>
        <div class="stat-sub"><?= __('dashboard_ai_tourists_sub') ?></div>
    </div>
    <div class="card">
        <div class="card-title">⭐ <?= __('dashboard_ai_sentiment') ?></div>
        <div class="stat-value" style="color:#eab308">92% <?= __('dashboard_ai_sentiment_val') ?></div>
        <div class="stat-sub"><?= __('dashboard_ai_sentiment_sub') ?></div>
    </div>
</div>

<div class="dashboard-grid">
    <div class="card" style="grid-column: span 2;">
        <div class="card-title"><?= __('dashboard_ai_flow') ?></div>
        <canvas id="flowChart" height="100"></canvas>
    </div>
    <div class="card">
        <div class="card-title"><?= __('dashboard_ai_hotspots') ?></div>
        <ul style="list-style:none; padding:0; margin:0;">
            <li style="padding:10px 0; border-bottom:1px solid #eee; display:flex; justify-content:space-between;">
                <span><?= __('dashboard_ai_spot1') ?></span>
                <span style="color:#ef4444; font-weight:600;">95% <?= __('dashboard_ai_capacity') ?></span>
            </li>
            <li style="padding:10px 0; border-bottom:1px solid #eee; display:flex; justify-content:space-between;">
                <span><?= __('dashboard_ai_spot2') ?></span>
                <span style="color:#f59e0b; font-weight:600;">72% <?= __('dashboard_ai_capacity') ?></span>
            </li>
            <li style="padding:10px 0; display:flex; justify-content:space-between;">
                <span><?= __('dashboard_ai_spot3') ?></span>
                <span style="color:#f59e0b; font-weight:600;">68% <?= __('dashboard_ai_capacity') ?></span>
            </li>
        </ul>
        <p style="font-size:12px; color:#888; margin-top:10px;"><?= __('dashboard_ai_hotspots_sub') ?></p>
    </div>
</div>

<script>
const ctx = document.getElementById('flowChart').getContext('2d');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: ['<?= __('dashboard_ai_week1') ?>', '<?= __('dashboard_ai_week2') ?>', '<?= __('dashboard_ai_week3') ?>', '<?= __('dashboard_ai_week4') ?>'],
        datasets: [{
            label: '<?= __('dashboard_ai_flow_forest') ?>',
            data: [1200, 1900, 1500, 2200],
            borderColor: '#15803d',
            tension: 0.4
        },
        {
            label: '<?= __('dashboard_ai_flow_sea') ?>',
            data: [400, 850, 700, 1100],
            borderColor: '#0284c7',
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'top' },
        }
    }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
