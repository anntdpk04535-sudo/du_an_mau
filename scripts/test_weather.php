<?php
// Smoke test cho includes/weather.php — chạy: /Applications/XAMPP/xamppfiles/bin/php scripts/test_weather.php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/geo.php';
require_once __DIR__ . '/../includes/weather.php';

$fail = 0;
function ok(bool $cond, string $msg): void
{
    global $fail;
    if ($cond) {
        echo "  ✅ {$msg}\n";
    } else {
        $fail++;
        echo "  ❌ {$msg}\n";
    }
}

echo "== weatherRiskFromCode ==\n";
ok(weatherRiskFromCode(65, 80) === 'indoor_preferred', '(code 65 mưa to, 80%) → indoor_preferred');
ok(weatherRiskFromCode(0, 0) === 'good', '(code 0 nắng, 0%) → good');
ok(weatherRiskFromCode(95, 90) === 'unsafe', '(code 95 dông, 90%) → unsafe');
ok(weatherRiskFromCode(96) === 'unsafe', 'code 96 dông + đá → unsafe');
ok(weatherRiskFromCode(80) === 'indoor_preferred', 'code 80 mưa rào → indoor_preferred');
ok(weatherRiskFromCode(45) === 'caution', 'code 45 sương mù → caution');
ok(weatherRiskFromCode(0, 75) === 'caution', 'code 0 nhưng xác suất mưa 75% → caution');
ok(weatherRiskFromCode(2) === 'good', 'code 2 ít mây → good');

echo "== weatherDescribeCode ==\n";
foreach ([0, 45, 65, 95, 999] as $code) {
    $info = weatherDescribeCode($code);
    ok(isset($info['vi'], $info['en'], $info['icon'], $info['advice_vi'], $info['advice_en']),
        "code {$code} → đủ khoá vi/en/icon/advice_vi/advice_en");
}

echo "== weatherNormalizePayload ==\n";
$payload = [
    'current' => ['temperature_2m' => 24.6, 'relative_humidity_2m' => 88, 'apparent_temperature' => 27.1, 'wind_speed_10m' => 7.4, 'weather_code' => 3],
    'daily' => [
        'time' => ['2026-08-06', '2026-08-07', '2026-08-08'],
        'weather_code' => [0, 80, 95],
        'temperature_2m_max' => [30.2, 27.5, 25.1],
        'temperature_2m_min' => [21.1, 20.9, 20.3],
        'precipitation_sum' => [0.0, 8.4, 22.7],
        'precipitation_probability_max' => [5, 78, 92],
    ],
];
$norm = weatherNormalizePayload($payload, 12.68, 108.04);
ok(count($norm['daily']) === 3, '3 ngày → 3 phần tử daily');
ok($norm['daily'][0]['risk'] === 'good', 'ngày 1 (code 0, 5%) → good');
ok($norm['daily'][1]['risk'] === 'indoor_preferred', 'ngày 2 (code 80) → indoor_preferred');
ok($norm['daily'][2]['risk'] === 'unsafe', 'ngày 3 (code 95) → unsafe');
ok($norm['current']['temperature'] === 25.0 || $norm['current']['temperature'] === 25, 'current temperature làm tròn 24.6 → 25');
ok($norm['daily'][2]['icon'] !== '' && $norm['daily'][2]['text_vi'] !== '', 'daily có icon + text_vi');

echo "== weatherAdvisories ==\n";
$norm['available'] = true;
$adv = weatherAdvisories($norm);
ok(count($adv) === 2, '2 ngày rủi ro (indoor_preferred + unsafe) → 2 khuyến cáo (thực tế: ' . count($adv) . ')');
ok(strpos(implode("\n", $adv), 'dông sét') !== false, 'khuyến cáo ngày unsafe nhắc dông sét');

$offline = ['available' => false, 'source' => 'none', 'current' => null, 'daily' => []];
$advOff = weatherAdvisories($offline);
ok(count($advOff) === 1 && strpos($advOff[0], 'Không lấy được') !== false, 'offline → 1 khuyến cáo "Không lấy được dự báo"');

echo "== weatherPromptLines ==\n";
$lines = weatherPromptLines($norm);
ok(count($lines) === 3, '3 ngày → 3 dòng prompt');
ok(weatherPromptLines($offline) === [], 'offline → mảng rỗng');

echo "== weatherFetchForecast (DB + mạng, chấp nhận offline) ==\n";
try {
    $db = getDB();
    $fc = weatherFetchForecast($db, WEATHER_DEFAULT_LAT, WEATHER_DEFAULT_LNG, 2);
    ok(isset($fc['available'], $fc['source'], $fc['daily']), 'kết quả luôn có available/source/daily');
    if (!empty($fc['available'])) {
        ok(count($fc['daily']) >= 1, 'available → có ít nhất 1 ngày daily (source: ' . $fc['source'] . ')');
        $fc2 = weatherFetchForecast($db, WEATHER_DEFAULT_LAT, WEATHER_DEFAULT_LNG, 2);
        ok(in_array($fc2['source'], ['cache', 'live', 'cache_stale'], true), 'gọi lần 2 → source hợp lệ (' . $fc2['source'] . ', kỳ vọng cache)');
    } else {
        ok($fc['daily'] === [], 'offline → available:false và daily rỗng');
        echo "  ⏭️  không có mạng/API lỗi — nhánh offline hoạt động đúng\n";
    }
} catch (Exception $e) {
    echo "  ⏭️  bỏ qua test DB: " . $e->getMessage() . "\n";
}

echo $fail === 0 ? "\n🎉 test_weather: TẤT CẢ PASS\n" : "\n💥 test_weather: {$fail} test FAIL\n";
exit($fail === 0 ? 0 : 1);
