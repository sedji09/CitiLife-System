<?php

/**
 * Route declarations for Citilife-System
 */

// Root URL (Redirects to dashboard or login via middleware)
$router->get('/', 'App\Controllers\LandingController@index', []);
$router->post('/', 'App\Controllers\LandingController@index', []);
$router->get('/index.php', 'App\Controllers\LandingController@index', []);
$router->post('/index.php', 'App\Controllers\LandingController@index', []);

// Google Search Console & SEO Routes
$router->get('/google210f5a5117fa0e42.html', function () {
    header('Content-Type: text/html; charset=UTF-8');
    echo "google-site-verification: google210f5a5117fa0e42.html\n";
    exit;
}, []);

$router->get('/robots.txt', function () {
    header('Content-Type: text/plain; charset=UTF-8');
    $file = __DIR__ . '/public/robots.txt';
    if (file_exists($file)) {
        readfile($file);
    } else {
        echo "User-agent: *\nAllow: /\nDisallow: /admin_central/\nDisallow: /branch_admin/\nDisallow: /staff/\nDisallow: /patient/\nSitemap: https://citilife-system-production-a5e9.up.railway.app/sitemap.xml\n";
    }
    exit;
}, []);

$router->get('/sitemap.xml', function () {
    header('Content-Type: application/xml; charset=UTF-8');
    $file = __DIR__ . '/public/sitemap.xml';
    if (file_exists($file)) {
        readfile($file);
    }
    exit;
}, []);

$router->get('/run-align-700-cases', function () {
    header('Content-Type: text/plain; charset=UTF-8');
    require_once __DIR__ . '/scripts/align_700_cases_and_patients.php';
    exit;
}, []);

// Authentication Routes (Guest Only)
$router->get('/login', function () {
    return redirect(url('?login=1'));
}, ['guest']);
$router->post('/login', function () {
    return redirect(url('?login=1'));
}, ['guest']);
$router->get('/login.php', function () {
    return redirect(url('?login=1'));
}, ['guest']);
$router->post('/login.php', function () {
    return redirect(url('?login=1'));
}, ['guest']);

$router->get('/staff-portal', 'App\Controllers\AuthController@login', ['guest']);
$router->post('/staff-portal', 'App\Controllers\AuthController@login', ['guest']);
$router->get('/staff-portal.php', 'App\Controllers\AuthController@login', ['guest']);
$router->post('/staff-portal.php', 'App\Controllers\AuthController@login', ['guest']);

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

$router->get('/set-password', 'App\Controllers\AuthController@resetPassword', []);
$router->post('/set-password', 'App\Controllers\AuthController@resetPassword', []);
$router->get('/set-password.php', 'App\Controllers\AuthController@resetPassword', []);
$router->post('/set-password.php', 'App\Controllers\AuthController@resetPassword', []);

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
    'case-review',
    'patient-history',
    'patient-records-history',
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
    'payment-verifications',
    'correction-requests',
    'correction-request'
];

foreach ($dashboardPages as $page) {
    $router->get('/' . $page, 'App\Controllers\PageController@dispatch', ['auth']);
    $router->post('/' . $page, 'App\Controllers\PageController@dispatch', ['auth']);
}

// Redirect legacy /patient-queue to unified /worklist
$router->get('/patient-queue', function () {
    require_once __DIR__ . '/config/database.php';
    $branchId = $_GET['branch_id'] ?? 0;
    $branchName = $_GET['branch'] ?? '';
    if (empty($branchName) && !empty($branchId)) {
        $bModel = new \BranchModel($pdo);
        $b = $bModel->getBranchById((int) $branchId);
        if (!empty($b['name'])) {
            $branchName = $b['name'];
        }
    }
    $highlight = $_GET['highlight'] ?? $_GET['highlight_case'] ?? $_GET['case_id'] ?? '';
    $params = [];
    if (!empty($branchName)) {
        $params[] = 'branch=' . urlencode($branchName);
    }
    if (!empty($highlight)) {
        $params[] = 'highlight_case=' . urlencode($highlight);
    }
    $query = !empty($params) ? '?' . implode('&', $params) : '';
    redirect(url('worklist' . $query));
}, ['auth']);

// Redirect legacy /xray-status to dashboard
$router->get('/xray-status', function () {
    redirect(url('dashboard'));
}, ['auth']);

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
$router->get('/image', 'App\Controllers\ImageController@view', ['auth']);
$router->get('/test-env', function () {
    $config_path = __DIR__ . '/config/smtp.php';
    $config = require $config_path;
    echo "SERVER: " . ($_SERVER['BREVO_API_KEY'] ?? 'NONE') . "<br>";
    echo "ENV: " . ($_ENV['BREVO_API_KEY'] ?? 'NONE') . "<br>";
    echo "GETENV: " . (getenv('BREVO_API_KEY') ?: 'NONE') . "<br>";
    echo "CONFIG: " . (!empty($config['brevo_api_key']) ? 'YES' : 'NO') . "<br>";
});
$router->get('/test-email', 'app/Api/test_email.php');

$router->get('/radtech/patient-registration', 'App\Controllers\radtech\PatientRegistrationController@handle', ['auth']);
$router->post('/radtech/patient-registration', 'App\Controllers\radtech\PatientRegistrationController@handle', ['auth']);
$router->post('/radtech/re-edit-case', 'App\Controllers\radtech\ReEditController@handle', ['auth']);
$router->post('/app/api/re_edit_case.php', 'App\Controllers\radtech\ReEditController@handle', ['auth']);
$router->get('/app/api/messages.php', 'app/Api/messages.php');
$router->post('/app/api/messages.php', 'app/Api/messages.php');
$router->get('/app/Api/messages.php', 'app/Api/messages.php');
$router->post('/app/Api/messages.php', 'app/Api/messages.php');
$router->post('/app/api/update_profile.php', 'app/Api/update_profile.php');
$router->post('/app/Api/update_profile.php', 'app/Api/update_profile.php');
$router->post('/app/api/request_password_reset.php', 'app/Api/request_password_reset.php');
$router->post('/app/Api/request_password_reset.php', 'app/Api/request_password_reset.php');
$router->post('/app/api/cancel_case.php', 'app/Api/cancel_case.php');
$router->post('/app/Api/cancel_case.php', 'app/Api/cancel_case.php');
$router->post('/app/api/submit_payment.php', 'app/Api/submit_payment.php');
$router->post('/app/Api/submit_payment.php', 'app/Api/submit_payment.php');
$router->post('/app/api/submit_feedback.php', 'app/Api/submit_feedback.php');
$router->post('/app/Api/submit_feedback.php', 'app/Api/submit_feedback.php');
$router->post('/app/api/send_email_change_otp.php', 'app/Api/send_email_change_otp.php');
$router->post('/app/Api/send_email_change_otp.php', 'app/Api/send_email_change_otp.php');
$router->post('/app/api/verify_email_change_otp.php', 'app/Api/verify_email_change_otp.php');
$router->post('/app/Api/verify_email_change_otp.php', 'app/Api/verify_email_change_otp.php');
$router->post('/config/update_patient.php', 'config/update_patient.php');
$router->get('/app/api/check_philhealth.php', 'app/Api/check_philhealth.php');
$router->get('/app/Api/check_philhealth.php', 'app/Api/check_philhealth.php');
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
$router->get('/app/api/radiologists_status.php', 'app/Api/radiologists_status.php');
$router->get('/app/api/session_ping.php', 'app/Api/session_ping.php');
$router->post('/app/api/session_ping.php', 'app/Api/session_ping.php');
$router->get('/session-ping', 'app/Api/session_ping.php');
$router->post('/session-ping', 'app/Api/session_ping.php');

$router->get('/migrate', 'app/Api/migrate.php');
$router->get('/app/api/migrate', 'app/Api/migrate.php');
$router->get('/app/Api/migrate', 'app/Api/migrate.php');

// Automated Database Backup Cron Endpoint
$router->get('/api/cron/backup', 'app/Api/cron_backup.php');
$router->post('/api/cron/backup', 'app/Api/cron_backup.php');
$router->get('/api/cron-backup', 'app/Api/cron_backup.php');
$router->post('/api/cron-backup', 'app/Api/cron_backup.php');
$router->get('/app/api/cron_backup.php', 'app/Api/cron_backup.php');
$router->post('/app/api/cron_backup.php', 'app/Api/cron_backup.php');


$router->get('/system-health', function () {
    global $pdo;
    header('Content-Type: application/json');
    $status = ['status' => 'ok', 'tables' => []];
    $tables = ['users', 'patients', 'requests', 'cases', 'notifications', 'messages', 'payments', 'branches', 'xray_services'];
    foreach ($tables as $t) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) FROM `{$t}`");
            $status['tables'][$t] = ['exists' => true, 'count' => (int) $stmt->fetchColumn()];
        } catch (\Throwable $e) {
            error_log("System health check error on table {$t}: " . $e->getMessage());
            $status['tables'][$t] = ['exists' => false];
        }
    }
    try {
        $stmt = $pdo->query("SELECT role, COUNT(*) as count FROM users GROUP BY role");
        $status['users_by_role'] = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    } catch (\Throwable $e) {
        error_log("System health check users error: " . $e->getMessage());
        $status['users_by_role'] = ['status' => 'unavailable'];
    }
    try {
        $stmt = $pdo->query("SELECT role, COUNT(*) as count FROM notifications GROUP BY role");
        $status['notifications_by_role'] = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    } catch (\Throwable $e) {
        error_log("System health check notifs error: " . $e->getMessage());
        $status['notifications_by_role'] = ['status' => 'unavailable'];
    }
    echo json_encode($status, JSON_PRETTY_PRINT);
    exit;
});

// Fallback route for Tailwind CSS on Railway where DocumentRoot is public
$router->get('/debug-router', function () {
    echo "URI: " . $_SERVER['REQUEST_URI'] . "<br>";
    echo "PROJECT_DIR: " . PROJECT_DIR . "<br>";
    echo "SCRIPT_NAME: " . $_SERVER['SCRIPT_NAME'] . "<br>";
    echo "DOCUMENT_ROOT: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
    exit;
});

$router->get('/tailwind/src/output.css', function () {
    $file = basePath('tailwind/src/output.css');
    if (file_exists($file)) {
        header('Content-Type: text/css');
        readfile($file);
    } else {
        header("HTTP/1.0 404 Not Found");
        echo "CSS not found";
    }
});

// Test / Preview Error Pages
$router->get('/test-error/403', function () use ($router) {
    $router->error(403); });
$router->get('/test-error/404', function () use ($router) {
    $router->error(404); });
$router->get('/test-error/500', function () use ($router) {
    $router->error(500); });
$router->get('/test-error/503', function () use ($router) {
    $router->error(503); });

// Secure Authenticated Medical File Streamer
$router->get('/image', 'App\Controllers\ImageController@view', ['auth']);
$router->get('/cases/image', 'App\Controllers\ImageController@view', ['auth']);
$router->get('/secure-file', 'App\Controllers\ImageController@view', ['auth']);







