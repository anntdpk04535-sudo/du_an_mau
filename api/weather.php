<?php
/**
 * Weather API Proxy
 * Fetches current weather for Buon Ma Thuot via Open-Meteo (server-side)
 * with timeout and fallback weather payload if upstream API is unavailable.
 */
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: max-age=600'); // cache 10 minutes

$lat = '12.6667';
$lon = '108.0500';
$url = "https://api.open-meteo.com/v1/forecast?latitude={$lat}&longitude={$lon}&current_weather=true";

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 5,
    CURLOPT_CONNECTTIMEOUT => 3,
    CURLOPT_SSL_VERIFYPEER => false,
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200 && $response) {
    echo $response;
    exit;
}

// Fallback weather data for Buon Ma Thuot if API is unreachable
echo json_encode([
    'latitude' => 12.6667,
    'longitude' => 108.0500,
    'current_weather' => [
        'temperature' => 28.0,
        'windspeed' => 9.2,
        'winddirection' => 110,
        'weathercode' => 1,
        'time' => date('Y-m-d\TH:i')
    ],
    'fallback' => true
], JSON_UNESCAPED_UNICODE);
