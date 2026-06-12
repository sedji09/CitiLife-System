<?php
/**
 * ReportsController.php
 * Handles report generation for Admin Central.
 */

require_once __DIR__ . '/../../Models/BranchModel.php';
require_once __DIR__ . '/../../Models/CaseModel.php';

$branchModel = new \BranchModel($pdo);
$caseModel = new \CaseModel($pdo);

// Handle AJAX Request for generating report data
if (isset($_GET['ajax_generate'])) {
    header('Content-Type: application/json');

    $startDate = $_GET['date_from'] ?? date('Y-m-01');
    $endDate = $_GET['date_to'] ?? date('Y-m-t');
    $branchIdsStr = $_GET['branches'] ?? '';

    $branchIds = !empty($branchIdsStr) ? explode(',', $branchIdsStr) : [];

    try {
        $stats = $caseModel->getReportStats($startDate, $endDate, $branchIds);

        // Also get monthly counts for trend chart if needed
        $monthlyTrends = [];
        if (!empty($branchIds) && count($branchIds) === 1) {
            $year = date('Y', strtotime($startDate));
            $monthlyTrends = $caseModel->getBranchMonthlyStats($branchIds[0], $year);
        }

        echo json_encode([
            'success' => true,
            'data' => $stats,
            'trends' => $monthlyTrends
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// ---------------------------------------------------------
// EXPORT: PDF
// ---------------------------------------------------------
if (isset($_GET['export_pdf'])) {
    $startDate = $_GET['date_from'] ?? date('Y-m-01');
    $endDate = $_GET['date_to'] ?? date('Y-m-t');
    $branchIdsStr = $_GET['branches'] ?? '';
    $branchIds = !empty($branchIdsStr) ? explode(',', $branchIdsStr) : [];

    $statsList = $caseModel->getReportStats($startDate, $endDate, $branchIds);

    // Calculate Grand Total
    $grandTotal = [
        'total' => 0,
        'philhealth' => 0,
        'without_philhealth' => 0,
        'stat' => 0,
        'urgent' => 0,
        'routine' => 0
    ];
    foreach ($statsList as $s) {
        $grandTotal['total'] += ($s['total_patients'] ?? 0);
        $grandTotal['philhealth'] += ($s['with_philhealth'] ?? 0);
        $grandTotal['without_philhealth'] += ($s['without_philhealth'] ?? 0);
        $grandTotal['stat'] += ($s['emergency_count'] ?? 0);
        $grandTotal['urgent'] += ($s['urgent_count'] ?? 0);
        $grandTotal['routine'] += ($s['routine_count'] ?? 0);
    }

    // Load Dompdf
    require_once __DIR__ . '/../../../vendor/autoload.php';
    $dompdf = new \Dompdf\Dompdf(['isRemoteEnabled' => true, 'defaultFont' => 'sans-serif']);

    $rangeLabel = date('F j, Y', strtotime($startDate)) . ' to ' . date('F j, Y', strtotime($endDate));
    $logoPath = realpath(__DIR__ . '/../../../public/assets/img/logo/citilife-logo.png');
    $logoBase64 = "";
    if ($logoPath && file_exists($logoPath)) {
        $logoBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
    }

    // Header Metadata
    $headerMetadata = $branchModel->getBranchMetadata('GAPAN'); // Default to Gapan for Central
    if (count($branchIds) === 1) {
        $branchInfo = $branchModel->getBranchById($branchIds[0]);
        if ($branchInfo) {
            $headerMetadata = $branchModel->getBranchMetadata($branchInfo['name']);
        }
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
                margin-bottom: 30px;
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
                            System-Wide Operations Dashboard<br>
                            Connected Across All Branches<br>
                            Centralized Data Management
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <div class="report-title">
            <h1>Centralized Statistics Report</h1>
            <h6>Range: <?= $rangeLabel ?></h6>
        </div>

        <div class="section-header">Patient Case Summary</div>
        <table style="width: 100%; border-collapse: separate; border-spacing: 5px 0;">
            <tr>
                <td style="width: 19%;">
                    <div class="summary-card blue-accent">
                        <div class="card-label">Total Patients</div>
                        <div class="card-value"><?= number_format($grandTotal['total']) ?></div>
                    </div>
                </td>
                <td style="width: 19%;">
                    <div class="summary-card green-accent">
                        <div class="card-label">With PhilHealth</div>
                        <div class="card-value"><?= number_format($grandTotal['philhealth']) ?></div>
                    </div>
                </td>
                <td style="width: 19%;">
                    <div class="summary-card red-accent">
                        <div class="card-label">STAT</div>
                        <div class="card-value"><?= number_format($grandTotal['stat']) ?></div>
                    </div>
                </td>
                <td style="width: 19%;">
                    <div class="summary-card orange-accent">
                        <div class="card-label">Urgent Cases</div>
                        <div class="card-value"><?= number_format($grandTotal['urgent']) ?></div>
                    </div>
                </td>
                <td style="width: 19%;">
                    <div class="summary-card slate-accent">
                        <div class="card-label">Routine Cases</div>
                        <div class="card-value"><?= number_format($grandTotal['routine']) ?></div>
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
                    <td class="text-right"><?= number_format($grandTotal['stat']) ?></td>
                    <td class="text-right">
                        <?= $grandTotal['total'] > 0 ? number_format($grandTotal['stat'] / $grandTotal['total'] * 100, 1) . '%' : '0.0%' ?>
                    </td>
                </tr>
                <tr>
                    <td><strong>Urgent / Priority</strong></td>
                    <td class="text-right"><?= number_format($grandTotal['urgent']) ?></td>
                    <td class="text-right">
                        <?= $grandTotal['total'] > 0 ? number_format($grandTotal['urgent'] / $grandTotal['total'] * 100, 1) . '%' : '0.0%' ?>
                    </td>
                </tr>
                <tr>
                    <td><strong>Routine / Normal</strong></td>
                    <td class="text-right"><?= number_format($grandTotal['routine']) ?></td>
                    <td class="text-right">
                        <?= $grandTotal['total'] > 0 ? number_format($grandTotal['routine'] / $grandTotal['total'] * 100, 1) . '%' : '0.0%' ?>
                    </td>
                </tr>
            </tbody>
            <tfoot>
                <tr style="background-color: #f1f5f9; font-weight: bold;">
                    <td>Total Patients Registered</td>
                    <td class="text-right"><?= number_format($grandTotal['total']) ?></td>
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
                    <td class="text-right"><?= number_format($grandTotal['philhealth']) ?></td>
                    <td class="text-right">
                        <?= $grandTotal['total'] > 0 ? number_format($grandTotal['philhealth'] / $grandTotal['total'] * 100, 1) . '%' : '0.0%' ?>
                    </td>
                </tr>
                <tr>
                    <td>Without PhilHealth</td>
                    <td class="text-right"><?= number_format($grandTotal['without_philhealth']) ?></td>
                    <td class="text-right">
                        <?= $grandTotal['total'] > 0 ? number_format($grandTotal['without_philhealth'] / $grandTotal['total'] * 100, 1) . '%' : '0.0%' ?>
                    </td>
                </tr>
            </tbody>
        </table>

        <div class="section-header">Branch-Level Clinical Summary</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 25%;">Branch Location</th>
                    <th class="text-right">Total</th>
                    <th class="text-right">% Share</th>
                    <th class="text-right">STAT</th>
                    <th class="text-right">Urgent</th>
                    <th class="text-right">Routine</th>
                    <th class="text-right">PhilHealth</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($statsList as $s): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($s['branch_name']) ?></strong></td>
                        <td class="text-right"><?= number_format($s['total_patients'] ?? 0) ?></td>
                        <td class="text-right">
                            <?= $grandTotal['total'] > 0 ? number_format(($s['total_patients'] ?? 0) / $grandTotal['total'] * 100, 1) . '%' : '0.0%' ?>
                        </td>
                        <td class="text-right"><?= number_format($s['emergency_count'] ?? 0) ?></td>
                        <td class="text-right"><?= number_format($s['urgent_count'] ?? 0) ?></td>
                        <td class="text-right"><?= number_format($s['routine_count'] ?? 0) ?></td>
                        <td class="text-right"><?= number_format($s['with_philhealth'] ?? 0) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="background-color: #f1f5f9; font-weight: bold;">
                    <td>SYSTEM TOTAL</td>
                    <td class="text-right"><?= number_format($grandTotal['total']) ?></td>
                    <td class="text-right">100%</td>
                    <td class="text-right"><?= number_format($grandTotal['stat']) ?></td>
                    <td class="text-right"><?= number_format($grandTotal['urgent']) ?></td>
                    <td class="text-right"><?= number_format($grandTotal['routine']) ?></td>
                    <td class="text-right"><?= number_format($grandTotal['philhealth']) ?></td>
                </tr>
            </tfoot>
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

    $dompdf->stream("Central_Report_" . date('Ymd') . ".pdf", ["Attachment" => true]);
    exit;
}

// ---------------------------------------------------------
// EXPORT: Excel (.xlsx)
// ---------------------------------------------------------
if (isset($_GET['export_excel'])) {
    $startDate = $_GET['date_from'] ?? date('Y-m-01');
    $endDate = $_GET['date_to'] ?? date('Y-m-t');
    $branchIdsStr = $_GET['branches'] ?? '';
    $branchIds = !empty($branchIdsStr) ? explode(',', $branchIdsStr) : [];

    $statsList = $caseModel->getReportStats($startDate, $endDate, $branchIds);

    require_once __DIR__ . '/../../../vendor/autoload.php';

    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Centralized Statistics');

    // --- Styling Presets ---
    $headerStyle = [
        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
        'fill' => [
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'startColor' => ['rgb' => '1E3A8A']
        ],
        'alignment' => [
            'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
        ],
    ];

    $totalRowStyle = [
        'font' => ['bold' => true],
        'fill' => [
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'startColor' => ['rgb' => 'F1F5F9']
        ],
    ];

    // --- Report Header ---
    $sheet->mergeCells('A1:F1');
    $sheet->setCellValue('A1', "CITILIFE DIAGNOSTIC CENTER - CENTRALIZED STATISTICS REPORT");
    $sheet->getStyle('A1')->applyFromArray([
        'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'C0392B']],
        'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]
    ]);

    $sheet->setCellValue('A2', "Consolidated Report Date Range:");
    $sheet->setCellValue('C2', date('M j, Y', strtotime($startDate)) . ' to ' . date('M j, Y', strtotime($endDate)));
    $sheet->getStyle('A2')->getFont()->setBold(true);

    // --- Table Headers ---
    $sheet->setCellValue('A4', "BRANCH NAME");
    $sheet->setCellValue('B4', "TOTAL PATIENTS");
    $sheet->setCellValue('C4', "WITH PHILHEALTH");
    $sheet->setCellValue('D4', "EMERGENCY");
    $sheet->setCellValue('E4', "URGENT / PRIORITY");
    $sheet->setCellValue('F4', "ROUTINE / NORMAL");
    $sheet->getStyle('A4:F4')->applyFromArray($headerStyle);
    $sheet->getRowDimension(4)->setRowHeight(25);

    // --- Data Rows ---
    $currentRow = 5;
    $grand = [
        'total' => 0,
        'philhealth' => 0,
        'stat' => 0,
        'urgent' => 0,
        'routine' => 0
    ];

    foreach ($statsList as $s) {
        $sheet->setCellValue('A' . $currentRow, $s['branch_name']);
        $sheet->setCellValue('B' . $currentRow, $s['total_patients']);
        $sheet->setCellValue('C' . $currentRow, $s['with_philhealth']);
        $sheet->setCellValue('D' . $currentRow, $s['emergency_count']);
        $sheet->setCellValue('E' . $currentRow, $s['urgent_count']);
        $sheet->setCellValue('F' . $currentRow, $s['routine_count']);

        // Summation
        $grand['total'] += ($s['total_patients'] ?? 0);
        $grand['philhealth'] += ($s['with_philhealth'] ?? 0);
        $grand['stat'] += ($s['emergency_count'] ?? 0);
        $grand['urgent'] += ($s['urgent_count'] ?? 0);
        $grand['routine'] += ($s['routine_count'] ?? 0);

        if ($currentRow % 2 == 0) {
            $sheet->getStyle('A' . $currentRow . ':F' . $currentRow)->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setRGB('F8FAFC');
        }
        $currentRow++;
    }

    // --- Grand Total Row ---
    $sheet->setCellValue('A' . $currentRow, "GRAND TOTAL");
    $sheet->setCellValue('B' . $currentRow, $grand['total']);
    $sheet->setCellValue('C' . $currentRow, $grand['philhealth']);
    $sheet->setCellValue('D' . $currentRow, $grand['stat']);
    $sheet->setCellValue('E' . $currentRow, $grand['urgent']);
    $sheet->setCellValue('F' . $currentRow, $grand['routine']);
    $sheet->getStyle('A' . $currentRow . ':F' . $currentRow)->applyFromArray($totalRowStyle);

    // --- Borders and Formatting ---
    $tableRange = 'A4:F' . $currentRow;
    $sheet->getStyle($tableRange)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
    $sheet->getStyle($tableRange)->getBorders()->getAllBorders()->getColor()->setRGB('CBD5E1');

    // Centers for numeric columns
    $sheet->getStyle('B5:F' . $currentRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

    // Final Touch: Auto-size & Freeze
    foreach (range('A', 'F') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
    $sheet->freezePane('A5');

    // --- Output Transmission ---
    $filename = "Central_Report_" . date('Ymd') . ".xlsx";
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');

    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

// Initial page load data
$allBranches = $branchModel->getAllBranches();
