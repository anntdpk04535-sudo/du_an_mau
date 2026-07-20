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
    <a href="<?= url('/admin/index.php') ?>" class="<?= $currentPath == 'index.php' ? 'active' : '' ?>">📊 <?= __('admin_nav_dashboard') ?></a>
    <a href="<?= url('/admin/dashboard_ai.php') ?>" class="<?= $currentPath == 'dashboard_ai.php' ? 'active' : '' ?>" style="background:var(--green-100); color:var(--green-900);"><?= __('admin_nav_dashboard_ai') ?></a>
    <a href="<?= url('/admin/users.php') ?>" class="<?= $currentPath == 'users.php' ? 'active' : '' ?>">👥 <?= __('admin_nav_users') ?></a>
    <a href="<?= url('/admin/destinations.php') ?>" class="<?= $currentPath == 'destinations.php' ? 'active' : '' ?>">📍 <?= __('admin_nav_destinations') ?></a>
    <a href="<?= url('/admin/categories.php') ?>" class="<?= $currentPath == 'categories.php' ? 'active' : '' ?>">🏷️ <?= __('admin_nav_categories') ?></a>
    <a href="<?= url('/admin/articles.php') ?>" class="<?= $currentPath == 'articles.php' ? 'active' : '' ?>">📝 <?= __('admin_nav_articles') ?></a>
    <a href="<?= url('/admin/contacts.php') ?>" class="<?= $currentPath == 'contacts.php' ? 'active' : '' ?>">📬 <?= __('admin_nav_contacts') ?></a>
    <a href="<?= url('/admin/reviews.php') ?>" class="<?= $currentPath == 'reviews.php' ? 'active' : '' ?>">⭐ <?= __('admin_nav_reviews') ?></a>
    <a href="<?= url('/admin/virtual_tours.php') ?>" class="<?= $currentPath == 'virtual_tours.php' ? 'active' : '' ?>">🌐 <?= __('admin_nav_virtual_tours') ?></a>
    <a href="<?= url('/admin/forum.php') ?>" class="<?= $currentPath == 'forum.php' ? 'active' : '' ?>">💬 <?= __('nav_forum') ?></a>
    <a href="<?= url('/admin/destinations.php?logout=1') ?>" style="margin-left: auto; background: #fee2e2; color: #991b1b;"><?= __('admin_nav_logout') ?></a>
</div>