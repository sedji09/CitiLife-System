<?php
/**
 * download-report.php
 * Generates and streams a PDF report directly to the browser for download.
 * Opened from the Patient dashboard.
 */
session_start();

$caseModel = new \CaseModel($pdo);
$userModel = new \UserModel($pdo);
$branchModel = new \BranchModel($pdo);

$userId = $_SESSION['user_id'] ?? null;
if (isset($_GET['ref'])) {
    $decoded = base64_decode($_GET['ref']);
    if (strpos($decoded, 'CitiLife_Case_') === 0) {
        $id = (int) str_replace('CitiLife_Case_', '', $decoded);
    } else {
        $id = 0;
    }
} else {
    $id = (int) ($_GET['id'] ?? 0);
}

// Redirect back to view-report (with ref token instead of raw id) just to be safe
$refToken = base64_encode('CitiLife_Case_' . $id);
$patientId = $_SESSION['patient_id'] ?? null;

if (!$id || !$patientId) {
    header("Location: /" . PROJECT_DIR . "/view-report?ref=" . $refToken);
    exit;
}

// Access control:
$case = $caseModel->getCaseById($id);
if (!$case || (int) $case['patient_id'] !== (int) $patientId) {
    header("Location: /" . PROJECT_DIR . "/view-report?ref=" . $refToken);
    exit;
}

// The code below is refactored for decoupling but currently unreachable due to redirect above
$patientId = $_SESSION['patient_id'] ?? null;

if (!$patientId) {
    die('<p style="font-family:sans-serif;padding:2rem;color:red;">No patient profile linked to your account.</p>');
}

// Fetch the case via Model
$case = $caseModel->getCaseById($id);

if (!$case || (int) $case['patient_id'] !== (int) $patientId) {
    die('<p style="font-family:sans-serif;padding:2rem;color:red;">Case not found or access denied.</p>');
}

// 3-Month Availability Check
$isExpired = strtotime($case['created_at']) < strtotime('-3 months');
if ($isExpired) {
    header("Location: /" . PROJECT_DIR . "/my-records?expired=1");
    exit;
}

$isReleased = in_array($case['status'], ['Released', 'Completed']) || !empty($case['released']);
if (!$isReleased) {
    die('<p style="font-family:sans-serif;padding:2rem;color:red;">Report is not yet available for download.</p>');
}

$isPreview = false;
$isDownload = true;

// Log download action
require_once basePath('app/Models/AuditLogModel.php');
$auditLogModel = new \AuditLogModel($pdo);
$auditLogModel->addLog(
    $_SESSION['user_id'],
    'Downloaded Result',
    'Patient Portal',
    'Case',
    $id,
    "Patient downloaded X-Ray result PDF (" . $case['case_number'] . ")",
    $case['branch_id']
);

// Fetch Radiologist Name via Model
$radName = $case['radiologist_name'] ?? 'Radiologist on Duty';
$radTitle = $case['radiologist_title'] ?? '';
$radSignature = $case['radiologist_signature'] ?? '';

$radFullNameWithTitle = htmlspecialchars($radName);
if (!empty($radTitle)) {
    $radFullNameWithTitle .= ', ' . htmlspecialchars($radTitle);
}
// Add DR. prefix if not present for non-placeholder names
if ($radName !== 'Radiologist on Duty' && !str_contains(strtoupper($radFullNameWithTitle), 'DR.')) {
    $radFullNameWithTitle = 'DR. ' . $radFullNameWithTitle;
}

$fullName = htmlspecialchars(strtoupper($case['first_name'] . ' ' . $case['last_name']));
$examType = $case['exam_type'];
$examTypeArray = array_filter(array_map('trim', explode(',', $examType)));
$caseNum = htmlspecialchars($case['case_number']);
$patientID = htmlspecialchars($case['patient_number'] ?? $case['patient_id']);
$age = htmlspecialchars($case['age']);
$sex = htmlspecialchars(ucfirst($case['sex']));
$branch = htmlspecialchars($case['branch_name']);
$dateExam = $case['date_completed'] ?? $case['created_at'];
$dateStr = date('F d, Y', strtotime($dateExam));

$radtechName = $case['radtech_name'] ?? '';
$radtechTitle = $case['radtech_title'] ?? '';
$radtechSignature = $case['radtech_signature'] ?? '';

// Final fallback if still empty
if (empty($radtechName))
    $radtechName = 'Radiologic Technologist';

// Combine name and titles for the display
$radtechFullNameWithTitle = htmlspecialchars($radtechName);
if (!empty($radtechTitle)) {
    $radtechFullNameWithTitle .= ', ' . htmlspecialchars($radtechTitle);
}

// Get Branch Metadata via Model (Address, Contacts)
$bInfoMatch = $branchModel->getBranchMetadata($branch);
$bAddressRows = explode("\n", $bInfoMatch['address']);

$clinical = nl2br(htmlspecialchars($case['clinical_information'] ?? '—'));
$findingsRaw = trim($case['findings'] ?? '');
$impressionRaw = trim($case['impression'] ?? '');
$recommend = nl2br(htmlspecialchars($case['recommendation'] ?? '—'));

$isMultiExam = false;
$parsedData = [];
if (!empty($findingsRaw) && (str_starts_with($findingsRaw, '{') || str_starts_with($findingsRaw, '['))) {
    $decoded = json_decode($findingsRaw, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        $isMultiExam = true;
        $parsedData = $decoded;
    }
}

if (!$isMultiExam) {
    // Clean prefix if exists (e.g. "[ABDOMEN (PEDIA)] ")
    $cleanExamType = $case['exam_type'] ?? '';
    if (!empty($cleanExamType)) {
        $prefix = "[{$cleanExamType}] ";
        if (str_starts_with($findingsRaw, $prefix)) {
            $findingsRaw = substr($findingsRaw, strlen($prefix));
        }
        if (str_starts_with($impressionRaw, $prefix)) {
            $impressionRaw = substr($impressionRaw, strlen($prefix));
        }
        $findingsStr = nl2br(htmlspecialchars($findingsRaw ?: '—'));
        $impressionStr = nl2br(htmlspecialchars($impressionRaw ?: '—'));
    } else {
        $findingsStr = nl2br(htmlspecialchars($findingsRaw ?: '—'));
        $impressionStr = nl2br(htmlspecialchars($impressionRaw ?: '—'));
    }
}

?><!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Radiology Report — <?= $caseNum ?></title>
    <link rel="stylesheet" href="/<?= PROJECT_DIR ?>/public/assets/vendor/bootstrap-icons/bootstrap-icons.min.css">
    <style>
        /* ── Google Fonts ── */
        @import url('https://fonts.googleapis.com/css2?family=Raleway:ital,wght@0,100..900;1,100..900&display=swap');

        /* ── Reset & Base ── */
        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 11pt;
            color: #1a1a1a;
            background: #f0f0f0;
        }

        /* ── Watermark ── */
        .watermark {
            position: absolute;
            top: 55%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 95%;
            max-width: 900px;
            opacity: 0.12;
            z-index: 0;
            pointer-events: none;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .watermark img {
            width: 100%;
            height: auto;
            object-fit: contain;
        }

        /* ── Page Shell ── */
        .page {
            width: 215.9mm;
            /* US Letter */
            min-height: 279.4mm;
            margin: 20px auto;
            background: #fff;
            padding: 14mm 14mm 40mm 14mm;
            border: 1px solid #ccc;
            box-shadow: 0 4px 24px rgba(0, 0, 0, .12);
            position: relative;
            overflow: hidden;
        }

        .page::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            pointer-events: none;
            z-index: 10;
            opacity: 0.15;
            /* Adjusted opacity to ensure readability under larger text */
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='400' height='400'%3E%3Ctext x='200' y='200' font-family='Arial, sans-serif' font-size='55' font-weight='900' fill='%23000000' fill-opacity='0.6' text-anchor='middle' transform='rotate(-45 200 200)'%3ECONFIDENTIAL%3C/text%3E%3C/svg%3E");
            background-repeat: repeat;
        }


        .image-page {
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            align-items: center;
        }

        @media print {
            .image-page {
                page-break-before: always;
            }
        }

        .report-header,
        .title-report,
        .info-box,
        .section,
        .signature-block,
        .report-footer {
            position: relative;
        }

        /* ── Header ── */
        .report-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-bottom: 15px;
            border-bottom: 1.5px solid #111;
            margin-bottom: 15px;
        }

        .header-left {
            display: flex;
            align-items: center;
        }

        .header-logo {
            width: 100px;
            height: 100px;
            flex-shrink: 0;
            object-fit: contain;
            margin-right: 15px;
        }

        .header-text {
            font-family: 'Raleway', sans-serif;
            display: flex;
            flex-direction: column;
        }

        .header-text h1 {
            font-size: 45px;
            font-weight: 700;
            color: #c0392b;
            letter-spacing: 2px;
            margin: 0;
            line-height: 0.95;
            transform: scaleY(1.05);
            transform-origin: bottom left;
        }

        .header-text p {
            font-size: 16px;
            font-weight: 600;
            color: #c0392b;
            margin: 0;
            margin-left: 5px;
            margin-top: 4px;
            line-height: 1;
            letter-spacing: 1.5px;
        }

        .header-right {
            text-align: left;
            font-family: Arial, sans-serif;
            font-size: 8.5pt;
            color: #444;
            max-width: 320px;
        }

        .header-right .icon-row {
            display: flex;
            align-items: flex-start;
            margin-bottom: 4px;
        }

        .header-right .icon-row i {
            font-style: normal;
            margin-right: 6px;
            font-size: 10pt;
            margin-top: 0px;
            line-height: 1;
        }

        .header-right .text-col {
            display: flex;
            flex-direction: column;
            line-height: 1.35;
        }

        /* ── Report Title ── */
        .report-title {
            text-align: center;
            font-family: 'Raleway', sans-serif;
            font-size: 13pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin: 12px 0 14px;
            color: #1a1a1a;
        }

        .title-report {
            border: 4px double #777;
            padding: 5px 4px;
            text-align: center;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-top: 20px;
            margin-bottom: 20px;
        }

        .title-report h1 {
            color: #c0392b;
        }

        .report-info {
            border: 1px solid #111;
            padding: 20px;
            padding-bottom: 50px;
            margin-bottom: 60px;
        }

        /* ── Patient Info Box ── */
        .info-box {
            background: #fafafa;
            border: 1px solid #ddd;
            border-left: 4px solid #c0392b;
            border-radius: 3px;
            padding: 10px 14px;
            margin-bottom: 16px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 6px 20px;
        }

        .info-item label {
            display: block;
            font-size: 7.5pt;
            font-family: 'Raleway', sans-serif;
            text-transform: uppercase;
            color: #888;
            letter-spacing: .5px;
            margin-bottom: 1px;
        }

        .info-item span {
            font-size: 10.5pt;
            font-weight: bold;
            color: #111;
        }

        .exam-type {
            margin-bottom: 20px;
        }

        .exam-type label {
            display: block;
            font-size: 7.5pt;
            font-family: 'Raleway', sans-serif;
            text-transform: uppercase;
            color: #888;
            letter-spacing: .5px;
            margin-bottom: 6px;
        }

        .exam-type-chips {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
        }

        .exam-type-chip {
            display: inline-flex;
            align-items: center;
            border: 1.5px solid #c0392b;
            border-radius: 4px;
            background: #fff5f5;
            padding: 2px 8px;
            font-size: 9pt;
            font-weight: bold;
            color: #c0392b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            line-height: 1.5;
        }

        /* ── Sections ── */
        .section {
            margin-bottom: 14px;
        }

        .section-title {
            font-family: 'Raleway', sans-serif;
            font-size: 8pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #000;
            border-bottom: 1px solid #cccccc;
            padding-bottom: 3px;
            margin-bottom: 6px;
        }

        .section-body {
            font-size: 10.5pt;
            line-height: 1.65;
            color: #222;
            min-height: 28px;
            word-break: break-word;
            overflow-wrap: break-word;
        }

        /* ── Signature Block ── */
        .signature-block {
            margin-top: 32px;
            padding-top: 20px;
            display: flex;
            justify-content: space-between;
        }

        .sig-inner {
            text-align: center;
            min-width: 200px;
        }

        .sig-line {
            border-top: 1.5px solid #333;
            margin-bottom: 4px;
        }

        .sig-name {
            font-family: 'Times New Roman', serif;
            font-size: 10pt;
            font-weight: bold;
            color: #111;
            margin-bottom: 5px;
            text-transform: uppercase;
        }

        .sig-title {
            font-family: Arial, sans-serif;
            font-weight: bold;
            font-size: 9pt;
            color: #666;
            margin-top: 2px;
            text-transform: uppercase;
        }

        /* ── Branches Ribbon ── */
        .branches {
            position: absolute;
            bottom: 20mm;
            left: 14mm;
            right: 14mm;
            text-align: center;
            font-family: Arial, sans-serif;
            font-size: 7.5pt;
            font-weight: bold;
            color: #7b7b7bff;
            background: linear-gradient(90deg, rgba(255, 180, 150, 0.6) 0%, rgba(255, 220, 200, 0.5) 50%, rgba(255, 180, 150, 0.6) 100%);
            padding: 6px 0;
            letter-spacing: 0.5px;
            word-spacing: 1px;
            white-space: nowrap;
        }

        /* ── Footer ── */
        .report-footer {
            position: absolute;
            bottom: 12mm;
            left: 14mm;
            right: 14mm;
            display: flex;
            justify-content: space-between;
            font-family: Arial, sans-serif;
            font-size: 8pt;
            color: #888;
        }

        /* ── Print controls (screen only) ── */
        .print-bar {
            display: flex;
            justify-content: center;
            gap: 12px;
            padding: 14px;
            background: #333;
        }

        .btn-print {
            background: #c0392b;
            color: #fff;
            border: none;
            padding: 9px 28px;
            font-size: 13px;
            font-family: Arial, sans-serif;
            font-weight: bold;
            border-radius: 6px;
            cursor: pointer;
            letter-spacing: .5px;
        }

        .btn-print:hover {
            background: #a93226;
        }

        .btn-download {
            background: #2980b9;
            color: #fff;
            border: none;
            padding: 9px 28px;
            font-size: 13px;
            font-family: Arial, sans-serif;
            font-weight: bold;
            border-radius: 6px;
            cursor: pointer;
            letter-spacing: .5px;
        }

        .btn-download:hover {
            background: #1f618d;
        }

        .btn-close {
            background: #555;
            color: #fff;
            border: none;
            padding: 9px 20px;
            font-size: 13px;
            font-family: Arial, sans-serif;
            border-radius: 6px;
            cursor: pointer;
        }

        /* ── Print Media ── */
        @page {
            size: letter;
            margin: 0mm !important;
        }

        @media print {
            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            body {
                background: #fff;
                margin: 0;
                padding: 0;
            }

            .print-bar {
                display: none;
            }

            .page {
                margin: 0;
                border: none;
                box-shadow: none;
                width: 100%;
                min-height: 100vh;
                padding: 15mm 18mm 40mm;
            }
        }

        <?php if ($isPreview): ?>
            /* Preview mode styles */
            body {
                background: transparent;
                margin: 0;
                padding: 0;
                overflow: hidden;
                /* NO SCROLLING AT ALL */
                display: flex;
                align-items: center;
                justify-content: center;
                height: 100vh;
                width: 100vw;
            }

            .page {
                margin: 0;
                border: 1px solid #ddd;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
                zoom: 1 !important;
                /* Reset any native zoom */
                flex-shrink: 0;
                /* Prevent flexbox from squishing the layout */
                width: 816px;
                /* Force exact pixel width for scaling */
                min-height: 1056px;
                /* Force exact pixel height */
                position: relative;
            }

        <?php endif; ?>
    </style>
</head>

<body>
    <div id="loader-overlay"
        style="position:fixed; top:0; left:0; width:100vw; height:100vh; background:#f9fafb; z-index:99999; display:flex; flex-direction:column; align-items:center; justify-content:center; font-family:'Raleway', Arial, sans-serif; text-align:center; padding:24px; box-sizing:border-box;">
        <div
            style="width: 56px; height: 56px; border: 4px solid #f3f3f3; border-top: 4px solid #c0392b; border-radius: 50%; animation: spin 1s linear infinite; margin-bottom: 24px;">
        </div>
        <h2 style="color:#111; margin:0 0 12px; font-size: clamp(20px, 5vw, 24px); font-weight: 700;">Preparing Your
            Final Report</h2>
        <p style="color:#666; margin:0; font-size: clamp(14px, 4vw, 16px); max-width: 400px; line-height: 1.5;">Please
            wait while we generate a secure PDF. Your download will start automatically in a moment.</p>
        <style>
            @keyframes spin {
                0% {
                    transform: rotate(0deg);
                }

                100% {
                    transform: rotate(360deg);
                }
            }
        </style>
    </div>
    <?php if (!$isPreview): ?>
        <!-- Print toolbar (hidden when printing) -->
        <div class="print-bar">
            <button class="btn-print" onclick="window.print()"><svg xmlns="http://www.w3.org/2000/svg" width="16"
                    height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round"
                    style="display:inline-block;vertical-align:middle;margin-right:6px;">
                    <polyline points="6 9 6 2 18 2 18 9" />
                    <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2" />
                    <rect x="6" y="14" width="12" height="8" />
                </svg> Print</button>
            <button class="btn-download" onclick="downloadPDF()"><svg xmlns="http://www.w3.org/2000/svg" width="16"
                    height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round"
                    style="display:inline-block;vertical-align:middle;margin-right:6px;">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                    <polyline points="7 10 12 15 17 10" />
                    <line x1="12" y1="15" x2="12" y2="3" />
                </svg> Download PDF</button>
            <button class="btn-close" onclick="window.close()"><svg xmlns="http://www.w3.org/2000/svg" width="16"
                    height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round"
                    style="display:inline-block;vertical-align:middle;margin-right:4px;">
                    <line x1="18" y1="6" x2="6" y2="18" />
                    <line x1="6" y1="6" x2="18" y2="18" />
                </svg> Close</button>
        </div>
    <?php endif; ?>

    <!-- Report Page -->
    <div class="page">
        <!-- Watermark -->
        <div class="watermark">
            <img src="/<?= PROJECT_DIR ?>/public/assets/img/logo/logo-template.png" alt="Watermark">
        </div>

        <!-- Header -->
        <div class="report-header">
            <div class="header-left">
                <img src="/<?= PROJECT_DIR ?>/public/assets/img/logo/citilife-logo.png" alt="CitiLife Logo"
                    class="header-logo">
                <div class="header-text">
                    <h1>CITILIFE</h1>
                    <p>DIAGNOSTIC CENTER</p>
                </div>
            </div>
            <div class="header-right">
                <div class="icon-row" style="margin-bottom: 6px;">
                    <i class="bi bi-radioactive"
                        style="color:#8b0000; font-size:13px; margin-right:7px; margin-top:1px; flex-shrink:0;"></i>
                    <div class="text-col">
                        <?php foreach ($bAddressRows as $row): ?>
                            <span <?= strpos($row, '(') !== false ? 'style="font-size:7.5pt;color:#777;"' : 'style="font-weight:bold;color:#8b0000;"' ?>><?= htmlspecialchars($row) ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="icon-row">
                    <i class="bi bi-telephone-fill"
                        style="color:#666; font-size:12px; margin-right:7px; flex-shrink:0;"></i>
                    <div class="text-col" style="font-weight:bold;color:#333;">
                        <span><?= htmlspecialchars($bInfoMatch['contact1']) ?></span>
                    </div>
                </div>
                <?php if (!empty($bInfoMatch['contact2'])): ?>
                    <div class="icon-row">
                        <i class="bi bi-telephone-fill"
                            style="color:#666; font-size:12px; margin-right:7px; flex-shrink:0;"></i>
                        <div class="text-col" style="font-weight:bold;color:#333;">
                            <span><?= htmlspecialchars($bInfoMatch['contact2']) ?></span>
                        </div>
                    </div>
                <?php endif; ?>
                <?php if (!empty($bInfoMatch['contact3'])): ?>
                    <div class="icon-row">
                        <i class="bi bi-telephone-fill"
                            style="color:#666; font-size:12px; margin-right:7px; flex-shrink:0;"></i>
                        <div class="text-col" style="font-weight:bold;color:#333;">
                            <span><?= htmlspecialchars($bInfoMatch['contact3']) ?></span>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="title-report">
            <h1>Roentgenological Report</h1>
        </div>
        <!-- Patient Info -->
        <div class="info-box">
            <div class="info-grid">
                <div class="info-item">
                    <label>Patient Name</label>
                    <span><?= $fullName ?></span>
                </div>
                <div class="info-item">
                    <label>Patient No.</label>
                    <span>
                        <?= $patientID ?>
                    </span>
                </div>
                <div class="info-item">
                    <label>Age / Sex</label>
                    <span><?= $age ?> / <?= $sex ?></span>
                </div>


                <div class="info-item">
                    <label>Case No.</label>
                    <span>
                        <?= $caseNum ?>
                    </span>
                </div>
                <div class="info-item">
                    <label>Branch</label>
                    <span><?= $branch ?></span>
                </div>
                <div class="info-item">
                    <label>D&#97;te of Examination</label>
                    <span><?= $dateStr ?></span>
                </div>
            </div>
        </div>
        <div class="report-info">
            <?php if ($isMultiExam && !empty($parsedData)): ?>
                <?php
                $count = 0;
                $total = count($parsedData);
                foreach ($parsedData as $examName => $data):
                    $count++;
                    $isLast = ($count === $total);
                    ?>
                    <div
                        style="margin-top: 15px; margin-bottom: 20px; <?= !$isLast ? 'border-bottom: 1.5px dashed #e0e0e0; padding-bottom: 20px;' : '' ?>">
                        <h3
                            style="font-family:'Raleway',sans-serif;font-size:11pt;color:#c0392b;margin-bottom:14px;text-transform:uppercase;display:flex;align-items:center;">
                            <span
                                style="color:#c0392b;font-size:16pt;margin-right:8px;line-height:0;margin-top:-2px;">&bull;</span><span
                                style="border-bottom: 1.5px solid #c0392b; padding-bottom: 2px; font-family: 'Times New Roman', Times, serif;"><?= htmlspecialchars($examName) ?></span>
                        </h3>

                        <div class="section" style="margin-left: 14px;">
                            <div class="section-title">Radiographic Findings</div>
                            <div class="section-body"><?= nl2br(htmlspecialchars($data['findings'] ?? '—')) ?></div>
                        </div>

                        <div class="section" style="margin-left: 14px;">
                            <div class="section-title">Impression</div>
                            <div class="section-body"><?= nl2br(htmlspecialchars($data['impression'] ?? '—')) ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="exam-block">
                    <h3
                        style="font-family:'Raleway',sans-serif;font-size:11pt;color:#c0392b;margin-bottom:14px;text-transform:uppercase;display:flex;align-items:center;">
                        <span
                            style="color:#c0392b;font-size:16pt;margin-right:8px;line-height:0;margin-top:-2px;">&bull;</span><span
                            style="border-bottom: 1.5px solid #c0392b; padding-bottom: 2px; font-family: 'Times New Roman', Times, serif;"><?= htmlspecialchars($examType) ?></span>
                    </h3>
                    <!-- Findings -->
                    <div class="section" style="margin-left: 14px;">
                        <div class="section-title">Radiographic Findings</div>
                        <div class="section-body"><?= $findingsStr ?></div>
                    </div>

                    <!-- Impression -->
                    <div class="section" style="margin-left: 14px;">
                        <div class="section-title">Impression</div>
                        <div class="section-body"><?= $impressionStr ?></div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div
            style="border: 1px solid #111; padding: 4px 8px; font-family: 'Times New Roman', Times, serif; font-size: 8pt; font-weight: bold; width: fit-content; margin-bottom: 12px;">
            *Results are purely based on radiographic findings. Please correlate clinically.
        </div>
        <!-- Signature -->
        <div class="signature-block">
            <div class="sig-inner">
                <?php if (!empty($radtechSignature)): ?>
                    <div class="sig-image"
                        style="height:50px; display:flex; align-items:flex-end; justify-content:center; margin-bottom:-10px;">
                        <img src="<?= $radtechSignature ?>" style="max-height:60px; max-width:180px; object-fit:contain;">
                    </div>
                <?php else: ?>
                    <div style="height:40px;"></div>
                <?php endif; ?>
                <div class="sig-name"
                    style="display:inline-block; border-bottom:1.5px solid #333; padding-bottom:2px; margin-bottom:4px;">
                    <?= $radtechFullNameWithTitle ?>
                </div>
                <div class="sig-title">Radiologic Technologist</div>
            </div>
            <div class="sig-inner">
                <?php if (!empty($radSignature)): ?>
                    <div class="sig-image"
                        style="height:50px; display:flex; align-items:flex-end; justify-content:center; margin-bottom:-10px;">
                        <img src="<?= $radSignature ?>" style="max-height:60px; max-width:180px; object-fit:contain;">
                    </div>
                <?php else: ?>
                    <div style="height:40px;"></div>
                <?php endif; ?>
                <div class="sig-name"
                    style="display:inline-block; border-bottom:1.5px solid #333; padding-bottom:2px; margin-bottom:4px;">
                    <?= $radFullNameWithTitle ?>
                </div>
                <div class="sig-title">Radiologist</div>
            </div>
        </div>
        <div
            style="text-align: center; margin: 18px auto 12px; max-width: 92%; padding: 12px 18px; border: 1.5px dashed #c0392b; background-color: #fffdfd; border-radius: 4px;">
            <strong
                style="color: #c0392b; font-family: 'Raleway', sans-serif; font-size: 11pt; display: block; margin-bottom: 4px; letter-spacing: 0.5px;">CONFIDENTIAL
                MEDICAL RECORD</strong>
            <p style="font-family: Arial, sans-serif; font-size: 8.5pt; color: #555; line-height: 1.4; margin: 0;">This
                document contains sensitive patient information. Unauthorized access, screenshotting, copying, sharing,
                or distribution is prohibited.</p>
        </div>

        <div class="branches">
            GAPAN &bull; PE&Ntilde;ARANDA &bull; GENERAL TINIO &bull; STO DOMINGO &bull; SAN ANTONIO &bull; PANTABANGAN
            &bull; BONGABON
        </div>
        <!-- Footer -->
        <div class="report-footer">
            <span>CitiLife Diagnostic — <?= $branch ?> Branch</span>
            <span>Case: <?= $caseNum ?> &nbsp;|&nbsp; Generated: <?= date('M d, Y h:i A') ?></span>
        </div>

    </div>

    <!-- X-Ray Image Page (Only rendered when printing) -->
    <?php if (!$isPreview && !empty($case['image_path'])):
        $savedPaths = [];
        $decoded = json_decode($case['image_path'], true);
        if (is_array($decoded)) {
            $savedPaths = $decoded;
        } else {
            $savedPaths = [$case['image_path']];
        }
        ?>
        <?php foreach ($savedPaths as $idx => $sPath): ?>
            <div class="page image-page">

                <div
                    style="width: 100%; text-align: center; margin-bottom: 20px; font-family: 'Raleway', sans-serif; position: relative; z-index: 1;">
                    <h2
                        style="font-size: 16pt; font-weight: bold; color: #111; letter-spacing: 1px; text-transform: uppercase;">
                        X-Ray Plate Image <?= count($savedPaths) > 1 ? '(' . ($idx + 1) . ')' : '' ?></h2>
                    <p style="font-family: Arial, sans-serif; font-size: 10pt; color: #555; margin-top: 5px;">Case No:
                        <?= $caseNum ?> &nbsp;|&nbsp; Patient Name: <?= $fullName ?>
                    </p>
                </div>

                <div
                    style="flex: 1; width: 100%; display: flex; align-items: center; justify-content: center; overflow: hidden; border: 1px solid #ccc; background: #000; padding: 4px; box-shadow: inset 0 0 10px rgba(0,0,0,0.5); position: relative; z-index: 1;">
                    <img src="<?= htmlspecialchars($sPath) ?>" alt="X-Ray Image"
                        style="max-width: 100%; max-height: 220mm; object-fit: contain;">
                </div>

                <div class="report-footer">
                    <span>CitiLife Diagnostic — <?= $branch ?> Branch</span>
                    <span>Case: <?= $caseNum ?> &nbsp;|&nbsp; Generated: <?= date('M d, Y h:i A') ?></span>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php if (!$isPreview): ?>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
        <script>
            // Automatically open print dialog or download when page loads
            window.addEventListener('load', () => {
                // Small delay so styles fully render
                setTimeout(() => {
                    downloadPDF();
                }, 500);
            });

            function downloadPDF() {
                const btnBar = document.querySelector('.print-bar');
                if (btnBar) btnBar.style.display = 'none';

                // Create a temporary container for PDF generation to ensure perfect rendering
                const element = document.createElement('div');

                // Loop through each .page and append a cloned version
                const pages = document.querySelectorAll('.page');
                pages.forEach((page, index) => {
                    const clone = page.cloneNode(true);
                    // Force strict size and remove borders to prevent html2canvas 1px overflow spilling to a blank page
                    clone.style.margin = '0';
                    clone.style.boxShadow = 'none';
                    clone.style.border = 'none';
                    element.appendChild(clone);

                    // Add html2pdf explicit native page break between pages
                    if (index < pages.length - 1) {
                        const spacer = document.createElement('div');
                        spacer.className = 'html2pdf__page-break';
                        element.appendChild(spacer);
                    }
                });

                const opt = {
                    margin: [0, 0, 0, 0],
                    filename: 'Radiology_Report_<?= htmlspecialchars($caseNum) ?>.pdf',
                    image: { type: 'jpeg', quality: 1 },
                    html2canvas: { scale: 2, useCORS: true, logging: false },
                    jsPDF: { unit: 'in', format: 'letter', orientation: 'portrait' },
                    pagebreak: { mode: 'legacy' } // Rely on html2pdf__page-break
                };

                // Use html2pdf
                html2pdf().set(opt).from(element).save().then(() => {
                    document.getElementById('loader-overlay').innerHTML = `
                        <div style="width: 56px; height: 56px; border-radius: 50%; background: #22c55e; display: flex; align-items: center; justify-content: center; margin-bottom: 24px; box-shadow: 0 4px 12px rgba(34, 197, 94, 0.2);">
                            <svg style="width: 28px; height: 28px; color: white;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                        </div>
                        <h2 style="color:#111; margin:0 0 12px; font-size: clamp(20px, 5vw, 24px); font-weight: 700;">Report Downloaded!</h2>
                        <p style="color:#666; margin:0; font-size: clamp(14px, 4vw, 16px); max-width: 400px; line-height: 1.5;">Returning you to your records safely...</p>
                    `;
                    setTimeout(() => {
                        if (document.referrer) {
                            window.location.href = document.referrer;
                        } else {
                            window.history.back();
                        }
                    }, 1500);
                });
            }
        </script>
    <?php else: ?>
        <script>
            // Fit the entire report inside the container seamlessly (like object-fit: contain)
            function fitToContainer() {
                const page = document.querySelector('.page');
                const containerWidth = window.innerWidth;
                const containerHeight = window.innerHeight;

                // Allow a small padding inside the iframe
                const padding = 16;
                const availWidth = containerWidth - padding;
                const availHeight = containerHeight - padding;

                const pageWidth = 816; // 215.9mm
                const pageHeight = 1056; // 279.4mm

                const scale = Math.min(availWidth / pageWidth, availHeight / pageHeight);

                page.style.transformOrigin = 'center center';
                page.style.transform = `scale(${scale})`;
            }

            window.addEventListener('resize', fitToContainer);
            window.addEventListener('DOMContentLoaded', fitToContainer);
            window.addEventListener('load', fitToContainer);
        </script>
    <?php endif; ?>
</body>

</html>