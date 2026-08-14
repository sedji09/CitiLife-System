<?php

namespace App\Controllers;

class LandingController
{
    public function index()
    {
        // Intercept legacy query string routes from notifications (e.g. index.php?role=patient&page=xray-status&case_id=318)
        // and redirect them to the new clean URLs mapping to the router.
        if (isset($_GET['page'])) {
            $page = $_GET['page'];
            
            // Build the new URL path
            $url = '/' . (defined('PROJECT_DIR') ? PROJECT_DIR : 'CitiLife-System') . '/' . $page;
            
            // Reconstruct the query string without 'page' and 'role' if needed, or just append everything except 'page'
            $queryParams = $_GET;
            unset($queryParams['page']);
            
            if (!empty($queryParams)) {
                $url .= '?' . http_build_query($queryParams);
            }
            
            header("Location: $url");
            exit;
        }

        // Simply load the public landing view.
        // It does not use the central dashboard layout because it's a standalone public page.
        loadView('pages/public/landing');
    }
}

