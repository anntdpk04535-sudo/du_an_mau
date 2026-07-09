<?php
require_once __DIR__ . '/../includes/functions.php';

$userSession = currentUser();
if (!$userSession) {
    header('Location: ' . url('/public/login.php'));
    exit;
}

$db = getDB();
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userSession['id']]);
$user = $stmt->fetch();
if (!$user) {
    header('Location: ' . url('/public/logout.php'));
    exit;
}

$pageTitle = 'Trang cá nhân - Đắk Lắk Travel AI';
$message = '';
$error = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $newPassword = $_POST['new_password'] ?? '';
    $avatar = $user['avatar'] ?? null;
    
    if (empty($fullName)) {
        $error = 'Vui lòng nhập họ tên.';
    } else {
        try {
            // Handle image upload
            if (isset($_FILES['avatar_upload']) && $_FILES['avatar_upload']['error'] === UPLOAD_ERR_OK) {
                $ext = pathinfo($_FILES['avatar_upload']['name'], PATHINFO_EXTENSION);
                $filename = 'avatar_' . time() . '_' . uniqid() . '.' . $ext;
                $destPath = __DIR__ . '/../assets/images/uploads/' . $filename;
                $uploadDir = dirname($destPath);
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                if (move_uploaded_file($_FILES['avatar_upload']['tmp_name'], $destPath)) {
                    $avatar = url('/assets/images/uploads/' . $filename);
                }
            }

            if (!empty($newPassword)) {
                $hash = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $db->prepare("UPDATE users SET full_name = ?, avatar = ?, password_hash = ? WHERE id = ?");
                $stmt->execute([$fullName, $avatar, $hash, $user['id']]);
            } else {
                $stmt = $db->prepare("UPDATE users SET full_name = ?, avatar = ? WHERE id = ?");
                $stmt->execute([$fullName, $avatar, $user['id']]);
            }
            
            // Update session and local variable
            $_SESSION['user']['full_name'] = $fullName;
            $_SESSION['user']['avatar'] = $avatar;
            $user['full_name'] = $fullName;
            $user['avatar'] = $avatar;
            $message = 'Cập nhật thông tin thành công!';
        } catch (Exception $e) {
            $error = 'Lỗi hệ thống: ' . $e->getMessage();
        }
    }
}

// Pagination parameters
$itiLimit = 4;
$wishLimit = 4;
$itiPage = isset($_GET['iti_page']) && (int)$_GET['iti_page'] > 0 ? (int)$_GET['iti_page'] : 1;
$wishPage = isset($_GET['wish_page']) && (int)$_GET['wish_page'] > 0 ? (int)$_GET['wish_page'] : 1;

$itiOffset = ($itiPage - 1) * $itiLimit;
$wishOffset = ($wishPage - 1) * $wishLimit;

// Count itineraries
$stmtItiCount = $db->prepare("SELECT COUNT(*) FROM itineraries WHERE user_id = ?");
$stmtItiCount->execute([$user['id']]);
$totalItineraries = $stmtItiCount->fetchColumn();
$totalItiPages = ceil($totalItineraries / $itiLimit);

// Fetch user itineraries
$stmt = $db->prepare("SELECT * FROM itineraries WHERE user_id = ? ORDER BY created_at DESC LIMIT $itiLimit OFFSET $itiOffset");
$stmt->execute([$user['id']]);
$itineraries = $stmt->fetchAll();

// Count wishlist
$stmtWishCount = $db->prepare("SELECT COUNT(*) FROM wishlists WHERE user_id = ?");
$stmtWishCount->execute([$user['id']]);
$totalWishlists = $stmtWishCount->fetchColumn();
$totalWishPages = ceil($totalWishlists / $wishLimit);

// Fetch wishlist
$stmt = $db->prepare("SELECT d.* FROM wishlists w JOIN destinations d ON w.destination_id = d.id WHERE w.user_id = ? ORDER BY w.created_at DESC LIMIT $wishLimit OFFSET $wishOffset");
$stmt->execute([$user['id']]);
$wishlists = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<style>
.profile-layout {
    display: grid;
    grid-template-columns: 320px 1fr;
    gap: 30px;
    align-items: start;
    margin-top: 20px;
}
@media (max-width: 900px) {
    .profile-layout { grid-template-columns: 1fr; }
}
.profile-card {
    background: white;
    border-radius: var(--radius);
    padding: 30px;
    box-shadow: 0 4px 20px rgba(0,0,0,.08);
    text-align: center;
}
.profile-avatar {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--green-600), var(--green-400));
    color: white;
    font-size: 36px;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 16px;
    box-shadow: 0 4px 12px rgba(45,106,79,.3);
}
.itinerary-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 20px;
    margin-top: 20px;
}
.itinerary-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 12px rgba(0,0,0,.06);
    border: 1px solid #f1f5f9;
    transition: transform 0.2s, box-shadow 0.2s;
    display: flex;
    flex-direction: column;
}
.itinerary-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(0,0,0,.1);
}
.itinerary-card h3 {
    margin: 0 0 10px;
    font-size: 16px;
    color: var(--green-900);
}
.itinerary-card p {
    margin: 0 0 16px;
    color: #64748b;
    font-size: 13px;
    flex-grow: 1;
}
.itinerary-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 12px;
    color: #94a3b8;
    border-top: 1px solid #f1f5f9;
    padding-top: 12px;
    margin-top: auto;
}
.empty-state {
    text-align: center;
    padding: 40px;
    background: white;
    border-radius: var(--radius);
    color: #64748b;
    border: 2px dashed #e2e8f0;
}
</style>

<div class="profile-layout">
    <!-- Cột thông tin cá nhân -->
    <div>
        <div class="profile-card">
            <?php if (!empty($user['avatar'])): ?>
                <img src="<?= e($user['avatar']) ?>" alt="Avatar" class="profile-avatar" style="object-fit: cover;">
            <?php else: ?>
                <div class="profile-avatar">
                    <?= mb_strtoupper(mb_substr($user['full_name'], 0, 1)) ?>
                </div>
            <?php endif; ?>
            <h2 style="margin: 0 0 4px;"><?= e($user['full_name']) ?></h2>
            <p style="color: #666; font-size: 14px; margin-bottom: 24px;"><?= e($user['email']) ?></p>
            
            <?php if ($message): ?>
                <div style="background: #dcfce7; color: #166534; padding: 10px; border-radius: 8px; margin-bottom: 16px; font-size: 14px;">
                    ✅ <?= e($message) ?>
                </div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div style="background: #fee2e2; color: #991b1b; padding: 10px; border-radius: 8px; margin-bottom: 16px; font-size: 14px;">
                    ❌ <?= e($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" style="text-align: left;">
                <div class="form-group">
                    <label><?= __('avatar_label') ?></label>
                    <input type="file" name="avatar_upload" accept="image/*">
                </div>
                <div class="form-group">
                    <label><?= __('fullname_label') ?></label>
                    <input type="text" name="full_name" value="<?= e($user['full_name']) ?>" required>
                </div>
                <div class="form-group">
                    <label><?= __('new_password_label') ?></label>
                    <input type="password" name="new_password" placeholder="Mật khẩu mới...">
                </div>
                <button type="submit" class="btn" style="width:100%;">💾 <?= __('update_profile') ?></button>
            </form>
        </div>
    </div>

    <!-- Cột lịch trình -->
    <div>
        <h2 style="margin-top:0;">🗺️ <?= __('my_saved_iti') ?></h2>
        <p style="color:#666;"><?= __('my_saved_iti_sub') ?></p>

        <?php if (empty($itineraries)): ?>
            <div class="empty-state">
                <div style="font-size: 40px; margin-bottom: 16px;">🧭</div>
                <h3>Bạn chưa tạo lịch trình nào</h3>
                <p>Hãy để AI giúp bạn lên kế hoạch cho chuyến đi Đắk Lắk sắp tới nhé!</p>
                <a href="<?= url('/public/itinerary.php') ?>" class="btn" style="margin-top: 10px; display: inline-block;">Tạo lịch trình ngay</a>
            </div>
        <?php else: ?>
            <div class="itinerary-grid">
                <?php foreach ($itineraries as $it): ?>
                    <a href="<?= url('/public/itinerary_view.php?id=' . $it['id']) ?>" class="itinerary-card" style="text-decoration: none;">
                        <h3><?= e($it['title']) ?></h3>
                        <p>Sở thích: <?= e($it['preferences'] ?: 'Không có') ?></p>
                        <div class="itinerary-meta">
                            <span>⏱️ <?= $it['days'] ?> ngày</span>
                            <span>📅 <?= date('d/m/Y', strtotime($it['created_at'])) ?></span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
            
            <?php if (isset($totalItiPages) && $totalItiPages > 1): ?>
            <div class="pagination" style="display: flex; justify-content: center; gap: 10px; margin-top: 20px;">
                <?php for ($i = 1; $i <= $totalItiPages; $i++): ?>
                    <a href="?iti_page=<?= $i ?>&wish_page=<?= $wishPage ?>" class="btn <?= $i === $itiPage ? '' : 'secondary' ?>" style="padding: 5px 12px; font-size: 14px;">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
        <?php endif; ?>

        <h2 style="margin-top:40px;">❤️ <?= __('saved_dest') ?></h2>
        <p style="color:#666;"><?= __('saved_dest_desc') ?></p>

        <?php if (empty($wishlists)): ?>
            <div class="empty-state">
                <div style="font-size: 40px; margin-bottom: 16px;">🤍</div>
                <h3>Chưa có điểm đến nào</h3>
                <p>Khám phá các điểm đến và bấm lưu để xem lại sau nhé!</p>
                <a href="<?= url('/public/destinations.php') ?>" class="btn" style="margin-top: 10px; display: inline-block;">Khám phá ngay</a>
            </div>
        <?php else: ?>
            <div class="itinerary-grid">
                <?php foreach ($wishlists as $w): ?>
                    <a href="<?= url('/public/destination.php?slug=' . $w['slug']) ?>" class="itinerary-card" style="text-decoration: none; padding: 0; overflow: hidden;">
                        <?php if ($w['image_url']): ?>
                            <img src="<?= e($w['image_url']) ?>" alt="<?= e($w['name']) ?>" style="width:100%; height:120px; object-fit:cover; border-bottom: 1px solid #f1f5f9;">
                        <?php else: ?>
                            <div style="width:100%; height:120px; background:#d8f3dc; display:flex; align-items:center; justify-content:center; font-size:30px;">🌄</div>
                        <?php endif; ?>
                        <div style="padding: 16px; display: flex; flex-direction: column; flex-grow: 1;">
                            <h3 style="margin-top: 0;"><?= e($w['name']) ?></h3>
                            <p style="margin-bottom: 10px; line-height: 1.4;"><?= mb_substr(e($w['short_desc']), 0, 80) ?>...</p>
                            <div class="itinerary-meta" style="margin-top: auto;">
                                <span>💰 <?= e(priceLevelVi($w['price_level'])) ?></span>
                                <span>⭐ <?= number_format((float)$w['avg_rating'], 1) ?></span>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
            
            <?php if (isset($totalWishPages) && $totalWishPages > 1): ?>
            <div class="pagination" style="display: flex; justify-content: center; gap: 10px; margin-top: 20px;">
                <?php for ($i = 1; $i <= $totalWishPages; $i++): ?>
                    <a href="?iti_page=<?= $itiPage ?>&wish_page=<?= $i ?>" class="btn <?= $i === $wishPage ? '' : 'secondary' ?>" style="padding: 5px 12px; font-size: 14px;">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
        <?php endif; ?>


    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
