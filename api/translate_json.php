<?php
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
set_time_limit(120); // API gọi AI dịch Json có thể tốn thời gian

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$data = $input['data'] ?? [];
$targetLang = $_SESSION['lang'] ?? 'vi';

if (empty($data)) {
    echo json_encode(['success' => false]);
    exit;
}

$langName = $targetLang === 'en' ? 'English' : 'Vietnamese';
$prompt = "Translate all text values in the following JSON into $langName. DO NOT change the JSON structure or keys. ONLY translate the string values. Return ONLY the valid JSON without any markdown formatting.\n\n" . json_encode($data, JSON_UNESCAPED_UNICODE);

$translated = callGemini([['role' => 'user', 'content' => $prompt]], "You are a precise JSON translator.", 2500, 0.1, 'application/json');

$translated = trim($translated);

// Cố gắng trích xuất cục JSON phòng khi Gemini trả về thêm văn bản rườm rà
if (preg_match('/\{.*\}/s', $translated, $matches)) {
    $decoded = json_decode($matches[0], true);
} else {
    $decoded = json_decode($translated, true);
}

if ($decoded) {
    echo json_encode(['success' => true, 'data' => $decoded]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to parse JSON', 'raw' => $translated]);
}
