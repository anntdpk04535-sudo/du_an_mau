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
