<?php
require_once __DIR__ . '/../../../config/database.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$sessionUserId = $_SESSION['user_id'] ?? 0;
$sessionRole = $_SESSION['role'] ?? '';

$caseModel = new \CaseModel($pdo);
$userModel = new \UserModel($pdo);
$branchModel = new \BranchModel($pdo);

$id = (int) ($_GET['id'] ?? 0);
$isPreview = filter_var($_GET['preview'] ?? false, FILTER_VALIDATE_BOOLEAN);
$isDownload = filter_var($_GET['download'] ?? false, FILTER_VALIDATE_BOOLEAN);
$isSnapshot = filter_var($_GET['snapshot'] ?? false, FILTER_VALIDATE_BOOLEAN);

if (!$id) {
    die('<p style="font-family:sans-serif;padding:2rem;color:red;">Invalid case ID.</p>');
}

// 1. Fetch case details (Backend logic)
$case = $caseModel->getCaseById($id);

if (!$case) {
    die('<p style="font-family:sans-serif;padding:2rem;color:red;">Case not found.</p>');
}

// 2. Fetch radiologist info (Backend logic)
$radName = $case['radiologist_name'] ?? 'Radiologist on Duty';
$radTitle = $case['radiologist_title'] ?? '';
$radSignature = $case['radiologist_signature'] ?? '';

// Fallback for Radiologist: If viewing and not assigned yet, show current logged-in Radiologist
if (empty($case['radiologist_id']) && $sessionRole === 'radiologist' && $sessionUserId > 0) {
    $currRad = $userModel->getUserById($sessionUserId);
    if ($currRad) {
        $radName = !empty($currRad['full_name_report']) ? $currRad['full_name_report'] : ($currRad['name'] ?? 'Radiologist on Duty');
        $radTitle = $currRad['professional_title'] ?? '';
        $radSignature = $currRad['signature'] ?? '';
    }
}

$radFullNameWithTitle = htmlspecialchars($radName);
if (!empty($radTitle)) {
    $radFullNameWithTitle .= ', ' . htmlspecialchars($radTitle);
}
// Add DR. prefix if not present for non-placeholder names
if ($radName !== 'Radiologist on Duty' && !str_contains(strtoupper($radFullNameWithTitle), 'DR.')) {
    $radFullNameWithTitle = 'DR. ' . $radFullNameWithTitle;
}

$fullName = htmlspecialchars(strtoupper($case['first_name'] . ' ' . $case['last_name']));
$examType = htmlspecialchars($case['exam_type']);
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

// Fallback: If no RadTech recorded in case, use current logged-in staff info
if (empty($radtechName) && $sessionUserId > 0) {
    $currUser = $userModel->getUserById($sessionUserId);
    if ($currUser) {
        $radtechName = !empty($currUser['full_name_report']) ? $currUser['full_name_report'] : ($currUser['name'] ?? '');
        $radtechTitle = $currUser['professional_title'] ?? '';
        $radtechSignature = $currUser['signature'] ?? '';
    }
}

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

?>
<!DOCTYPE html>
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
            background: #d4d4d8;
            /* Dark grey background */
            margin: 0;
            padding: 0 0 40px 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            min-height: 100vh;
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
            width: 210mm;
            /* A4 */
            min-height: 297mm;
            margin: 0 auto 30px auto;
            background: #fff;
            padding: 8mm 10mm;
            border:
                <?= ($isPreview && !isset($_GET['no_shadow'])) ? 'none' : (isset($_GET['no_shadow']) ? 'none' : '1px solid #ccc') ?>
            ;
            box-shadow:
                <?= ($isPreview && !isset($_GET['no_shadow'])) ? '0 10px 40px rgba(0,0,0,0.15)' : (isset($_GET['no_shadow']) ? 'none' : '0 4px 24px rgba(0, 0, 0, .12)') ?>
            ;
            position: relative;
            overflow: visible;
            display: flex;
            flex-direction: column;
            border-radius:
                <?= ($isPreview && !isset($_GET['no_shadow'])) ? '4px' : '0' ?>
            ;
            flex-shrink: 0;
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
            z-index: 1;
        }

        /* ── Header ── */
        .report-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1.5px solid #111;
            width: 100%;
            box-sizing: border-box;
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
            text-align: center;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-top: 10px;
            margin-bottom: 10px;
            padding: 2px 4px;
            box-sizing: border-box;
            width: 100%;
        }

        .title-report h1 {
            color: #c0392b;
            margin: 0;
            font-size: 16pt;
        }

        .report-info {
            border: 1px solid #111;
            padding: 14px 18px;
            margin-bottom: 14px;
            width: 100%;
            box-sizing: border-box;
        }

        /* ── Patient Info Box ── */
        .info-box {
            background: #fafafa;
            border: 1px solid #ddd;
            border-left: 4px solid #c0392b;
            border-radius: 0;
            padding: 10px 14px;
            margin-bottom: 16px;
            margin-top: 12px;
            width: 100%;
            box-sizing: border-box;
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
            margin-bottom: 10px;
        }

        .exam-type label {
            display: block;
            font-size: 7.5pt;
            font-family: 'Raleway', sans-serif;
            font-weight: 700;
            color: #888;
            text-transform: uppercase;
            letter-spacing: .5px;
            margin-bottom: 4px;
        }

        .exam-type span {
            font-size: 14pt;
            font-weight: 700;
            color: #c0392b;
            text-transform: uppercase;
        }

        .exam-block h3 {
            font-weight: 800;

        }

        .exam-block {
            padding-bottom: 12px;
            margin-bottom: 12px;
            border-bottom: 1.5px dashed #ccc;
        }

        .exam-block:last-child {
            border-bottom: none;
            padding-bottom: 0;
            margin-bottom: 0;
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
            border-bottom: 1.5px solid #cccccc;
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
            margin-top: 18px;
            padding-top: 8px;
            display: flex;
            justify-content: space-between;
        }

        .sig-inner {
            text-align: center;
            min-width: 200px;
        }

        .sig-name {
            font-family: 'Times New Roman', serif;
            font-size: 10pt;
            font-weight: bold;
            color: #111;
            margin-bottom: 2px;
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
            text-align: center;
            font-family: Arial, sans-serif;
            font-size: 7.5pt;
            font-weight: bold;
            color: #7b7b7bff;
            background: linear-gradient(90deg, rgba(255, 180, 150, 0.6) 0%, rgba(255, 220, 200, 0.5) 50%, rgba(255, 180, 150, 0.6) 100%);
            padding: 5px 0;
            letter-spacing: 0.5px;
            word-spacing: 8px;
            white-space: nowrap;
            margin-top: 14px;
        }

        /* ── Footer ── */
        .report-footer {
            display: flex;
            justify-content: space-between;
            font-family: Arial, sans-serif;
            font-size: 8pt;
            color: #888;
            border-top: 1px solid #eee;
            padding-top: 8px;
            margin-top: 10px;
        }

        /* ── Print controls (screen only) ── */
        .print-bar {
            position: sticky;
            top: 0;
            z-index: 1000;
            display: flex;
            justify-content: center;
            gap: 12px;
            padding: 14px;
            background: #f4f4f5;
            /* Light gray */
            width: 100%;
            margin-bottom: 20px;
            border-bottom: 1px solid #e4e4e7;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .btn-print,
        .btn-download,
        .btn-close {
            background: #ffffff;
            color: #374151;
            border: 1px solid #d1d5db;
            padding: 8px 16px;
            font-size: 13px;
            font-family: Arial, sans-serif;
            font-weight: 500;
            border-radius: 4px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            transition: all 0.2s ease;
        }

        .btn-print i,
        .btn-download i,
        .btn-close i {
            margin-right: 6px;
        }

        .btn-print:hover,
        .btn-download:hover,
        .btn-close:hover {
            background: #f9fafb;
            border-color: #9ca3af;
        }

        /* ── Print Media ── */
        @page {
            size: A4;
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

            .print-bar,
            .no-print {
                display: none !important;
            }

            .page {
                margin: 0;
                border: none;
                box-shadow: none;
                width: 210mm;
                min-height: 296mm;
                height: 296mm;
                padding: 8mm 12mm;
                display: flex;
                flex-direction: column;
                overflow: hidden;
                page-break-after: always;
            }

            .page:last-of-type {
                page-break-after: auto;
            }
        }

        <?php if ($isPreview): ?>
            @media screen {

                /* Preview mode styles */
                body {
                    background: #f4f4f5;
                    /* Light gray to match the rest of the UI */
                }

                .page {
                    margin: 0 auto 30px auto;
                    /* Ensure gap between pages */
                    border: 1px solid #ddd;
                    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
                    zoom: 1 !important;
                    flex-shrink: 0;
                    width: 794px;
                    min-height: 1123px;
                    position: relative;
                }
            }

        <?php endif; ?>
    </style>
</head>

<body>
    <?php if ($isDownload): ?>
        <div id="downloadOverlay"
            style="position:fixed; top:0; left:0; width:100%; height:100%; background:white; z-index:99999; display:flex; flex-direction:column; align-items:center; justify-content:center; font-family:sans-serif;">
            <div id="loadingSpinner"
                style="width: 50px; height: 50px; border: 5px solid #f3f3f3; border-top: 5px solid #c0392b; border-radius: 50%; animation: spin 1s linear infinite; margin-bottom: 20px;">
            </div>
            <div id="successCheck"
                style="display: none; width: 50px; height: 50px; border-radius: 50%; background: #22c55e; color: white; align-items: center; justify-content: center; font-size: 30px; margin-bottom: 20px; font-weight: bold;">
                ✓</div>
            <h2 id="loadingTitle" style="color:#333;">Generating PDF...</h2>
            <p id="loadingDesc" style="color:#666;">Please wait while your document is being prepared. You will be
                redirected shortly.</p>
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
    <?php endif; ?>

    <!-- Print toolbar (hidden when printing) -->
    <div class="print-bar no-print">
        <button type="button" class="btn-download" onclick="downloadPDF()"><i class="bi bi-file-earmark-pdf"></i>
            Generate PDF</button>
        <button type="button" class="btn-print" onclick="window.print()"><i class="bi bi-printer"></i> Print
            Report</button>
    </div>

    <?php
    // ── Helper: render the shared page header + patient info block ──
    function renderPageHeader($bAddressRows, $bInfoMatch, $fullName, $patientID, $age, $sex, $caseNum, $branch, $dateStr, $examType)
    {
        ?>
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
                    <span><?= $patientID ?></span>
                </div>
                <div class="info-item">
                    <label>Age / Sex</label>
                    <span><?= $age ?> / <?= $sex ?></span>
                </div>
                <div class="info-item">
                    <label>Case No.</label>
                    <span><?= $caseNum ?></span>
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
    <?php } ?>

    <?php
    // ── Helper: render the footer group (signature + branches + footer) ──
    function renderFooterGroup($radtechFullNameWithTitle, $radtechSignature, $radFullNameWithTitle, $radSignature, $branch, $caseNum)
    {
        ?>
        <!-- Spacer to push footer to bottom -->
        <div style="flex-grow: 1;"></div>
        <!-- Footer Group -->
        <div class="footer-group" style="page-break-inside: avoid; width: 100%;">
            <div
                style="border: 1px solid #111; padding: 4px 8px; font-family: 'Times New Roman', Times, serif; font-size: 8pt; font-weight: bold; width: fit-content; margin-bottom: 12px;">
                *Results are purely based on radiographic findings. Please correlate clinically.
            </div>
            <div class="signature-block">
                <div class="sig-inner">
                    <?php if (!empty($radtechSignature)): ?>
                        <div class="sig-image"
                            style="height:50px; display:flex; align-items:flex-end; justify-content:center; margin-bottom:-10px;">
                            <img src="<?= $radtechSignature ?>" style="max-height:60px; max-width:180px; object-fit:contain;">
                        </div>
                    <?php else: ?>
                        <div style="height:30px;"></div>
                    <?php endif; ?>
                    <div class="sig-name"
                        style="display:inline-block; border-bottom:1px solid #111; padding-bottom:2px; margin-bottom:4px;">
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
                        <div style="height:30px;"></div>
                    <?php endif; ?>
                    <div class="sig-name"
                        style="display:inline-block; border-bottom:1px solid #111; padding-bottom:2px; margin-bottom:4px;">
                        <?= $radFullNameWithTitle ?>
                    </div>
                    <div class="sig-title">Radiologist</div>
                </div>
            </div>
            <div class="branches">
                GAPAN &bull; PE&Ntilde;ARANDA &bull; GENERAL TINIO &bull; STO DOMINGO &bull; SAN ANTONIO &bull; PANTABANGAN
                &bull; BONGABON
            </div>
            <div class="report-footer">
                <span>CitiLife Diagnostic — <?= $branch ?> Branch</span>
                <span>Case: <?= $caseNum ?> &nbsp;|&nbsp; Generated: <?= date('M d, Y h:i A') ?></span>
            </div>
        </div>
    <?php } ?>

    <?php
    if ($isMultiExam && !empty($parsedData)):
        $examCount = 0;
        foreach ($parsedData as $examName => $data):
            $examCount++;
            // If single_page is requested, stop after the first page
            if (isset($_GET['single_page']) && $_GET['single_page'] === 'true' && $examCount > 1)
                break;
            ?>
            <div class="page report-page">
                <?php renderPageHeader($bAddressRows, $bInfoMatch, $fullName, $patientID, $age, $sex, $caseNum, $branch, $dateStr, $examType); ?>
                <div class="report-info">
                    <div class="exam-block">
                        <h3
                            style="font-family:'Raleway',sans-serif;font-size:11pt;color:#c0392b;margin-bottom:14px;text-transform:uppercase;display:flex;align-items:center;">
                            <span
                                style="color:#c0392b;font-size:16pt;margin-right:8px;line-height:0;margin-top:-2px;">&bull;</span><span
                                style="border-bottom: 1.5px solid #c0392b; padding-bottom: 2px; font-family: 'Times New Roman', Times, serif;"><?= htmlspecialchars($examName) ?></span>
                        </h3>
                        <div class="section" style="margin-left:14px;">
                            <div class="section-title">Radiographic Findings</div>
                            <div class="section-body"><?= nl2br(htmlspecialchars($data['findings'] ?? '—')) ?></div>
                        </div>
                        <div class="section" style="margin-left:14px;">
                            <div class="section-title">Impression</div>
                            <div class="section-body"><?= nl2br(htmlspecialchars($data['impression'] ?? '—')) ?></div>
                        </div>
                    </div>
                </div>
                <?php renderFooterGroup($radtechFullNameWithTitle, $radtechSignature, $radFullNameWithTitle, $radSignature, $branch, $caseNum); ?>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <!-- Single / non-JSON report: one page -->
        <div class="page report-page">
            <?php renderPageHeader($bAddressRows, $bInfoMatch, $fullName, $patientID, $age, $sex, $caseNum, $branch, $dateStr, $examType); ?>

            <div class="report-info">
                <div class="exam-block">
                    <h3
                        style="font-family:'Raleway',sans-serif;font-size:11pt;color:#c0392b;margin-bottom:14px;text-transform:uppercase;display:flex;align-items:center;">
                        <span
                            style="color:#c0392b;font-size:16pt;margin-right:8px;line-height:0;margin-top:-2px;">&bull;</span><span
                            style="border-bottom: 1.5px solid #c0392b; padding-bottom: 2px; font-family: 'Times New Roman', Times, serif;"><?= htmlspecialchars($examType) ?></span>
                    </h3>
                    <!-- Findings -->
                    <div class="section" style="margin-left:14px;">
                        <div class="section-title">Radiographic Findings</div>
                        <div class="section-body"><?= $findingsStr ?></div>
                    </div>
                    <!-- Impression -->
                    <div class="section" style="margin-left:14px;">
                        <div class="section-title">Impression</div>
                        <div class="section-body"><?= $impressionStr ?></div>
                    </div>
                </div>
            </div>

            <?php renderFooterGroup($radtechFullNameWithTitle, $radtechSignature, $radFullNameWithTitle, $radSignature, $branch, $caseNum); ?>
        </div>
    <?php endif; ?>

    <!-- X-Ray Image Page (Only rendered when printing) -->
    <?php if (!$isPreview && !$isSnapshot && !empty($case['image_path'])):
        $savedPaths = [];
        $decoded = json_decode($case['image_path'], true);
        if (is_array($decoded)) {
            $savedPaths = $decoded;
        } else {
            $savedPaths = [$case['image_path']];
        }
        ?>
        <?php foreach ($savedPaths as $idx => $sPath): ?>
            <div class="page report-page image-page">

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
                    <img src="/<?= PROJECT_DIR ?>/<?= htmlspecialchars($sPath) ?>" alt="X-Ray Image"
                        style="max-width: 100%; max-height: 220mm; object-fit: contain;">
                </div>

                <div class="report-footer" style="margin-top: auto;">
                    <span>CitiLife Diagnostic — <?= $branch ?> Branch</span>
                    <span>Case: <?= $caseNum ?> &nbsp;|&nbsp; Generated: <?= date('M d, Y h:i A') ?></span>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Load PDF generation scripts regardless of preview mode -->
    <script src="/<?= PROJECT_DIR ?>/public/assets/vendor/html2canvas/html2canvas.min.js"></script>
    <script src="/<?= PROJECT_DIR ?>/public/assets/vendor/jspdf/jspdf.umd.min.js"></script>
    <script>
        const _IS_MULTI = <?= $isMultiExam ? 'true' : 'false' ?>;
        const _BRANCH = '<?= addslashes($branch) ?>';
        const _CASENUM = '<?= addslashes($caseNum) ?>';
        const _GENDATE = '<?= date('M d, Y h:i A') ?>';

        /**
         * autoSplitPages()
         * Reads all .exam-block heights and checks if they overflow an A4 page.
         * If overflow is detected, the single page is rebuilt as multiple pages,
         * each with the FULL template (header + patient info + signature + footer).
         */
        function autoSplitPages() {
            if (!_IS_MULTI) return;

            const page = document.getElementById('main-report-page');
            if (!page) return;

            const examBlocks = [...page.querySelectorAll('.exam-block')];
            if (examBlocks.length <= 1) return;

            // A4 usable height in CSS pixels (96 dpi):
            // 297mm total − 8mm top − 8mm bottom = 281mm ≈ 1062 px
            const A4_USABLE = Math.round(281 * 96 / 25.4);

            // Measure fixed elements overhead (present on every page)
            const els = {
                header: page.querySelector('.report-header'),
                title: page.querySelector('.title-report'),
                infoBox: page.querySelector('.info-box'),
                examLabel: page.querySelector('.report-info .exam-type'),
                footer: page.querySelector('.footer-group'),
            };

            let overhead = 50; // buffer for gaps, border, misc
            Object.values(els).forEach(el => {
                if (el) overhead += el.offsetHeight + 14;
            });

            const availForExams = A4_USABLE - overhead;

            // Measure each exam block (include bottom separator gap)
            const blockH = examBlocks.map(b => b.offsetHeight + 24);

            // Greedy packing: fill pages until overflow
            const pagePacks = [];
            let pack = [], packH = 0;
            blockH.forEach((h, i) => {
                if (packH + h > availForExams && pack.length > 0) {
                    pagePacks.push([...pack]);
                    pack = [i];
                    packH = h;
                } else {
                    pack.push(i);
                    packH += h;
                }
            });
            if (pack.length) pagePacks.push(pack);

            if (pagePacks.length <= 1) return; // Fits on one page — nothing to do!

            // ── Capture template HTML snippets ──
            const watermarkHTML = page.querySelector('.watermark')?.outerHTML ?? '';
            const reportHeaderHTML = page.querySelector('.report-header').outerHTML;
            const titleReportHTML = page.querySelector('.title-report').outerHTML;
            const infoBoxHTML = page.querySelector('.info-box').outerHTML;
            const examTypeHTML = els.examLabel?.outerHTML ?? '';
            const footerGroupHTML = els.footer?.outerHTML ?? '';
            const blockHTML = examBlocks.map(b => b.outerHTML);

            // Remove original page, remember insertion point
            const parent = page.parentNode;
            const nextSib = page.nextSibling;
            page.remove();

            const total = pagePacks.length;

            pagePacks.forEach((indices, pi) => {
                const newPage = document.createElement('div');
                newPage.className = 'page report-page';

                const examsHTML = indices.map(i => blockHTML[i]).join('');

                newPage.innerHTML = `
                        ${watermarkHTML}
                        ${reportHeaderHTML}
                        ${titleReportHTML}
                        ${infoBoxHTML}
                        <div class="report-info">
                            ${examTypeHTML}
                            ${examsHTML}
                        </div>
                        ${footerGroupHTML}
                    `;

                parent.insertBefore(newPage, nextSib);
            });
        }

        // ── Load: split first ──
        window.addEventListener('load', () => {
            autoSplitPages();
            <?php if ($isDownload): ?>
                setTimeout(() => {
                    downloadPDF();
                }, 500); // Give DOM a moment to settle
            <?php endif; ?>
        });

        // ── Download PDF ──
        // Renders each .page element from the live DOM one-by-one using
        // html2canvas (with onclone to strip margins/shadows) then stitches
        // them into a single A4 PDF via jsPDF — no print dialog.
        async function downloadPDF() {
            const btnBar = document.querySelector('.print-bar');
            if (btnBar) btnBar.style.display = 'none';

            const pages = [...document.querySelectorAll('.page')];
            if (pages.length === 0) {
                if (btnBar) btnBar.style.display = 'flex';
                return;
            }

            try {
                const { jsPDF } = window.jspdf;
                const pdf = new jsPDF({ unit: 'mm', format: 'a4', orientation: 'portrait' });

                for (let i = 0; i < pages.length; i++) {
                    const canvas = await html2canvas(pages[i], {
                        scale: 2,
                        useCORS: true,
                        logging: false,
                        onclone: (clonedDoc) => {
                            // Strip screen-only decorations from the cloned render
                            // without touching the visible page
                            clonedDoc.querySelectorAll('.page').forEach(el => {
                                el.style.margin = '0';
                                el.style.boxShadow = 'none';
                                el.style.border = 'none';
                                el.style.background = '#fff';
                            });
                        }
                    });

                    const imgData = canvas.toDataURL('image/jpeg', 0.97);
                    if (i > 0) pdf.addPage();
                    pdf.addImage(imgData, 'JPEG', 0, 0, 210, 297);
                }

                pdf.save('Radiology_Report_<?= htmlspecialchars($caseNum) ?>.pdf');

                <?php if ($isDownload): ?>
                    const spinner = document.getElementById('loadingSpinner');
                    const check = document.getElementById('successCheck');
                    const title = document.getElementById('loadingTitle');
                    const desc = document.getElementById('loadingDesc');

                    if (spinner) spinner.style.display = 'none';
                    if (check) check.style.display = 'flex';
                    if (title) title.textContent = 'Download Complete!';
                    if (desc) desc.textContent = 'Returning to patient records...';

                    setTimeout(() => {
                        if (window.history.length > 1) {
                            window.history.back();
                        } else {
                            window.close();
                        }
                        // Fallback if history.back or close fails
                        setTimeout(() => {
                            window.location.href = '<?= PROJECT_DIR ?>/index.php?page=xray-patient-records';
                        }, 500);
                    }, 1500); // 1.5 second delay so they see the checkmark
                <?php endif; ?>
            } catch (err) {
                console.error('PDF generation failed:', err);
                alert('PDF generation failed. Falling back to print dialog — choose "Save as PDF" as destination.');

                <?php if ($isDownload): ?>
                    if (window.history.length > 1) {
                        window.history.back();
                    } else {
                        window.close();
                        setTimeout(() => window.location.href = '<?= PROJECT_DIR ?>/index.php?page=xray-patient-records', 500);
                    }
                <?php else: ?>
                    window.print();
                <?php endif; ?>
            }

            if (btnBar) btnBar.style.display = 'flex';
        }
    </script>
</body>

</html>