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
if (!function_exists('sendViaBrevo')) {
    function sendViaBrevo($apiKey, $config, $toEmail, $toName, $subject, $body, $altBody = '') {
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
        curl_setopt($ch, CURLOPT_TIMEOUT, 6);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $headers = [
            'accept: application/json',
            'api-key: ' . $apiKey,
            'content-type: application/json'
        ];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        $response = json_decode($result, true);
        if ($httpCode >= 200 && $httpCode < 300 && isset($response['messageId'])) {
            return true;
        } else {
            error_log("Brevo API Error (HTTP {$httpCode}): " . $result);
            return false;
        }
    }
}

if (!function_exists('sendEmail')) {
    function sendEmail($toEmail, $toName, $subject, $body, $altBody = '') {
        $config_path = __DIR__ . '/../../config/smtp.php';
        if (!file_exists($config_path)) {
            error_log("SMTP config not found at {$config_path}");
            return false;
        }
        
        $config = require $config_path;

        $brevoKey = !empty($config['brevo_api_key']) 
            ? $config['brevo_api_key'] 
            : ($_SERVER['BREVO_API_KEY'] ?? $_ENV['BREVO_API_KEY'] ?? getenv('BREVO_API_KEY') ?: '');

        $isLocalhost = strpos($_SERVER['HTTP_HOST'] ?? 'localhost', 'localhost') !== false || strpos($_SERVER['HTTP_HOST'] ?? '', '127.0.0.1') !== false;

        // 1. On Production (Railway/Cloud), use Brevo API FIRST for instant delivery (<1s) and zero port-blocking issues
        if (!$isLocalhost && !empty($brevoKey)) {
            $sent = sendViaBrevo($brevoKey, $config, $toEmail, $toName, $subject, $body, $altBody);
            if ($sent) {
                return true;
            }
        }

        // 2. Try Direct Gmail SMTP (with fast 3s timeout to prevent hanging)
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
                $mail->Port       = $config['port'] ?: 465;
                $mail->Timeout    = 3; // Fast 3-second timeout

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
                error_log("Gmail SMTP failed: {$mail->ErrorInfo}. Trying Brevo fallback...");
            }
        }

        // 3. Brevo API fallback (if Gmail SMTP failed or on localhost)
        if (!empty($brevoKey)) {
            return sendViaBrevo($brevoKey, $config, $toEmail, $toName, $subject, $body, $altBody);
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
        
        try {
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                pclose(popen("start /B php \"$scriptPath\" \"$payload\" 2>nul >nul", "r"));
            } else {
                if (!function_exists('exec')) {
                    throw new Exception("exec is disabled");
                }
                exec("php \"$scriptPath\" \"$payload\" > /dev/null 2>&1 &");
            }
        } catch (\Throwable $e) {
            // Fallback to synchronous sending if background fails or exec is disabled
            sendEmail($toEmail, $toName, $subject, $body, $altBody);
        }
    }
}
