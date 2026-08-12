<?php
$logFile = 'C:\\Users\\seigi\\.gemini\\antigravity-ide\\brain\\1fb2bb91-0f6a-47d9-805f-9bfe897238f4\\.system_generated\\logs\\transcript_full.jsonl';
$targetFile = 'c:\\xampp\\htdocs\\CitiLife-System\\views\\pages\\radtech\\patient-lists.view.php';
$content = file_get_contents($targetFile);
$applied = 0;

$handle = fopen($logFile, 'r');
if ($handle) {
    while (($line = fgets($handle)) !== false) {
        $data = json_decode($line, true);
        if ($data && isset($data['type']) && $data['type'] === 'PLANNER_RESPONSE') {
            if (isset($data['tool_calls'])) {
                foreach ($data['tool_calls'] as $tc) {
                    if (isset($tc['name']) && $tc['name'] === 'multi_replace_file_content') {
                        $args = $tc['args'];
                        if (strpos($args['TargetFile'] ?? '', 'patient-lists.view.php') !== false) {
                            $chunks = $args['ReplacementChunks'];
                            if (is_string($chunks)) $chunks = json_decode($chunks, true);
                            if (is_array($chunks)) {
                                foreach ($chunks as $chunk) {
                                    $target = $chunk['TargetContent'] ?? '';
                                    $replacement = $chunk['ReplacementContent'] ?? '';
                                    if (strpos($content, $target) !== false) {
                                        $content = str_replace($target, $replacement, $content);
                                        $applied++;
                                    } else {
                                        echo "Could not find target chunk...\n";
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
    fclose($handle);
}
file_put_contents('c:\\xampp\\htdocs\\CitiLife-System\\views\\pages\\radtech\\patient-lists_recovered.view.php', $content);
echo "Applied $applied chunks.\n";
