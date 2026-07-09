<?php
$log_path = 'C:\Users\PHONG\.gemini\antigravity-ide\brain\8a368dcf-433b-46da-b90d-ecf56bbf013e\.system_generated\logs\transcript_full.jsonl';
$lines = file($log_path);
$found = false;
foreach ($lines as $line) {
    if (strpos($line, 'dự án của tôi còn thiếu chức năng gì không') !== false) {
        $found = true;
    }
    if ($found && strpos($line, 'PLANNER_RESPONSE') !== false) {
        $data = json_decode($line, true);
        if (isset($data['content'])) {
            echo $data['content'] . "\n---\n";
            break;
        }
    }
}
