<?php

/**
 * Helper for generating and saving PDF reports for finalized radiology cases.
 */

require_once __DIR__ . '/../../vendor/autoload.php';

function generateAndSaveReportPdf($caseId, $pdo)
{
    // Ensure dompdf is loaded
    $dompdf = new \Dompdf\Dompdf(['isRemoteEnabled' => true, 'defaultFont' => 'sans-serif']);

    // Capture the print report view's HTML output
    // Simulate HTTP GET params that print-report.view.php expects
    $_GET['id'] = $caseId;
    $_GET['no_shadow'] = true; // Disable shadows for PDF
    
    // We need to set session role/id if not present so fallback logic works 
    // or just let it use the current session (which should be the radiologist).

    ob_start();
    // Path is relative to this helper
    require __DIR__ . '/../../views/pages/radtech/print-report.view.php';
    $html = ob_get_clean();

    // Since Dompdf can struggle with complex CSS/flexbox, we hope print-report.view.php is compatible.
    // Given the context, this is a standard approach.
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    $output = $dompdf->output();

    // Create unique filename
    $fileName = 'report_' . $caseId . '_' . time() . '.pdf';
    $savePath = __DIR__ . '/../../../public/uploads/reports/' . $fileName;
    $dbPath = 'public/uploads/reports/' . $fileName;

    if (file_put_contents($savePath, $output)) {
        // Update database with the PDF path
        $stmt = $pdo->prepare("UPDATE cases SET pdf_path = ? WHERE id = ?");
        $stmt->execute([$dbPath, $caseId]);
        return true;
    }

    return false;
}
