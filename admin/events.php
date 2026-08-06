<?php
require_once __DIR__ . '/../includes/content_helpers.php';
requireAdmin();

$db = getDB();
$editing = null;
$error = '';
$success = '';

function slugify(string $text): string {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    return strtolower($text ?: 'n-a');
}

try {
    // Delete action
    if (isset($_GET['delete'])) {
        $deleteId = (int)$_GET['delete'];
        $stmt = $db->prepare('DELETE FROM `events` WHERE id = ?');
        $stmt->execute([$deleteId]);
        header('Location: ' . url('/admin/events.php?msg=deleted'));
        exit;
    }

    // Toggle Featured action
    if (isset($_GET['toggle_featured'])) {
        $id = (int)$_GET['toggle_featured'];
        $stmt = $db->prepare('UPDATE `events` SET is_featured = CASE WHEN is_featured = 1 THEN 0 ELSE 1 END WHERE id = ?');
        $stmt->execute([$id]);
        header('Location: ' . url('/admin/events.php'));
        exit;
    }

    // Fetch item for editing
    if (isset($_GET['edit'])) {
        $editId = (int)$_GET['edit'];
        $stmt = $db->prepare('SELECT * FROM `events` WHERE id = ?');
        $stmt->execute([$editId]);
        $editing = $stmt->fetch();
    }

    // Handle Form Submit (Add / Edit)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $id = (int)($_POST['id'] ?? 0);
        $title = trim((string)($_POST['title'] ?? ''));
        $category = trim((string)($_POST['category'] ?? 'van-hoa'));
        $startDate = trim((string)($_POST['start_date'] ?? ''));
        $endDate = trim((string)($_POST['end_date'] ?? ''));
        $location = trim((string)($_POST['location'] ?? ''));
        $shortDesc = trim((string)($_POST['short_desc'] ?? ''));
        $content = trim((string)($_POST['content'] ?? ''));
        $status = trim((string)($_POST['status'] ?? 'published'));
        $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
        $imageUrl = trim((string)($_POST['image_url'] ?? ''));

        if ($title === '') throw new RuntimeException('Tiêu đề lễ hội là bắt buộc.');
        if ($startDate === '' || $endDate === '') throw new RuntimeException('Ngày bắt đầu và kết thúc là bắt buộc.');

        // Custom slug or generate from title
        $slug = trim((string)($_POST['slug'] ?? ''));
        if (empty($slug)) {
            $slug = slugify($title);
        }

        // Handle Image Upload if provided
        if (!empty($_FILES['image_file']['name'])) {
            $file = $_FILES['image_file'];
            $uploadedPath = uploadLocalImage($file, 'events');
            if ($uploadedPath) {
                $imageUrl = $uploadedPath;
            }
        }

        if ($id > 0) {
            $stmt = $db->prepare('UPDATE `events` SET 
                `title` = ?, `slug` = ?, `category` = ?, `start_date` = ?, `end_date` = ?, 
                `location` = ?, `short_desc` = ?, `content` = ?, `image_url` = ?, 
                `is_featured` = ?, `status` = ? WHERE `id` = ?');
            $stmt->execute([
                $title, $slug, $category, $startDate, $endDate, 
                $location, $shortDesc, $content, $imageUrl, 
                $isFeatured, $status, $id
            ]);
            $success = 'Cập nhật lễ hội thành công!';
        } else {
            $stmt = $db->prepare('INSERT INTO `events` 
                (`title`, `slug`, `category`, `start_date`, `end_date`, `location`, `short_desc`, `content`, `image_url`, `is_featured`, `status`) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([
                $title, $slug, $category, $startDate, $endDate, 
                $location, $shortDesc, $content, $imageUrl, 
                $isFeatured, $status
            ]);
            $success = 'Thêm lễ hội mới thành công!';
        }

        header('Location: ' . url('/admin/events.php?msg=success'));
        exit;
    }
} catch (Throwable $e) {
    $error = $e->getMessage();
}

$events = getAllEvents(null, 'published');
// Fetch all events including draft
$allEventsStmt = $db->query('SELECT * FROM `events` ORDER BY start_date DESC');
$allEvents = $allEventsStmt ? $allEventsStmt->fetchAll() : [];

$pageTitle = 'Quản lý Sự kiện & Lễ hội - Admin';
include __DIR__ . '/../includes/header.php';
?>

<div class="admin-container" style="max-width: 1200px; margin: 20px auto;">
  <?php include __DIR__ . '/nav.php'; ?>

  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <h1 class="section-title" style="margin: 0;">🎪 Quản lý Sự kiện & Lễ hội Đắk Lắk</h1>
    <?php if ($editing): ?>
      <a href="<?= url('/admin/events.php') ?>" class="btn secondary">➕ Thêm lễ hội mới</a>
    <?php endif; ?>
  </div>

  <?php if (!empty($_GET['msg']) && $_GET['msg'] === 'success'): ?>
    <div class="form-box" style="background: #dcfce7; color: #166534; margin-bottom: 20px; font-weight: 600;">
      ✅ Thao tác cập nhật lễ hội thành công!
    </div>
  <?php endif; ?>

  <?php if (!empty($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
    <div class="form-box" style="background: #fee2e2; color: #991b1b; margin-bottom: 20px; font-weight: 600;">
      🗑️ Đã xóa sự kiện thành công!
    </div>
  <?php endif; ?>

  <?php if ($error): ?>
    <div class="form-box" style="background: #fee2e2; color: #991b1b; margin-bottom: 20px; font-weight: 600;">
      ❌ Lỗi: <?= e($error) ?>
    </div>
  <?php endif; ?>

  <!-- Add / Edit Form Box -->
  <div class="form-box" style="background: #FFFFFF; border-radius: 16px; padding: 24px; margin-bottom: 30px; box-shadow: 0 4px 20px rgba(0,0,0,0.06);">
    <h2 style="font-size: 20px; color: var(--basalt-red); margin-top: 0; margin-bottom: 20px; border-bottom: 2px dashed var(--line); padding-bottom: 10px;">
      <?= $editing ? '✏️ Chỉnh sửa Lễ hội: ' . e($editing['title']) : '➕ Thêm Lễ hội & Sự kiện mới' ?>
    </h2>

    <form method="post" enctype="multipart/form-data">
      <input type="hidden" name="id" value="<?= e((string)($editing['id'] ?? 0)) ?>">

      <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 16px;">
        <div class="form-group">
          <label>Tên Lễ hội / Sự kiện <span style="color:red">*</span></label>
          <input type="text" name="title" required value="<?= e($editing['title'] ?? '') ?>" placeholder="VD: Lễ hội Cà phê Buôn Ma Thuột 2026">
        </div>

        <div class="form-group">
          <label>Danh mục</label>
          <select name="category" required style="width:100%; padding: 10px; border-radius: 8px; border: 1px solid var(--line);">
            <option value="nong-san" <?= ($editing['category'] ?? '') === 'nong-san' ? 'selected' : '' ?>>☕ Nông sản & Cà phê</option>
            <option value="van-hoa" <?= ($editing['category'] ?? '') === 'van-hoa' ? 'selected' : '' ?>>🥁 Văn hóa & Cồng chiêng</option>
            <option value="phong-tuc" <?= ($editing['category'] ?? '') === 'phong-tuc' ? 'selected' : '' ?>>🔥 Nghi lễ & Phong tục</option>
          </select>
        </div>
      </div>

      <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px;">
        <div class="form-group">
          <label>Ngày bắt đầu <span style="color:red">*</span></label>
          <input type="date" name="start_date" required value="<?= e($editing['start_date'] ?? date('Y-m-d')) ?>">
        </div>

        <div class="form-group">
          <label>Ngày kết thúc <span style="color:red">*</span></label>
          <input type="date" name="end_date" required value="<?= e($editing['end_date'] ?? date('Y-m-d', strtotime('+3 days'))) ?>">
        </div>

        <div class="form-group">
          <label>Trạng thái</label>
          <select name="status" style="width:100%; padding: 10px; border-radius: 8px; border: 1px solid var(--line);">
            <option value="published" <?= ($editing['status'] ?? '') === 'published' ? 'selected' : '' ?>>🟢 Xuất bản (Hiển thị)</option>
            <option value="draft" <?= ($editing['status'] ?? '') === 'draft' ? 'selected' : '' ?>>🟡 Nháp (Ẩn)</option>
          </select>
        </div>
      </div>

      <div class="form-group">
        <label>Địa điểm tổ chức</label>
        <input type="text" name="location" value="<?= e($editing['location'] ?? '') ?>" placeholder="VD: Quảng trường 10/3, TP. Buôn Ma Thuột">
      </div>

      <div class="form-group">
        <label>Mô tả ngắn</label>
        <textarea name="short_desc" rows="2" placeholder="Mô tả tóm tắt hiện trên danh sách..."><?= e($editing['short_desc'] ?? '') ?></textarea>
      </div>

      <div class="form-group">
        <label>Nội dung chi tiết (Hỗ trợ HTML: &lt;h4&gt;, &lt;p&gt;, &lt;ul&gt;, &lt;li&gt;...)</label>
        <textarea name="content" rows="6" placeholder="Nội dung giới thiệu chi tiết các hoạt động..."><?= e($editing['content'] ?? '') ?></textarea>
      </div>

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
        <div class="form-group">
          <label>Hình ảnh (Tải lên từ máy)</label>
          <input type="file" name="image_file" accept="image/jpeg,image/png,image/webp">
        </div>

        <div class="form-group">
          <label>Hoặc đường dẫn Ảnh (URL)</label>
          <input type="text" name="image_url" value="<?= e($editing['image_url'] ?? '') ?>" placeholder="/assets/images/...">
        </div>
      </div>

      <div class="form-group" style="display: flex; align-items: center; gap: 10px; margin-top: 10px;">
        <input type="checkbox" id="is_featured" name="is_featured" value="1" <?= (!isset($editing['is_featured']) || $editing['is_featured'] == 1) ? 'checked' : '' ?> style="width: 18px; height: 18px;">
        <label for="is_featured" style="cursor: pointer; font-weight: 700; color: var(--basalt-red);">⭐ Hiển thị Lễ hội Nổi bật trên Trang chủ</label>
      </div>

      <div style="margin-top: 20px; display: flex; gap: 12px;">
        <button type="submit" class="btn btn-basalt"><?= $editing ? '💾 Lưu Cập Nhật' : '➕ Thêm Lễ Hội Mới' ?></button>
        <?php if ($editing): ?>
          <a href="<?= url('/admin/events.php') ?>" class="btn secondary">Hủy bỏ</a>
        <?php endif; ?>
      </div>
    </form>
  </div>

  <!-- Events List Table -->
  <div class="form-box" style="background: #FFFFFF; border-radius: 16px; padding: 24px; box-shadow: 0 4px 20px rgba(0,0,0,0.06);">
    <h2 style="font-size: 20px; color: var(--coffee-brown); margin-top: 0; margin-bottom: 16px;">📋 Danh Sách Tất Cả Sự Kiện & Lễ Hội (<?= count($allEvents) ?>)</h2>

    <div style="overflow-x: auto;">
      <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
        <thead>
          <tr style="background: #FAF6F0; border-bottom: 2px solid var(--line); text-align: left;">
            <th style="padding: 12px; width: 60px;">Ảnh</th>
            <th style="padding: 12px;">Tên Lễ Hội</th>
            <th style="padding: 12px;">Danh Mục</th>
            <th style="padding: 12px;">Thời Gian</th>
            <th style="padding: 12px;">Địa Điểm</th>
            <th style="padding: 12px;">Nổi Bật</th>
            <th style="padding: 12px; text-align: center;">Thao Tác</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($allEvents)): ?>
            <?php foreach ($allEvents as $ev): ?>
              <tr style="border-bottom: 1px solid #F0EAE1;">
                <td style="padding: 10px;">
                  <img src="<?= url($ev['image_url'] ?: '/assets/images/placeholder.svg') ?>" alt="<?= e($ev['title']) ?>" onerror="this.onerror=null;this.src='<?= url('/assets/images/placeholder.svg') ?>';" style="width: 48px; height: 36px; object-fit: cover; border-radius: 6px;">

                </td>
                <td style="padding: 10px;">
                  <strong><a href="<?= url('/public/event_detail.php?slug=' . $ev['slug']) ?>" target="_blank" style="color: var(--coffee-brown); text-decoration: none;"><?= e($ev['title']) ?></a></strong>
                </td>
                <td style="padding: 10px;">
                  <span class="badge" style="background: #FAF6F0; color: var(--basalt-red); border: 1px solid var(--line);">
                    <?= $ev['category'] === 'nong-san' ? '☕ Nông sản' : ($ev['category'] === 'van-hoa' ? '🥁 Văn hóa' : '🔥 Nghi lễ') ?>
                  </span>
                </td>
                <td style="padding: 10px; white-space: nowrap;">
                  📅 <?= date('d/m/Y', strtotime($ev['start_date'])) ?><br>
                  <small style="color: var(--text-muted);">đến <?= date('d/m/Y', strtotime($ev['end_date'])) ?></small>
                </td>
                <td style="padding: 10px;">
                  <small><?= e($ev['location']) ?></small>
                </td>
                <td style="padding: 10px; text-align: center;">
                  <a href="?toggle_featured=<?= $ev['id'] ?>" style="text-decoration: none; font-size: 18px;" title="Bấm để bật/tắt nổi bật">
                    <?= $ev['is_featured'] ? '⭐' : '⚪' ?>
                  </a>
                </td>
                <td style="padding: 10px; text-align: center; white-space: nowrap;">
                  <a href="?edit=<?= $ev['id'] ?>" class="btn secondary" style="padding: 4px 10px; font-size: 13px;">✏️ Sửa</a>
                  <a href="?delete=<?= $ev['id'] ?>" onclick="return confirm('Bạn có chắc chắn muốn xóa lễ hội này?')" class="btn secondary" style="padding: 4px 10px; font-size: 13px; color: #991b1b; background: #fee2e2;">🗑️ Xóa</a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="7" style="padding: 20px; text-align: center; color: var(--text-muted);">Chưa có sự kiện nào trong hệ thống.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
