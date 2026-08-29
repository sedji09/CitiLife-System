<?php
libxml_use_internal_errors(true);
$doc = new DOMDocument();
$doc->loadHTMLFile('views/layouts/partials/patient_sidebar.php');
foreach (libxml_get_errors() as $error) {
    echo "Line " . $error->line . ": " . $error->message . "\n";
}
