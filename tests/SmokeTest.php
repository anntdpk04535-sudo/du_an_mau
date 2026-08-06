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

    /**
     * Bảo vệ đúng bất biến: mọi bảng `CREATE TABLE` trong bản dump nền
     * (`database/daklak_travel.sql`) phải có mặt trong DB test sau khi nạp.
     *
     * KHÔNG đếm tổng số bảng — Bước 6 của bootstrap.php áp toàn bộ
     * `database/migrations/*.sql` vào chính DB test này ngay khi
     * `scripts/migrate_media.php` tồn tại (từ Task 2 trở đi), nên tổng số
     * bảng của `daklak_travel_test` sẽ tăng dần theo migration và không
     * phải bất biến. Test này chỉ khẳng định `testImportSchema()` không bỏ
     * sót bảng nào của *dump nền* — đúng lỗ hổng finding gốc nêu (statement
     * lỗi bị nuốt âm thầm) — nên vẫn xanh dù DB test có thêm bao nhiêu bảng
     * từ migration sau này.
     */
    public function test_moi_bang_trong_dump_nen_deu_duoc_nap(): void
    {
        $sql = (string)file_get_contents(dirname(__DIR__) . '/database/daklak_travel.sql');
        preg_match_all('/^CREATE TABLE `([^`]+)`/m', $sql, $matches);
        $expectedTables = $matches[1];

        self::assertNotEmpty(
            $expectedTables,
            'Không rút được tên bảng nào từ dump nền — regex có thể đã hỏng, test sẽ tự vô hiệu nếu bỏ qua điều này.'
        );

        $found = $this->db->prepare(
            'SELECT COUNT(*) FROM information_schema.tables
             WHERE table_schema = DATABASE() AND table_name = ?'
        );

        foreach ($expectedTables as $table) {
            $found->execute([$table]);
            self::assertSame(
                1,
                (int)$found->fetchColumn(),
                "Bảng `{$table}` có trong dump nền nhưng không có trong DB test."
            );
        }
    }

    /** BASE_URL phải cố định, không phụ thuộc vào việc gọi phpunit từ đâu. */
    public function test_base_url_xac_dinh_duoc_khi_chay_cli(): void
    {
        require_once dirname(__DIR__) . '/includes/functions.php';
        self::assertSame('http://localhost/du_an_mau', BASE_URL);
        self::assertSame('http://localhost/du_an_mau/am-thuc', url('/am-thuc'));
    }
}
