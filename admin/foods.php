<?php
require_once __DIR__ . '/../includes/content_helpers.php';
requireAdmin();
$db = getDB();
$editing = null;
$error = '';

try {
    // Quick Toggle Featured
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_featured') {
        $foodId = (int)$_POST['id'];
        $currentVal = (int)$_POST['current_val'];
        $newVal = $currentVal ? 0 : 1;
        $db->prepare("UPDATE foods SET is_featured = ? WHERE id = ?")->execute([$newVal, $foodId]);
        header('Location: ' . url('/admin/foods.php?page=' . ($_GET['page'] ?? 1)));
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['action'])) {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim((string)($_POST['name'] ?? ''));
        if ($name === '') throw new RuntimeException('Tên món/quán là bắt buộc.');
        $slug = trim((string)($_POST['slug'] ?? '')) ?: strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', iconv('UTF-8', 'ASCII//TRANSLIT', $name)), '-'));
        $imageUrl = trim((string)($_POST['image_url'] ?? ''));
        $isFeatured = isset($_POST['is_featured']) ? 1 : 0;

        // Upload local image if provided
        if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES['image_file']['name'], PATHINFO_EXTENSION);
            $filename = time() . '_food_' . uniqid() . '.' . $ext;
            $destPath = __DIR__ . '/../assets/images/uploads/' . $filename;
            if (move_uploaded_file($_FILES['image_file']['tmp_name'], $destPath)) {
                $imageUrl = url('/assets/images/uploads/' . $filename);
            }
        }

        $data = [
            $name,
            trim((string)($_POST['name_en'] ?? '')),
            $slug,
            $_POST['entity_type'] ?? 'dish',
            trim((string)($_POST['description'] ?? '')),
            (int)($_POST['destination_id'] ?? 0) ?: null,
            trim((string)($_POST['address'] ?? '')),
            ($_POST['latitude'] ?? '') !== '' ? (float)$_POST['latitude'] : null,
            ($_POST['longitude'] ?? '') !== '' ? (float)$_POST['longitude'] : null,
            (float)($_POST['price_min'] ?? 0) ?: null,
            (float)($_POST['price_max'] ?? 0) ?: null,
            $imageUrl,
            $isFeatured,
            trim((string)($_POST['source_url'] ?? '')),
            $_POST['last_verified_at'] ?: null
        ];
        // Admin nhập tay tọa độ → đánh dấu geo_source='manual' để backfill không ghi đè.
        $geoManual = ($_POST['latitude'] ?? '') !== '' && ($_POST['longitude'] ?? '') !== '' && columnExists($db, 'foods', 'geo_source');

        if ($id) {
            $stmt = $db->prepare('UPDATE foods SET name=?,name_en=?,slug=?,entity_type=?,description=?,destination_id=?,address=?,latitude=?,longitude=?,price_min=?,price_max=?,image_url=?,is_featured=?,source_url=?,last_verified_at=? WHERE id=?');
            $stmt->execute([...$data, $id]);
            if ($geoManual) $db->prepare("UPDATE foods SET geo_source='manual' WHERE id=?")->execute([$id]);
        } else {
            $stmt = $db->prepare('INSERT INTO foods(name,name_en,slug,entity_type,description,destination_id,address,latitude,longitude,price_min,price_max,image_url,is_featured,source_url,last_verified_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
            $stmt->execute($data);
            if ($geoManual) $db->prepare("UPDATE foods SET geo_source='manual' WHERE id=?")->execute([(int)$db->lastInsertId()]);
        }
        header('Location: ' . url('/admin/foods.php')); exit;
    }

    if (isset($_GET['delete'])) { 
        $db->prepare('DELETE FROM foods WHERE id=?')->execute([(int)$_GET['delete']]); 
        header('Location: '.url('/admin/foods.php')); exit; 
    }
    if (isset($_GET['edit'])) { 
        $stmt=$db->prepare('SELECT * FROM foods WHERE id=?'); 
        $stmt->execute([(int)$_GET['edit']]); 
        $editing=$stmt->fetch(); 
    }
} catch (Throwable $e) { $error = $e->getMessage(); }

$destinations = $db->query('SELECT id,name FROM destinations ORDER BY name')->fetchAll();

// Pagination for foods
$page = (int)($_GET['page'] ?? 1);
if ($page < 1) $page = 1;
$limit = 15;
$offset = ($page - 1) * $limit;

$totalFoods = $db->query("SELECT COUNT(*) FROM foods")->fetchColumn();
$totalPages = ceil($totalFoods / $limit);

$stmt = $db->prepare('SELECT f.*,d.name AS destination_name FROM foods f LEFT JOIN destinations d ON d.id=f.destination_id ORDER BY f.id DESC LIMIT ? OFFSET ?');
$stmt->bindValue(1, $limit, PDO::PARAM_INT);
$stmt->bindValue(2, $offset, PDO::PARAM_INT);
$stmt->execute();
$foods = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>
<?php include __DIR__ . '/nav.php'; ?>
<h1 class="section-title">🍜 Quản lý Ẩm thực địa phương</h1>

<?php if ($error): ?><div class="form-box" style="color:#991b1b;">❌ <?= e($error) ?></div><?php endif; ?>

<div class="form-box">
  <h3><?= $editing ? 'Sửa món ăn / nhà hàng' : 'Thêm món ăn / nhà hàng mới' ?></h3>
  <form method="post" enctype="multipart/form-data">
    <input type="hidden" name="id" value="<?= e((string)($editing['id'] ?? 0)) ?>">
    <div style="display:flex; gap:15px; flex-wrap:wrap;">
      <div class="form-group" style="flex:2; min-width:240px;"><label>Tên món/quán</label><input name="name" required value="<?= e($editing['name'] ?? '') ?>"></div>
      <div class="form-group" style="flex:1; min-width:180px;"><label>Tên tiếng Anh</label><input name="name_en" value="<?= e($editing['name_en'] ?? '') ?>"></div>
    </div>

    <div style="display:flex; gap:15px; flex-wrap:wrap;">
      <div class="form-group" style="flex:1; min-width:180px;"><label>Loại</label><select name="entity_type"><?php foreach(['dish'=>'Món ăn đặc sản','restaurant'=>'Nhà hàng / Quán ăn','cafe'=>'Quán Cà phê / Trà'] as $v=>$label): ?><option value="<?= $v ?>" <?= ($editing['entity_type']??'dish')===$v?'selected':'' ?>><?= $label ?></option><?php endforeach; ?></select></div>
      <div class="form-group" style="flex:1; min-width:180px;"><label>Điểm đến liên kết</label><select name="destination_id"><option value="">— Không chọn —</option><?php foreach($destinations as $d): ?><option value="<?= $d['id'] ?>" <?= ($editing['destination_id']??null)==$d['id']?'selected':'' ?>><?= e($d['name']) ?></option><?php endforeach; ?></select></div>
    </div>

    <div class="form-group"><label>Mô tả</label><textarea name="description" rows="3"><?= e($editing['description'] ?? '') ?></textarea></div>
    <div class="form-group"><label>Địa chỉ</label><input name="address" value="<?= e($editing['address'] ?? '') ?>"></div>

    <div style="display:flex;gap:15px">
      <div class="form-group" style="flex:1"><label>Vĩ độ (latitude)</label><input type="number" step="any" name="latitude" placeholder="VD: 12.6667" value="<?= e((string)($editing['latitude'] ?? '')) ?>"></div>
      <div class="form-group" style="flex:1"><label>Kinh độ (longitude)</label><input type="number" step="any" name="longitude" placeholder="VD: 108.0500" value="<?= e((string)($editing['longitude'] ?? '')) ?>"></div>
    </div>

    <div style="display:flex;gap:15px">
      <div class="form-group" style="flex:1"><label>Giá từ (VNĐ)</label><input type="number" name="price_min" value="<?= e((string)($editing['price_min']??'')) ?>"></div>
      <div class="form-group" style="flex:1"><label>Giá đến (VNĐ)</label><input type="number" name="price_max" value="<?= e((string)($editing['price_max']??'')) ?>"></div>
    </div>

    <div class="form-group">
      <label>Hình ảnh món ăn/quán</label>
      <div style="display:flex; gap:10px;">
        <input type="file" name="image_file" accept="image/*" style="flex:1;">
        <input type="url" name="image_url" placeholder="Hoặc dán URL ảnh..." value="<?= e($editing['image_url'] ?? '') ?>" style="flex:1;">
      </div>
      <?php if (!empty($editing['image_url'])): ?>
        <img src="<?= url($editing['image_url']) ?>" style="margin-top:8px; width:120px; height:80px; object-fit:cover; border-radius:8px;" onerror="this.onerror=null;this.src='<?= url('/assets/images/placeholder.svg') ?>';">
      <?php endif; ?>
    </div>

    <div class="form-group" style="display:flex; align-items:center; gap:10px; margin:15px 0;">
      <input type="checkbox" name="is_featured" id="is_feat_food" value="1" <?= !empty($editing['is_featured']) ? 'checked' : '' ?> style="width:20px; height:20px;">
      <label for="is_feat_food" style="margin:0; font-weight:700; color:var(--basalt-red); cursor:pointer;">🌟 Nổi bật (Hiển thị trang chính Ẩm thực)</label>
    </div>

    <button class="btn" type="submit"><?= $editing?'Lưu cập nhật':'Thêm mới' ?></button>
  </form>
</div>

<h3 class="section-title">Danh sách Ẩm thực (<?= $totalFoods ?> bản ghi)</h3>
<table style="width:100%;background:#fff;border-collapse:collapse;border-radius:12px;overflow:hidden;">
  <tr style="background:#f1f5f9;text-align:left;">
    <th style="padding:10px; width:70px;">Hình ảnh</th>
    <th style="padding:10px;">Tên</th>
    <th style="padding:10px;">Loại</th>
    <th style="padding:10px;">Điểm đến</th>
    <th style="padding:10px;">Nổi bật</th>
    <th style="padding:10px;">Thao tác</th>
  </tr>
  <?php foreach($foods as $f): ?>
    <tr style="border-top:1px solid #eee">
      <td style="padding:10px">
        <img src="<?= url($f['image_url'] ?: '/assets/images/placeholder.svg') ?>" style="width:50px; height:50px; object-fit:cover; border-radius:8px;" onerror="this.onerror=null;this.src='<?= url('/assets/images/placeholder.svg') ?>';">
      </td>

      <td style="padding:10px; font-weight:600;"><?= e($f['name']) ?></td>
      <td style="padding:10px"><?= e($f['entity_type']) ?></td>
      <td style="padding:10px"><?= e($f['destination_name']??'—') ?></td>
      <td style="padding:10px">
        <form method="post" style="display:inline;">
          <input type="hidden" name="action" value="toggle_featured">
          <input type="hidden" name="id" value="<?= $f['id'] ?>">
          <input type="hidden" name="current_val" value="<?= (int)$f['is_featured'] ?>">
          <button type="submit" style="background:none; border:none; cursor:pointer; font-size:16px;">
            <?= !empty($f['is_featured']) ? '🌟 <span style="font-size:12px; color:#15803d; font-weight:700;">Nổi bật</span>' : '⚪ <span style="font-size:12px; color:#94a3b8;">Thường</span>' ?>
          </button>
        </form>
      </td>
      <td style="padding:10px">
        <a href="?edit=<?= $f['id'] ?>&page=<?= $page ?>">Sửa</a> · 
        <a href="?delete=<?= $f['id'] ?>" onclick="return confirm('Xóa bản ghi này?')" style="color:#ef4444;">Xóa</a>
      </td>
    </tr>
  <?php endforeach; ?>
</table>

<?php if ($totalPages > 1): ?>
<div style="display:flex; gap:10px; justify-content:center; margin-top:20px; flex-wrap:wrap;">
    <?php for ($i = 1; $i <= min($totalPages, 15); $i++): ?>
        <a href="?page=<?= $i ?>" class="btn <?= $i === $page ? 'secondary' : '' ?>" style="padding: 6px 12px; font-size:14px;"><?= $i ?></a>
    <?php endfor; ?>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
