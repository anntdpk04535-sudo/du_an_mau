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
$prompt = "Translate all natural language text values in the following JSON into $langName. DO NOT change any JSON keys, IDs, numeric values, or structure. ONLY translate human-readable sentence/phrase values. Return ONLY the valid JSON without any markdown formatting.\n\n" . json_encode($data, JSON_UNESCAPED_UNICODE);

$translated = callGemini([['role' => 'user', 'content' => $prompt]], "You are a precise JSON translator.", 8192, 0.1, 'application/json');

$clean = trim($translated);
$clean = preg_replace('/^```(?:json)?\s*|\s*```$/m', '', $clean);
$clean = trim($clean, "` \n");

$firstBrace = strpos($clean, '{');
$lastBrace  = strrpos($clean, '}');
if ($firstBrace !== false && $lastBrace !== false && $lastBrace > $firstBrace) {
    $clean = substr($clean, $firstBrace, $lastBrace - $firstBrace + 1);
}

$decoded = json_decode($clean, true);

// Retry if initial JSON decode failed
if (!$decoded) {
    $retryPrompt = "Fix and format the following text into strictly valid JSON without code fences:\n" . $translated;
    $retryTranslated = callGemini([['role' => 'user', 'content' => $retryPrompt]], "You are a JSON fixer.", 8192, 0.0, 'application/json');
    $retryClean = trim(preg_replace('/^```(?:json)?\s*|\s*```$/m', '', trim($retryTranslated)));
    $fBrace = strpos($retryClean, '{');
    $lBrace = strrpos($retryClean, '}');
    if ($fBrace !== false && $lBrace !== false && $lBrace > $fBrace) {
        $retryClean = substr($retryClean, $fBrace, $lBrace - $fBrace + 1);
    }
    $decoded = json_decode($retryClean, true);
}

if ($decoded && is_array($decoded)) {
    echo json_encode(['success' => true, 'data' => $decoded]);
} else {
    // Fallback to original data if AI JSON formatting fails
    echo json_encode(['success' => true, 'data' => $data, 'fallback' => true]);
}
