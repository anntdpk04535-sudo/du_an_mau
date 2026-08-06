<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

// Bước 0: giả lập ngữ cảnh HTTP. includes/functions.php dựng hằng BASE_URL từ
// $_SERVER['SCRIPT_NAME'] và $_SERVER['HTTP_HOST'] lúc include; chạy CLI thì hai
// biến này trỏ vào vendor/bin/phpunit nên BASE_URL sẽ khác nhau tuỳ cách gọi.
// Đặt cứng ở đây để url() cho ra cùng một chuỗi trong mọi lần chạy test.
// Với ba giá trị này, functions.php cắt hậu tố /public và cho
// BASE_URL === 'http://localhost/du_an_mau'.
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SCRIPT_NAME'] = '/du_an_mau/public/index.php';
$_SERVER['REQUEST_URI'] = '/du_an_mau/public/index.php';
$_SERVER['HTTPS'] = '';

// Bước 1: nạp .env — cần DB_HOST/DB_USER/DB_PASS và các khóa API.
// Việc này cũng đặt DB_NAME = tên DB THẬT, sẽ được ghi đè ở bước 3.
require_once __DIR__ . '/../config/env.php';

// Bước 2: chọn tên DB test từ biến riêng, không đọc DB_NAME.
$testDb = getenv('TEST_DB_NAME') ?: 'daklak_travel_test';
if (!str_ends_with($testDb, '_test')) {
    fwrite(STDERR, "TỪ CHỐI: DB test phải kết thúc bằng _test, nhận được: {$testDb}\n");
    exit(1);
}

$host = getenv('DB_HOST') ?: 'localhost';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';

$root = new PDO("mysql:host={$host};charset=utf8mb4", $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);
$root->exec("CREATE DATABASE IF NOT EXISTS `{$testDb}`
             CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

// Bước 3: ghi đè TRƯỚC KHI db.php định nghĩa hằng DB_NAME.
putenv("DB_NAME={$testDb}");
$_ENV['DB_NAME'] = $testDb;
$_SERVER['DB_NAME'] = $testDb;

// Bước 4: giờ hằng DB_NAME mới trỏ vào DB test.
require_once __DIR__ . '/../config/db.php';

if (DB_NAME !== $testDb) {
    fwrite(STDERR, "TỪ CHỐI: DB_NAME = " . DB_NAME . ", mong đợi {$testDb}\n");
    exit(1);
}

$db = getDB();

// Bước 5: nạp runner migration TRƯỚC khi nạp lược đồ — testSchemaStatements()
// dùng chung splitSqlStatements()/sqlCodeOnlyView() định nghĩa trong file này.
// File chỉ tồn tại từ Task 2 trở đi.
$runner = __DIR__ . '/../scripts/migrate_media.php';
if (is_file($runner)) {
    require_once $runner;
}

// Bước 6: nạp lược đồ nền nếu DB test còn trống.
$tableCount = (int)$db->query(
    'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE()'
)->fetchColumn();
if ($tableCount === 0) {
    testImportSchema($db, __DIR__ . '/../database/daklak_travel.sql');
}

// Bước 7: áp dụng mọi migration. runMigrationFile() có ghi schema_migrations
// nên chạy lại không tốn gì.
if (is_file($runner)) {
    foreach (glob(__DIR__ . '/../database/migrations/*.sql') ?: [] as $file) {
        runMigrationFile($db, $file, basename($file, '.sql'));
    }
}

/**
 * Nạp CẤU TRÚC từ bản dump, bỏ toàn bộ INSERT.
 * Test phải chạy trên lược đồ xác định, không phụ thuộc dữ liệu thật.
 *
 * Statement lỗi (vd. chỉ thị riêng của mysqldump) bị bỏ qua có chủ đích để
 * không làm sập cả bộ test, nhưng KHÔNG được im lặng: đếm số lượng và giữ
 * lại mẫu để cảnh báo ra STDERR sau khi nạp xong — cảnh báo, không throw,
 * không exit, để lỗi thật (thiếu bảng do regex tách statement sai, v.d.)
 * còn có dấu vết thay vì biến mất hoàn toàn.
 */
/**
 * Chọn danh sách statement sẽ được nạp từ bản dump: giữ phần CẤU TRÚC, bỏ
 * toàn bộ INSERT/REPLACE/LOCK TABLES.
 *
 * Tách riêng khỏi testImportSchema() để test được mà không cần chạm DB.
 *
 * Dùng CHUNG splitSqlStatements() của scripts/migrate_media.php thay cho bộ
 * tách riêng cũ (`preg_split('/;\s*\R/')` + lọc dòng đầu). Bộ tách cũ coi
 * `/*!40101 ... * /` (bỏ khoảng trắng khi đọc) là comment rồi vứt bỏ, nên cả
 * 7 chỉ thị `/*!` của dump — trong đó có `SET NAMES utf8mb4` — không bao giờ
 * được thi hành. Với MariaDB đó là mã thi hành thật, và mọi task sau đều
 * assert trên nội dung tiếng Việt nên mất nó là bẫy charset chờ sẵn.
 *
 * LƯU Ý QUAN TRỌNG khi đọc hàm này: bộ lọc INSERT BẮT BUỘC phải chạy trên
 * bản CHÍNH TẮC do sqlClassifierView() dựng, không phải trên chuỗi thô.
 * Đây là điểm gọi thứ hai của cùng một lớp lỗi đã làm thủng
 * statementHasSingleDdlClause() bốn vòng liền — một regex neo đầu chuỗi
 * quăng vào đầu ra của bộ quét — và ở đây nó có hai cách thủng đã đo được:
 *
 * - Bộ quét GIỮ NGUYÊN comment đứng trước statement (`-- Dumping data for
 *   table ...`). Chạy regex trên chuỗi thô thì comment đứng chắn phía trước
 *   làm nó không khớp và 19 lệnh INSERT của dump lọt thẳng vào DB test.
 * - mysqldump sinh `/*!40000 INSERT ... * /` (bỏ khoảng trắng khi đọc) hợp
 *   lệ, và gate `/*!` là state 'code' nên bản chỉ-chứa-code KHÔNG cắt nó —
 *   regex lại trượt lần nữa.
 *
 * Bản chính tắc bóc cả hai thứ đó trước khi regex được chạy, nên bất biến
 * "nạp cấu trúc, không nạp dữ liệu" không còn phụ thuộc vào việc hôm nay bộ
 * quét biểu diễn comment hay gate phiên bản thế nào.
 *
 * Chuỗi TRẢ VỀ vẫn là bản nguyên vẹn — bản chính tắc chỉ dùng để quyết định
 * giữ hay bỏ, không bao giờ được đem đi thi hành.
 *
 * @return string[]
 */
function testSchemaStatements(string $sql): array
{
    $statements = [];

    foreach (splitSqlStatements($sql) as $statement) {
        [$canonical] = sqlClassifierView($statement);

        if ($canonical === ''
            || preg_match('/^(INSERT|REPLACE|LOCK\s+TABLES|UNLOCK\s+TABLES)\b/i', $canonical)
        ) {
            continue;
        }

        $statements[] = $statement;
    }

    return $statements;
}

function testImportSchema(PDO $db, string $dumpPath): void
{
    $sql = (string)file_get_contents($dumpPath);

    $failedCount = 0;
    $failedSamples = [];

    foreach (testSchemaStatements($sql) as $statement) {
        try {
            $db->exec($statement);
        } catch (PDOException $e) {
            $failedCount++;
            if (count($failedSamples) < 3) {
                $failedSamples[] = mb_substr($statement, 0, 80, 'UTF-8') . ' — ' . $e->getMessage();
            }
        }
    }

    if ($failedCount > 0) {
        fwrite(STDERR, "CẢNH BÁO: {$failedCount} statement trong {$dumpPath} không áp dụng được khi nạp schema test.\n");
        foreach ($failedSamples as $sample) {
            fwrite(STDERR, "  - {$sample}\n");
        }
    }
}
