<?php
require_once __DIR__ . '/mailer_helper.php';

// Accept JSON payload from CLI arg (file path or base64)
if ($argc > 1) {
    $arg = $argv[1];
    $data = null;
    if (file_exists($arg)) {
        $content = file_get_contents($arg);
        @unlink($arg);
        $data = json_decode($content, true);
    } else {
        $data = json_decode(base64_decode($arg), true);
    }
    if ($data) {
        sendEmail($data['toEmail'], $data['toName'], $data['subject'], $data['body'], $data['altBody'] ?? '');
    }
}
