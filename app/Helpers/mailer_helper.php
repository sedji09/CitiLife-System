<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Ensure Composer autoloader is loaded
$autoloader_path = __DIR__ . '/../../vendor/autoload.php';
if (file_exists($autoloader_path)) {
    require_once $autoloader_path;
}

require_once __DIR__ . '/email_template_helper.php';

/**
 * Send an email using PHPMailer
 *
 * @param string $toEmail Recipient email address
 * @param string $toName Recipient name
 * @param string $subject Email subject
 * @param string $body Email body (HTML supported)
 * @param string $altBody Plain text alternative body
 * @return bool True if sent, false on error
 */
if (!function_exists('sendEmail')) {
    function sendEmail($toEmail, $toName, $subject, $body, $altBody = '') {
    $config_path = __DIR__ . '/../../config/smtp.php';
    if (!file_exists($config_path)) {
        error_log("SMTP config not found at {$config_path}");
        return false;
    }
    
    $config = require $config_path;

    $logoPath = __DIR__ . '/../../public/assets/img/logo/citilife-logo.png';

    // 1. Try Direct Gmail SMTP first (ensures official Google Profile Avatar is shown to patients)
    if (!empty($config['username']) && !empty($config['password'])) {
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = $config['host'] ?: 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = $config['username'];
            $mail->Password   = $config['password'];
            $mail->SMTPSecure = ($config['encryption'] === 'ssl' || $config['port'] == 465) 
                ? PHPMailer::ENCRYPTION_SMTPS 
                : PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = $config['port'] ?: 587;
            $mail->Timeout    = 10;

            // SMTPOptions for local/self-signed cert compatibility
            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );

            // Recipients
            $mail->setFrom($config['from_email'], $config['from_name']);
            $mail->addAddress($toEmail, $toName);

            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $body;
            $mail->AltBody = $altBody ?: strip_tags($body);

            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Gmail SMTP failed: {$mail->ErrorInfo}. Trying Brevo fallback if available...");
        }
    }

    // 2. Brevo API fallback (if Gmail SMTP fails or not configured)
    if (!empty($config['brevo_api_key'])) {
        $data = [
            'sender' => ['name' => $config['from_name'], 'email' => $config['from_email']],
            'to' => [['email' => $toEmail, 'name' => $toName]],
            'subject' => $subject,
            'htmlContent' => $body,
            'textContent' => $altBody ?: strip_tags($body)
        ];

        $apiUrl = 'https://api.brevo.com/v3/smtp/email';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $headers = [
            'accept: application/json',
            'api-key: ' . $config['brevo_api_key'],
            'content-type: application/json'
        ];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $result = curl_exec($ch);
        
        $response = json_decode($result, true);
        if (isset($response['messageId'])) {
            return true;
        } else {
            error_log("Brevo API Error: " . $result);
            return false;
        }
    }

    return false;
}
}

if (!function_exists('sendEmailAsync')) {
    function sendEmailAsync($toEmail, $toName, $subject, $body, $altBody = '') {
        $data = [
            'toEmail' => $toEmail,
            'toName' => $toName,
            'subject' => $subject,
            'body' => $body,
            'altBody' => $altBody
        ];
        
        $payload = base64_encode(json_encode($data));
        $scriptPath = __DIR__ . '/background_mailer.php';
        
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            pclose(popen("start /B php \"$scriptPath\" \"$payload\" 2>nul >nul", "r"));
        } else {
            exec("php \"$scriptPath\" \"$payload\" > /dev/null 2>&1 &");
        }
    }
}
