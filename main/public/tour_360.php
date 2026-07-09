<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    redirect('/public/virtual_tours.php');
}

$pdo = getDB();
$stmt = $pdo->prepare("SELECT * FROM destinations WHERE id = ?");
$stmt->execute([$id]);
$tour = $stmt->fetch();

if (!$tour) {
    redirect('/public/virtual_tours.php');
}

$currentLang = $_SESSION['lang'] ?? 'vi';
$title = ($currentLang === 'en' && !empty($tour['name_en'])) ? $tour['name_en'] : $tour['name'];
$desc = ($currentLang === 'en' && !empty($tour['description_en'])) ? $tour['description_en'] : $tour['description'];

$pageTitle = $title . ' - ' . __('virtual_tour_360');

include __DIR__ . '/../includes/header.php';
?>

<!-- Pannellum CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/pannellum@2.5.6/build/pannellum.css">

<style>
    #panorama {
        width: 100%;
        height: 70vh;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        margin-bottom: 20px;
    }
    .tour-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        flex-wrap: wrap;
    }
    .tour-header h1 {
        margin: 0;
        color: var(--primary-color);
    }
    .back-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        color: #666;
        text-decoration: none;
        font-weight: bold;
        transition: color 0.3s ease;
    }
    .back-btn:hover {
        color: var(--primary-color);
    }
</style>

<div class="container section">
    <div class="tour-header">
        <div>
            <h1><?= e($title) ?></h1>
            <p style="color: #666; margin-top: 5px;"><?= e($desc) ?></p>
        </div>
        <a href="<?= url('/public/virtual_tours.php') ?>" class="back-btn">
            ⬅ <?= __('back_to_tours') ?>
        </a>
    </div>
    
    <?php if (!empty($tour['image_360_url'])): ?>
        <div id="panorama"></div>
        <script src="https://cdn.jsdelivr.net/npm/pannellum@2.5.6/build/pannellum.js"></script>
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                pannellum.viewer('panorama', {
                    "type": "equirectangular",
                    "panorama": "<?= $tour['image_360_url'] ?>",
                    "autoLoad": true,
                    "autoRotate": -2,
                    "compass": true,
                    "hfov": 110,
                    "minHfov": 50,
                    "maxHfov": 120
                });
            });
        </script>
    <?php else: ?>
        <div style="background: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); text-align: center;">
            <div style="font-size: 40px; margin-bottom: 15px;">🚧</div>
            <h3 style="color: var(--green-900); margin-top: 0;">Đang cập nhật không gian 360°</h3>
            <p style="color: #666; margin-bottom: 25px;">Địa điểm này hiện chưa có dữ liệu ảnh toàn cảnh 360 độ (Panorama). Vui lòng xem ảnh thông thường dưới đây.</p>
            <img src="<?= e($tour['image_url']) ?>" alt="<?= e($title) ?>" style="max-width: 100%; max-height: 60vh; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); object-fit: contain;">
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
