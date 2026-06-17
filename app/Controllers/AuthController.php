<?php

namespace App\Controllers;

class AuthController
{
    public function login()
    {
        global $pdo;
        require basePath('app/Controllers/auth/login.php');
    }

    public function patientLogin()
    {
        global $pdo;
        require basePath('app/Controllers/auth/patient-login.php');
    }

    public function patientSignup()
    {
        global $pdo;
        require basePath('app/Controllers/auth/patient-signup.php');
    }

    public function forgotPassword()
    {
        global $pdo;
        require basePath('app/Controllers/auth/forgot-password.php');
    }

    public function resetPassword()
    {
        global $pdo;
        require basePath('app/Controllers/auth/reset-password.php');
    }

    public function verify()
    {
        global $pdo;
        require basePath('app/Controllers/auth/verify.php');
    }

    public function otpLogin()
    {
        global $pdo;
        require basePath('app/Controllers/auth/otp-login.php');
    }

    public function logout()
    {
        global $pdo;
        require basePath('app/Controllers/auth/logout.php');
    }

    public function acceptPrivacy()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['data_privacy_accepted'] = true;
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;
    }
}
