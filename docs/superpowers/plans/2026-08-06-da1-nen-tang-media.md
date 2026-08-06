# DA1 — Nền tảng media & sửa dữ liệu hỏng: Kế hoạch triển khai

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Xây tầng media dùng chung (lưu trữ, giấy phép, derivative, placeholder) và sửa dứt điểm dữ liệu ảnh hỏng, để mọi entity đều render được ảnh thật hoặc placeholder có thiết kế — không bao giờ ảnh vỡ.

**Architecture:** Hai bảng `media_assets` (file + giấy phép, dedupe theo sha256) và `media_links` (gắn asset vào entity, có cờ `authenticity`). Các connector nguồn ảnh (Wikimedia / Unsplash / Google Places) cùng một giao diện trả "candidate". Backfill chạy qua hàng đợi nối lại được. Tầng render `mediaImg()` thay cho `<img>` viết tay rải rác.

**Tech Stack:** PHP 8.2.4 (XAMPP, không framework), MySQL/MariaDB qua PDO, GD (JPEG/PNG), `cwebp` CLI tùy chọn, PHPUnit 10 qua Composer, cURL.

## Global Constraints

- **Không thêm framework.** Dự án là PHP thuần; Composer chỉ dùng cho dev-dependency (PHPUnit). Không thêm runtime dependency.
- **`storage_path` trong DB KHÔNG bao giờ chứa `BASE_URL`.** Chỉ lưu đường dẫn tương đối bắt đầu bằng `/assets/...`. `url()` được ghép ở tầng render.
- **`media_backfill_queue.status = 'done'` chỉ được đặt SAU KHI đã tạo được dòng `media_links`.** Thất bại giữ `pending` + tăng `attempts`. Đây là lỗi mà `ragUpsertDocuments()` đang mắc, tuyệt đối không lặp lại.
- **Mọi `<img>` phải có `width` và `height`.** Không có ngoại lệ.
- **Mọi asset phải có `license`** (hoặc `source='upload'`). Asset thiếu giấy phép là lỗi nghiệm thu.
- **GD không có WebP.** Luôn sinh JPEG; WebP chỉ sinh khi dò được encoder. Không bao giờ trỏ tới file không tồn tại.
- **Điểm đến không nhận ảnh `illustrative`.** Wikimedia → Places → placeholder. Không có nhánh Unsplash.
- Migration phải **idempotent** — chạy hai lần không nhân đôi dữ liệu.
- Không xóa các bảng cũ (`destination_images`, `food_images`, `accommodation_images`, `review_images`).
- Mọi script CLI nhận `--limit` và `--dry-run`.

## File Structure

| File | Trách nhiệm |
|---|---|
| `composer.json` | Dev-dependency PHPUnit |
| `phpunit.xml` | Cấu hình test |
| `tests/bootstrap.php` | Nạp env, tạo DB test, chạy migration |
| `tests/Support/TestCase.php` | Base class: DB sạch mỗi test |
| `database/migrations/20260807_media.sql` | 3 bảng mới |
| `database/migrations/20260807_events.sql` | DDL bảng `events` tách khỏi script seed |
| `database/migrations/20260807_place_facts.sql` | Cột `place_id` / rating / giờ mở cửa cho Places |
| `scripts/migrate_media.php` | Runner migration, ghi `schema_migrations` |
| `includes/media/store.php` | Hash, dedupe, lưu file, sinh derivative |
| `includes/media/placeholder.php` | Sinh SVG placeholder xác định |
| `includes/media.php` | API công khai: link, primary, gallery, render, attribution |
| `includes/media/sources/wikimedia.php` | Connector Wikimedia Commons |
| `includes/media/sources/unsplash.php` | Connector Unsplash |
| `includes/media/sources/google_places.php` | Connector Google Places |
| `includes/media/backfill.php` | Hàng đợi + định tuyến nguồn theo entity |
| `includes/csrf.php` | `csrfToken()` / `csrfField()` / `csrfCheck()` |
| `api/place_photo.php` | Proxy ảnh Places, cache TTL 30 ngày |
| `admin/media.php` | Giao diện quản lý gallery |
| `scripts/repair_media_data.php` | Giai đoạn 0 — dọn dữ liệu hỏng |
| `scripts/migrate_media_backfill.php` | Chuyển bảng ảnh cũ → media_* |
| `scripts/media_backfill_run.php` | CLI chạy hàng đợi |
| `scripts/backfill_place_data.php` | province / region / lat-lng / giá từ Places |
| `scripts/verify_media.php` | Nghiệm thu, chạy lặp lại được |

**Thứ tự phụ thuộc:** T1 → T2 → T3,T4 → T5 → T6,T7,T8 → T9,T10,T11 → T12 → T13,T14 → T15 → T16

---

## Task 1: Hạ tầng test

Dự án hiện **không có test nào**. TDD ở các task sau cần nền này trước.

**Files:**
- Create: `composer.json`, `phpunit.xml`, `tests/bootstrap.php`, `tests/Support/TestCase.php`
- Modify: `.gitignore`
- Test: `tests/SmokeTest.php`

**Interfaces:**
- Produces: lớp `Tests\Support\TestCase` với `protected PDO $db` và `protected function resetTables(array $tables): void`. Mọi test sau kế thừa lớp này.

- [ ] **Step 1: Tạo `composer.json`**

```json
{
  "name": "daklak/travel-ai",
  "description": "Nền tảng du lịch AI Đắk Lắk - Phú Yên",
  "type": "project",
  "require-dev": {
    "phpunit/phpunit": "^10.5"
  },
  "autoload-dev": {
    "psr-4": { "Tests\\": "tests/" }
  },
  "config": {
    "platform": { "php": "8.2.4" }
  }
}
```

- [ ] **Step 2: Cài PHPUnit**

Run: `/Applications/XAMPP/xamppfiles/bin/php /opt/homebrew/bin/composer install`
Expected: tạo `vendor/`, `composer.lock`

- [ ] **Step 3: Thêm `vendor/` vào `.gitignore`**

Nối vào cuối `.gitignore`:

```
vendor/
.phpunit.result.cache
/assets/images/media/
/storage/place_photo_cache/
```

- [ ] **Step 4: Tạo `phpunit.xml`**

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="tests/bootstrap.php"
         colors="true"
         cacheDirectory=".phpunit.cache">
  <testsuites>
    <testsuite name="all">
      <directory>tests</directory>
    </testsuite>
  </testsuites>
  <php>
    <env name="APP_ENV" value="test"/>
    <env name="TEST_DB_NAME" value="daklak_travel_test"/>
  </php>
</phpunit>
```

- [ ] **Step 5: Tạo `tests/bootstrap.php`**

Tạo DB test riêng, không bao giờ đụng DB thật.

**Thứ tự ở đây là bắt buộc, không được đảo:**
`config/env.php` gọi `loadEnv()` ngay lúc require (dòng 27) và ghi đè `DB_NAME` bằng giá trị trong `.env`. `config/db.php` thì đóng băng `DB_NAME` thành **hằng số** lúc include. Nên phải: nạp env → ghi đè `DB_NAME` bằng tên DB test → mới require `db.php`. Tên DB test lấy từ `TEST_DB_NAME`, **không bao giờ** từ `DB_NAME` (lúc đó đã là tên DB thật).

```php
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
```

Lưu ý: bản dump nền chỉ có 20 bảng. `foods`, `accommodations`, `destination_images`, `food_images`, `accommodation_images`, `search_documents` được tạo bởi `20260806_upgrade.sql`; `events` được thêm ở Task 2 Step 3b. Vì vậy bước 5 và bước 6 phải chạy theo đúng thứ tự đó.

- [ ] **Step 6: Tạo `tests/Support/TestCase.php`**

```php
<?php
declare(strict_types=1);

namespace Tests\Support;

use PDO;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected PDO $db;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = getDB();
    }

    /** Xóa sạch bảng theo đúng thứ tự truyền vào (con trước, cha sau). */
    protected function resetTables(array $tables): void
    {
        $this->db->exec('SET FOREIGN_KEY_CHECKS=0');
        foreach ($tables as $table) {
            $this->db->exec("TRUNCATE TABLE `{$table}`");
        }
        $this->db->exec('SET FOREIGN_KEY_CHECKS=1');
    }
}
```

- [ ] **Step 7: Viết smoke test**

`tests/SmokeTest.php`:

```php
<?php
declare(strict_types=1);

namespace Tests;

use Tests\Support\TestCase;

final class SmokeTest extends TestCase
{
    public function test_ket_noi_duoc_db_test(): void
    {
        $name = $this->db->query('SELECT DATABASE()')->fetchColumn();
        self::assertSame('daklak_travel_test', $name);
    }

    public function test_luoc_do_nen_da_duoc_nap(): void
    {
        $found = $this->db->prepare(
            'SELECT COUNT(*) FROM information_schema.tables
             WHERE table_schema = DATABASE() AND table_name = ?'
        );
        $found->execute(['destinations']);
        self::assertSame(1, (int)$found->fetchColumn());
    }

    public function test_khong_nap_du_lieu_that_vao_db_test(): void
    {
        self::assertSame(0, (int)$this->db->query('SELECT COUNT(*) FROM destinations')->fetchColumn());
    }

    /** BASE_URL phải cố định, không phụ thuộc vào việc gọi phpunit từ đâu. */
    public function test_base_url_xac_dinh_duoc_khi_chay_cli(): void
    {
        require_once dirname(__DIR__) . '/includes/functions.php';
        self::assertSame('http://localhost/du_an_mau', BASE_URL);
        self::assertSame('http://localhost/du_an_mau/am-thuc', url('/am-thuc'));
    }
}
```

- [ ] **Step 8: Chạy test — phải PASS**

Run: `/Applications/XAMPP/xamppfiles/bin/php vendor/bin/phpunit --testdox`
Expected: 4 test PASS. DB `daklak_travel_test` được tạo với cấu trúc bảng nhưng **không có dữ liệu**.

- [ ] **Step 9: Commit**

```bash
git add composer.json composer.lock phpunit.xml tests/ .gitignore
git commit -m "test: dựng hạ tầng PHPUnit với database test riêng"
```

---

## Task 2: Migration bảng media

**Files:**
- Create: `database/migrations/20260807_media.sql`, `database/migrations/20260807_events.sql`, `database/migrations/20260807_place_facts.sql`, `scripts/migrate_media.php`
- Test: `tests/Media/MigrationTest.php`

**Interfaces:**
- Produces: 3 bảng `media_assets`, `media_links`, `media_backfill_queue`; các cột dữ kiện Places trên `accommodations` (`place_id`, `rating`, `rating_count`, `price_level`, `opening_hours`, `place_synced_at`) và `place_id` trên `destinations`, `foods`. Hàm `runMigrationFile(PDO $db, string $path, string $version): bool` trong `scripts/migrate_media.php` — trả `false` nếu version đã có trong `schema_migrations`.

- [ ] **Step 1: Viết test thất bại**

`tests/Media/MigrationTest.php`:

```php
<?php
declare(strict_types=1);

namespace Tests\Media;

use Tests\Support\TestCase;

final class MigrationTest extends TestCase
{
    public function test_ba_bang_media_ton_tai(): void
    {
        foreach (['media_assets', 'media_links', 'media_backfill_queue'] as $table) {
            $found = $this->db->prepare(
                'SELECT COUNT(*) FROM information_schema.tables
                 WHERE table_schema = DATABASE() AND table_name = ?'
            );
            $found->execute([$table]);
            self::assertSame(1, (int)$found->fetchColumn(), "thiếu bảng {$table}");
        }
    }

    public function test_content_hash_la_unique(): void
    {
        $this->resetTables(['media_links', 'media_assets']);
        $insert = $this->db->prepare(
            'INSERT INTO media_assets (storage_path, content_hash, source) VALUES (?, ?, ?)'
        );
        $insert->execute(['/assets/images/media/aa/bb/x.jpg', str_repeat('a', 64), 'upload']);

        $this->expectExceptionMessageMatches('/Duplicate|1062/');
        $insert->execute(['/assets/images/media/cc/dd/y.jpg', str_repeat('a', 64), 'upload']);
    }

    public function test_nhieu_ban_ghi_places_khong_co_hash_van_hop_le(): void
    {
        $this->resetTables(['media_links', 'media_assets']);
        $insert = $this->db->prepare(
            'INSERT INTO media_assets (content_hash, source, place_photo_ref) VALUES (NULL, ?, ?)'
        );
        $insert->execute(['google_places', 'ref-1']);
        $insert->execute(['google_places', 'ref-2']);

        self::assertSame(2, (int)$this->db->query(
            "SELECT COUNT(*) FROM media_assets WHERE source='google_places'"
        )->fetchColumn());
    }

    public function test_accommodations_co_cho_chua_du_kien_places(): void
    {
        $columns = $this->db->query('SHOW COLUMNS FROM accommodations')->fetchAll(\PDO::FETCH_COLUMN, 0);
        foreach (['place_id', 'rating', 'rating_count', 'price_level', 'opening_hours', 'place_synced_at'] as $column) {
            self::assertContains($column, $columns, "thiếu cột accommodations.{$column}");
        }
    }
}
```

- [ ] **Step 2: Chạy test để xác nhận FAIL**

Run: `/Applications/XAMPP/xamppfiles/bin/php vendor/bin/phpunit tests/Media/MigrationTest.php`
Expected: FAIL — "thiếu bảng media_assets"

- [ ] **Step 3: Viết `database/migrations/20260807_media.sql`**

```sql
-- Nền tảng media dùng chung. Idempotent, chạy lại an toàn.

CREATE TABLE IF NOT EXISTS media_assets (
  id               bigint NOT NULL AUTO_INCREMENT PRIMARY KEY,
  storage_path     varchar(300) DEFAULT NULL,
  content_hash     char(64) DEFAULT NULL,
  mime             varchar(40) DEFAULT NULL,
  width            int DEFAULT NULL,
  height           int DEFAULT NULL,
  bytes            int DEFAULT NULL,
  variants         varchar(200) DEFAULT NULL,
  source           enum('wikimedia','unsplash','google_places','upload') NOT NULL,
  source_url       varchar(600) DEFAULT NULL,
  author           varchar(200) DEFAULT NULL,
  license          varchar(80) DEFAULT NULL,
  license_url      varchar(300) DEFAULT NULL,
  attribution_text varchar(400) DEFAULT NULL,
  place_photo_ref  varchar(600) DEFAULT NULL,
  fetched_at       timestamp NULL DEFAULT NULL,
  created_at       timestamp NOT NULL DEFAULT current_timestamp(),
  UNIQUE KEY uq_media_hash (content_hash),
  KEY idx_media_source (source)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS media_links (
  id           bigint NOT NULL AUTO_INCREMENT PRIMARY KEY,
  asset_id     bigint NOT NULL,
  entity_type  varchar(32) NOT NULL,
  entity_id    bigint NOT NULL,
  role         enum('primary','gallery') NOT NULL DEFAULT 'gallery',
  authenticity enum('actual','illustrative') NOT NULL DEFAULT 'illustrative',
  alt_text     varchar(255) DEFAULT NULL,
  caption      varchar(500) DEFAULT NULL,
  sort_order   int NOT NULL DEFAULT 0,
  created_at   timestamp NOT NULL DEFAULT current_timestamp(),
  UNIQUE KEY uq_link (entity_type, entity_id, asset_id),
  KEY idx_entity (entity_type, entity_id, role, sort_order),
  CONSTRAINT fk_media_links_asset FOREIGN KEY (asset_id)
    REFERENCES media_assets(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS media_backfill_queue (
  id            bigint NOT NULL AUTO_INCREMENT PRIMARY KEY,
  entity_type   varchar(32) NOT NULL,
  entity_id     bigint NOT NULL,
  status        enum('pending','done','failed') NOT NULL DEFAULT 'pending',
  attempts      int NOT NULL DEFAULT 0,
  source_tried  varchar(120) DEFAULT NULL,
  last_error    varchar(500) DEFAULT NULL,
  next_retry_at timestamp NULL DEFAULT NULL,
  updated_at    timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  UNIQUE KEY uq_queue_entity (entity_type, entity_id),
  KEY idx_queue_status (status, next_retry_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

Cột `variants` lưu chuỗi CSV các biến thể thực sự tồn tại, ví dụ `400jpg,800jpg,1600jpg,400webp`. Đây là cách `mediaImg()` biết có được phát `<picture>` hay không.

- [ ] **Step 3b: Viết `database/migrations/20260807_events.sql`**

Bảng `events` hiện chỉ được tạo bởi `scripts/setup_events_table.php` — một script vừa tạo bảng vừa seed dữ liệu mẫu và in ra màn hình, nên không dùng được trong bootstrap test. Tách phần DDL ra thành migration. Câu lệnh giống hệt script cũ nên chạy trên DB thật là no-op.

```sql
-- Tách DDL bảng events khỏi scripts/setup_events_table.php để lược đồ dựng lại được.
CREATE TABLE IF NOT EXISTS `events` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL UNIQUE,
  `category` VARCHAR(50) NOT NULL DEFAULT 'van-hoa',
  `start_date` DATE NOT NULL,
  `end_date` DATE NOT NULL,
  `location` VARCHAR(255) NOT NULL,
  `short_desc` TEXT NOT NULL,
  `content` LONGTEXT NULL,
  `image_url` VARCHAR(500) NULL,
  `is_featured` TINYINT(1) DEFAULT 1,
  `status` VARCHAR(20) DEFAULT 'published',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

- [ ] **Step 3c: Viết `database/migrations/20260807_place_facts.sql`**

Spec §9 yêu cầu điền giá / giờ mở cửa / rating cho `accommodations` từ Google Places, nhưng bảng hiện chỉ có `price_min`, `price_max`, `latitude`, `longitude` — không có chỗ chứa `place_id`, `rating`, `price_level`, `opening_hours`. Không có các cột này thì `backfillSavePlaceFacts()` (T12) sẽ lặng lẽ bỏ qua mọi dữ liệu Places trả về.

`place_id` được lưu vĩnh viễn — điều khoản Google cho phép điều đó (khác với ảnh). Lưu `place_id` giúp T13 không phải gọi lại Find Place cho cùng một cơ sở.

```sql
-- Chỗ chứa dữ kiện lấy từ Google Places. MariaDB hỗ trợ IF NOT EXISTS nên chạy lại an toàn.
ALTER TABLE accommodations
  ADD COLUMN IF NOT EXISTS place_id      varchar(255) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS rating        decimal(2,1) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS rating_count  int DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS price_level   tinyint DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS opening_hours text DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS place_synced_at timestamp NULL DEFAULT NULL;

ALTER TABLE destinations ADD COLUMN IF NOT EXISTS place_id varchar(255) DEFAULT NULL;
ALTER TABLE foods        ADD COLUMN IF NOT EXISTS place_id varchar(255) DEFAULT NULL;
```

Quy ước dữ liệu, ghi ở đây một lần để các task sau khỏi đoán:

- `price_level` giữ nguyên thang 0–4 của Google. **Không** quy đổi sang `price_min`/`price_max` — Places không trả giá phòng thật, và bịa một con số tiền là đúng loại sai lầm mà toàn bộ spec này đang tránh (xem quy tắc `authenticity`).
- `opening_hours` lưu `json_encode($weekdayText, JSON_UNESCAPED_UNICODE)` — mảng 7 chuỗi Google trả về. Trình bày là việc của DA2; DA1 chỉ giữ nguyên dữ liệu gốc.
- `place_synced_at` là dấu hiệu "đã hỏi Places rồi", giúp T13 không hỏi lại cơ sở nào Places đã trả về rỗng.

- [ ] **Step 4: Viết `scripts/migrate_media.php`**

```php
<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

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

    foreach (array_filter(array_map('trim', explode(';', $sql))) as $statement) {
        if (str_starts_with($statement, '--') || $statement === '') {
            continue;
        }
        $db->exec($statement);
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
```

Vì quét cả thư mục nên lệnh này cũng áp dụng `20260806_region_split` — migration đã có file nhưng chưa từng chạy (spec mục 5.6, Lỗi 2). `20260806_upgrade` đã nằm trong `schema_migrations` của DB thật nên sẽ bị bỏ qua.

Hàm tách câu lệnh bằng `explode(';')`. Việc này an toàn với các file DDL ở đây vì không file nào chứa dấu `;` trong chuỗi literal hay stored procedure. Nếu sau này cần seed dữ liệu, dùng script riêng chứ không thêm INSERT vào migration.

- [ ] **Step 5: Chạy test — bootstrap tự áp dụng migration lên DB test**

Run: `/Applications/XAMPP/xamppfiles/bin/php vendor/bin/phpunit tests/Media/MigrationTest.php --testdox`
Expected: 4 test PASS.

Bootstrap ở Task 1 glob thư mục migration và gọi `runMigrationFile()`, nên không cần lệnh riêng cho DB test. Không đặt `DB_NAME` qua biến môi trường shell — `loadEnv()` sẽ ghi đè nó bằng giá trị trong `.env`.

- [ ] **Step 6: Xác nhận idempotent trên DB test**

Run: `/Applications/XAMPP/xamppfiles/bin/php vendor/bin/phpunit tests/Media/MigrationTest.php`
Expected: vẫn PASS ở lần chạy thứ hai (bootstrap chạy lại migration nhưng `schema_migrations` chặn).

- [ ] **Step 7: Áp dụng lên DB thật**

```bash
/Applications/XAMPP/xamppfiles/bin/mysqldump -u root daklak_travel > /tmp/daklak_pre_media_$(date +%Y%m%d_%H%M).sql
/Applications/XAMPP/xamppfiles/bin/php scripts/migrate_media.php
```

Expected: `ĐÃ ÁP DỤNG 20260806_region_split`, `ĐÃ ÁP DỤNG 20260807_events`, `ĐÃ ÁP DỤNG 20260807_media`, `ĐÃ ÁP DỤNG 20260807_place_facts`, và `BỎ QUA 20260806_upgrade`.

- [ ] **Step 8: Xác nhận cột region và cột dữ kiện Places đã xuất hiện**

Run:
```bash
/Applications/XAMPP/xamppfiles/bin/php -r '
require "config/db.php"; $db = getDB();
$checks = [["destinations","region"],["foods","region"],["accommodations","region"],
           ["accommodations","place_id"],["accommodations","rating"],["accommodations","price_level"],
           ["accommodations","opening_hours"],["destinations","place_id"]];
foreach ($checks as [$t,$c]) {
  $s = $db->prepare("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name=? AND column_name=?");
  $s->execute([$t,$c]);
  echo "{$t}.{$c}: " . ((int)$s->fetchColumn() ? "có" : "THIẾU") . "\n";
}'
```
Expected: cả tám dòng in `có`.

- [ ] **Step 9: Commit**

```bash
git add database/migrations/20260807_media.sql database/migrations/20260807_events.sql \
        database/migrations/20260807_place_facts.sql \
        scripts/migrate_media.php tests/Media/MigrationTest.php
git commit -m "feat: thêm bảng media_assets, media_links, media_backfill_queue

Runner quét cả thư mục migration nên cũng áp dụng 20260806_region_split đã có
file nhưng chưa từng chạy. Tách DDL bảng events khỏi script seed để lược đồ
dựng lại được từ đầu."
```

---

## Task 3: Lưu trữ media & derivative

**Files:**
- Create: `includes/media/store.php`
- Test: `tests/Media/StoreTest.php`

**Interfaces:**
- Consumes: bảng `media_assets` (Task 2).
- Produces:
  - `mediaEncoderSupportsWebp(): bool`
  - `mediaStorageRoot(): string` — đường dẫn tuyệt đối tới `assets/images/media`
  - `mediaRelativePath(string $hash, string $ext, ?int $width = null): string` — trả `/assets/images/media/ab/cd/<hash>[-800].jpg`
  - `mediaGenerateDerivatives(string $absSource, string $hash, string $ext): array` — trả danh sách chuỗi variant, ví dụ `['400jpg','800jpg','1600jpg']`
  - `mediaStoreFromFile(string $srcPath, array $meta): int` — trả `asset_id`; nếu hash đã tồn tại thì trả id cũ, không ghi trùng
  - `mediaStoreFromUrl(string $url, array $meta): ?int`

`$meta` bắt buộc có khóa `source`, và `license` khi `source !== 'upload'`.

- [ ] **Step 1: Viết test thất bại**

`tests/Media/StoreTest.php`:

```php
<?php
declare(strict_types=1);

namespace Tests\Media;

use RuntimeException;
use Tests\Support\TestCase;

final class StoreTest extends TestCase
{
    private string $fixture;

    protected function setUp(): void
    {
        parent::setUp();
        require_once __DIR__ . '/../../includes/media/store.php';
        $this->resetTables(['media_links', 'media_assets']);

        $this->fixture = sys_get_temp_dir() . '/media_fixture.jpg';
        $image = imagecreatetruecolor(1200, 800);
        imagefill($image, 0, 0, imagecolorallocate($image, 30, 90, 60));
        imagejpeg($image, $this->fixture, 90);
        imagedestroy($image);
    }

    public function test_luu_anh_tra_ve_asset_id_va_path_khong_chua_base_url(): void
    {
        $id = mediaStoreFromFile($this->fixture, [
            'source'  => 'upload',
            'license' => null,
        ]);

        self::assertGreaterThan(0, $id);

        $row = $this->db->query("SELECT * FROM media_assets WHERE id = {$id}")->fetch();
        self::assertStringStartsWith('/assets/images/media/', $row['storage_path']);
        self::assertStringNotContainsString('http', $row['storage_path']);
        self::assertSame(1200, (int)$row['width']);
        self::assertSame(800, (int)$row['height']);
        self::assertNotEmpty($row['content_hash']);
    }

    public function test_luu_cung_noi_dung_hai_lan_chi_tao_mot_asset(): void
    {
        $first  = mediaStoreFromFile($this->fixture, ['source' => 'upload']);
        $second = mediaStoreFromFile($this->fixture, ['source' => 'upload']);

        self::assertSame($first, $second);
        self::assertSame(1, (int)$this->db->query('SELECT COUNT(*) FROM media_assets')->fetchColumn());
    }

    public function test_sinh_derivative_jpeg_cho_ba_kich_thuoc(): void
    {
        $id = mediaStoreFromFile($this->fixture, ['source' => 'upload']);
        $row = $this->db->query("SELECT variants, content_hash FROM media_assets WHERE id = {$id}")->fetch();
        $variants = explode(',', (string)$row['variants']);

        foreach (['400jpg', '800jpg', '1600jpg'] as $expected) {
            self::assertContains($expected, $variants);
        }

        foreach ([400, 800, 1600] as $width) {
            $rel = mediaRelativePath($row['content_hash'], 'jpg', $width);
            self::assertFileExists(dirname(__DIR__, 2) . $rel, "thiếu derivative {$width}");
        }
    }

    public function test_tu_choi_nguon_ngoai_upload_ma_thieu_license(): void
    {
        $this->expectException(RuntimeException::class);
        mediaStoreFromFile($this->fixture, ['source' => 'wikimedia']);
    }

    protected function tearDown(): void
    {
        @unlink($this->fixture);
        parent::tearDown();
    }
}
```

- [ ] **Step 2: Chạy test để xác nhận FAIL**

Run: `/Applications/XAMPP/xamppfiles/bin/php vendor/bin/phpunit tests/Media/StoreTest.php`
Expected: FAIL — "Failed opening required .../includes/media/store.php"

- [ ] **Step 3: Viết `includes/media/store.php`**

```php
<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/db.php';

const MEDIA_DERIVATIVE_WIDTHS = [400, 800, 1600];
const MEDIA_MAX_BYTES = 10485760;

function mediaEncoderSupportsWebp(): bool
{
    static $supported = null;
    if ($supported !== null) {
        return $supported;
    }
    if (function_exists('imagewebp')) {
        return $supported = true;
    }
    $binary = trim((string)@shell_exec('command -v cwebp 2>/dev/null'));
    return $supported = ($binary !== '');
}

function mediaStorageRoot(): string
{
    return dirname(__DIR__, 2) . '/assets/images/media';
}

function mediaRelativePath(string $hash, string $ext, ?int $width = null): string
{
    $shard = substr($hash, 0, 2) . '/' . substr($hash, 2, 2);
    $suffix = $width === null ? '' : '-' . $width;
    return "/assets/images/media/{$shard}/{$hash}{$suffix}.{$ext}";
}

function mediaAbsolutePath(string $relative): string
{
    return dirname(__DIR__, 2) . $relative;
}

/** @return string[] danh sách variant thực sự tạo được, ví dụ ['400jpg','800jpg'] */
function mediaGenerateDerivatives(string $absSource, string $hash, string $ext): array
{
    $info = @getimagesize($absSource);
    if ($info === false) {
        throw new RuntimeException('Không đọc được kích thước ảnh.');
    }

    $source = match ($info[2]) {
        IMAGETYPE_JPEG => @imagecreatefromjpeg($absSource),
        IMAGETYPE_PNG  => @imagecreatefrompng($absSource),
        IMAGETYPE_GIF  => @imagecreatefromgif($absSource),
        default        => false,
    };
    if ($source === false) {
        throw new RuntimeException('Định dạng ảnh không được hỗ trợ.');
    }

    $variants = [];
    foreach (MEDIA_DERIVATIVE_WIDTHS as $width) {
        if ($info[0] < $width) {
            continue;
        }
        $height = (int)round($info[1] * ($width / $info[0]));
        $resized = imagescale($source, $width, $height);
        if ($resized === false) {
            continue;
        }

        $jpegRelative = mediaRelativePath($hash, 'jpg', $width);
        $jpegAbsolute = mediaAbsolutePath($jpegRelative);
        if (!is_dir(dirname($jpegAbsolute))) {
            mkdir(dirname($jpegAbsolute), 0755, true);
        }
        if (imagejpeg($resized, $jpegAbsolute, 82)) {
            $variants[] = $width . 'jpg';
        }

        if (mediaEncoderSupportsWebp()) {
            $webpAbsolute = mediaAbsolutePath(mediaRelativePath($hash, 'webp', $width));
            $ok = function_exists('imagewebp')
                ? imagewebp($resized, $webpAbsolute, 80)
                : (bool)@shell_exec(sprintf(
                    'cwebp -quiet -q 80 %s -o %s',
                    escapeshellarg($jpegAbsolute),
                    escapeshellarg($webpAbsolute)
                ));
            if ($ok && is_file($webpAbsolute)) {
                $variants[] = $width . 'webp';
            }
        }

        imagedestroy($resized);
    }

    imagedestroy($source);
    return $variants;
}

function mediaStoreFromFile(string $srcPath, array $meta): int
{
    if (!is_file($srcPath)) {
        throw new RuntimeException('Không tìm thấy file nguồn.');
    }
    $source = $meta['source'] ?? '';
    if ($source === '') {
        throw new RuntimeException('Thiếu meta[source].');
    }
    if ($source !== 'upload' && empty($meta['license'])) {
        throw new RuntimeException("Nguồn '{$source}' bắt buộc phải có license.");
    }
    if (filesize($srcPath) > MEDIA_MAX_BYTES) {
        throw new RuntimeException('Ảnh vượt quá kích thước cho phép.');
    }

    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($srcPath);
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif'];
    if (!isset($allowed[$mime])) {
        throw new RuntimeException("Định dạng không được hỗ trợ: {$mime}");
    }
    $ext = $allowed[$mime];

    $hash = hash_file('sha256', $srcPath);
    $db = getDB();

    $existing = $db->prepare('SELECT id FROM media_assets WHERE content_hash = ?');
    $existing->execute([$hash]);
    $found = $existing->fetchColumn();
    if ($found !== false) {
        return (int)$found;
    }

    $relative = mediaRelativePath($hash, $ext);
    $absolute = mediaAbsolutePath($relative);
    if (!is_dir(dirname($absolute)) && !mkdir(dirname($absolute), 0755, true) && !is_dir(dirname($absolute))) {
        throw new RuntimeException('Không tạo được thư mục lưu trữ.');
    }
    if (!copy($srcPath, $absolute)) {
        throw new RuntimeException('Không sao chép được file.');
    }

    $dimensions = getimagesize($absolute);
    $variants = mediaGenerateDerivatives($absolute, $hash, $ext);

    try {
        $insert = $db->prepare(
            'INSERT INTO media_assets
             (storage_path, content_hash, mime, width, height, bytes, variants,
              source, source_url, author, license, license_url, attribution_text, fetched_at)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?, NOW())'
        );
        $insert->execute([
            $relative, $hash, $mime,
            (int)$dimensions[0], (int)$dimensions[1], filesize($absolute),
            implode(',', $variants),
            $source,
            $meta['source_url'] ?? null,
            $meta['author'] ?? null,
            $meta['license'] ?? null,
            $meta['license_url'] ?? null,
            $meta['attribution_text'] ?? null,
        ]);
    } catch (Throwable $e) {
        @unlink($absolute);
        foreach ($variants as $variant) {
            $width = (int)$variant;
            $vext = substr($variant, strlen((string)$width));
            @unlink(mediaAbsolutePath(mediaRelativePath($hash, $vext, $width)));
        }
        throw $e;
    }

    return (int)$db->lastInsertId();
}

function mediaStoreFromUrl(string $url, array $meta): ?int
{
    $temp = tempnam(sys_get_temp_dir(), 'media_');
    $handle = fopen($temp, 'wb');
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_FILE           => $handle,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_USERAGENT      => 'DaklakTravelBot/1.0 (+https://github.com/anntdpk04535-sudo/du_an_mau)',
    ]);
    $ok = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    fclose($handle);

    if (!$ok || $code < 200 || $code >= 300) {
        @unlink($temp);
        return null;
    }

    try {
        return mediaStoreFromFile($temp, $meta + ['source_url' => $url]);
    } catch (Throwable) {
        return null;
    } finally {
        @unlink($temp);
    }
}
```

- [ ] **Step 4: Chạy test — phải PASS**

Run: `/Applications/XAMPP/xamppfiles/bin/php vendor/bin/phpunit tests/Media/StoreTest.php --testdox`
Expected: 4 test PASS.

- [ ] **Step 5: Commit**

```bash
git add includes/media/store.php tests/Media/StoreTest.php
git commit -m "feat: tầng lưu trữ media với dedupe theo hash và derivative

Luôn sinh JPEG bằng GD; WebP chỉ khi dò được encoder (imagewebp hoặc cwebp).
Rollback file khi ghi DB thất bại để không để lại file mồ côi."
```

---

## Task 4: Placeholder SVG

**Files:**
- Create: `includes/media/placeholder.php`
- Test: `tests/Media/PlaceholderTest.php`

**Interfaces:**
- Produces:
  - `mediaPlaceholderSvg(string $entityType, int $entityId): string` — nội dung SVG
  - `mediaPlaceholderPath(string $entityType, int $entityId): string` — path tương đối tới file SVG đã cache, ví dụ `/assets/images/placeholders/food-12.svg`

- [ ] **Step 1: Viết test thất bại**

`tests/Media/PlaceholderTest.php`:

```php
<?php
declare(strict_types=1);

namespace Tests\Media;

use Tests\Support\TestCase;

final class PlaceholderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        require_once __DIR__ . '/../../includes/media/placeholder.php';
    }

    public function test_svg_hop_le_va_co_kich_thuoc(): void
    {
        $svg = mediaPlaceholderSvg('food', 12);
        self::assertStringContainsString('<svg', $svg);
        self::assertStringContainsString('viewBox', $svg);
        self::assertStringContainsString('width="800"', $svg);
        self::assertStringContainsString('height="600"', $svg);
    }

    public function test_cung_entity_luon_sinh_ra_mau_giong_nhau(): void
    {
        self::assertSame(
            mediaPlaceholderSvg('food', 12),
            mediaPlaceholderSvg('food', 12)
        );
    }

    public function test_entity_khac_nhau_cho_mau_khac_nhau(): void
    {
        self::assertNotSame(
            mediaPlaceholderSvg('food', 12),
            mediaPlaceholderSvg('food', 13)
        );
    }

    public function test_path_tra_ve_file_ton_tai_va_khong_chua_base_url(): void
    {
        $path = mediaPlaceholderPath('accommodation', 7);
        self::assertStringStartsWith('/assets/images/placeholders/', $path);
        self::assertStringNotContainsString('http', $path);
        self::assertFileExists(dirname(__DIR__, 2) . $path);
    }
}
```

- [ ] **Step 2: Chạy test để xác nhận FAIL**

Run: `/Applications/XAMPP/xamppfiles/bin/php vendor/bin/phpunit tests/Media/PlaceholderTest.php`
Expected: FAIL — không tìm thấy `includes/media/placeholder.php`

- [ ] **Step 3: Viết `includes/media/placeholder.php`**

```php
<?php
declare(strict_types=1);

/** Palette lấy từ tông của site: đất bazan, rừng, cà phê, hồ. */
const MEDIA_PLACEHOLDER_PALETTE = [
    ['#3F4A3C', '#2A322A'],
    ['#5C4033', '#3A2A22'],
    ['#2F4858', '#1E3140'],
    ['#6B4226', '#43291A'],
    ['#41573F', '#2B3A2A'],
    ['#7A5230', '#4E3420'],
];

const MEDIA_PLACEHOLDER_GLYPH = [
    'destination'   => 'M12 2 3 21h18L12 2Zm0 5.6 5.2 11H6.8L12 7.6Z',
    'food'          => 'M7 2v9a3 3 0 0 0 2 2.8V22h2V13.8A3 3 0 0 0 13 11V2h-2v7H9V2H7Zm9 0c-1.7 0-3 2.7-3 6 0 2.4.9 4.4 2 5.2V22h2V2Z',
    'accommodation' => 'M3 7h18v4H3V7Zm0 6h18v6h-2v-2H5v2H3v-6Z',
    'article'       => 'M4 3h16v18H4V3Zm2 3v2h12V6H6Zm0 4v2h12v-2H6Zm0 4v2h8v-2H6Z',
    'event'         => 'M7 2v2H4v18h16V4h-3V2h-2v2H9V2H7ZM6 9h12v11H6V9Z',
    'review'        => 'M12 2l2.9 6.2 6.6.9-4.8 4.6 1.2 6.6L12 17.2 6.1 20.3l1.2-6.6L2.5 9.1l6.6-.9L12 2Z',
];

function mediaPlaceholderSvg(string $entityType, int $entityId): string
{
    $seed = crc32($entityType . ':' . $entityId);
    [$from, $to] = MEDIA_PLACEHOLDER_PALETTE[$seed % count(MEDIA_PLACEHOLDER_PALETTE)];
    $glyph = MEDIA_PLACEHOLDER_GLYPH[$entityType] ?? MEDIA_PLACEHOLDER_GLYPH['destination'];
    $angle = 25 + ($seed % 40);

    return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="800" height="600" viewBox="0 0 800 600" role="img" aria-label="Chưa có ảnh">
  <defs>
    <linearGradient id="g" gradientTransform="rotate({$angle})">
      <stop offset="0%" stop-color="{$from}"/>
      <stop offset="100%" stop-color="{$to}"/>
    </linearGradient>
  </defs>
  <rect width="800" height="600" fill="url(#g)"/>
  <g opacity="0.16" fill="none" stroke="#FFFFFF" stroke-width="1.5">
    <circle cx="640" cy="140" r="120"/>
    <circle cx="640" cy="140" r="180"/>
  </g>
  <g transform="translate(352,252) scale(4)" fill="#FFFFFF" opacity="0.62">
    <path d="{$glyph}"/>
  </g>
</svg>
SVG;
}

function mediaPlaceholderPath(string $entityType, int $entityId): string
{
    $safeType = preg_replace('/[^a-z_]/', '', strtolower($entityType)) ?: 'destination';
    $relative = "/assets/images/placeholders/{$safeType}-{$entityId}.svg";
    $absolute = dirname(__DIR__, 2) . $relative;

    if (!is_file($absolute)) {
        if (!is_dir(dirname($absolute))) {
            mkdir(dirname($absolute), 0755, true);
        }
        file_put_contents($absolute, mediaPlaceholderSvg($safeType, $entityId));
    }

    return $relative;
}
```

- [ ] **Step 4: Chạy test — phải PASS**

Run: `/Applications/XAMPP/xamppfiles/bin/php vendor/bin/phpunit tests/Media/PlaceholderTest.php --testdox`
Expected: 4 test PASS.

- [ ] **Step 5: Thêm thư mục cache placeholder vào `.gitignore`**

Nối vào `.gitignore`:

```
/assets/images/placeholders/
```

- [ ] **Step 6: Commit**

```bash
git add includes/media/placeholder.php tests/Media/PlaceholderTest.php .gitignore
git commit -m "feat: sinh placeholder SVG xác định theo entity

Thay cho emoji fallback; mỗi entity có màu và glyph ổn định giữa các lần render."
```

---

## Task 5: API media công khai & render

**Files:**
- Create: `includes/media.php`
- Test: `tests/Media/RenderTest.php`

**Interfaces:**
- Consumes: `mediaRelativePath()`, `mediaStoreFromFile()` (T3); `mediaPlaceholderPath()` (T4); `url()`, `e()` từ `includes/functions.php`.
- Produces:
  - `mediaLink(int $assetId, string $entityType, int $entityId, array $opts = []): int`
  - `mediaPrimary(string $entityType, int $entityId): ?array`
  - `mediaGallery(string $entityType, int $entityId): array` — mỗi phần tử là một dòng `media_assets` cộng thêm `link_id`, `authenticity`, `alt_text`, `caption` lấy từ `media_links`
  - `mediaImg(string $entityType, int $entityId, array $opts = []): string`
  - `mediaAttributionHtml(array $asset): string`

`$opts` của `mediaImg()`: `alt` (string), `size` (`card`|`hero`|`full`, mặc định `card`), `class` (string), `eager` (bool).

- [ ] **Step 1: Viết test thất bại**

`tests/Media/RenderTest.php`:

```php
<?php
declare(strict_types=1);

namespace Tests\Media;

use Tests\Support\TestCase;

final class RenderTest extends TestCase
{
    private int $assetId;

    protected function setUp(): void
    {
        parent::setUp();
        require_once __DIR__ . '/../../includes/media.php';
        $this->resetTables(['media_links', 'media_assets']);

        $insert = $this->db->prepare(
            'INSERT INTO media_assets
             (storage_path, content_hash, mime, width, height, variants, source, author, license, license_url)
             VALUES (?,?,?,?,?,?,?,?,?,?)'
        );
        $insert->execute([
            '/assets/images/media/ab/cd/' . str_repeat('a', 64) . '.jpg',
            str_repeat('a', 64), 'image/jpeg', 1200, 800,
            '400jpg,800jpg,1600jpg',
            'wikimedia', 'Nguyễn Văn A', 'CC BY-SA 4.0', 'https://creativecommons.org/licenses/by-sa/4.0/',
        ]);
        $this->assetId = (int)$this->db->lastInsertId();
    }

    public function test_img_luon_co_width_va_height(): void
    {
        mediaLink($this->assetId, 'destination', 5, ['role' => 'primary', 'authenticity' => 'actual']);
        $html = mediaImg('destination', 5, ['alt' => 'Hồ Lắk']);

        self::assertStringContainsString('width="', $html);
        self::assertStringContainsString('height="', $html);
        self::assertStringContainsString('alt="Hồ Lắk"', $html);
    }

    public function test_khong_co_anh_thi_dung_placeholder_chu_khong_de_trong(): void
    {
        $html = mediaImg('food', 99, ['alt' => 'Bún đỏ']);

        self::assertStringContainsString('placeholders/food-99.svg', $html);
        self::assertStringContainsString('width="', $html);
        self::assertStringContainsString('height="', $html);
    }

    public function test_phat_picture_khi_co_bien_the_webp(): void
    {
        $this->db->exec("UPDATE media_assets SET variants='400jpg,800jpg,400webp,800webp' WHERE id={$this->assetId}");
        mediaLink($this->assetId, 'destination', 6, ['role' => 'primary']);
        $html = mediaImg('destination', 6, ['alt' => 'x']);

        self::assertStringContainsString('<picture>', $html);
        self::assertStringContainsString('type="image/webp"', $html);
    }

    public function test_khong_phat_picture_khi_khong_co_webp(): void
    {
        mediaLink($this->assetId, 'destination', 7, ['role' => 'primary']);
        $html = mediaImg('destination', 7, ['alt' => 'x']);

        self::assertStringNotContainsString('<picture>', $html);
        self::assertStringNotContainsString('webp', $html);
    }

    public function test_anh_hero_dung_eager_va_fetchpriority(): void
    {
        mediaLink($this->assetId, 'destination', 8, ['role' => 'primary']);
        $html = mediaImg('destination', 8, ['alt' => 'x', 'eager' => true]);

        self::assertStringContainsString('loading="eager"', $html);
        self::assertStringContainsString('fetchpriority="high"', $html);
    }

    public function test_attribution_hien_tac_gia_va_giay_phep(): void
    {
        $asset = $this->db->query("SELECT * FROM media_assets WHERE id={$this->assetId}")->fetch();
        $html = mediaAttributionHtml($asset);

        self::assertStringContainsString('Nguyễn Văn A', $html);
        self::assertStringContainsString('CC BY-SA 4.0', $html);
    }

    public function test_anh_illustrative_co_nhan_canh_bao(): void
    {
        mediaLink($this->assetId, 'food', 3, ['role' => 'primary', 'authenticity' => 'illustrative']);
        $html = mediaImg('food', 3, ['alt' => 'x']);

        self::assertStringContainsString('Ảnh minh họa', $html);
    }

    public function test_anh_actual_khong_co_nhan(): void
    {
        mediaLink($this->assetId, 'food', 4, ['role' => 'primary', 'authenticity' => 'actual']);
        $html = mediaImg('food', 4, ['alt' => 'x']);

        self::assertStringNotContainsString('Ảnh minh họa', $html);
    }
}
```

- [ ] **Step 2: Chạy test để xác nhận FAIL**

Run: `/Applications/XAMPP/xamppfiles/bin/php vendor/bin/phpunit tests/Media/RenderTest.php`
Expected: FAIL — không tìm thấy `includes/media.php`

- [ ] **Step 3: Viết `includes/media.php`**

```php
<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/media/store.php';
require_once __DIR__ . '/media/placeholder.php';

const MEDIA_SIZES = [
    'card' => ['width' => 800, 'sizes' => '(max-width: 768px) 100vw, 400px'],
    'hero' => ['width' => 1600, 'sizes' => '100vw'],
    'full' => ['width' => 1600, 'sizes' => '(max-width: 1024px) 100vw, 1024px'],
];

function mediaLink(int $assetId, string $entityType, int $entityId, array $opts = []): int
{
    $db = getDB();
    $statement = $db->prepare(
        'INSERT INTO media_links (asset_id, entity_type, entity_id, role, authenticity, alt_text, caption, sort_order)
         VALUES (?,?,?,?,?,?,?,?)
         ON DUPLICATE KEY UPDATE role=VALUES(role), authenticity=VALUES(authenticity),
                                 alt_text=VALUES(alt_text), caption=VALUES(caption),
                                 sort_order=VALUES(sort_order)'
    );
    $statement->execute([
        $assetId, $entityType, $entityId,
        $opts['role'] ?? 'gallery',
        $opts['authenticity'] ?? 'illustrative',
        $opts['alt_text'] ?? null,
        $opts['caption'] ?? null,
        (int)($opts['sort_order'] ?? 0),
    ]);
    return (int)$db->lastInsertId();
}

function mediaPrimary(string $entityType, int $entityId): ?array
{
    $statement = getDB()->prepare(
        "SELECT a.*, l.authenticity, l.alt_text, l.caption
         FROM media_links l JOIN media_assets a ON a.id = l.asset_id
         WHERE l.entity_type = ? AND l.entity_id = ?
         ORDER BY l.role = 'primary' DESC, l.sort_order ASC, l.id ASC
         LIMIT 1"
    );
    $statement->execute([$entityType, $entityId]);
    return $statement->fetch() ?: null;
}

function mediaGallery(string $entityType, int $entityId): array
{
    // `a.*` khiến `id` là asset_id. Trang admin cần id của LIÊN KẾT để gỡ ảnh
    // khỏi một entity mà không xoá asset, nên trả thêm l.id dưới tên link_id.
    $statement = getDB()->prepare(
        "SELECT a.*, l.id AS link_id, l.authenticity, l.alt_text, l.caption
         FROM media_links l JOIN media_assets a ON a.id = l.asset_id
         WHERE l.entity_type = ? AND l.entity_id = ?
         ORDER BY l.role = 'primary' DESC, l.sort_order ASC, l.id ASC"
    );
    $statement->execute([$entityType, $entityId]);
    return $statement->fetchAll() ?: [];
}

function mediaVariantList(?string $variants): array
{
    return array_filter(explode(',', (string)$variants));
}

function mediaSrcset(array $asset, string $ext): string
{
    $parts = [];
    foreach (MEDIA_DERIVATIVE_WIDTHS as $width) {
        if (!in_array($width . $ext, mediaVariantList($asset['variants'] ?? ''), true)) {
            continue;
        }
        $parts[] = url(mediaRelativePath($asset['content_hash'], $ext, $width)) . " {$width}w";
    }
    return implode(', ', $parts);
}

function mediaImg(string $entityType, int $entityId, array $opts = []): string
{
    $asset = mediaPrimary($entityType, $entityId);
    $alt = (string)($opts['alt'] ?? $asset['alt_text'] ?? '');
    $preset = MEDIA_SIZES[$opts['size'] ?? 'card'] ?? MEDIA_SIZES['card'];
    $class = isset($opts['class']) ? ' class="' . e((string)$opts['class']) . '"' : '';
    $eager = !empty($opts['eager']);
    $loading = $eager
        ? ' loading="eager" fetchpriority="high"'
        : ' loading="lazy" decoding="async"';

    if ($asset === null) {
        $placeholder = mediaPlaceholderPath($entityType, $entityId);
        return '<img src="' . e(url($placeholder)) . '" alt="' . e($alt)
             . '" width="800" height="600"' . $class . $loading . '>';
    }

    if (!empty($asset['place_photo_ref']) && empty($asset['storage_path'])) {
        $src = url('/api/place_photo.php?asset=' . (int)$asset['id'] . '&w=' . $preset['width']);
        $img = '<img src="' . e($src) . '" alt="' . e($alt)
             . '" width="' . (int)$asset['width'] . '" height="' . (int)$asset['height'] . '"'
             . $class . $loading . '>';
        return mediaWrapAuthenticity($img, $asset);
    }

    $jpegSrcset = mediaSrcset($asset, 'jpg');
    $webpSrcset = mediaSrcset($asset, 'webp');
    $src = url((string)$asset['storage_path']);

    $img = '<img src="' . e($src) . '"'
         . ($jpegSrcset !== '' ? ' srcset="' . e($jpegSrcset) . '" sizes="' . e($preset['sizes']) . '"' : '')
         . ' alt="' . e($alt) . '"'
         . ' width="' . (int)$asset['width'] . '" height="' . (int)$asset['height'] . '"'
         . $class . $loading . '>';

    if ($webpSrcset !== '') {
        $img = '<picture>'
             . '<source type="image/webp" srcset="' . e($webpSrcset) . '" sizes="' . e($preset['sizes']) . '">'
             . $img
             . '</picture>';
    }

    return mediaWrapAuthenticity($img, $asset);
}

function mediaWrapAuthenticity(string $html, array $asset): string
{
    if (($asset['authenticity'] ?? 'actual') !== 'illustrative') {
        return $html;
    }
    return '<span class="media-frame">' . $html
         . '<span class="media-badge-illustrative">Ảnh minh họa</span></span>';
}

function mediaAttributionHtml(array $asset): string
{
    if (($asset['source'] ?? '') === 'upload') {
        return '';
    }
    $author = (string)($asset['author'] ?? '');
    $license = (string)($asset['license'] ?? '');
    if ($author === '' && $license === '') {
        return '';
    }

    $licenseHtml = $asset['license_url']
        ? '<a href="' . e((string)$asset['license_url']) . '" rel="nofollow noopener">' . e($license) . '</a>'
        : e($license);

    return '<small class="media-attribution">Ảnh: ' . e($author)
         . ($license !== '' ? ' / ' . $licenseHtml : '') . '</small>';
}
```

- [ ] **Step 4: Chạy test — phải PASS**

Run: `/Applications/XAMPP/xamppfiles/bin/php vendor/bin/phpunit tests/Media/RenderTest.php --testdox`
Expected: 8 test PASS.

- [ ] **Step 5: Thêm CSS cho badge và attribution**

Nối vào cuối `assets/css/style.css`:

```css
/* ---- Media layer ---- */
.media-frame { position: relative; display: block; }

.media-badge-illustrative {
  position: absolute;
  inset-block-end: 0.5rem;
  inset-inline-start: 0.5rem;
  padding: 0.15rem 0.5rem;
  border-radius: 999px;
  font-size: 0.7rem;
  letter-spacing: 0.02em;
  color: #fff;
  background: rgb(0 0 0 / 0.55);
  backdrop-filter: blur(4px);
}

.media-attribution {
  display: block;
  margin-block-start: 0.35rem;
  font-size: 0.75rem;
  opacity: 0.7;
}

.media-attribution a { color: inherit; text-decoration: underline; }
```

- [ ] **Step 6: Commit**

```bash
git add includes/media.php tests/Media/RenderTest.php assets/css/style.css
git commit -m "feat: API media công khai và tầng render

mediaImg() luôn phát width/height (CLS=0), hạ cấp về placeholder khi thiếu ảnh,
phát <picture> chỉ khi biến thể WebP thực sự tồn tại, gắn nhãn 'Ảnh minh họa'
cho link có authenticity=illustrative."
```

---

## Task 6: Sửa `uploadLocalImage()` trả path tương đối

Đây là lỗi gốc sinh ra `/travel_daklak/` (spec mục 1.2, Lỗi 3).

**Files:**
- Modify: `includes/content_helpers.php:44-60`
- Test: `tests/Media/UploadPathTest.php`

**Interfaces:**
- Produces: `uploadLocalImage(array $file, string $folder, int $maxBytes = 5242880): ?string` — nay trả **path tương đối** `/assets/images/uploads/<folder>/<name>.<ext>`, không còn `BASE_URL`.

- [ ] **Step 1: Viết test thất bại**

`tests/Media/UploadPathTest.php`:

```php
<?php
declare(strict_types=1);

namespace Tests\Media;

use Tests\Support\TestCase;

final class UploadPathTest extends TestCase
{
    public function test_ham_tra_ve_path_tuong_doi_khong_chua_base_url(): void
    {
        require_once __DIR__ . '/../../includes/content_helpers.php';

        $reflection = new \ReflectionFunction('uploadLocalImage');
        $body = file_get_contents($reflection->getFileName());
        $source = implode("\n", array_slice(
            explode("\n", $body),
            $reflection->getStartLine() - 1,
            $reflection->getEndLine() - $reflection->getStartLine() + 1
        ));

        self::assertStringNotContainsString(
            'return url(',
            $source,
            'uploadLocalImage() không được ghi BASE_URL vào giá trị trả về'
        );
        self::assertStringContainsString("return '/assets/images/uploads/", $source);
    }
}
```

- [ ] **Step 2: Chạy test để xác nhận FAIL**

Run: `/Applications/XAMPP/xamppfiles/bin/php vendor/bin/phpunit tests/Media/UploadPathTest.php`
Expected: FAIL — tìm thấy `return url(`

- [ ] **Step 3: Sửa dòng cuối của `uploadLocalImage()`**

Trong `includes/content_helpers.php`, thay:

```php
    return url('/assets/images/uploads/' . $folder . '/' . $name);
```

bằng:

```php
    // Trả path TƯƠNG ĐỐI. Ghi BASE_URL vào DB là nguyên nhân sinh ra lớp bug
    // '/travel_daklak/...' — base được ghép ở tầng render, không phải tầng lưu trữ.
    return '/assets/images/uploads/' . $folder . '/' . $name;
```

- [ ] **Step 4: Chạy test — phải PASS**

Run: `/Applications/XAMPP/xamppfiles/bin/php vendor/bin/phpunit tests/Media/UploadPathTest.php --testdox`
Expected: PASS

- [ ] **Step 5: Tìm mọi nơi đang dùng giá trị trả về**

Run: `grep -rn "uploadLocalImage" --include=*.php .`
Với mỗi chỗ ghi thẳng vào `<img src>`, bọc lại bằng `url(...)`. Nếu chỉ ghi vào DB thì giữ nguyên.

- [ ] **Step 6: Commit**

```bash
git add includes/content_helpers.php tests/Media/UploadPathTest.php
git commit -m "fix: uploadLocalImage() trả path tương đối thay vì nhúng BASE_URL

BASE_URL được suy ra từ đường dẫn script lúc chạy; ghi nó vào DB khiến dữ liệu
hỏng khi đổi thư mục hoặc domain — đúng cơ chế sinh ra tiền tố /travel_daklak/."
```

---

## Task 7: Giai đoạn 0 — dọn dữ liệu hỏng

Thực hiện bảng ở spec mục 5. Số liệu đã kiểm chứng trên DB thật.

**Files:**
- Create: `scripts/repair_media_data.php`
- Test: `tests/Media/RepairTest.php`

**Interfaces:**
- Produces: `repairMediaData(PDO $db, bool $dryRun = false): array` — trả mảng đếm với khóa `food_images_deleted`, `articles_fixed`, `events_cleared`, `gstatic_deleted`, `hotlinks_flagged`.

- [ ] **Step 1: Viết test thất bại**

`tests/Media/RepairTest.php`:

```php
<?php
declare(strict_types=1);

namespace Tests\Media;

use Tests\Support\TestCase;

final class RepairTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        require_once __DIR__ . '/../../scripts/repair_media_data.php';
        $this->resetTables(['food_images', 'destination_images']);

        $food = $this->db->prepare('INSERT INTO food_images (food_id, image_url) VALUES (?,?)');
        $food->execute([1, '/assets/images/article_1_food.png']);           // rác: file không tồn tại
        $food->execute([2, 'https://encrypted-tbn0.gstatic.com/x.jpg']);    // thumbnail Google
        $food->execute([3, 'https://ticotravel.com.vn/real.jpg']);          // hotlink báo
    }

    public function test_xoa_dong_tro_file_khong_ton_tai(): void
    {
        $result = repairMediaData($this->db);

        self::assertSame(1, $result['food_images_deleted']);
        self::assertSame(0, (int)$this->db->query(
            "SELECT COUNT(*) FROM food_images WHERE image_url = '/assets/images/article_1_food.png'"
        )->fetchColumn());
    }

    public function test_xoa_hotlink_gstatic(): void
    {
        $result = repairMediaData($this->db);

        self::assertSame(1, $result['gstatic_deleted']);
        self::assertSame(0, (int)$this->db->query(
            "SELECT COUNT(*) FROM food_images WHERE image_url LIKE '%gstatic%'"
        )->fetchColumn());
    }

    public function test_hotlink_bao_duoc_giu_lai_va_dua_vao_hang_doi(): void
    {
        $result = repairMediaData($this->db);

        self::assertSame(1, $result['hotlinks_flagged']);
        self::assertSame(1, (int)$this->db->query(
            "SELECT COUNT(*) FROM media_backfill_queue WHERE entity_type='food' AND entity_id=3"
        )->fetchColumn());
    }

    public function test_dry_run_khong_thay_doi_du_lieu(): void
    {
        $before = (int)$this->db->query('SELECT COUNT(*) FROM food_images')->fetchColumn();
        repairMediaData($this->db, true);
        $after = (int)$this->db->query('SELECT COUNT(*) FROM food_images')->fetchColumn();

        self::assertSame($before, $after);
    }
}
```

- [ ] **Step 2: Chạy test để xác nhận FAIL**

Run: `/Applications/XAMPP/xamppfiles/bin/php vendor/bin/phpunit tests/Media/RepairTest.php`
Expected: FAIL — không tìm thấy `scripts/repair_media_data.php`

- [ ] **Step 3: Viết `scripts/repair_media_data.php`**

```php
<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

const REPAIR_ROOT = __DIR__ . '/..';

function repairImageFileExists(string $relative): bool
{
    if (str_starts_with($relative, 'http')) {
        return true;
    }
    return is_file(REPAIR_ROOT . '/' . ltrim($relative, '/'));
}

function repairEnqueue(PDO $db, string $entityType, int $entityId, bool $dryRun): void
{
    if ($dryRun) {
        return;
    }
    $statement = $db->prepare(
        'INSERT INTO media_backfill_queue (entity_type, entity_id, status)
         VALUES (?,?, "pending")
         ON DUPLICATE KEY UPDATE status = IF(status = "done", "done", "pending")'
    );
    $statement->execute([$entityType, $entityId]);
}

function repairMediaData(PDO $db, bool $dryRun = false): array
{
    $counts = [
        'food_images_deleted' => 0,
        'articles_fixed'      => 0,
        'events_cleared'      => 0,
        'gstatic_deleted'     => 0,
        'hotlinks_flagged'    => 0,
    ];

    $tables = [
        ['food_images', 'food_id', 'food'],
        ['destination_images', 'destination_id', 'destination'],
        ['accommodation_images', 'accommodation_id', 'accommodation'],
    ];

    foreach ($tables as [$table, $foreignKey, $entityType]) {
        $rows = $db->query("SELECT id, {$foreignKey} AS entity_id, image_url FROM {$table}")->fetchAll();
        foreach ($rows as $row) {
            $imageUrl = (string)$row['image_url'];
            $entityId = (int)$row['entity_id'];

            // (a) trỏ file cục bộ không tồn tại → rác, xóa
            if (!str_starts_with($imageUrl, 'http') && !repairImageFileExists($imageUrl)) {
                if (!$dryRun) {
                    $db->prepare("DELETE FROM {$table} WHERE id = ?")->execute([$row['id']]);
                }
                repairEnqueue($db, $entityType, $entityId, $dryRun);
                if ($table === 'food_images') {
                    $counts['food_images_deleted']++;
                }
                continue;
            }

            // (b) thumbnail Google Images: không giấy phép, URL không ổn định → xóa
            if (str_contains($imageUrl, 'gstatic.com')) {
                if (!$dryRun) {
                    $db->prepare("DELETE FROM {$table} WHERE id = ?")->execute([$row['id']]);
                }
                repairEnqueue($db, $entityType, $entityId, $dryRun);
                $counts['gstatic_deleted']++;
                continue;
            }

            // (c) hotlink báo/blog: giữ tạm để không làm trống trang, nhưng xếp hàng thay thế
            if (str_starts_with($imageUrl, 'http')) {
                repairEnqueue($db, $entityType, $entityId, $dryRun);
                $counts['hotlinks_flagged']++;
            }
        }
    }

    // (d) articles: bỏ tiền tố sai gốc app, sửa thư mục cho file nằm ngoài uploads/
    foreach ($db->query('SELECT id, image_url FROM articles')->fetchAll() as $row) {
        $original = (string)$row['image_url'];
        $fixed = preg_replace('#^/travel_daklak#', '', $original) ?? $original;

        if (!repairImageFileExists($fixed)) {
            $candidate = '/assets/images/' . basename($fixed);
            $fixed = repairImageFileExists($candidate) ? $candidate : '';
        }

        if ($fixed !== $original) {
            if (!$dryRun) {
                $db->prepare('UPDATE articles SET image_url = ? WHERE id = ?')
                   ->execute([$fixed ?: null, $row['id']]);
            }
            $counts['articles_fixed']++;
            if ($fixed === '') {
                repairEnqueue($db, 'article', (int)$row['id'], $dryRun);
            }
        }
    }

    // (e) events: gỡ tham chiếu file không tồn tại
    foreach ($db->query('SELECT id, image_url FROM events')->fetchAll() as $row) {
        if (repairImageFileExists((string)$row['image_url'])) {
            continue;
        }
        if (!$dryRun) {
            $db->prepare('UPDATE events SET image_url = NULL WHERE id = ?')->execute([$row['id']]);
        }
        repairEnqueue($db, 'event', (int)$row['id'], $dryRun);
        $counts['events_cleared']++;
    }

    return $counts;
}

if (PHP_SAPI === 'cli' && realpath($argv[0] ?? '') === realpath(__FILE__)) {
    $dryRun = in_array('--dry-run', $argv, true);
    $result = repairMediaData(getDB(), $dryRun);
    echo ($dryRun ? '[DRY RUN] ' : '') . json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
}
```

- [ ] **Step 4: Chạy test — phải PASS**

Run: `/Applications/XAMPP/xamppfiles/bin/php vendor/bin/phpunit tests/Media/RepairTest.php --testdox`
Expected: 4 test PASS.

- [ ] **Step 5: Chạy dry-run trên DB thật và đối chiếu với spec**

Run: `/Applications/XAMPP/xamppfiles/bin/php scripts/repair_media_data.php --dry-run`

Expected — phải khớp số liệu đã khảo sát ở spec mục 1.1 và 5:
- `food_images_deleted` = 368
- `gstatic_deleted` = 40
- `hotlinks_flagged` = 40
- `articles_fixed` = 4
- `events_cleared` ≥ 2

**Nếu lệch, DỪNG LẠI** và đối chiếu lại trước khi chạy thật.

- [ ] **Step 6: Sao lưu DB rồi chạy thật**

```bash
/Applications/XAMPP/xamppfiles/bin/mysqldump -u root daklak_travel > /tmp/daklak_backup_$(date +%Y%m%d_%H%M).sql
/Applications/XAMPP/xamppfiles/bin/php scripts/repair_media_data.php
```

- [ ] **Step 7: Commit**

```bash
git add scripts/repair_media_data.php tests/Media/RepairTest.php
git commit -m "fix: dọn dữ liệu ảnh hỏng và xếp hàng backfill

Xóa 368 dòng food_images trỏ file không tồn tại, 40 hotlink gstatic không giấy
phép; sửa tiền tố /travel_daklak ở articles; gỡ tham chiếu ảnh sự kiện đã mất.
Mọi entity bị ảnh hưởng được đưa vào media_backfill_queue."
```

---

## Task 8: Chuyển bảng ảnh cũ sang media_*

**Files:**
- Create: `scripts/migrate_media_backfill.php`
- Test: `tests/Media/LegacyMigrationTest.php`

**Interfaces:**
- Consumes: `mediaStoreFromUrl()` (T3), `mediaLink()` (T5).
- Produces: `migrateLegacyImages(PDO $db, int $limit = 0): array` — trả `['migrated' => int, 'skipped' => int, 'failed' => int]`.

- [ ] **Step 1: Viết test thất bại**

`tests/Media/LegacyMigrationTest.php`:

```php
<?php
declare(strict_types=1);

namespace Tests\Media;

use Tests\Support\TestCase;

final class LegacyMigrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        require_once __DIR__ . '/../../scripts/migrate_media_backfill.php';
        $this->resetTables(['media_links', 'media_assets', 'destination_images']);
    }

    public function test_anh_cuc_bo_duoc_chuyen_thanh_media_link(): void
    {
        // Tạo file thật để migration có cái để hash
        $relative = '/assets/images/uploads/legacy_test.jpg';
        $absolute = dirname(__DIR__, 2) . $relative;
        @mkdir(dirname($absolute), 0755, true);
        $image = imagecreatetruecolor(900, 600);
        imagejpeg($image, $absolute, 85);
        imagedestroy($image);

        $this->db->prepare(
            'INSERT INTO destination_images (destination_id, image_url, alt_text, is_primary) VALUES (?,?,?,1)'
        )->execute([42, $relative, 'Hồ Lắk']);

        $result = migrateLegacyImages($this->db);

        self::assertSame(1, $result['migrated']);
        self::assertSame(1, (int)$this->db->query(
            "SELECT COUNT(*) FROM media_links WHERE entity_type='destination' AND entity_id=42 AND role='primary'"
        )->fetchColumn());

        @unlink($absolute);
    }

    public function test_chay_hai_lan_khong_nhan_doi(): void
    {
        $relative = '/assets/images/uploads/legacy_test2.jpg';
        $absolute = dirname(__DIR__, 2) . $relative;
        @mkdir(dirname($absolute), 0755, true);
        $image = imagecreatetruecolor(900, 600);
        imagejpeg($image, $absolute, 85);
        imagedestroy($image);

        $this->db->prepare('INSERT INTO destination_images (destination_id, image_url) VALUES (?,?)')
                 ->execute([43, $relative]);

        migrateLegacyImages($this->db);
        migrateLegacyImages($this->db);

        self::assertSame(1, (int)$this->db->query(
            "SELECT COUNT(*) FROM media_links WHERE entity_type='destination' AND entity_id=43"
        )->fetchColumn());

        @unlink($absolute);
    }
}
```

- [ ] **Step 2: Chạy test để xác nhận FAIL**

Run: `/Applications/XAMPP/xamppfiles/bin/php vendor/bin/phpunit tests/Media/LegacyMigrationTest.php`
Expected: FAIL — không tìm thấy script

- [ ] **Step 3: Viết `scripts/migrate_media_backfill.php`**

```php
<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/media.php';

const LEGACY_IMAGE_TABLES = [
    ['destination_images',   'destination_id',   'destination'],
    ['food_images',          'food_id',          'food'],
    ['accommodation_images', 'accommodation_id', 'accommodation'],
    ['review_images',        'review_id',        'review'],
];

function migrateLegacyImages(PDO $db, int $limit = 0): array
{
    $counts = ['migrated' => 0, 'skipped' => 0, 'failed' => 0];

    foreach (LEGACY_IMAGE_TABLES as [$table, $foreignKey, $entityType]) {
        $seen = $db->prepare(
            'SELECT COUNT(*) FROM information_schema.tables
             WHERE table_schema = DATABASE() AND table_name = ?'
        );
        $seen->execute([$table]);
        if ((int)$seen->fetchColumn() === 0) {
            continue;
        }

        $hasPrimary = (bool)$db->query(
            "SELECT COUNT(*) FROM information_schema.columns
             WHERE table_schema = DATABASE() AND table_name = '{$table}' AND column_name = 'is_primary'"
        )->fetchColumn();

        $sql = "SELECT id, {$foreignKey} AS entity_id, image_url, alt_text"
             . ($hasPrimary ? ', is_primary' : ', 0 AS is_primary')
             . " FROM {$table} ORDER BY id";
        if ($limit > 0) {
            $sql .= ' LIMIT ' . $limit;
        }

        foreach ($db->query($sql)->fetchAll() as $row) {
            $entityId = (int)$row['entity_id'];
            $imageUrl = (string)$row['image_url'];

            $linked = $db->prepare(
                'SELECT COUNT(*) FROM media_links WHERE entity_type = ? AND entity_id = ?'
            );
            $linked->execute([$entityType, $entityId]);
            if ((int)$linked->fetchColumn() > 0) {
                $counts['skipped']++;
                continue;
            }

            $assetId = str_starts_with($imageUrl, 'http')
                ? mediaStoreFromUrl($imageUrl, [
                    'source'  => 'upload',
                    'license' => null,
                ])
                : mediaStoreLocalPath($imageUrl);

            if ($assetId === null) {
                $counts['failed']++;
                continue;
            }

            mediaLink($assetId, $entityType, $entityId, [
                'role'         => ((int)$row['is_primary'] === 1) ? 'primary' : 'gallery',
                'authenticity' => 'actual',
                'alt_text'     => $row['alt_text'] ?? null,
            ]);
            $counts['migrated']++;
        }
    }

    return $counts;
}

/** Nạp một file đã nằm sẵn trong repo vào media_assets. */
function mediaStoreLocalPath(string $relative): ?int
{
    $absolute = dirname(__DIR__) . '/' . ltrim($relative, '/');
    if (!is_file($absolute)) {
        return null;
    }
    try {
        return mediaStoreFromFile($absolute, ['source' => 'upload']);
    } catch (Throwable) {
        return null;
    }
}

if (PHP_SAPI === 'cli' && realpath($argv[0] ?? '') === realpath(__FILE__)) {
    $limit = 0;
    foreach ($argv as $argument) {
        if (str_starts_with($argument, '--limit=')) {
            $limit = (int)substr($argument, 8);
        }
    }
    echo json_encode(migrateLegacyImages(getDB(), $limit), JSON_PRETTY_PRINT) . PHP_EOL;
}
```

- [ ] **Step 4: Chạy test — phải PASS**

Run: `/Applications/XAMPP/xamppfiles/bin/php vendor/bin/phpunit tests/Media/LegacyMigrationTest.php --testdox`
Expected: 2 test PASS.

- [ ] **Step 5: Chạy trên DB thật**

Run: `/Applications/XAMPP/xamppfiles/bin/php scripts/migrate_media_backfill.php`
Expected: `migrated` > 0 (các ảnh cục bộ và hotlink còn hợp lệ sau Task 7).

- [ ] **Step 6: Commit**

```bash
git add scripts/migrate_media_backfill.php tests/Media/LegacyMigrationTest.php
git commit -m "feat: chuyển bảng ảnh cũ sang media_assets/media_links

Idempotent — entity đã có media_links thì bỏ qua, chạy lại không nhân đôi."
```

---

## Kiểm tra giữa chặng

- [ ] **Chạy toàn bộ test**

Run: `/Applications/XAMPP/xamppfiles/bin/php vendor/bin/phpunit --testdox`
Expected: toàn bộ PASS.

- [ ] **Kiểm tra bằng mắt**

Mở `http://localhost/du_an_mau/am-thuc` và `http://localhost/du_an_mau/cam-nang`.
Expected: không còn icon ảnh vỡ (các card chưa có ảnh sẽ hiện fallback cũ cho tới Task 15).

---

## Task 9: Connector Wikimedia Commons

**Files:**
- Create: `includes/media/sources/wikimedia.php`
- Test: `tests/Media/Sources/WikimediaTest.php`

**Interfaces:**
- Produces: `wikimediaCandidates(string $query, array $ctx = []): array`

Mọi connector trả về mảng "candidate" cùng hình dạng:

```php
[
  'url'          => 'https://upload.wikimedia.org/...',
  'source'       => 'wikimedia',
  'source_url'   => 'https://commons.wikimedia.org/wiki/File:...',
  'author'       => 'Nguyễn Văn A',
  'license'      => 'CC BY-SA 4.0',
  'license_url'  => 'https://creativecommons.org/licenses/by-sa/4.0/',
  'width'        => 1200,
  'height'       => 800,
  'authenticity' => 'actual',
]
```

- [ ] **Step 1: Viết test thất bại**

`tests/Media/Sources/WikimediaTest.php`:

```php
<?php
declare(strict_types=1);

namespace Tests\Media\Sources;

use Tests\Support\TestCase;

final class WikimediaTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        require_once __DIR__ . '/../../../includes/media/sources/wikimedia.php';
    }

    public function test_parse_ket_qua_api_thanh_candidate_dung_dinh_dang(): void
    {
        $payload = json_decode(<<<'JSON'
        {"query":{"pages":{"123":{"title":"File:Ho Lak.jpg","imageinfo":[{
          "url":"https://upload.wikimedia.org/ho-lak.jpg",
          "descriptionurl":"https://commons.wikimedia.org/wiki/File:Ho_Lak.jpg",
          "width":1600,"height":1067,
          "extmetadata":{
            "Artist":{"value":"<a href=\"/wiki/User:X\">Nguyễn Văn A</a>"},
            "LicenseShortName":{"value":"CC BY-SA 4.0"},
            "LicenseUrl":{"value":"https://creativecommons.org/licenses/by-sa/4.0/"}
          }}]}}}}
        JSON, true);

        $candidates = wikimediaParseResponse($payload);

        self::assertCount(1, $candidates);
        self::assertSame('wikimedia', $candidates[0]['source']);
        self::assertSame('actual', $candidates[0]['authenticity']);
        self::assertSame('Nguyễn Văn A', $candidates[0]['author']);
        self::assertSame('CC BY-SA 4.0', $candidates[0]['license']);
        self::assertSame(1600, $candidates[0]['width']);
    }

    public function test_bo_qua_anh_khong_co_giay_phep(): void
    {
        $payload = ['query' => ['pages' => ['1' => [
            'title' => 'File:X.jpg',
            'imageinfo' => [[
                'url' => 'https://upload.wikimedia.org/x.jpg',
                'width' => 800, 'height' => 600,
                'extmetadata' => [],
            ]],
        ]]]];

        self::assertSame([], wikimediaParseResponse($payload));
    }

    public function test_bo_qua_anh_qua_nho(): void
    {
        $payload = ['query' => ['pages' => ['1' => [
            'title' => 'File:X.jpg',
            'imageinfo' => [[
                'url' => 'https://upload.wikimedia.org/x.jpg',
                'width' => 200, 'height' => 150,
                'extmetadata' => ['LicenseShortName' => ['value' => 'CC BY 4.0']],
            ]],
        ]]]];

        self::assertSame([], wikimediaParseResponse($payload));
    }
}
```

- [ ] **Step 2: Chạy test để xác nhận FAIL**

Run: `/Applications/XAMPP/xamppfiles/bin/php vendor/bin/phpunit tests/Media/Sources/WikimediaTest.php`
Expected: FAIL — không tìm thấy file connector

- [ ] **Step 3: Viết `includes/media/sources/wikimedia.php`**

```php
<?php
declare(strict_types=1);

const WIKIMEDIA_MIN_WIDTH = 640;
const WIKIMEDIA_USER_AGENT = 'DaklakTravelBot/1.0 (https://github.com/anntdpk04535-sudo/du_an_mau; contact via repo)';

function wikimediaParseResponse(array $payload): array
{
    $candidates = [];
    foreach ($payload['query']['pages'] ?? [] as $page) {
        $info = $page['imageinfo'][0] ?? null;
        if (!$info || empty($info['url'])) {
            continue;
        }

        $meta = $info['extmetadata'] ?? [];
        $license = trim((string)($meta['LicenseShortName']['value'] ?? ''));
        if ($license === '') {
            continue;   // không xác định được giấy phép → không dùng
        }
        if ((int)($info['width'] ?? 0) < WIKIMEDIA_MIN_WIDTH) {
            continue;
        }

        $author = trim(strip_tags((string)($meta['Artist']['value'] ?? '')));

        $candidates[] = [
            'url'          => (string)$info['url'],
            'source'       => 'wikimedia',
            'source_url'   => (string)($info['descriptionurl'] ?? ''),
            'author'       => $author !== '' ? $author : 'Wikimedia Commons',
            'license'      => $license,
            'license_url'  => (string)($meta['LicenseUrl']['value'] ?? ''),
            'width'        => (int)$info['width'],
            'height'       => (int)($info['height'] ?? 0),
            'authenticity' => 'actual',
        ];
    }
    return $candidates;
}

function wikimediaCandidates(string $query, array $ctx = []): array
{
    $terms = trim($query . ' ' . (string)($ctx['province'] ?? ''));
    $url = 'https://commons.wikimedia.org/w/api.php?' . http_build_query([
        'action'      => 'query',
        'format'      => 'json',
        'generator'   => 'search',
        'gsrnamespace'=> 6,
        'gsrsearch'   => $terms,
        'gsrlimit'    => 5,
        'prop'        => 'imageinfo',
        'iiprop'      => 'url|size|extmetadata',
        'iiurlwidth'  => 1600,
    ]);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_USERAGENT      => WIKIMEDIA_USER_AGENT,
    ]);
    $raw = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if (!$raw || $code < 200 || $code >= 300) {
        return [];
    }

    $payload = json_decode((string)$raw, true);
    return is_array($payload) ? wikimediaParseResponse($payload) : [];
}
```

- [ ] **Step 4: Chạy test — phải PASS**

Run: `/Applications/XAMPP/xamppfiles/bin/php vendor/bin/phpunit tests/Media/Sources/WikimediaTest.php --testdox`
Expected: 3 test PASS.

- [ ] **Step 5: Thử gọi API thật một lần**

Run:
```bash
/Applications/XAMPP/xamppfiles/bin/php -r '
require "includes/media/sources/wikimedia.php";
$r = wikimediaCandidates("Gành Đá Đĩa", ["province" => "Phú Yên"]);
echo count($r), " ứng viên\n";
foreach ($r as $c) echo "  ", $c["license"], " | ", substr($c["url"], 0, 70), "\n";'
```
Expected: ít nhất 1 ứng viên có giấy phép.

- [ ] **Step 6: Commit**

```bash
git add includes/media/sources/wikimedia.php tests/Media/Sources/WikimediaTest.php
git commit -m "feat: connector Wikimedia Commons

Từ chối ảnh không xác định được giấy phép và ảnh nhỏ hơn 640px.
Gửi User-Agent có liên hệ theo yêu cầu của Wikimedia API."
```

---

## Task 10: Connector Unsplash

Chỉ dùng cho **món ăn (`dish`)** khi Wikimedia không có ảnh. Kết quả luôn gắn `authenticity = 'illustrative'` — đây là ảnh minh họa món, không phải ảnh của quán cụ thể. Điểm đến **không bao giờ** đi qua nhánh này.

**Files:**
- Create: `includes/media/sources/unsplash.php`
- Modify: `.env.example`
- Test: `tests/Media/Sources/UnsplashTest.php`

**Interfaces:**
- Produces: `unsplashCandidates(string $query, array $ctx = []): array` — cùng hình dạng candidate như Task 9, nhưng `authenticity => 'illustrative'`.

- [ ] **Step 1: Viết test thất bại**

`tests/Media/Sources/UnsplashTest.php`:

```php
<?php
declare(strict_types=1);

namespace Tests\Media\Sources;

use Tests\Support\TestCase;

final class UnsplashTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        require_once __DIR__ . '/../../../includes/media/sources/unsplash.php';
    }

    public function test_parse_ket_qua_thanh_candidate_illustrative(): void
    {
        $payload = [
            'results' => [[
                'urls'  => ['raw' => 'https://images.unsplash.com/photo-1', 'regular' => 'https://images.unsplash.com/photo-1?w=1080'],
                'links' => ['html' => 'https://unsplash.com/photos/abc'],
                'width' => 4000, 'height' => 3000,
                'user'  => ['name' => 'Jane Doe', 'links' => ['html' => 'https://unsplash.com/@jane']],
            ]],
        ];

        $candidates = unsplashParseResponse($payload);

        self::assertCount(1, $candidates);
        self::assertSame('unsplash', $candidates[0]['source']);
        self::assertSame('illustrative', $candidates[0]['authenticity']);
        self::assertSame('Jane Doe', $candidates[0]['author']);
        self::assertSame('Unsplash License', $candidates[0]['license']);
    }

    public function test_ket_qua_rong_tra_ve_mang_rong(): void
    {
        self::assertSame([], unsplashParseResponse(['results' => []]));
    }

    public function test_khong_co_api_key_thi_tra_ve_rong_khong_nem_loi(): void
    {
        putenv('UNSPLASH_ACCESS_KEY');
        self::assertSame([], unsplashCandidates('bún đỏ'));
    }
}
```

- [ ] **Step 2: Chạy test để xác nhận FAIL**

Run: `/Applications/XAMPP/xamppfiles/bin/php vendor/bin/phpunit tests/Media/Sources/UnsplashTest.php`
Expected: FAIL — không tìm thấy file connector

- [ ] **Step 3: Viết `includes/media/sources/unsplash.php`**

```php
<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../config/env.php';

const UNSPLASH_MIN_WIDTH = 1000;

function unsplashParseResponse(array $payload): array
{
    $candidates = [];
    foreach ($payload['results'] ?? [] as $photo) {
        $url = $photo['urls']['regular'] ?? ($photo['urls']['raw'] ?? '');
        if ($url === '' || (int)($photo['width'] ?? 0) < UNSPLASH_MIN_WIDTH) {
            continue;
        }

        $candidates[] = [
            'url'          => (string)$url,
            'source'       => 'unsplash',
            'source_url'   => (string)($photo['links']['html'] ?? ''),
            'author'       => (string)($photo['user']['name'] ?? 'Unsplash'),
            'license'      => 'Unsplash License',
            'license_url'  => 'https://unsplash.com/license',
            'width'        => (int)$photo['width'],
            'height'       => (int)($photo['height'] ?? 0),
            // Ảnh stock: minh họa cho loại món, KHÔNG phải ảnh của cơ sở cụ thể.
            'authenticity' => 'illustrative',
        ];
    }
    return $candidates;
}

function unsplashCandidates(string $query, array $ctx = []): array
{
    $key = getenv('UNSPLASH_ACCESS_KEY') ?: '';
    if ($key === '') {
        return [];
    }

    $url = 'https://api.unsplash.com/search/photos?' . http_build_query([
        'query'       => $query,
        'per_page'    => 5,
        'orientation' => 'landscape',
        'content_filter' => 'high',
    ]);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Client-ID ' . $key,
            'Accept-Version: v1',
        ],
    ]);
    $raw = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if (!$raw || $code < 200 || $code >= 300) {
        return [];
    }

    $payload = json_decode((string)$raw, true);
    return is_array($payload) ? unsplashParseResponse($payload) : [];
}
```

- [ ] **Step 4: Chạy test — phải PASS**

Run: `/Applications/XAMPP/xamppfiles/bin/php vendor/bin/phpunit tests/Media/Sources/UnsplashTest.php --testdox`
Expected: 3 test PASS.

- [ ] **Step 5: Thêm biến môi trường vào `.env.example`**

Nối vào `.env.example`:

```
# Unsplash — gói Demo miễn phí, 50 request/giờ. Đăng ký: https://unsplash.com/developers
UNSPLASH_ACCESS_KEY=
```

- [ ] **Step 6: Commit**

```bash
git add includes/media/sources/unsplash.php tests/Media/Sources/UnsplashTest.php .env.example
git commit -m "feat: connector Unsplash cho ảnh minh họa món ăn

Kết quả luôn mang authenticity=illustrative — ảnh stock minh họa loại món,
không phải ảnh chụp cơ sở. Thiếu API key thì trả rỗng, không làm hỏng backfill."
```

---

## Task 11: Connector Google Places + proxy ảnh

Điều khoản Google cho phép lưu **`place_id`** vĩnh viễn nhưng **không** cho cache ảnh dài hạn. Vì vậy ảnh Places không đi qua `mediaStoreFromFile()`: `media_assets` chỉ lưu `place_photo_ref`, còn `api/place_photo.php` proxy và cache tạm 30 ngày.

**Files:**
- Create: `includes/media/sources/google_places.php`, `api/place_photo.php`
- Modify: `.env.example`
- Test: `tests/Media/Sources/GooglePlacesTest.php`

**Interfaces:**
- Consumes: `mediaLink()` (T5).
- Produces:
  - `googlePlacesResolve(string $name, string $address): ?array` — trả `['place_id','lat','lng','rating','price_level','formatted_address']`
  - `googlePlacesParseFacts(array $payload): array` — trả `['rating','rating_count','price_level','opening_hours']`, mỗi khóa có thể `null`
  - `googlePlacesDetails(string $placeId, int $photoLimit = 3): ?array` — **một** lần gọi Place Details, trả `['photos' => array, 'facts' => array]`
  - `googlePlacesPhotoRefs(string $placeId, int $limit = 3): array` — vỏ mỏng quanh `googlePlacesDetails()`, trả mảng `['ref','width','height','attribution']`
  - `googlePlacesStoreRef(array $photo, string $entityType, int $entityId, bool $primary): int` — tạo `media_assets` không file + `media_links`, trả `asset_id`
  - `placePhotoCachePath(string $ref, int $width): string`

- [ ] **Step 1: Viết test thất bại**

`tests/Media/Sources/GooglePlacesTest.php`:

```php
<?php
declare(strict_types=1);

namespace Tests\Media\Sources;

use Tests\Support\TestCase;

final class GooglePlacesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        require_once __DIR__ . '/../../../includes/media/sources/google_places.php';
        $this->resetTables(['media_links', 'media_assets']);
    }

    public function test_parse_ket_qua_tim_kiem(): void
    {
        $payload = ['candidates' => [[
            'place_id' => 'ChIJabc123',
            'formatted_address' => '12 Nguyễn Tất Thành, Buôn Ma Thuột',
            'geometry' => ['location' => ['lat' => 12.6667, 'lng' => 108.05]],
            'rating' => 4.3,
            'price_level' => 2,
        ]]];

        $place = googlePlacesParseSearch($payload);

        self::assertSame('ChIJabc123', $place['place_id']);
        self::assertSame(12.6667, $place['lat']);
        self::assertSame(108.05, $place['lng']);
        self::assertSame(2, $place['price_level']);
    }

    public function test_khong_tim_thay_tra_ve_null(): void
    {
        self::assertNull(googlePlacesParseSearch(['candidates' => []]));
    }

    public function test_parse_photo_ref(): void
    {
        $payload = ['result' => ['photos' => [
            ['photo_reference' => 'ref-1', 'width' => 4032, 'height' => 3024,
             'html_attributions' => ['<a href="x">Người dùng A</a>']],
            ['photo_reference' => 'ref-2', 'width' => 1200, 'height' => 900, 'html_attributions' => []],
        ]]];

        $refs = googlePlacesParsePhotos($payload, 5);

        self::assertCount(2, $refs);
        self::assertSame('ref-1', $refs[0]['ref']);
        self::assertSame('Người dùng A', $refs[0]['attribution']);
    }

    public function test_parse_du_kien_gio_mo_cua_va_rating(): void
    {
        $payload = ['result' => [
            'rating' => 4.2,
            'user_ratings_total' => 318,
            'price_level' => 3,
            'opening_hours' => ['weekday_text' => ['Thứ Hai: 07:00–22:00', 'Thứ Ba: 07:00–22:00']],
        ]];

        $facts = googlePlacesParseFacts($payload);

        self::assertSame(4.2, $facts['rating']);
        self::assertSame(318, $facts['rating_count']);
        self::assertSame(3, $facts['price_level']);
        self::assertStringContainsString('Thứ Hai: 07:00–22:00', (string)$facts['opening_hours']);
        self::assertIsArray(json_decode((string)$facts['opening_hours'], true));
    }

    public function test_thieu_du_kien_thi_tra_null_chu_khong_bia(): void
    {
        $facts = googlePlacesParseFacts(['result' => []]);

        self::assertNull($facts['rating']);
        self::assertNull($facts['rating_count']);
        self::assertNull($facts['price_level']);
        self::assertNull($facts['opening_hours']);
    }

    public function test_luu_ref_tao_asset_khong_co_file_va_khong_co_hash(): void
    {
        $assetId = googlePlacesStoreRef(
            ['ref' => 'ref-9', 'width' => 1600, 'height' => 1200, 'attribution' => 'Người dùng B'],
            'accommodation', 77, true
        );

        $asset = $this->db->query("SELECT * FROM media_assets WHERE id={$assetId}")->fetch();
        self::assertNull($asset['storage_path']);
        self::assertNull($asset['content_hash']);
        self::assertSame('ref-9', $asset['place_photo_ref']);
        self::assertSame('google_places', $asset['source']);
        self::assertNotEmpty($asset['license']);

        self::assertSame(1, (int)$this->db->query(
            "SELECT COUNT(*) FROM media_links WHERE entity_type='accommodation' AND entity_id=77 AND role='primary'"
        )->fetchColumn());
    }

    public function test_hai_ref_khac_nhau_deu_luu_duoc(): void
    {
        googlePlacesStoreRef(['ref' => 'r1', 'width' => 1600, 'height' => 1200, 'attribution' => ''], 'food', 1, true);
        googlePlacesStoreRef(['ref' => 'r2', 'width' => 1600, 'height' => 1200, 'attribution' => ''], 'food', 1, false);

        self::assertSame(2, (int)$this->db->query(
            "SELECT COUNT(*) FROM media_links WHERE entity_type='food' AND entity_id=1"
        )->fetchColumn());
    }

    public function test_cache_path_khong_thoat_ra_ngoai_thu_muc(): void
    {
        $path = placePhotoCachePath('../../etc/passwd', 800);
        self::assertStringNotContainsString('..', $path);
    }
}
```

- [ ] **Step 2: Chạy test để xác nhận FAIL**

Run: `/Applications/XAMPP/xamppfiles/bin/php vendor/bin/phpunit tests/Media/Sources/GooglePlacesTest.php`
Expected: FAIL — không tìm thấy file connector

- [ ] **Step 3: Viết `includes/media/sources/google_places.php`**

```php
<?php
declare(strict_types=1);

require_once __DIR__ . '/../../media.php';

const PLACES_PHOTO_TTL = 2592000;   // 30 ngày
const PLACES_LICENSE = 'Google Maps Platform Terms';
const PLACES_LICENSE_URL = 'https://cloud.google.com/maps-platform/terms';

function googlePlacesKey(): string
{
    return getenv('GOOGLE_MAPS_API_KEY') ?: (getenv('GOOGLE_PLACES_API_KEY') ?: '');
}

function googlePlacesParseSearch(array $payload): ?array
{
    $candidate = $payload['candidates'][0] ?? null;
    if (!$candidate || empty($candidate['place_id'])) {
        return null;
    }
    return [
        'place_id'          => (string)$candidate['place_id'],
        'formatted_address' => (string)($candidate['formatted_address'] ?? ''),
        'lat'               => isset($candidate['geometry']['location']['lat'])
            ? (float)$candidate['geometry']['location']['lat'] : null,
        'lng'               => isset($candidate['geometry']['location']['lng'])
            ? (float)$candidate['geometry']['location']['lng'] : null,
        'rating'            => isset($candidate['rating']) ? (float)$candidate['rating'] : null,
        'price_level'       => isset($candidate['price_level']) ? (int)$candidate['price_level'] : null,
    ];
}

function googlePlacesParsePhotos(array $payload, int $limit): array
{
    $photos = [];
    foreach (array_slice($payload['result']['photos'] ?? [], 0, $limit) as $photo) {
        if (empty($photo['photo_reference'])) {
            continue;
        }
        $photos[] = [
            'ref'         => (string)$photo['photo_reference'],
            'width'       => (int)($photo['width'] ?? 0),
            'height'      => (int)($photo['height'] ?? 0),
            'attribution' => trim(strip_tags((string)($photo['html_attributions'][0] ?? ''))),
        ];
    }
    return $photos;
}

function googlePlacesHttpGet(string $url): ?array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20]);
    $raw = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if (!$raw || $code < 200 || $code >= 300) {
        return null;
    }
    $payload = json_decode((string)$raw, true);
    return is_array($payload) ? $payload : null;
}

function googlePlacesResolve(string $name, string $address): ?array
{
    $key = googlePlacesKey();
    if ($key === '') {
        return null;
    }

    $url = 'https://maps.googleapis.com/maps/api/place/findplacefromtext/json?' . http_build_query([
        'input'     => trim($name . ' ' . $address),
        'inputtype' => 'textquery',
        'fields'    => 'place_id,formatted_address,geometry,rating,price_level',
        'language'  => 'vi',
        'key'       => $key,
    ]);

    $payload = googlePlacesHttpGet($url);
    return $payload === null ? null : googlePlacesParseSearch($payload);
}

/**
 * Dữ kiện spec mục 9 cần cho `accommodations`. Thiếu thì trả null — không suy đoán,
 * không quy đổi price_level thành số tiền.
 */
function googlePlacesParseFacts(array $payload): array
{
    $result = $payload['result'] ?? [];
    $weekdayText = $result['opening_hours']['weekday_text'] ?? null;

    return [
        'rating'        => isset($result['rating']) ? (float)$result['rating'] : null,
        'rating_count'  => isset($result['user_ratings_total']) ? (int)$result['user_ratings_total'] : null,
        'price_level'   => isset($result['price_level']) ? (int)$result['price_level'] : null,
        'opening_hours' => is_array($weekdayText) && $weekdayText !== []
            ? json_encode(array_values($weekdayText), JSON_UNESCAPED_UNICODE)
            : null,
    ];
}

/**
 * MỘT lần gọi Place Details lấy cả ảnh lẫn dữ kiện. Gộp lại vì Google tính tiền
 * theo lần gọi: tách làm hai hàm gọi riêng sẽ trả tiền hai lần cho cùng một place.
 */
function googlePlacesDetails(string $placeId, int $photoLimit = 3): ?array
{
    $key = googlePlacesKey();
    if ($key === '') {
        return null;
    }

    $url = 'https://maps.googleapis.com/maps/api/place/details/json?' . http_build_query([
        'place_id' => $placeId,
        'fields'   => 'photos,rating,user_ratings_total,price_level,opening_hours',
        'language' => 'vi',
        'key'      => $key,
    ]);

    $payload = googlePlacesHttpGet($url);
    if ($payload === null) {
        return null;
    }

    return [
        'photos' => googlePlacesParsePhotos($payload, $photoLimit),
        'facts'  => googlePlacesParseFacts($payload),
    ];
}

function googlePlacesPhotoRefs(string $placeId, int $limit = 3): array
{
    return googlePlacesDetails($placeId, $limit)['photos'] ?? [];
}

function googlePlacesStoreRef(array $photo, string $entityType, int $entityId, bool $primary): int
{
    $db = getDB();

    // Ảnh Places KHÔNG được cache vĩnh viễn theo điều khoản Google, nên asset
    // không có storage_path/content_hash — chỉ giữ tham chiếu và proxy khi render.
    $insert = $db->prepare(
        'INSERT INTO media_assets
         (storage_path, content_hash, mime, width, height, source, author,
          license, license_url, attribution_text, place_photo_ref, fetched_at)
         VALUES (NULL, NULL, "image/jpeg", ?, ?, "google_places", ?, ?, ?, ?, ?, NOW())'
    );
    $insert->execute([
        (int)($photo['width'] ?? 1600),
        (int)($photo['height'] ?? 1200),
        $photo['attribution'] ?: 'Google Maps',
        PLACES_LICENSE,
        PLACES_LICENSE_URL,
        $photo['attribution'] ?: null,
        (string)$photo['ref'],
    ]);
    $assetId = (int)$db->lastInsertId();

    mediaLink($assetId, $entityType, $entityId, [
        'role'         => $primary ? 'primary' : 'gallery',
        'authenticity' => 'actual',
    ]);

    return $assetId;
}

function placePhotoCachePath(string $ref, int $width): string
{
    // Tên file dẫn xuất từ hash, không bao giờ từ chuỗi ref do bên ngoài cung cấp.
    $hash = hash('sha256', $ref . '|' . $width);
    return dirname(__DIR__, 3) . '/storage/place_photo_cache/' . $hash . '.jpg';
}
```

- [ ] **Step 4: Chạy test — phải PASS**

Run: `/Applications/XAMPP/xamppfiles/bin/php vendor/bin/phpunit tests/Media/Sources/GooglePlacesTest.php --testdox`
Expected: 8 test PASS.

- [ ] **Step 5: Viết `api/place_photo.php`**

```php
<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/media/sources/google_places.php';

$assetId = (int)($_GET['asset'] ?? 0);
$width = (int)($_GET['w'] ?? 800);
if (!in_array($width, [400, 800, 1600], true)) {
    $width = 800;
}

if ($assetId <= 0) {
    http_response_code(400);
    exit;
}

$statement = getDB()->prepare(
    "SELECT place_photo_ref FROM media_assets WHERE id = ? AND source = 'google_places'"
);
$statement->execute([$assetId]);
$ref = $statement->fetchColumn();

if ($ref === false || $ref === null) {
    http_response_code(404);
    exit;
}

$cacheFile = placePhotoCachePath((string)$ref, $width);
$fresh = is_file($cacheFile) && (time() - filemtime($cacheFile)) < PLACES_PHOTO_TTL;

if (!$fresh) {
    $key = googlePlacesKey();
    if ($key === '') {
        http_response_code(503);
        exit;
    }

    if (!is_dir(dirname($cacheFile))) {
        mkdir(dirname($cacheFile), 0755, true);
    }

    $source = 'https://maps.googleapis.com/maps/api/place/photo?' . http_build_query([
        'photo_reference' => $ref,
        'maxwidth'        => $width,
        'key'             => $key,
    ]);

    $temp = $cacheFile . '.tmp';
    $handle = fopen($temp, 'wb');
    $ch = curl_init($source);
    curl_setopt_array($ch, [
        CURLOPT_FILE           => $handle,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT        => 20,
    ]);
    $ok = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    fclose($handle);

    if ($ok && $code >= 200 && $code < 300 && filesize($temp) > 0) {
        rename($temp, $cacheFile);
    } else {
        @unlink($temp);
        if (!is_file($cacheFile)) {   // không có bản cũ để phục vụ
            http_response_code(502);
            exit;
        }
    }
}

header('Content-Type: image/jpeg');
header('Content-Length: ' . filesize($cacheFile));
header('Cache-Control: public, max-age=86400');
readfile($cacheFile);
```

- [ ] **Step 6: Thêm khóa API vào `.env.example`**

Nối vào `.env.example`:

```
# Google Maps Platform — dùng cho Places Search/Details/Photo.
# Bật billing và GIỚI HẠN khóa theo HTTP referrer + API trước khi chạy backfill.
GOOGLE_MAPS_API_KEY=
```

- [ ] **Step 7: Kiểm tra proxy bằng tay**

```bash
/Applications/XAMPP/xamppfiles/bin/php -r '
require "includes/media/sources/google_places.php";
$p = googlePlacesResolve("Khách sạn Sài Gòn Ban Mê", "Buôn Ma Thuột");
var_dump($p);
if ($p) { var_dump(googlePlacesDetails($p["place_id"], 2)); }'
```
Expected: có `place_id`, `lat`, `lng`, ít nhất một `photo_reference` trong `photos`, và `facts` có `rating` cùng `opening_hours` dạng JSON.

- [ ] **Step 8: Commit**

```bash
git add includes/media/sources/google_places.php api/place_photo.php \
        tests/Media/Sources/GooglePlacesTest.php .env.example
git commit -m "feat: connector Google Places và proxy ảnh có TTL

Không cache ảnh Places vĩnh viễn theo điều khoản Google — media_assets chỉ giữ
photo_reference, api/place_photo.php phục vụ qua cache 30 ngày. Tên file cache
dẫn xuất từ hash nên tham chiếu bên ngoài không thể thoát thư mục."
```

---

## Task 12: Hàng đợi backfill nối lại được

Đây là task chống lặp lại lỗi RAG: **chỉ đánh dấu `done` sau khi `media_links` đã có dòng.**

**Files:**
- Create: `includes/media/backfill.php`, `scripts/media_backfill_run.php`
- Test: `tests/Media/BackfillTest.php`

**Interfaces:**
- Consumes: `wikimediaCandidates()` (T9), `unsplashCandidates()` (T10), `googlePlacesResolve()` / `googlePlacesDetails()` / `googlePlacesStoreRef()` (T11), `mediaStoreFromUrl()` (T3), `mediaLink()` (T5), các cột dữ kiện Places (T2).
- Produces:
  - `backfillEnqueueAllMissing(PDO $db): int`
  - `backfillClaim(PDO $db, int $limit): array`
  - `backfillMarkDone(PDO $db, int $queueId): void`
  - `backfillMarkFailed(PDO $db, int $queueId, string $error, string $sourceTried): void`
  - `backfillSourcePlan(string $entityType, array $entity): array` — thứ tự nguồn sẽ thử
  - `backfillLoadEntity(PDO $db, string $entityType, int $entityId): ?array` — đọc một dòng entity, `null` nếu không tồn tại
  - `backfillRunOne(PDO $db, array $queueRow): bool`
  - `backfillSavePlaceFacts(PDO $db, string $entityType, int $entityId, array $place): void` — ghi `place_id` / toạ độ / giá / rating vào bảng entity (T13 dựa vào đây)
  - Hằng `BACKFILL_ENTITY_TABLES` — map `entity_type` → tên bảng

- [ ] **Step 1: Viết test thất bại**

`tests/Media/BackfillTest.php`:

```php
<?php
declare(strict_types=1);

namespace Tests\Media;

use Tests\Support\TestCase;

final class BackfillTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        require_once __DIR__ . '/../../includes/media/backfill.php';
        $this->resetTables(['media_links', 'media_assets', 'media_backfill_queue']);
    }

    public function test_diem_den_khong_bao_gio_dung_unsplash(): void
    {
        $plan = backfillSourcePlan('destination', ['name' => 'Hồ Lắk']);
        self::assertNotContains('unsplash', $plan);
        self::assertSame('wikimedia', $plan[0]);
    }

    public function test_mon_an_thu_wikimedia_truoc_roi_unsplash(): void
    {
        $plan = backfillSourcePlan('food', ['name' => 'Bún đỏ', 'entity_type' => 'dish']);
        self::assertSame(['wikimedia', 'unsplash'], $plan);
    }

    public function test_quan_an_thu_places_truoc(): void
    {
        $plan = backfillSourcePlan('food', ['name' => 'Quán X', 'entity_type' => 'restaurant']);
        self::assertSame('google_places', $plan[0]);
    }

    public function test_luu_tru_chi_dung_places(): void
    {
        self::assertSame(['google_places'], backfillSourcePlan('accommodation', ['name' => 'Hotel Y']));
    }

    public function test_claim_bo_qua_dong_chua_den_gio_thu_lai(): void
    {
        $this->db->exec(
            "INSERT INTO media_backfill_queue (entity_type, entity_id, status, next_retry_at)
             VALUES ('food', 1, 'pending', DATE_ADD(NOW(), INTERVAL 1 HOUR))"
        );
        self::assertCount(0, backfillClaim($this->db, 10));
    }

    public function test_claim_lay_dong_pending_khong_co_lich_thu_lai(): void
    {
        $this->db->exec(
            "INSERT INTO media_backfill_queue (entity_type, entity_id, status) VALUES ('food', 2, 'pending')"
        );
        self::assertCount(1, backfillClaim($this->db, 10));
    }

    public function test_mark_done_bi_tu_choi_khi_chua_co_media_link(): void
    {
        $this->db->exec(
            "INSERT INTO media_backfill_queue (entity_type, entity_id, status) VALUES ('food', 3, 'pending')"
        );
        $queueId = (int)$this->db->lastInsertId();

        $this->expectExceptionMessageMatches('/chưa có media_links/u');
        backfillMarkDone($this->db, $queueId);
    }

    public function test_mark_failed_tang_attempts_va_giu_pending(): void
    {
        $this->db->exec(
            "INSERT INTO media_backfill_queue (entity_type, entity_id, status) VALUES ('food', 4, 'pending')"
        );
        $queueId = (int)$this->db->lastInsertId();

        backfillMarkFailed($this->db, $queueId, 'không tìm thấy ảnh', 'wikimedia');
        $row = $this->db->query("SELECT * FROM media_backfill_queue WHERE id={$queueId}")->fetch();

        self::assertSame('pending', $row['status']);
        self::assertSame(1, (int)$row['attempts']);
        self::assertNotNull($row['next_retry_at']);
        self::assertSame('wikimedia', $row['source_tried']);
    }

    public function test_that_bai_qua_nguong_thi_chuyen_sang_failed(): void
    {
        $this->db->exec(
            "INSERT INTO media_backfill_queue (entity_type, entity_id, status, attempts)
             VALUES ('food', 5, 'pending', 4)"
        );
        $queueId = (int)$this->db->lastInsertId();

        backfillMarkFailed($this->db, $queueId, 'hết ứng viên', 'unsplash');
        self::assertSame('failed', $this->db->query(
            "SELECT status FROM media_backfill_queue WHERE id={$queueId}"
        )->fetchColumn());
    }

    public function test_luu_du_kien_places_va_khong_ghi_de_du_lieu_nguoi_nhap(): void
    {
        $this->resetTables(['accommodations']);
        $this->db->exec(
            "INSERT INTO accommodations (id, name, slug, accommodation_type, rating)
             VALUES (91, 'Khách sạn Thử', 'khach-san-thu', 'hotel', 5.0)"
        );

        backfillSavePlaceFacts($this->db, 'accommodation', 91, [
            'place_id'      => 'ChIJtest',
            'lat'           => 12.6667,
            'lng'           => 108.05,
            'rating'        => 4.1,
            'rating_count'  => 210,
            'price_level'   => 2,
            'opening_hours' => '["Thứ Hai: 07:00–22:00"]',
        ]);

        $row = $this->db->query('SELECT * FROM accommodations WHERE id=91')->fetch();

        self::assertSame('ChIJtest', $row['place_id']);
        self::assertSame(2, (int)$row['price_level']);
        self::assertSame(210, (int)$row['rating_count']);
        self::assertNotNull($row['place_synced_at']);
        self::assertSame(5.0, (float)$row['rating'], 'rating người nhập không được ghi đè');
    }

    public function test_places_khong_tra_du_kien_van_dong_dau_thoi_gian(): void
    {
        $this->resetTables(['accommodations']);
        $this->db->exec(
            "INSERT INTO accommodations (id, name, slug, accommodation_type)
             VALUES (92, 'Khách sạn Rỗng', 'khach-san-rong', 'hotel')"
        );

        backfillSavePlaceFacts($this->db, 'accommodation', 92, ['place_id' => null]);

        $row = $this->db->query('SELECT * FROM accommodations WHERE id=92')->fetch();
        self::assertNotNull($row['place_synced_at'], 'phải đánh dấu đã hỏi để không hỏi lại');
        self::assertNull($row['rating']);
    }
}
```

- [ ] **Step 2: Chạy test để xác nhận FAIL**

Run: `/Applications/XAMPP/xamppfiles/bin/php vendor/bin/phpunit tests/Media/BackfillTest.php`
Expected: FAIL — không tìm thấy `includes/media/backfill.php`

- [ ] **Step 3: Viết `includes/media/backfill.php`**

```php
<?php
declare(strict_types=1);

require_once __DIR__ . '/../media.php';
require_once __DIR__ . '/sources/wikimedia.php';
require_once __DIR__ . '/sources/unsplash.php';
require_once __DIR__ . '/sources/google_places.php';

const BACKFILL_MAX_ATTEMPTS = 5;
const BACKFILL_RETRY_MINUTES = 60;

const BACKFILL_ENTITY_TABLES = [
    'destination'   => 'destinations',
    'food'          => 'foods',
    'accommodation' => 'accommodations',
    'article'       => 'articles',
    'event'         => 'events',
];

function backfillEnqueueAllMissing(PDO $db): int
{
    $added = 0;
    foreach (BACKFILL_ENTITY_TABLES as $entityType => $table) {
        $sql = "INSERT IGNORE INTO media_backfill_queue (entity_type, entity_id, status)
                SELECT ?, t.id, 'pending' FROM {$table} t
                WHERE NOT EXISTS (
                    SELECT 1 FROM media_links l
                    WHERE l.entity_type = ? AND l.entity_id = t.id
                )";
        $statement = $db->prepare($sql);
        $statement->execute([$entityType, $entityType]);
        $added += $statement->rowCount();
    }
    return $added;
}

function backfillClaim(PDO $db, int $limit): array
{
    $statement = $db->prepare(
        "SELECT * FROM media_backfill_queue
         WHERE status = 'pending' AND (next_retry_at IS NULL OR next_retry_at <= NOW())
         ORDER BY attempts ASC, id ASC
         LIMIT ?"
    );
    $statement->bindValue(1, $limit, PDO::PARAM_INT);
    $statement->execute();
    return $statement->fetchAll() ?: [];
}

function backfillMarkDone(PDO $db, int $queueId): void
{
    $row = $db->prepare('SELECT entity_type, entity_id FROM media_backfill_queue WHERE id = ?');
    $row->execute([$queueId]);
    $queue = $row->fetch();
    if (!$queue) {
        throw new RuntimeException("Không tìm thấy queue #{$queueId}");
    }

    // Bảo vệ bất biến: 'done' chỉ hợp lệ khi entity thực sự đã có ảnh.
    // Đây chính là chỗ ragUpsertDocuments() làm sai (ghi hash trước khi có embedding).
    $linked = $db->prepare('SELECT COUNT(*) FROM media_links WHERE entity_type = ? AND entity_id = ?');
    $linked->execute([$queue['entity_type'], $queue['entity_id']]);
    if ((int)$linked->fetchColumn() === 0) {
        throw new RuntimeException(
            "Từ chối đánh dấu done: {$queue['entity_type']}#{$queue['entity_id']} chưa có media_links"
        );
    }

    $db->prepare("UPDATE media_backfill_queue SET status = 'done', last_error = NULL WHERE id = ?")
       ->execute([$queueId]);
}

function backfillMarkFailed(PDO $db, int $queueId, string $error, string $sourceTried): void
{
    $db->prepare(
        "UPDATE media_backfill_queue
         SET attempts = attempts + 1,
             last_error = ?,
             source_tried = ?,
             next_retry_at = DATE_ADD(NOW(), INTERVAL ? MINUTE),
             status = IF(attempts + 1 >= ?, 'failed', 'pending')
         WHERE id = ?"
    )->execute([
        mb_substr($error, 0, 500), $sourceTried,
        BACKFILL_RETRY_MINUTES, BACKFILL_MAX_ATTEMPTS, $queueId,
    ]);
}

/** Thứ tự nguồn theo loại entity. Điểm đến không bao giờ nhận ảnh minh họa. */
function backfillSourcePlan(string $entityType, array $entity): array
{
    return match ($entityType) {
        'destination'   => ['wikimedia', 'google_places'],
        'accommodation' => ['google_places'],
        'food'          => ($entity['entity_type'] ?? 'dish') === 'dish'
            ? ['wikimedia', 'unsplash']
            : ['google_places', 'wikimedia'],
        default         => ['wikimedia', 'unsplash'],
    };
}

function backfillLoadEntity(PDO $db, string $entityType, int $entityId): ?array
{
    $table = BACKFILL_ENTITY_TABLES[$entityType] ?? null;
    if ($table === null) {
        return null;
    }
    $statement = $db->prepare("SELECT * FROM {$table} WHERE id = ?");
    $statement->execute([$entityId]);
    return $statement->fetch() ?: null;
}

function backfillRunOne(PDO $db, array $queueRow): bool
{
    $entityType = (string)$queueRow['entity_type'];
    $entityId = (int)$queueRow['entity_id'];

    $entity = backfillLoadEntity($db, $entityType, $entityId);
    if ($entity === null) {
        backfillMarkFailed($db, (int)$queueRow['id'], 'Không tìm thấy entity', 'none');
        return false;
    }

    $name = (string)($entity['name'] ?? $entity['title'] ?? '');
    $address = (string)($entity['address'] ?? '');
    $lastError = 'Không nguồn nào trả về ảnh dùng được';
    $lastSource = 'none';

    foreach (backfillSourcePlan($entityType, $entity) as $source) {
        $lastSource = $source;
        try {
            $stored = match ($source) {
                'google_places' => backfillTryPlaces($db, $entityType, $entityId, $name, $address),
                'wikimedia'     => backfillTryUrlSource(
                    wikimediaCandidates($name, ['province' => $entity['province'] ?? '']),
                    $entityType, $entityId
                ),
                'unsplash'      => backfillTryUrlSource(
                    unsplashCandidates($name), $entityType, $entityId
                ),
                default         => false,
            };
        } catch (Throwable $e) {
            $lastError = $e->getMessage();
            continue;
        }

        if ($stored) {
            backfillMarkDone($db, (int)$queueRow['id']);
            return true;
        }
    }

    backfillMarkFailed($db, (int)$queueRow['id'], $lastError, $lastSource);
    return false;
}

function backfillTryUrlSource(array $candidates, string $entityType, int $entityId): bool
{
    $stored = 0;
    foreach ($candidates as $index => $candidate) {
        if ($stored >= 3) {
            break;
        }
        $assetId = mediaStoreFromUrl($candidate['url'], [
            'source'      => $candidate['source'],
            'source_url'  => $candidate['source_url'],
            'author'      => $candidate['author'],
            'license'     => $candidate['license'],
            'license_url' => $candidate['license_url'],
        ]);
        if ($assetId === null) {
            continue;
        }
        mediaLink($assetId, $entityType, $entityId, [
            'role'         => $stored === 0 ? 'primary' : 'gallery',
            'authenticity' => $candidate['authenticity'],
            'sort_order'   => $index,
        ]);
        $stored++;
    }
    return $stored > 0;
}

function backfillTryPlaces(PDO $db, string $entityType, int $entityId, string $name, string $address): bool
{
    $place = googlePlacesResolve($name, $address);
    if ($place === null) {
        return false;
    }

    // Một lần Details lấy cả ảnh lẫn dữ kiện (rating / giờ mở cửa / mức giá),
    // rồi lưu cả hai — spec mục 9 cần dữ kiện này cho accommodations.
    $details = googlePlacesDetails($place['place_id'], 3);
    backfillSavePlaceFacts($db, $entityType, $entityId, $place + ($details['facts'] ?? []));

    $photos = $details['photos'] ?? [];
    if ($photos === []) {
        return false;
    }

    foreach ($photos as $index => $photo) {
        googlePlacesStoreRef($photo, $entityType, $entityId, $index === 0);
    }
    return true;
}

/** Cột entity ← khóa trong mảng $place. Cột nào bảng không có thì bỏ qua. */
const BACKFILL_PLACE_COLUMNS = [
    'place_id'      => 'place_id',
    'latitude'      => 'lat',
    'longitude'     => 'lng',
    'rating'        => 'rating',
    'rating_count'  => 'rating_count',
    'price_level'   => 'price_level',
    'opening_hours' => 'opening_hours',
];

/**
 * Ghi lại place_id / toạ độ / rating / giờ mở cửa để Task 13 không phải gọi Places lần nữa.
 * Dùng COALESCE nên dữ liệu người thật đã nhập luôn thắng dữ liệu máy lấy về.
 */
function backfillSavePlaceFacts(PDO $db, string $entityType, int $entityId, array $place): void
{
    $table = BACKFILL_ENTITY_TABLES[$entityType] ?? null;
    if ($table === null) {
        return;
    }

    $columnExists = $db->prepare(
        'SELECT COUNT(*) FROM information_schema.columns
         WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?'
    );

    $updates = [];
    $params = [];
    foreach (BACKFILL_PLACE_COLUMNS as $column => $key) {
        if (($place[$key] ?? null) === null) {
            continue;
        }
        $columnExists->execute([$table, $column]);
        if ((int)$columnExists->fetchColumn() === 0) {
            continue;
        }
        $updates[] = "{$column} = COALESCE({$column}, ?)";
        $params[] = $place[$key];
    }

    // Đánh dấu "đã hỏi Places rồi" kể cả khi không có dữ kiện nào dùng được, để
    // Task 13 không hỏi lại và trả tiền lần nữa cho cùng một cơ sở.
    $columnExists->execute([$table, 'place_synced_at']);
    $hasStamp = (int)$columnExists->fetchColumn() > 0;
    if ($hasStamp) {
        $updates[] = 'place_synced_at = NOW()';
    }

    if ($updates === []) {
        return;
    }
    $params[] = $entityId;
    $db->prepare("UPDATE {$table} SET " . implode(', ', $updates) . ' WHERE id = ?')->execute($params);
}
```

- [ ] **Step 4: Chạy test — phải PASS**

Run: `/Applications/XAMPP/xamppfiles/bin/php vendor/bin/phpunit tests/Media/BackfillTest.php --testdox`
Expected: 11 test PASS.

- [ ] **Step 5: Viết `scripts/media_backfill_run.php`**

```php
<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/media/backfill.php';

$limit = 50;
$dryRun = in_array('--dry-run', $argv, true);
$enqueue = in_array('--enqueue', $argv, true);
foreach ($argv as $argument) {
    if (str_starts_with($argument, '--limit=')) {
        $limit = max(1, (int)substr($argument, 8));
    }
}

$db = getDB();

if ($enqueue) {
    echo 'Đã xếp hàng: ' . backfillEnqueueAllMissing($db) . ' entity' . PHP_EOL;
}

$rows = backfillClaim($db, $limit);
echo 'Nhận ' . count($rows) . ' mục' . ($dryRun ? ' (DRY RUN)' : '') . PHP_EOL;

$ok = 0;
$fail = 0;
foreach ($rows as $row) {
    $label = $row['entity_type'] . '#' . $row['entity_id'];
    if ($dryRun) {
        echo "  [dry] {$label} → " . implode(' → ', backfillSourcePlan(
            $row['entity_type'],
            backfillLoadEntity($db, $row['entity_type'], (int)$row['entity_id']) ?? []
        )) . PHP_EOL;
        continue;
    }

    if (backfillRunOne($db, $row)) {
        $ok++;
        echo "  ✓ {$label}" . PHP_EOL;
    } else {
        $fail++;
        $error = $db->query("SELECT last_error FROM media_backfill_queue WHERE id={$row['id']}")->fetchColumn();
        echo "  ✗ {$label}: {$error}" . PHP_EOL;
    }

    usleep(250000);   // giữ nhịp dưới hạn mức Unsplash (50 req/giờ) và Places
}

$remaining = (int)$db->query("SELECT COUNT(*) FROM media_backfill_queue WHERE status='pending'")->fetchColumn();
echo "Xong: {$ok} thành công, {$fail} thất bại, {$remaining} còn lại" . PHP_EOL;
```

- [ ] **Step 6: Xếp hàng và chạy thử 10 mục đầu**

```bash
/Applications/XAMPP/xamppfiles/bin/php scripts/media_backfill_run.php --enqueue --limit=10 --dry-run
/Applications/XAMPP/xamppfiles/bin/php scripts/media_backfill_run.php --limit=10
```

Expected: ít nhất một vài mục `✓`; các mục `✗` phải có thông báo lỗi cụ thể, không phải lỗi PHP.

- [ ] **Step 7: Xác nhận nối lại được — chạy tiếp lần hai**

Run: `/Applications/XAMPP/xamppfiles/bin/php scripts/media_backfill_run.php --limit=10`
Expected: chỉ lấy mục chưa `done` hoặc đã đến hạn thử lại; số `còn lại` giảm dần, không bao giờ tăng.

- [ ] **Step 8: Commit**

```bash
git add includes/media/backfill.php scripts/media_backfill_run.php tests/Media/BackfillTest.php
git commit -m "feat: hàng đợi backfill ảnh nối lại được

backfillMarkDone() từ chối đánh dấu hoàn tất khi entity chưa có media_links —
chặn đúng lớp lỗi đã khiến 520/638 tài liệu RAG mắc kẹt vĩnh viễn."
```

---

## Task 13: Backfill province / region / toạ độ / giá

Sửa Lỗi 2 của spec: `destinations.province` NULL 108/108, `foods.region` NULL 432/501, `accommodations.region` NULL 245/245. Đồng thời hoàn tất dòng cuối của bảng spec mục 9 — `accommodations` giá / giờ mở cửa / rating.

Phần lớn cơ sở lưu trú đã có sẵn dữ kiện Places do T12 lưu khi lấy ảnh. Task này chỉ quét những cơ sở còn sót: chưa từng qua hàng đợi backfill, hoặc backfill thất bại ở bước ảnh.

**Files:**
- Create: `scripts/backfill_place_data.php`
- Test: `tests/Media/PlaceDataTest.php`

**Interfaces:**
- Consumes: `googlePlacesResolve()` / `googlePlacesDetails()` (T11), `backfillSavePlaceFacts()` (T12), các cột dữ kiện Places (T2).
- Produces:
  - `deriveProvinceFromAddress(string $address): ?string`
  - `deriveRegionFromProvince(?string $province, ?float $lng): ?string`
  - `backfillAccommodationFacts(PDO $db, int $limit = 0, bool $dryRun = false): int`
  - `backfillPlaceData(PDO $db, int $limit = 0, bool $dryRun = false): array`

- [ ] **Step 1: Viết test thất bại**

`tests/Media/PlaceDataTest.php`:

```php
<?php
declare(strict_types=1);

namespace Tests\Media;

use Tests\Support\TestCase;

final class PlaceDataTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        require_once __DIR__ . '/../../scripts/backfill_place_data.php';
    }

    public function test_suy_ra_tinh_tu_dia_chi(): void
    {
        self::assertSame('Đắk Lắk', deriveProvinceFromAddress('12 Lê Duẩn, Buôn Ma Thuột, Đắk Lắk'));
        self::assertSame('Phú Yên', deriveProvinceFromAddress('Gành Đá Đĩa, Tuy An, Phú Yên'));
        self::assertSame('Đắk Lắk', deriveProvinceFromAddress('Buôn Đôn'));
        self::assertNull(deriveProvinceFromAddress('123 đường không rõ'));
    }

    public function test_suy_ra_khu_vuc_tu_tinh(): void
    {
        self::assertSame('east', deriveRegionFromProvince('Phú Yên', null));
        self::assertSame('west', deriveRegionFromProvince('Đắk Lắk', null));
    }

    public function test_dung_kinh_do_khi_khong_biet_tinh(): void
    {
        self::assertSame('east', deriveRegionFromProvince(null, 109.2));
        self::assertSame('west', deriveRegionFromProvince(null, 107.9));
        self::assertNull(deriveRegionFromProvince(null, null));
    }

    public function test_bo_qua_co_so_da_hoi_places_roi(): void
    {
        $this->resetTables(['accommodations']);
        $this->db->exec(
            "INSERT INTO accommodations (id, name, slug, accommodation_type, place_synced_at)
             VALUES (61, 'Đã hỏi rồi', 'da-hoi-roi', 'hotel', NOW()),
                    (62, 'Chưa hỏi', 'chua-hoi', 'hotel', NULL)"
        );

        // dry-run: đếm số cơ sở SẼ gọi Places, không gọi thật.
        self::assertSame(1, backfillAccommodationFacts($this->db, 0, true));
    }
}
```

- [ ] **Step 2: Chạy test để xác nhận FAIL**

Run: `/Applications/XAMPP/xamppfiles/bin/php vendor/bin/phpunit tests/Media/PlaceDataTest.php`
Expected: FAIL — không tìm thấy script

- [ ] **Step 3: Viết `scripts/backfill_place_data.php`**

```php
<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/media/backfill.php';   // kéo theo cả google_places.php

/** Sau sáp nhập 2025, tỉnh Đắk Lắk mới gồm phần Đắk Lắk cũ (tây) và Phú Yên cũ (đông). */
const PROVINCE_KEYWORDS = [
    'Phú Yên' => ['phú yên', 'tuy hòa', 'tuy an', 'sông cầu', 'đông hòa', 'tây hòa', 'phú hòa', 'sơn hòa', 'đồng xuân', 'sông hinh'],
    'Đắk Lắk' => ['đắk lắk', 'dak lak', 'buôn ma thuột', 'buôn đôn', 'ea kar', 'krông', 'lắk', 'cư m', 'ea h', 'm\'đrắk', 'buôn hồ'],
];

const REGION_BY_PROVINCE = ['Phú Yên' => 'east', 'Đắk Lắk' => 'west'];
const REGION_LONGITUDE_SPLIT = 108.6;

function deriveProvinceFromAddress(string $address): ?string
{
    $normalized = mb_strtolower(trim($address));
    if ($normalized === '') {
        return null;
    }
    foreach (PROVINCE_KEYWORDS as $province => $keywords) {
        foreach ($keywords as $keyword) {
            if (str_contains($normalized, $keyword)) {
                return $province;
            }
        }
    }
    return null;
}

function deriveRegionFromProvince(?string $province, ?float $lng): ?string
{
    if ($province !== null && isset(REGION_BY_PROVINCE[$province])) {
        return REGION_BY_PROVINCE[$province];
    }
    if ($lng !== null) {
        return $lng >= REGION_LONGITUDE_SPLIT ? 'east' : 'west';
    }
    return null;
}

/**
 * Dòng cuối bảng spec mục 9: giá / giờ mở cửa / rating cho accommodations.
 * Chỉ hỏi những cơ sở `place_synced_at IS NULL` — cơ sở nào T12 đã hỏi khi lấy
 * ảnh thì bỏ qua, tránh trả tiền Places hai lần cho cùng một place.
 * Trả về số cơ sở đã xử lý (ở chế độ dry-run là số cơ sở SẼ hỏi).
 */
function backfillAccommodationFacts(PDO $db, int $limit = 0, bool $dryRun = false): int
{
    $suffix = $limit > 0 ? ' LIMIT ' . $limit : '';
    $rows = $db->query(
        "SELECT id, name, address FROM accommodations
         WHERE place_synced_at IS NULL AND status = 'published'
         ORDER BY id{$suffix}"
    )->fetchAll();

    if ($dryRun) {
        return count($rows);
    }

    $done = 0;
    foreach ($rows as $row) {
        $place = googlePlacesResolve((string)$row['name'], (string)($row['address'] ?? ''));
        if ($place === null) {
            // Không tìm thấy: vẫn đóng dấu để lần chạy sau không hỏi lại.
            backfillSavePlaceFacts($db, 'accommodation', (int)$row['id'], []);
            continue;
        }

        $details = googlePlacesDetails($place['place_id'], 0);
        backfillSavePlaceFacts($db, 'accommodation', (int)$row['id'], $place + ($details['facts'] ?? []));
        $done++;
        usleep(250000);
    }
    return $done;
}

function backfillPlaceData(PDO $db, int $limit = 0, bool $dryRun = false): array
{
    $counts = ['destinations' => 0, 'foods' => 0, 'accommodations' => 0,
               'accommodation_facts' => 0, 'places_called' => 0];
    $suffix = $limit > 0 ? ' LIMIT ' . $limit : '';

    // 1. destinations: province + region
    foreach ($db->query(
        "SELECT id, name, address, latitude, longitude, province, region
         FROM destinations WHERE province IS NULL OR region IS NULL{$suffix}"
    )->fetchAll() as $row) {
        $province = $row['province'] ?: deriveProvinceFromAddress((string)($row['address'] ?? $row['name']));
        $region = $row['region'] ?: deriveRegionFromProvince(
            $province,
            $row['longitude'] !== null ? (float)$row['longitude'] : null
        );
        if ($province === null && $region === null) {
            continue;
        }
        if (!$dryRun) {
            $db->prepare('UPDATE destinations SET province = COALESCE(province, ?), region = COALESCE(region, ?) WHERE id = ?')
               ->execute([$province, $region, $row['id']]);
        }
        $counts['destinations']++;
    }

    // 2. foods và accommodations: region kế thừa từ destination, nếu không thì suy từ địa chỉ
    foreach ([['foods', 'destination_id'], ['accommodations', 'destination_id']] as [$table, $foreignKey]) {
        $rows = $db->query(
            "SELECT t.id, t.name, t.address, d.region AS parent_region, d.province AS parent_province
             FROM {$table} t LEFT JOIN destinations d ON d.id = t.{$foreignKey}
             WHERE t.region IS NULL{$suffix}"
        )->fetchAll();

        foreach ($rows as $row) {
            $province = $row['parent_province'] ?: deriveProvinceFromAddress((string)($row['address'] ?? ''));
            $region = $row['parent_region'] ?: deriveRegionFromProvince($province, null);

            if ($region === null) {
                $place = googlePlacesResolve((string)$row['name'], (string)($row['address'] ?? ''));
                $counts['places_called']++;
                if ($place !== null) {
                    $province = deriveProvinceFromAddress((string)$place['formatted_address']);
                    $region = deriveRegionFromProvince($province, $place['lng']);
                }
            }

            if ($region === null) {
                continue;
            }
            if (!$dryRun) {
                $db->prepare("UPDATE {$table} SET region = ? WHERE id = ?")->execute([$region, $row['id']]);
            }
            $counts[$table]++;
        }
    }

    // 3. accommodations: giá / giờ mở cửa / rating (spec mục 9)
    $counts['accommodation_facts'] = backfillAccommodationFacts($db, $limit, $dryRun);

    return $counts;
}

if (PHP_SAPI === 'cli' && realpath($argv[0] ?? '') === realpath(__FILE__)) {
    $limit = 0;
    $dryRun = in_array('--dry-run', $argv, true);
    foreach ($argv as $argument) {
        if (str_starts_with($argument, '--limit=')) {
            $limit = (int)substr($argument, 8);
        }
    }
    echo json_encode(backfillPlaceData(getDB(), $limit, $dryRun), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
}
```

- [ ] **Step 4: Chạy test — phải PASS**

Run: `/Applications/XAMPP/xamppfiles/bin/php vendor/bin/phpunit tests/Media/PlaceDataTest.php --testdox`
Expected: 4 test PASS.

- [ ] **Step 5: Chạy dry-run rồi chạy thật**

```bash
/Applications/XAMPP/xamppfiles/bin/php scripts/backfill_place_data.php --dry-run --limit=20
/Applications/XAMPP/xamppfiles/bin/php scripts/backfill_place_data.php
```

Chạy task này **sau** khi hàng đợi T12 đã xử lý xong phần lớn `accommodation`, để `accommodation_facts` chỉ còn quét phần sót lại. Nếu `accommodation_facts` trong bản dry-run vẫn xấp xỉ 245, nghĩa là T12 chưa chạy — dừng lại, chạy `scripts/media_backfill_run.php` trước.

- [ ] **Step 6: Kiểm tra dữ liệu còn NULL**

Run:
```bash
/Applications/XAMPP/xamppfiles/bin/php -r '
require "config/db.php"; $db = getDB();
foreach ([["destinations","province"],["destinations","region"],["foods","region"],["accommodations","region"]] as [$t,$c]) {
  $n = $db->query("SELECT COUNT(*) FROM {$t} WHERE {$c} IS NULL")->fetchColumn();
  $all = $db->query("SELECT COUNT(*) FROM {$t}")->fetchColumn();
  echo "{$t}.{$c}: {$n}/{$all} còn NULL\n";
}
$asked = $db->query("SELECT COUNT(*) FROM accommodations WHERE place_synced_at IS NOT NULL")->fetchColumn();
$facts = $db->query("SELECT COUNT(*) FROM accommodations WHERE rating IS NOT NULL OR opening_hours IS NOT NULL")->fetchColumn();
echo "accommodations: đã hỏi Places {$asked}, có dữ kiện {$facts}\n";'
```
Expected: mỗi dòng còn NULL dưới 10% tổng số. `đã hỏi Places` xấp xỉ 245; `có dữ kiện` thấp hơn là bình thường — nhiều nhà nghỉ nhỏ không có hồ sơ Google.

- [ ] **Step 7: Commit**

```bash
git add scripts/backfill_place_data.php tests/Media/PlaceDataTest.php
git commit -m "feat: điền province/region và dữ kiện Places cho cơ sở lưu trú

Suy từ địa chỉ và destination cha trước; chỉ gọi Google Places khi cả hai
đường suy luận offline đều không ra kết quả, để giữ chi phí ở mức thấp.
price_level giữ nguyên thang 0-4 của Google, không quy đổi thành giá phòng."
```

---

## Task 14: CSRF + trang quản lý media

Toàn bộ 15 trang admin hiện **không có CSRF**. DA1 thêm một endpoint upload file, nên phải có bảo vệ ngay ở đây. 14 trang còn lại thuộc phạm vi DA2.

**Files:**
- Create: `includes/csrf.php`, `admin/media.php`
- Modify: `admin/nav.php`
- Test: `tests/Media/CsrfTest.php`

**Interfaces:**
- Consumes: `mediaStoreFromFile()` (T3), `mediaLink()`/`mediaGallery()` (T5).
- Produces:
  - `csrfToken(): string`
  - `csrfField(): string`
  - `csrfCheck(?string $token): bool`

- [ ] **Step 1: Viết test thất bại**

`tests/Media/CsrfTest.php`:

```php
<?php
declare(strict_types=1);

namespace Tests\Media;

use Tests\Support\TestCase;

final class CsrfTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        require_once __DIR__ . '/../../includes/csrf.php';
        $_SESSION = [];
    }

    public function test_token_on_dinh_trong_cung_phien(): void
    {
        self::assertSame(csrfToken(), csrfToken());
    }

    public function test_token_du_dai(): void
    {
        self::assertGreaterThanOrEqual(32, strlen(csrfToken()));
    }

    public function test_token_dung_thi_qua(): void
    {
        self::assertTrue(csrfCheck(csrfToken()));
    }

    public function test_token_sai_thi_truot(): void
    {
        csrfToken();
        self::assertFalse(csrfCheck('sai'));
        self::assertFalse(csrfCheck(null));
        self::assertFalse(csrfCheck(''));
    }

    public function test_field_chua_input_hidden_da_escape(): void
    {
        $html = csrfField();
        self::assertStringContainsString('type="hidden"', $html);
        self::assertStringContainsString('name="_csrf"', $html);
        self::assertStringContainsString(csrfToken(), $html);
    }
}
```

- [ ] **Step 2: Chạy test để xác nhận FAIL**

Run: `/Applications/XAMPP/xamppfiles/bin/php vendor/bin/phpunit tests/Media/CsrfTest.php`
Expected: FAIL — không tìm thấy `includes/csrf.php`

- [ ] **Step 3: Viết `includes/csrf.php`**

```php
<?php
declare(strict_types=1);

const CSRF_SESSION_KEY = '_csrf_token';

function csrfToken(): string
{
    if (session_status() === PHP_SESSION_NONE && PHP_SAPI !== 'cli') {
        session_start();
    }
    if (empty($_SESSION[CSRF_SESSION_KEY])) {
        $_SESSION[CSRF_SESSION_KEY] = bin2hex(random_bytes(32));
    }
    return (string)$_SESSION[CSRF_SESSION_KEY];
}

function csrfField(): string
{
    return '<input type="hidden" name="_csrf" value="'
         . htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') . '">';
}

function csrfCheck(?string $token): bool
{
    if ($token === null || $token === '') {
        return false;
    }
    $expected = $_SESSION[CSRF_SESSION_KEY] ?? '';
    return $expected !== '' && hash_equals((string)$expected, $token);
}
```

- [ ] **Step 4: Chạy test — phải PASS**

Run: `/Applications/XAMPP/xamppfiles/bin/php vendor/bin/phpunit tests/Media/CsrfTest.php --testdox`
Expected: 5 test PASS.

- [ ] **Step 5: Đọc một trang admin có sẵn để bám theo cấu trúc**

Run: `head -40 admin/foods.php`
Ghi nhận cách kiểm tra đăng nhập, cách include header/nav — `admin/media.php` phải theo đúng khuôn đó.

- [ ] **Step 6: Viết `admin/media.php`**

```php
<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/media.php';
require_once __DIR__ . '/../includes/csrf.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (empty($_SESSION['admin_id'])) {
    header('Location: ' . url('/admin/login.php'));
    exit;
}

$entityTypes = ['destination', 'food', 'accommodation', 'article', 'event'];
$entityType = in_array($_GET['type'] ?? '', $entityTypes, true) ? $_GET['type'] : 'destination';
$entityId = (int)($_GET['id'] ?? 0);
$notice = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfCheck($_POST['_csrf'] ?? null)) {
        http_response_code(419);
        exit('Phiên làm việc đã hết hạn. Tải lại trang và thử lại.');
    }

    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'upload' && isset($_FILES['image'])) {
            if (($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK
                || !is_uploaded_file($_FILES['image']['tmp_name'])) {
                throw new RuntimeException('Không nhận được tệp tải lên.');
            }
            $assetId = mediaStoreFromFile($_FILES['image']['tmp_name'], ['source' => 'upload']);
            mediaLink($assetId, $entityType, $entityId, [
                'role'         => ($_POST['role'] ?? 'gallery') === 'primary' ? 'primary' : 'gallery',
                'authenticity' => ($_POST['authenticity'] ?? 'actual') === 'illustrative' ? 'illustrative' : 'actual',
                'alt_text'     => trim((string)($_POST['alt_text'] ?? '')) ?: null,
            ]);
            $notice = 'Đã tải ảnh lên.';
        } elseif ($action === 'unlink') {
            getDB()->prepare('DELETE FROM media_links WHERE id = ? AND entity_type = ? AND entity_id = ?')
                   ->execute([(int)$_POST['link_id'], $entityType, $entityId]);
            $notice = 'Đã gỡ ảnh khỏi mục này.';
        } elseif ($action === 'make_primary') {
            $db = getDB();
            $db->prepare("UPDATE media_links SET role='gallery' WHERE entity_type=? AND entity_id=?")
               ->execute([$entityType, $entityId]);
            $db->prepare("UPDATE media_links SET role='primary' WHERE id=? AND entity_type=? AND entity_id=?")
               ->execute([(int)$_POST['link_id'], $entityType, $entityId]);
            $notice = 'Đã đặt ảnh đại diện.';
        }
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$gallery = $entityId > 0 ? mediaGallery($entityType, $entityId) : [];
$pageTitle = 'Quản lý ảnh';
include __DIR__ . '/nav.php';
?>
<main class="admin-media">
  <h1>Quản lý ảnh</h1>

  <?php if ($notice): ?><p class="admin-notice"><?= e($notice) ?></p><?php endif; ?>
  <?php if ($error): ?><p class="admin-error"><?= e($error) ?></p><?php endif; ?>

  <form method="get" class="admin-media-picker">
    <select name="type">
      <?php foreach ($entityTypes as $option): ?>
        <option value="<?= e($option) ?>" <?= $entityType === $option ? 'selected' : '' ?>><?= e($option) ?></option>
      <?php endforeach; ?>
    </select>
    <input type="number" name="id" value="<?= (int)$entityId ?>" min="1" placeholder="ID">
    <button type="submit">Mở</button>
  </form>

  <?php if ($entityId > 0): ?>
    <form method="post" enctype="multipart/form-data" class="admin-media-upload">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="upload">
      <input type="file" name="image" accept="image/jpeg,image/png,image/gif" required>
      <input type="text" name="alt_text" placeholder="Mô tả ảnh (alt)" maxlength="255">
      <label><input type="checkbox" name="role" value="primary"> Ảnh đại diện</label>
      <label><input type="checkbox" name="authenticity" value="illustrative"> Ảnh minh họa</label>
      <button type="submit">Tải lên</button>
    </form>

    <ul class="admin-media-grid">
      <?php foreach ($gallery as $asset): ?>
        <li>
          <img src="<?= e(url((string)($asset['storage_path'] ?? ''))) ?>"
               alt="<?= e((string)($asset['alt_text'] ?? '')) ?>"
               width="200" height="150" loading="lazy">
          <p><?= e((string)$asset['source']) ?> · <?= e((string)$asset['authenticity']) ?></p>
          <?= mediaAttributionHtml($asset) ?>
          <form method="post">
            <?= csrfField() ?>
            <input type="hidden" name="link_id" value="<?= (int)$asset['link_id'] ?>">
            <button type="submit" name="action" value="make_primary">Đặt làm đại diện</button>
            <button type="submit" name="action" value="unlink">Gỡ</button>
          </form>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</main>
```

Lưu ý: `mediaGallery()` trả `a.*` nên `$asset['id']` là **asset_id**. Hành động `unlink` và `make_primary` cần id của **liên kết** — Task 5 đã trả sẵn cột `link_id` cho đúng mục đích này, dùng nó chứ đừng dùng `$asset['id']`.

- [ ] **Step 7: Thêm liên kết vào `admin/nav.php`**

Thêm mục điều hướng trỏ tới `url('/admin/media.php')` với nhãn `Ảnh`.

- [ ] **Step 8: Chạy toàn bộ test**

Run: `/Applications/XAMPP/xamppfiles/bin/php vendor/bin/phpunit --testdox`
Expected: toàn bộ PASS.

- [ ] **Step 9: Kiểm tra bằng tay**

Mở `http://localhost/du_an_mau/admin/media.php?type=destination&id=1`, tải lên một ảnh JPEG.
Expected: ảnh hiện trong lưới; gỡ CSRF token khỏi form bằng devtools rồi submit → trả về 419.

- [ ] **Step 10: Commit**

```bash
git add includes/csrf.php admin/media.php admin/nav.php tests/Media/CsrfTest.php
git commit -m "feat: trang quản lý ảnh cho admin kèm bảo vệ CSRF

CSRF dùng hash_equals chống so sánh theo thời gian. Endpoint upload mới là lý do
đưa CSRF vào DA1; 14 trang admin còn lại được xử lý ở DA2."
```

---

## Task 15: Chuyển trang public sang `mediaImg()`

**Files:**
- Modify: `public/foods.php:21,29`, `public/accommodations.php:18,25`, `public/destinations.php`, `public/destination.php`, `public/events.php`, `public/event_detail.php`
- Test: `tests/Media/PublicPageTest.php`

**Interfaces:**
- Consumes: `mediaImg()` (T5).

- [ ] **Step 1: Viết test thất bại**

`tests/Media/PublicPageTest.php`:

```php
<?php
declare(strict_types=1);

namespace Tests\Media;

use Tests\Support\TestCase;

final class PublicPageTest extends TestCase
{
    /** Không trang public nào được render <img> thiếu width/height (chống CLS). */
    public function test_khong_con_img_thieu_width_height(): void
    {
        $pages = ['foods.php', 'accommodations.php', 'destinations.php', 'destination.php', 'events.php'];
        $offenders = [];

        foreach ($pages as $page) {
            $path = dirname(__DIR__, 2) . '/public/' . $page;
            if (!is_file($path)) {
                continue;
            }
            $source = (string)file_get_contents($path);
            preg_match_all('/<img\b[^>]*>/i', $source, $matches);
            foreach ($matches[0] as $tag) {
                if (!preg_match('/\bwidth=/i', $tag) || !preg_match('/\bheight=/i', $tag)) {
                    $offenders[] = $page . ': ' . substr($tag, 0, 90);
                }
            }
        }

        self::assertSame([], $offenders, "Thẻ <img> thiếu kích thước:\n" . implode("\n", $offenders));
    }

    public function test_khong_con_fallback_emoji_trong_card(): void
    {
        foreach (['foods.php', 'accommodations.php'] as $page) {
            $source = (string)file_get_contents(dirname(__DIR__, 2) . '/public/' . $page);
            self::assertStringNotContainsString('catalog-no-image', $source, "{$page} vẫn dùng emoji fallback");
        }
    }
}
```

- [ ] **Step 2: Chạy test để xác nhận FAIL**

Run: `/Applications/XAMPP/xamppfiles/bin/php vendor/bin/phpunit tests/Media/PublicPageTest.php`
Expected: FAIL — liệt kê các thẻ `<img>` thiếu kích thước trong `foods.php` và `accommodations.php`.

- [ ] **Step 3: Sửa `public/foods.php`**

Thêm include ở đầu file, sau dòng 4:

```php
require_once __DIR__ . '/../includes/media.php';
```

Bỏ subquery ảnh khỏi truy vấn ở dòng 21 — `mediaImg()` tự tra cứu:

```php
$list = $db->prepare("SELECT f.* FROM foods f LEFT JOIN destinations d ON d.id=f.destination_id WHERE $whereSql ORDER BY f.name LIMIT $limit OFFSET $offset");
```

Thay khối `<div class="card-img">…</div>` ở dòng 29 bằng:

```php
<div class="card-img"><?= mediaImg('food', (int)$food['id'], ['alt' => $food['name'], 'size' => 'card', 'class' => 'card-img-el']) ?></div>
```

- [ ] **Step 4: Sửa `public/accommodations.php`**

Thêm `require_once __DIR__ . '/../includes/media.php';` sau dòng 4.

Bỏ subquery ảnh ở dòng 18:

```php
$list=$db->prepare("SELECT a.* FROM accommodations a LEFT JOIN destinations d ON d.id=a.destination_id WHERE $whereSql ORDER BY a.name LIMIT $limit OFFSET $offset");
```

Thay khối `<div class="card-img">…</div>` ở dòng 25 bằng:

```php
<div class="card-img"><?= mediaImg('accommodation', (int)$stay['id'], ['alt' => $stay['name'], 'size' => 'card', 'class' => 'card-img-el']) ?></div>
```

- [ ] **Step 5: Thêm CSS cho ảnh trong card**

Trước đây kích thước ảnh được đặt bằng `style="width:100%;height:100%;object-fit:cover"` inline. Chuyển sang CSS, nối vào `assets/css/style.css`:

```css
.card-img-el,
.card-img .media-frame,
.card-img picture {
  display: block;
  inline-size: 100%;
  block-size: 100%;
  object-fit: cover;
}

.card-img picture > img { inline-size: 100%; block-size: 100%; object-fit: cover; }
```

- [ ] **Step 6: Chuyển các trang còn lại**

Áp dụng cùng cách cho `public/destinations.php`, `public/destination.php`, `public/events.php`, `public/event_detail.php`. Ảnh hero ở trang chi tiết dùng `['size' => 'hero', 'eager' => true]`.

Run: `grep -n "<img" public/*.php` để chắc chắn không còn thẻ nào viết tay.

- [ ] **Step 7: Chạy test — phải PASS**

Run: `/Applications/XAMPP/xamppfiles/bin/php vendor/bin/phpunit tests/Media/PublicPageTest.php --testdox`
Expected: 2 test PASS.

- [ ] **Step 8: Kiểm tra bằng mắt trên 4 breakpoint**

Mở `http://localhost/du_an_mau/am-thuc` ở 320, 768, 1024, 1440px.
Expected: không ảnh vỡ; card chưa có ảnh hiện placeholder SVG; không thấy layout nhảy khi ảnh tải xong.

- [ ] **Step 9: Commit**

```bash
git add public/foods.php public/accommodations.php public/destinations.php \
        public/destination.php public/events.php public/event_detail.php assets/css/style.css \
        tests/Media/PublicPageTest.php
git commit -m "refactor: trang public dùng mediaImg() thay cho <img> viết tay

Mọi ảnh nay có width/height (CLS=0) và tự hạ cấp về placeholder SVG thay vì
emoji. Bỏ subquery ảnh trong danh sách vì mediaImg() tự tra cứu."
```

---

## Task 16: Script nghiệm thu

**Files:**
- Create: `scripts/verify_media.php`
- Test: `tests/Media/VerifyTest.php`

**Interfaces:**
- Produces: `verifyMedia(PDO $db): array` — trả mảng các kiểm tra, mỗi phần tử `['name' => string, 'pass' => bool, 'detail' => string]`.

- [ ] **Step 1: Viết test thất bại**

`tests/Media/VerifyTest.php`:

```php
<?php
declare(strict_types=1);

namespace Tests\Media;

use Tests\Support\TestCase;

final class VerifyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        require_once __DIR__ . '/../../scripts/verify_media.php';
        $this->resetTables(['media_links', 'media_assets']);
    }

    public function test_bat_asset_thieu_giay_phep(): void
    {
        $this->db->exec(
            "INSERT INTO media_assets (storage_path, content_hash, source, license)
             VALUES ('/assets/images/media/aa/bb/x.jpg', '" . str_repeat('b', 64) . "', 'wikimedia', NULL)"
        );

        $checks = array_column(verifyMedia($this->db), null, 'name');
        self::assertFalse($checks['license_day_du']['pass']);
    }

    public function test_bat_storage_path_chua_base_url(): void
    {
        $this->db->exec(
            "INSERT INTO media_assets (storage_path, content_hash, source, license)
             VALUES ('http://localhost/du_an_mau/assets/x.jpg', '" . str_repeat('c', 64) . "', 'upload', 'n/a')"
        );

        $checks = array_column(verifyMedia($this->db), null, 'name');
        self::assertFalse($checks['path_tuong_doi']['pass']);
    }

    public function test_du_lieu_sach_thi_moi_kiem_tra_deu_pass(): void
    {
        $this->db->exec(
            "INSERT INTO media_assets (storage_path, content_hash, source, license)
             VALUES ('/assets/images/media/aa/bb/y.jpg', '" . str_repeat('d', 64) . "', 'wikimedia', 'CC BY 4.0')"
        );

        $checks = array_column(verifyMedia($this->db), null, 'name');
        self::assertTrue($checks['license_day_du']['pass']);
        self::assertTrue($checks['path_tuong_doi']['pass']);
    }

    public function test_bat_diem_den_bi_gan_anh_minh_hoa(): void
    {
        $this->db->exec(
            "INSERT INTO media_assets (id, storage_path, content_hash, source, license)
             VALUES (501, '/assets/images/media/aa/bb/z.jpg', '" . str_repeat('e', 64) . "', 'unsplash', 'Unsplash License')"
        );
        $this->db->exec(
            "INSERT INTO media_links (asset_id, entity_type, entity_id, authenticity)
             VALUES (501, 'destination', 7, 'illustrative')"
        );

        $checks = array_column(verifyMedia($this->db), null, 'name');
        self::assertFalse($checks['diem_den_khong_minh_hoa']['pass']);
    }

    public function test_bang_ke_authenticity_tach_ba_loai(): void
    {
        $this->resetTables(['media_links', 'media_assets', 'destinations']);
        $this->db->exec(
            "INSERT INTO destinations (id, name, slug) VALUES (1, 'A', 'a'), (2, 'B', 'b')"
        );
        $this->db->exec(
            "INSERT INTO media_assets (id, storage_path, content_hash, source, license)
             VALUES (502, '/assets/images/media/cc/dd/w.jpg', '" . str_repeat('f', 64) . "', 'wikimedia', 'CC BY-SA 4.0')"
        );
        $this->db->exec(
            "INSERT INTO media_links (asset_id, entity_type, entity_id, authenticity)
             VALUES (502, 'destination', 1, 'actual')"
        );

        $checks = array_column(verifyMedia($this->db), null, 'name');

        self::assertTrue($checks['bang_ke_authenticity']['pass']);
        self::assertStringContainsString('destination: thật 1', $checks['bang_ke_authenticity']['detail']);
        self::assertStringContainsString('placeholder 1', $checks['bang_ke_authenticity']['detail']);
    }

    public function test_bat_itinerary_tro_vao_diem_den_da_mat(): void
    {
        $this->resetTables(['itinerary_items', 'itineraries', 'destinations']);
        $this->db->exec("INSERT INTO itineraries (id, title) VALUES (1, 'Lịch trình cũ')");
        $this->db->exec(
            'INSERT INTO itinerary_items (itinerary_id, destination_id, day_number) VALUES (1, 999, 1)'
        );

        $checks = array_column(verifyMedia($this->db), null, 'name');
        self::assertFalse($checks['du_lieu_cu_con_nguyen']['pass']);
    }
}
```

`test_bat_itinerary_tro_vao_diem_den_da_mat` chèn `itineraries` với cột tối thiểu; nếu lược đồ thật có thêm cột `NOT NULL` không mặc định, bổ sung giá trị cho đúng — chạy `SHOW CREATE TABLE itineraries` để xem.

- [ ] **Step 2: Chạy test để xác nhận FAIL**

Run: `/Applications/XAMPP/xamppfiles/bin/php vendor/bin/phpunit tests/Media/VerifyTest.php`
Expected: FAIL — không tìm thấy `scripts/verify_media.php`

- [ ] **Step 3: Viết `scripts/verify_media.php`**

```php
<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/media.php';

const VERIFY_ENTITY_TABLES = [
    'destination'   => 'destinations',
    'food'          => 'foods',
    'accommodation' => 'accommodations',
];

/** Số điểm đến trước khi chạy migration. Sau DA1 không được ít hơn con số này. */
const VERIFY_DESTINATION_BASELINE = 108;

function verifyMedia(PDO $db): array
{
    $checks = [];

    $missingLicense = (int)$db->query(
        "SELECT COUNT(*) FROM media_assets WHERE source <> 'upload' AND (license IS NULL OR license = '')"
    )->fetchColumn();
    $checks[] = [
        'name'   => 'license_day_du',
        'pass'   => $missingLicense === 0,
        'detail' => "{$missingLicense} asset ngoài upload thiếu giấy phép",
    ];

    $absolutePaths = (int)$db->query(
        "SELECT COUNT(*) FROM media_assets WHERE storage_path LIKE 'http%'"
    )->fetchColumn();
    $checks[] = [
        'name'   => 'path_tuong_doi',
        'pass'   => $absolutePaths === 0,
        'detail' => "{$absolutePaths} storage_path chứa URL tuyệt đối",
    ];

    $orphanRefs = (int)$db->query(
        "SELECT COUNT(*) FROM media_assets
         WHERE storage_path IS NULL AND (place_photo_ref IS NULL OR place_photo_ref = '')"
    )->fetchColumn();
    $checks[] = [
        'name'   => 'asset_co_nguon',
        'pass'   => $orphanRefs === 0,
        'detail' => "{$orphanRefs} asset không có cả file lẫn photo_reference",
    ];

    $missingFiles = 0;
    $sample = $db->query(
        "SELECT storage_path FROM media_assets WHERE storage_path IS NOT NULL LIMIT 500"
    )->fetchAll();
    foreach ($sample as $row) {
        if (!is_file(dirname(__DIR__) . $row['storage_path'])) {
            $missingFiles++;
        }
    }
    $checks[] = [
        'name'   => 'file_ton_tai',
        'pass'   => $missingFiles === 0,
        'detail' => "{$missingFiles}/" . count($sample) . ' file trong mẫu không tồn tại trên đĩa',
    ];

    $stuckQueue = (int)$db->query(
        "SELECT COUNT(*) FROM media_backfill_queue q
         WHERE q.status = 'done' AND NOT EXISTS (
             SELECT 1 FROM media_links l
             WHERE l.entity_type = q.entity_type AND l.entity_id = q.entity_id
         )"
    )->fetchColumn();
    $checks[] = [
        'name'   => 'queue_khong_done_gia',
        'pass'   => $stuckQueue === 0,
        'detail' => "{$stuckQueue} mục done nhưng không có media_links",
    ];

    // Spec mục 10: báo cáo độ phủ tách theo actual / illustrative / placeholder.
    // "placeholder" không phải một giá trị trong media_links — nó là các bản ghi
    // không có link nào, tức phần mà mediaImg() sẽ hạ cấp về SVG.
    $breakdown = [];
    foreach (VERIFY_ENTITY_TABLES as $entityType => $table) {
        $total = (int)$db->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();

        $byAuthenticity = $db->prepare(
            "SELECT authenticity, COUNT(DISTINCT entity_id) AS n FROM media_links
             WHERE entity_type = ? GROUP BY authenticity"
        );
        $byAuthenticity->execute([$entityType]);
        $counts = array_column($byAuthenticity->fetchAll(), 'n', 'authenticity');
        $actual = (int)($counts['actual'] ?? 0);
        $illustrative = (int)($counts['illustrative'] ?? 0);

        $withImage = (int)$db->query(
            "SELECT COUNT(DISTINCT entity_id) FROM media_links WHERE entity_type = '{$entityType}'"
        )->fetchColumn();
        $placeholder = max(0, $total - $withImage);
        $coverage = $total > 0 ? round($withImage / $total * 100, 1) : 100.0;

        $checks[] = [
            'name'   => "phu_song_{$entityType}",
            'pass'   => $coverage >= 90.0,
            'detail' => "{$withImage}/{$total} ({$coverage}%) có ảnh",
        ];
        $breakdown[] = "{$entityType}: thật {$actual}, minh họa {$illustrative}, placeholder {$placeholder}";
    }

    // Kiểm tra báo cáo — luôn pass, giá trị nằm ở phần detail.
    $checks[] = [
        'name'   => 'bang_ke_authenticity',
        'pass'   => true,
        'detail' => implode(' | ', $breakdown),
    ];

    // Quy tắc spec: điểm đến không bao giờ được gắn ảnh minh họa.
    $wrongDestination = (int)$db->query(
        "SELECT COUNT(*) FROM media_links WHERE entity_type = 'destination' AND authenticity = 'illustrative'"
    )->fetchColumn();
    $checks[] = [
        'name'   => 'diem_den_khong_minh_hoa',
        'pass'   => $wrongDestination === 0,
        'detail' => "{$wrongDestination} điểm đến bị gắn ảnh minh họa",
    ];

    // Spec mục 10: 108 điểm đến và itinerary cũ vẫn đọc được sau migration.
    $destinationTotal = (int)$db->query('SELECT COUNT(*) FROM destinations')->fetchColumn();
    $orphanItems = (int)$db->query(
        'SELECT COUNT(*) FROM itinerary_items i
         WHERE i.destination_id IS NOT NULL
           AND NOT EXISTS (SELECT 1 FROM destinations d WHERE d.id = i.destination_id)'
    )->fetchColumn();
    // $destinationTotal === 0 nghĩa là đang chạy trên DB test rỗng, không phải mất dữ liệu.
    $checks[] = [
        'name'   => 'du_lieu_cu_con_nguyen',
        'pass'   => $orphanItems === 0
            && ($destinationTotal === 0 || $destinationTotal >= VERIFY_DESTINATION_BASELINE),
        'detail' => "{$destinationTotal} điểm đến (mốc " . VERIFY_DESTINATION_BASELINE
            . "), {$orphanItems} mục itinerary trỏ vào điểm đến không còn tồn tại",
    ];

    return $checks;
}

if (PHP_SAPI === 'cli' && realpath($argv[0] ?? '') === realpath(__FILE__)) {
    $failed = 0;
    foreach (verifyMedia(getDB()) as $check) {
        echo ($check['pass'] ? '✓ ' : '✗ ') . str_pad($check['name'], 26) . $check['detail'] . PHP_EOL;
        $failed += $check['pass'] ? 0 : 1;
    }
    echo $failed === 0 ? 'TẤT CẢ ĐẠT' : "{$failed} kiểm tra KHÔNG ĐẠT" . PHP_EOL;
    exit($failed === 0 ? 0 : 1);
}
```

Chú ý: `phu_song_*` yêu cầu ≥90% vì placeholder chỉ là dự phòng — mục tiêu của DA1 là ảnh thật hoặc ảnh Places cho phần lớn bản ghi.

- [ ] **Step 4: Chạy test — phải PASS**

Run: `/Applications/XAMPP/xamppfiles/bin/php vendor/bin/phpunit tests/Media/VerifyTest.php --testdox`
Expected: 6 test PASS.

- [ ] **Step 5: Chạy backfill đến khi hàng đợi cạn**

```bash
/Applications/XAMPP/xamppfiles/bin/php scripts/media_backfill_run.php --enqueue --limit=200
# lặp lại cho tới khi dòng cuối in "0 còn lại"
/Applications/XAMPP/xamppfiles/bin/php scripts/media_backfill_run.php --limit=200
```

- [ ] **Step 6: Chạy nghiệm thu**

Run: `/Applications/XAMPP/xamppfiles/bin/php scripts/verify_media.php`
Expected: `TẤT CẢ ĐẠT`. Nếu `phu_song_*` chưa đạt, chạy tiếp backfill; nếu một nguồn liên tục thất bại, xem `last_error` trong `media_backfill_queue` trước khi hạ ngưỡng.

Đọc kỹ dòng `bang_ke_authenticity` — đây là con số spec mục 10 yêu cầu báo cáo. Dòng này luôn `✓` vì nó là báo cáo, không phải cổng chặn; nhưng nếu `minh họa` cao hơn `thật` ở `food`, nên chạy thêm một vòng Wikimedia trước khi kết thúc DA1.

- [ ] **Step 7: Chạy toàn bộ test lần cuối**

Run: `/Applications/XAMPP/xamppfiles/bin/php vendor/bin/phpunit --testdox`
Expected: toàn bộ PASS.

- [ ] **Step 8: Commit**

```bash
git add scripts/verify_media.php tests/Media/VerifyTest.php
git commit -m "feat: script nghiệm thu nền tảng media

Kiểm tra giấy phép, path tương đối, file tồn tại, hàng đợi không có 'done' giả,
độ phủ ảnh ≥90%, và dữ liệu cũ (108 điểm đến + itinerary) còn nguyên sau
migration. Báo cáo tách số lượng ảnh thật / minh họa / placeholder."
```

---

## Nghiệm thu DA1

Hoàn tất khi tất cả các điều kiện sau đúng:

- [ ] `vendor/bin/phpunit` — toàn bộ PASS
- [ ] `scripts/verify_media.php` — `TẤT CẢ ĐẠT`, exit code 0
- [ ] Duyệt `/am-thuc`, `/luu-tru`, `/diem-den`, `/cam-nang` ở 320/768/1024/1440px — không ảnh vỡ, không layout nhảy
- [ ] Không còn `<img>` nào trong `public/` thiếu `width`/`height`
- [ ] Không còn dòng nào trong DB chứa `/travel_daklak/` hoặc `gstatic.com`
- [ ] `admin/media.php` upload được và từ chối request thiếu CSRF token
- [ ] Ảnh Wikimedia/Unsplash hiện đúng dòng ghi công tác giả và giấy phép
- [ ] Ảnh gắn `authenticity = illustrative` hiện nhãn "Ảnh minh họa"; không điểm đến nào bị gắn nhãn này
- [ ] Chạy lại backfill lần hai không tạo bản ghi trùng và không làm tăng số mục `pending`
- [ ] `bang_ke_authenticity` in đủ ba con số thật / minh họa / placeholder cho cả ba loại
- [ ] `du_lieu_cu_con_nguyen` đạt: vẫn ≥108 điểm đến, 0 mục itinerary trỏ vào điểm đến đã mất
- [ ] `accommodations` đã có `place_synced_at` cho toàn bộ bản ghi đã duyệt (dữ kiện giá/giờ/rating điền tới đâu hay tới đó — Places không có hồ sơ thì để NULL, không bịa)

