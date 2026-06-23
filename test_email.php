<?php
require_once __DIR__ . '/app/Helpers/mailer_helper.php';
$result = sendEmail('citilifediagnosticcenter26@gmail.com', 'Test User', 'Test Subject', '<h1>Test HTML</h1><p>This is a test</p>');
var_dump($result);
