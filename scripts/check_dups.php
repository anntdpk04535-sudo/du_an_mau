<?php
$dir = new RecursiveIteratorIterator(new RecursiveDirectoryIterator('C:\xampp\htdocs\travel_daklak\main'));
foreach ($dir as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $content = file_get_contents($file->getPathname());
        // Simple heuristic: look for identical blocks of > 30 characters separated by nothing or whitespace
        preg_match_all('/(.{30,})\s+\1/s', $content, $matches);
        if (!empty($matches[1])) {
            foreach ($matches[1] as $dup) {
                if (strpos($dup, 'foreach') === false && strpos($dup, 'function') === false && trim($dup) !== '') {
                    echo "Duplicate block found in " . $file->getPathname() . ":\n" . substr($dup, 0, 100) . "...\n\n";
                }
            }
        }
    }
}
