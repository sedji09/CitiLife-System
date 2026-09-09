<?php

namespace App\Controllers;

class LandingController
{
    public function index()
    {
        // Forward POST requests to PageController to preserve submitted form payload
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['page'])) {
            return (new \App\Controllers\PageController())->dispatch();
        }

        // Intercept legacy query string routes from notifications (e.g. index.php?role=patient&page=xray-status&case_id=318)
        // and redirect them to the new clean URLs mapping to the router.
        if (isset($_GET['page'])) {
            $page = $_GET['page'];
            if ($page === 'xray-status') {
                $page = 'dashboard';
            }
            
            // Reconstruct query parameters
            $queryParams = $_GET;
            unset($queryParams['page']);
            
            $queryString = !empty($queryParams) ? '?' . http_build_query($queryParams) : '';
            $url = url($page . $queryString);
            
            redirect($url);
        }

        // Simply load the public landing view.
        // It does not use the central dashboard layout because it's a standalone public page.
        loadView('pages/public/landing');
    }
}

