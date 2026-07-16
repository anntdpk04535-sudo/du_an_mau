<?php
$log_path = 'C:\Users\PHONG\.gemini\antigravity-ide\brain\8a368dcf-433b-46da-b90d-ecf56bbf013e\.system_generated\logs\transcript_full.jsonl';
$lines = file($log_path);

foreach ($lines as $line) {
    if (empty(trim($line))) continue;
    $data = json_decode($line, true);
    
    if (isset($data['tool_calls'])) {
        foreach ($data['tool_calls'] as $tc) {
            $name = $tc['name'];
            $args = $tc['args'];
            
            if (!isset($args['TargetFile'])) continue;
            
            $target = $args['TargetFile'];
            if (is_string($target) && str_starts_with($target, '"')) $target = json_decode($target, true);
            if (!$target) continue;

            if ($name === 'write_to_file') {
                $content = $args['CodeContent'];
                if (is_string($content) && str_starts_with($content, '"')) $content = json_decode($content, true);
                if (!file_exists(dirname($target))) {
                    mkdir(dirname($target), 0777, true);
                }
                file_put_contents($target, str_replace("\r\n", "\n", $content));
                echo "Wrote $target\n";
            }
            elseif ($name === 'replace_file_content') {
                if (!file_exists($target)) continue;
                $fileContent = str_replace("\r\n", "\n", file_get_contents($target));
                
                $tContent = $args['TargetContent'];
                if (is_string($tContent) && str_starts_with($tContent, '"')) $tContent = json_decode($tContent, true);
                $tContent = str_replace("\r\n", "\n", $tContent);
                
                $rContent = $args['ReplacementContent'];
                if (is_string($rContent) && str_starts_with($rContent, '"')) $rContent = json_decode($rContent, true);
                $rContent = str_replace("\r\n", "\n", $rContent);
                
                $fileContent = str_replace($tContent, $rContent, $fileContent);
                file_put_contents($target, $fileContent);
                echo "Replaced in $target\n";
            }
            elseif ($name === 'multi_replace_file_content') {
                if (!file_exists($target)) continue;
                $fileContent = str_replace("\r\n", "\n", file_get_contents($target));
                
                $chunks = $args['ReplacementChunks'];
                if (is_string($chunks)) $chunks = json_decode($chunks, true);
                
                if (is_array($chunks)) {
                    foreach ($chunks as $chunk) {
                        $tContent = str_replace("\r\n", "\n", $chunk['TargetContent']);
                        $rContent = str_replace("\r\n", "\n", $chunk['ReplacementContent']);
                        $fileContent = str_replace($tContent, $rContent, $fileContent);
                    }
                    file_put_contents($target, $fileContent);
                    echo "Multi-replaced in $target\n";
                }
            }
        }
    }
}
echo "Recovery complete!\n";
?>