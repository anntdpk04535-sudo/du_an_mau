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
