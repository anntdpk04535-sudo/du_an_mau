<style>
.admin-nav {
    display: flex;
    gap: 15px;
    margin-bottom: 20px;
    flex-wrap: wrap;
    background: white;
    padding: 15px 20px;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}
.admin-nav a {
    text-decoration: none;
    color: var(--text-dark);
    font-weight: 600;
    padding: 8px 16px;
    border-radius: 8px;
    background: #f1f5f9;
    transition: all 0.2s;
}
.admin-nav a:hover {
    background: var(--green-700);
    color: white;
}
.admin-nav a.active {
    background: var(--green-700);
    color: white;
}
</style>
<?php
$currentPath = basename($_SERVER['SCRIPT_NAME']);
?>
<div class="admin-nav">
    <a href="<?= url('/admin/index.php') ?>" class="<?= $currentPath == 'index.php' ? 'active' : '' ?>">📊 Tổng quan</a>
    <a href="<?= url('/admin/users.php') ?>" class="<?= $currentPath == 'users.php' ? 'active' : '' ?>">👥 Người dùng</a>
    <a href="<?= url('/admin/destinations.php') ?>" class="<?= $currentPath == 'destinations.php' ? 'active' : '' ?>">📍 Điểm đến</a>
    <a href="<?= url('/admin/categories.php') ?>" class="<?= $currentPath == 'categories.php' ? 'active' : '' ?>">🏷️ Danh mục</a>
    <a href="<?= url('/admin/articles.php') ?>" class="<?= $currentPath == 'articles.php' ? 'active' : '' ?>">📝 Cẩm nang</a>
    <a href="<?= url('/admin/contacts.php') ?>" class="<?= $currentPath == 'contacts.php' ? 'active' : '' ?>">📬 Liên hệ</a>
    <a href="<?= url('/admin/reviews.php') ?>" class="<?= $currentPath == 'reviews.php' ? 'active' : '' ?>">⭐ Đánh giá</a>
    <a href="<?= url('/admin/virtual_tours.php') ?>" class="<?= $currentPath == 'virtual_tours.php' ? 'active' : '' ?>">🌐 Tour 360°</a>
    <a href="<?= url('/admin/destinations.php?logout=1') ?>" style="margin-left: auto; background: #fee2e2; color: #991b1b;">Đăng xuất</a>
</div>