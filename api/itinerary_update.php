<?php
require_once __DIR__ . '/../includes/content_helpers.php';
header('Content-Type: application/json; charset=utf-8');
$user=currentUser();
if(!$user){http_response_code(401);echo json_encode(['success'=>false,'error'=>'Bạn cần đăng nhập.']);exit;}
$in=jsonRequest();$id=(int)($in['itinerary_id']??0);$version=(int)($in['version']??0);$items=is_array($in['items']??null)?$in['items']:[];$db=getDB();
try {
  $db->beginTransaction();
  $s=$db->prepare('SELECT version FROM itineraries WHERE id=? AND user_id=? FOR UPDATE');$s->execute([$id,$user['id']]);$current=$s->fetchColumn();
  if($current===false)throw new RuntimeException('Không tìm thấy lịch trình.');
  if($version && (int)$current!==$version){http_response_code(409);throw new RuntimeException('Lịch trình đã được thay đổi ở nơi khác.');}
  $db->prepare('DELETE FROM itinerary_items WHERE itinerary_id=?')->execute([$id]);
  $insert=$db->prepare('INSERT INTO itinerary_items(itinerary_id,destination_id,food_id,accommodation_id,day_number,time_slot,activity,address,transport,reason,suggestion,community_impact,price_min,price_max,is_locked,sort_order) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
  $sort=0;
  foreach($items as $item){
    $type=$item['entity_type']??'destination';$entity=(int)($item['entity_id']??0);
    $did=$type==='destination'?($entity?:null):null;$fid=$type==='food'?($entity?:null):null;$aid=$type==='accommodation'?($entity?:null):null;
    $insert->execute([$id,$did,$fid,$aid,(int)($item['day_number']??1),$item['time_slot']??$item['time']??'',$item['activity']??'',$item['address']??'',$item['transport']??'',$item['reason']??null,$item['suggestion']??null,$item['community_impact']??null,$item['price_min']??null,$item['price_max']??null,!empty($item['is_locked'])?1:0,$sort++]);
  }
  $new=(int)$current+1;$db->prepare('UPDATE itineraries SET version=?,updated_at=CURRENT_TIMESTAMP WHERE id=?')->execute([$new,$id]);$db->commit();echo json_encode(['success'=>true,'version'=>$new]);
}catch(Throwable $e){if($db->inTransaction())$db->rollBack();if(http_response_code()===200)http_response_code(400);echo json_encode(['success'=>false,'error'=>$e->getMessage()],JSON_UNESCAPED_UNICODE);}
