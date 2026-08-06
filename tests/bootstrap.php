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

// Bước 5: nạp lược đồ nền nếu DB test còn trống.
$tableCount = (int)$db->query(
    'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE()'
)->fetchColumn();
if ($tableCount === 0) {
    testImportSchema($db, __DIR__ . '/../database/daklak_travel.sql');
}

// Bước 6: áp dụng mọi migration. runMigrationFile() có ghi schema_migrations
// nên chạy lại không tốn gì. File này chỉ tồn tại từ Task 2 trở đi.
$runner = __DIR__ . '/../scripts/migrate_media.php';
if (is_file($runner)) {
    require_once $runner;
    foreach (glob(__DIR__ . '/../database/migrations/*.sql') ?: [] as $file) {
        runMigrationFile($db, $file, basename($file, '.sql'));
    }
}

/**
 * Nạp CẤU TRÚC từ bản dump, bỏ toàn bộ INSERT.
 * Test phải chạy trên lược đồ xác định, không phụ thuộc dữ liệu thật.
 */
function testImportSchema(PDO $db, string $dumpPath): void
{
    $sql = (string)file_get_contents($dumpPath);

    foreach (preg_split('/;\s*\R/', $sql) ?: [] as $chunk) {
        $lines = preg_split('/\R/', $chunk) ?: [];
        while ($lines !== []) {
            $first = trim($lines[0]);
            if ($first === '' || str_starts_with($first, '--') || str_starts_with($first, '/*')) {
                array_shift($lines);
                continue;
            }
            break;
        }

        $statement = trim(implode("\n", $lines));
        if ($statement === '' || preg_match('/^(INSERT|REPLACE|LOCK TABLES|UNLOCK TABLES)\b/i', $statement)) {
            continue;
        }

        try {
            $db->exec($statement);
        } catch (PDOException) {
            // Chỉ thị riêng của mysqldump không áp dụng được — bỏ qua.
        }
    }
}
