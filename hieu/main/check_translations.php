<?php
$lang = require 'C:\xampp\htdocs\travel_daklak\main\includes\lang_vi.php';
$missing = [];
$dir = new RecursiveIteratorIterator(new RecursiveDirectoryIterator('C:\xampp\htdocs\travel_daklak\main\public'));
foreach ($dir as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $content = file_get_contents($file->getPathname());
        preg_match_all('/__\(\'([^\']+)\'\)/', $content, $matches);
        foreach ($matches[1] as $key) {
            if (!isset($lang[$key])) {
                $missing[$key] = 1;
            }
        }
    }
}
$dir = new RecursiveIteratorIterator(new RecursiveDirectoryIterator('C:\xampp\htdocs\travel_daklak\main\includes'));
foreach ($dir as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $content = file_get_contents($file->getPathname());
        preg_match_all('/__\(\'([^\']+)\'\)/', $content, $matches);
        foreach ($matches[1] as $key) {
            if (!isset($lang[$key])) {
                $missing[$key] = 1;
            }
        }
    }
}
print_r(array_keys($missing));
