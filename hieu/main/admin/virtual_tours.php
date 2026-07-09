<?php
/**
 * Admin: Quản lý Virtual Tour 360°
 * Xem danh sách, bật/tắt, quản lý scenes & hotspots
 */
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$pageTitle = 'Quản lý Tour 360° - Admin';
$db = getDB();

// ── Xử lý toggle bật/tắt virtual tour ──
if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $db->exec("UPDATE destinations SET virtual_tour_enabled = NOT virtual_tour_enabled WHERE id = $id");
    header('Location: ' . url('/admin/virtual_tours.php'));
    exit;
}

// ── Xử lý xoá scene ──
if (isset($_GET['delete_scene'])) {
    $sceneId = (int)$_GET['delete_scene'];
    $db->prepare("DELETE FROM virtual_tour_scenes WHERE id = ?")->execute([$sceneId]);
    header('Location: ' . url('/admin/virtual_tours.php') . (isset($_GET['dest']) ? '?dest=' . (int)$_GET['dest'] : ''));
    exit;
}

// ── Xử lý thêm/sửa scene ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_scene'])) {
    $sceneId = (int)($_POST['scene_id'] ?? 0);
    $destId = (int)($_POST['destination_id'] ?? 0);
    $sceneKey = trim($_POST['scene_key'] ?? '');
    $title = trim($_POST['title'] ?? '');
    $titleEn = trim($_POST['title_en'] ?? '');
    $panoramaUrl = trim($_POST['panorama_url'] ?? '');
    $thumbnailUrl = trim($_POST['thumbnail_url'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $descriptionEn = trim($_POST['description_en'] ?? '');
    $pitch = (float)($_POST['pitch'] ?? 0);
    $yaw = (float)($_POST['yaw'] ?? 0);
    $hfov = (int)($_POST['hfov'] ?? 110);
    $sortOrder = (int)($_POST['sort_order'] ?? 0);
    $isDefault = isset($_POST['is_default']) ? 1 : 0;

    if ($sceneId > 0) {
        $stmt = $db->prepare("UPDATE virtual_tour_scenes SET 
            destination_id=?, scene_key=?, title=?, title_en=?, panorama_url=?, thumbnail_url=?,
            description=?, description_en=?, pitch=?, yaw=?, hfov=?, sort_order=?, is_default=?
            WHERE id=?");
        $stmt->execute([$destId, $sceneKey, $title, $titleEn, $panoramaUrl, $thumbnailUrl,
            $description, $descriptionEn, $pitch, $yaw, $hfov, $sortOrder, $isDefault, $sceneId]);
    } else {
        $stmt = $db->prepare("INSERT INTO virtual_tour_scenes 
            (destination_id, scene_key, title, title_en, panorama_url, thumbnail_url, 
             description, description_en, pitch, yaw, hfov, sort_order, is_default)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([$destId, $sceneKey, $title, $titleEn, $panoramaUrl, $thumbnailUrl,
            $description, $descriptionEn, $pitch, $yaw, $hfov, $sortOrder, $isDefault]);
    }

    header('Location: ' . url('/admin/virtual_tours.php?dest=' . $destId));
    exit;
}

// ── Lấy dữ liệu ──
$destinations = $db->query("
    SELECT d.*, 
           (SELECT COUNT(*) FROM virtual_tour_scenes WHERE destination_id = d.id) as scene_count
    FROM destinations d 
    ORDER BY d.virtual_tour_enabled DESC, d.name ASC
")->fetchAll();

$selectedDest = (int)($_GET['dest'] ?? 0);
$scenes = [];
$editingScene = null;

if ($selectedDest > 0) {
    $scenes = $db->prepare("SELECT * FROM virtual_tour_scenes WHERE destination_id = ? ORDER BY sort_order, id");
    $scenes->execute([$selectedDest]);
    $scenes = $scenes->fetchAll();
}

if (isset($_GET['edit_scene'])) {
    $stmt = $db->prepare("SELECT * FROM virtual_tour_scenes WHERE id = ?");
    $stmt->execute([(int)$_GET['edit_scene']]);
    $editingScene = $stmt->fetch();
    if ($editingScene) $selectedDest = $editingScene['destination_id'];
}

// Analytics
$totalViews = $db->query("SELECT COUNT(*) FROM virtual_tour_interactions WHERE action = 'view_tour'")->fetchColumn();
$totalAudio = $db->query("SELECT COUNT(*) FROM virtual_tour_interactions WHERE action = 'play_audio'")->fetchColumn();
$totalComplete = $db->query("SELECT COUNT(*) FROM virtual_tour_interactions WHERE action = 'complete_tour'")->fetchColumn();

include __DIR__ . '/../includes/header.php';
?>

<?php include __DIR__ . '/nav.php'; ?>

<h1 style="display:flex; align-items:center; gap:10px;">🌐 Quản lý Tour 360°</h1>

<!-- ── Analytics Cards ── -->
<div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap:14px; margin-bottom:24px;">
  <div style="background: linear-gradient(135deg, #ede9fe, #ddd6fe); padding:18px 20px; border-radius:14px; border:1px solid #c4b5fd;">
    <div style="font-size:28px; font-weight:800; color:#6d28d9;"><?= number_format($totalViews) ?></div>
    <div style="font-size:13px; color:#7c3aed; font-weight:600;">👁️ Lượt xem tour</div>
  </div>
  <div style="background: linear-gradient(135deg, #fef3c7, #fde68a); padding:18px 20px; border-radius:14px; border:1px solid #fbbf24;">
    <div style="font-size:28px; font-weight:800; color:#b45309;"><?= number_format($totalAudio) ?></div>
    <div style="font-size:13px; color:#d97706; font-weight:600;">🔊 Nghe thuyết minh</div>
  </div>
  <div style="background: linear-gradient(135deg, #d1fae5, #a7f3d0); padding:18px 20px; border-radius:14px; border:1px solid #6ee7b7;">
    <div style="font-size:28px; font-weight:800; color:#065f46;"><?= number_format($totalComplete) ?></div>
    <div style="font-size:13px; color:#059669; font-weight:600;">✅ Hoàn thành tour</div>
  </div>
</div>

<!-- ── Danh sách điểm đến ── -->
<div class="form-box">
  <h3>📍 Điểm đến & Trạng thái Tour 360°</h3>
  <table style="width:100%; border-collapse:collapse; font-size:14px;">
    <thead>
      <tr style="background:#f1f5f9; text-align:left;">
        <th style="padding:10px 12px;">Điểm đến</th>
        <th style="padding:10px 12px; text-align:center;">Số cảnh</th>
        <th style="padding:10px 12px; text-align:center;">Trạng thái</th>
        <th style="padding:10px 12px; text-align:center;">Thao tác</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($destinations as $dest): ?>
      <tr style="border-bottom:1px solid #e2e8f0;">
        <td style="padding:10px 12px; font-weight:600;"><?= e($dest['name']) ?></td>
        <td style="padding:10px 12px; text-align:center;">
          <span style="background:#e0e7ff; color:#3730a3; padding:2px 10px; border-radius:20px; font-weight:700; font-size:13px;">
            <?= $dest['scene_count'] ?>
          </span>
        </td>
        <td style="padding:10px 12px; text-align:center;">
          <?php if ($dest['virtual_tour_enabled']): ?>
            <span style="background:#d1fae5; color:#065f46; padding:3px 12px; border-radius:20px; font-size:12px; font-weight:700;">✅ Đang bật</span>
          <?php else: ?>
            <span style="background:#fee2e2; color:#991b1b; padding:3px 12px; border-radius:20px; font-size:12px; font-weight:700;">⏸ Tắt</span>
          <?php endif; ?>
        </td>
        <td style="padding:10px 12px; text-align:center;">
          <a href="<?= url('/admin/virtual_tours.php?toggle=' . $dest['id']) ?>"
             style="text-decoration:none; padding:5px 12px; border-radius:8px; font-size:12px; font-weight:600;
                    background:<?= $dest['virtual_tour_enabled'] ? '#fee2e2' : '#d1fae5' ?>;
                    color:<?= $dest['virtual_tour_enabled'] ? '#991b1b' : '#065f46' ?>;">
            <?= $dest['virtual_tour_enabled'] ? '⏸ Tắt' : '▶ Bật' ?>
          </a>
          <a href="<?= url('/admin/virtual_tours.php?dest=' . $dest['id']) ?>"
             style="text-decoration:none; padding:5px 12px; border-radius:8px; font-size:12px; font-weight:600; background:#e0e7ff; color:#3730a3; margin-left:4px;">
            🎬 Quản lý cảnh
          </a>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php if ($selectedDest > 0): ?>
<!-- ── Quản lý Scenes ── -->
<div class="form-box" style="margin-top:20px;">
  <h3 style="display:flex; align-items:center; justify-content:space-between;">
    <span>🎬 Danh sách cảnh
    <?php
      $destName = '';
      foreach ($destinations as $dd) { if ($dd['id'] == $selectedDest) { $destName = $dd['name']; break; } }
    ?>
    — <?= e($destName) ?></span>
    <a href="<?= url('/admin/virtual_tours.php?dest=' . $selectedDest . '&edit_scene=0') ?>"
       style="background:var(--green-700); color:white; padding:8px 16px; border-radius:8px; font-size:13px; font-weight:700; text-decoration:none;">
      + Thêm cảnh mới
    </a>
  </h3>

  <?php if (empty($scenes) && !$editingScene): ?>
    <p style="color:#94a3b8; text-align:center; padding:30px;">Chưa có cảnh nào. Hãy thêm cảnh đầu tiên!</p>
  <?php else: ?>
    <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap:14px;">
      <?php foreach ($scenes as $scene): ?>
      <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:14px; overflow:hidden;">
        <img src="<?= e($scene['panorama_url']) ?>" alt="<?= e($scene['title']) ?>"
             style="width:100%; height:120px; object-fit:cover;"
             onerror="this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 300 120%22><rect fill=%22%23e2e8f0%22 width=%22300%22 height=%22120%22/><text x=%22150%22 y=%2265%22 text-anchor=%22middle%22 fill=%22%2394a3b8%22 font-size=%2216%22>360°</text></svg>'">
        <div style="padding:12px;">
          <div style="font-weight:700; font-size:14px; margin-bottom:4px;">
            <?= e($scene['title']) ?>
            <?php if ($scene['is_default']): ?>
              <span style="background:#fde68a; color:#92400e; font-size:10px; padding:1px 6px; border-radius:8px;">Mặc định</span>
            <?php endif; ?>
          </div>
          <div style="font-size:12px; color:#64748b; margin-bottom:8px;">
            Key: <?= e($scene['scene_key']) ?> | Thứ tự: <?= $scene['sort_order'] ?>
          </div>
          <div style="display:flex; gap:6px;">
            <a href="<?= url('/admin/virtual_tours.php?dest=' . $selectedDest . '&edit_scene=' . $scene['id']) ?>"
               style="flex:1; text-align:center; padding:6px; background:#e0e7ff; color:#3730a3; border-radius:6px; font-size:12px; font-weight:600; text-decoration:none;">
              ✏️ Sửa
            </a>
            <a href="<?= url('/admin/virtual_tours.php?dest=' . $selectedDest . '&delete_scene=' . $scene['id']) ?>"
               onclick="return confirm('Xoá cảnh này?')"
               style="flex:1; text-align:center; padding:6px; background:#fee2e2; color:#991b1b; border-radius:6px; font-size:12px; font-weight:600; text-decoration:none;">
              🗑️ Xoá
            </a>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<!-- ── Form thêm/sửa Scene ── -->
<?php if (isset($_GET['edit_scene'])): ?>
<div class="form-box" style="margin-top:20px;">
  <h3><?= $editingScene ? '✏️ Sửa cảnh: ' . e($editingScene['title']) : '➕ Thêm cảnh mới' ?></h3>
  <form method="POST" action="<?= url('/admin/virtual_tours.php') ?>">
    <input type="hidden" name="save_scene" value="1">
    <input type="hidden" name="scene_id" value="<?= $editingScene['id'] ?? 0 ?>">
    <input type="hidden" name="destination_id" value="<?= $selectedDest ?>">

    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:14px;">
      <div>
        <label style="font-weight:600; font-size:13px;">Scene Key (duy nhất)</label>
        <input type="text" name="scene_key" value="<?= e($editingScene['scene_key'] ?? '') ?>" required
               style="width:100%; padding:10px; border:1px solid #d1d5db; border-radius:8px; margin-top:4px;"
               placeholder="VD: ho_lak_1">
      </div>
      <div>
        <label style="font-weight:600; font-size:13px;">Thứ tự hiển thị</label>
        <input type="number" name="sort_order" value="<?= $editingScene['sort_order'] ?? 0 ?>"
               style="width:100%; padding:10px; border:1px solid #d1d5db; border-radius:8px; margin-top:4px;">
      </div>
      <div>
        <label style="font-weight:600; font-size:13px;">Tên cảnh (Tiếng Việt)</label>
        <input type="text" name="title" value="<?= e($editingScene['title'] ?? '') ?>" required
               style="width:100%; padding:10px; border:1px solid #d1d5db; border-radius:8px; margin-top:4px;">
      </div>
      <div>
        <label style="font-weight:600; font-size:13px;">Tên cảnh (English)</label>
        <input type="text" name="title_en" value="<?= e($editingScene['title_en'] ?? '') ?>"
               style="width:100%; padding:10px; border:1px solid #d1d5db; border-radius:8px; margin-top:4px;">
      </div>
      <div style="grid-column: span 2;">
        <label style="font-weight:600; font-size:13px;">URL ảnh Panorama 360° (equirectangular)</label>
        <input type="url" name="panorama_url" value="<?= e($editingScene['panorama_url'] ?? '') ?>" required
               style="width:100%; padding:10px; border:1px solid #d1d5db; border-radius:8px; margin-top:4px;"
               placeholder="https://example.com/panorama.jpg">
      </div>
      <div style="grid-column: span 2;">
        <label style="font-weight:600; font-size:13px;">URL ảnh Thumbnail (tùy chọn)</label>
        <input type="url" name="thumbnail_url" value="<?= e($editingScene['thumbnail_url'] ?? '') ?>"
               style="width:100%; padding:10px; border:1px solid #d1d5db; border-radius:8px; margin-top:4px;">
      </div>
      <div>
        <label style="font-weight:600; font-size:13px;">Mô tả (Tiếng Việt)</label>
        <textarea name="description" rows="3"
                  style="width:100%; padding:10px; border:1px solid #d1d5db; border-radius:8px; margin-top:4px; resize:vertical;"
        ><?= e($editingScene['description'] ?? '') ?></textarea>
      </div>
      <div>
        <label style="font-weight:600; font-size:13px;">Mô tả (English)</label>
        <textarea name="description_en" rows="3"
                  style="width:100%; padding:10px; border:1px solid #d1d5db; border-radius:8px; margin-top:4px; resize:vertical;"
        ><?= e($editingScene['description_en'] ?? '') ?></textarea>
      </div>
      <div>
        <label style="font-weight:600; font-size:13px;">Pitch (góc dọc mặc định)</label>
        <input type="number" step="0.01" name="pitch" value="<?= $editingScene['pitch'] ?? 0 ?>"
               style="width:100%; padding:10px; border:1px solid #d1d5db; border-radius:8px; margin-top:4px;">
      </div>
      <div>
        <label style="font-weight:600; font-size:13px;">Yaw (góc ngang mặc định)</label>
        <input type="number" step="0.01" name="yaw" value="<?= $editingScene['yaw'] ?? 0 ?>"
               style="width:100%; padding:10px; border:1px solid #d1d5db; border-radius:8px; margin-top:4px;">
      </div>
      <div>
        <label style="font-weight:600; font-size:13px;">HFOV (tầm nhìn)</label>
        <input type="number" name="hfov" value="<?= $editingScene['hfov'] ?? 110 ?>"
               style="width:100%; padding:10px; border:1px solid #d1d5db; border-radius:8px; margin-top:4px;">
      </div>
      <div style="display:flex; align-items:flex-end;">
        <label style="display:flex; align-items:center; gap:8px; padding:10px 0; cursor:pointer;">
          <input type="checkbox" name="is_default" <?= ($editingScene['is_default'] ?? 0) ? 'checked' : '' ?>
                 style="width:18px; height:18px;">
          <span style="font-weight:600; font-size:13px;">Đặt làm cảnh mặc định</span>
        </label>
      </div>
    </div>

    <div style="margin-top:16px; display:flex; gap:10px;">
      <button type="submit" class="btn"
              style="background:var(--green-700); color:white; border:none; padding:10px 24px; border-radius:8px; font-weight:700; cursor:pointer;">
        💾 <?= $editingScene ? 'Cập nhật' : 'Thêm cảnh' ?>
      </button>
      <a href="<?= url('/admin/virtual_tours.php?dest=' . $selectedDest) ?>"
         style="padding:10px 24px; border-radius:8px; background:#f1f5f9; color:#334155; text-decoration:none; font-weight:600;">
        Huỷ
      </a>
    </div>
  </form>
</div>
<?php endif; ?>

<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
