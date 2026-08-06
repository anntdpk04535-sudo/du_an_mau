<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

/**
 * Mã lỗi MySQL/MariaDB coi là "đối tượng đã tồn tại" — vô hại khi áp lại một
 * migration cũ (vd. `20260806_upgrade.sql`) có clause `ADD KEY` không kèm
 * `IF NOT EXISTS` bên trong một ALTER TABLE nhiều clause. Không nuốt các lỗi
 * khác (cột sai kiểu, cú pháp sai, mất kết nối...).
 */
function migrationErrorIsBenign(PDOException $e): bool
{
    $code = (int)($e->errorInfo[1] ?? 0);
    // 1050 bảng đã tồn tại, 1060 cột trùng tên, 1061 tên khoá trùng,
    // 1068 nhiều khoá chính, 1091 đối tượng không tồn tại khi DROP.
    return in_array($code, [1050, 1060, 1061, 1068, 1091], true);
}

function runMigrationFile(PDO $db, string $path, string $version): bool
{
    $db->exec('CREATE TABLE IF NOT EXISTS schema_migrations (
        version varchar(120) NOT NULL PRIMARY KEY,
        applied_at timestamp NOT NULL DEFAULT current_timestamp()
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

    $seen = $db->prepare('SELECT COUNT(*) FROM schema_migrations WHERE version = ?');
    $seen->execute([$version]);
    if ((int)$seen->fetchColumn() > 0) {
        return false;
    }

    $sql = file_get_contents($path);
    if ($sql === false) {
        throw new RuntimeException("Không đọc được migration: {$path}");
    }

    // Bỏ các dòng `-- comment` TRƯỚC KHI tách theo dấu `;`. Một vài migration
    // cũ (vd. 20260806_upgrade.sql, 20260806_region_split.sql) có câu văn kiểu
    // "Safe to run; all statements are idempotent." trong phần chú thích đầu
    // file — nếu tách bằng explode(';') trên toàn bộ nội dung thô, dấu `;`
    // nằm giữa câu chú thích đó sẽ cắt chú thích làm đôi và phần đuôi (không
    // còn bắt đầu bằng `--`) sẽ bị đem đi exec() như SQL thật, gây lỗi cú
    // pháp. Lọc theo từng dòng trước rồi mới ghép lại và tách theo `;` để
    // tránh lệ thuộc vào việc `;` có xuất hiện trong chú thích hay không.
    $codeLines = array_filter(
        preg_split('/\R/', $sql) ?: [],
        static fn (string $line): bool => !str_starts_with(trim($line), '--')
    );
    $sqlWithoutComments = implode("\n", $codeLines);

    foreach (array_filter(array_map('trim', explode(';', $sqlWithoutComments))) as $statement) {
        if ($statement === '') {
            continue;
        }
        try {
            $db->exec($statement);
        } catch (PDOException $e) {
            // Migration cũ không phải lúc nào cũng có IF NOT EXISTS trên mọi
            // clause (vd. ADD KEY trong 20260806_upgrade.sql). Áp lại một
            // migration như vậy không được làm sập cả runner — nhưng cũng
            // không được nuốt im lặng, nên báo ra STDERR rồi đi tiếp sang
            // statement kế tiếp.
            if (!migrationErrorIsBenign($e)) {
                throw $e;
            }
            fwrite(STDERR, "CẢNH BÁO [{$version}]: bỏ qua lỗi 'đã tồn tại' — " . $e->getMessage() . "\n");
        }
    }

    $mark = $db->prepare('INSERT INTO schema_migrations (version) VALUES (?)');
    $mark->execute([$version]);
    return true;
}

if (PHP_SAPI === 'cli' && realpath($argv[0] ?? '') === realpath(__FILE__)) {
    $db = getDB();
    // Quét cả thư mục theo thứ tự tên file — không hardcode danh sách, để
    // migration mới thêm sau này tự được nhận.
    foreach (glob(__DIR__ . '/../database/migrations/*.sql') ?: [] as $file) {
        $version = basename($file, '.sql');
        $applied = runMigrationFile($db, $file, $version);
        echo ($applied ? 'ĐÃ ÁP DỤNG  ' : 'BỎ QUA      ') . $version . PHP_EOL;
    }
}
