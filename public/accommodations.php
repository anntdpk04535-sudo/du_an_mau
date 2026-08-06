<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/content_helpers.php';
$pageTitle = 'Nơi lưu trú';
$db = getDB();
$keyword = trim((string)($_GET['q'] ?? ''));
$type = in_array($_GET['type'] ?? '', ['homestay', 'hotel', 'resort'], true) ? $_GET['type'] : '';
$region = in_array($_GET['region'] ?? '', ['east', 'west'], true) ? $_GET['region'] : '';
$page = max(1, (int)($_GET['page'] ?? 1)); $limit = 12;
$where = ["a.status='published'"]; $params = [];
if ($keyword !== '') { $where[] = '(a.name LIKE ? OR a.address LIKE ? OR a.description LIKE ?)'; array_push($params, "%$keyword%", "%$keyword%", "%$keyword%"); }
if ($type !== '') { $where[] = 'a.accommodation_type = ?'; $params[] = $type; }
if ($region !== '') { $where[] = '(a.region = ? OR (a.region IS NULL AND d.region = ?))'; $params[] = $region; $params[] = $region; }
$whereSql = implode(' AND ', $where);
$count = $db->prepare("SELECT COUNT(*) FROM accommodations a LEFT JOIN destinations d ON d.id=a.destination_id WHERE $whereSql"); $count->execute($params); $total=(int)$count->fetchColumn();
$pages=max(1,(int)ceil($total/$limit)); $page=min($page,$pages); $offset=($page-1)*$limit;
$list=$db->prepare("SELECT a.id, a.name, a.name_en, a.slug, a.accommodation_type, a.address, a.price_min, a.price_max, a.contact_phone, a.description, a.region, a.is_featured, COALESCE(a.image_url, (SELECT image_url FROM accommodation_images ai WHERE ai.accommodation_id=a.id ORDER BY ai.is_primary DESC,ai.sort_order,ai.id LIMIT 1)) AS card_image FROM accommodations a LEFT JOIN destinations d ON d.id=a.destination_id WHERE $whereSql ORDER BY a.is_featured DESC, a.name LIMIT $limit OFFSET $offset"); $list->execute($params); $stays=$list->fetchAll();
$queryParams=$_GET; unset($queryParams['page']);
include __DIR__ . '/../includes/header.php';
?><div class="catalog-page catalog-stays"><div class="section-heading-row"><div><p class="catalog-kicker">NGỦ · NGHỈ · SỐNG CHẬM</p><h1 class="section-title">🛏️ Nơi nghỉ đáng nhớ</h1><p class="section-sub">Các cơ sở lưu trú đã được đồng bộ và có nguồn tham chiếu.</p></div><a href="<?= url('/') ?>" class="btn secondary section-view-all">Về trang chủ</a></div>
<form class="catalog-filters" method="get" action="<?= url('/luu-tru') ?>"><input name="q" value="<?= e($keyword) ?>" placeholder="Tìm khách sạn, homestay, địa chỉ"><select name="type"><option value="">Tất cả loại</option><?php foreach(['hotel'=>'Khách sạn','homestay'=>'Homestay','resort'=>'Resort'] as $key=>$label): ?><option value="<?= $key ?>" <?= $type===$key?'selected':'' ?>><?= $label ?></option><?php endforeach; ?></select><button class="btn" type="submit">Lọc</button></form>
<div class="pills region-pills"><a class="pill <?= $region===''?'active':'' ?>" href="<?= url('/luu-tru') . ($keyword||$type ? '?' . e(http_build_query(array_filter(['q'=>$keyword,'type'=>$type]))) : '') ?>">Tất cả khu vực</a><?php foreach(['east'=>'Đông Đắk Lắk (Phú Yên)','west'=>'Tây Đắk Lắk (Đắk Lắk)'] as $key=>$label): $p=$queryParams; $p['region']=$key; ?><a class="pill <?= $region===$key?'active':'' ?>" href="<?= url('/luu-tru') . '?' . e(http_build_query($p)) ?>"><?= $label ?></a><?php endforeach; ?></div>

<?php if ($pages > 1): ?><nav class="catalog-pagination" aria-label="Phân trang lưu trú"><span>Trang <?= $page ?> / <?= $pages ?> · <?= $total ?> kết quả</span><?php $p=$queryParams; $p['page']=max(1,$page-1); ?><a class="<?= $page<=1?'is-disabled':'' ?>" href="<?= url('/luu-tru') . '?' . e(http_build_query($p)) ?>">‹ Trước</a><?php for($i=1;$i<=$pages;$i++): $p=$queryParams; $p['page']=$i; ?><a class="<?= $i===$page?'is-current':'' ?>" href="<?= url('/luu-tru') . '?' . e(http_build_query($p)) ?>"><?= $i ?></a><?php endfor; $p=$queryParams; $p['page']=min($pages,$page+1); ?><a class="<?= $page>=$pages?'is-disabled':'' ?>" href="<?= url('/luu-tru') . '?' . e(http_build_query($p)) ?>">Sau ›</a></nav><?php endif; ?>
<div class="grid catalog-grid"><?php if (!$stays): ?><div class="catalog-empty"><span class="catalog-empty-mark">⌁</span><h2>Chưa tìm thấy nơi nghỉ phù hợp</h2><p>Thử đổi loại hình, từ khóa hoặc tuyến Đông/Tây Đắk Lắk.</p><a class="btn secondary" href="<?= url('/luu-tru') ?>">Xóa bộ lọc</a></div><?php endif; ?><?php foreach ($stays as $stay): ?><article class="card catalog-card"><div class="card-img"><?php if (!empty($stay['card_image'])): ?><img src="<?= url($stay['card_image']) ?>" alt="<?= e($stay['name']) ?>" loading="lazy" style="width:100%;height:100%;object-fit:cover" onerror="this.onerror=null;this.src='<?= url('/assets/images/placeholder.svg') ?>';"><?php else: ?><span class="catalog-no-image">🛏️</span><?php endif; ?></div><div class="card-body"><h3><?= e($stay['name']) ?></h3><p><?= e($stay['address'] ?? 'Đắk Lắk') ?></p><span class="badge"><?= e($stay['accommodation_type']) ?></span><?php if (!empty($stay['is_featured'])): ?><span class="badge" style="background:#fef3c7; color:#b45309; border:1px solid #fde68a;">🌟 Nổi bật</span><?php endif; ?><?php if (!empty($stay['price_min'])): ?><span class="catalog-price">Từ <?= number_format((float)$stay['price_min'], 0, ',', '.') ?>đ</span><?php endif; ?><?php if (!empty($stay['contact_phone'])): ?><a href="tel:<?= e($stay['contact_phone']) ?>" class="stay-phone-link" onclick="event.stopPropagation();">📞 <?= e($stay['contact_phone']) ?></a><?php endif; ?></div></article><?php endforeach; ?></div><p class="catalog-footer-note">Hiển thị <?= count($stays) ?> / <?= $total ?> cơ sở · Chỉ hiển thị bản ghi đã duyệt</p></div>


<?php include __DIR__ . '/../includes/footer.php'; ?>
