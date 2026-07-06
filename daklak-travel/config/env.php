<?php
/**
 * Đọc file .env (nếu có) và nạp vào getenv()/putenv().
 * Không bắt buộc phải có .env — nếu thiếu, các define() trong db.php / ai.php
 * sẽ dùng giá trị mặc định hoặc rỗng.
 */

function loadEnvFile(string $path): void
{
    if (!is_file($path)) {
        return;
    }
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        if (!str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = array_map('trim', explode('=', $line, 2));
        $value = trim($value, "\"'");
        if ($key !== '' && getenv($key) === false) {
            putenv("{$key}={$value}");
        }
    }
}

// Tìm .env ở thư mục gốc dự án (1 cấp trên /config)
loadEnvFile(dirname(__DIR__) . '/.env');
