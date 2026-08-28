<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../Helpers/mailer_helper.php';

header('Content-Type: text/plain');

if (file_exists(__DIR__ . '/../../env.php')) {
    require_once __DIR__ . '/../../env.php';
}

$config_path = __DIR__ . '/../../config/smtp.php';
if (!file_exists($config_path)) {
    die("SMTP config not found");
}
$config = require $config_path;

echo "Starting Mail Test...\n";
echo "Sender Email: " . $config['from_email'] . "\n";
echo "Brevo API Key Exists: " . (!empty($config['brevo_api_key']) ? 'YES' : 'NO') . "\n";

$result = sendEmail(
    $config['from_email'], 
    'Test Recipient', 
    'Brevo API Test', 
    '<h1>Success!</h1><p>Your email system is now working via Brevo API!</p>'
);

if ($result) {
    echo "\n\nSUCCESS! Email sent to " . $config['from_email'] . ".\nCheck your inbox/spam folder.";
} else {
    echo "\n\nFAILED! Please check the server error logs for details.";
}
