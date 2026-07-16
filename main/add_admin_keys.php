<?php
$vi_keys = [
    'admin_nav_dashboard' => 'Tổng quan',
    'admin_nav_users' => 'Người dùng',
    'admin_nav_destinations' => 'Điểm đến',
    'admin_nav_categories' => 'Danh mục',
    'admin_nav_articles' => 'Cẩm nang',
    'admin_nav_contacts' => 'Liên hệ',
    'admin_nav_reviews' => 'Đánh giá',
    'admin_nav_virtual_tours' => 'Tour 360°',
    'admin_nav_logout' => 'Đăng xuất',
    
    'admin_dashboard_title' => 'Tổng quan hệ thống - Admin',
    'admin_dashboard_heading' => 'Dashboard Tổng Quan',
    'admin_total_users' => 'Tổng Người Dùng',
    'admin_total_itineraries' => 'Lượt tạo Lịch trình AI',
    'admin_total_chat_sessions' => 'Phiên Chat AI',
    'admin_top_destinations' => 'Top 5 Điểm đến được đánh giá cao nhất',
    'admin_avg_rating_label' => 'Điểm đánh giá trung bình',
];

$en_keys = [
    'admin_nav_dashboard' => 'Dashboard',
    'admin_nav_users' => 'Users',
    'admin_nav_destinations' => 'Destinations',
    'admin_nav_categories' => 'Categories',
    'admin_nav_articles' => 'Articles',
    'admin_nav_contacts' => 'Contacts',
    'admin_nav_reviews' => 'Reviews',
    'admin_nav_virtual_tours' => 'Virtual Tours',
    'admin_nav_logout' => 'Logout',
    
    'admin_dashboard_title' => 'System Dashboard - Admin',
    'admin_dashboard_heading' => 'System Dashboard',
    'admin_total_users' => 'Total Users',
    'admin_total_itineraries' => 'AI Itineraries Created',
    'admin_total_chat_sessions' => 'AI Chat Sessions',
    'admin_top_destinations' => 'Top 5 Highest Rated Destinations',
    'admin_avg_rating_label' => 'Average Rating',
];

function update_lang_file($file_path, $keys) {
    $content = file_get_contents($file_path);
    $arr_str = "";
    foreach ($keys as $k => $v) {
        $v_esc = str_replace("'", "\'", $v);
        $arr_str .= "    '$k' => '$v_esc',\n";
    }
    $content = preg_replace('/\];\s*$/', "\n$arr_str];\n", $content);
    file_put_contents($file_path, $content);
}

update_lang_file('c:\xampp\htdocs\travel_daklak\main\includes\lang_vi.php', $vi_keys);
update_lang_file('c:\xampp\htdocs\travel_daklak\main\includes\lang_en.php', $en_keys);
echo "Done";
?>
