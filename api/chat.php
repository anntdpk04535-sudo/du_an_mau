<?php
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$userMessage = trim((string) ($input['message'] ?? ''));

if ($userMessage === '') {
    echo json_encode(['reply' => 'Bạn hãy nhập câu hỏi nhé!', 'images' => []]);
    exit;
}

if (empty($_SESSION['chat_session_id'])) {
    $_SESSION['chat_session_id'] = bin2hex(random_bytes(16));
}
$sessionId = $_SESSION['chat_session_id'];
$user = currentUser();
$userId = $user['id'] ?? null;

$destinationsContext = getDestinationsSummaryForAI();

$lang = $_SESSION['lang'] ?? 'vi';
$randomSeed = mt_rand(1000, 9999);

$approachTexts = [
    'vi' => [
        'Hãy trả lời một cách hóm hỉnh, vui tươi và tràn đầy năng lượng.',
        'Hãy đóng vai một hướng dẫn viên du lịch bản địa nhiệt tình, hiếu khách.',
        'Hãy trả lời ngắn gọn, súc tích nhưng đầy đủ thông tin hữu ích nhất.',
        'Hãy kể một câu chuyện nhỏ hoặc chia sẻ một sự thật thú vị về địa điểm được hỏi nếu có thể.',
        'Hãy trả lời như một người bạn thân đang gợi ý chỗ chơi cho bạn mình.',
    ],
    'en' => [
        'Answer in a witty, cheerful, and energetic manner.',
        'Act as an enthusiastic and hospitable local tour guide.',
        'Keep the answer short, concise, but full of the most useful information.',
        'Tell a short story or share an interesting fact about the requested location if possible.',
        'Answer like a close friend suggesting a place to hang out.',
    ]
];
$randomApproach = array_rand($approachTexts[$lang]);
$approachText = $approachTexts[$lang][$randomApproach];

$foodListVi = [
    'Bún đỏ cô Thu (Ngã 4 Phan Đình Giót - Lê Hồng Phong)',
    'Quán Cà Te (140 Lê Thánh Tông)',
    'Phở hai tô 52 (52 Ngô Quyền)',
    'Bánh ướt chồng dĩa 45 (45 Lê Thánh Tông)',
    'Bún chìa Cô Chua (222 Nguyễn Tất Thành)',
    'Bò nhúng me Cà Te Quán (140 Lê Thánh Tông)',
    'Nem nướng Thanh Hùng (D11, Nguyễn Đình Chiểu)',
    'Bánh canh cá lóc xắt (đường Bà Triệu)',
    'Gà nướng Bản Đôn (Buôn Đôn)'
];
$foodListEn = [
    'Bun do Co Thu (Phan Dinh Giot - Le Hong Phong crossroads)',
    'Quan Ca Te (140 Le Thanh Tong)',
    'Pho hai to 52 (52 Ngo Quyen)',
    'Banh uot chong dia 45 (45 Le Thanh Tong)',
    'Bun chia Co Chua (222 Nguyen Tat Thanh)',
    'Bo nhung me Ca Te Quan (140 Le Thanh Tong)',
    'Nem nuong Thanh Hung (D11, Nguyen Dinh Chieu)',
    'Banh canh ca loc xat (Ba Trieu st.)',
    'Ga nuong Ban Don (Buon Don)'
];

$cafeList = [
    'Làng cà phê Trung Nguyên (163 Lý Thái Tổ)',
    'Arul Coffee (17 Trần Nhật Duật)',
    'SOUL Roastery (87 Nguyễn Khuyến)',
    'Lee\'s Hillside (55 đường số 3, Cư Bur)',
    'House Of Lens Coffee (14/19 Cù Chính Lan)',
    'Shinin\' (146 Ngô Quyền)',
    'Tỏi Đá Cafe'
];

$hotelList = [
    'Lee\'s House (55 đường số 3, Cư Bur)',
    'Zan Homestay (37 Hồ Giáo)',
    'Lak Tented Camp (ven Hồ Lắk)',
    'The Highland House (79 Văn Tiến Dũng)',
    'Khách sạn Mường Thanh (81 Nguyễn Tất Thành)',
    'Khách sạn Elephants (142 Hai Bà Trưng)',
    'Khách sạn Hai Bà Trưng (8 Hai Bà Trưng)',
    'Sài Gòn - Ban Mê (01-03 Phan Chu Trinh)',
    'Huyen Thoai Homestay (Buôn Đôn)'
];

shuffle($foodListVi);
shuffle($foodListEn);
shuffle($cafeList);
shuffle($hotelList);

$randomFoodsVi = implode(', ', array_slice($foodListVi, 0, 3));
$randomFoodsEn = implode(', ', array_slice($foodListEn, 0, 3));
$randomCafes = implode(', ', array_slice($cafeList, 0, 3));
$randomHotels = implode(', ', array_slice($hotelList, 0, 3));

if ($lang === 'en') {
    $systemPrompt = <<<SYS
You are a friendly AI travel assistant, deeply knowledgeable about Dak Lak province, Vietnam (Central Highlands).
{$approachText}
Please answer naturally, usefully, and in English. Prioritize mentioning destinations from the following data list if relevant:

{$destinationsContext}

YOU ARE EQUIPPED WITH THE FOLLOWING ADDITIONAL KNOWLEDGE FOR CONSULTING (suggest when appropriate):
- Transport: Arrive in Dak Lak by plane or sleeper bus. For local travel, rent a motorbike (100k-150k/day) or use taxi/Grab.
- Food & Dining: {$randomFoodsEn}. Beautiful cafes: {$randomCafes}.
- Accommodation: {$randomHotels}.
- Clothing: Wear sneakers during the day. Bring a light jacket for cool evenings. For photos, wear Vintage, Boho styles or rent ethnic costumes.
- Souvenirs: Coffee, dried beef/venison, coffee flower honey, macadamia nuts, Ede/M'Nong brocade.

VERY IMPORTANT:
Absolutely DO NOT repeat the list of food places, cafes, or accommodations that you have already suggested in your previous responses (in the chat history). Always prioritize providing completely new suggestions so the user has multiple choices.

If the question is not related to Dak Lak tourism, politely and tactfully guide the user back to the topic of Dak Lak tourism.
Present the answer clearly and elegantly with proper line breaks between items/days. Use bold (**Day Title / Location Name**) to highlight important information.
Do not mention that you cannot send images - the system will handle that automatically.
SYS;
} else {
    $systemPrompt = <<<SYS
Bạn là trợ lý AI tư vấn du lịch thân thiện, am hiểu sâu về tỉnh Đắk Lắk, Việt Nam (Tây Nguyên).
{$approachText}
Hãy trả lời tự nhiên, hữu ích, bằng tiếng Việt. Ưu tiên nhắc tới các điểm đến có trong danh sách dữ liệu sau nếu liên quan:

{$destinationsContext}

BẠN ĐƯỢC TRANG BỊ KIẾN THỨC BỔ SUNG SAU ĐỂ TƯ VẤN (hãy gợi ý khi phù hợp):
- Phương tiện: Đến Đắk Lắk bằng máy bay, xe khách giường nằm. Di chuyển tại chỗ nên thuê xe máy (100k-150k/ngày) hoặc taxi/Grab.
- Ẩm thực & Quán ăn: {$randomFoodsVi}. Quán cà phê đẹp: {$randomCafes}.
- Lưu trú: {$randomHotels}.
- Trang phục: Ban ngày mang giày thể thao. Buổi tối mang áo khoác mỏng vì se lạnh. Chụp ảnh nên mặc đồ Vintage, Boho hoặc thuê trang phục đồng bào.
- Quà lưu niệm: Cà phê, thịt bò/nai khô, mật ong hoa cà phê, hạt mắc ca, đồ thổ cẩm Ê-đê/M'Nông.

LƯU Ý CỰC KỲ QUAN TRỌNG: 
Tuyệt đối KHÔNG lặp lại danh sách các quán ăn, quán cà phê hay địa điểm đã gợi ý trong các câu trả lời trước đó của bạn (trong phần lịch sử trò chuyện). Luôn luôn ưu tiên cung cấp các gợi ý hoàn toàn mới để người dùng có nhiều lựa chọn.

Nếu câu hỏi không liên quan đến du lịch Đắk Lắk, vẫn trả lời lịch sự nhưng khéo léo hướng người dùng quay lại chủ đề du lịch Đắk Lắk.
Trình bày bố cục rõ ràng, đẹp mắt với ngắt dòng thích hợp giữa các mục/ngày. Dùng in đậm (**Tên ngày / Địa điểm / Thời gian**) để làm nổi bật thông tin quan trọng.
Không đề cập việc bạn không thể gửi hình ảnh — hệ thống sẽ tự xử lý phần đó.
SYS;
}

try {
    $db = getDB();
    $histStmt = $db->prepare(
        "SELECT role, message FROM chat_logs WHERE session_id = ? ORDER BY id DESC LIMIT 10"
    );
    $histStmt->execute([$sessionId]);
    $history = array_reverse($histStmt->fetchAll());
} catch (Exception $e) {
    $history = [];
}

$messages = [];
foreach ($history as $h) {
    $messages[] = ['role' => $h['role'], 'content' => $h['message']];
}

$hiddenDirective = $lang === 'en' 
    ? "\n\n[System directive: Answer strictly based on the user's specific request. If they ask for food/cafes/hotels, prioritize using the newly provided destinations in the system prompt. Absolutely DO NOT repeat destinations you have already suggested in previous responses.]"
    : "\n\n[Chỉ thị hệ thống: Trả lời ĐÚNG TRỌNG TÂM câu hỏi. Nếu người dùng hỏi về quán ăn, cà phê, hay nơi nghỉ ngơi, hãy ưu tiên chọn các địa điểm mới được cung cấp trong kiến thức bổ sung. TUYỆT ĐỐI KHÔNG lặp lại các địa điểm mà bạn đã gợi ý trong các câu trả lời trước.]";

$augmentedUserMessage = "[#REQ-{$randomSeed}] " . $userMessage . $hiddenDirective;
$messages[] = ['role' => 'user', 'content' => $augmentedUserMessage];

// Dùng temperature ngẫu nhiên từ 0.75 đến 0.95 để tăng tính sáng tạo và tự nhiên
$temperature = round(mt_rand(75, 95) / 100, 2);
$reply = callGemini($messages, $systemPrompt, 600, $temperature);

$images = [];

// Lưu lịch sử chat
try {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO chat_logs (session_id, user_id, role, message) VALUES (?, ?, 'user', ?)");
    $stmt->execute([$sessionId, $userId, $userMessage]);

    $stmt = $db->prepare("INSERT INTO chat_logs (session_id, user_id, role, message) VALUES (?, ?, 'assistant', ?)");
    $stmt->execute([$sessionId, $userId, $reply]);
} catch (Exception $e) {
    // Bỏ qua lỗi lưu DB
}

echo json_encode(['reply' => $reply, 'images' => $images]);