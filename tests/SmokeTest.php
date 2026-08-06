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
     * Cố định tổng số bảng của lược đồ nền, không chỉ kiểm tra một bảng lẻ.
     * `testImportSchema()` trong bootstrap.php bỏ qua statement lỗi một cách
     * im lặng có chủ đích (chỉ thị riêng của mysqldump); nếu về sau regex
     * tách statement xử lý sai một CREATE TABLE (literal chứa ";\n", trigger,
     * stored routine...), lược đồ sẽ nạp thiếu mà không gì báo động — trừ
     * test này.
     *
     * Con số 20 lấy từ `grep -c '^CREATE TABLE' database/daklak_travel.sql`
     * tại thời điểm viết test (Task 1, 2026-08-06), khớp với số bảng thực tế
     * trong `daklak_travel_test` sau khi nạp. Cần cập nhật con số này khi
     * `database/daklak_travel.sql` thêm/bớt bảng trong bản dump nền — KHÔNG
     * cập nhật khi Task 2 trở đi thêm bảng qua `database/migrations/*.sql`
     * (foods, accommodations, destination_images, food_images,
     * accommodation_images, search_documents, events...), vì đó là schema do
     * migration runner tạo, ngoài phạm vi hàm này.
     */
    public function test_tong_so_bang_luoc_do_nen_dung_20(): void
    {
        $count = (int)$this->db->query(
            'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE()'
        )->fetchColumn();
        self::assertSame(20, $count);
    }

    /** BASE_URL phải cố định, không phụ thuộc vào việc gọi phpunit từ đâu. */
    public function test_base_url_xac_dinh_duoc_khi_chay_cli(): void
    {
        require_once dirname(__DIR__) . '/includes/functions.php';
        self::assertSame('http://localhost/du_an_mau', BASE_URL);
        self::assertSame('http://localhost/du_an_mau/am-thuc', url('/am-thuc'));
    }
}
