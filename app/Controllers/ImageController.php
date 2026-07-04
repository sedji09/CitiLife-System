<?php

namespace App\Controllers;

use Framework\Router;

class ImageController
{
    /**
     * Handles secure viewing of uploaded images
     */
    public function view()
    {
        // Must be logged in (also handled by 'auth' middleware)
        if (!isset($_SESSION['user_id'])) {
            header('HTTP/1.0 403 Forbidden');
            echo "Forbidden.";
            return;
        }

        if (!isset($_GET['file']) || empty($_GET['file'])) {
            header('HTTP/1.0 400 Bad Request');
            echo "Bad Request.";
            return;
        }

        $fileRequested = $_GET['file'];

        // Prevent directory traversal attacks
        if (strpos($fileRequested, '..') !== false) {
            header('HTTP/1.0 400 Bad Request');
            echo "Bad Request.";
            return;
        }

        // The exact base path
        $basePath = __DIR__ . '/../../';

        // 1. Try modern storage/cases path (Preferred)
        $storagePath = $basePath . 'storage/cases/' . basename($fileRequested);
        
        // 2. Try the legacy path (Backward Compatibility)
        // Note: the fileRequested might already contain 'public/assets/uploads/cases/' or 'storage/cases/' from the DB
        $dbPath = $basePath . ltrim($fileRequested, '/');

        $finalPath = '';

        if (file_exists($dbPath)) {
            $finalPath = $dbPath;
        } elseif (file_exists($storagePath)) {
            $finalPath = $storagePath;
        } else {
            // Also try legacy exact path just in case
            $legacyPath = $basePath . 'public/assets/uploads/cases/' . basename($fileRequested);
            if (file_exists($legacyPath)) {
                $finalPath = $legacyPath;
            }
        }

        if (empty($finalPath) || !file_exists($finalPath)) {
            header('HTTP/1.0 404 Not Found');
            echo "File not found.";
            return;
        }

        // Determine content type
        $mimeType = mime_content_type($finalPath);
        if (!$mimeType) {
            $ext = strtolower(pathinfo($finalPath, PATHINFO_EXTENSION));
            if ($ext === 'jpg' || $ext === 'jpeg') $mimeType = 'image/jpeg';
            elseif ($ext === 'png') $mimeType = 'image/png';
            elseif ($ext === 'gif') $mimeType = 'image/gif';
            elseif ($ext === 'pdf') $mimeType = 'application/pdf';
            else $mimeType = 'application/octet-stream';
        }

        // Send headers
        header('Content-Type: ' . $mimeType);
        header('Content-Length: ' . filesize($finalPath));
        
        // Output file
        readfile($finalPath);
        exit();
    }
}
