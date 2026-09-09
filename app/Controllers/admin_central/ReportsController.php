<?php

namespace App\Controllers\admin_central;

class ReportsController
{
    public function handle()
    {
        global $pdo;


/**
 * ReportsController.php
 * Handles report generation for Admin Central.
 */


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
        $diagStats = $caseModel->getDiagnosticStats($startDate, $endDate, $branchIds);

        // Also get monthly counts for trend chart if needed
        $monthlyTrends = [];
        if (!empty($branchIds) && count($branchIds) === 1) {
            $year = date('Y', strtotime($startDate));
            $monthlyTrends = $caseModel->getBranchMonthlyStats($branchIds[0], $year);
        }

        echo json_encode([
            'success' => true,
            'data' => $stats,
            'trends' => $monthlyTrends,
            'diagnostic_stats' => $diagStats
        ]);
    } catch (\Exception $e) {
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
    $diagStats = $caseModel->getDiagnosticStats($startDate, $endDate, $branchIds);

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
    $logoRelPath = function_exists('getSystemLogo') ? getSystemLogo() : 'public/assets/img/logo/citilife-logo.png';
    $logoPath = realpath(__DIR__ . '/../../../' . $logoRelPath);
    if (!$logoPath || !file_exists($logoPath)) {
        $logoPath = realpath(__DIR__ . '/../../../public/assets/img/logo/citilife-logo.png');
    }
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
                padding-bottom: 10px;
                margin-bottom: 12px;
            }

            .header-table {
                width: 100%;
                border-collapse: collapse;
            }

            .logo-cell {
                width: 70px;
                vertical-align: middle;
            }

            .logo {
                width: 70px;
                height: auto;
                display: block;
            }

            .clinic-info {
                vertical-align: middle;
                padding-left: 12px;
            }

            .clinic-info h1 {
                font-size: 36px;
                font-weight: 700;
                color: #c0392b;
                letter-spacing: 1.5px;
                margin: 0;
                line-height: 0.85;
                text-transform: uppercase;
            }

            .clinic-info p {
                font-size: 13px;
                font-weight: 600;
                color: #c0392b;
                margin: 0;
                margin-top: 2px;
                line-height: 1;
                letter-spacing: 1px;
                text-transform: uppercase;
            }

            .branch-info {
                vertical-align: middle;
                text-align: right;
                color: #64748b;
                font-size: 11px;
            }

            .metadata {
                color: #64748b;
                margin: 0;
                line-height: 1.3;
            }

            .report-title {
                text-align: center;
                margin-bottom: 12px;
            }

            .report-title h1 {
                font-size: 18px;
                font-weight: bold;
                color: #1e293b;
                margin: 0;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }

            .report-title h6 {
                font-size: 9px;
                font-weight: bold;
                color: #475569;
                margin: 3px 0;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }

            /* Summary Grid */
            .summary-card {
                padding: 8px 10px;
                border-radius: 6px;
                border: 1px solid #e2e8f0;
                background-color: #f8fafc;
            }

            .card-label {
                font-size: 8px;
                font-weight: bold;
                color: #64748b;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                margin-bottom: 3px;
            }

            .card-value {
                font-size: 16px;
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

            /* Sections & Page Breaks */
            .report-section {
                page-break-inside: avoid;
                margin-bottom: 10px;
            }

            .section-header {
                font-size: 11px;
                font-weight: bold;
                color: #1e293b;
                margin: 10px 0 5px;
                border-bottom: 1px solid #e2e8f0;
                padding-bottom: 3px;
                page-break-after: avoid;
            }

            /* Table Styles */
            .data-table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 10px;
                font-size: 10px;
                page-break-inside: auto;
            }

            .data-table thead {
                display: table-header-group;
            }

            .data-table tfoot {
                display: table-footer-group;
            }

            .data-table tr {
                page-break-inside: avoid;
            }

            .data-table th {
                background-color: #f1f5f9;
                color: #475569;
                font-weight: bold;
                text-align: left;
                padding: 5px 8px;
                border-bottom: 2px solid #e2e8f0;
                text-transform: uppercase;
                font-size: 8.5px;
            }

            .data-table td {
                padding: 5px 8px;
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
                        <?php 
                            $fullSysName = function_exists('getSystemName') ? getSystemName() : 'Citilife Diagnostic Center';
                            $parts = explode(' ', $fullSysName, 2);
                            $mainTitle = $parts[0] ?? '';
                            $subTitle = $parts[1] ?? '';
                        ?>
                        <h1><?= htmlspecialchars($mainTitle) ?></h1>
                        <?php if (!empty($subTitle)): ?>
                            <p><?= htmlspecialchars($subTitle) ?></p>
                        <?php endif; ?>
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

        <div class="report-section">
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
        </div>

        <div class="report-section">
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
        </div>

        <div class="report-section">
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
        </div>

        <div class="report-section">
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
        </div>
        
        <div class="report-section" style="page-break-before: always;">
            <div class="section-header">Top 10 Diagnostic Findings & Disease Prevalence Ranking</div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 10%; text-align: center;">Rank</th>
                        <th style="width: 56%;">Diagnostic Finding / Impression</th>
                        <th class="text-right" style="width: 17%;">Total Cases</th>
                        <th class="text-right" style="width: 17%;">Prevalence Rate<br>(% Share)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($diagStats['ranking'])): ?>
                        <?php foreach ($diagStats['ranking'] as $item): ?>
                            <?php $isTop1 = ($item['rank'] === 1); ?>
                            <tr style="<?= $isTop1 ? 'background-color: #fef2f2;' : '' ?>">
                                <td style="text-align: center; font-weight: bold; padding: 6px 8px;">
                                    <?php if ($isTop1): ?>
                                        <span style="display: inline-block; background-color: #dc2626; color: #ffffff; border-radius: 4px; padding: 1px 7px; font-size: 9.5px; font-weight: bold;">1</span>
                                    <?php else: ?>
                                        <span style="color: #64748b;"><?= $item['rank'] ?></span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 6px 8px;">
                                    <?php if ($isTop1): ?>
                                        <strong style="color: #991b1b;"><?= htmlspecialchars($item['diagnosis']) ?></strong>
                                    <?php else: ?>
                                        <?= htmlspecialchars($item['diagnosis']) ?>
                                    <?php endif; ?>
                                </td>
                                <td class="text-right" style="padding: 6px 8px; <?= $isTop1 ? 'font-weight: bold; color: #991b1b;' : '' ?>">
                                    <?= number_format($item['count']) ?>
                                </td>
                                <td class="text-right" style="padding: 6px 8px; <?= $isTop1 ? 'font-weight: bold; color: #991b1b;' : '' ?>">
                                    <?= number_format($item['percentage'], 1) ?>%
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" style="text-align: center; color: #94a3b8; padding: 15px;">No diagnostic impressions recorded for this period.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
                <?php if (!empty($diagStats['ranking'])): ?>
                <tfoot>
                    <tr style="background-color: #f1f5f9; font-weight: bold;">
                        <td colspan="2">TOTAL DIAGNOSED CASES</td>
                        <td class="text-right"><?= number_format($diagStats['total_diagnosed']) ?></td>
                        <td class="text-right">100.0%</td>
                    </tr>
                </tfoot>
                <?php endif; ?>
            </table>
        </div>

    </body>

    </html>
    <?php
    $html = ob_get_clean();
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    // ── Canvas Footer: draw both texts on the SAME y-baseline ──
    $canvas = $dompdf->getCanvas();
    $font = $dompdf->getFontMetrics()->getFont('helvetica', 'normal');
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

    // Add Audit Log
    require_once __DIR__ . '/../../Models/AuditLogModel.php';
    $auditLogModel = new \AuditLogModel($pdo);
    $auditLogModel->addLog(
        $_SESSION['user_id'],
        'Downloaded Statistical Report (PDF)',
        'Reports Generation',
        'Reports',
        null,
        "Range: $rangeLabel, Branches: " . (empty($branchIds) ? 'All' : implode(',', $branchIds)),
        null // Central admin may not have branch_id
    );

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
    $diagStats = $caseModel->getDiagnosticStats($startDate, $endDate, $branchIds);



    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Branch Statistics');

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
    $sheet->mergeCells('A1:G1');
    $sheet->setCellValue('A1', "CITILIFE DIAGNOSTIC CENTER - CENTRALIZED STATISTICS REPORT");
    $sheet->getStyle('A1')->applyFromArray([
        'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'C0392B']],
        'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]
    ]);

    $sheet->mergeCells('A2:B2');
    $sheet->setCellValue('A2', "Consolidated Report Date Range:");
    $sheet->getStyle('A2')->getFont()->setBold(true);
    $sheet->mergeCells('C2:E2');
    $sheet->setCellValue('C2', date('M j, Y', strtotime($startDate)) . ' to ' . date('M j, Y', strtotime($endDate)));

    // --- Table Headers ---
    $sheet->setCellValue('A4', "NO.");
    $sheet->setCellValue('B4', "BRANCH NAME");
    $sheet->setCellValue('C4', "TOTAL PATIENTS");
    $sheet->setCellValue('D4', "WITH PHILHEALTH");
    $sheet->setCellValue('E4', "EMERGENCY");
    $sheet->setCellValue('F4', "URGENT / PRIORITY");
    $sheet->setCellValue('G4', "ROUTINE / NORMAL");
    $sheet->getStyle('A4:G4')->applyFromArray($headerStyle);
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

    $branchNum = 1;
    foreach ($statsList as $s) {
        $sheet->setCellValue('A' . $currentRow, "No. " . $branchNum++);
        $sheet->setCellValue('B' . $currentRow, $s['branch_name']);
        $sheet->setCellValue('C' . $currentRow, $s['total_patients']);
        $sheet->setCellValue('D' . $currentRow, $s['with_philhealth']);
        $sheet->setCellValue('E' . $currentRow, $s['emergency_count']);
        $sheet->setCellValue('F' . $currentRow, $s['urgent_count']);
        $sheet->setCellValue('G' . $currentRow, $s['routine_count']);

        // Summation
        $grand['total'] += ($s['total_patients'] ?? 0);
        $grand['philhealth'] += ($s['with_philhealth'] ?? 0);
        $grand['stat'] += ($s['emergency_count'] ?? 0);
        $grand['urgent'] += ($s['urgent_count'] ?? 0);
        $grand['routine'] += ($s['routine_count'] ?? 0);

        if ($currentRow % 2 == 0) {
            $sheet->getStyle('A' . $currentRow . ':G' . $currentRow)->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setRGB('F8FAFC');
        }
        $currentRow++;
    }

    // --- Grand Total Row ---
    $sheet->setCellValue('A' . $currentRow, "TOTAL");
    $sheet->setCellValue('B' . $currentRow, "All Branches Consolidated");
    $sheet->setCellValue('C' . $currentRow, $grand['total']);
    $sheet->setCellValue('D' . $currentRow, $grand['philhealth']);
    $sheet->setCellValue('E' . $currentRow, $grand['stat']);
    $sheet->setCellValue('F' . $currentRow, $grand['urgent']);
    $sheet->setCellValue('G' . $currentRow, $grand['routine']);
    $sheet->getStyle('A' . $currentRow . ':G' . $currentRow)->applyFromArray($totalRowStyle);

    // --- Borders and Formatting for Sheet 1 ---
    $tableRange = 'A4:G' . $currentRow;
    $sheet->getStyle($tableRange)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
    $sheet->getStyle($tableRange)->getBorders()->getAllBorders()->getColor()->setRGB('CBD5E1');

    // Centers for numeric columns & No. column
    $sheet->getStyle('A5:A' . $currentRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('C5:G' . $currentRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
    $sheet->freezePane('A5');

    // Auto-size columns B through G to fit their contents comfortably
    foreach (range('B', 'G') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
    $sheet->getColumnDimension('A')->setAutoSize(false)->setWidth(10);

    // ==========================================
    // --- SHEET 2: DISEASE PREVALENCE RANKING ---
    // ==========================================
    $sheet2 = $spreadsheet->createSheet();
    $sheet2->setTitle('Disease Prevalence');

    // Report Header for Sheet 2
    $sheet2->mergeCells('A1:D1');
    $sheet2->setCellValue('A1', "CITILIFE DIAGNOSTIC CENTER - DISEASE PREVALENCE REPORT");
    $sheet2->getStyle('A1')->applyFromArray([
        'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'C0392B']],
        'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]
    ]);

    $sheet2->mergeCells('A2:B2');
    $sheet2->setCellValue('A2', "Consolidated Report Date Range:");
    $sheet2->getStyle('A2')->getFont()->setBold(true);
    $sheet2->mergeCells('C2:D2');
    $sheet2->setCellValue('C2', date('M j, Y', strtotime($startDate)) . ' to ' . date('M j, Y', strtotime($endDate)));

    // Table Headers for Sheet 2
    $sheet2->setCellValue('A4', "NO.");
    $sheet2->setCellValue('B4', "DIAGNOSTIC FINDING / CLINICAL IMPRESSION");
    $sheet2->setCellValue('C4', "TOTAL CASES");
    $sheet2->setCellValue('D4', "PREVALENCE RATE (% SHARE)");
    $sheet2->getStyle('A4:D4')->applyFromArray($headerStyle);
    $sheet2->getRowDimension(4)->setRowHeight(25);

    $diagCurRow = 5;
    if (!empty($diagStats['ranking'])) {
        foreach ($diagStats['ranking'] as $item) {
            $sheet2->setCellValue('A' . $diagCurRow, "No. " . $item['rank']);
            $sheet2->setCellValue('B' . $diagCurRow, $item['diagnosis']);
            $sheet2->setCellValue('C' . $diagCurRow, $item['count']);
            $sheet2->setCellValue('D' . $diagCurRow, $item['percentage'] . '%');

            if ($diagCurRow % 2 == 0) {
                $sheet2->getStyle('A' . $diagCurRow . ':D' . $diagCurRow)->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('F8FAFC');
            }
            $diagCurRow++;
        }

        // Summary Total Row for Diagnoses
        $sheet2->setCellValue('A' . $diagCurRow, "TOTAL");
        $sheet2->setCellValue('B' . $diagCurRow, "Diagnosed Cases Analyzed");
        $sheet2->setCellValue('C' . $diagCurRow, $diagStats['total_diagnosed']);
        $sheet2->setCellValue('D' . $diagCurRow, "100%");
        $sheet2->getStyle('A' . $diagCurRow . ':D' . $diagCurRow)->applyFromArray($totalRowStyle);
    } else {
        $sheet2->mergeCells('A' . $diagCurRow . ':D' . $diagCurRow);
        $sheet2->setCellValue('A' . $diagCurRow, "No diagnostic impressions recorded for this period.");
        $sheet2->getStyle('A' . $diagCurRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
    }

    $diagRange = 'A4:D' . $diagCurRow;
    $sheet2->getStyle($diagRange)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
    $sheet2->getStyle($diagRange)->getBorders()->getAllBorders()->getColor()->setRGB('CBD5E1');
    $sheet2->getStyle('A5:A' . $diagCurRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
    $sheet2->getStyle('C5:D' . $diagCurRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

    $sheet2->getColumnDimension('A')->setAutoSize(false)->setWidth(10);
    $sheet2->getColumnDimension('B')->setAutoSize(true);
    $sheet2->getColumnDimension('C')->setAutoSize(true);
    $sheet2->getColumnDimension('D')->setAutoSize(true);

    $sheet2->freezePane('A5');

    // Set first sheet as active when user opens the workbook
    $spreadsheet->setActiveSheetIndex(0);

    // --- Output Transmission ---
    $filename = "Central_Report_" . date('Ymd') . ".xlsx";
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
        "Range: $startDate to $endDate, Branches: " . (empty($branchIds) ? 'All' : implode(',', $branchIds)),
        null
    );

    exit;
}

// Initial page load data
$allBranches = $branchModel->getAllBranches();

        return get_defined_vars();
    }
}
