<?php
require_once __DIR__ . '/../includes/functions.php';

$db = getDB();

$articles = [
    [
        'title' => 'Top 5 món đặc sản không thể bỏ lỡ khi đến Đắk Lắk',
        'slug' => 'top-5-mon-dac-san-dak-lak',
        'summary' => 'Khám phá ẩm thực Tây Nguyên qua những món ăn đậm chất núi rừng: Gà nướng bản Đôn, Bún đỏ, Gỏi lá, Rượu cần...',
        'content' => '<p>Đắk Lắk không chỉ nổi tiếng với những cánh rừng bạt ngàn, những ngọn thác hùng vĩ mà còn bởi nét văn hóa ẩm thực độc đáo. Dưới đây là 5 món đặc sản bạn nhất định phải thử khi đến vùng đất này:</p>
                      <h3>1. Gà nướng Bản Đôn</h3>
                      <p>Gà được nuôi thả rông trên đồi núi, thịt chắc và thơm. Gà sau khi làm sạch được tẩm ướp với muối ớt sả, mật ong rừng rồi nướng trên than hồng. Ăn kèm với cơm lam dẻo thơm thì không còn gì tuyệt vời bằng.</p>
                      <h3>2. Bún đỏ Buôn Ma Thuột</h3>
                      <p>Một tô bún đỏ rực rỡ với nước dùng ngọt thanh từ xương, cua đồng giã nhỏ, thịt băm, trứng cút... Sợi bún to, dai giòn, được nhuộm màu đỏ tự nhiên từ hạt điều, ăn cùng rau cần nước, cải ngọt luộc.</p>
                      <h3>3. Gỏi lá</h3>
                      <p>Món ăn là sự kết hợp của hơn 40 loại lá rừng khác nhau, cuộn cùng thịt ba chỉ luộc, tôm rang, da heo thái mỏng... Chấm cùng thứ nước chấm sền sệt đặc chế từ hèm rượu, trứng vịt, thịt băm.</p>
                      <h3>4. Rượu cần</h3>
                      <p>Thức uống truyền thống của đồng bào dân tộc thiểu số Tây Nguyên. Rượu được ủ men lá rừng, có vị ngọt nồng ấm, được uống trực tiếp từ chóe bằng những cần trúc dài.</p>
                      <h3>5. Cà phê Buôn Ma Thuột</h3>
                      <p>Tất nhiên, đến "thủ phủ cà phê" thì không thể bỏ qua một ly cà phê đậm đà, thơm lừng. Cà phê ở đây có hương vị đặc trưng, đánh thức mọi giác quan.</p>',
        'image_url' => 'https://images.unsplash.com/photo-1563805042-7684c8a9e9cb?q=80&w=800&auto=format&fit=crop',
        'status' => 'published',
        'author_id' => 1
    ],
    [
        'title' => 'Kinh nghiệm du lịch Bảo tàng Thế giới Cà phê 2024',
        'slug' => 'kinh-nghiem-du-lich-bao-tang-the-gioi-ca-phe',
        'summary' => 'Bảo tàng Thế giới Cà phê không chỉ là nơi lưu giữ lịch sử về cà phê mà còn là một kiệt tác kiến trúc độc đáo, điểm check-in cực "hot" tại Buôn Ma Thuột.',
        'content' => '<p>Được mệnh danh là "Châu Âu thu nhỏ giữa lòng Buôn Ma Thuột", <strong>Bảo tàng Thế giới Cà phê</strong> là một điểm đến không thể bỏ qua đối với bất kỳ ai đặt chân đến Đắk Lắk.</p>
                      <h3>Kiến trúc độc đáo</h3>
                      <p>Bảo tàng có kiến trúc lấy cảm hứng từ không gian nhà dài truyền thống của đồng bào Tây Nguyên, kết hợp với những đường nét uốn lượn hiện đại. Không gian mở, đón ánh sáng tự nhiên, tạo cảm giác vô cùng thoáng đãng.</p>
                      <h3>Khám phá lịch sử cà phê</h3>
                      <p>Bên trong bảo tàng là không gian trưng bày hơn 10.000 hiện vật liên quan đến cà phê từ khắp nơi trên thế giới. Bạn sẽ được tìm hiểu về nguồn gốc, lịch sử, văn hóa cà phê qua các thời kỳ, cũng như quy trình sản xuất cà phê từ hạt đến ly.</p>
                      <h3>Góc check-in "triệu view"</h3>
                      <p>Mọi ngóc ngách trong bảo tàng đều có thể trở thành bối cảnh tuyệt vời cho những bức ảnh "sống ảo". Từ kiến trúc bên ngoài, không gian trưng bày bên trong, đến những quán cà phê mang phong cách hiện đại.</p>
                      <p><strong>Lưu ý:</strong> Hãy chọn cho mình những trang phục vintage hoặc phong cách năng động để có những bức ảnh "hợp cảnh" nhất nhé!</p>',
        'image_url' => 'https://images.unsplash.com/photo-1507133750070-4cb62688b139?q=80&w=800&auto=format&fit=crop',
        'status' => 'published',
        'author_id' => 1
    ],
    [
        'title' => 'Lịch trình 3 ngày 2 đêm phượt Đắk Lắk trọn vẹn',
        'slug' => 'lich-trinh-3-ngay-2-dem-phuot-dak-lak',
        'summary' => 'Gợi ý lịch trình phượt Đắk Lắk 3 ngày 2 đêm chi tiết nhất, đi qua những địa điểm nổi tiếng: Hồ Lắk, Thác Dray Nur, Làng cà phê Trung Nguyên...',
        'content' => '<p>Bạn đang lên kế hoạch phượt Đắk Lắk trong 3 ngày 2 đêm? Dưới đây là gợi ý lịch trình chi tiết giúp bạn khám phá trọn vẹn vẻ đẹp của vùng đất Tây Nguyên đầy nắng và gió.</p>
                      <h3>Ngày 1: Khám phá thành phố Buôn Ma Thuột</h3>
                      <ul>
                          <li><strong>Sáng:</strong> Đến Buôn Ma Thuột, thưởng thức bún đỏ. Tham quan Bảo tàng Đắk Lắk, tìm hiểu văn hóa các dân tộc.</li>
                          <li><strong>Chiều:</strong> Tham quan Làng cà phê Trung Nguyên, thưởng thức cà phê và check-in không gian kiến trúc độc đáo.</li>
                          <li><strong>Tối:</strong> Dạo quanh Quảng trường 10/3, thưởng thức ẩm thực đường phố, mua sắm đồ lưu niệm.</li>
                      </ul>
                      <h3>Ngày 2: Chinh phục thác nước và khám phá Hồ Lắk</h3>
                      <ul>
                          <li><strong>Sáng:</strong> Di chuyển đến Thác Dray Nur - Dray Sap, chiêm ngưỡng vẻ đẹp hùng vĩ của dòng thác đổ. Tắm thác, tham gia các hoạt động trekking nhẹ nhàng.</li>
                          <li><strong>Chiều:</strong> Di chuyển đến Hồ Lắk, hồ nước ngọt tự nhiên lớn thứ hai Việt Nam. Ngồi thuyền độc mộc ngắm cảnh hồ, cưỡi voi, thăm buôn làng của người M\'Nông.</li>
                          <li><strong>Tối:</strong> Thưởng thức các món nướng ven hồ, thưởng thức rượu cần và hòa mình vào không gian văn hóa cồng chiêng.</li>
                      </ul>
                      <h3>Ngày 3: Khám phá Buôn Đôn và trở về</h3>
                      <ul>
                          <li><strong>Sáng:</strong> Đến Buôn Đôn, tham quan cầu treo thanh niên, nhà sàn cổ. Trải nghiệm cưỡi voi lội sông Sêrêpốk.</li>
                          <li><strong>Trưa:</strong> Thưởng thức đặc sản Gà nướng Bản Đôn, cơm lam.</li>
                          <li><strong>Chiều:</strong> Mua sắm đặc sản (cà phê, hạt tiêu, mật ong rừng...) làm quà. Kết thúc hành trình 3 ngày 2 đêm đầy thú vị.</li>
                      </ul>
                      <p><em>Lưu ý: Lịch trình có thể linh hoạt thay đổi tùy theo sở thích và điều kiện thời tiết thực tế.</em></p>',
        'image_url' => 'https://images.unsplash.com/photo-1621255855269-e685f6da386d?q=80&w=800&auto=format&fit=crop',
        'status' => 'published',
        'author_id' => 1
    ]
];

$stmt = $db->prepare("INSERT INTO articles (title, slug, summary, content, image_url, status, author_id) VALUES (?, ?, ?, ?, ?, ?, ?)");

$count = 0;
foreach ($articles as $a) {
    // Check if exists
    $check = $db->prepare("SELECT id FROM articles WHERE slug = ?");
    $check->execute([$a['slug']]);
    if (!$check->fetch()) {
        $stmt->execute([
            $a['title'], 
            $a['slug'], 
            $a['summary'], 
            $a['content'], 
            $a['image_url'], 
            $a['status'], 
            $a['author_id']
        ]);
        $count++;
    }
}

echo "Inserted $count articles successfully.\n";
