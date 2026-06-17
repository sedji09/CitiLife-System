<?php

/**
 * Route declarations for CitiLife-System
 */

// Root URL (Redirects to dashboard or login via middleware)
$router->get('/', 'App\Controllers\PageController@dispatch', ['auth']);
$router->post('/', 'App\Controllers\PageController@dispatch', ['auth']);
$router->get('/index.php', 'App\Controllers\PageController@dispatch', ['auth']);
$router->post('/index.php', 'App\Controllers\PageController@dispatch', ['auth']);

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
    'print-report'
];

foreach ($dashboardPages as $page) {
    $router->get('/' . $page, 'App\Controllers\PageController@dispatch', ['auth']);
    $router->post('/' . $page, 'App\Controllers\PageController@dispatch', ['auth']);
}

// Legacy API Endpoints (mapped to App/Api for absolute JS compatibility)
$router->get('/app/api/case_activity.php', 'App/Api/case_activity.php');
$router->post('/app/api/case_activity.php', 'App/Api/case_activity.php');
$router->get('/app/api/notifications.php', 'App/Api/notifications.php');
$router->post('/app/api/notifications.php', 'App/Api/notifications.php');
$router->get('/app/api/search_branch_cases.php', 'App/Api/search_branch_cases.php');
$router->post('/app/api/search_branch_cases.php', 'App/Api/search_branch_cases.php');
$router->get('/app/api/messages.php', 'App/Api/messages.php');
$router->post('/app/api/messages.php', 'App/Api/messages.php');
$router->post('/app/api/update_profile.php', 'App/Api/update_profile.php');
$router->post('/app/api/request_password_reset.php', 'App/Api/request_password_reset.php');
$router->post('/app/api/cancel_case.php', 'App/Api/cancel_case.php');
$router->post('/app/config/update_patient.php', 'config/update_patient.php');
