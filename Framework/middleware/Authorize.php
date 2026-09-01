<?php

namespace Framework\middleware;

use Framework\Session;

class Authorize
{
    /**
     * Check if user is authenticated
     *
     * @return bool
     */
    public function isAuthenticated()
    {
        return Session::has('user') || Session::has('role');
    }

    /**
     * Handle the user's request
     *
     * @param string $role
     * @return void
     */
    public function handle($role)
    {
        if ($role === 'guest' && $this->isAuthenticated()) {
            $userRole = Session::get('role') ?? ($_SESSION['role'] ?? null);
            $redirectTarget = $_GET['redirect'] ?? $_SESSION['redirect_url'] ?? null;
            if (!empty($redirectTarget) && $userRole === 'patient') {
                unset($_SESSION['redirect_url']);
                return redirect($redirectTarget);
            }
            return redirect('/' . PROJECT_DIR . '/dashboard');
        } elseif ($role === 'auth' && !$this->isAuthenticated()) {
            $currentUri = $_SERVER['REQUEST_URI'] ?? '';
            // If accessing patient pages or patient view report, route to patient-login with redirect
            if (
                strpos($currentUri, 'view-report') !== false ||
                strpos($currentUri, 'my-records') !== false ||
                strpos($currentUri, 'case-status') !== false ||
                strpos($currentUri, 'download-report') !== false ||
                strpos($currentUri, 'registration') !== false ||
                strpos($currentUri, 'services-pricing') !== false ||
                strpos($currentUri, 'feedback') !== false ||
                strpos($currentUri, 'patient') !== false
            ) {
                return redirect('/' . PROJECT_DIR . '/patient-login?redirect=' . urlencode($currentUri));
            }
            return redirect('/' . PROJECT_DIR . '/login');
        }
    }
}
