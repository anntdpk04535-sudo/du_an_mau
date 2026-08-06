<?php
declare(strict_types=1);

namespace Tests\Support;

use PHPUnit\Framework\TestCase as BaseTestCase;

/**
 * `tests/bootstrap.php` nạp dump nền bằng bộ tách riêng của nó
 * (`preg_split('/;\s*\R/', $sql)` + lọc dòng đầu), yếu hơn bộ quét dùng chung
 * trong `scripts/migrate_media.php`. Hệ quả đã đo được trên dump THẬT: cả 7
 * chỉ thị `/*!40101 ... * /` (bỏ khoảng trắng khi đọc) — trong đó có
 * `SET NAMES utf8mb4` — bị vứt mất hoàn toàn. Mọi task sau đều assert trên
 * nội dung tiếng Việt nên đây là bẫy charset chờ sẵn.
 *
 * Các test dưới đây khoá hành vi của hàm chọn statement, KHÔNG chạm DB.
 */
final class SchemaImportTest extends BaseTestCase
{
    private function dump(): string
    {
        $sql = (string)file_get_contents(__DIR__ . '/../../database/daklak_travel.sql');
        self::assertNotSame('', $sql, 'không đọc được database/daklak_travel.sql');
        return $sql;
    }

    /**
     * `/*!40101 SET NAMES utf8mb4 * /` là mã thi hành thật với MariaDB. Bộ
     * tách cũ coi nó là comment rồi loại bỏ, nên DB test được nạp mà không hề
     * đặt charset kết nối.
     */
    public function test_chi_thi_charset_conditional_comment_khong_bi_vut_mat(): void
    {
        $statements = \testSchemaStatements($this->dump());

        $joined = implode("\n", $statements);
        self::assertStringContainsString(
            'SET NAMES utf8mb4',
            $joined,
            'chỉ thị SET NAMES utf8mb4 phải được giữ lại — mất nó là bẫy charset cho mọi task sau'
        );

        $survived = 0;
        foreach ($statements as $statement) {
            $survived += preg_match_all('~/\*!~', $statement);
        }
        self::assertSame(
            7,
            $survived,
            'cả 7 chỉ thị /*! trong dump nền phải sống sót'
        );
    }

    /**
     * Bất biến sống còn của `testImportSchema()`: nạp CẤU TRÚC, bỏ TOÀN BỘ
     * INSERT — test phải chạy trên lược đồ xác định, không phụ thuộc dữ liệu
     * thật.
     *
     * Đây chính là chỗ một lần thay bộ tách "cho nhanh" sẽ phá: bộ quét dùng
     * chung GIỮ NGUYÊN comment đứng trước statement, nên
     * `preg_match('/^(INSERT|...)/i', $statement)` chạy trên chuỗi THÔ không
     * còn khớp (`-- Dumping data for table ...` đứng chắn phía trước) và 19
     * lệnh INSERT lọt lưới vào DB test. Bộ lọc phải chạy trên bản sao
     * chỉ-chứa-code.
     */
    public function test_khong_mot_lenh_insert_nao_lot_luoi(): void
    {
        $leaked = [];
        foreach (\testSchemaStatements($this->dump()) as $statement) {
            [$codeOnly] = \sqlCodeOnlyView($statement);
            if (preg_match('/^\s*(INSERT|REPLACE|LOCK\s+TABLES|UNLOCK\s+TABLES)\b/i', $codeOnly)) {
                $leaked[] = substr(preg_replace('/\s+/', ' ', trim($codeOnly)) ?? '', 0, 90);
            }
        }

        self::assertSame(
            [],
            $leaked,
            'không lệnh INSERT/REPLACE/LOCK TABLES nào được lọt vào danh sách nạp lược đồ'
        );
    }

    /**
     * Vòng sửa 5 — `testSchemaStatements()` là ĐIỂM GỌI THỨ HAI của đúng cái
     * lớp lỗi vừa đóng trong `statementHasSingleDdlClause()`: một regex neo
     * đầu chuỗi quăng vào đầu ra thô của bộ quét. mysqldump sinh
     * `/*!40000 INSERT ... * /` (bỏ khoảng trắng khi đọc) hoàn toàn hợp lệ,
     * và với gate đứng chắn phía trước thì `/^\s*INSERT\b/i` không khớp — dữ
     * liệu THẬT lọt thẳng vào DB test, phá đúng bất biến "nạp cấu trúc,
     * không nạp dữ liệu" mà hàm này tồn tại để bảo vệ.
     *
     * Cách chữa giống hệt: lọc trên bản CHÍNH TẮC do `sqlClassifierView()`
     * dựng, chứ không trên bản chỉ-chứa-code thô.
     */
    public function test_insert_boc_trong_gate_phien_ban_khong_lot_luoi(): void
    {
        $sql = "CREATE TABLE `t` (`id` int);\n"
             . "/*!40000 INSERT INTO `t` VALUES (1) */;\n"
             . "/*M!40000 INSERT INTO `t` VALUES (2) */;\n"
             . "  /*!40000 LOCK TABLES `t` WRITE */;\n";

        $joined = implode("\n", \testSchemaStatements($sql));

        self::assertStringNotContainsString(
            'INSERT INTO',
            $joined,
            'INSERT bọc trong gate /*! vẫn phải bị loại — dữ liệu thật không được vào DB test'
        );
        self::assertStringNotContainsString(
            'LOCK TABLES',
            $joined,
            'LOCK TABLES bọc trong gate /*! vẫn phải bị loại'
        );
        self::assertStringContainsString(
            'CREATE TABLE',
            $joined,
            'phần cấu trúc vẫn phải được giữ lại'
        );
    }

    /**
     * Bộ tách dùng chung phải nhận diện được các statement DDL thật trong
     * dump — canary chống việc "lọc quá tay" làm mất luôn CREATE TABLE.
     */
    public function test_van_giu_du_cac_lenh_tao_bang(): void
    {
        $createCount = 0;
        foreach (\testSchemaStatements($this->dump()) as $statement) {
            [$codeOnly] = \sqlCodeOnlyView($statement);
            if (preg_match('/^\s*CREATE\s+TABLE\b/i', $codeOnly)) {
                $createCount++;
            }
        }

        self::assertGreaterThanOrEqual(
            20,
            $createCount,
            'dump nền phải còn đủ các lệnh CREATE TABLE sau khi lọc'
        );
    }
}
