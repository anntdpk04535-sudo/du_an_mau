<?php
declare(strict_types=1);
/* Seed ẩm thực Đắk Lắk Đông/Tây. entity_type: dish / restaurant / cafe.
   Dữ liệu là các món ăn, quán, cà phê thực tế nổi tiếng của Đắk Lắk. */
require_once __DIR__ . '/seed_helpers.php';
$db = dd();

// [name, entity_type, description, region, price_min, price_max, address]
$rows = [
// ====== TÂY ======
['Cá lăng sông Serepok','dish','Cá lăng tươi chế biến lẩu, nướng, ấq đặc sản sông Serepok, thịt chắc ngọt.','west',200,350,'Buôn Đôn'],
['Gà nướng Buôn Đôn','dish','Gà thả vườn tẩm ướp gia vị đậm đà, nướng nguyên con than hồng.','west',180,320,'TT. Buôn Đôn'],
['Cơm lam Buôn Đôn','dish','Cơm nấu trong ống nứa, dẻo thơm mùi nứa, ăn kèm muối mè.','west',40,80,'Buôn Đôn'],
['Rượu cần M\'nông','dish','Rượu cần truyền thống uống bằng cần trúc, vị men cay nồng đặc trưng Tây Nguyên.','west',80,150,'Buôn Đôn, hồ Lắk'],
['Bò một nắng','dish','Thịt bò phơi một nắng, nướng lên ăn kèm nước chấm đặc biệt.','west',200,300,'Krông Ana'],
['Mật ong hoa dã quỳ','dish','Mật ong dã quỳ nguyên chất, thơm ngon của vùng Tây Nguyên.','west',150,250,'Ea Súp, Krông Bông'],
['Heo rừng nướng Buôn Đôn','dish','Heo rừng nướng nguyên con, thịt săn chắc, thấm gia vị.','west',250,400,'Buôn Đôn'],
['Cà đắng xào','dish','Món rau cà đắng đặc sản của đồng bào Tây Nguyên, xào với thịt.','west',50,80,'Lắk, Cư Kuin'],
['Thịt trâu gác bếp','dish','Thịt trâu tẩm ướp gia vị rừng, gác bếp khói, ăn kèm chẩm chéo.','west',250,350,'Lắk, Krông Bông'],
['Rau đồ (măng, nấm, rau rừng)','dish','Các loại rau rừng, măng, nấm đồ chín, chấm muối mè.','west',40,60,'Buôn Đôn'],
['Canh chua cá lăng','dish','Canh chua cá lăng với măng chua, rau thơm rừng.','west',150,250,'Buôn Đôn'],
['Chẩm chéo (Muối chấm Tây Nguyên)','dish','Muối chấm đặc sản từ mắc khén, ớt xiêm, tỏi rừng.','west',20,50,'Cả tỉnh'],
['Cơm lam gà rừng Lắk','dish','Cơm lam ăn với gà rừng nướng, đặc sản vùng hồ Lắk.','west',200,300,'Huyện Lắk'],
['Cá nướng hồ Lắk','dish','Cá nướng tươi tại hồ Lắk, ăn kèm rau rừng.','west',150,250,'Huyện Lắk'],
['Gỏi lá rừng','dish','Gỏi cuốn nhiều loại lá rừng, thịt luộc, nước chấm đặc trưng.','west',60,100,'Buôn Đôn'],
['Rượu ghè hồ Lắk','dish','Rượu ghè của người M\'nông bên hồ Lắk, men lá rừng.','west',100,180,'Huyện Lắk'],
['Lẩu cá lăng sông Serepok','restaurant','Lẩu cá lăng chua cay, nấu măng chua, ăn cùng rau rừng.','west',250,450,'Buôn Đôn'],
['Gà nướng cơm lam quán ven hồ','restaurant','Quán gà nướng cơm lam ngay bờ hồ Lắk, ngắm cảnh.','west',250,400,'TT. Liên Sơn, Lắk'],
['Nhà hàng cá sông Serepok','restaurant','Nhà hàng chuyên cá lăng, cá chiên giòn ven sông.','west',250,500,'Buôn Đôn'],
['Quán đặc sản buôn làng','restaurant','Quán phục vụ đặc sản Tây Nguyên: cơm lam, gà nướng, rượu cần.','west',150,300,'Krông Ana'],
['Cà phê vối Buôn Đôn','cafe','Cà phê vối rang xay truyền thống của đồng bào nơi đây.','west',20,40,'Buôn Đôn'],
['Cà phê cội già Yok Đôn','cafe','Cà phê thưởng thức giữa rừng, cạnh cây cội già.','west',30,60,'VQG Yok Đôn'],
['Cà phê bên hồ Lắk','cafe','Thưởng thức cà phê, ngắm hoàng hôn trên hồ Lắk.','west',30,60,'Huyện Lắk'],
['Nước ép trái cây Tây Nguyên','cafe','Nước ép bơ, sầu riêng, mãng cầu xiêm tươi.','west',25,50,'Buôn Đôn, Lắk'],
['Rau rừng hấp nước dừa','dish','Rau rừng hấp nước dừa, chấm mắm ớt, thanh mát.','west',40,70,'Krông Ana'],
['Mắm cà pháo Tây Nguyên','dish','Mắm cà pháo ăn với thịt luộc, đặc sản dân dã.','west',30,50,'Cả tỉnh'],
// ====== ĐÔNG ======
['Phở khô Buôn Ma Thuột','dish','Món phở khô đặc trưng của thủ phủ cà phê, nước sốt đậm đà.','east',50,80,'TP. Buôn Ma Thuột'],
['Bánh mì cay Buôn Ma Thuột','dish','Bánh mì cay truyền thống với nước sốt cà chua thơm ngon.','east',20,40,'TP. Buôn Ma Thuột'],
['Gỏi đu đủ khô bò','dish','Gỏi đu đủ bào, khô bò, đậu phộng, nước mắm chua ngọt.','east',50,80,'TP. Buôn Ma Thuột'],
['Sữa đậu nành nóng','dish','Sữa đậu nành nóng thơm ngon, quán ăn sáng quen thuộc.','east',15,30,'TP. Buôn Ma Thuột'],
['Cà phê phin nguyên chất','cafe','Cà phê rang xay nguyên chất thủ phủ cà phê, nhỏ phin truyền thống.','east',20,50,'TP. Buôn Ma Thuột'],
['Cà phê sữa đá','cafe','Cà phê sữa đá đậm vị Tây Nguyên.','east',20,40,'TP. Buôn Ma Thuột'],
['Cà phê cacao','cafe','Cà phê pha cùng cacao, hương vị đặc sắc của Đắk Lắk.','east',30,50,'TP. Buôn Ma Thuột'],
['Cơm tấm sườn nướng','restaurant','Cơm tấm sườn nướng với nước mắm ngon.','east',50,80,'TP. Buôn Ma Thuột'],
['Bánh cuốn nóng','restaurant','Bánh cuốn mỏng, nhân tôm thịt, chấm nước mắm chanh ớt.','east',40,60,'TP. Buôn Ma Thuột'],
['Cháo lòng, lòng heo','restaurant','Cháo lòng, lòng heo, huyết luộc ăn sáng.','east',30,60,'TP. Buôn Ma Thuột'],
['Bún bò Buôn Ma Thuột','restaurant','Bún bò giò heo thơm ngon nước dùng đậm đà.','east',40,70,'TP. Buôn Ma Thuột'],
['Hủ tiếu Nam Vang','restaurant','Hủ tiếu Nam Vang đầy đủ tôm thịt gan.','east',40,60,'TP. Buôn Ma Thuột'],
['Bánh xèo mực','restaurant','Bánh xèo giòn rụm, nhân mực tươi.','east',60,100,'TP. Buôn Ma Thuột'],
['Bánh tráng nướng','dish','Bánh tráng nướng giòn phết trứng, pate, khô bò.','east',15,30,'TP. Buôn Ma Thuột'],
['Cơm chay chùa','restaurant','Cơm chay thanh đạm, món chay thực dưỡng.','east',30,50,'TP. Buôn Ma Thuột'],
['Lẩu bò Tây Nguyên','restaurant','Lẩu bò nhúng giấm, thịt bò tươi, nước dùng chua cay.','east',200,350,'TP. Buôn Ma Thuột'],
['Bánh ướt thịt nướng','restaurant','Bánh ướt mềm cuốn thịt nướng, nước mắm chua ngọt.','east',40,60,'TP. Buôn Ma Thuột'],
['Trà chanh dây','cafe','Trà chanh dây tươi mát, giải khát.','east',20,35,'TP. Buôn Ma Thuột'],
['Sinh tố bơ dừa','cafe','Sinh tố bơ béo ngậy, thạch dừa.','east',30,50,'TP. Buôn Ma Thuột'],
['Nước mía nguyên chất','cafe','Nước mía ép tươi, thêm quất, gừng.','east',15,25,'TP. Buôn Ma Thuột'],
['Cà phê chồn Trung Nguyên','cafe','Cà phê chồn hảo hạng, hương vị độc đáo.','east',80,150,'TP. Buôn Ma Thuột'],
['Cà phê muối Buôn Ma Thuột','cafe','Cà phê muối đặc sản, vị béo mặn ngọt độc đáo.','east',30,50,'TP. Buôn Ma Thuột'],
['Socola ca cao Ea Kar','dish','Socola làm từ ca cao sạch Ea Kar, vị đậm.','east',100,150,'Ea Kar'],
['Bánh dân gian Ê Đê (Bánh tét, bánh dày)','dish','Các loại bánh truyền thống của người Ê Đê.','east',30,60,'Krông Năng'],
['Cơm gà xé','restaurant','Cơm gà xé ăn kèm nước mắm gừng, hành phi.','east',40,60,'TP. Buôn Ma Thuột'],
['Cháo vịt','restaurant','Cháo vịt thịt vịt ngọt, tiêu gừng.','east',40,60,'TP. Buôn Ma Thuột'],
['Bún riêu cua','restaurant','Bún riêu cua đồng, gạch cua béo.','east',40,60,'TP. Buôn Ma Thuột'],
['Xôi xéo','restaurant','Xôi xéo đỗ xanh, hành phi, ăn sáng.','east',20,30,'TP. Buôn Ma Thuột'],
['Bánh khọt','restaurant','Bánh khọt giòn, tôm tươi, nước chấm chua ngọt.','east',40,70,'TP. Buôn Ma Thuột'],
['Bánh căn trứng','restaurant','Bánh căn nóng, trứng, chấm nước mắm nêm.','east',30,50,'TP. Buôn Ma Thuột'],
['Bò nướng lá lốt','restaurant','Bò nướng lá lốt thơm lừng, chấm nước mắm chua ngọt.','east',120,200,'TP. Buôn Ma Thuột'],
['Ốc nướng tiêu xanh','restaurant','Ốc nướng tiêu xanh, sả ớt đậm vị.','east',80,150,'TP. Buôn Ma Thuột'],
['Lẩu gà lá é','restaurant','Lẩu gà lá é thơm nức, bổ dưỡng.','east',150,250,'TP. Buôn Ma Thuột'],
['Chè thập cẩm','restaurant','Chè đủ loại đậu, thạch, nước cốt dừa.','east',20,35,'TP. Buôn Ma Thuột'],
['Rau má dừa','cafe','Rau má dừa mát lạnh, giải nhiệt.','east',20,30,'TP. Buôn Ma Thuột'],
['Sinh tố bơ cà phê','cafe','Sinh tố bơ kết hợp cà phê, đặc sản.','east',35,55,'TP. Buôn Ma Thuột'],
['Cà phê nâu Trung Nguyên','cafe','Cà phê nâu rang mộc, đậm vị truyền thống.','east',25,45,'TP. Buôn Ma Thuột'],
['Bia tươi các quán nhậu','restaurant','Bia tươi kèm mồi nhậu: lòng, móng giò, thịt bò.','east',150,300,'TP. Buôn Ma Thuột'],
['Ốc len xào dừa','restaurant','Ốc len xào dừa béo ngậy.','east',50,80,'TP. Buôn Ma Thuột'],
['Nem chua Tây Nguyên','dish','Nem chua chua cay, ăn kèm lá sung.','east',50,80,'TP. Buôn Ma Thuột'],
['Bánh gio (bánh ú tro)','dish','Bánh gio mát, ăn với đường mật.','east',15,25,'TP. Buôn Ma Thuột'],
['Chè bắp','restaurant','Chè bắp ngô non, béo ngọt.','east',15,25,'TP. Buôn Ma Thuột'],
['Xôi gà','restaurant','Xôi gà nóng hổi, thịt gà xé, hành phi.','east',35,50,'TP. Buôn Ma Thuột'],
];

$ins = $db->prepare("INSERT IGNORE INTO foods
 (region,entity_type,name,name_en,slug,description,address,price_min,price_max,last_verified_at)
 VALUES (?,?,?,?,?,?,?,?,?,CURRENT_DATE)");
$n=0; $byRegion=['east'=>0,'west'=>0];
foreach($rows as $r){
  [$name,$type,$desc,$region,$min,$max,$addr]=$r;
  $slug=slugify($name);
  try {
    if($ins->execute([$region,$type,$name,$name,$slug,$desc,$addr,$min,$max])) $n++;
    $byRegion[$region]++;
  } catch (Throwable $e) { fwrite(STDERR,"ERR $name: ".$e->getMessage()."\n"); }
}
echo json_encode(['attempted'=>count($rows),'inserted'=>$n,'by_region'=>$byRegion],JSON_UNESCAPED_UNICODE).PHP_EOL;