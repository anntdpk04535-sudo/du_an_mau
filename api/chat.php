<?php
@set_time_limit(180);
require_once __DIR__ . '/../includes/rag.php';


header('Content-Type: application/json; charset=utf-8');
$input = json_decode(file_get_contents('php://input') ?: '{}', true) ?: [];
$userMessage = trim((string)($input['message'] ?? ''));
if ($userMessage === '') { echo json_encode(['reply'=>'Bạn hãy nhập câu hỏi nhé!','images'=>[],'results'=>[],'sources'=>[],'retrieval_mode'=>'keyword'], JSON_UNESCAPED_UNICODE); exit; }
if (empty($_SESSION['chat_session_id'])) $_SESSION['chat_session_id'] = bin2hex(random_bytes(16));
$sessionId = $_SESSION['chat_session_id']; $user=currentUser(); $userId=$user['id']??null; $lang=$_SESSION['lang']??'vi';
$retrieved=ragSearch($userMessage,8); $cards=ragResultCards($retrieved);
$context=implode("\n",array_map(fn($r)=>'- ['.$r['entity_type'].':'.$r['entity_id'].'] '.$r['content'],$retrieved));
$context=$context?:'Chưa có bản ghi phù hợp trong cơ sở dữ liệu.';
$history=[]; try{$s=getDB()->prepare('SELECT role,message FROM chat_logs WHERE session_id=? ORDER BY id DESC LIMIT 10');$s->execute([$sessionId]);$history=array_reverse($s->fetchAll());}catch(Throwable){}
$messages=array_map(fn($h)=>['role'=>$h['role'],'content'=>$h['message']],$history); $messages[]=['role'=>'user','content'=>$userMessage];
$system=$lang==='en' ? "You are a Dak Lak and Phu Yen travel assistant. Answer in English, concise and useful. Use only the retrieved database context below; never invent restaurants, hotels, prices or URLs. If context is insufficient, say so.\n\nRETRIEVED CONTEXT:\n{$context}" : "Bạn là trợ lý du lịch Đắk Lắk và Phú Yên. Trả lời tiếng Việt, ngắn gọn và hữu ích. Chỉ dùng ngữ cảnh dữ liệu bên dưới; không tự bịa quán ăn, lưu trú, giá hoặc URL. Nếu thiếu dữ liệu, hãy nói rõ.\n\nNGỮ CẢNH TRUY XUẤT:\n{$context}";
$reply=callGemini($messages,$system,600,0.75);
$images=array_values(array_filter(array_map(fn($c)=>!empty($c['image_url'])?['url'=>$c['image_url'],'alt'=>$c['title'],'entity_type'=>$c['type'],'entity_id'=>$c['id']]:null,$cards)));
try{$db=getDB();$s=$db->prepare("INSERT INTO chat_logs(session_id,user_id,role,message) VALUES(?,?, 'user',?)");$s->execute([$sessionId,$userId,$userMessage]);$s=$db->prepare("INSERT INTO chat_logs(session_id,user_id,role,message) VALUES(?,?, 'assistant',?)");$s->execute([$sessionId,$userId,$reply]);}catch(Throwable){}
$embeddingAvailable=ragEmbedding($userMessage)!==null;
echo json_encode(['reply'=>$reply,'images'=>$images,'results'=>$cards,'sources'=>array_map(fn($r)=>['type'=>$r['entity_type'],'id'=>(int)$r['entity_id'],'score'=>round((float)$r['score'],4)],$retrieved),'retrieval_mode'=>$embeddingAvailable?'semantic':'keyword'],JSON_UNESCAPED_UNICODE);
