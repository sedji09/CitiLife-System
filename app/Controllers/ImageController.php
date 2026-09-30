<?php

namespace App\Controllers;

use Framework\Router;

class ImageController
{
    /**
     * Handles secure viewing of uploaded medical images and reports (Data Privacy RA 10173)
     */
    public function serve()
    {
        return $this->view();
    }

    public function view()
    {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        // Authentication Check: Must have active CitiLife session
        if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
            http_response_code(403);
            if (function_exists('basePath') && file_exists(basePath('views/errors/403.view.php'))) {
                require basePath('views/errors/403.view.php');
            } else {
                echo '<!DOCTYPE html><html><head><title>403 Forbidden</title><style>body{font-family:sans-serif;background:#0f172a;color:#f8fafc;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;}div{text-align:center;padding:32px;background:#1e293b;border-radius:16px;box-shadow:0 10px 25px rgba(0,0,0,0.5);max-width:400px;}h2{color:#ef4444;margin-top:0;}p{color:#94a3b8;font-size:14px;line-height:1.6;}</style></head><body><div><h2>Access Denied</h2><p>You must be authenticated with valid security permissions to view this diagnostic record.</p></div></body></html>';
            }
            exit();
        }

        $fileRequested = $_GET['file'] ?? ($_GET['secure_file'] ?? '');
        if (!empty($fileRequested)) {
            $fileRequested = urldecode($fileRequested);
        } else {
            $uri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
            if (preg_match('#/(?:public/)?uploads/(cases|reports|signatures|receipts)/(.*)$#i', $uri, $matches)) {
                $fileRequested = 'public/uploads/' . $matches[1] . '/' . urldecode($matches[2]);
            }
        }

        if (empty($fileRequested)) {
            http_response_code(400);
            header('Content-Type: text/plain');
            echo "Bad Request: No file specified.";
            exit();
        }

        // Prevent Directory Traversal & Null Byte Injections
        if (strpos($fileRequested, '..') !== false || strpos($fileRequested, "\0") !== false) {
            http_response_code(400);
            header('Content-Type: text/plain');
            echo "Bad Request: Invalid file path.";
            exit();
        }

        $basePath = dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR;
        $cleanRel = ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $fileRequested), DIRECTORY_SEPARATOR);

        // Candidates to resolve path reliably on Windows / Linux / Railway
        $candidates = [
            $basePath . $cleanRel,
            $basePath . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'cases' . DIRECTORY_SEPARATOR . basename($cleanRel),
            $basePath . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'reports' . DIRECTORY_SEPARATOR . basename($cleanRel),
            $basePath . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'signatures' . DIRECTORY_SEPARATOR . basename($cleanRel),
            $basePath . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'receipts' . DIRECTORY_SEPARATOR . basename($cleanRel),
            $basePath . 'storage' . DIRECTORY_SEPARATOR . 'cases' . DIRECTORY_SEPARATOR . basename($cleanRel),
            $basePath . 'storage' . DIRECTORY_SEPARATOR . 'reports' . DIRECTORY_SEPARATOR . basename($cleanRel)
        ];

        $finalPath = null;
        foreach ($candidates as $cand) {
            if (file_exists($cand) && is_file($cand)) {
                $finalPath = $cand;
                break;
            }
        }

        if (!$finalPath) {
            http_response_code(404);
            header('Content-Type: text/plain');
            echo "File not found.";
            exit();
        }

        // Prevent streaming of dangerous or executable scripts
        $ext = strtolower(pathinfo($finalPath, PATHINFO_EXTENSION));
        $dangerousExts = ['php', 'phtml', 'php3', 'php4', 'php5', 'php7', 'php8', 'phps', 'pl', 'py', 'cgi', 'sh', 'exe', 'bat', 'cmd', 'htaccess'];
        if (in_array($ext, $dangerousExts)) {
            http_response_code(403);
            header('Content-Type: text/plain');
            echo "Access Denied.";
            exit();
        }

        // Determine MIME type
        $mimeTypes = [
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png'  => 'image/png',
            'gif'  => 'image/gif',
            'webp' => 'image/webp',
            'svg'  => 'image/svg+xml',
            'pdf'  => 'application/pdf',
            'dcm'  => 'application/dicom',
            'zip'  => 'application/zip',
            'txt'  => 'text/plain'
        ];

        $mimeType = $mimeTypes[$ext] ?? (function_exists('mime_content_type') ? @mime_content_type($finalPath) : 'application/octet-stream');

        // Clean any active output buffer
        while (ob_get_level()) {
            ob_end_clean();
        }

        // Send secure headers
        header('Content-Type: ' . $mimeType);
        header('Content-Length: ' . filesize($finalPath));
        header('Cache-Control: private, max-age=86400, must-revalidate');
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');

        $isDownload = isset($_GET['download']) && $_GET['download'] == '1';
        $disposition = $isDownload ? 'attachment' : 'inline';
        header("Content-Disposition: {$disposition}; filename=\"" . basename($finalPath) . "\"");

        readfile($finalPath);
        exit();
    }
}
