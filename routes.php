<?php

/**
 * Route declarations for CitiLife-System
 */

// Root URL (Redirects to dashboard or login via middleware)
$router->get('/', 'App\Controllers\LandingController@index', []);
$router->post('/', 'App\Controllers\LandingController@index', []);
$router->get('/index.php', 'App\Controllers\LandingController@index', []);
$router->post('/index.php', 'App\Controllers\LandingController@index', []);

// Authentication Routes (Guest Only)
$router->get('/login', 'App\Controllers\AuthController@login', ['guest']);
$router->post('/login', 'App\Controllers\AuthController@login', ['guest']);
$router->get('/login.php', 'App\Controllers\AuthController@login', ['guest']);
$router->post('/login.php', 'App\Controllers\AuthController@login', ['guest']);

$router->get('/patient-login', 'App\Controllers\AuthController@patientLogin', ['guest']);
$router->post('/patient-login', 'App\Controllers\AuthController@patientLogin', ['guest']);
$router->get('/patient-login.php', 'App\Controllers\AuthController@patientLogin', ['guest']);
$router->post('/patient-login.php', 'App\Controllers\AuthController@patientLogin', ['guest']);

$router->get('/patient-signup', 'App\Controllers\AuthController@patientSignup', ['guest']);
$router->post('/patient-signup', 'App\Controllers\AuthController@patientSignup', ['guest']);
$router->get('/patient-signup.php', 'App\Controllers\AuthController@patientSignup', ['guest']);
$router->post('/patient-signup.php', 'App\Controllers\AuthController@patientSignup', ['guest']);

$router->get('/forgot-password', 'App\Controllers\AuthController@forgotPassword', ['guest']);
$router->post('/forgot-password', 'App\Controllers\AuthController@forgotPassword', ['guest']);
$router->get('/forgot-password.php', 'App\Controllers\AuthController@forgotPassword', ['guest']);
$router->post('/forgot-password.php', 'App\Controllers\AuthController@forgotPassword', ['guest']);

$router->get('/reset-password', 'App\Controllers\AuthController@resetPassword', []);
$router->post('/reset-password', 'App\Controllers\AuthController@resetPassword', []);
$router->get('/reset-password.php', 'App\Controllers\AuthController@resetPassword', []);
$router->post('/reset-password.php', 'App\Controllers\AuthController@resetPassword', []);

$router->get('/verify', 'App\Controllers\AuthController@verify', ['guest']);
$router->post('/verify', 'App\Controllers\AuthController@verify', ['guest']);
$router->get('/verify.php', 'App\Controllers\AuthController@verify', ['guest']);
$router->post('/verify.php', 'App\Controllers\AuthController@verify', ['guest']);

$router->get('/otp-login', 'App\Controllers\AuthController@otpLogin', ['guest']);
$router->post('/otp-login', 'App\Controllers\AuthController@otpLogin', ['guest']);
$router->get('/otp-login.php', 'App\Controllers\AuthController@otpLogin', ['guest']);
$router->post('/otp-login.php', 'App\Controllers\AuthController@otpLogin', ['guest']);

// Logout Route (Auth required)
$router->get('/logout', 'App\Controllers\AuthController@logout');
$router->get('/logout.php', 'App\Controllers\AuthController@logout');

// Privacy accept route
$router->post('/accept-privacy', 'App\Controllers\AuthController@acceptPrivacy', ['auth']);

// Whitelisted dashboard pages (routed dynamically to PageController)
$dashboardPages = [
    'dashboard',
    'patient-registration',
    'patient-lists',
    'report-ready',
    'check-record-request',
    'patient-approval',
    'xray-patient-records',
    'record-request',
    'view-record-request',
    'patient-details',
    'records-history',
    'worklist',
    'patient-queue',
    'case-review',
    'patient-history',
    'patient-records-history',
    'xray-status',
    'case-status',
    'my-records',
    'registration',
    'download-report',
    'view-report',
    'patient-approvals',
    'record-requests',
    'branch-xray-cases',
    'reports',
    'users',
    'branches',
    'patient-records',
    'audit-logs',
    'user-role-settings',
    'settings',
    'security-settings',
    'backup-maintenance',
    'print-report',
    'feedback',
    'service-pricing',
    'services-pricing',
    'payment-verifications'
];

foreach ($dashboardPages as $page) {
    $router->get('/' . $page, 'App\Controllers\PageController@dispatch', ['auth']);
    $router->post('/' . $page, 'App\Controllers\PageController@dispatch', ['auth']);
}

// Legacy API Endpoints (mapped to app/api for absolute JS compatibility)
$router->get('/app/api/case_activity.php', 'app/Api/case_activity.php');
$router->post('/app/api/case_activity.php', 'app/Api/case_activity.php');
$router->get('/app/api/notifications.php', 'app/Api/notifications.php');
$router->post('/app/api/notifications.php', 'app/Api/notifications.php');
$router->get('/app/api/active_users_count.php', 'app/Api/active_users_count.php');
$router->get('/app/api/search_branch_cases.php', 'app/Api/search_branch_cases.php');
$router->post('/app/api/search_branch_cases.php', 'app/Api/search_branch_cases.php');
$router->get('/branch-dashboard', 'auth/branch-dashboard.php');
$router->get('/patient-dashboard', 'auth/patient-dashboard.php');
$router->get('/image', 'ImageController@serve');
$router->get('/test-env', function() {
    $config_path = __DIR__ . '/config/smtp.php';
    $config = require $config_path;
    echo "SERVER: " . ($_SERVER['BREVO_API_KEY'] ?? 'NONE') . "<br>";
    echo "ENV: " . ($_ENV['BREVO_API_KEY'] ?? 'NONE') . "<br>";
    echo "GETENV: " . (getenv('BREVO_API_KEY') ?: 'NONE') . "<br>";
    echo "CONFIG: " . (!empty($config['brevo_api_key']) ? 'YES' : 'NO') . "<br>";
});
$router->get('/test-email', 'app/Api/test_email.php');

$router->get('/radtech/patient-registration', 'radtech/PatientRegistrationController@index');
$router->get('/app/api/messages.php', 'app/Api/messages.php');
$router->post('/app/api/messages.php', 'app/Api/messages.php');
$router->post('/app/api/update_profile.php', 'app/Api/update_profile.php');
$router->post('/app/api/request_password_reset.php', 'app/Api/request_password_reset.php');
$router->post('/app/api/cancel_case.php', 'app/Api/cancel_case.php');
$router->post('/app/api/submit_payment.php', 'app/Api/submit_payment.php');
$router->post('/app/api/submit_feedback.php', 'app/Api/submit_feedback.php');
$router->post('/app/api/send_email_change_otp.php', 'app/Api/send_email_change_otp.php');
$router->post('/app/api/verify_email_change_otp.php', 'app/Api/verify_email_change_otp.php');
$router->post('/app/config/update_patient.php', 'app/config/update_patient.php');
$router->get('/app/api/disputes.php', 'app/Api/disputes.php');
$router->post('/app/api/disputes.php', 'app/Api/disputes.php');
$router->get('/App/Api/disputes.php', 'app/Api/disputes.php');
$router->post('/App/Api/disputes.php', 'app/Api/disputes.php');

// Additional missing APIs that were working on localhost directly but failing on Railway router
$router->get('/app/api/notifications.php', 'app/Api/notifications.php');
$router->post('/app/api/notifications.php', 'app/Api/notifications.php');
$router->get('/app/api/case_activity.php', 'app/Api/case_activity.php');
$router->post('/app/api/case_activity.php', 'app/Api/case_activity.php');
$router->get('/app/api/search_branch_cases.php', 'app/Api/search_branch_cases.php');
$router->post('/app/api/search_branch_cases.php', 'app/Api/search_branch_cases.php');
$router->get('/app/api/active_users_count.php', 'app/Api/active_users_count.php');

// Fallback route for Tailwind CSS on Railway where DocumentRoot is public
$router->get('/debug-router', function() {
    echo "URI: " . $_SERVER['REQUEST_URI'] . "<br>";
    echo "PROJECT_DIR: " . PROJECT_DIR . "<br>";
    echo "SCRIPT_NAME: " . $_SERVER['SCRIPT_NAME'] . "<br>";
    echo "DOCUMENT_ROOT: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
    exit;
});

$router->get('/tailwind/src/output.css', function() {
    $file = basePath('tailwind/src/output.css');
    if (file_exists($file)) {
        header('Content-Type: text/css');
        readfile($file);
    } else {
        header("HTTP/1.0 404 Not Found");
        echo "CSS not found";
    }
});

