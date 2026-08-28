<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Ensure Composer autoloader is loaded
$autoloader_path = __DIR__ . '/../../vendor/autoload.php';
if (file_exists($autoloader_path)) {
    require_once $autoloader_path;
}

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

    // Use Brevo API if configured
    if (!empty($config['brevo_api_key'])) {
        $data = [
            'sender' => ['name' => $config['from_name'], 'email' => $config['from_email']],
            'to' => [['email' => $toEmail, 'name' => $toName]],
            'subject' => $subject,
            'htmlContent' => $body,
            'textContent' => $altBody ?: strip_tags($body)
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.brevo.com/v3/smtp/email');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        
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

    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = gethostbyname($config['host']); // Force IPv4
        $mail->SMTPAuth   = true;
        $mail->Username   = $config['username'];
        $mail->Password   = $config['password'];
        $mail->SMTPSecure = $config['encryption'] === 'tls' ? PHPMailer::ENCRYPTION_STARTTLS : PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = $config['port'];
        $mail->Timeout    = 10;
        
        // Disable SSL verification for forced IP
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
        error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
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
