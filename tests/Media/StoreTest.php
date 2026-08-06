<?php
declare(strict_types=1);

namespace Tests\Media;

use RuntimeException;
use Tests\Support\TestCase;

final class StoreTest extends TestCase
{
    private string $fixture;
    private string $fixtureHash;

    protected function setUp(): void
    {
        parent::setUp();
        require_once __DIR__ . '/../../includes/media/store.php';
        $this->resetTables(['media_links', 'media_assets']);

        // 1800x1200: rộng hơn width lớn nhất trong MEDIA_DERIVATIVE_WIDTHS
        // (1600) để cả ba variant 400/800/1600 đều thực sự được sinh ra.
        // mediaGenerateDerivatives() bỏ qua mọi width >= chiều rộng ảnh gốc,
        // nên một fixture chỉ 1200px sẽ không bao giờ sinh variant 1600 —
        // đúng như ảnh thật từ Wikimedia/Unsplash luôn rộng hơn 1600px.
        $this->fixture = sys_get_temp_dir() . '/media_fixture.jpg';
        $image = imagecreatetruecolor(1800, 1200);
        imagefill($image, 0, 0, imagecolorallocate($image, 30, 90, 60));
        imagejpeg($image, $this->fixture, 90);
        imagedestroy($image);
        $this->fixtureHash = hash_file('sha256', $this->fixture);

        // F7 (review vòng 1): fixture tất định -> hash cố định -> thư mục
        // shard cố định trên đĩa. resetTables() chỉ TRUNCATE bảng, không đụng
        // filesystem — nếu không dọn ở đây, assertFileExists() ở lần chạy
        // SAU có thể xanh giả nhờ file sót lại từ lần chạy TRƯỚC, che mất một
        // derivative sinh hỏng thật trong lần chạy hiện tại.
        $this->clearFixtureShard();
    }

    private function clearFixtureShard(): void
    {
        $shardDir = dirname(mediaAbsolutePath(mediaRelativePath($this->fixtureHash, 'jpg')));
        if (!is_dir($shardDir)) {
            return;
        }
        foreach (glob($shardDir . '/' . $this->fixtureHash . '*') ?: [] as $file) {
            @unlink($file);
        }
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
        self::assertSame(1800, (int)$row['width']);
        self::assertSame(1200, (int)$row['height']);
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

    /**
     * F2 (review vòng 1, Critical): shell_exec() trả về STDOUT chứ không
     * phải mã thoát — `cwebp -quiet` không in gì ra stdout nên
     * (bool)shell_exec(...) luôn là false dù file .webp được cwebp tạo ra
     * THÀNH CÔNG thật trên đĩa. Hệ quả cũ: file .webp mồ côi trên đĩa,
     * không có mặt trong cột variants, không bao giờ được dọn bởi rollback.
     *
     * Bất biến đúng: MỌI file .webp có mặt trên đĩa phải được liệt kê trong
     * variants, và ngược lại — phát biểu theo kiểu "khớp nhau" (không đòi
     * WebP phải luôn tồn tại), vì WebP vẫn là tuỳ chọn tuỳ theo máy có
     * imagewebp()/cwebp hay không.
     */
    public function test_moi_file_webp_tren_dia_deu_khop_voi_cot_variants(): void
    {
        $id = mediaStoreFromFile($this->fixture, ['source' => 'upload']);
        $row = $this->db->query("SELECT variants, content_hash FROM media_assets WHERE id = {$id}")->fetch();
        $variants = explode(',', (string)$row['variants']);

        foreach ([400, 800, 1600] as $width) {
            $webpAbsolute = dirname(__DIR__, 2) . mediaRelativePath($row['content_hash'], 'webp', $width);
            $existsOnDisk = is_file($webpAbsolute);
            $listedInVariants = in_array($width . 'webp', $variants, true);

            self::assertSame(
                $existsOnDisk,
                $listedInVariants,
                "variant {$width}webp: trên đĩa=" . var_export($existsOnDisk, true)
                    . " nhưng trong variants=" . var_export($listedInVariants, true)
            );
        }
    }

    public function test_tu_choi_nguon_ngoai_upload_ma_thieu_license(): void
    {
        $this->expectException(RuntimeException::class);
        // F8 (review vòng 1): expectException(RuntimeException::class) một
        // mình vẫn xanh nếu code ném lỗi vì lý do KHÁC (vd. thiếu
        // meta[source], hoặc — sau F10 — source không nằm trong whitelist).
        // Khoá thêm nội dung thông điệp để test thực sự xác nhận đúng nhánh
        // "thiếu license", không phải bất kỳ nhánh nào khác cũng ném
        // RuntimeException.
        $this->expectExceptionMessage('license');
        mediaStoreFromFile($this->fixture, ['source' => 'wikimedia']);
    }

    /**
     * F10 (review vòng 1): `source` không nằm trong enum của cột DB phải bị
     * chặn NGAY TẠI BIÊN (validate input tại boundary), trước khi có bất kỳ
     * I/O nào — không được để rơi xuống tận INSERT rồi vỡ ở tầng DB (lúc đó
     * file trên đĩa đã bị ghi, phải rollback tốn công thay vì fail sớm).
     */
    public function test_tu_choi_source_khong_nam_trong_danh_sach_cho_phep(): void
    {
        $this->expectException(RuntimeException::class);
        mediaStoreFromFile($this->fixture, ['source' => 'not_a_real_source']);
    }

    /**
     * F1 (review vòng 1, Critical) — bài kiểm bất biến ở tầng API công khai:
     * chèn sẵn một hàng có cùng content_hash (mô phỏng "tiến trình khác đã
     * thắng và commit trước"), đặt một file ĐÁNH DẤU (không phải ảnh thật)
     * tại đúng đường dẫn mà content_hash đó suy ra. mediaStoreFromFile() coi
     * đây là "đã tồn tại" và phải trả về id cũ; file đánh dấu phải còn
     * NGUYÊN VẸN, không bị ghi đè lẫn không bị dọn bởi bất kỳ nhánh rollback
     * nào.
     *
     * Lưu ý minh bạch: bài test này đi qua nhánh fast-path SELECT (đã có sẵn
     * từ code gốc và CHƯA BAO GIỜ lỗi), nên nó KHÔNG phải bài RED/GREEN phân
     * biệt code cũ/mới cho F1 — nó là một bài kiểm bất biến end-to-end bổ
     * sung. Bài kiểm phân biệt thật cho cơ chế sửa F1 (INSERT ... ON
     * DUPLICATE KEY UPDATE) nằm ở tests/Media/PersistAssetRowTest.php, nơi
     * duy nhất có thể ép được đúng tình huống "cả hai bên đã vượt qua kiểm
     * tra tồn tại trước khi bên nào commit".
     */
    public function test_trung_hash_khong_dung_den_file_danh_dau_da_co_san(): void
    {
        $storagePath = mediaRelativePath($this->fixtureHash, 'jpg');
        $insert = $this->db->prepare(
            'INSERT INTO media_assets (storage_path, content_hash, source) VALUES (?, ?, ?)'
        );
        $insert->execute([$storagePath, $this->fixtureHash, 'upload']);
        $winnerId = (int)$this->db->lastInsertId();

        $markerAbsolute = dirname(__DIR__, 2) . $storagePath;
        if (!is_dir(dirname($markerAbsolute))) {
            mkdir(dirname($markerAbsolute), 0755, true);
        }
        file_put_contents($markerAbsolute, 'MARKER-KHONG-DUOC-DUNG-TOI');

        $returnedId = mediaStoreFromFile($this->fixture, ['source' => 'upload']);

        self::assertSame($winnerId, $returnedId, 'phải trả về id của hàng đã tồn tại, không tạo hàng mới');
        self::assertSame(
            1,
            (int)$this->db->query('SELECT COUNT(*) FROM media_assets')->fetchColumn(),
            'không được tạo thêm hàng thứ hai'
        );
        self::assertSame(
            'MARKER-KHONG-DUOC-DUNG-TOI',
            file_get_contents($markerAbsolute),
            'file đánh dấu phải còn nguyên — dedupe theo hash không được ghi đè/xoá file đã có'
        );

        @unlink($markerAbsolute);
    }

    protected function tearDown(): void
    {
        @unlink($this->fixture);
        parent::tearDown();
    }
}
