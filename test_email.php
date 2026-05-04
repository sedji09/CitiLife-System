<?php
require_once __DIR__ . '/app/helpers/mailer_helper.php';

echo "<h2>Testing Email System</h2>";

// Take destination email from GET parameter
$to = $_GET['to'] ?? '';

if (empty($to)) {
    die("<p>Please provide an email to test via GET parameter 'to'.</p><p>Example: <b>http://localhost/CitiLife-System/test_email.php?to=your@email.com</b></p>");
}

echo "<p>Attempting to send a test email to: <b>$to</b>...</p>";

$subject = "CitiLife System - Test Email";
$body = "<h2>Hello!</h2><p>This is a test email to verify that PHPMailer and SMTP are working correctly in your system.</p>";

$result = sendEmail($to, "Test User", $subject, $body);

if ($result) {
    echo "<h3 style='color:green;'>Success!</h3><p>Email was sent successfully. Please check your inbox (and spam folder).</p>";
} else {
    echo "<h3 style='color:red;'>Failed.</h3><p>Check your error logs or ensure your SMTP config in <code>app/config/smtp.php</code> is correct.</p>";
}
