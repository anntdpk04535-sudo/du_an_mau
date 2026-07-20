<?php
$log_path = 'C:\Users\PHONG\.gemini\antigravity-ide\brain\8a368dcf-433b-46da-b90d-ecf56bbf013e\.system_generated\logs\transcript_full.jsonl';
$lines = file($log_path);
foreach ($lines as $line) {
    if (empty(trim($line))) continue;
    $data = json_decode($line, true);
    if (isset($data['tool_calls'])) {
        foreach ($data['tool_calls'] as $tc) {
            if ($tc['name'] === 'multi_replace_file_content') {
                $tf = $tc['args']['TargetFile'];
                if (is_string($tf) && str_starts_with($tf, '"')) $tf = json_decode($tf, true);
                if (strpos($tf, 'lang_vi.php') !== false) {
                    $chunks = $tc['args']['ReplacementChunks'];
                    if (is_string($chunks)) $chunks = json_decode($chunks, true);
                    if (is_array($chunks)) {
                        foreach ($chunks as $chunk) {
                            echo "--- CHUNK ---\n" . $chunk['ReplacementContent'] . "\n";
                        }
                    }
                }
            }
        }
    }
}
