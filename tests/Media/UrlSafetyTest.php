<?php
declare(strict_types=1);

namespace Tests\Media;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * F3 (review vòng 1, Important) — SSRF: mediaStoreFromUrl() cũ dùng
 * CURLOPT_FOLLOWLOCATION không kèm ràng buộc scheme/địa chỉ nội bộ, cho phép
 * một redirect 302 dẫn tới gopher://127.0.0.1:PORT hoặc
 * http://169.254.169.254/... (cloud metadata) bắn payload tuỳ ý vào hạ tầng
 * nội bộ.
 *
 * Test hàm KIỂM TRA thuần (mediaUrlIsFetchable) — đúng ràng buộc của vòng
 * sửa: KHÔNG mạng, KHÔNG DB. Chỉ dùng địa chỉ IP literal (không cần DNS) và
 * duy nhất một hostname ('localhost') vốn phân giải cục bộ qua
 * gethostbynamel()/dns_get_record() mà không cần chạm Internet thật trên bất
 * kỳ máy nào có stack mạng chuẩn.
 */
final class UrlSafetyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        require_once __DIR__ . '/../../includes/media/store.php';
    }

    /** @return array<string, array{0: string, 1: bool}> */
    public static function urlCases(): array
    {
        return [
            'https công khai (IP literal)'           => ['https://8.8.8.8/x.jpg', true],
            'http công khai (IP literal)'             => ['http://8.8.8.8/x.jpg', true],
            'IPv6 công khai (IP literal, có ngoặc)'   => ['http://[2001:4860:4860::8888]/x.jpg', true],
            'loopback IPv4 127.0.0.1'                 => ['http://127.0.0.1/x.jpg', false],
            'loopback IPv4 khác trong dải 127/8'      => ['http://127.5.5.5/x.jpg', false],
            'loopback IPv6 ::1 (có ngoặc)'             => ['http://[::1]/x.jpg', false],
            'private 10.0.0.0/8'                       => ['http://10.0.0.1/x.jpg', false],
            'private 172.16.0.0/12'                    => ['http://172.16.0.1/x.jpg', false],
            'private 192.168.0.0/16'                   => ['http://192.168.1.1/x.jpg', false],
            'link-local 169.254/16 (cloud metadata)'  => ['http://169.254.169.254/latest/meta-data/', false],
            'unique-local IPv6 fc00::/7'               => ['http://[fc00::1]/x.jpg', false],
            'link-local IPv6 fe80::/10'                => ['http://[fe80::1]/x.jpg', false],
            'hostname phân giải cục bộ về loopback'   => ['http://localhost/x.jpg', false],
            'scheme gopher tới loopback (payload SSRF cổ điển)' => ['gopher://127.0.0.1:9911/_SET', false],
            'scheme gopher tới IP công khai (vẫn phải chặn theo scheme)' => ['gopher://8.8.8.8/x', false],
            'scheme file'                               => ['file:///etc/passwd', false],
            'scheme ftp'                                 => ['ftp://8.8.8.8/x', false],
            'thiếu host'                                 => ['http:///x.jpg', false],
            'chuỗi rỗng'                                 => ['', false],
            'không có scheme'                            => ['8.8.8.8/x.jpg', false],
        ];
    }

    #[DataProvider('urlCases')]
    public function test_mediaUrlIsFetchable_phan_loai_dung(string $url, bool $expected): void
    {
        self::assertSame($expected, mediaUrlIsFetchable($url), "URL: {$url}");
    }
}
