<?php
/**
 * Cấu hình AI - gọi Google Gemini API
 * Đăng ký lấy API key tại: https://aistudio.google.com/app/apikey
 *
 * Key được đọc từ file .env (xem .env.example), KHÔNG hard-code trong file này
 * để tránh lộ key khi đẩy code lên GitHub.
 */
require_once __DIR__ . '/env.php';

define('GEMINI_API_KEY', getenv('GEMINI_API_KEY') ?: '');
define('GEMINI_MODEL', 'gemini-3.5-flash');
define('GEMINI_API_URL', 'https://generativelanguage.googleapis.com/v1beta/models/' . GEMINI_MODEL . ':generateContent');

/**
 * Gọi Gemini API với 1 hoặc nhiều message.
 *
 * @param array  $messages  [['role'=>'user'|'assistant','content'=>'...'], ...]
 * @param string $system    System prompt (vai trò/định hướng AI)
 * @param int    $maxTokens
 * @return string Nội dung trả lời (text) hoặc thông báo lỗi
 */
function callGemini(array $messages, string $system = '', int $maxTokens = 1024, float $temperature = 1.0, string $responseMimeType = 'text/plain'): string
{
    if (empty(GEMINI_API_KEY)) {
        return 'Lỗi: Chưa cấu hình GEMINI_API_KEY trong file .env (xem .env.example).';
    }

    // Gemini dùng role 'user' / 'model' (không có 'assistant')
    $contents = [];
    foreach ($messages as $m) {
        $role = $m['role'] === 'assistant' ? 'model' : 'user';
        $contents[] = [
            'role' => $role,
            'parts' => [['text' => $m['content']]],
        ];
    }

    $payload = [
        'contents' => $contents,
        'generationConfig' => [
            'maxOutputTokens' => $maxTokens,
            'temperature' => $temperature,
            'responseMimeType' => $responseMimeType,
            // Tắt "thinking" để model không tốn token suy nghĩ ngầm,
            // tránh bị cắt cụt phần trả lời thực tế (đặc biệt khi yêu cầu JSON dài).
            'thinkingConfig' => ['thinkingBudget' => 0],
        ],
    ];
    if ($system !== '') {
        $payload['systemInstruction'] = [
            'parts' => [['text' => $system]],
        ];
    }

    $ch = curl_init(GEMINI_API_URL . '?key=' . GEMINI_API_KEY);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
        ],
        CURLOPT_TIMEOUT => 120,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        return 'Lỗi kết nối AI: ' . $curlError;
    }

    $data = json_decode($response, true);

    if ($httpCode !== 200) {
        $msg = $data['error']['message'] ?? 'Lỗi không xác định từ API';
        return 'Lỗi AI (' . $httpCode . '): ' . $msg;
    }

    $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
    $finishReason = $data['candidates'][0]['finishReason'] ?? null;

    if ($text === null) {
        if ($finishReason === 'MAX_TOKENS') {
            return 'Lỗi: AI bị cắt do vượt giới hạn token, hãy tăng maxTokens khi gọi callGemini().';
        }
        return 'AI không trả về nội dung hợp lệ.';
    }

    return $text;
}
