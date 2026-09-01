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

$testBody = renderOtpEmail(
    'Admin User',
    '569209',
    'authentication',
    15,
    'Please verify your identity, <strong>Admin User</strong>',
    "You're receiving this email because a verification code was requested for your Citilife account."
);

$result = sendEmail(
    $config['from_email'], 
    'Test Recipient', 
    'Citilife Email System Test', 
    $testBody
);

if ($result) {
    echo "\n\nSUCCESS! Email sent to " . $config['from_email'] . ".\nCheck your inbox/spam folder.";
} else {
    echo "\n\nFAILED! Please check the server error logs for details.";
}
