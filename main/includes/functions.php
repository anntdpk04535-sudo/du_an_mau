<?php
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/ai.php';


session_start();

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
    if ($categoryId) {
        $stmt = $db->prepare("SELECT * FROM destinations WHERE category_id = ? ORDER BY rating DESC");
        $stmt->execute([$categoryId]);
        return $stmt->fetchAll();
    }
    return $db->query("SELECT * FROM destinations ORDER BY rating DESC")->fetchAll();
}

function getDestinationBySlug(string $slug): ?array
{
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM destinations WHERE slug = ?");
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
