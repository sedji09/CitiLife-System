<?php
require_once __DIR__ . '/mailer_helper.php';

// Accept JSON payload from CLI arg
if ($argc > 1) {
    $data = json_decode(base64_decode($argv[1]), true);
    if ($data) {
        sendEmail($data['toEmail'], $data['toName'], $data['subject'], $data['body'], $data['altBody'] ?? '');
    }
}
