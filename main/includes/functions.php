<?php
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/ai.php';


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Tự động nhận diện đường dẫn gốc của project (vd: /daklak-travel) để mọi link
// hoạt động đúng cả khi project nằm trong thư mục con của domain.
if (!defined('BASE_URL')) {
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    // Các trang nằm trong /public hoặc /admin (1 cấp dưới gốc project)
    $base = preg_replace('#/(public|admin)$#', '', $scriptDir);
    define('BASE_URL', rtrim($base, '/'));
}

function url(string $path): string
{
    return BASE_URL . $path;
}

function e(?string $str): string
{
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Dịch mức chi phí sang tiếng Việt.
 */
function priceLevelVi(?string $level): string
{
    return match(strtolower(trim($level ?? ''))) {
        'free'   => 'Miễn phí',
        'low'    => 'Thấp',
        'medium' => 'Trung bình',
        'high'   => 'Cao',
        default  => $level ?? '',
    };
}

function getAllCategories(): array
{
    $db = getDB();
    return $db->query("SELECT * FROM categories ORDER BY name")->fetchAll();
}

function getAllDestinations(?int $categoryId = null): array
{
    $db = getDB();
    $sql = "
        SELECT d.*,
               COALESCE(AVG(r.rating), d.rating)          AS display_rating,
               ROUND(AVG(r.rating), 1)                    AS avg_rating,
               COUNT(r.id)                                AS review_count
        FROM destinations d
        LEFT JOIN reviews r ON r.destination_id = d.id
    ";
    if ($categoryId) {
        $stmt = $db->prepare($sql . " WHERE d.category_id = ? GROUP BY d.id ORDER BY display_rating DESC");
        $stmt->execute([$categoryId]);
        return $stmt->fetchAll();
    }
    return $db->query($sql . " GROUP BY d.id ORDER BY display_rating DESC")->fetchAll();
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
    return $row ?: null;
}

function getDestinationsSummaryForAI(): string
{
    $destinations = getAllDestinations();
    $lines = [];
    foreach ($destinations as $d) {
        $lines[] = sprintf(
            "- %s (slug:%s): %s | địa chỉ: %s | thời gian tham quan ~%sh | mức chi phí: %s | rating %s | tags: %s",
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
