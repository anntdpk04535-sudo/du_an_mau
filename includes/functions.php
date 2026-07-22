<?php
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/ai.php';


if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'httponly' => true,
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'samesite' => 'Lax'
    ]);
    session_start();
}

if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'vi'; // default language
}

function __(string $key): string
{
    static $langArr = null;
    if ($langArr === null) {
        $langCode = $_SESSION['lang'] ?? 'vi';
        $langFile = __DIR__ . '/lang_' . $langCode . '.php';
        if (file_exists($langFile)) {
            $langArr = require $langFile;
        } else {
            $langArr = require __DIR__ . '/lang_vi.php';
        }
    }
    return $langArr[$key] ?? $key;
}


// Tự động nhận diện đường dẫn gốc của project (vd: /daklak-travel) để mọi link
// hoạt động đúng cả khi project nằm trong thư mục con của domain.
if (!defined('BASE_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    $base = preg_replace('#/(public|admin|api)$#', '', $scriptDir);
    define('BASE_URL', $protocol . $host . rtrim($base, '/'));
}

function url(string $path): string
{
    return BASE_URL . $path;
}

function e(?string $str): string
{
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

function get_avatar(?string $avatarUrl): string
{
    if (empty($avatarUrl)) {
        return url('/assets/images/default-avatar.png');
    }
    
    // Extract the true relative path if it contains /assets/images/
    if (preg_match('#/assets/images/(.*)$#', $avatarUrl, $matches)) {
        return url('/assets/images/' . $matches[1]);
    }
    
    if (str_starts_with($avatarUrl, 'http://') || str_starts_with($avatarUrl, 'https://')) {
        return $avatarUrl;
    }
    
    if (!str_starts_with($avatarUrl, '/')) {
        $avatarUrl = '/' . $avatarUrl;
    }
    return url($avatarUrl);
}


/**
 * Dịch mức chi phí sang tiếng Việt.
 */
function priceLevelVi(?string $level): string
{
    $isEn = ($_SESSION['lang'] ?? 'vi') === 'en';
    return match(strtolower(trim($level ?? ''))) {
        'free'   => $isEn ? 'Free' : 'Miễn phí',
        'low'    => $isEn ? 'Low' : 'Thấp',
        'medium' => $isEn ? 'Medium' : 'Trung bình',
        'high'   => $isEn ? 'High' : 'Cao',
        default  => $level ?? '',
    };
}

function translateDbRow(array $row, array $fields): array
{
    if (($_SESSION['lang'] ?? 'vi') === 'en') {
        foreach ($fields as $field) {
            if (isset($row[$field . '_en']) && $row[$field . '_en'] !== '') {
                $row[$field] = $row[$field . '_en'];
            }
        }
    }
    return $row;
}

function translateItineraryDynamic(array $itinerary): array
{
    if (($_SESSION['lang'] ?? 'vi') === 'en') {
        $title = $itinerary['title'] ?? '';
        // Translate title
        $title = str_replace('Lịch trình', 'Itinerary', $title);
        $title = str_replace('ngày', 'days', $title);
        $title = str_replace('Thiên nhiên', 'Nature', $title);
        $title = str_replace('Văn hoá - bản địa', 'Culture', $title);
        $title = str_replace('Văn hoá', 'Culture', $title);
        $title = str_replace('Ẩm thực', 'Food', $title);
        $title = str_replace('Trekking/mạo hiểm', 'Trekking/Adventure', $title);
        $title = str_replace('Cà phê', 'Coffee', $title);
        $title = str_replace('Gia đình có trẻ nhỏ', 'Family with kids', $title);
        $title = str_replace('Chụp ảnh', 'Photography', $title);
        // Lowercase matches
        $title = str_replace('thiên nhiên', 'nature', $title);
        $title = str_replace('văn hoá - bản địa', 'culture', $title);
        $title = str_replace('văn hoá', 'culture', $title);
        $title = str_replace('ẩm thực', 'food', $title);
        $title = str_replace('cà phê', 'coffee', $title);
        $title = str_replace('gia đình có trẻ nhỏ', 'family with kids', $title);
        $title = str_replace('chụp ảnh', 'photography', $title);
        
        $itinerary['title'] = $title;

        // Translate preferences
        if (!empty($itinerary['preferences'])) {
            $pref = $itinerary['preferences'];
            $pref = str_replace('Thiên nhiên', 'Nature', $pref);
            $pref = str_replace('Văn hoá - bản địa', 'Culture', $pref);
            $pref = str_replace('Văn hoá', 'Culture', $pref);
            $pref = str_replace('Ẩm thực', 'Food', $pref);
            $pref = str_replace('Trekking/mạo hiểm', 'Trekking/Adventure', $pref);
            $pref = str_replace('Cà phê', 'Coffee', $pref);
            $pref = str_replace('Gia đình có trẻ nhỏ', 'Family with kids', $pref);
            $pref = str_replace('Chụp ảnh', 'Photography', $pref);

            $pref = str_replace('thiên nhiên', 'nature', $pref);
            $pref = str_replace('văn hoá - bản địa', 'culture', $pref);
            $pref = str_replace('văn hoá', 'culture', $pref);
            $pref = str_replace('ẩm thực', 'food', $pref);
            $pref = str_replace('cà phê', 'coffee', $pref);
            $pref = str_replace('gia đình có trẻ nhỏ', 'family with kids', $pref);
            $pref = str_replace('chụp ảnh', 'photography', $pref);
            
            $itinerary['preferences'] = $pref;
        }
    }
    return $itinerary;
}

function getAllCategories(): array
{
    $db = getDB();
    $rows = $db->query("SELECT * FROM categories ORDER BY name")->fetchAll();
    foreach ($rows as &$row) {
        $row = translateDbRow($row, ['name']);
    }
    return $rows;
}

function getAllDestinations(?int $categoryId = null, string $keyword = '', string $priceLevel = '', float $minRating = 0, int $limit = 0, int $offset = 0): array
{
    $db = getDB();
    $sql = "
        SELECT d.*,
               COALESCE(AVG(r.rating), d.rating)          AS display_rating,
               ROUND(AVG(r.rating), 1)                    AS avg_rating,
               COUNT(r.id)                                AS review_count
        FROM destinations d
        LEFT JOIN reviews r ON r.destination_id = d.id
        WHERE 1=1
    ";
    
    $params = [];
    
    if ($categoryId) {
        $sql .= " AND d.category_id = ?";
        $params[] = $categoryId;
    }
    
    if ($keyword !== '') {
        $sql .= " AND (d.name LIKE ? OR d.short_desc LIKE ?)";
        $params[] = "%$keyword%";
        $params[] = "%$keyword%";
    }
    
    if ($priceLevel !== '') {
        $sql .= " AND d.price_level = ?";
        $params[] = $priceLevel;
    }
    
    $sql .= " GROUP BY d.id";
    
    if ($minRating > 0) {
        $sql .= " HAVING display_rating >= ?";
        $params[] = $minRating;
    }
    
    $sql .= " ORDER BY display_rating DESC";
    
    if ($limit > 0) {
        $sql .= " LIMIT $limit OFFSET $offset";
    }
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();
    foreach ($rows as &$row) {
        $row = translateDbRow($row, ['name', 'short_desc', 'description']);
    }
    return $rows;
}

function getTotalDestinations(?int $categoryId = null, string $keyword = '', string $priceLevel = '', float $minRating = 0): int
{
    $db = getDB();
    $sql = "
        SELECT COUNT(DISTINCT d.id)
        FROM destinations d
        LEFT JOIN reviews r ON r.destination_id = d.id
        WHERE 1=1
    ";
    
    $params = [];
    
    if ($categoryId) {
        $sql .= " AND d.category_id = ?";
        $params[] = $categoryId;
    }
    
    if ($keyword !== '') {
        $sql .= " AND (d.name LIKE ? OR d.short_desc LIKE ?)";
        $params[] = "%$keyword%";
        $params[] = "%$keyword%";
    }
    
    if ($priceLevel !== '') {
        $sql .= " AND d.price_level = ?";
        $params[] = $priceLevel;
    }
    
    if ($minRating > 0) {
        $sql .= " GROUP BY d.id HAVING COALESCE(AVG(r.rating), d.rating) >= ?";
        $params[] = $minRating;
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return (int)$stmt->fetchColumn();
}

function getDestinationBySlug(string $slug): ?array
{
    $db = getDB();
    $stmt = $db->prepare("
        SELECT d.*,
               COALESCE(AVG(r.rating), d.rating)  AS display_rating,
               ROUND(AVG(r.rating), 1)             AS avg_rating,
               COUNT(r.id)                         AS review_count
        FROM destinations d
        LEFT JOIN reviews r ON r.destination_id = d.id
        WHERE d.slug = ?
        GROUP BY d.id
    ");
    $stmt->execute([$slug]);
    $row = $stmt->fetch();
    if ($row) {
        $row = translateDbRow($row, ['name', 'short_desc', 'description']);
    }
    return $row ?: null;
}

function getDestinationsSummaryForAI(): string
{
    $destinations = getAllDestinations();
    $lines = [];
    foreach ($destinations as $d) {
        $lines[] = sprintf(
            "- ID: %d | %s (slug:%s): %s | địa chỉ: %s | thời gian tham quan ~%sh | mức chi phí: %s | rating %s | tags: %s",
            $d['id'],
            $d['name'],
            $d['slug'],
            $d['short_desc'],
            $d['address'] ?: 'chưa cập nhật',
            $d['avg_visit_hours'],
            $d['price_level'],
            $d['rating'],
            $d['tags']
        );
    }
    return implode("\n", $lines);
}

function currentUser(): ?array
{
    return $_SESSION['user'] ?? null;
}

function requireAdmin(): void
{
    if (empty($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
        header('Location: ' . url('/admin/login.php'));
        exit;
    }
}
