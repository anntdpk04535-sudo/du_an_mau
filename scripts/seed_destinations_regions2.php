<?php
declare(strict_types=1);
/* Batch 2 - điểm đến Đắk Lắk Đông/Tây */
require_once __DIR__ . '/seed_helpers.php';
$db = dd();
$cat = []; foreach ($db->query("SELECT id,slug FROM categories") as $r) $cat[$r['slug']] = (int)$r['id'];

$rows = [
// ===== TÂY (thêm) =====
['Hồ Ea Sô','ho','Hồ nước lớn nhất Đắk Lắk','Hồ thủy lợi rộng hàng nghìn ha, cảnh quan hùng vĩ, điểm dã ngoại.','Xã Ea Sô, huyện Ea Kar',12.9833,108.6167,'free',3.0,'west','hồ,dã ngoại'],
['Thác Dray Kơ Nang','thac-nuoc','Thác nước đẹp gần Dray Nur','Thác nước phụ của hệ thống Serepok, cảnh quan đẹp.','Xã Dray Sáp, huyện Krông Ana',12.5600,108.1000,'free',1.5,'west','thác nước,thiên nhiên'],
['Khu du lịch Buôn Đôn','buon-lang-van-hoa','Khu du lịch sinh thái Buôn Đôn','Trải nghiệm voi thân thiện, thuyền sông Serepok, văn hoá người M\'nông.','Thị trấn Buôn Đôn',12.8811,107.7619,'low',3.0,'west','voi,sinh thái,văn hoá'],
['Nhà dài Buôn Đôn','buon-lang-van-hoa','Nhà dài truyền thống người M\'nông','Khám phá kiến trúc nhà dài và văn hoá gia đình mẫu hệ.','Thị trấn Buôn Đôn',12.8800,107.7600,'free',1.0,'west','nhà dài,văn hoá'],
['Mộ vua voi Khunjunob','buon-lang-van-hoa','Khu mộ vua voi nổi tiếng','Khu mộ cổ của những vua voi tài ba, nghĩa địa voi độc đáo.','Xã Krông Na, huyện Buôn Đôn',12.8833,107.7667,'free',1.5,'west','vua voi,lịch sử,biểu tượng'],
['Sông Serepok','thac-nuoc','Dòng sông huyền thoại Tây Nguyên','Đi thuyền trên sông, ngắm thác và rừng hai bên bờ.','Huyện Buôn Đôn',12.9000,107.7667,'low',2.0,'west','sông,sinh thái'],
['Khu căn cứ kháng chiến Nam Tây Nguyên','buon-lang-van-hoa','Di tích lịch sử căn cứ kháng chiến','Nơi ở và làm việc của cán bộ kháng chiến trong chiến tranh.','Xã Ea Bông, huyện Krông Ana',12.5667,108.0667,'free',1.5,'west','lịch sử,di tích'],
['Thác Krông Na','thac-nuoc','Thác nước thuộc xã Krông Na','Thác nước đẹp trên sông Serepok gần Buôn Đôn.','Xã Krông Na, huyện Buôn Đôn',12.8667,107.7500,'free',2.0,'west','thác nước,thiên nhiên'],
['Vườn bia trong rừng Buôn Đôn','buon-lang-van-hoa','Bia đá lịch sử giữa rừng','Các tấm bia ghi dấu lịch sử của buôn làng M\'nông.','Huyện Buôn Đôn',12.9000,107.7500,'free',1.0,'west','bia đá,lịch sử'],
['Hồ Dak Lak','ho','Hồ nước tại trung tâm thị trấn','Hồ nhỏ yên bình trong thị trấn Buôn Đôn.','TT. Buôn Đôn',12.8790,107.7590,'free',0.5,'west','hồ'],
['Chùa Vạn Thiện','buon-lang-van-hoa','Ngôi chùa cổ tại Buôn Đôn','Chùa cổ hơn 200 năm, gắn với lịch sử buôn làng.','Xã Krông Na, huyện Buôn Đôn',12.8850,107.7700,'free',0.5,'west','chùa,tâm linh'],
['Khu du lịch hồ Lắk bờ nam','ho','Bờ nam hồ Lắk hoang sơ','Ngắm mặt hồ từ bờ nam, đường vòng quanh hồ đẹp.','Xã Bông Krang, huyện Lắk',12.4000,108.1900,'free',1.5,'west','hồ,hoang sơ'],
['Làng cà phê Cư Kuin','am-thuc','Vùng trồng cà phê chất lượng cao','Tham quan vùng cà phê ngon nhất Đắk Lắk.','Huyện Cư Kuin',12.5333,108.0167,'low',1.5,'west','cà phê'],
['Đồi cà phê Ea Tul','am-thuc','Đồi cà phê bạt ngàn','Ngắm vườn cà phê xanh mướt và mùa hoa cà phê.','Xã Ea Tul, huyện Cư Kuin',12.5200,108.0100,'free',1.0,'west','cà phê,cảnh quan'],
['Suối khoáng nóng M\'rô','thac-nuoc','Suối nước nóng Cư M\'gar','Tắm khoáng nóng tự nhiên giữa rừng.','Xã Ea M\'roh, huyện Cư M\'gar',12.9500,108.0167,'low',1.5,'east','suối nóng'],
['Rừng nguyên sinh Ea Súp','thac-nuoc','Rừng nguyên sinh biên giới','Rừng tự nhiên rộng lớn, hệ sinh thái phong phú.','Huyện Ea Súp',13.1500,107.8833,'free',3.0,'west','rừng,thiên nhiên'],
['Hồ thủy điện Serepok 3','ho','Hồ thủy điện trên sông Serepok','Hồ rộng lớn do đập thủy điện tạo thành, cảnh đẹp.','Huyện Krông Ana',12.5500,108.0333,'free',1.5,'west','hồ,thủy điện'],
['Đảo nhỏ giữa hồ Lắk','ho','Đảo sinh thái giữa hồ','Đảo nhỏ giữa hồ Lắk, dừng chân ngắm cảnh.','Huyện Lắk',12.4250,108.1900,'free',1.0,'west','hồ,đảo'],
['Rẫy cao su Ea Tar','buon-lang-van-hoa','Đồi cao su bạt ngàn','Cánh đồng cao su trải dài, đẹp mùa lá đổi màu.','Xã Ea Tar, huyện Cư M\'gar',12.9167,108.0000,'free',1.0,'east','cao su,cảnh quan'],
['Thác H\'Giang','thac-nuoc','Thác nước tại Krông Ana','Thác nước đẹp trên hệ thống sông Serepok.','Huyện Krông Ana',12.5667,108.0833,'free',1.5,'west','thác nước'],
['Khu văn hóa các dân tộc hồ Lắk','buon-lang-van-hoa','Trung tâm văn hóa các dân tộc','Trình diễn cồng chiêng, nghề truyền thống ven hồ.','Huyện Lắk',12.4230,108.1800,'low',1.5,'west','văn hoá,cồng chiêng'],
['Thác Dray Bhung','thac-nuoc','Thác nước thuộc Krông Ana','Thác nước nhỏ đẹp trên sông Krông Ana.','Huyện Krông Ana',12.5833,108.0500,'free',1.5,'west','thác nước'],
['Hồ Ea Yông','ho','Hồ nước yên bình','Hồ nước nhỏ gần trung tâm, điểm dã ngoại.','Xã Ea Yông, huyện Cư Kuin',12.5833,108.0500,'free',1.0,'west','hồ'],
// ===== ĐÔNG (thêm) =====
['Thác Ba Tầng','thac-nuoc','Thác Thủy Tiên ba tầng','Thác nước ba tầng đẹp tại Krông Năng.','Xã Ea Puk, huyện Krông Năng',12.9691,108.3371,'free',2.5,'east','thác nước,thiên nhiên'],
['Hồ Ea Kao','ho','Hồ Ea Kao gần trung tâm','Hồ lớn gần TP, dã ngoại cuối tuần.','TP. Buôn Ma Thuột',12.6167,108.0833,'free',2.0,'east','hồ'],
['Vườn quốc gia Chu Yang Sin','vuon-quoc-gia','Vườn quốc gia lớn thứ hai Việt Nam','Rừng nguyên sinh đa dạng, leo núi, khám phá thác.','Huyện Krông Bông',12.5000,108.5000,'free',4.0,'east','vườn quốc gia,rừng'],
['Thác Bảy Tầng','thac-nuoc','Thác nước bảy tầng','Thác nước xếp tầng đẹp tại Ea Kar.','Huyện Ea Kar',12.8500,108.5833,'free',2.0,'east','thác nước'],
['Đèo Phượng Hoàng','buon-lang-van-hoa','Đèo hoa phượng tím','Con đèo đẹp với hàng phượng tím hai bên đường.','Huyện M\'Đrắk',12.7833,108.7833,'free',0.5,'east','đèo,cảnh đẹp'],
['Hồ Ea Lâm','ho','Hồ nước tại Ea Kar','Hồ thủy lợi đẹp, điểm câu cá.','Huyện Ea Kar',12.8833,108.6167,'free',1.5,'east','hồ,câu cá'],
['Thác K52','thac-nuoc','Thác nước đẹp tại Krông Bông','Thác nước hoang sơ trong VQG Chu Yang Sin.','Huyện Krông Bông',12.5167,108.4333,'free',3.0,'east','thác nước,trekking'],
['Rừng thông Ea H\'leo','thac-nuoc','Rừng thông cao nguyên','Đồi thông xanh mát, điểm chụp ảnh.','Huyện Ea H\'leo',13.2333,108.1833,'free',1.5,'east','rừng thông'],
['Hồ Đray Hling','ho','Hồ nước tại Krông Năng','Hồ nước yên bình giữa rừng cà phê.','Huyện Krông Năng',12.9500,108.3000,'free',1.0,'east','hồ'],
['Thác Pong Gu','thac-nuoc','Thác nước tại Krông Bông','Thác nước đẹp, đường rừng trekking.','Huyện Krông Bông',12.4667,108.4167,'free',2.5,'east','thác nước'],
['Đồi chè Ea Kar','am-thuc','Đồi chè xanh mướt','Cánh đồng chè bát ngát, cảnh quan đẹp.','Huyện Ea Kar',12.9000,108.5667,'free',1.5,'east','chè,cảnh quan'],
['Suối Kênh M\'Đrắk','thac-nuoc','Suối nước trong xanh','Suối đẹp cho tắm suối, dã ngoại.','Huyện M\'Đrắk',12.7333,108.7833,'free',2.0,'east','suối,dã ngoại'],
['Hồ thủy điện Krông Bông','ho','Hồ thủy điện trên sông Krông Bông','Hồ nước lớn, cảnh quan đẹp.','Huyện Krông Bông',12.5667,108.4500,'free',1.5,'east','hồ,thủy điện'],
['Thác Dak Lieng','thac-nuoc','Thác nước tại Lắk','Thác nước hoang sơ ở phía nam tỉnh.','Xã Đắk Liêng, huyện Lắk',12.3500,108.1667,'free',2.0,'west','thác nước'],
['Vườn quốc gia Yok Đôn khu Ea Súp','vuon-quoc-gia','Phân khu phục hồi Ea Súp','Khu vực rừng khộp của VQG Yok Đôn.','Huyện Ea Súp',13.1000,107.8000,'free',3.0,'west','vườn quốc gia,rừng'],
['Cầu buôn Triết','buon-lang-van-hoa','Cầu qua sông tại Cư Kuin','Cây cầu đẹp bắc qua sông Krông Ana.','Xã Ea Hu, huyện Cư Kuin',12.5500,108.1000,'free',0.5,'west','cầu'],
['Thác Dray Woh','thac-nuoc','Thác nước thuộc Krông Ana','Thác nước đẹp trên dòng sông.','Huyện Krông Ana',12.5700,108.0900,'free',1.5,'west','thác nước'],
['Hồ buôn Kroa','ho','Hồ nước tại Cư Kuin','Hồ nước đẹp gần buôn làng.','Xã Ea Bhốk, huyện Cư Kuin',12.5167,108.0500,'free',1.0,'west','hồ'],
['Thác Hà Lan','thac-nuoc','Thác nước tại Krông Năng','Thác nước đẹp, không khí mát mẻ.','Huyện Krông Năng',12.9333,108.3500,'free',2.0,'east','thác nước'],
['Rừng cà phê Ea Phê','am-thuc','Vùng trồng cà phê rộng lớn','Tham quan vườn cà phê lớn nhất Đắk Lắk.','Huyện Krông Pắc',12.6833,108.3500,'free',1.5,'east','cà phê'],
];

$ins = $db->prepare("INSERT IGNORE INTO destinations
 (category_id,name,slug,short_desc,description,address,latitude,longitude,price_level,avg_visit_hours,region,tags)
 VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
$n=0; $byRegion=['east'=>0,'west'=>0];
foreach($rows as $r){
  [$name,$ck,$sd,$desc,$addr,$lat,$lng,$price,$avg,$region,$tags]=$r;
  $slug=slugify($name);
  if($ins->execute([$cat[$ck]??1,$name,$slug,$sd,$desc,$addr,$lat,$lng,$price,$avg,$region,$tags])) $n++;
  $byRegion[$region]++;
}
echo json_encode(['attempted'=>count($rows),'inserted'=>$n,'by_region'=>$byRegion],JSON_UNESCAPED_UNICODE).PHP_EOL;