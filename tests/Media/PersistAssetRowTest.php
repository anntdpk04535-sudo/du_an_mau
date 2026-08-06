<?php
declare(strict_types=1);

namespace Tests\Media;

use Tests\Support\TestCase;

/**
 * F1 (review vòng 1, Critical) — race giữa hai tiến trình cùng content_hash:
 * cả hai vượt qua kiểm tra "hash đã tồn tại?" gần như đồng thời (trước khi
 * bên nào COMMIT), cả hai copy file + sinh derivative, cả hai INSERT — bên
 * thua đụng UNIQUE KEY uq_media_hash. Code CŨ bắt Throwable ở đúng catch bao
 * quanh INSERT và XOÁ VĨNH VIỄN các file vừa ghi — nhưng vì đường dẫn suy ra
 * thuần tuý từ content_hash, file "vừa ghi" của bên THUA trùng hệt file của
 * bên THẮNG. Hàng DB của bên thắng sống sót nhưng trỏ vào file đã bị xoá, và
 * không bao giờ tự chữa (mọi lần gọi sau đều trúng nhánh dedupe, trả id cũ,
 * không tạo lại file).
 *
 * Không cần hai tiến trình thật để tái hiện đúng CÁI LÕI của lỗi: bản chất
 * vấn đề là "INSERT đụng UNIQUE KEY có ném ngoại lệ hay không". Test này gọi
 * thẳng hàm mediaPersistAssetRow() — phần lõi DB được tách ra khi sửa F1 —
 * với một hàng ĐÃ CÓ SẴN cùng content_hash (mô phỏng "tiến trình khác đã
 * thắng"), dữ liệu khác (mô phỏng "tiến trình thua" mang storage_path khác).
 * Nếu hàm không ném lỗi và trả về đúng id của hàng đã thắng, tức nhánh xoá
 * file rollback trong mediaStoreFromFile() không bao giờ bị kích hoạt SAI
 * cho trường hợp trùng hash — chỉ còn kích hoạt cho lỗi THẬT.
 *
 * Test này KHÔNG chạm filesystem (đúng ranh giới trách nhiệm của
 * mediaPersistAssetRow(): chỉ lo phần DB) — bất biến "file trên đĩa không bị
 * đụng khi trùng hash" được kiểm ở tests/Media/StoreTest.php
 * (test_trung_hash_khong_dung_den_file_danh_dau_da_co_san), vốn kiểm ở tầng
 * API công khai.
 */
final class PersistAssetRowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        require_once __DIR__ . '/../../includes/media/store.php';
        $this->resetTables(['media_links', 'media_assets']);
    }

    public function test_dua_trung_hash_tra_ve_id_cu_khong_nem_loi_va_khong_tao_hang_moi(): void
    {
        $hash = str_repeat('a1', 32); // 64 ký tự hex hợp lệ (giả sha256)

        $winner = $this->db->prepare(
            'INSERT INTO media_assets (storage_path, content_hash, source) VALUES (?, ?, ?)'
        );
        $winner->execute(['/assets/images/media/a1/a1/' . $hash . '.jpg', $hash, 'upload']);
        $winnerId = (int)$this->db->lastInsertId();

        // "Tiến trình thua" mang dữ liệu KHÁC (storage_path khác) nhưng CÙNG
        // hash — đúng tình huống race: cả hai đã copy file + sinh derivative
        // trước khi biết ai thắng.
        $loserId = mediaPersistAssetRow($this->db, [
            '/assets/images/media/zz/zz/khac-duong-dan.jpg', $hash, 'image/jpeg',
            100, 100, 12345, '400jpg,800jpg,1600jpg',
            'upload', null, null, null, null, null,
        ]);

        self::assertSame($winnerId, $loserId, 'bên thua phải nhận lại đúng id của bên thắng, không phải id mới');
        self::assertSame(
            1,
            (int)$this->db->query(
                'SELECT COUNT(*) FROM media_assets WHERE content_hash = ' . $this->db->quote($hash)
            )->fetchColumn(),
            'race không được tạo ra hàng thứ hai cho cùng một content_hash'
        );

        $row = $this->db->query('SELECT storage_path FROM media_assets WHERE id = ' . $winnerId)->fetch();
        self::assertSame(
            '/assets/images/media/a1/a1/' . $hash . '.jpg',
            $row['storage_path'],
            'storage_path của hàng THẮNG không được bị ghi đè bởi dữ liệu của bên thua'
        );
    }

    public function test_khong_dua_van_chen_hang_moi_binh_thuong(): void
    {
        $hash = str_repeat('b2', 32);

        $id = mediaPersistAssetRow($this->db, [
            '/assets/images/media/b2/b2/' . $hash . '.jpg', $hash, 'image/jpeg',
            100, 100, 12345, '400jpg',
            'upload', null, null, null, null, null,
        ]);

        self::assertGreaterThan(0, $id);
        self::assertSame(
            1,
            (int)$this->db->query(
                'SELECT COUNT(*) FROM media_assets WHERE content_hash = ' . $this->db->quote($hash)
            )->fetchColumn()
        );
    }
}
