<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

/**
 * Mã lỗi MySQL/MariaDB coi là "đối tượng đã tồn tại" — vô hại khi áp lại một
 * statement DDL đơn-clause đã áp trước đó (vd. `ADD COLUMN` không kèm
 * `IF NOT EXISTS`). Không nuốt các lỗi khác (cột sai kiểu, cú pháp sai, mất
 * kết nối...). Bản thân mã lỗi không đủ để quyết định nuốt hay không — còn
 * phải kết hợp với statementHasSingleDdlClause() bên dưới.
 */
function migrationErrorIsBenign(PDOException $e): bool
{
    $code = (int)($e->errorInfo[1] ?? 0);
    // 1050 bảng đã tồn tại, 1060 cột trùng tên, 1061 tên khoá trùng,
    // 1068 nhiều khoá chính, 1091 đối tượng không tồn tại khi DROP.
    return in_array($code, [1050, 1060, 1061, 1068, 1091], true);
}

/**
 * Một `ALTER TABLE` nhiều clause (vd. `ADD COLUMN a, ADD COLUMN b, ADD KEY
 * c`) là MỘT thao tác nguyên tử trong MariaDB — nếu bất kỳ clause nào lỗi,
 * TOÀN BỘ statement bị huỷ, không có clause nào được áp dụng một phần. Vì
 * vậy không thể coi lỗi "đã tồn tại" ở một clause là vô hại rồi coi cả
 * statement là xong: các clause khác đứng trước/sau clause lỗi trên thực tế
 * KHÔNG được áp dụng, dù ta chỉ thấy lỗi ở một clause.
 *
 * Ca cụ thể từng xảy ra: `20260806_upgrade.sql` có
 * `ALTER TABLE itinerary_items ADD COLUMN ... (9 cột), ADD KEY ..., ADD KEY
 * ...` — hai clause `ADD KEY` cuối không có `IF NOT EXISTS` (nay đã vá
 * thành `ADD KEY IF NOT EXISTS`). Statement chỉ có một clause DDL thì không
 * có rủi ro "áp dụng một phần" này, nên vẫn được coi là an toàn để nuốt khi
 * lỗi thuộc nhóm "đã tồn tại".
 */
function statementHasSingleDdlClause(string $statement): bool
{
    if (!preg_match('/^\s*ALTER\s+TABLE\b/i', $statement)) {
        return true;
    }

    $depth = 0;
    $clauseCount = 1;
    $length = strlen($statement);
    for ($i = 0; $i < $length; $i++) {
        $char = $statement[$i];
        if ($char === '(') {
            $depth++;
        } elseif ($char === ')') {
            $depth--;
        } elseif ($char === ',' && $depth === 0) {
            $clauseCount++;
        }
    }

    return $clauseCount === 1;
}

/** Tạo bảng theo dõi migration nếu chưa có. Idempotent, gọi bao nhiêu lần cũng an toàn. */
function ensureSchemaMigrationsTable(PDO $db): void
{
    $db->exec('CREATE TABLE IF NOT EXISTS schema_migrations (
        version varchar(120) NOT NULL PRIMARY KEY,
        applied_at timestamp NOT NULL DEFAULT current_timestamp()
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
}

/** true nếu $version CHƯA có trong schema_migrations (còn đang chờ áp dụng). */
function migrationIsPending(PDO $db, string $version): bool
{
    ensureSchemaMigrationsTable($db);
    $seen = $db->prepare('SELECT COUNT(*) FROM schema_migrations WHERE version = ?');
    $seen->execute([$version]);
    return (int)$seen->fetchColumn() === 0;
}

function runMigrationFile(PDO $db, string $path, string $version): bool
{
    if (!migrationIsPending($db, $version)) {
        return false;
    }

    $sql = file_get_contents($path);
    if ($sql === false) {
        throw new RuntimeException("Không đọc được migration: {$path}");
    }

    // Bỏ các dòng `-- comment` TRƯỚC KHI tách theo dấu `;`. Một vài migration
    // cũ (vd. 20260806_upgrade.sql, 20260807_region_split.sql) có câu văn kiểu
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
            // Chỉ nuốt lỗi "đã tồn tại" khi statement chỉ có một clause DDL —
            // xem giải thích đầy đủ ở statementHasSingleDdlClause(). Statement
            // nhiều clause lỗi thì ném ra ngoài, không đánh dấu migration này
            // là xong, vì ta không thể biết phần nào đã thật sự áp dụng.
            if (!migrationErrorIsBenign($e) || !statementHasSingleDdlClause($statement)) {
                throw $e;
            }
            fwrite(STDERR, "CẢNH BÁO [{$version}]: bỏ qua lỗi 'đã tồn tại' — " . $e->getMessage() . "\n");
        }
    }

    $mark = $db->prepare('INSERT INTO schema_migrations (version) VALUES (?)');
    $mark->execute([$version]);
    return true;
}

/**
 * Đọc `--dry-run` và `--limit=N` từ argv CLI. Không đụng tới các phần tử
 * khác của argv (vd. đường dẫn script ở argv[0]).
 *
 * @param string[] $argv
 * @return array{dryRun: bool, limit: int|null}
 */
function parseMigrateCliOptions(array $argv): array
{
    $dryRun = false;
    $limit = null;
    foreach (array_slice($argv, 1) as $arg) {
        if ($arg === '--dry-run') {
            $dryRun = true;
            continue;
        }
        if (preg_match('/^--limit=(\d+)$/', $arg, $m) === 1) {
            $limit = (int)$m[1];
        }
    }
    return ['dryRun' => $dryRun, 'limit' => $limit];
}

/**
 * Áp (hoặc xem trước) các migration đang chờ, theo thứ tự $files truyền vào.
 * Trả về mảng dòng log dạng chuỗi để tầng gọi tự in ra — hàm này không echo
 * trực tiếp để test có thể gọi mà không cần bắt output buffer.
 *
 * - `$dryRun = true`: không exec statement nào, không ghi schema_migrations,
 *   chỉ báo migration nào SẼ được áp nếu chạy thật.
 * - `$limit`: chỉ áp (hoặc xem trước) tối đa $limit migration ĐANG CHỜ trong
 *   lần gọi này; các migration đã áp từ trước vẫn được liệt kê "BỎ QUA" bất
 *   kể limit (không tính vào hạn mức). Khi chạm limit, dừng xử lý toàn bộ —
 *   các file còn lại (dù đang chờ hay không) không xuất hiện trong log của
 *   lần gọi này, để lần chạy sau xử lý tiếp.
 *
 * @param string[] $files
 * @return string[]
 */
function runPendingMigrations(PDO $db, array $files, bool $dryRun = false, ?int $limit = null): array
{
    $log = [];
    $pendingHandled = 0;

    foreach ($files as $file) {
        $version = basename($file, '.sql');

        if (!migrationIsPending($db, $version)) {
            $log[] = 'BỎ QUA      ' . $version;
            continue;
        }

        if ($limit !== null && $pendingHandled >= $limit) {
            break;
        }

        if ($dryRun) {
            $log[] = 'SẼ ÁP DỤNG  ' . $version;
        } else {
            runMigrationFile($db, $file, $version);
            $log[] = 'ĐÃ ÁP DỤNG  ' . $version;
        }
        $pendingHandled++;
    }

    return $log;
}

if (PHP_SAPI === 'cli' && realpath($argv[0] ?? '') === realpath(__FILE__)) {
    $db = getDB();
    $options = parseMigrateCliOptions($argv);
    // Quét cả thư mục theo thứ tự tên file — không hardcode danh sách, để
    // migration mới thêm sau này tự được nhận.
    $files = glob(__DIR__ . '/../database/migrations/*.sql') ?: [];
    foreach (runPendingMigrations($db, $files, $options['dryRun'], $options['limit']) as $line) {
        echo $line . PHP_EOL;
    }
}
