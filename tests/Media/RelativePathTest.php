<?php
declare(strict_types=1);

namespace Tests\Media;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * F6 (review vòng 1, Minor nhưng dính bảo mật) — mediaRelativePath() trước
 * đây ghép $hash/$ext/$width thẳng vào chuỗi đường dẫn không kiểm tra gì.
 * Đây là API công khai mà Task 5 (API media công khai) sẽ gọi trực tiếp với
 * $ext lấy từ request — không validate thì một $ext như
 * 'jpg/../../../../../../tmp/evil.php' thoát khỏi storage root
 * (path traversal). Test này KHÔNG cần DB nên extend PHPUnit\TestCase thẳng,
 * không qua Tests\Support\TestCase.
 */
final class RelativePathTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        require_once __DIR__ . '/../../includes/media/store.php';
    }

    public function test_hash_hop_le_tra_ve_duong_dan_dung_dinh_dang(): void
    {
        $hash = str_repeat('ab', 32);
        self::assertSame(
            '/assets/images/media/ab/ab/' . $hash . '.jpg',
            mediaRelativePath($hash, 'jpg')
        );
        self::assertSame(
            '/assets/images/media/ab/ab/' . $hash . '-800.jpg',
            mediaRelativePath($hash, 'jpg', 800)
        );
    }

    public function test_tu_choi_hash_qua_ngan(): void
    {
        $this->expectException(InvalidArgumentException::class);
        mediaRelativePath('aabb', 'jpg');
    }

    public function test_tu_choi_hash_khong_phai_hex(): void
    {
        $this->expectException(InvalidArgumentException::class);
        mediaRelativePath(str_repeat('z', 64), 'jpg');
    }

    /**
     * Ca khai thác đúng như reviewer tái hiện: $ext mang path traversal.
     */
    public function test_tu_choi_ext_chua_path_traversal(): void
    {
        $this->expectException(InvalidArgumentException::class);
        mediaRelativePath(str_repeat('a', 64), 'jpg/../../../../../../tmp/evil.php');
    }

    public function test_tu_choi_ext_ngoai_danh_sach_cho_phep(): void
    {
        $this->expectException(InvalidArgumentException::class);
        mediaRelativePath(str_repeat('a', 64), 'php');
    }

    public function test_tu_choi_width_ngoai_danh_sach_derivative(): void
    {
        $this->expectException(InvalidArgumentException::class);
        mediaRelativePath(str_repeat('a', 64), 'jpg', 999);
    }
}
