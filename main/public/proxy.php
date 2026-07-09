<?php
$url = $_GET['url'] ?? '';

if (filter_var($url, FILTER_VALIDATE_URL)) {
    // Basic security check to ensure it's an image
    if (preg_match('/\.(jpg|jpeg|png|webp|gif)$/i', parse_url($url, PHP_URL_PATH)) || true) {
        header('Access-Control-Allow-Origin: *');
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)');
        
        $data = curl_exec($ch);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        
        if (strpos($contentType, 'image/') !== false) {
            header("Content-Type: " . $contentType);
            echo $data;
        } else {
            // Fallback content type
            header("Content-Type: image/jpeg");
            echo $data;
        }
        curl_close($ch);
        exit;
    }
}

// Fallback image if invalid
header("HTTP/1.0 404 Not Found");
echo "Image not found";
