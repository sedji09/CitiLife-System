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
}
