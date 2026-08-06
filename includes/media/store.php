<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/db.php';

const MEDIA_DERIVATIVE_WIDTHS = [400, 800, 1600];
const MEDIA_MAX_BYTES = 10485760;

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

function mediaRelativePath(string $hash, string $ext, ?int $width = null): string
{
    $shard = substr($hash, 0, 2) . '/' . substr($hash, 2, 2);
    $suffix = $width === null ? '' : '-' . $width;
    return "/assets/images/media/{$shard}/{$hash}{$suffix}.{$ext}";
}

function mediaAbsolutePath(string $relative): string
{
    return dirname(__DIR__, 2) . $relative;
}

/** @return string[] danh sách variant thực sự tạo được, ví dụ ['400jpg','800jpg'] */
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

        $jpegRelative = mediaRelativePath($hash, 'jpg', $width);
        $jpegAbsolute = mediaAbsolutePath($jpegRelative);
        if (!is_dir(dirname($jpegAbsolute))) {
            mkdir(dirname($jpegAbsolute), 0755, true);
        }
        if (imagejpeg($resized, $jpegAbsolute, 82)) {
            $variants[] = $width . 'jpg';
        }

        if (mediaEncoderSupportsWebp()) {
            $webpAbsolute = mediaAbsolutePath(mediaRelativePath($hash, 'webp', $width));
            $ok = function_exists('imagewebp')
                ? imagewebp($resized, $webpAbsolute, 80)
                : (bool)@shell_exec(sprintf(
                    'cwebp -quiet -q 80 %s -o %s',
                    escapeshellarg($jpegAbsolute),
                    escapeshellarg($webpAbsolute)
                ));
            if ($ok && is_file($webpAbsolute)) {
                $variants[] = $width . 'webp';
            }
        }

        imagedestroy($resized);
    }

    imagedestroy($source);
    return $variants;
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

    $existing = $db->prepare('SELECT id FROM media_assets WHERE content_hash = ?');
    $existing->execute([$hash]);
    $found = $existing->fetchColumn();
    if ($found !== false) {
        return (int)$found;
    }

    $relative = mediaRelativePath($hash, $ext);
    $absolute = mediaAbsolutePath($relative);
    if (!is_dir(dirname($absolute)) && !mkdir(dirname($absolute), 0755, true) && !is_dir(dirname($absolute))) {
        throw new RuntimeException('Không tạo được thư mục lưu trữ.');
    }
    if (!copy($srcPath, $absolute)) {
        throw new RuntimeException('Không sao chép được file.');
    }

    $dimensions = getimagesize($absolute);
    $variants = mediaGenerateDerivatives($absolute, $hash, $ext);

    try {
        $insert = $db->prepare(
            'INSERT INTO media_assets
             (storage_path, content_hash, mime, width, height, bytes, variants,
              source, source_url, author, license, license_url, attribution_text, fetched_at)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?, NOW())'
        );
        $insert->execute([
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
        @unlink($absolute);
        foreach ($variants as $variant) {
            $width = (int)$variant;
            $vext = substr($variant, strlen((string)$width));
            @unlink(mediaAbsolutePath(mediaRelativePath($hash, $vext, $width)));
        }
        throw $e;
    }

    return (int)$db->lastInsertId();
}

function mediaStoreFromUrl(string $url, array $meta): ?int
{
    $temp = tempnam(sys_get_temp_dir(), 'media_');
    $handle = fopen($temp, 'wb');
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_FILE           => $handle,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_USERAGENT      => 'DaklakTravelBot/1.0 (+https://github.com/anntdpk04535-sudo/du_an_mau)',
    ]);
    $ok = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    fclose($handle);

    if (!$ok || $code < 200 || $code >= 300) {
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
