<?php
/**
 * Weather API Proxy
 * Fetches current weather for Buon Ma Thuot via Open-Meteo (server-side)
 * to avoid any CORS / browser-side fetch issues.
 */
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: max-age=600'); // cache 10 minutes

$lat = '12.6667';
$lon = '108.0500';
$url = "https://api.open-meteo.com/v1/forecast?latitude={$lat}&longitude={$lon}&current_weather=true";

$ctx = stream_context_create([
    'http' => [
        'timeout'       => 8,
        'ignore_errors' => true,
    ],
]);

$raw = @file_get_contents($url, false, $ctx);

if ($raw === false) {
    http_response_code(502);
    echo json_encode(['error' => 'upstream_failed']);
    exit;
}

// Forward the response as-is
echo $raw;
