<?php
$lang_vi = require __DIR__ . '/../includes/lang_vi.php';
$lang_en = require __DIR__ . '/../includes/lang_en.php';
$missing_vi = [];
$missing_en = [];

$scan_dirs = [__DIR__ . '/../public', __DIR__ . '/../includes', __DIR__ . '/../api', __DIR__ . '/../admin'];
foreach ($scan_dirs as $scan_dir) {
    if (!is_dir($scan_dir)) continue;
    $dir = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($scan_dir));
    foreach ($dir as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $content = file_get_contents($file->getPathname());
            preg_match_all('/__\(\'([^\']+)\'\)/', $content, $matches);
            foreach ($matches[1] as $key) {
                if (!isset($lang_vi[$key])) {
                    $missing_vi[$key] = true;
                }
                if (!isset($lang_en[$key])) {
                    $missing_en[$key] = true;
                }
            }
        }
    }
}
echo "Missing in lang_vi.php:\n";
print_r(array_keys($missing_vi));
echo "\nMissing in lang_en.php:\n";
print_r(array_keys($missing_en));

$vi_not_en = array_diff_key($lang_vi, $lang_en);
$en_not_vi = array_diff_key($lang_en, $lang_vi);

echo "\nKeys in lang_vi.php but missing in lang_en.php:\n";
print_r(array_keys($vi_not_en));
echo "\nKeys in lang_en.php but missing in lang_vi.php:\n";
print_r(array_keys($en_not_vi));
