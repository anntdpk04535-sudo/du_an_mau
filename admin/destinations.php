<?php
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();
$pageTitle = __('admin_destinations_title');
$db = getDB();

// Xử lý xoá
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
  $stmt = $db->prepare("DELETE FROM destinations WHERE id = ?");
  $stmt->execute([(int) $_POST['delete_id']]);
  header('Location: ' . url('/admin/destinations.php'));
  exit;
}

// Xử lý thêm/sửa
$editing = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $id = (int) ($_POST['id'] ?? 0);
  $name = trim($_POST['name'] ?? '');
  $slug = trim($_POST['slug'] ?? '') ?: strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name));
  $shortDesc = trim($_POST['short_desc'] ?? '');
  $description = trim($_POST['description'] ?? '');
  $categoryId = (int) ($_POST['category_id'] ?? 0) ?: null;
  $avgHours = (float) ($_POST['avg_visit_hours'] ?? 2);
  $priceLevel = $_POST['price_level'] ?? 'low';
  $tags = trim($_POST['tags'] ?? '');
  $imageUrl = trim($_POST['image_url'] ?? '');
  
  // Handle Image Upload
  if (isset($_FILES['image_upload']) && $_FILES['image_upload']['error'] === UPLOAD_ERR_OK) {
      $ext = pathinfo($_FILES['image_upload']['name'], PATHINFO_EXTENSION);
      $filename = time() . '_' . uniqid() . '.' . $ext;
      $destPath = __DIR__ . '/../assets/images/uploads/' . $filename;
      if (move_uploaded_file($_FILES['image_upload']['tmp_name'], $destPath)) {
          $imageUrl = url('/assets/images/uploads/' . $filename);
      }
  }

  $latitude = ($_POST['latitude'] ?? '') !== '' ? (float) $_POST['latitude'] : null;
  $longitude = ($_POST['longitude'] ?? '') !== '' ? (float) $_POST['longitude'] : null;

  if (empty($name)) {
    $error = 'Tên điểm đến không được để trống.';
  } else {
    if ($id > 0) {
      // ✅ $id > 0 → đang SỬA → dùng UPDATE
      $stmt = $db->prepare(
        "UPDATE destinations SET name=?, slug=?, short_desc=?, description=?, category_id=?, avg_visit_hours=?, price_level=?, tags=?, image_url=?, latitude=?, longitude=? WHERE id=?"
      );
      $stmt->execute([$name, $slug, $shortDesc, $description, $categoryId, $avgHours, $priceLevel, $tags, $imageUrl, $latitude, $longitude, $id]);
    } else {
      // ✅ $id == 0 → đang THÊM MỚI → dùng INSERT
      $stmt = $db->prepare(
        "INSERT INTO destinations (name, slug, short_desc, description, category_id, avg_visit_hours, price_level, tags, image_url, latitude, longitude) VALUES (?,?,?,?,?,?,?,?,?,?,?)"
      );
      $stmt->execute([$name, $slug, $shortDesc, $description, $categoryId, $avgHours, $priceLevel, $tags, $imageUrl, $latitude, $longitude]);
    }

    header('Location: ' . url('/admin/destinations.php'));
    exit;
  }
}

if (isset($_GET['edit'])) {
  $stmt = $db->prepare("SELECT * FROM destinations WHERE id = ?");
  $stmt->execute([(int) $_GET['edit']]);
  $editing = $stmt->fetch();
}

$categories = getAllCategories();

// Pagination for destinations
$page = (int)($_GET['page'] ?? 1);
if ($page < 1) $page = 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$totalDestinations = $db->query("SELECT COUNT(*) FROM destinations")->fetchColumn();
$totalPages = ceil($totalDestinations / $limit);

$stmt = $db->prepare("SELECT d.*, c.name as category_name FROM destinations d LEFT JOIN categories c ON d.category_id = c.id ORDER BY d.id DESC LIMIT ? OFFSET ?");
$stmt->bindValue(1, $limit, PDO::PARAM_INT);
$stmt->bindValue(2, $offset, PDO::PARAM_INT);
$stmt->execute();
$destinations = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<h1 class="section-title"><?= __('admin_destinations_heading') ?></h1>
<?php include __DIR__ . '/nav.php'; ?>
<?php if (isset($_GET['logout'])) {
  unset($_SESSION['user']);
  header('Location: ' . url('/admin/login.php'));
  exit;
} ?>

<div class="form-box">
  <h3><?= $editing ? __('admin_destinations_edit') : __('admin_destinations_add') ?></h3>
  <?php if (!empty($error)): ?>
    <div style="background:#fee2e2; color:#991b1b; padding:12px; border-radius:8px; margin-bottom:16px; font-size:14px;">
      ❌ <?= e($error) ?>
    </div>
  <?php endif; ?>
  <form method="post" enctype="multipart/form-data">
    <input type="hidden" name="id" value="<?= e((string) ($editing['id'] ?? '')) ?>">
    <div class="form-group">
      <label><?= __('admin_destinations_name') ?></label>
      <input type="text" name="name" required value="<?= e($editing['name'] ?? '') ?>">
    </div>
    <div class="form-group">
      <label><?= __('admin_destinations_slug') ?></label>
      <input type="text" name="slug" value="<?= e($editing['slug'] ?? '') ?>">
    </div>
    <div class="form-group">
      <label><?= __('admin_destinations_category') ?></label>
      <select name="category_id">
        <option value=""><?= __('admin_destinations_cat_select') ?></option>
        <?php foreach ($categories as $c): ?>
          <option value="<?= e((string) $c['id']) ?>" <?= ($editing['category_id'] ?? null) == $c['id'] ? 'selected' : '' ?>>
            <?= e($c['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group">
      <label><?= __('admin_destinations_short_desc') ?></label>
      <input type="text" name="short_desc" value="<?= e($editing['short_desc'] ?? '') ?>">
    </div>
    <div class="form-group">
      <label><?= __('admin_destinations_desc') ?></label>
      <textarea name="description" rows="4"><?= e($editing['description'] ?? '') ?></textarea>
    </div>
    <div class="form-group">
      <label><?= __('admin_destinations_time') ?></label>
      <input type="number" step="0.5" name="avg_visit_hours"
        value="<?= e((string) ($editing['avg_visit_hours'] ?? 2)) ?>">
    </div>
    <div class="form-group">
      <label><?= __('admin_destinations_price') ?></label>
      <select name="price_level">
        <?php foreach (['free', 'low', 'medium', 'high'] as $pl): ?>
          <option value="<?= $pl ?>" <?= ($editing['price_level'] ?? '') === $pl ? 'selected' : '' ?>><?= $pl ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group">
      <label><?= __('admin_destinations_tags') ?></label>
      <input type="text" name="tags" value="<?= e($editing['tags'] ?? '') ?>">
    </div>
    <div class="form-group">
      <label><?= __('admin_destinations_image') ?></label>
      <div style="display:flex; gap:10px; margin-bottom:10px;">
          <input type="file" name="image_upload" accept="image/*" style="flex:1;">
          <input type="text" name="image_url" value="<?= e($editing['image_url'] ?? '') ?>" placeholder="<?= __('admin_destinations_image_ph') ?>" style="flex:1;">
      </div>
      <?php if (!empty($editing['image_url'])): ?>
        <img src="<?= e($editing['image_url']) ?>" style="margin-top:8px;max-width:200px;border-radius:8px;">
      <?php endif; ?>
    </div>

    <div style="display: flex; gap: 20px; flex-wrap: wrap;">
      <div class="form-group" style="flex: 1; min-width: 200px;">
        <label><?= __('admin_destinations_lat') ?></label>
        <input type="number" step="any" name="latitude" id="lat-input" value="<?= e((string) ($editing['latitude'] ?? '')) ?>" placeholder="Vd: 12.6667">
      </div>
      <div class="form-group" style="flex: 1; min-width: 200px;">
        <label><?= __('admin_destinations_lng') ?></label>
        <input type="number" step="any" name="longitude" id="lng-input" value="<?= e((string) ($editing['longitude'] ?? '')) ?>" placeholder="Vd: 108.0500">
      </div>
    </div>

    <div class="form-group">
      <label><?= __('admin_destinations_map') ?></label>
      <div id="admin-map" style="height: 300px; border-radius: 8px; border: 1px solid #ddd; z-index: 1;"></div>
      <p style="font-size: 12px; color: #666; margin-top: 4px;"><?= __('admin_destinations_map_hint') ?></p>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
      const defaultLat = 12.6667;
      const defaultLng = 108.0500;
      
      const latInput = document.getElementById('lat-input');
      const lngInput = document.getElementById('lng-input');
      
      let latVal = latInput.value;
      let lngVal = lngInput.value;
      
      const mapLat = latVal ? parseFloat(latVal) : defaultLat;
      const mapLng = lngVal ? parseFloat(lngVal) : defaultLng;
      const zoom = latVal && lngVal ? 14 : 11;
      
      const map = L.map('admin-map').setView([mapLat, mapLng], zoom);
      
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
      }).addTo(map);
      
      let marker;
      if (latVal && lngVal) {
        marker = L.marker([mapLat, mapLng], { draggable: true }).addTo(map);
      }
      
      function updateCoords(lat, lng) {
        latInput.value = lat.toFixed(6);
        lngInput.value = lng.toFixed(6);
      }
      
      map.on('click', function(e) {
        const lat = e.latlng.lat;
        const lng = e.latlng.lng;
        
        if (marker) {
          marker.setLatLng(e.latlng);
        } else {
          marker = L.marker(e.latlng, { draggable: true }).addTo(map);
          setupMarkerEvents(marker);
        }
        updateCoords(lat, lng);
      });
      
      function setupMarkerEvents(m) {
        m.on('dragend', function(evt) {
          const pos = evt.target.getLatLng();
          updateCoords(pos.lat, pos.lng);
        });
      }
      
      if (marker) {
        setupMarkerEvents(marker);
      }
    });
    </script>

    <button type="submit" class="btn"><?= $editing ? __('admin_destinations_save') : __('admin_destinations_submit') ?></button>
  </form>
</div>

<h3 class="section-title"><?= __('admin_destinations_list') ?></h3>
<table style="width:100%;background:white;border-radius:14px;overflow:hidden;border-collapse:collapse;">
  <tr style="background:#f1f1f1;text-align:left;">
    <th style="padding:10px;"><?= __('admin_destinations_th_name') ?></th>
    <th style="padding:10px;"><?= __('admin_destinations_category') ?></th>
    <th style="padding:10px;"><?= __('admin_destinations_th_rating') ?></th>
    <th style="padding:10px;"><?= __('admin_destinations_th_action') ?></th>
  </tr>
  <?php foreach ($destinations as $d): ?>
    <tr style="border-top:1px solid #eee;">
      <td style="padding:10px;"><?= e($d['name']) ?></td>
      <td style="padding:10px;"><?= e((string) ($d['category_name'] ?? '-')) ?></td>
      <td style="padding:10px;"><?= e((string) $d['rating']) ?></td>
      <td style="padding:10px;">
        <a href="<?= url('/admin/destinations.php') ?>?edit=<?= e((string) $d['id']) ?>"><?= __('admin_destinations_edit_btn') ?></a> |
        <form method="post" style="display:inline;">
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="delete_id" value="<?= e((string) $d['id']) ?>">
          <button type="submit" onclick="return confirm('<?= __('admin_destinations_delete_confirm') ?>')" style="background:none; border:none; color:inherit; text-decoration:underline; font:inherit; cursor:pointer; padding:0;"><?= __('admin_destinations_delete_btn') ?></button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
</table>

<?php if ($totalPages > 1): ?>
<div style="display:flex; gap:10px; justify-content:center; margin-top:20px;">
    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <a href="?page=<?= $i ?>" class="btn <?= $i === $page ? 'secondary' : '' ?>" style="padding: 6px 12px; font-size:14px;"><?= $i ?></a>
    <?php endfor; ?>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>