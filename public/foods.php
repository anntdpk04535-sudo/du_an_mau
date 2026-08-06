<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/content_helpers.php';
$pageTitle = 'Ẩm thực địa phương';
$db = getDB();
$keyword = trim((string)($_GET['q'] ?? ''));
$type = in_array($_GET['type'] ?? '', ['dish', 'restaurant', 'cafe'], true) ? $_GET['type'] : '';
$region = in_array($_GET['region'] ?? '', ['east', 'west'], true) ? $_GET['region'] : '';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 12;
$where = ["f.status='published'"];
$params = [];
if ($keyword !== '') { $where[] = '(f.name LIKE ? OR f.address LIKE ? OR f.description LIKE ?)'; array_push($params, "%$keyword%", "%$keyword%", "%$keyword%"); }
if ($type !== '') { $where[] = 'f.entity_type = ?'; $params[] = $type; }
if ($region !== '') { $where[] = '(f.region = ? OR (f.region IS NULL AND d.region = ?))'; $params[] = $region; $params[] = $region; }
$whereSql = implode(' AND ', $where);
$count = $db->prepare("SELECT COUNT(*) FROM foods f LEFT JOIN destinations d ON d.id=f.destination_id WHERE $whereSql");
$count->execute($params); $total = (int)$count->fetchColumn();
$pages = max(1, (int)ceil($total / $limit)); $page = min($page, $pages); $offset = ($page - 1) * $limit;
$list = $db->prepare("SELECT f.*, COALESCE(f.image_url, (SELECT image_url FROM food_images fi WHERE fi.food_id=f.id ORDER BY fi.is_primary DESC,fi.sort_order,fi.id LIMIT 1)) AS card_image FROM foods f LEFT JOIN destinations d ON d.id=f.destination_id WHERE $whereSql ORDER BY f.is_featured DESC, f.name LIMIT $limit OFFSET $offset");
$list->execute($params); $foods = $list->fetchAll();
$queryParams = $_GET; unset($queryParams['page']);
include __DIR__ . '/../includes/header.php';
?><div class="catalog-page catalog-foods"><div class="section-heading-row"><div><p class="catalog-kicker">ĂN · UỐNG · GẶP GỠ</p><h1 class="section-title">🍜 Hương vị địa phương</h1><p class="section-sub">Món ăn, quán ăn và cà phê trong cơ sở dữ liệu du lịch.</p></div><a href="<?= url('/') ?>" class="btn secondary section-view-all">Về trang chủ</a></div>
<form class="catalog-filters" method="get" action="<?= url('/am-thuc') ?>"><input name="q" value="<?= e($keyword) ?>" placeholder="Tìm món, quán, địa chỉ"><select name="type"><option value="">Tất cả loại</option><?php foreach(['dish'=>'Món ăn','restaurant'=>'Quán ăn','cafe'=>'Cà phê'] as $key=>$label): ?><option value="<?= $key ?>" <?= $type===$key?'selected':'' ?>><?= $label ?></option><?php endforeach; ?></select><button class="btn" type="submit">Lọc</button></form>
<div class="pills region-pills"><a class="pill <?= $region===''?'active':'' ?>" href="<?= url('/am-thuc') . ($keyword||$type ? '?' . e(http_build_query(array_filter(['q'=>$keyword,'type'=>$type]))) : '') ?>">Tất cả khu vực</a><?php foreach(['east'=>'Đông Đắk Lắk (Phú Yên)','west'=>'Tây Đắk Lắk (Đắk Lắk)'] as $key=>$label): $p=$queryParams; $p['region']=$key; ?><a class="pill <?= $region===$key?'active':'' ?>" href="<?= url('/am-thuc') . '?' . e(http_build_query($p)) ?>"><?= $label ?></a><?php endforeach; ?></div>

<?php if ($pages > 1): ?><nav class="catalog-pagination" aria-label="Phân trang ẩm thực"><span>Trang <?= $page ?> / <?= $pages ?> · <?= $total ?> kết quả</span><?php $p=$queryParams; $p['page']=max(1,$page-1); ?><a class="<?= $page<=1?'is-disabled':'' ?>" href="<?= url('/am-thuc') . '?' . e(http_build_query($p)) ?>">‹ Trước</a><?php for($i=1;$i<=$pages;$i++): $p=$queryParams; $p['page']=$i; ?><a class="<?= $i===$page?'is-current':'' ?>" href="<?= url('/am-thuc') . '?' . e(http_build_query($p)) ?>"><?= $i ?></a><?php endfor; $p=$queryParams; $p['page']=min($pages,$page+1); ?><a class="<?= $page>=$pages?'is-disabled':'' ?>" href="<?= url('/am-thuc') . '?' . e(http_build_query($p)) ?>">Sau ›</a></nav><?php endif; ?>
<div class="grid catalog-grid"><?php if (!$foods): ?><div class="catalog-empty"><span class="catalog-empty-mark">⌁</span><h2>Chưa tìm thấy hương vị phù hợp</h2><p>Thử tìm theo món, quán, cà phê hoặc đổi khu vực Đông/Tây.</p><a class="btn secondary" href="<?= url('/am-thuc') ?>">Xóa bộ lọc</a></div><?php endif; ?><?php foreach ($foods as $food): ?><article class="card catalog-card"><div class="card-img"><?php if (!empty($food['card_image'])): ?><img src="<?= url($food['card_image']) ?>" alt="<?= e($food['name']) ?>" loading="lazy" style="width:100%;height:100%;object-fit:cover" onerror="this.onerror=null;this.src='<?= url('/assets/images/placeholder.svg') ?>';"><?php else: ?><span class="catalog-no-image">🍜</span><?php endif; ?></div><div class="card-body"><h3><?= e($food['name']) ?></h3><p><?= e($food['address'] ?? 'Đắk Lắk') ?></p><span class="badge"><?= e($food['entity_type']) ?></span><?php if (!empty($food['is_featured'])): ?><span class="badge" style="background:#fef3c7; color:#b45309; border:1px solid #fde68a;">🌟 Nổi bật</span><?php endif; ?></div></article><?php endforeach; ?></div><p class="catalog-footer-note">Hiển thị <?= count($foods) ?> / <?= $total ?> kết quả · Ưu tiên bản ghi có nguồn tham chiếu</p></div>


<?php include __DIR__ . '/../includes/footer.php'; ?>
