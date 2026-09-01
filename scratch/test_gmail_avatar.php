<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/Helpers/email_template_helper.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'citilifediagnosticcenter26@gmail.com';
    $mail->Password   = 'vimk temr ldcc menb';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    $mail->setFrom('citilifediagnosticcenter26@gmail.com', 'Citilife Diagnostic Center');
    $mail->addAddress('seigipascual09@gmail.com', 'Seigi Pascual');

    $mail->isHTML(true);
    $mail->Subject = 'Test Direct Gmail SMTP Profile Avatar';
    $mail->Body    = renderOtpEmail(
        'Admin User',
        '777888',
        'authentication',
        15,
        'Direct Gmail SMTP Avatar Test',
        "Testing if Gmail native profile picture appears without Brevo relay."
    );

    $mail->send();
    echo "SUCCESS: Sent via direct Gmail SMTP!\n";
} catch (Exception $e) {
    echo "ERROR: " . $mail->ErrorInfo . "\n";
}
