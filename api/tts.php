<?php
$text = $_GET['text'] ?? '';
$lang = $_GET['lang'] ?? 'vi';
if (empty($text)) {
    http_response_code(400);
    exit('Text is required');
}

// Split text if it's too long (Google TTS limit is ~200 chars)
$text = mb_substr($text, 0, 200);

$url = "https://translate.google.com/translate_tts?ie=UTF-8&q=" . urlencode($text) . "&tl=" . $lang . "&client=tw-ob";

header('Content-Type: audio/mpeg');
header('Cache-Control: public, max-age=3600');
header('Access-Control-Allow-Origin: *');

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)');
curl_exec($ch);
curl_close($ch);
