<?php
$vi_append = <<<PHP
    'dashboard_ai_spot1' => 'Làng Cà phê Trung Nguyên',
    'dashboard_ai_spot2' => 'Thác Dray Nur',
    'dashboard_ai_spot3' => 'Gành Đá Đĩa',
    'dashboard_ai_week1' => 'Tuần 1',
    'dashboard_ai_week2' => 'Tuần 2',
    'dashboard_ai_week3' => 'Tuần 3',
    'dashboard_ai_week4' => 'Tuần 4',
PHP;

$en_append = <<<PHP
    'dashboard_ai_spot1' => 'Trung Nguyen Coffee Village',
    'dashboard_ai_spot2' => 'Dray Nur Waterfall',
    'dashboard_ai_spot3' => 'Ganh Da Dia',
    'dashboard_ai_week1' => 'Week 1',
    'dashboard_ai_week2' => 'Week 2',
    'dashboard_ai_week3' => 'Week 3',
    'dashboard_ai_week4' => 'Week 4',
PHP;

$vi_file = __DIR__ . '/includes/lang_vi.php';
$vi_content = file_get_contents($vi_file);
$vi_content = str_replace('];', $vi_append . "\n];", $vi_content);
file_put_contents($vi_file, $vi_content);

$en_file = __DIR__ . '/includes/lang_en.php';
$en_content = file_get_contents($en_file);
$en_content = str_replace('];', $en_append . "\n];", $en_content);
file_put_contents($en_file, $en_content);

echo "Translations appended.\n";
