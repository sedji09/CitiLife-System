<?php

return [
    'host' => 'smtp.gmail.com',
    'username' => 'citilifediagnosticcenter26@gmail.com',
    'password' => 'vimk temr ldcc menb',
    'port' => 465,
    'encryption' => 'ssl',
    'from_email' => 'seigipascual09@gmail.com',
    'from_name' => 'CitiLife Diagnostic Center',
    'brevo_api_key' => $_SERVER['BREVO_API_KEY'] ?? $_ENV['BREVO_API_KEY'] ?? getenv('BREVO_API_KEY') ?: '',
];
