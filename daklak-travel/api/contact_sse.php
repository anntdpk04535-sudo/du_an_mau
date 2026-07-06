<?php
// SSE - Server-Sent Events: push reply ngay khi admin gửi
require_once __DIR__ . '/../includes/functions.php';

$contactId = (int)($_GET["contact_id"] ?? 0);
if (!$contactId) {
    http_response_code(400);
    exit("invalid");
}

// SSE headers
header("Content-Type: text/event-stream");
header("Cache-Control: no-cache, no-store");
header("X-Accel-Buffering: no");   // Nginx: tắt buffer
header("Connection: keep-alive");
set_time_limit(0);

// Tắt output buffering hoàn toàn
while (ob_get_level()) ob_end_clean();
if (function_exists("apache_setenv")) apache_setenv("no-gzip", "1");

$lastReplyId = (int)($_GET["last_id"] ?? 0);
$startTime   = time();
$maxDuration = 25; // giây - tránh timeout, client sẽ tự reconnect

function sseWrite($event, $data) {
    echo "event: {$event}\n";
    echo "data: " . json_encode($data) . "\n\n";
    if (ob_get_level()) ob_flush();
    flush();
}

// Gửi ping đầu tiên để báo kết nối OK
sseWrite("ping", ["ts" => time()]);

try {
    $db = getDB();

    while (true) {
        // Kiểm tra client còn kết nối không
        if (connection_aborted()) break;
        if ((time() - $startTime) >= $maxDuration) {
            // Báo client reconnect
            sseWrite("reconnect", ["ts" => time()]);
            break;
        }

        // Query CHỈ lấy reply mới hơn last_id
        $stmt = $db->prepare(
            "SELECT r.id, r.reply_text, r.created_at, u.full_name as admin_name
             FROM contact_replies r
             JOIN users u ON r.admin_id = u.id
             WHERE r.contact_id = ? AND r.id > ?
             ORDER BY r.created_at ASC"
        );
        $stmt->execute([$contactId, $lastReplyId]);
        $newReplies = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($newReplies)) {
            foreach ($newReplies as $r) {
                if ($r["id"] > $lastReplyId) $lastReplyId = $r["id"];
            }
            sseWrite("reply", ["replies" => $newReplies, "last_id" => $lastReplyId]);
        }

        // Sleep ngắn để không burn CPU
        usleep(800000); // 0.8 giây
    }
} catch (Exception $e) {
    sseWrite("error", ["message" => $e->getMessage()]);
}
