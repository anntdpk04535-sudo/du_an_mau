<?php
declare(strict_types=1);
/* Seed điểm đến Đắk Lắk theo vùng Đông/Tây.
   Dữ liệu là các điểm đến thực tế, được ghi chép rộng rãi. */
require_once __DIR__ . '/seed_helpers.php';
$db = dd();

$cat = []; foreach ($db->query("SELECT id,slug FROM categories") as $r) $cat[$r['slug']] = (int)$r['id'];
// thác-nuoc=1 ho=2 buon-lang-van-hoa=3 vuon-quoc-gia=4 am-thuc=5

// [name, cat_key, short_desc, description, address, lat, lng, price_level, avg_hours, region, tags]
$rows = [
// ================= ĐÔNG (TP BMT + huyện đông/bắc) =================
['Bảo tàng Thế giới Cà phê','am-thuc','Bảo tàng cà phê thế giới đầu tiên tại Việt Nam','Công trình kiến trúc độc đáo lấy cảm hứng từ nhà dài Tây Nguyên, trưng bày hàng nghìn hiện vật về cà phê toàn cầu.','Số 454 Nguyễn Đình Chiểu, TP. Buôn Ma Thuột',12.6923,108.0495,'medium',2.5,'east','cà phê,bảo tàng,văn hoá'],
['Nhà đày Buôn Ma Thuột','buon-lang-van-hoa','Di tích lịch sử nhà tù nổi tiếng thời Pháp','Hệ thống nhà tù do thực dân Pháp xây dựng năm 1930, hiện là di tích lịch sử quốc gia.','Số 18 Đội Cấn, TP. Buôn Ma Thuột',12.6809,108.0462,'free',1.5,'east','lịch sử,di tích'],
['Bảo tàng Đắk Lắk','buon-lang-van-hoa','Bảo tàng văn hoá các dân tộc Tây Nguyên','Trưng bày hiện vật, nhà dài, chiêng và văn hoá của 44 dân tộc anh em.','Số 01 Y Ngông, TP. Buôn Ma Thuột',12.6877,108.0420,'free',1.5,'east','văn hoá,bảo tàng,Ê Đê'],
['Chùa Khải Đoan','buon-lang-van-hoa','Ngôi chùa lớn nhất Đắk Lắk','Chùa do vua Khải Định và vua Bảo Đại xây dựng, kiến trúc độc đáo.','Số 117 Phan Bội Châu, TP. Buôn Ma Thuột',12.6763,108.0431,'free',1.0,'east','chùa,kiến trúc,tâm linh'],
['Tượng đài Chiến Thắng','buon-lang-van-hoa','Biểu tượng giải phóng Buôn Ma Thuột','Quảng trường và tượng đài kỷ niệm chiến thắng lịch sử ngày 10/3/1975.','Quảng trường 10/3, TP. Buôn Ma Thuột',12.6833,108.0370,'free',0.5,'east','lịch sử,tượng đài'],
['Phố cà phê Buôn Ma Thuột','am-thuc','Con phố với hàng chục quán cà phê đặc sắc','Khu phố chuyên về cà phê nguyên chất, nơi thưởng thức văn hoá cà phê Tây Nguyên.','Đường Lê Duẩn, TP. Buôn Ma Thuột',12.6789,108.0468,'low',1.5,'east','cà phê,ẩm thực'],
['Chợ Buôn Ma Thuột','am-thuc','Chợ trung tâm mua sắm đặc sản Tây Nguyên','Nơi bày bán cà phê, mật ong, các loại trái cây và đặc sản Tây Nguyên.','Đường Trần Phú, TP. Buôn Ma Thuột',12.6801,108.0427,'low',1.5,'east','chợ,ẩm thực,đặc sản'],
['Thác Krông Kmar','thac-nuoc','Thác nước hoang sơ giữa đại ngàn','Thác nước đẹp nằm trên sông Krông Kmar, cách trung tâm khoảng 70km.','Xã Cư Pui, huyện Krông Bông',12.5333,108.4333,'free',2.5,'east','thác nước,thiên nhiên'],
['Rừng thông Đam Pờ','thac-nuoc','Khu rừng thông cao nguyên mát mẻ','Vùng đồi thông lý tưởng cho dã ngoại, chụp ảnh.','Xã Ea Toh, huyện Krông Năng',12.9885,108.3333,'free',2.0,'east','rừng thông,thiên nhiên'],
['Hồ Ea H’leo','ho','Hồ nước lớn thuộc huyện Ea H\'leo','Hồ thủy lợi lớn với cảnh quan rừng thông, điểm câu cá và dã ngoại.','Huyện Ea H\'leo, Đắk Lắk',13.2167,108.2167,'free',2.0,'east','hồ,thiên nhiên,câu cá'],
['Suối Đá Vàng','thac-nuoc','Suối nước vàng nổi tiếng Krông Năng','Khu suối với nước màu vàng đặc trưng do khoáng chất, địa hình đá granit.','Xã Ea Toh, huyện Krông Năng',12.9500,108.3667,'free',2.5,'east','suối,thiên nhiên'],
['Thác Gia Long','thac-nuoc','Thác nước lãng mạn bên dòng Krông Ana','Thác đôi với Dray Nur, nơi du khách thư giãn và chụp ảnh.','Xã Dray Sáp, huyện Krông Ana',12.5600,108.0900,'low',2.0,'west','thác nước,thiên nhiên'],
['Cụm thác Dray Nur - Dray Sáp','thac-nuoc','Hệ thống thác hùng vĩ bậc nhất Tây Nguyên','Hai thác lớn trên sông Serepok, có hang động và tắm thác.','Xã Dray Sáp, huyện Krông Ana',12.5667,108.1167,'low',3.0,'west','thác nước,trekking'],
['Núi lửa Chư Đăng Ya','thac-nuoc','Miệng núi lửa cổ với hoa dã quỳ','Núi lửa đã tắt với cánh đồng hoa dã quỳ nổi tiếng.','Xã Chư Đăng Ya, huyện Chư Păh, Gia Lai',13.9333,108.0167,'free',2.0,'east','núi lửa,hoa dã quỳ'],
['Khu du lịch hồ Ea Tân','ho','Hồ nước rộng lớn thuộc Krông Ana','Hồ thủy lợi lớn, nơi du lịch sinh thái và nghỉ dưỡng cuối tuần.','Huyện Krông Ana, Đắk Lắk',12.5833,108.0167,'free',2.5,'west','hồ,sinh thái'],
['Rừng cao su Ea Súp','buon-lang-van-hoa','Rừng cao su bạt ngàn huyện biên giới','Cánh rừng cao su trải dài, điểm chụp ảnh lý tưởng.','Huyện Ea Súp, Đắk Lắk',13.1833,107.9167,'free',1.5,'west','cao su,thiên nhiên'],
['Vườn cà phê Buôn Ma Thuột','am-thuc','Tham quan vườn cà phê và trải nghiệm thu hoạch','Trải nghiệm hái cà phê, xem quy trình sản xuất cà phê.','TP. Buôn Ma Thuột, Đắk Lắk',12.6833,108.0500,'low',2.0,'east','cà phê,trải nghiệm'],
['Làng gốm Krông Năng','buon-lang-van-hoa','Làng nghề gốm truyền thống','Làng gốm lâu đời của người Ê Đê, sản phẩm gốm thủ công độc đáo.','Huyện Krông Năng, Đắk Lắk',12.9833,108.2833,'low',1.5,'east','gốm,làng nghề,văn hoá'],
['Đập thủy lợi Ea Kao','ho','Hồ Ea Kao - khu vui chơi dã ngoại','Địa điểm cắm trại, câu cá, chèo thuyền gần trung tâm.','TP. Buôn Ma Thuột, Đắk Lắk',12.6167,108.0833,'free',2.0,'east','hồ,cắm trại'],
['Nhà thờ Chính Tòa Buôn Ma Thuột','buon-lang-van-hoa','Nhà thờ trung tâm giáo phận','Công trình kiến trúc tôn giáo lớn của giáo phận Buôn Ma Thuột.','Số 27 Nguyễn Công Trứ, TP. Buôn Ma Thuột',12.6804,108.0421,'free',0.5,'east','nhà thờ,kiến trúc'],
['Đình Bình Minh','buon-lang-van-hoa','Đình làng cổ xây năm 1958','Đình làng với kiến trúc cổ, nơi sinh hoạt văn hoá cộng đồng.','Xã Ea Kmút, huyện Ea Kar',12.7833,108.5333,'free',1.0,'east','đình làng,văn hoá'],
['Cửa khẩu Bu Prăng','buon-lang-van-hoa','Cửa khẩu biên giới Việt - Campuchia','Cửa khẩu quốc tế trên biên giới với nét văn hoá vùng biên.','Xã Ia Lốp, huyện Ea Súp',12.9500,107.7500,'free',1.0,'west','biên giới,văn hoá'],
['Hồ Chư Yang Sin','ho','Hồ nước bên dãy núi Chu Yang Sin','Hồ nhỏ trong VQG Chu Yang Sin, đường trekking đẹp.','VQG Chu Yang Sin, huyện Krông Bông',12.4833,108.4500,'free',3.0,'east','hồ,trekking'],
['Thác Bay Nhánh','thac-nuoc','Thác 7 nhánh hùng vĩ trên sông Serepok','Thác nước với 7 nhánh chia tách trên dòng sông.','Xã Ea Pô, huyện Cư Jút',12.5333,107.7500,'free',2.5,'west','thác nước,thiên nhiên'],
['Núi Đá Voi Mẹ','thac-nuoc','Tảng đá nguyên khối hình voi','Núi đá granit lớn, truyền thuyết voi mẹ.','Huyện Lắk, Đắk Lắk',12.4411,108.1991,'free',1.5,'west','núi đá,truyền thuyết'],
['Thác K50','thac-nuoc','Thác nước hoang sơ đẹp nhất VQG Chu Yang Sin','Thác nước đẹp nhất, đường đi rừng vất vả nhưng xứng đáng.','VQG Chu Yang Sin, huyện Krông Bông',12.5000,108.4667,'free',4.0,'east','thác nước,trekking,hoang sơ'],
['Khu du lịch thác Dray Sáp','thac-nuoc','Khu du lịch sinh thái tại thác khói','Dịch vụ tham quan, tắm thác và ăn uống tại thác.','Xã Dray Sáp, huyện Krông Ana',12.5728,108.1083,'low',2.5,'west','thác nước,khu du lịch'],
['Hồ Lak Sunset','ho','Điểm ngắm hoàng hôn đẹp nhất Tây Nguyên','Ngắm hoàng hôn trên hồ, chèo thuyền độc mộc.','Huyện Lắk, Đắk Lắk',12.4203,108.1854,'low',1.0,'west','hồ,hoàng hôn'],
['Buôn Jun','buon-lang-van-hoa','Buôn làng M\'nông nổi tiếng trên hồ Lắk','Buôn làng sinh sống trên mặt nước, nhà sàn truyền thống.','Huyện Lắk, Đắk Lắk',12.4150,108.1900,'free',1.5,'west','buôn làng,M\'nông'],
['Buôn Akô Dhông','buon-lang-van-hoa','Buôn Ê Đê giữa lòng thành phố','Nhà dài truyền thống, không gian văn hoá Ê Đê.','TP. Buôn Ma Thuột, Đắk Lắk',12.6833,108.0333,'free',1.5,'east','văn hoá,nhà dài,Ê Đê'],
['Làng du lịch cộng đồng Krông Ana','buon-lang-van-hoa','Làng cộng đồng bên sông Serepok','Homestay và trải nghiệm văn hoá người Ê Đê ven sông.','Xã Bình Hoà, huyện Krông Ana',12.5833,108.0333,'low',2.0,'west','cộng đồng,homestay'],
['Đèo Chư Bluk','buon-lang-van-hoa','Đèo hoang sơ giữa rừng nguyên sinh','Con đèo đẹp dẫn vào VQG Yok Đôn, rừng nguyên sinh.','Huyện Buôn Đôn, Đắk Lắk',12.9333,107.7333,'free',1.5,'west','đèo,rừng'],
['Thác Dray H’linh','thac-nuoc','Thác nước đẹp thuộc huyện Lắk','Thác nước hoang sơ nằm giữa rừng, cách hồ Lắk không xa.','Xã Đắk Liêng, huyện Lắk',12.3500,108.1667,'free',2.0,'west','thác nước,hoang sơ'],
['Rừng tràm Trí Nguyên','thac-nuoc','Rừng tràm ngập nước yên bình','Cảnh quan rừng tràm trên mặt nước, ngắm chim.','Xã Ea Trul, huyện Krông Pắc',12.7000,108.3500,'free',1.5,'east','rừng tràm,sinh thái'],
['Hồ Ea Ral','ho','Hồ nước lớn giữa đồi cà phê','Hồ thủy lợi với cảnh quan đồi cà phê xung quanh.','Xã Ea Ral, huyện Krông Bông',12.6333,108.5167,'free',1.5,'east','hồ,thiên nhiên'],
['Chợ đêm Buôn Ma Thuột','am-thuc','Chợ đêm với đặc sản và món ăn đêm','Khu ẩm thực đêm với các món đặc sản Tây Nguyên.','Đường Trần Phú, TP. Buôn Ma Thuột',12.6801,108.0427,'low',2.0,'east','chợ đêm,ẩm thực'],
['Phố đi bộ Buôn Ma Thuột','buon-lang-van-hoa','Phố đi bộ cuối tuần','Không gian đi bộ, giải trí và ẩm thực cuối tuần.','Trung tâm TP. Buôn Ma Thuột',12.6798,108.0421,'free',2.0,'east','phố đi bộ,giải trí'],
['Trung Nguyên Coffee Village','am-thuc','Không gian cà phê và văn hoá Trung Nguyên','Khu trải nghiệm cà phê, thư viện cà phê và kiến trúc độc đáo.','Số 82 Hai Bà Trưng, TP. Buôn Ma Thuột',12.6821,108.0485,'medium',1.5,'east','cà phê,văn hoá'],
['Vườn quốc gia Yok Đôn','vuon-quoc-gia','Vườn quốc gia lớn nhất Việt Nam','Rừng khộp lá rụng độc đáo, voi rừng, du lịch sinh thái thân thiện voi.','Huyện Buôn Đôn, Đắk Lắk',13.0833,107.7833,'medium',4.0,'west','vườn quốc gia,rừng,voi'],
['Cầu treo Buôn Đôn','buon-lang-van-hoa','Cầu treo nổi tiếng bắc qua sông Serepok','Cầu treo dài, nét đặc trưng của Buôn Đôn.','Thị trấn Buôn Đôn, Đắk Lắk',12.8811,107.7619,'free',0.5,'west','cầu treo,biểu tượng'],
['Biệt điện Bảo Đại','buon-lang-van-hoa','Dinh thự vua Bảo Đại bên hồ Lắk','Dinh thự cũ nơi vua Bảo Đại nghỉ dưỡng, kiến trúc cổ.','TT. Liên Sơn, huyện Lắk',12.4234,108.1538,'low',1.0,'west','dinh thự,lịch sử'],
['Khu du lịch hồ Lắk','ho','Khu du lịch sinh thái hồ Lắk','Thuyền độc mộc, cưỡi voi thân thiện, nhà sàn nghỉ dưỡng.','Huyện Lắk, Đắk Lắk',12.4203,108.1854,'low',3.0,'west','hồ,sinh thái,voi'],
['Thác Dray Nur từ hang','thac-nuoc','Khám phá hang sau thác Dray Nur','Đi qua hang sau màn nước thác, trải nghiệm độc đáo.','Xã Dray Sáp, huyện Krông Ana',12.5667,108.1167,'low',3.0,'west','thác nước,hang'],
['Vườn chanh dây Tây Nguyên','am-thuc','Vườn cây ăn trái đặc sản','Trải nghiệm hái chanh dây, sầu riêng tại vườn.','Xã Ea Tul, huyện Cư Kuin',12.5333,108.0167,'low',1.5,'east','vườn cây,trải nghiệm'],
['Rẫy cà phê Ako Dhong','am-thuc','Rẫy cà phê truyền thống của người Ê Đê','Tham quan rẫy cà phê của bà con buôn Akô Dhông.','TP. Buôn Ma Thuột',12.6850,108.0300,'free',1.0,'east','cà phê,văn hoá'],
['Suối nước nóng Ea Súp','thac-nuoc','Suối khoáng nóng thiên nhiên','Suối nước nóng trong rừng, tắm khoáng tự nhiên.','Huyện Ea Súp, Đắk Lắk',13.1000,107.8333,'low',1.5,'west','suối nóng,thư giãn'],
['Làng nghề dệt thổ cẩm Ea M\'roh','buon-lang-van-hoa','Làng dệt thổ cẩm truyền thống','Nghề dệt thổ cẩm thủ công của người Mnông.','Xã Ea M\'roh, huyện Cư M\'gar',12.9500,108.0167,'low',1.5,'east','thổ cẩm,làng nghề'],
['Hồ buôn Trấp','ho','Hồ nước tại thị trấn Buôn Trấp','Hồ nước trong xanh, nơi dã ngoại của người dân.','TT. Buôn Trấp, huyện Krông Ana',12.5667,108.0167,'free',1.0,'west','hồ,dã ngoại'],
['Thác Pông Gua','thac-nuoc','Thác nước thuộc huyện Krông Bông','Thác nước hoang sơ giữa rừng, gần VQG Chu Yang Sin.','Huyện Krông Bông, Đắk Lắk',12.4500,108.4000,'free',2.0,'east','thác nước,hoang sơ'],
['Cổng trời Ea H\'leo','thac-nuoc','Điểm ngắm toàn cảnh cao nguyên','Khu vực cao với tầm nhìn bao quát rừng núi.','Xã Ea H\'leo, Đắk Lắk',13.2500,108.2000,'free',1.5,'east','ngắm cảnh,thiên nhiên'],
['Hồ Chư M\'gar','ho','Hồ nước yên bình bên núi Chư M\'gar','Hồ nước và ngọn núi lửa đã tắt Chư M\'gar.','Huyện Cư M\'gar, Đắk Lắk',12.9000,108.0333,'free',2.0,'east','hồ,núi'],
['Khu rừng bạt ngàn Ea H\'leo','thac-nuoc','Rừng nguyên sinh rộng lớn','Rừng tự nhiên đa dạng, thích hợp trekking.','Huyện Ea H\'leo, Đắk Lắk',13.2000,108.2667,'free',3.0,'east','rừng,trekking'],
['Núi Chư Yang Sin','thac-nuoc','Nóc nhà của Đắk Lắk','Đỉnh núi cao nhất Đắk Lắk (2442m), tuyến leo nổi tiếng.','VQG Chu Yang Sin, huyện Krông Bông',12.4833,108.4667,'free',8.0,'east','núi,leo núi'],
];

$ins = $db->prepare("INSERT IGNORE INTO destinations
 (category_id,name,slug,short_desc,description,address,latitude,longitude,price_level,avg_visit_hours,region,tags)
 VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
$n=0; $byRegion=['east'=>0,'west'=>0];
foreach($rows as $r){
  [$name,$ck,$sd,$desc,$addr,$lat,$lng,$price,$avg,$region,$tags]=$r;
  $slug=slugify($name);
  $n += $ins->execute([$cat[$ck]??1,$name,$slug,$sd,$desc,$addr,$lat,$lng,$price,$avg,$region,$tags]);
  $byRegion[$region]++;
}
echo json_encode(['inserted'=>$n,'by_region'=>$byRegion],JSON_UNESCAPED_UNICODE).PHP_EOL;