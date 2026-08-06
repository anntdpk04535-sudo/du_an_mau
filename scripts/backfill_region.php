<?php
declare(strict_types=1);
$db = new PDO('mysql:unix_socket=/Applications/XAMPP/xamppfiles/var/mysql/mysql.sock;dbname=daklak_travel', 'root', '', [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$rows = $db->query("SELECT id,address FROM destinations")->fetchAll();
$westDistricts = ['lắk','buôn đôn','krông ana','krông ană','ea súp','ea sup','krông bông','cư kuin'];
$eastDistricts = ['buôn ma thuột','tp. buôn','ea h\'leo','ea hle','krông pắc','krông pac','ea kar','m\'đrắk','mdrắk','m\'drak','cư m\'gar','cư mgar','krông năng(E)'];
$st = $db->prepare("UPDATE destinations SET region=? WHERE id=?");
$n=0;
foreach($rows as $r){
  $a=mb_strtolower((string)$r['address'],'UTF-8');
  if(!$a || mb_strpos($a,'phú yên')!==false) continue;
  // Lấy phần trước dấu phẩy -> district/tp
  $district=trim(strtok($a,",") ?: $a);
  $isWest=false;
  foreach($westDistricts as $w){ if(mb_strpos($district,$w)!==false){$isWest=true;break;} }
  $st->execute([$isWest?'west':'east',$r['id']]); $n++;
}
echo "backfilled $n destinations\n";