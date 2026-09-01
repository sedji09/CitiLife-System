<?php

if (file_exists(__DIR__ . '/../env.php')) {
    require_once __DIR__ . '/../env.php';
}

return [
    'host' => $_SERVER['SMTP_HOST'] ?? $_ENV['SMTP_HOST'] ?? getenv('SMTP_HOST') ?: 'smtp.gmail.com',
    'username' => $_SERVER['SMTP_USERNAME'] ?? $_ENV['SMTP_USERNAME'] ?? getenv('SMTP_USERNAME') ?: 'citilifediagnosticcenter26@gmail.com',
    'password' => $_SERVER['SMTP_PASSWORD'] ?? $_ENV['SMTP_PASSWORD'] ?? getenv('SMTP_PASSWORD') ?: '',
    'port' => $_SERVER['SMTP_PORT'] ?? $_ENV['SMTP_PORT'] ?? getenv('SMTP_PORT') ?: 465,
    'encryption' => $_SERVER['SMTP_ENCRYPTION'] ?? $_ENV['SMTP_ENCRYPTION'] ?? getenv('SMTP_ENCRYPTION') ?: 'ssl',
    'from_email' => $_SERVER['SMTP_FROM_EMAIL'] ?? $_ENV['SMTP_FROM_EMAIL'] ?? getenv('SMTP_FROM_EMAIL') ?: 'citilifediagnosticcenter26@gmail.com',
    'from_name' => $_SERVER['SMTP_FROM_NAME'] ?? $_ENV['SMTP_FROM_NAME'] ?? getenv('SMTP_FROM_NAME') ?: 'Citilife Diagnostic Center',
    'brevo_api_key' => $_SERVER['BREVO_API_KEY'] ?? $_ENV['BREVO_API_KEY'] ?? getenv('BREVO_API_KEY') ?: '',
];
