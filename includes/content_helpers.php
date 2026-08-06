<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';

function tableExists(PDO $db, string $table): bool
{
    if (!preg_match('/^[A-Za-z0-9_]+$/', $table)) return false;
    try {
        $stmt = $db->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?');
        $stmt->execute([$table]);
        return (int)$stmt->fetchColumn() > 0;
    } catch (Throwable) {
        return false;
    }
}

function columnExists(PDO $db, string $table, string $column): bool
{
    if (!preg_match('/^[A-Za-z0-9_]+$/', $table . $column)) return false;
    try { $s=$db->prepare('SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name=? AND column_name=?');$s->execute([$table,$column]);return (int)$s->fetchColumn()>0; } catch (Throwable) { return false; }
}

function fetchEntityImages(PDO $db, string $table, string $foreignKey, int $id): array
{
    if (!tableExists($db, $table) || !preg_match('/^[A-Za-z0-9_]+$/', $table . $foreignKey)) return [];
    $stmt = $db->prepare("SELECT id, image_url, alt_text, caption, is_primary, sort_order FROM {$table} WHERE {$foreignKey} = ? ORDER BY is_primary DESC, sort_order ASC, id ASC");
    $stmt->execute([$id]);
    return $stmt->fetchAll() ?: [];
}

function uploadLocalImage(array $file, string $folder, int $maxBytes = 5242880): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'] ?? '')) return null;
    if (($file['size'] ?? 0) <= 0 || $file['size'] > $maxBytes) throw new RuntimeException('Ảnh vượt quá kích thước cho phép.');
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($allowed[$mime])) throw new RuntimeException('Định dạng ảnh không được hỗ trợ.');
    $dimensions = @getimagesize($file['tmp_name']);
    if (!$dimensions || $dimensions[0] > 10000 || $dimensions[1] > 10000) throw new RuntimeException('Kích thước ảnh không hợp lệ.');
    $folder = trim($folder, '/');
    $baseDir = __DIR__ . '/../assets/images/uploads/' . $folder;
    if (!is_dir($baseDir) && !mkdir($baseDir, 0755, true) && !is_dir($baseDir)) throw new RuntimeException('Không thể tạo thư mục upload.');
    $name = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
    $target = $baseDir . '/' . $name;
    if (!move_uploaded_file($file['tmp_name'], $target)) throw new RuntimeException('Không thể lưu ảnh.');
    return url('/assets/images/uploads/' . $folder . '/' . $name);
}

function jsonRequest(): array
{
    $payload = json_decode(file_get_contents('php://input') ?: '{}', true);
    return is_array($payload) ? $payload : [];
}

function getAllEvents(?string $category = null, string $status = 'published'): array
{
    $db = getDB();
    if (!tableExists($db, 'events')) return [];
    
    $sql = "SELECT * FROM `events` WHERE status = ?";
    $params = [$status];

    if (!empty($category) && $category !== 'all') {
        $sql .= " AND category = ?";
        $params[] = $category;
    }

    $sql .= " ORDER BY start_date ASC";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll() ?: [];
}

function getFeaturedEvents(int $limit = 3): array
{
    $db = getDB();
    if (!tableExists($db, 'events')) return [];
    
    $stmt = $db->prepare("SELECT * FROM `events` WHERE status = 'published' AND is_featured = 1 ORDER BY start_date ASC LIMIT ?");
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll() ?: [];
}

function getEventBySlug(string $slug): ?array
{
    $db = getDB();
    if (!tableExists($db, 'events')) return null;
    
    $stmt = $db->prepare("SELECT * FROM `events` WHERE slug = ? LIMIT 1");
    $stmt->execute([$slug]);
    $res = $stmt->fetch();
    return $res ?: null;
}

function getFeaturedDestinations(int $limit = 6): array
{
    $db = getDB();
    $stmt = $db->prepare("SELECT d.*, c.name as category_name FROM destinations d LEFT JOIN categories c ON d.category_id = c.id WHERE d.is_featured = 1 ORDER BY d.id DESC LIMIT ?");
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll() ?: [];
}

function getFeaturedFoods(int $limit = 6): array
{
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM foods WHERE is_featured = 1 AND status = 'published' ORDER BY id DESC LIMIT ?");
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll() ?: [];
}

function getFeaturedAccommodations(int $limit = 6): array
{
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM accommodations WHERE is_featured = 1 AND status = 'published' ORDER BY id DESC LIMIT ?");
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll() ?: [];
}


