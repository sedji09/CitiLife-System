<?php

return [
    'host' => $_SERVER['SMTP_HOST'] ?? getenv('SMTP_HOST') ?: 'smtp.gmail.com',
    'username' => $_SERVER['SMTP_USERNAME'] ?? getenv('SMTP_USERNAME') ?: 'citilifediagnosticcenter26@gmail.com',
    'password' => $_SERVER['SMTP_PASSWORD'] ?? getenv('SMTP_PASSWORD') ?: '',
    'port' => $_SERVER['SMTP_PORT'] ?? getenv('SMTP_PORT') ?: 465,
    'encryption' => $_SERVER['SMTP_ENCRYPTION'] ?? getenv('SMTP_ENCRYPTION') ?: 'ssl',
    'from_email' => $_SERVER['SMTP_FROM_EMAIL'] ?? getenv('SMTP_FROM_EMAIL') ?: 'citilifediagnosticcenter26@gmail.com',
    'from_name' => $_SERVER['SMTP_FROM_NAME'] ?? getenv('SMTP_FROM_NAME') ?: 'CitiLife Diagnostic Center',
];
