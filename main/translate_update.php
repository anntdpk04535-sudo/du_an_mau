<?php
$vi_append = <<<PHP
    'iti_reroute_btn' => '⛈️ Mưa lớn (Re-route)',
    'iti_suggestion' => '💡 <strong>Gợi ý:</strong> ',
    'iti_reason' => '🧐 <strong>Lý do chọn:</strong> ',
    'iti_community_impact' => '💰 <strong>Sinh kế cộng đồng:</strong> ',
    'iti_mic_blocked' => 'Trình duyệt đã chặn quyền sử dụng Micro. Vui lòng cấp quyền Micro cho trang web để sử dụng tính năng này (nhấp vào biểu tượng ổ khoá trên thanh địa chỉ).',
    'iti_mic_network' => 'Cần có kết nối internet để nhận diện giọng nói.',
    'iti_mic_error' => 'Lỗi nhận diện giọng nói: ',
    'iti_mic_try_again' => '. Vui lòng thử lại trên trình duyệt Chrome/Edge hoặc kiểm tra xem thiết bị đã có Micro chưa.',
    'iti_rerouting_loading' => '⛈️ Đang phân tích thời tiết và điều chỉnh lộ trình...',
    'iti_reroute_success' => 'Đã tự động thay đổi lịch trình cho phù hợp với trời mưa!',
    'iti_reroute_need_iti' => 'Vui lòng tạo lịch trình trước!',
    'iti_mic_title' => 'Nhập bằng giọng nói',
    'admin_nav_dashboard_ai' => '🤖 OneTrip AI',
    'dashboard_ai_title' => '🤖 DakLak OneTrip AI - Bảng Điều Hành',
    'dashboard_ai_revenue' => 'Tổng sinh kế cộng đồng dự kiến',
    'dashboard_ai_revenue_sub' => 'Được điều hướng từ AI đến các hộ dân & OCOP (Tháng này)',
    'dashboard_ai_tourists' => 'Tổng lượt khách phục vụ',
    'dashboard_ai_tourists_sub' => 'Khách du lịch được lên lịch trình cá nhân hóa',
    'dashboard_ai_sentiment' => 'Cảm xúc du khách (AI Sentiment)',
    'dashboard_ai_sentiment_val' => 'Tích cực',
    'dashboard_ai_sentiment_sub' => 'Dựa trên phân tích 1.2K phản hồi & đánh giá',
    'dashboard_ai_flow' => 'Lưu lượng khách Rừng - Biển (Tây Nguyên -> Phú Yên)',
    'dashboard_ai_flow_forest' => 'Khách lưu trú Đắk Lắk (Rừng)',
    'dashboard_ai_flow_sea' => 'Khách nối chuyến Phú Yên (Biển)',
    'dashboard_ai_hotspots' => 'Điểm nóng (Dự báo quá tải)',
    'dashboard_ai_hotspots_sub' => '* AI đang chủ động điều hướng bớt khách sang các điểm lân cận.',
    'dashboard_ai_capacity' => 'Công suất',
PHP;

$en_append = <<<PHP
    'iti_reroute_btn' => '⛈️ Heavy Rain (Re-route)',
    'iti_suggestion' => '💡 <strong>Suggestion:</strong> ',
    'iti_reason' => '🧐 <strong>Reason:</strong> ',
    'iti_community_impact' => '💰 <strong>Community Impact:</strong> ',
    'iti_mic_blocked' => 'Microphone access is blocked. Please grant microphone permissions (click the lock icon in the address bar).',
    'iti_mic_network' => 'Internet connection required for speech recognition.',
    'iti_mic_error' => 'Speech recognition error: ',
    'iti_mic_try_again' => '. Please try again on Chrome/Edge or verify your microphone.',
    'iti_rerouting_loading' => '⛈️ Analyzing weather and adjusting itinerary...',
    'iti_reroute_success' => 'Itinerary automatically adjusted for heavy rain!',
    'iti_reroute_need_iti' => 'Please generate an itinerary first!',
    'iti_mic_title' => 'Voice Input',
    'admin_nav_dashboard_ai' => '🤖 OneTrip AI',
    'dashboard_ai_title' => '🤖 DakLak OneTrip AI - Dashboard',
    'dashboard_ai_revenue' => 'Estimated Community Revenue',
    'dashboard_ai_revenue_sub' => 'Routed by AI to locals & OCOP (This month)',
    'dashboard_ai_tourists' => 'Total Tourists Served',
    'dashboard_ai_tourists_sub' => 'Tourists receiving personalized itineraries',
    'dashboard_ai_sentiment' => 'Tourist Sentiment (AI)',
    'dashboard_ai_sentiment_val' => 'Positive',
    'dashboard_ai_sentiment_sub' => 'Based on 1.2K reviews & feedback analysis',
    'dashboard_ai_flow' => 'Forest - Sea Tourist Flow (Central Highlands -> Phu Yen)',
    'dashboard_ai_flow_forest' => 'Dak Lak Tourists (Forest)',
    'dashboard_ai_flow_sea' => 'Phu Yen Connecting Tourists (Sea)',
    'dashboard_ai_hotspots' => 'Hotspots (Overload Forecast)',
    'dashboard_ai_hotspots_sub' => '* AI is proactively rerouting tourists to nearby spots.',
    'dashboard_ai_capacity' => 'Capacity',
PHP;

$vi_file = __DIR__ . '/includes/lang_vi.php';
$vi_content = file_get_contents($vi_file);
$vi_content = str_replace('];', $vi_append . "\n];", $vi_content);
file_put_contents($vi_file, $vi_content);

$en_file = __DIR__ . '/includes/lang_en.php';
$en_content = file_get_contents($en_file);
$en_content = str_replace('];', $en_append . "\n];", $en_content);
file_put_contents($en_file, $en_content);

echo "Translations appended.\n";
