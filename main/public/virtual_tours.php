<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = __('virtual_tour_title');
$currentLang = $_SESSION['lang'] ?? 'vi';

$pdo = getDB();

// Pagination logic
$limit = 6;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Total count
$totalStmt = $pdo->query("SELECT COUNT(*) FROM destinations");
$total = $totalStmt->fetchColumn();
$totalPages = ceil($total / $limit);

// Fetch destinations
$stmt = $pdo->prepare("SELECT * FROM destinations ORDER BY id DESC LIMIT :limit OFFSET :offset");
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$destinations = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="hero-section" style="background: linear-gradient(135deg, var(--green-700), var(--green-900)); color: white; padding: 60px 0; text-align: center;">
    <h1><?= __('virtual_tour_title') ?></h1>
    <p><?= __('virtual_tour_sub') ?></p>
</div>

<div class="container section">
    <div class="grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; margin-top: 30px;">
        <?php foreach ($destinations as $dest): ?>
            <?php 
                $title = ($currentLang === 'en' && !empty($dest['name_en'])) ? $dest['name_en'] : $dest['name'];
                $desc = ($currentLang === 'en' && !empty($dest['short_desc_en'])) ? $dest['short_desc_en'] : $dest['short_desc'];
            ?>
            <div class="card" style="border-radius: 12px; overflow: hidden; background: #fff; box-shadow: 0 4px 6px rgba(0,0,0,0.1); transition: transform 0.3s ease;">
                <div class="card-img" style="height: 200px; overflow: hidden; position: relative; padding: 0;">
                    <img src="<?= e($dest['image_url']) ?>" alt="<?= e($title) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: rgba(0,0,0,0.5); border-radius: 50%; padding: 15px; display: flex; align-items: center; justify-content: center;">
                        <span style="font-size: 24px;">🌐</span>
                    </div>
                </div>
                <div class="card-body" style="padding: 20px;">
                    <h3 style="margin-top: 0; color: var(--green-900);"><?= e($title) ?></h3>
                    <p style="color: #666; font-size: 14px; line-height: 1.5; margin-bottom: 20px;"><?= e($desc) ?></p>
                    
                    <?php if (!empty($dest['image_360_url'])): ?>
                        <?php 
                            $panoUrl = str_starts_with($dest['image_360_url'], 'http') ? $dest['image_360_url'] : url($dest['image_360_url']);
                        ?>
                        <button onclick="open360('<?= $panoUrl ?>', '<?= e($title) ?>')" class="btn primary" style="display: block; text-align: center; width: 100%; box-sizing: border-box; background: var(--orange-500); color: white; border: none; cursor: pointer; padding: 10px; border-radius: 6px; font-weight: bold;">
                            🌐 <?= __('view_360') ?>
                        </button>
                    <?php else: ?>
                        <button onclick="alert('Địa điểm này đang được cập nhật ảnh 360 độ. Vui lòng quay lại sau nhé!');" class="btn" style="display: block; text-align: center; width: 100%; box-sizing: border-box; background: #eee; color: #999; border: none; cursor: not-allowed; padding: 10px; border-radius: 6px; font-weight: bold;">
                            🚧 Đang cập nhật
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div style="text-align: center; margin-top: 40px; margin-bottom: 20px;">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="?page=<?= $i ?>" class="btn <?= $i === $page ? 'primary' : 'secondary' ?>" style="<?= $i === $page ? 'background: var(--green-900);' : 'background: #eee; color: var(--text-dark);' ?> padding: 8px 16px; margin: 0 5px;">
                <?= $i ?>
            </a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</div>

<!-- 360 Viewer Modal -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/pannellum@2.5.6/build/pannellum.css"/>
<script src="https://cdn.jsdelivr.net/npm/pannellum@2.5.6/build/pannellum.js"></script>

<div id="pano-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; z-index:99999; background:black;">
    <div id="pano-container" style="width:100%; height:100%;"></div>
    <button onclick="close360()" style="position:absolute; top:20px; left:20px; z-index:100000; background:rgba(0,0,0,0.6); color:white; border:2px solid white; padding:10px 20px; border-radius:30px; font-weight:bold; cursor:pointer; font-size: 14px; transition: 0.2s;">⬅ Đóng 360°</button>
    <div id="pano-title" style="position:absolute; top:20px; left:50%; transform:translateX(-50%); z-index:100000; background:rgba(0,0,0,0.6); color:white; padding:10px 25px; border-radius:30px; font-weight:bold; font-size:16px; text-shadow: 0 1px 3px rgba(0,0,0,0.8);"></div>
</div>

<script>
let panoViewer = null;

window.open360 = function(url, title) {
    document.getElementById('pano-modal').style.display = 'block';
    document.getElementById('pano-title').innerText = title;
    
    // Khóa cuộn trang
    document.body.style.overflow = 'hidden';
    
    if (panoViewer) {
        panoViewer.destroy();
    }
    
    panoViewer = pannellum.viewer('pano-container', {
        "type": "equirectangular",
        "panorama": url,
        "autoLoad": true,
        "autoRotate": -2,
        "compass": true,
        "hfov": 110,
        "minHfov": 50,
        "maxHfov": 120
    });
};

window.close360 = function() {
    document.getElementById('pano-modal').style.display = 'none';
    document.body.style.overflow = 'auto';
    if (panoViewer) {
        panoViewer.destroy();
        panoViewer = null;
    }
};
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
