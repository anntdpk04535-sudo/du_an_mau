<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/db.php';

const MEDIA_DERIVATIVE_WIDTHS = [400, 800, 1600];
const MEDIA_MAX_BYTES = 10485760;
const MEDIA_ALLOWED_EXTENSIONS = ['jpg', 'png', 'gif', 'webp'];
const MEDIA_ALLOWED_SOURCES = ['wikimedia', 'unsplash', 'google_places', 'upload'];
const MEDIA_ALLOWED_URL_SCHEMES = ['http', 'https'];
const MEDIA_MAX_REDIRECTS = 5;

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

/**
 * F6 (review vòng 1, Minor/bảo mật): $hash/$ext/$width được validate chặt
 * trước khi ghép vào đường dẫn. Đây là API công khai mà lớp API media (Task
 * 5) sẽ gọi trực tiếp với $ext lấy từ request — thiếu kiểm tra thì một $ext
 * dạng 'jpg/../../../../../../tmp/evil.php' thoát khỏi storage root (path
 * traversal). Ném InvalidArgumentException (nhất quán với cách
 * scripts/migrate_media.php báo lỗi tham số CLI sai) vì đây là lỗi LẬP
 * TRÌNH/đầu vào không hợp lệ, khác với RuntimeException dùng cho lỗi vận
 * hành (I/O, DB) ở các hàm khác trong file này.
 */
function mediaRelativePath(string $hash, string $ext, ?int $width = null): string
{
    if (strlen($hash) !== 64 || !ctype_xdigit($hash)) {
        throw new InvalidArgumentException('content_hash không hợp lệ: phải là chuỗi hex 64 ký tự (sha256).');
    }
    if (!in_array($ext, MEDIA_ALLOWED_EXTENSIONS, true)) {
        throw new InvalidArgumentException("Phần mở rộng không được hỗ trợ: '{$ext}'.");
    }
    if ($width !== null && !in_array($width, MEDIA_DERIVATIVE_WIDTHS, true)) {
        throw new InvalidArgumentException("Kích thước derivative không hợp lệ: {$width}.");
    }

    $shard = substr($hash, 0, 2) . '/' . substr($hash, 2, 2);
    $suffix = $width === null ? '' : '-' . $width;
    return "/assets/images/media/{$shard}/{$hash}{$suffix}.{$ext}";
}

function mediaAbsolutePath(string $relative): string
{
    return dirname(__DIR__, 2) . $relative;
}

/**
 * F2 (review vòng 1, Critical): trước đây dùng
 * (bool)@shell_exec('cwebp ... -o ...') để suy ra thành công/thất bại.
 * shell_exec() trả về STDOUT đã capture, KHÔNG PHẢI mã thoát — `cwebp
 * -quiet` không in gì ra stdout khi thành công nên biểu thức này LUÔN false,
 * dù cwebp thật sự tạo file .webp thành công. Hệ quả: file .webp mồ côi
 * trên đĩa, không được ghi vào variants, không bao giờ được rollback dọn khi
 * INSERT lỗi. Sửa: dùng exec() để lấy mã thoát thật qua tham số $exitCode.
 *
 * Đồng thời sửa luôn lỗi nén lossy hai lần: bản cũ cho cwebp đọc lại chính
 * file .jpg vừa nén ở bước trên ($jpegAbsolute) làm input — ảnh đã mất chi
 * tiết do JPEG (lossy) rồi lại bị cwebp nén lossy lần nữa. Ở đây cwebp đọc
 * từ một PNG (lossless) ghi tạm trực tiếp từ GD resource $resized, tránh
 * chồng hai lớp nén lossy; PNG tạm được xoá ngay sau khi cwebp đọc xong.
 */
function mediaEncodeWebpViaCwebp(GdImage $resized, string $webpAbsolute): bool
{
    $tmpPng = $webpAbsolute . '.src.png';
    if (!imagepng($resized, $tmpPng)) {
        return false;
    }

    $exitCode = 1;
    exec(sprintf(
        'cwebp -quiet -q 80 %s -o %s 2>/dev/null',
        escapeshellarg($tmpPng),
        escapeshellarg($webpAbsolute)
    ), $unusedOutput, $exitCode);
    @unlink($tmpPng);

    return $exitCode === 0;
}

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

        $jpegAbsolute = mediaAbsolutePath(mediaRelativePath($hash, 'jpg', $width));
        $jpegDir = dirname($jpegAbsolute);
        // F13 (review vòng 1, Minor): mkdir() có thể thất bại (đua giữa
        // is_dir() và mkdir(), quyền thư mục cha...) — kiểm tra giá trị trả
        // về + is_dir() lại, nhất quán với guard đã có sẵn ở
        // mediaStoreFromFile(), thay vì gọi mkdir() rồi mặc định tin nó
        // thành công.
        if (!is_dir($jpegDir) && !mkdir($jpegDir, 0755, true) && !is_dir($jpegDir)) {
            imagedestroy($resized);
            continue;
        }

        if (imagejpeg($resized, $jpegAbsolute, 82)) {
            $variants[] = $width . 'jpg';
        }

        if (mediaEncoderSupportsWebp()) {
            $webpAbsolute = mediaAbsolutePath(mediaRelativePath($hash, 'webp', $width));
            $webpOk = function_exists('imagewebp')
                ? imagewebp($resized, $webpAbsolute, 80)
                : mediaEncodeWebpViaCwebp($resized, $webpAbsolute);
            if ($webpOk && is_file($webpAbsolute)) {
                $variants[] = $width . 'webp';
            }
        }

        imagedestroy($resized);
    }

    imagedestroy($source);
    return $variants;
}

/**
 * F1 (review vòng 1, Critical): tách phần ghi hàng media_assets ra một hàm
 * riêng dùng INSERT ... ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id) —
 * idiom MySQL/MariaDB cho "insert-or-get-id" NGUYÊN TỬ ở tầng DB engine.
 * UNIQUE KEY uq_media_hash (content_hash) đảm bảo statement này KHÔNG BAO
 * GIỜ ném lỗi vì trùng hash: nếu đụng UNIQUE KEY, MySQL tự chuyển sang nhánh
 * UPDATE giả (chỉ cập nhật id = chính nó) và LAST_INSERT_ID(id) trả về id
 * của hàng đã tồn tại.
 *
 * Đây là lý do cấu trúc lại giải quyết tận gốc F1: catch block ở
 * mediaStoreFromFile() trước đây bắt MỌI Throwable từ statement INSERT rồi
 * xoá file — kể cả khi Throwable đó chỉ là "trùng hash vì thua race", trong
 * khi file vừa xoá lại chính là file của BÊN THẮNG (đường dẫn suy từ hash).
 * Với ON DUPLICATE KEY UPDATE, tình huống "trùng hash" không còn là lỗi nữa
 * — không Throwable nào được ném cho case đó — nên catch block ở
 * mediaStoreFromFile() chỉ còn kích hoạt cho lỗi THẬT (mất kết nối, ràng
 * buộc khác vỡ...), đúng như ý nghĩa "rollback khi có lỗi" của nó.
 */
function mediaPersistAssetRow(PDO $db, array $columns): int
{
    $insert = $db->prepare(
        'INSERT INTO media_assets
         (storage_path, content_hash, mime, width, height, bytes, variants,
          source, source_url, author, license, license_url, attribution_text, fetched_at)
         VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?, NOW())
         ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)'
    );
    $insert->execute($columns);
    return (int)$db->lastInsertId();
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
    // F10 (review vòng 1, Minor): chặn source ngoài enum của cột DB NGAY TẠI
    // BIÊN, trước bất kỳ I/O nào — tránh rơi xuống tận INSERT rồi vỡ ở tầng
    // DB sau khi đã copy file + sinh derivative tốn công (và phải rollback).
    if (!in_array($source, MEDIA_ALLOWED_SOURCES, true)) {
        throw new RuntimeException("Nguồn không hợp lệ: '{$source}'.");
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

    // Fast path THUẦN TUÝ để tiết kiệm I/O (bỏ qua copy + resize khi đã biết
    // chắc hash tồn tại) — KHÔNG phải cơ chế đảm bảo đúng đắn dưới đua tranh.
    // Đúng đắn dưới đua tranh đến từ mediaPersistAssetRow() (ON DUPLICATE KEY
    // UPDATE), chạy dù nhánh fast-path này có mặt hay không — xem F1.
    $existing = $db->prepare('SELECT id FROM media_assets WHERE content_hash = ?');
    $existing->execute([$hash]);
    $found = $existing->fetchColumn();
    if ($found !== false) {
        return (int)$found;
    }

    $relative = mediaRelativePath($hash, $ext);
    $absolute = mediaAbsolutePath($relative);
    $variants = [];

    // F5 (review vòng 1, Important): copy() + đọc kích thước + sinh
    // derivative + INSERT giờ nằm chung MỘT try/catch. Bản cũ chỉ bọc riêng
    // statement INSERT — nếu copy() hay mediaGenerateDerivatives() ném lỗi
    // (đĩa đầy, ảnh hỏng giữa getimagesize() ngoài và getimagesize() trong,
    // mkdir() thất bại...) thì file đã copy xong (nếu có) bị bỏ mồ côi vĩnh
    // viễn trên đĩa, không ai dọn.
    try {
        if (!is_dir(dirname($absolute)) && !mkdir(dirname($absolute), 0755, true) && !is_dir(dirname($absolute))) {
            throw new RuntimeException('Không tạo được thư mục lưu trữ.');
        }
        if (!copy($srcPath, $absolute)) {
            throw new RuntimeException('Không sao chép được file.');
        }

        $dimensions = getimagesize($absolute);
        if ($dimensions === false) {
            throw new RuntimeException('Không đọc được kích thước ảnh sau khi lưu.');
        }
        $variants = mediaGenerateDerivatives($absolute, $hash, $ext);

        return mediaPersistAssetRow($db, [
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
        // Từ khi mediaPersistAssetRow() dùng ON DUPLICATE KEY UPDATE, mọi
        // Throwable tới đây đều là lỗi THẬT (không còn case "trùng hash" —
        // case đó được nuốt êm bên trong mediaPersistAssetRow(), không ném
        // ra ngoài) nên xoá file là an toàn và đúng đắn trong mọi trường hợp.
        @unlink($absolute);
        foreach ($variants as $variant) {
            $width = (int)$variant;
            $vext = substr($variant, strlen((string)$width));
            @unlink(mediaAbsolutePath(mediaRelativePath($hash, $vext, $width)));
        }
        throw $e;
    }
}

/**
 * F3 (review vòng 1, Important — SSRF): trả về IP CÔNG KHAI đầu tiên mà host
 * của $url phân giải tới, hoặc null nếu URL không hợp lệ / scheme không
 * phải http-https / không phân giải được / MỌI địa chỉ phân giải được đều
 * thuộc dải riêng tư/loopback/link-local/dành riêng.
 *
 * Dùng CHUNG cho việc kiểm tra (mediaUrlIsFetchable) lẫn việc ghim kết nối
 * (CURLOPT_RESOLVE trong mediaStoreFromUrl): ghim thẳng vào IP đã kiểm tra
 * thay vì để curl tự phân giải lại DNS lúc connect() đóng luôn lỗ hổng
 * DNS-rebinding (TOCTOU giữa lúc kiểm tra ở đây và lúc thực sự kết nối).
 *
 * Host IPv6 literal lấy từ parse_url() còn giữ nguyên cặp ngoặc vuông (vd.
 * '[::1]') — filter_var('[::1]', FILTER_VALIDATE_IP) trả về false do
 * ngoặc, nên phải trim('[]') trước khi kiểm tra hoặc phân giải. (Đã xác
 * minh thực nghiệm bằng php -r trước khi viết hàm này.)
 */
function mediaResolvePublicIp(string $url): ?string
{
    $parts = parse_url($url);
    if ($parts === false || !isset($parts['scheme'], $parts['host']) || $parts['host'] === '') {
        return null;
    }
    if (!in_array(strtolower($parts['scheme']), MEDIA_ALLOWED_URL_SCHEMES, true)) {
        return null;
    }

    $host = trim($parts['host'], '[]');
    $ips = [];

    if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
        $ips[] = $host;
    } else {
        $v4 = @gethostbynamel($host);
        if (is_array($v4)) {
            $ips = array_merge($ips, $v4);
        }
        $v6Records = @dns_get_record($host, DNS_AAAA);
        if (is_array($v6Records)) {
            foreach ($v6Records as $record) {
                if (isset($record['ipv6'])) {
                    $ips[] = $record['ipv6'];
                }
            }
        }
    }

    foreach ($ips as $ip) {
        if (mediaIpIsPublic($ip)) {
            return $ip;
        }
    }
    return null;
}

function mediaIpIsPublic(string $ip): bool
{
    return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
}

/**
 * F3 (review vòng 1, Important — SSRF): hàm kiểm THUẦN, không mạng không DB
 * — dùng để unit-test độc lập (tests/Media/UrlSafetyTest.php) và để
 * mediaStoreFromUrl() tái sử dụng ở từng chặng redirect.
 */
function mediaUrlIsFetchable(string $url): bool
{
    return mediaResolvePublicIp($url) !== null;
}

/**
 * F3 + F4 (review vòng 1, Important — SSRF): bản cũ dùng
 * CURLOPT_FOLLOWLOCATION => true không kèm ràng buộc gì, cho phép một
 * redirect 302 từ máy chủ tưởng chừng vô hại dẫn tới gopher://127.0.0.1
 * hoặc http://169.254.169.254 (cloud metadata) — curl sẽ tự đi theo mà
 * KHÔNG hề kiểm tra lại đích mới có an toàn hay không.
 *
 * Sửa: tắt CURLOPT_FOLLOWLOCATION, tự lặp qua từng redirect (tối đa
 * MEDIA_MAX_REDIRECTS chặng), và validate lại BẰNG mediaResolvePublicIp() ở
 * MỖI chặng — kể cả chặng đầu tiên — trước khi tải. IP đã xác nhận công khai
 * được ghim thẳng vào request qua CURLOPT_RESOLVE để khoá luôn lỗ hổng
 * DNS-rebinding (DNS trả IP công khai lúc kiểm tra, đổi sang IP nội bộ lúc
 * curl thực sự connect() — TOCTOU).
 *
 * F4: thêm CURLOPT_MAXFILESIZE (chặn Content-Length khai man/không có) và
 * CURLOPT_XFERINFOFUNCTION (chặn stream không giới hạn không khai
 * Content-Length trước — server có thể bơm dữ liệu vô hạn nếu chỉ dựa vào
 * MAXFILESIZE, vốn chỉ đọc header).
 */
function mediaStoreFromUrl(string $url, array $meta): ?int
{
    $currentUrl = $url;

    for ($hop = 0; $hop <= MEDIA_MAX_REDIRECTS; $hop++) {
        $ip = mediaResolvePublicIp($currentUrl);
        if ($ip === null) {
            return null;
        }

        // F14 (review vòng 1, Minor): tempnam()/fopen() có thể thất bại
        // (hết dung lượng /tmp, hết file descriptor, quyền...). Bản cũ
        // không kiểm tra: nếu fopen() trả false, CURLOPT_FILE nhận false
        // thay vì resource hợp lệ, và với declare(strict_types=1) đây sẽ
        // ném TypeError không được xử lý thay vì lỗi rõ ràng. Dừng sớm và
        // trả null — nhất quán với hợp đồng "trả null khi thất bại" sẵn có
        // của hàm này.
        $temp = tempnam(sys_get_temp_dir(), 'media_');
        if ($temp === false) {
            return null;
        }
        $handle = fopen($temp, 'wb');
        if ($handle === false) {
            @unlink($temp);
            return null;
        }

        $parts = parse_url($currentUrl);
        $scheme = strtolower((string)$parts['scheme']);
        $host = trim((string)$parts['host'], '[]');
        $port = $parts['port'] ?? ($scheme === 'https' ? 443 : 80);

        $ch = curl_init($currentUrl);
        curl_setopt_array($ch, [
            CURLOPT_FILE              => $handle,
            CURLOPT_FOLLOWLOCATION    => false,
            CURLOPT_TIMEOUT           => 30,
            CURLOPT_PROTOCOLS         => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_MAXFILESIZE       => MEDIA_MAX_BYTES,
            CURLOPT_NOPROGRESS        => false,
            CURLOPT_XFERINFOFUNCTION  => static function ($res, int $downloadSize, int $downloaded, int $uploadSize, int $uploaded): int {
                return $downloaded > MEDIA_MAX_BYTES ? 1 : 0;
            },
            CURLOPT_RESOLVE           => ["{$host}:{$port}:{$ip}"],
            CURLOPT_USERAGENT         => 'DaklakTravelBot/1.0 (+https://github.com/anntdpk04535-sudo/du_an_mau)',
        ]);
        $ok = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $redirectUrl = curl_getinfo($ch, CURLINFO_REDIRECT_URL) ?: null;
        curl_close($ch);
        fclose($handle);

        if (!$ok) {
            @unlink($temp);
            return null;
        }

        if ($code >= 300 && $code < 400) {
            @unlink($temp);
            if ($redirectUrl === null) {
                return null;
            }
            $currentUrl = $redirectUrl;
            continue;
        }

        if ($code < 200 || $code >= 300) {
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

    return null; // vượt quá MEDIA_MAX_REDIRECTS chặng redirect cho phép
}
