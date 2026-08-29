<?php
libxml_use_internal_errors(true);
$files = [
    'views/layouts/dashboard.php',
    'views/layouts/partials/head_assets.php',
    'views/layouts/partials/skeleton_loader.php',
    'views/layouts/partials/toasts.php',
    'views/layouts/partials/patient_sidebar.php',
    'views/layouts/partials/topbar.php',
    'views/pages/patient/dashboard.view.php',
    'views/layouts/partials/scripts.php'
];

foreach ($files as $file) {
    if (!file_exists($file)) continue;
    $doc = new DOMDocument();
    $doc->loadHTMLFile($file);
    foreach (libxml_get_errors() as $error) {
        if(strpos($error->message, 'redefined') !== false) {
            echo $file . " Line " . $error->line . ": " . trim($error->message) . "\n";
        }
    }
    libxml_clear_errors();
}
