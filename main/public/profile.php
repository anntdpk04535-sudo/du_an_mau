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
    
    if (empty($fullName)) {
        $error = 'Vui lòng nhập họ tên.';
    } else {
        try {
            if (!empty($newPassword)) {
                $hash = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $db->prepare("UPDATE users SET full_name = ?, password_hash = ? WHERE id = ?");
                $stmt->execute([$fullName, $hash, $user['id']]);
            } else {
                $stmt = $db->prepare("UPDATE users SET full_name = ? WHERE id = ?");
                $stmt->execute([$fullName, $user['id']]);
            }
            // Update session if needed
            $_SESSION['user']['full_name'] = $fullName;
            $user['full_name'] = $fullName;
            $message = 'Cập nhật thông tin thành công!';
        } catch (Exception $e) {
            $error = 'Lỗi hệ thống: ' . $e->getMessage();
        }
    }
}

// Fetch user itineraries
$stmt = $db->prepare("SELECT * FROM itineraries WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user['id']]);
$itineraries = $stmt->fetchAll();

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
            <div class="profile-avatar">
                <?= mb_strtoupper(mb_substr($user['full_name'], 0, 1)) ?>
            </div>
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

            <form method="POST" style="text-align: left;">
                <div class="form-group">
                    <label>Họ và tên</label>
                    <input type="text" name="full_name" value="<?= e($user['full_name']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Đổi mật khẩu (để trống nếu không đổi)</label>
                    <input type="password" name="new_password" placeholder="Mật khẩu mới...">
                </div>
                <button type="submit" class="btn" style="width:100%;">💾 Cập nhật thông tin</button>
            </form>
        </div>
    </div>

    <!-- Cột lịch trình -->
    <div>
        <h2 style="margin-top:0;">🗺️ Lịch trình AI đã lưu</h2>
        <p style="color:#666;">Khám phá lại những kế hoạch du lịch mà AI đã gợi ý cho bạn.</p>

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
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
