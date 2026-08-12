<?php
$lines = file('C:/Users/seigi/.gemini/antigravity-ide/brain/21f1e8dd-5cf4-45a8-9596-4486f4034cde/.system_generated/logs/transcript_full.jsonl');
foreach($lines as $line) {
    if (strpos($line, 'patient-lists.view.php') !== false && strpos($line, 'Total Lines: 1056') !== false) {
        file_put_contents('extracted2.json', $line);
        echo "Found second extraction!\n";
        break;
    }
}
