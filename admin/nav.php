<style>
.admin-nav {
    display: flex;
    gap: 10px;
    margin-bottom: 24px;
    flex-wrap: wrap;
    background: #ffffff;
    padding: 16px 20px;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.06);
    border: 1px solid #e2e8f0;
    align-items: center;
}
.admin-nav a {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    text-decoration: none;
    color: #334155;
    font-weight: 600;
    font-size: 14px;
    padding: 9px 16px;
    border-radius: 10px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    transition: all 0.2s ease;
}
.admin-nav a:hover {
    background: #2d6a4f !important;
    color: #ffffff !important;
    border-color: #2d6a4f !important;
}
.admin-nav a.active {
    background: #1b4332 !important;
    color: #ffffff !important;
    border-color: #1b4332 !important;
    box-shadow: 0 4px 12px rgba(27, 67, 50, 0.25);
}
.admin-nav a.nav-logout {
    margin-left: auto;
    background: #fef2f2;
    color: #991b1b;
    border-color: #fecaca;
}
.admin-nav a.nav-logout:hover {
    background: #dc2626 !important;
    color: #ffffff !important;
    border-color: #dc2626 !important;
}
</style>
<?php
$currentPath = basename($_SERVER['SCRIPT_NAME']);
?>
<div class="admin-nav">
    <a href="<?= url('/admin/index.php') ?>" class="<?= $currentPath == 'index.php' ? 'active' : '' ?>">📊 <?= __('admin_nav_dashboard') !== 'admin_nav_dashboard' ? __('admin_nav_dashboard') : 'Tổng quan' ?></a>
    <a href="<?= url('/admin/dashboard_ai.php') ?>" class="<?= $currentPath == 'dashboard_ai.php' ? 'active' : '' ?>">🤖 OneTrip AI</a>
    <a href="<?= url('/admin/users.php') ?>" class="<?= $currentPath == 'users.php' ? 'active' : '' ?>">👥 <?= __('admin_nav_users') !== 'admin_nav_users' ? __('admin_nav_users') : 'Người dùng' ?></a>
    <a href="<?= url('/admin/destinations.php') ?>" class="<?= $currentPath == 'destinations.php' ? 'active' : '' ?>">📍 <?= __('admin_nav_destinations') !== 'admin_nav_destinations' ? __('admin_nav_destinations') : 'Điểm đến' ?></a>
    <a href="<?= url('/admin/categories.php') ?>" class="<?= $currentPath == 'categories.php' ? 'active' : '' ?>">🏷️ <?= __('admin_nav_categories') !== 'admin_nav_categories' ? __('admin_nav_categories') : 'Danh mục' ?></a>
    <a href="<?= url('/admin/articles.php') ?>" class="<?= $currentPath == 'articles.php' ? 'active' : '' ?>">📝 <?= __('admin_nav_articles') !== 'admin_nav_articles' ? __('admin_nav_articles') : 'Cẩm nang' ?></a>
    <a href="<?= url('/admin/contacts.php') ?>" class="<?= $currentPath == 'contacts.php' ? 'active' : '' ?>">📬 <?= __('admin_nav_contacts') !== 'admin_nav_contacts' ? __('admin_nav_contacts') : 'Liên hệ' ?></a>
    <a href="<?= url('/admin/reviews.php') ?>" class="<?= $currentPath == 'reviews.php' ? 'active' : '' ?>">⭐ <?= __('admin_nav_reviews') !== 'admin_nav_reviews' ? __('admin_nav_reviews') : 'Đánh giá' ?></a>
    <a href="<?= url('/admin/virtual_tours.php') ?>" class="<?= $currentPath == 'virtual_tours.php' ? 'active' : '' ?>">🌐 <?= __('admin_nav_virtual_tours') !== 'admin_nav_virtual_tours' ? __('admin_nav_virtual_tours') : 'Tour 360°' ?></a>
    <a href="<?= url('/admin/forum.php') ?>" class="<?= $currentPath == 'forum.php' ? 'active' : '' ?>">💬 <?= __('nav_forum') ?></a>
    <a href="<?= url('/admin/destinations.php?logout=1') ?>" class="nav-logout">🚪 <?= __('admin_nav_logout') !== 'admin_nav_logout' ? __('admin_nav_logout') : 'Đăng xuất' ?></a>
</div>