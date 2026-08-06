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
 * Bóc các lớp "gate phiên bản" `/*!12345 ... * /` và `/*M!12345 ... * /` khỏi
 * bản sao chỉ-chứa-code, thay mỗi ký tự bị bóc bằng một khoảng trắng (giữ
 * nguyên chỉ số ký tự và mảng trạng thái đi kèm).
 *
 * Vì sao cần bước này: sqlCharStates() đánh `/*!` và `/*M!` là state 'code'
 * — ĐÚNG, vì với MariaDB đó là mã thi hành thật, và chuỗi gửi $db->exec()
 * bắt buộc phải giữ nguyên chúng. Nhưng hệ quả là bản sao chỉ-chứa-code bắt
 * đầu bằng `/*!100200 ` chứ không bằng động từ SQL, nên MỌI regex neo đầu
 * chuỗi ở tầng phân loại đều trượt. Lớp bóc gate này khôi phục lại điều mà
 * tầng phân loại luôn giả định: chuỗi nó nhận mở đầu bằng động từ SQL.
 *
 * Đếm ĐỘ SÂU thay vì cắt cặp mở/đóng nên gate lồng gate
 * (`/*!100200 /*M!100300 ... * / * /`) được bóc hết, và gate KHÔNG đóng
 * (mysqldump hay cắt ngang) vẫn được bóc phần mở.
 *
 * @param array<int, 'code'|'string'> $states
 * @return array{0: string, 1: array<int, 'code'|'string'>}
 */
function sqlStripVersionGates(string $view, array $states): array
{
    $length = strlen($view);
    $stripped = '';
    $strippedStates = [];
    $depth = 0;
    $i = 0;

    while ($i < $length) {
        $state = $states[$i] ?? 'code';

        if ($state === 'code') {
            $markerLength = 0;
            if (substr($view, $i, 3) === '/*!') {
                $markerLength = 3;
            } elseif (substr($view, $i, 4) === '/*M!') {
                $markerLength = 4;
            }

            if ($markerLength > 0) {
                // Nuốt luôn số phiên bản dính ngay sau marker (`/*!100200`).
                // Gate không có số (`/*! ... * /`) cũng hợp lệ — khi đó vòng
                // lặp dưới không chạy lần nào.
                $end = $i + $markerLength;
                while ($end < $length && $view[$end] >= '0' && $view[$end] <= '9') {
                    $end++;
                }
                for (; $i < $end; $i++) {
                    $stripped .= ' ';
                    $strippedStates[] = 'code';
                }
                $depth++;
                continue;
            }

            if ($depth > 0 && substr($view, $i, 2) === '*/') {
                $stripped .= '  ';
                $strippedStates[] = 'code';
                $strippedStates[] = 'code';
                $depth--;
                $i += 2;
                continue;
            }
        }

        $stripped .= $view[$i];
        $strippedStates[] = $state;
        $i++;
    }

    return [$stripped, $strippedStates];
}

/**
 * Gộp mọi dãy khoảng trắng ở state 'code' thành đúng MỘT dấu cách và cắt
 * khoảng trắng hai đầu. Khoảng trắng bên trong vùng 'string' được giữ
 * NGUYÊN VẸN — đó là dữ liệu, không phải định dạng.
 *
 * Bước này khoá lại một họ lỗi regex thay vì từng ca lẻ: khi chuỗi đầu vào
 * đã chính tắc, `\s+` không còn cơ hội backtrack nhả lại một ký tự trắng để
 * lookahead đứng sai chỗ (`CREATE TABLE  IF NOT EXISTS` với hai dấu cách
 * từng bị nhận nhầm là không có `IF NOT EXISTS`), và mọi biến thể
 * tab/xuống dòng/nhiều dòng đều quy về cùng một hình dạng.
 *
 * @param array<int, 'code'|'string'> $states
 * @return array{0: string, 1: array<int, 'code'|'string'>}
 */
function sqlCollapseCodeWhitespace(string $view, array $states): array
{
    $length = strlen($view);
    $collapsed = '';
    $collapsedStates = [];
    $pendingSpace = false;

    for ($i = 0; $i < $length; $i++) {
        $state = $states[$i] ?? 'code';
        $char = $view[$i];

        if ($state === 'code' && strpos(" \t\n\r\v\f", $char) !== false) {
            // Chưa phát ra gì => đang ở đầu chuỗi => cắt luôn. Khoảng trắng
            // đuôi cũng tự biến mất vì $pendingSpace không bao giờ được xả.
            $pendingSpace = $collapsed !== '';
            continue;
        }

        if ($pendingSpace) {
            $collapsed .= ' ';
            $collapsedStates[] = 'code';
            $pendingSpace = false;
        }

        $collapsed .= $char;
        $collapsedStates[] = $state;
    }

    return [$collapsed, $collapsedStates];
}

/**
 * Bản CHÍNH TẮC của một statement, dành riêng cho tầng phân loại.
 *
 * Đây là ranh giới cứng giữa hai tầng, và là bài học rút ra sau bốn vòng sửa
 * liên tiếp cùng thủng một chỗ: mỗi lần tầng dưới (sqlCharStates /
 * sqlCodeOnlyView) được sửa cho đúng hơn về mặt từ vựng, HÌNH DẠNG chuỗi mà
 * tầng trên nhận lại đổi theo, trong khi tầng trên vẫn quăng regex neo đầu
 * chuỗi vào đầu ra thô đó. Từ nay tầng trên KHÔNG bao giờ nhìn chuỗi thô
 * nữa — nó chỉ nhìn đầu ra của hàm này:
 *
 *   nguyên bản -> bỏ comment (thay bằng khoảng trắng) -> bóc gate phiên bản
 *   -> gộp khoảng trắng -> mới đưa cho regex
 *
 * Hệ quả kiểm chứng được, và đã có test canh gác khoá lại: bản chính tắc của
 * `/*!100200 ALTER TABLE t ADD a, ADD b * /` GIỐNG HỆT bản chính tắc của
 * `ALTER TABLE t ADD a, ADD b`.
 *
 * KHÔNG dùng cho chuỗi thật gửi $db->exec() — chuỗi thi hành luôn là bản
 * nguyên vẹn chưa cắt gọt.
 *
 * @return array{0: string, 1: array<int, 'code'|'string'>}
 */
function sqlClassifierView(string $statement): array
{
    [$view, $states] = sqlCodeOnlyView($statement);
    [$view, $states] = sqlStripVersionGates($view, $states);

    return sqlCollapseCodeWhitespace($view, $states);
}

/**
 * Đếm số clause DDL của một `ALTER TABLE` đã chính tắc hoá.
 *
 * Chỉ tính ký tự ở state 'code' — dấu `(`/`)`/`,` nằm trong chuỗi literal
 * (`DEFAULT 'closed)temporarily'`, `DEFAULT 'ok,pending'`) không được tính
 * vào độ sâu ngoặc hay số clause. Bản đời đầu duyệt ký tự thô nên sai cả hai
 * chiều: literal chứa `)` làm âm độ sâu ngoặc khiến dấu phẩy ranh giới clause
 * thật bị bỏ sót (nhiều clause tưởng một), literal chứa `,` bị đếm nhầm thành
 * ranh giới clause (một clause tưởng nhiều, phá tính idempotent).
 *
 * @param array<int, 'code'|'string'> $states
 */
function sqlAlterTableClauseCount(string $canonical, array $states): int
{
    $depth = 0;
    $clauseCount = 1;
    $length = strlen($canonical);

    for ($i = 0; $i < $length; $i++) {
        if (($states[$i] ?? 'code') !== 'code') {
            continue;
        }
        $char = $canonical[$i];
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
            if (preg_match('/^\s*(?:ALGORITHM\s*=|LOCK\s*=|WAIT\b|NOWAIT\b)/i', substr($canonical, $i + 1))) {
                continue;
            }
            $clauseCount++;
        }
    }

    return $clauseCount;
}

/**
 * Statement này có an toàn để NUỐT một lỗi thuộc nhóm "đã tồn tại" không?
 *
 * Một `ALTER TABLE` nhiều clause (vd. `ADD COLUMN a, ADD COLUMN b, ADD KEY
 * c`) là MỘT thao tác nguyên tử trong MariaDB — nếu bất kỳ clause nào lỗi,
 * TOÀN BỘ statement bị huỷ, không clause nào được áp dụng một phần. Nuốt lỗi
 * ở đó là ghi schema_migrations trên một lược đồ chưa đầy đủ: hỏng âm thầm,
 * và lần chạy sau sẽ không bao giờ sửa lại nữa vì version đã được đánh dấu
 * xong. (Ca thật: `20260806_upgrade.sql` có `ALTER TABLE itinerary_items` 10
 * clause.)
 *
 * HAI QUYẾT ĐỊNH CẤU TRÚC ở đây, đều nhằm đóng cả LỚP lỗi chứ không riêng
 * một ca:
 *
 * 1. Chỉ làm việc trên sqlClassifierView() — bản CHÍNH TẮC. Tầng này không
 *    còn phụ thuộc vào việc bộ quét từ vựng bên dưới hôm nay biểu diễn
 *    comment hay gate phiên bản thế nào.
 *
 * 2. DANH SÁCH CHO PHÉP, mặc định `false`. Trước đây hàm mặc định `return
 *    true` (được nuốt), nên MỌI lỗ hổng nhận diện — mỗi lần tầng dưới đổi
 *    hình dạng đầu vào — đều biến thành lỗi ÂM THẦM NUỐT, đúng hướng nguy
 *    hiểm nhất. Nay hình dạng lạ rơi về `false`: migration DỪNG, ném lỗi
 *    thật ra ngoài, schema_migrations không được ghi. Cùng một lỗ hổng, hậu
 *    quả đổi từ "hỏng ngầm không cứu được" thành "ồn ào, sửa được".
 *
 * Chỉ bốn hình dạng được vào danh sách, mỗi hình dạng đều chỉ đụng MỘT đối
 * tượng nên không có rủi ro "áp dụng một phần":
 *   - `ALTER TABLE` đúng 1 clause DDL
 *   - `CREATE [TEMPORARY] TABLE IF NOT EXISTS`
 *   - `CREATE [UNIQUE|FULLTEXT|SPATIAL] INDEX`
 *   - `DROP INDEX`
 *
 * Cố ý nằm NGOÀI danh sách: `CREATE TABLE` không có `IF NOT EXISTS` (lỗi
 * 1050 nghĩa là bảng cũ vẫn nguyên trạng, có thể khác hẳn định nghĩa trong
 * migration — nuốt là đánh dấu "xong" trên một lược đồ sai) và
 * `RENAME TABLE a TO b, c TO d` (nhiều cặp trong một thao tác nguyên tử).
 */
function statementHasSingleDdlClause(string $statement): bool
{
    [$canonical, $states] = sqlClassifierView($statement);

    if (preg_match('/^ALTER\s+TABLE\b/i', $canonical)) {
        return sqlAlterTableClauseCount($canonical, $states) === 1;
    }

    // Mệnh đề KHẲNG ĐỊNH thay cho lookahead PHỦ ĐỊNH `(?!IF\s+NOT\s+EXISTS)`
    // của bản cũ: lookahead phủ định đứng sau `\s+` tham lam bị backtrack nhả
    // lại một ký tự trắng, nên với hai dấu cách nó soi vào khoảng trắng thứ
    // hai và không bao giờ thấy `IF`. Hỏi thẳng "có IF NOT EXISTS không" thì
    // cái bẫy đó không tồn tại.
    if (preg_match('/^CREATE\s+(?:TEMPORARY\s+)?TABLE\s+IF\s+NOT\s+EXISTS\b/i', $canonical)) {
        return true;
    }

    if (preg_match('/^CREATE\s+(?:UNIQUE\s+|FULLTEXT\s+|SPATIAL\s+)?INDEX\b/i', $canonical)) {
        return true;
    }

    if (preg_match('/^DROP\s+INDEX\b/i', $canonical)) {
        return true;
    }

    return false;
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
