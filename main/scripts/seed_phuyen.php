<?php
require_once __DIR__ . '/includes/functions.php';
$db = getDB();

$places = [
    [
        'Gành Đá Đĩa - Phú Yên',
        'ganh-da-dia-phu-yen',
        'Kiệt tác thiên nhiên với các khối đá hình lăng trụ xếp liền nhau.',
        'Gành Đá Đĩa là một trong những danh thắng quốc gia đặc biệt của Phú Yên, thu hút đông đảo du khách bởi cấu tạo địa chất độc đáo. Các khối đá bazan hình lục giác, ngũ giác xếp chồng lên nhau như những chồng đĩa vươn ra biển.',
        1, // Assuming category 1 is nature/sightseeing
        3.0,
        'low',
        'biển, thiên nhiên, phú yên, chụp ảnh',
        'https://images.unsplash.com/photo-1698651874457-3f82b7b20468?auto=format&fit=crop&q=80',
        13.3444, 109.2975
    ],
    [
        'Đầm Ô Loan',
        'dam-o-loan-phu-yen',
        'Đầm nước lợ nổi tiếng với đặc sản sò huyết ngon nức tiếng.',
        'Nằm yên bình dưới chân đèo Quán Cau, Đầm Ô Loan mang vẻ đẹp tĩnh lặng, thơ mộng. Nơi đây là điểm đến lý tưởng để ngắm hoàng hôn và thưởng thức hải sản tươi sống, đặc biệt là sò huyết.',
        3, // Food / Nature
        2.0,
        'medium',
        'hải sản, thiên nhiên, phú yên, ẩm thực',
        'https://images.unsplash.com/photo-1707011964177-3eab20c8f9b9?auto=format&fit=crop&q=80',
        13.2792, 109.2558
    ],
    [
        'Vịnh Vũng Rô',
        'vinh-vung-ro',
        'Vịnh biển xanh biếc, lịch sử hào hùng và hải sản phong phú.',
        'Vũng Rô là một vịnh nhỏ thuộc xã Hòa Xuân Nam, huyện Đông Hòa. Nơi đây từng là bến đỗ của những con tàu Không số trong chiến tranh. Ngày nay, Vũng Rô níu chân du khách bởi làn nước trong xanh và các bè hải sản tươi ngon.',
        1,
        4.0,
        'medium',
        'biển, lịch sử, phú yên, hải sản',
        'https://images.unsplash.com/photo-1627918512534-8c01b11b5ff9?auto=format&fit=crop&q=80',
        12.8714, 109.3908
    ],
    [
        'Bãi Môn - Mũi Điện',
        'bai-mon-mui-dien',
        'Nơi đón bình minh đầu tiên trên đất liền Việt Nam.',
        'Mũi Điện (Mũi Đại Lãnh) và Bãi Môn tạo nên một bức tranh tuyệt đẹp. Dưới chân ngọn hải đăng là bãi cát trắng mịn và làn nước trong vắt. Trải nghiệm cắm trại và đón những tia nắng đầu tiên là không thể bỏ lỡ.',
        1,
        3.0,
        'low',
        'biển, bình minh, thiên nhiên, phú yên',
        'https://images.unsplash.com/photo-1632766345917-cfc470bf7e71?auto=format&fit=crop&q=80',
        12.8772, 109.4319
    ]
];

$stmt = $db->prepare("INSERT INTO destinations (name, slug, short_desc, description, category_id, avg_visit_hours, price_level, tags, image_url, latitude, longitude) VALUES (?,?,?,?,?,?,?,?,?,?,?)");

foreach ($places as $p) {
    // Check if exists
    $check = $db->prepare("SELECT id FROM destinations WHERE slug = ?");
    $check->execute([$p[1]]);
    if (!$check->fetch()) {
        $stmt->execute($p);
        echo "Inserted {$p[0]}\n";
    } else {
        echo "Skipped {$p[0]}\n";
    }
}
echo "Done seeding.\n";
