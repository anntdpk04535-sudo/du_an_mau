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
 * `)`...), 'string' (bên trong một vùng có trích dẫn — xem bên dưới),
 * 'comment' (bên trong `-- ...`, `# ...` hoặc `/* ... * /` thuần tuý, kể cả
 * khối nhiều dòng — bỏ khoảng trắng giữa `*` và `/` khi đọc, viết liền ở
 * đây sẽ tự đóng luôn docblock này).
 *
 * Đây là bộ quét DUY NHẤT, dùng chung cho cả splitSqlStatements() (tách
 * statement) lẫn statementHasSingleDdlClause() (đếm clause), nên hai bên
 * không thể lệch pha với nhau.
 *
 * MÔ HÌNH TỪ VỰNG (theo đúng MariaDB, đã xác minh trên 10.4.28 của dự án):
 *
 * - Ba loại trích dẫn `'...'`, `"..."`, `` `...` `` đi chung MỘT nhánh, chỉ
 *   khác ký tự đóng. Gán tất cả vào state 'string' vì với bộ quét thì vai
 *   trò của chúng giống hệt nhau: nội dung bên trong PHẢI được nhảy qua, bất
 *   kể nó là dữ liệu hay định danh. (`"..."` mặc định là string literal;
 *   bật `ANSI_QUOTES` thì thành định danh — nhưng dù nghĩa nào, một dấu nháy
 *   đơn lạc bên trong cũng không được phép mở state 'string' và đảo pha toàn
 *   bộ phần SQL đứng sau.)
 * - Backslash escape: MariaDB mặc định BẬT (`sql_mode` không chứa
 *   `NO_BACKSLASH_ESCAPES`), nên `'M\'gar'` là MỘT chuỗi — bỏ qua 2 ký tự.
 *   NGOẠI LỆ: bên trong backtick, `\` là ký tự thường, không escape.
 * - Escape kiểu nhân đôi ký tự đóng (`''`, `""`, ` `` `) vẫn hoạt động cho
 *   cả ba loại.
 * - `--` CHỈ mở comment khi theo sau là khoảng trắng, tab hoặc xuống dòng.
 *   `DEFAULT 1--1` là `1 - (-1)`, không phải comment.
 * - `/*!...* /` và `/*M!...* /` là MÃ THI HÀNH THẬT với MariaDB, không phải
 *   comment: mở ra state 'code' để nội dung bên trong được quét như SQL bình
 *   thường (kể cả chuỗi lồng bên trong), và `*` `/` đóng khối tự rơi vào
 *   nhánh 'code' mặc định.
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

        if ($char === "'" || $char === '"' || $char === '`') {
            // Một nhánh chung cho cả ba loại trích dẫn, tham số hoá bằng ký
            // tự đóng. Backslash chỉ là ký tự escape ở `'...'` và `"..."`.
            $i = sqlScanQuoted($sql, $i, $char, $states);
            continue;
        }

        if ($char === '-' && $next === '-') {
            // `--` chỉ mở comment khi theo sau là khoảng trắng/tab/xuống
            // dòng (hoặc hết chuỗi). Ngược lại nó là toán tử trừ, rơi xuống
            // nhánh 'code' mặc định ở cuối vòng lặp.
            $after = $i + 2 < $length ? $sql[$i + 2] : "\n";
            if ($after === ' ' || $after === "\t" || $after === "\n" || $after === "\r") {
                while ($i < $length && $sql[$i] !== "\n") {
                    $states[$i] = 'comment';
                    $i++;
                }
                continue;
            }
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
            $third = $i + 2 < $length ? $sql[$i + 2] : '';
            $fourth = $i + 3 < $length ? $sql[$i + 3] : '';
            $isExecutable = $third === '!' || ($third === 'M' && $fourth === '!');

            if ($isExecutable) {
                // `/*!` (hoặc `/*M!`) — mã thi hành thật. Đánh dấu ký tự mở
                // là 'code' rồi TRẢ VỀ vòng lặp chính để phần thân được quét
                // như SQL bình thường; `*` `/` đóng khối cũng thành 'code'.
                $markerLength = $third === '!' ? 3 : 4;
                for ($offset = 0; $offset < $markerLength && $i + $offset < $length; $offset++) {
                    $states[$i + $offset] = 'code';
                }
                $i += $markerLength;
                continue;
            }

            // Comment khối thuần tuý `/* ... */`, có thể trải nhiều dòng.
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
 * Quét một vùng có trích dẫn bắt đầu tại $start (ký tự mở nằm ở $start), gán
 * state 'string' cho mọi ký tự thuộc vùng đó vào $states (tham chiếu), và trả
 * về chỉ số ngay SAU ký tự đóng.
 *
 * $closer là ký tự đóng, đồng thời là ký tự mở — `'`, `"` hoặc `` ` ``.
 * Backslash chỉ được coi là ký tự escape khi $closer KHÁC backtick: bên trong
 * `` `...` `` MariaDB không xử lý `\` như escape (đã xác minh: `` SELECT `a\` ``
 * là định danh hợp lệ). Escape kiểu nhân đôi ký tự đóng luôn được hỗ trợ.
 *
 * Vùng không được đóng cho tới hết chuỗi (SQL cụt) được coi là kéo dài tới
 * hết — an toàn hơn là đoán nó kết thúc sớm.
 *
 * @param array<int, 'code'|'string'|'comment'> $states
 */
function sqlScanQuoted(string $sql, int $start, string $closer, array &$states): int
{
    $length = strlen($sql);
    $backslashEscapes = $closer !== '`';

    $states[$start] = 'string';
    $i = $start + 1;

    while ($i < $length) {
        $char = $sql[$i];

        if ($backslashEscapes && $char === '\\' && $i + 1 < $length) {
            $states[$i] = 'string';
            $states[$i + 1] = 'string';
            $i += 2;
            continue;
        }

        if ($char === $closer) {
            if ($i + 1 < $length && $sql[$i + 1] === $closer) {
                // Nhân đôi ký tự đóng = escape của chính nó, chưa kết thúc.
                $states[$i] = 'string';
                $states[$i + 1] = 'string';
                $i += 2;
                continue;
            }
            $states[$i] = 'string';
            return $i + 1;
        }

        $states[$i] = 'string';
        $i++;
    }

    return $i;
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
 * Dựng bản sao "chỉ-chứa-code" của một chuỗi SQL: mọi ký tự ở trạng thái
 * 'comment' (theo sqlCharStates()) được THAY BẰNG MỘT KHOẢNG TRẮNG, ký tự ở
 * trạng thái 'code' và 'string' GIỮ NGUYÊN. Trả về cặp [chuỗi kết quả, mảng
 * trạng thái ứng với từng ký tự của chuỗi đó].
 *
 * Thay-bằng-khoảng-trắng chứ KHÔNG xoá hẳn: comment là RANH GIỚI TỪ VỰNG
 * trong SQL, đúng như trình phân tích của MariaDB coi nó. Xoá hẳn làm hai
 * token hai bên dính vào nhau — `ALTER/x/TABLE t ADD a, ADD b` (với `/x/` là
 * một comment khối) cho ra `ALTERTABLE t ...`, và mọi regex nhận diện động
 * từ đứng đầu đều trượt. Giữ độ dài chuỗi không đổi cũng khiến chỉ số ký tự
 * của bản sao trùng khít với bản gốc, tiện cho các bước xử lý sau.
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
            $codeOnly .= ' ';
            $codeOnlyStates[] = 'code';
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

    // Statement nguyên tử KHÔNG phải ALTER TABLE, nhưng vẫn tạo/đổi nhiều đối
    // tượng trong một thao tác — lỗi "đã tồn tại" ở đây cũng huỷ toàn bộ
    // statement, nên tuyệt đối không được nuốt.
    //
    // - `RENAME TABLE a TO b, c TO d`: nhiều cặp trong một thao tác nguyên tử.
    // - `CREATE TABLE` KHÔNG có `IF NOT EXISTS`: lỗi 1050 nghĩa là bảng cũ vẫn
    //   nguyên trạng, có thể khác hẳn định nghĩa trong migration — nuốt lỗi
    //   này là đánh dấu "xong" trên một lược đồ sai.
    //
    // `CREATE INDEX`/`DROP INDEX` chỉ đụng MỘT đối tượng nên nhánh mặc định
    // `return true` bên dưới vẫn đúng với chúng.
    if (preg_match('/^\s*RENAME\s+TABLE\b/i', $codeOnly)) {
        return false;
    }
    if (preg_match('/^\s*CREATE\s+(?:OR\s+REPLACE\s+)?(?:TEMPORARY\s+)?TABLE\s+(?!IF\s+NOT\s+EXISTS\b)/i', $codeOnly)) {
        return false;
    }

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
            // `ALGORITHM=`, `LOCK=`, `WAIT n`, `NOWAIT` là TUỲ CHỌN của ALTER
            // TABLE, không phải clause DDL — nhưng vẫn ngăn cách bằng dấu
            // phẩy ở độ sâu 0. Đếm chúng như clause làm một statement 1-clause
            // thật bị coi là nhiều clause, phá tính idempotent (lần chạy thứ
            // hai ném lỗi thay vì được nuốt như thiết kế).
            if (preg_match('/^\s*(?:ALGORITHM\s*=|LOCK\s*=|WAIT\b|NOWAIT\b)/i', substr($codeOnly, $i + 1))) {
                continue;
            }
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

    $statements = splitSqlStatements($sql);
    if ($statements === []) {
        // Một file tách ra 0 statement thi hành được là LỖI, không phải thành
        // công: trước đây `foreach` chạy 0 vòng rồi vẫn ghi schema_migrations,
        // nên một migration mà mọi DDL đều bị bộ quét nuốt (vd. gate toàn bộ
        // bằng `/*!100200 ... */` — kỹ thuật MariaDB phổ thông) được đánh dấu
        // "đã áp dụng" trong khi không câu lệnh nào từng chạy.
        throw new RuntimeException(
            "Migration không có statement thi hành được: {$path}. "
            . "Không đánh dấu là đã áp dụng — kiểm tra lại nội dung file."
        );
    }

    foreach ($statements as $statement) {
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
