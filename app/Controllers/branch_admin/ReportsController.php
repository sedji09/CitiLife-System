<?php

namespace App\Controllers\branch_admin;

class ReportsController
{
    public function handle()
    {
        global $pdo;


/**
 * ReportsController.php
 * Handles report generation and exporting for Branch Administrators.
 */

$caseModel = new \CaseModel($pdo);
$branchModel = new \BranchModel($pdo);

$branchId = $_SESSION['branch_id'] ?? null;
if (!$branchId) {
    // Fallback if not set (should not happen for branch admin)
    die("Unauthorized: Branch ID not found.");
}

$branchData = $branchModel->getBranchById($branchId);
$branchName = $branchData['name'] ?? 'Unknown Branch';

// ---------------------------------------------------------
// 1. AJAX: Fetch Stats for UI
// ---------------------------------------------------------
if (isset($_GET['ajax_get_stats'])) {
    header('Content-Type: application/json');

    $startDate = $_GET['from'] ?? date('Y-m-01');
    $endDate = $_GET['to'] ?? date('Y-m-t');

    try {
        $stats = $caseModel->getBranchBreakdown($branchId, $startDate, $endDate);

        // Also get monthly counts if it's a wide range (e.g. yearly)
        $monthlyCounts = [];
        if (isset($_GET['include_monthly'])) {
            $year = date('Y', strtotime($startDate));
            $monthlyCounts = $caseModel->getBranchMonthlyStats($branchId, $year);
        }

        echo json_encode([
            'success' => true,
            'data' => $stats,
            'monthly' => $monthlyCounts
        ]);
    } catch (\Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// ---------------------------------------------------------
// 2. EXPORT: PDF
// ---------------------------------------------------------
if (isset($_GET['export_pdf'])) {
    $startDate = $_GET['from'] ?? date('Y-m-01');
    $endDate = $_GET['to'] ?? date('Y-m-t');

    $stats = $caseModel->getBranchBreakdown($branchId, $startDate, $endDate);

    // Load Dompdf
    require_once __DIR__ . '/../../../vendor/autoload.php';
    $dompdf = new \Dompdf\Dompdf([
        'isRemoteEnabled' => true,
        'defaultFont' => 'sans-serif'
    ]);

    // Format display range
    $rangeLabel = date('F j, Y', strtotime($startDate)) . ' to ' . date('F j, Y', strtotime($endDate));
    $branchMetadata = $branchModel->getBranchMetadata($branchName);
    $logoPath = realpath(__DIR__ . '/../../../public/assets/img/logo/citilife-logo.png');
    $logoBase64 = "";
    if ($logoPath && file_exists($logoPath)) {
        $logoBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
    }

    // HTML Template for PDF
    ob_start();
    ?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <style>
            @page {
                margin: 0.5in;
            }

            body {
                font-family: 'Helvetica', 'Arial', sans-serif;
                color: #1e293b;
                line-height: 1.5;
                margin: 0;
            }

            /* Header */
            .report-header {
                width: 100%;
                border-bottom: 2px solid #2563eb;
                padding-bottom: 20px;
                margin-bottom: 30px;
            }

            .header-table {
                width: 100%;
                border-collapse: collapse;
            }

            .logo-cell {
                width: 80px;
                vertical-align: middle;
            }

            .logo {
                width: 80px;
                height: auto;
                display: block;
            }

            .clinic-info {
                vertical-align: middle;
                padding-left: 15px;
            }

            .clinic-info h1 {
                font-size: 45px;
                font-weight: 700;
                color: #c0392b;
                letter-spacing: 2px;
                margin: 0;
                line-height: 0.85;
                text-transform: uppercase;
            }

            .clinic-info p {
                font-size: 16px;
                font-weight: 600;
                color: #c0392b;
                margin: 0;
                margin-top: 2px;
                line-height: 1;
                letter-spacing: 1.5px;
                text-transform: uppercase;
            }

            .branch-info {
                vertical-align: middle;
                text-align: right;
                color: #64748b;
                font-size: 12px;
            }

            .metadata {
                color: #64748b;
                margin: 0;
                line-height: 1.4;
            }

            .report-title {
                text-align: center;
            }

            .report-title h1 {
                font-size: 24px;
                font-weight: bold;
                color: #1e293b;
                margin: 0;
                text-transform: uppercase;
                letter-spacing: 1px;
            }

            .report-title h6 {
                font-size: 10px;
                font-weight: bold;
                color: #475569;
                margin: 5px 0;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }

            /* Summary Grid */
            .summary-card {
                padding: 12px;
                border-radius: 8px;
                border: 1px solid #e2e8f0;
                background-color: #f8fafc;
            }

            .card-label {
                font-size: 9px;
                font-weight: bold;
                color: #64748b;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                margin-bottom: 6px;
            }

            .card-value {
                font-size: 20px;
                font-weight: bold;
                color: #1e3a8a;
            }

            .blue-accent {
                border-left: 4px solid #2563eb;
            }

            .green-accent {
                border-left: 4px solid #10b981;
            }

            .red-accent {
                border-left: 4px solid #ef4444;
            }

            .orange-accent {
                border-left: 4px solid #f59e0b;
            }

            .slate-accent {
                border-left: 4px solid #64748b;
            }

            /* Sections */
            .section-header {
                font-size: 13px;
                font-weight: bold;
                color: #1e293b;
                margin: 25px 0 10px;
                border-bottom: 1px solid #e2e8f0;
                padding-bottom: 4px;
            }

            /* Table Styles */
            .data-table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 20px;
                font-size: 11px;
            }

            .data-table th {
                background-color: #f1f5f9;
                color: #475569;
                font-weight: bold;
                text-align: left;
                padding: 8px 10px;
                border-bottom: 2px solid #e2e8f0;
                text-transform: uppercase;
                font-size: 9px;
            }

            .data-table td {
                padding: 8px 10px;
                border-bottom: 1px solid #f1f5f9;
                color: #334155;
            }

            .data-table tr:nth-child(even) {
                background-color: #f8fafc;
            }

            .data-table .text-right {
                text-align: right;
            }
        </style>
    </head>

    <body>
        <div class="report-header">
            <table class="header-table">
                <tr>
                    <td class="logo-cell">
                        <?php if ($logoBase64): ?>
                            <img src="<?= $logoBase64 ?>" class="logo">
                        <?php endif; ?>
                    </td>
                    <td class="clinic-info">
                        <h1>CitiLife</h1>
                        <p>Diagnostic Center</p>
                    </td>
                    <td class="branch-info">
                        <div class="metadata">
                            <?php
                            $addressLines = explode("\n", $branchMetadata['address'] ?? '');
                            echo htmlspecialchars($addressLines[0] ?? '') . "<br>";
                            if (!empty($addressLines[1])) {
                                echo htmlspecialchars($addressLines[1]) . "<br>";
                            }
                            echo "Contacts: " . htmlspecialchars($branchMetadata['contact1'] ?? '');
                            if (!empty($branchMetadata['contact2']))
                                echo " / " . htmlspecialchars($branchMetadata['contact2']);
                            ?>
                        </div>
                    </td>
                </tr>
            </table>
        </div>
        <div class="report-title">
            <h1>Patient Statistics Report</h1>
            <h6>Range: <?= $rangeLabel ?></h6>

        </div>
        <div class="section-header">Patient Case Summary</div>
        <table style="width: 100%; border-collapse: separate; border-spacing: 5px 0;">
            <tr>
                <td style="width: 19%;">
                    <div class="summary-card blue-accent">
                        <div class="card-label">Total Patients</div>
                        <div class="card-value"><?= number_format($stats['total_patients'] ?? 0) ?></div>
                    </div>
                </td>
                <td style="width: 19%;">
                    <div class="summary-card green-accent">
                        <div class="card-label">With PhilHealth</div>
                        <div class="card-value"><?= number_format($stats['with_philhealth'] ?? 0) ?></div>
                    </div>
                </td>
                <td style="width: 19%;">
                    <div class="summary-card red-accent">
                        <div class="card-label">STAT</div>
                        <div class="card-value"><?= number_format($stats['emergency_count'] ?? 0) ?></div>
                    </div>
                </td>
                <td style="width: 19%;">
                    <div class="summary-card orange-accent">
                        <div class="card-label">Urgent Cases</div>
                        <div class="card-value"><?= number_format($stats['urgent_count'] ?? 0) ?></div>
                    </div>
                </td>
                <td style="width: 19%;">
                    <div class="summary-card slate-accent">
                        <div class="card-label">Routine Cases</div>
                        <div class="card-value"><?= number_format($stats['routine_count'] ?? 0) ?></div>
                    </div>
                </td>
            </tr>
        </table>

        <div class="section-header">Case Priority Breakdown</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 60%;">Category</th>
                    <th class="text-right" style="width: 20%;">Total Cases</th>
                    <th class="text-right" style="width: 20%;">Percentage</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>STAT / Critical</strong></td>
                    <td class="text-right"><?= number_format($stats['emergency_count'] ?? 0) ?></td>
                    <td class="text-right">
                        <?= ($stats['total_patients'] ?? 0) > 0 ? number_format(($stats['emergency_count'] ?? 0) / $stats['total_patients'] * 100, 1) . '%' : '0.0%' ?>
                    </td>
                </tr>
                <tr>
                    <td><strong>Urgent / Priority</strong></td>
                    <td class="text-right"><?= number_format($stats['urgent_count'] ?? 0) ?></td>
                    <td class="text-right">
                        <?= ($stats['total_patients'] ?? 0) > 0 ? number_format(($stats['urgent_count'] ?? 0) / $stats['total_patients'] * 100, 1) . '%' : '0.0%' ?>
                    </td>
                </tr>
                <tr>
                    <td><strong>Routine / Normal</strong></td>
                    <td class="text-right"><?= number_format($stats['routine_count'] ?? 0) ?></td>
                    <td class="text-right">
                        <?= ($stats['total_patients'] ?? 0) > 0 ? number_format(($stats['routine_count'] ?? 0) / $stats['total_patients'] * 100, 1) . '%' : '0.0%' ?>
                    </td>
                </tr>
            </tbody>
            <tfoot>
                <tr style="background-color: #f1f5f9; font-weight: bold;">
                    <td>Total Registrations</td>
                    <td class="text-right"><?= number_format($stats['total_patients'] ?? 0) ?></td>
                    <td class="text-right">100%</td>
                </tr>
            </tfoot>
        </table>

        <div class="section-header">Insurance Coverage Statistics</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 60%;">PhilHealth Status</th>
                    <th class="text-right" style="width: 20%;">Count</th>
                    <th class="text-right" style="width: 20%;">Coverage Ratio</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>With PhilHealth</td>
                    <td class="text-right"><?= number_format($stats['with_philhealth'] ?? 0) ?></td>
                    <td class="text-right">
                        <?= ($stats['total_patients'] ?? 0) > 0 ? number_format(($stats['with_philhealth'] ?? 0) / $stats['total_patients'] * 100, 1) . '%' : '0.0%' ?>
                    </td>
                </tr>
                <tr>
                    <td>Without PhilHealth</td>
                    <td class="text-right"><?= number_format($stats['without_philhealth'] ?? 0) ?></td>
                    <td class="text-right">
                        <?= ($stats['total_patients'] ?? 0) > 0 ? number_format(($stats['without_philhealth'] ?? 0) / $stats['total_patients'] * 100, 1) . '%' : '0.0%' ?>
                    </td>
                </tr>
            </tbody>
        </table>


    </body>

    </html>
    <?php
    $html = ob_get_clean();

    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    // ── Canvas Footer: draw both texts on the SAME y-baseline ──
    $canvas = $dompdf->getCanvas();
    $font = $dompdf->getFontMetrics()->get_font('helvetica', 'normal');
    $color = [148 / 255, 163 / 255, 184 / 255]; // #94a3b8
    $lineCol = [226 / 255, 232 / 255, 240 / 255]; // #e2e8f0
    $w = $canvas->get_width();        // ~595pt for A4
    $h = $canvas->get_height();       // ~842pt for A4
    $mx = 36;                          // 0.5in margin in pts
    $px = 15;                          // 20px side padding → ~15pt

    $lineY = ($h - $mx) - 19;  // separator line
    $textY = ($h - $mx) - 10;  // text baseline (same for both sides)

    $canvas->line($mx + $px, $lineY, $w - $mx - $px, $lineY, $lineCol, 0.5);
    $canvas->page_text($mx + $px, $textY, 'Generated: ' . date('F j, Y g:i A'), $font, 8, $color);
    $canvas->page_text($w - $mx - $px - 65, $textY, 'Page {PAGE_NUM} of {PAGE_COUNT}', $font, 8, $color);

    $filename = "Report_" . str_replace(' ', '_', $branchName) . "_" . date('Ymd') . ".pdf";

    // Add Audit Log
    require_once __DIR__ . '/../../Models/AuditLogModel.php';
    $auditLogModel = new \AuditLogModel($pdo);
    $auditLogModel->addLog(
        $_SESSION['user_id'],
        'Downloaded Statistical Report (PDF)',
        'Reports Generation',
        'Reports',
        null,
        "Range: $rangeLabel",
        $branchId
    );

    $dompdf->stream($filename, ["Attachment" => true]);
    exit;
}

// ---------------------------------------------------------
// 3. EXPORT: Excel (.xlsx)
// ---------------------------------------------------------
if (isset($_GET['export_excel'])) {
    $startDate = $_GET['from'] ?? date('Y-m-01');
    $endDate = $_GET['to'] ?? date('Y-m-t');

    $stats = $caseModel->getBranchBreakdown($branchId, $startDate, $endDate);



    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Branch Statistics');

    // --- Styling Presets ---
    $headerStyle = [
        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
        'fill' => [
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'startColor' => ['rgb' => '1E3A8A'] // Deep Blue
        ],
        'alignment' => [
            'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
        ],
    ];

    $titleStyle = [
        'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'C0392B']], // CitiLife Red
        'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
    ];

    // --- Report Header ---
    $sheet->mergeCells('A1:C1');
    $sheet->setCellValue('A1', "CITILIFE DIAGNOSTIC CENTER - " . strtoupper($branchName));
    $sheet->getStyle('A1')->applyFromArray($titleStyle);

    $sheet->setCellValue('A2', "Patient Statistics Report Detail");
    $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('475569'));

    $sheet->setCellValue('A3', "Date Range:");
    $sheet->setCellValue('B3', date('M j, Y', strtotime($startDate)) . ' to ' . date('M j, Y', strtotime($endDate)));
    $sheet->getStyle('A3')->getFont()->setBold(true);

    // --- Table Headers ---
    $sheet->setCellValue('A5', "CATEGORY");
    $sheet->setCellValue('B5', "METRIC / DESCRIPTION");
    $sheet->setCellValue('C5', "TOTAL CASE COUNT");
    $sheet->getStyle('A5:C5')->applyFromArray($headerStyle);
    $sheet->getRowDimension(5)->setRowHeight(25);

    // --- Data Rows ---
    $data = [
        ["General", "Total Registered Patients", $stats['total_patients'] ?? 0],
        ["PhilHealth", "Patients with PhilHealth", $stats['with_philhealth'] ?? 0],
        ["PhilHealth", "Patients without PhilHealth", $stats['without_philhealth'] ?? 0],
        ["Case Priority", "STAT / Critical Cases", $stats['emergency_count'] ?? 0],
        ["Case Priority", "Urgent / Priority Cases", $stats['urgent_count'] ?? 0],
        ["Case Priority", "Routine / Normal Cases", $stats['routine_count'] ?? 0],
    ];

    $startRow = 6;
    $currentRow = $startRow;
    foreach ($data as $item) {
        $sheet->fromArray($item, NULL, 'A' . $currentRow);

        // Zebra Striping & Alignment
        if ($currentRow % 2 == 0) {
            $sheet->getStyle('A' . $currentRow . ':C' . $currentRow)->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setRGB('F8FAFC');
        }

        $sheet->getStyle('C' . $currentRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $currentRow++;
    }

    // --- Borders ---
    $tableRange = 'A5:C' . ($currentRow - 1);
    $sheet->getStyle($tableRange)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
    $sheet->getStyle($tableRange)->getBorders()->getAllBorders()->getColor()->setRGB('CBD5E1');

    // --- Footer Metadata ---
    $currentRow += 2;
    $sheet->setCellValue('A' . $currentRow, "Report Generated on:");
    $sheet->setCellValue('B' . $currentRow, date('F j, Y g:i A'));
    $sheet->getStyle('A' . $currentRow)->getFont()->setItalic(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('64748B'));
    $sheet->getStyle('B' . $currentRow)->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('64748B'));

    // --- Final Polish: Auto-size columns & Freeze Panes ---
    foreach (range('A', 'C') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
    $sheet->freezePane('A6');

    // --- Transmission ---
    $filename = "Report_" . str_replace(' ', '_', $branchName) . "_" . date('Ymd') . ".xlsx";

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');

    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->save('php://output');

    // Add Audit Log
    require_once __DIR__ . '/../../Models/AuditLogModel.php';
    $auditLogModel = new \AuditLogModel($pdo);
    $auditLogModel->addLog(
        $_SESSION['user_id'],
        'Downloaded Statistical Report (Excel)',
        'Reports Generation',
        'Reports',
        null,
        "Range: $startDate to $endDate",
        $branchId
    );

    exit;
}

        return get_defined_vars();
    }
}
