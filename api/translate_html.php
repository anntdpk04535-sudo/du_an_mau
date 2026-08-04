<?php
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$html = $input['html'] ?? '';
$targetLang = $_SESSION['lang'] ?? 'vi';

if (empty($html)) {
    echo json_encode(['success' => false]);
    exit;
}

$prompt = "Translate the text contents of the following HTML into " . ($targetLang === 'en' ? 'English' : 'Vietnamese') . ". Do not modify HTML tags, classes, or IDs. Only translate the human-readable text (keep emojis intact). Return ONLY the HTML code:\n\n" . $html;

$translated = callGemini([['role' => 'user', 'content' => $prompt]], "You are a precise HTML translator.", 8192, 0.1);

$clean = trim($translated);
$clean = preg_replace('/^```html\s*|\s*```$/m', '', $clean);
$clean = trim($clean, "` \n");

echo json_encode(['success' => true, 'html' => $clean]);
