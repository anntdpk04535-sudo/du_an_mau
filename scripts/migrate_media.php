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
 * Duyệt một chuỗi SQL đúng một lượt, gán cho MỖI ký tự một trong ba trạng
 * thái: 'code' (SQL thật, đáng để phân tích cấu trúc — dấu `;`, `,`, `(`,
 * `)`...), 'string' (bên trong chuỗi literal `'...'`, escape `''` được xử
 * lý đúng), 'comment' (bên trong `-- ...`, `# ...` hoặc `/* ... * /`, kể cả
 * khối nhiều dòng — bỏ khoảng trắng giữa `*` và `/` khi đọc, viết liền ở
 * đây sẽ tự đóng luôn docblock này).
 *
 * Đây là bộ quét DUY NHẤT, dùng chung cho cả splitSqlStatements() (tách
 * statement) lẫn statementHasSingleDdlClause() (đếm clause) — trước đây hai
 * nơi này quét SQL độc lập bằng cách "mù" (không phân biệt code/string/
 * comment), nên cùng một gốc bệnh gây ra 2 lỗi khác nhau: bộ tách cắt sai
 * tại `;` nằm trong literal/comment khối, còn bộ đếm đếm sai `(`/`)`/`,`
 * nằm trong literal. Sửa một chỗ, dùng chung, để hai bên không thể lệch
 * pha với nhau nữa.
 *
 * @return array<int, 'code'|'string'|'comment'>
 */
function sqlCharStates(string $sql): array
{
    $length = strlen($sql);
    $states = [];
    $i = 0;

    while ($i < $length) {
        $char = $sql[$i];
        $next = $i + 1 < $length ? $sql[$i + 1] : '';

        if ($char === "'") {
            // Chuỗi literal '...'; '' bên trong chuỗi là escape của chính
            // dấu nháy đơn (chuẩn SQL), không kết thúc chuỗi.
            $states[$i] = 'string';
            $i++;
            while ($i < $length) {
                if ($sql[$i] === "'" && ($i + 1 < $length) && $sql[$i + 1] === "'") {
                    $states[$i] = 'string';
                    $states[$i + 1] = 'string';
                    $i += 2;
                    continue;
                }
                $states[$i] = 'string';
                $i++;
                if ($sql[$i - 1] === "'") {
                    break;
                }
            }
            continue;
        }

        if ($char === '-' && $next === '-') {
            // Comment dòng đơn `-- ...`, kết thúc ở cuối dòng.
            while ($i < $length && $sql[$i] !== "\n") {
                $states[$i] = 'comment';
                $i++;
            }
            continue;
        }

        if ($char === '#') {
            // Comment dòng đơn kiểu MySQL `# ...`, kết thúc ở cuối dòng.
            while ($i < $length && $sql[$i] !== "\n") {
                $states[$i] = 'comment';
                $i++;
            }
            continue;
        }

        if ($char === '/' && $next === '*') {
            // Comment khối `/* ... */`, có thể trải nhiều dòng.
            $states[$i] = 'comment';
            $states[$i + 1] = 'comment';
            $i += 2;
            while ($i < $length) {
                if ($sql[$i] === '*' && ($i + 1 < $length) && $sql[$i + 1] === '/') {
                    $states[$i] = 'comment';
                    $states[$i + 1] = 'comment';
                    $i += 2;
                    break;
                }
                $states[$i] = 'comment';
                $i++;
            }
            continue;
        }

        $states[$i] = 'code';
        $i++;
    }

    return $states;
}

/**
 * Tách một chuỗi SQL nhiều statement thành mảng các statement riêng lẻ, chỉ
 * cắt tại dấu `;` khi nó thật sự ở trạng thái 'code' (ngoài chuỗi literal
 * và ngoài mọi loại comment) theo sqlCharStates().
 *
 * Thay thế cách cũ (lọc bỏ từng DÒNG có nội dung bắt đầu bằng `--` rồi
 * explode(';') thô trên phần còn lại): cách cũ vẫn cắt sai khi dấu `;` nằm
 * trong comment khối `/* ... * /` nhiều dòng (không bắt đầu dòng bằng `--`
 * nên không bị lọc, nhưng dấu `;` bên trong vẫn bị explode cắt), hoặc khi
 * `;` nằm trong một chuỗi literal.
 *
 * Một "statement" mà sau khi loại phần comment không còn ký tự SQL/chuỗi
 * nào (toàn comment + khoảng trắng — vd. một block comment độc lập đứng
 * giữa hai statement thật) bị loại bỏ, không đưa vào kết quả: gửi một
 * statement toàn comment cho `PDO::exec()` sẽ bị MariaDB báo lỗi 1065
 * "Query was empty".
 *
 * @return string[]
 */
function splitSqlStatements(string $sql): array
{
    $states = sqlCharStates($sql);
    $length = strlen($sql);
    $statements = [];
    $start = 0;

    for ($i = 0; $i <= $length; $i++) {
        $atEnd = $i === $length;
        $isTopLevelSemicolon = !$atEnd && $sql[$i] === ';' && ($states[$i] ?? 'code') === 'code';

        if (!$isTopLevelSemicolon && !$atEnd) {
            continue;
        }

        $chunk = substr($sql, $start, $i - $start);
        $hasRealCode = false;
        for ($offset = $start; $offset < $i; $offset++) {
            $state = $states[$offset] ?? 'code';
            if ($state === 'comment') {
                continue;
            }
            if (trim($sql[$offset]) !== '') {
                $hasRealCode = true;
                break;
            }
        }

        if ($hasRealCode) {
            $statements[] = trim($chunk);
        }
        $start = $i + 1;
    }

    return $statements;
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
 *
 * Đếm clause chỉ dựa trên ký tự ở trạng thái 'code' theo sqlCharStates() —
 * dấu `(`/`)`/`,` nằm trong một chuỗi literal (vd.
 * `DEFAULT 'closed)temporarily'` hay `DEFAULT 'ok,pending'`) không được
 * tính vào độ sâu ngoặc hay số clause. Bản trước duyệt ký tự thô, không
 * phân biệt literal, nên bị sai cả hai chiều: literal chứa `)` làm âm độ
 * sâu ngoặc khiến dấu phẩy ranh giới clause thật bị bỏ sót (nhiều clause
 * tưởng một), literal chứa `,` bị đếm nhầm thành ranh giới clause (một
 * clause tưởng nhiều, phá tính idempotent).
 */
/**
 * Dựng bản sao "chỉ-chứa-code" của một chuỗi SQL: bỏ hẳn mọi ký tự ở trạng
 * thái 'comment' (theo sqlCharStates()), GIỮ NGUYÊN ký tự ở trạng thái
 * 'code' và 'string'. Trả về cặp [chuỗi đã bỏ comment, mảng trạng thái ứng
 * với từng ký tự của chuỗi đó].
 *
 * Dùng riêng cho việc PHÂN LOẠI statement (nhận diện `ALTER TABLE`, đếm
 * clause) — KHÔNG dùng cho chuỗi thật gửi tới $db->exec(). Một base dump
 * có thể chứa comment điều kiện kiểu MySQL `/*!40101 ... * /` là cú pháp
 * thật cần thực thi, không phải comment thuần túy, nên tuyệt đối không
 * được đụng vào chuỗi thực thi.
 *
 * @return array{0: string, 1: array<int, 'code'|'string'>}
 */
function sqlCodeOnlyView(string $sql): array
{
    $states = sqlCharStates($sql);
    $length = strlen($sql);
    $codeOnly = '';
    $codeOnlyStates = [];

    for ($i = 0; $i < $length; $i++) {
        $state = $states[$i] ?? 'code';
        if ($state === 'comment') {
            continue;
        }
        $codeOnly .= $sql[$i];
        $codeOnlyStates[] = $state;
    }

    return [$codeOnly, $codeOnlyStates];
}

/**
 * Statement nhiều clause (vd. ALTER TABLE ... ADD COLUMN a, ADD COLUMN b)
 * không được coi là an toàn để bỏ qua lỗi "already exists" — vì MariaDB
 * chạy nguyên statement như MỘT thao tác atomic, lỗi ở một clause sẽ
 * rollback toàn bộ statement.
 *
 * QUAN TRỌNG: cả việc nhận diện `ALTER TABLE` lẫn việc đếm clause đều phải
 * chạy trên bản sao "chỉ-chứa-code" (comment đã bị lược bỏ, string literal
 * giữ nguyên) do sqlCodeOnlyView() dựng ra — KHÔNG chạy trực tiếp trên
 * $statement gốc. Lý do: statement thật lấy từ splitSqlStatements() có thể
 * bắt đầu bằng một comment `--`/`/ * * /` đứng ngay trước, không có `;` xen
 * giữa (vd. dòng đầu của database/migrations/20260807_place_facts.sql).
 * Nếu regex chạy trên chuỗi gốc, comment đứng chắn phía trước sẽ làm
 * `preg_match('/^\s*ALTER\s+TABLE\b/i', ...)` không khớp, rơi vào nhánh
 * `return true` mặc định — bỏ qua đếm clause hoàn toàn, coi statement
 * nhiều clause là an toàn một cách sai lệch.
 */
function statementHasSingleDdlClause(string $statement): bool
{
    [$codeOnly, $codeOnlyStates] = sqlCodeOnlyView($statement);

    if (!preg_match('/^\s*ALTER\s+TABLE\b/i', $codeOnly)) {
        return true;
    }

    $depth = 0;
    $clauseCount = 1;
    $length = strlen($codeOnly);

    for ($i = 0; $i < $length; $i++) {
        if (($codeOnlyStates[$i] ?? 'code') !== 'code') {
            continue;
        }
        $char = $codeOnly[$i];
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

/** true nếu bảng schema_migrations đã tồn tại (không tạo mới nếu chưa có). */
function schemaMigrationsTableExists(PDO $db): bool
{
    $stmt = $db->query(
        "SELECT COUNT(*) FROM information_schema.tables
         WHERE table_schema = DATABASE() AND table_name = 'schema_migrations'"
    );
    return $stmt !== false && (int)$stmt->fetchColumn() > 0;
}

/**
 * true nếu $version CHƯA có trong schema_migrations (còn đang chờ áp dụng).
 *
 * $createTableIfMissing mặc định true (hành vi gốc, dùng cho áp dụng thật —
 * bảng phải tồn tại để sau đó ghi INSERT). Truyền false cho chế độ xem
 * trước (`--dry-run`): KHÔNG được tạo bảng chỉ vì đang kiểm tra trạng thái;
 * nếu bảng chưa tồn tại thì coi mọi migration là đang chờ (đúng thực tế —
 * chưa có bảng nghĩa là chưa migration nào từng được ghi nhận).
 */
function migrationIsPending(PDO $db, string $version, bool $createTableIfMissing = true): bool
{
    if ($createTableIfMissing) {
        ensureSchemaMigrationsTable($db);
    } elseif (!schemaMigrationsTableExists($db)) {
        return true;
    }

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

    foreach (splitSqlStatements($sql) as $statement) {
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
 * `--limit=` phải là số nguyên không âm hợp lệ (`\d+`). Một giá trị không
 * khớp (vd. `--limit=abc`, `--limit=`, `--limit=-1`) KHÔNG được lờ đi im
 * lặng — im lặng nghĩa là $limit giữ null và migration chạy KHÔNG giới hạn,
 * tức người gõ sai cú pháp tưởng mình đang an toàn nhưng thực ra áp toàn
 * bộ. Ném lỗi ngay để tầng gọi (khối CLI cuối file) dừng lại và báo rõ
 * thay vì âm thầm chạy sai ý định.
 *
 * @param string[] $argv
 * @return array{dryRun: bool, limit: int|null}
 * @throws InvalidArgumentException nếu giá trị --limit không phải số nguyên không âm.
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
        if (str_starts_with($arg, '--limit=')) {
            $value = substr($arg, strlen('--limit='));
            if (preg_match('/^\d+$/', $value) !== 1) {
                throw new InvalidArgumentException(
                    "Giá trị --limit không hợp lệ: '{$value}'. Phải là số nguyên không âm, vd. --limit=3."
                );
            }
            $limit = (int)$value;
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
 *   KHÔNG tạo bảng schema_migrations nếu nó chưa tồn tại (xem
 *   migrationIsPending()) — chỉ báo migration nào SẼ được áp nếu chạy thật.
 *   Đây phải là thao tác "chỉ đọc" tuyệt đối, đúng đúng vai trò công cụ xem
 *   trước an toàn trước khi động vào DB thật.
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

        if (!migrationIsPending($db, $version, !$dryRun)) {
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
    try {
        $options = parseMigrateCliOptions($argv);
    } catch (InvalidArgumentException $e) {
        fwrite(STDERR, 'LỖI: ' . $e->getMessage() . PHP_EOL);
        exit(1);
    }

    $db = getDB();
    // Quét cả thư mục theo thứ tự tên file — không hardcode danh sách, để
    // migration mới thêm sau này tự được nhận.
    $files = glob(__DIR__ . '/../database/migrations/*.sql') ?: [];
    foreach (runPendingMigrations($db, $files, $options['dryRun'], $options['limit']) as $line) {
        echo $line . PHP_EOL;
    }
}
