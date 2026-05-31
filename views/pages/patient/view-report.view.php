<?php
/**
 * view-report.php
 * Patient-side secure view-only report.
 * Matches the layout of radtech/print-report.php exactly, wrapped in a Messenger-style viewer.
 * Supports dynamic A4 pagination for long findings.
 */
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../models/CaseModel.php';
require_once __DIR__ . '/../../../models/UserModel.php';
require_once __DIR__ . '/../../../models/BranchModel.php';

$caseModel = new \CaseModel($pdo);
$userModel = new \UserModel($pdo);
$branchModel = new \BranchModel($pdo);

$patientId = $_SESSION['patient_id'] ?? 0;

function showSecureError($message) {
    echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Restricted</title>
    <style>
        body { font-family: "Inter", "Segoe UI", sans-serif; background: #000; color: #f9fafb; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
        .error-card { background: #111827; border: 1px solid #374151; border-radius: 12px; padding: 32px; width: 90%; max-width: 380px; text-align: center; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.5); }
        .error-icon { width: 56px; height: 56px; background: rgba(220,38,38,0.15); color: #ef4444; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 24px; margin-bottom: 20px; }
        h1 { margin: 0 0 10px; font-size: 20px; font-weight: 600; color: #fff; }
        p { margin: 0 0 24px; color: #9ca3af; font-size: 14px; line-height: 1.5; }
        .btn { background: #dc2626; color: #fff; padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 14px; display: inline-block; transition: background 0.2s; border: none; cursor: pointer; width: calc(100% - 48px); }
        .btn:hover { background: #b91c1c; }
    </style>
</head>
<body>
    <div class="error-card">
        <div class="error-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" fill="currentColor" viewBox="0 0 16 16">
              <path d="M8 1a2 2 0 0 1 2 2v4H6V3a2 2 0 0 1 2-2zm3 6V3a3 3 0 0 0-6 0v4a2 2 0 0 0-2 2v5a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2z"/>
            </svg>
        </div>
        <h1>Security Check Failed</h1>
        <p>' . htmlspecialchars($message) . '</p>
        <button class="btn" onclick="javascript:history.back()">Return Security</button>
    </div>
</body>
</html>';
    exit;
}

// --- URL Parameter Hiding Logic (PRG Pattern) ---
// If the URL contains the ref token, save it to session and redirect immediately.
// This completely cleans the URL bar so panelists/users don't see any parameters!
if (isset($_GET['ref'])) {
    $_SESSION['active_report_ref'] = $_GET['ref'];
    header("Location: /" . PROJECT_DIR . "/view-report");
    exit;
}

// Retrieve the token from session instead of URL
$refToken = $_SESSION['active_report_ref'] ?? '';
$id = 0;

if (!empty($refToken)) {
    $decoded = base64_decode($refToken);
    if (strpos($decoded, 'CitiLife_Case_') === 0) {
        $id = (int) str_replace('CitiLife_Case_', '', $decoded);
    }
} else {
    // Fallback if someone uses raw ?id= somehow (e.g. older versions)
    $id = (int) ($_GET['id'] ?? 0);
    if ($id > 0) {
        $_SESSION['active_report_ref'] = base64_encode('CitiLife_Case_' . $id);
        header("Location: /" . PROJECT_DIR . "/view-report");
        exit;
    }
}

if (!$id) {
    showSecureError('Invalid access token or the report link is corrupted. Please go back to your records and try again.');
}

// Fetch case details via Model
$case = $caseModel->getCaseById($id);

// Access control: must belong to this patient
if (!$case || (int) $case['patient_id'] !== (int) $patientId) {
    showSecureError('Report not found or access denied. This incident has been logged for security purposes.');
}

// Fetch Radiologist Name via Model
$radName = $userModel->getRadTechName($case['radiologist_id']) ?: 'Jiar Maglaque M.D. FPCR';

$fullName = htmlspecialchars(strtoupper($case['first_name'] . ' ' . $case['last_name']));
$caseNum = htmlspecialchars($case['case_number']);
$patientID = htmlspecialchars($case['patient_number'] ?? $case['patient_id']);
$age = htmlspecialchars($case['age']);
$sex = htmlspecialchars(ucfirst($case['sex'] ?? '—'));
$branch = htmlspecialchars($case['branch_name']);
$dateExam = $case['date_completed'] ?? $case['created_at'];
$dateStr = date('F d, Y', strtotime($dateExam));
$radtechName = htmlspecialchars('Fransisco Dela Cruz, RXT, RRT');

// Get Branch Metadata via Model (Address, Contacts)
$bInfoMatch = $branchModel->getBranchMetadata($branch);
$bAddressRows = explode("\n", $bInfoMatch['address']);

$findingsRaw = trim($case['findings'] ?? '');
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
        $impressionRaw = $case['impression'] ?? '';
        if (str_starts_with($impressionRaw, $prefix)) {
            $impressionRaw = substr($impressionRaw, strlen($prefix));
        }
        $findingsStr = nl2br(htmlspecialchars($findingsRaw ?: '—'));
        $impressionStr = nl2br(htmlspecialchars($impressionRaw ?: '—'));
    } else {
        $findingsStr = nl2br(htmlspecialchars($findingsRaw ?: '—'));
        $impressionStr = nl2br(htmlspecialchars($case['impression'] ?? '—'));
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>View Report — <?= $caseNum ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Raleway:ital,wght@0,100..900;1,100..900&family=Inter:wght@400;600;700&display=swap');

        :root {
            --viewer-bg: #000;
            --accent-red: #c0392b;
            --text-gray: #b0b3b8;
        }

        body {
            user-select: none;
            background: var(--viewer-bg);
            margin: 0;
            padding: 0;
            overflow: hidden;
            font-family: 'Inter', sans-serif;
            color: #fff;
        }

        .topbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 60px;
            background: linear-gradient(to bottom, rgba(0, 0, 0, 0.8) 0%, transparent 100%);
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 12px;
            pointer-events: none;
        }

        .topbar>* {
            pointer-events: auto;
        }

        .topbar-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .btn-close {
            color: #fff;
            font-size: 26px;
            cursor: pointer;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            transition: background 0.2s;
            background: rgba(0, 0, 0, 0.3);
            -webkit-backdrop-filter: blur(4px);
            backdrop-filter: blur(4px);
        }

        .btn-close:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .header-brand {
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .header-brand h1 {
            font-size: 14px;
            font-weight: 600;
            margin: 0;
            color: #fff;
            white-space: nowrap;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.8);
        }

        .header-brand span {
            font-size: 11px;
            color: #ccc;
            display: flex;
            align-items: center;
            gap: 6px;
            white-space: nowrap;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.8);
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .zoom-controls {
            display: flex;
            align-items: center;
            gap: 4px;
            background: rgba(0, 0, 0, 0.3);
            padding: 4px;
            border-radius: 20px;
            color: #fff;
            font-size: 14px;
            -webkit-backdrop-filter: blur(4px);
            backdrop-filter: blur(4px);
        }

        .zoom-btn {
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            transition: background 0.2s;
            font-size: 18px;
        }

        .zoom-btn:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .zoom-val {
            font-size: 13px;
            font-weight: 500;
            min-width: 40px;
            text-align: center;
        }

        .bottom-nav {
            position: fixed;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 1001;
            display: flex;
            align-items: center;
            gap: 15px;
            background: rgba(30, 30, 30, 0.85);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 8px 16px;
            border-radius: 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.5);
            -webkit-backdrop-filter: blur(8px);
            backdrop-filter: blur(8px);
        }

        .nav-btn {
            background: transparent;
            border: none;
            color: #fff;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 20px;
            transition: background 0.2s;
            outline: none;
        }

        .nav-btn:hover:not(:disabled) {
            background: rgba(255, 255, 255, 0.15);
        }

        .nav-btn:disabled {
            opacity: 0.3;
            cursor: not-allowed;
        }

        .nav-counter {
            color: #ddd;
            font-size: 13px;
            font-weight: 500;
            min-width: 40px;
            text-align: center;
        }

        .viewer-main {
            position: absolute;
            top: 60px;
            left: 0;
            right: 0;
            bottom: 0;
            overflow: hidden;
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: grab;
            touch-action: none;
        }

        .viewer-main:active {
            cursor: grabbing;
        }

        #report-scroller {
            transform-origin: center center;
            transition: transform 0.1s ease-out;
        }

        .page {
            width: 210mm;
            min-height: 297mm;
            background: #fff;
            padding: 8mm 12mm;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
            position: relative;
            color: #1a1a1a;
            font-family: 'Times New Roman', Times, serif;
            font-size: 11.5pt;
            display: flex;
            flex-direction: column;
            margin-bottom: 30px;
        }

        .report-header,
        .title-report,
        .info-box,
        .report-info,
        .footer-group {
            position: relative;
            z-index: 1;
        }

        .report-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1.5px solid #111;
            padding-bottom: 5px;
        }

        .header-left {
            display: flex;
            align-items: center;
        }

        .header-logo {
            width: 100px;
            height: 100px;
            margin-right: 15px;
            object-fit: contain;
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
            line-height: 1;
        }

        .header-right .text-col {
            display: flex;
            flex-direction: column;
            line-height: 1.35;
        }

        .title-report {
            border: 1px solid #000;
            text-align: center;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin: 10px 0;
        }

        .title-report h1 {
            color: #c0392b;
            font-size: 14pt;
            margin: 2px 0;
        }

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

        .report-info {
            border: 1.5px solid #111;
            padding: 15px 20px;
            margin-bottom: 20px;
            flex-shrink: 0;
            min-height: 400px;
        }

        .exam-block {
            padding-bottom: 15px;
            margin-bottom: 15px;
            border-bottom: 1px dashed #ccc;
        }

        .exam-block:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .exam-block h3 {
            font-family: 'Raleway', sans-serif;
            font-size: 11.5pt;
            color: #c0392b;
            margin-bottom: 15px;
            text-transform: uppercase;
            font-weight: 800;
            display: flex;
            align-items: center;
        }

        .section {
            margin-bottom: 18px;
        }

        .section-title {
            font-family: Arial, sans-serif;
            font-size: 8.5pt;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #000;
            border-bottom: 1px solid #ddd;
            padding-bottom: 4px;
            margin-bottom: 8px;
        }

        .section-body {
            font-size: 10.5pt;
            line-height: 1.6;
            color: #222;
            word-break: break-word;
            overflow-wrap: break-word;
        }

        .footer-group {
            margin-top: auto;
            padding-top: 15px;
            width: 100%;
        }

        .signature-block {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
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
            font-size: 10pt;
            font-weight: bold;
            text-transform: uppercase;
            color: #111;
        }

        .sig-title {
            font-family: Arial, sans-serif;
            font-weight: bold;
            font-size: 9pt;
            color: #666;
            margin-top: 2px;
            text-transform: uppercase;
        }

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
            margin-top: 10px;
            text-transform: uppercase;
        }

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

        .image-viewport {
            border-radius: 4px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 180mm;
            margin: 10px 0;
        }

        .image-viewport img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        .viewer-main img {
            -webkit-user-drag: none;
            -khtml-user-drag: none;
            -moz-user-drag: none;
            -o-user-drag: none;
        }

        .secure-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 2000;
            pointer-events: none;
        }

        /* Custom Alert Modal styles */
        .custom-alert-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.75);
            backdrop-filter: blur(4px);
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease;
        }

        .custom-alert-overlay.show {
            opacity: 1;
            pointer-events: auto;
        }

        .custom-alert-box {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 24px;
            width: 90%;
            max-width: 400px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            text-align: center;
            font-family: 'Inter', sans-serif;
            transform: translateY(20px) scale(0.95);
            transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .custom-alert-overlay.show .custom-alert-box {
            transform: translateY(0) scale(1);
        }

        .custom-alert-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: #fee2e2;
            color: #ef4444;
            font-size: 24px;
            margin-bottom: 16px;
        }

        .custom-alert-title {
            color: #111827;
            font-size: 18px;
            font-weight: 600;
            margin: 0 0 8px 0;
        }

        .custom-alert-text {
            color: #4b5563;
            font-size: 14px;
            margin: 0 0 24px 0;
            line-height: 1.5;
        }

        /* Dark Mode Overrides */
        body.theme-dark .custom-alert-box {
            background: #111827;
            border-color: #374151;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.5), 0 10px 10px -5px rgba(0, 0, 0, 0.2);
        }
        body.theme-dark .custom-alert-icon {
            background: rgba(220, 38, 38, 0.15);
        }
        body.theme-dark .custom-alert-title {
            color: #f9fafb;
        }
        body.theme-dark .custom-alert-text {
            color: #9ca3af;
        }

        .custom-alert-btn {
            background: #dc2626;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 10px 24px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
            width: 100%;
            outline: none;
        }

        .custom-alert-btn:hover {
            background: #b91c1c;
        }
    </style>
</head>

<body oncontextmenu="return false;">

    <script>
        // Check for globally set system theme logic
        if (localStorage.getItem('citilife_theme') === 'dark') {
            document.body.classList.add('theme-dark');
        }
    </script>

    <div class="secure-overlay"></div>

    <!-- Custom Screenshot Alert Modal -->
    <div class="custom-alert-overlay" id="screenshot-alert">
        <div class="custom-alert-box">
            <div class="custom-alert-icon">
                <i class="bi bi-shield-lock-fill"></i>
            </div>
            <h3 class="custom-alert-title">Screenshots Restricted</h3>
            <p class="custom-alert-text">For your privacy and data security, taking screenshots or downloading the medical report is restricted in this view.</p>
            <button class="custom-alert-btn" onclick="document.getElementById('screenshot-alert').classList.remove('show')">I Understand</button>
        </div>
    </div>

    <header class="topbar">
        <div class="topbar-left">
            <a href="javascript:history.back()" class="btn-close" title="Close"><i class="bi bi-x"></i></a>
            <div class="header-brand">
                <h1><?= $caseNum ?></h1>
                <span><i class="bi bi-shield-lock-fill" style="font-size:10px; color:#c0392b;"></i> View Only</span>
            </div>
        </div>

        <div class="topbar-right">
            <div class="zoom-controls">
                <div class="zoom-btn" onclick="adjustZoom(-0.1)"><i class="bi bi-dash"></i></div>
                <div class="zoom-val" id="zoom-val">100%</div>
                <div class="zoom-btn" onclick="adjustZoom(0.1)"><i class="bi bi-plus"></i></div>
            </div>
        </div>
    </header>

    <div class="bottom-nav" id="bottom-nav" style="display:none;">
        <button class="nav-btn prev-btn" onclick="prevPage()"><i class="bi bi-chevron-left"
                style="font-size:18px;"></i></button>
        <span class="nav-counter" id="page-indicator">1 / 1</span>
        <button class="nav-btn next-btn" onclick="nextPage()"><i class="bi bi-chevron-right"
                style="font-size:18px;"></i></button>
    </div>

    <main class="viewer-main">
        <div id="report-scroller">
            <div id="document-root">
                <!-- Main Report Page -->
                <div class="page" id="main-report-page">

                    <div class="report-header">
                        <div class="header-left">
                            <img src="/<?= PROJECT_DIR ?>/public/assets/img/logo/citilife-logo.png" class="header-logo">
                            <div class="header-text">
                                <h1>CITILIFE</h1>
                                <p>DIAGNOSTIC CENTER</p>
                            </div>
                        </div>
                        <div class="header-right">
                            <div class="icon-row">
                                <i class="bi bi-radioactive"
                                    style="color:#8b0000; font-size:13px; margin-right:7px; margin-top:2px;"></i>
                                <div class="text-col">
                                    <?php foreach ($bAddressRows as $row): ?>
                                        <span
                                            style="<?= strpos($row, '(') !== false ? 'font-size:7.5pt;color:#777;' : 'font-weight:bold;color:#8b0000;' ?>"><?= htmlspecialchars($row) ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <div class="icon-row">
                                <i class="bi bi-telephone-fill"
                                    style="color:#666; font-size:12px; margin-right:7px; margin-top:2px;"></i>
                                <div class="text-col" style="font-weight:bold;color:#333;">
                                    <span><?= htmlspecialchars($bInfoMatch['contact1']) ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="title-report">
                        <h1>Roentgenological Report</h1>
                    </div>

                    <div class="info-box">
                        <div class="info-grid">
                            <div class="info-item"><label>Patient Name</label><span><?= $fullName ?></span></div>
                            <div class="info-item"><label>Patient No.</label><span><?= $patientID ?></span></div>
                            <div class="info-item"><label>Age / Sex</label><span><?= $age ?> / <?= $sex ?></span></div>
                            <div class="info-item"><label>Case No.</label><span><?= $caseNum ?></span></div>
                            <div class="info-item"><label>Branch</label><span><?= $branch ?></span></div>
                            <div class="info-item"><label>Date of Examination</label><span><?= $dateStr ?></span></div>
                        </div>
                    </div>

                    <div class="report-info">
                        <?php if ($isMultiExam): ?>
                            <?php foreach ($parsedData as $examName => $data): ?>
                                <div class="exam-block">
                                    <h3><span
                                            style="color:#c0392b;font-size:16pt;margin-right:8px;line-height:0;margin-top:-2px;">&bull;</span><?= htmlspecialchars($examName) ?>
                                    </h3>
                                    <div class="section" style="margin-left:14px;">
                                        <div class="section-title">Radiographic Findings</div>
                                        <div class="section-body"><?= nl2br(htmlspecialchars($data['findings'] ?? '—')) ?></div>
                                    </div>
                                    <div class="section" style="margin-left:14px;">
                                        <div class="section-title">Impression</div>
                                        <div class="section-body"><?= nl2br(htmlspecialchars($data['impression'] ?? '—')) ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="exam-block">
                                <h3><span
                                        style="color:#c0392b;font-size:16pt;margin-right:8px;line-height:0;margin-top:-2px;">&bull;</span><?= htmlspecialchars($case['exam_type'] ?? 'Examination') ?>
                                </h3>
                                <div class="section" style="margin-left:14px;">
                                    <div class="section-title">Radiographic Findings</div>
                                    <div class="section-body"><?= $findingsStr ?></div>
                                </div>
                                <div class="section" style="margin-left:14px;">
                                    <div class="section-title">Impression</div>
                                    <div class="section-body"><?= $impressionStr ?></div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="footer-group">
                        <div class="signature-block">
                            <div class="sig-inner">
                                <div style="height:40px;"></div>
                                <div class="sig-name"><?= $radtechName ?></div>
                                <div class="sig-line"></div>
                                <div class="sig-title">Radiologic Technologist</div>
                            </div>
                            <div class="sig-inner">
                                <div style="height:40px;"></div>
                                <div class="sig-name"><?= $radName ?></div>
                                <div class="sig-line"></div>
                                <div class="sig-title">Radiologist</div>
                            </div>
                        </div>
                        <div class="branches">
                            GAPAN &bull; PE&Ntilde;ARANDA &bull; GENERAL TINIO &bull; STO DOMINGO &bull; SAN ANTONIO
                            &bull; PANTABANGAN &bull; BONGABON
                        </div>
                        <div class="report-footer">
                            <span>CitiLife Diagnostic — <?= $branch ?> Branch</span>
                            <span>Case: <?= $caseNum ?> &nbsp;|&nbsp; Generated: <?= date('M d, Y h:i A') ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Imaging Pages -->
            <?php
            $images = [];
            if (!empty($case['image_path'])) {
                $decoded = json_decode($case['image_path'], true);
                $images = is_array($decoded) ? $decoded : [$case['image_path']];
            }
            ?>
            <?php foreach ($images as $idx => $imgSrc): ?>
                <div class="page">

                    <div style="width: 100%; text-align: center; margin-bottom: 25px; font-family: 'Raleway', sans-serif;">
                        <h2
                            style="font-size: 16pt; font-weight: bold; color: #111; letter-spacing: 1px; text-transform: uppercase;">
                            X-Ray Plate Image <?= count($images) > 1 ? '(' . ($idx + 1) . ')' : '' ?>
                        </h2>
                        <p style="font-family: Arial, sans-serif; font-size: 10pt; color: #555; margin-top: 6px;">
                            Case No: <?= $caseNum ?> &nbsp;|&nbsp; Patient Name: <?= $fullName ?>
                        </p>
                    </div>

                    <div class="image-viewport">
                        <img src="/<?= PROJECT_DIR ?>/<?= htmlspecialchars($imgSrc) ?>"
                            style="max-height: 220mm; width: auto; object-fit: contain;">
                    </div>

                    <div class="report-footer" style="margin-top: auto; padding-top: 15px; border-top: 1px solid #eee;">
                        <span>CitiLife Diagnostic — <?= $branch ?> Branch</span>
                        <span>Case: <?= $caseNum ?> &nbsp;|&nbsp; Generated: <?= date('M d, Y h:i A') ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>

    <script>
        // Immediately clean the URL bar to hide all parameters from users
        if (window.history && window.history.replaceState) {
            window.history.replaceState(null, null, window.location.pathname);
        }

        document.getElementById('bottom-nav').style.display = 'flex';
        const scroller = document.getElementById('report-scroller');
        const mainArea = document.querySelector('.viewer-main');
        let zoom = 1.0;
        let baseScale = 1.0;
        let currentPage = 1;
        let totalPages = 1;

        let isDragging = false;
        let startX, startY;
        let panX = 0, panY = 0;

        mainArea.addEventListener('mousedown', (e) => {
            isDragging = true;
            startX = e.clientX - panX;
            startY = e.clientY - panY;
        });
        window.addEventListener('mouseup', () => { isDragging = false; });
        window.addEventListener('mousemove', (e) => {
            if (!isDragging) return;
            e.preventDefault();
            panX = e.clientX - startX;
            panY = e.clientY - startY;
            applyZoom();
        });

        mainArea.addEventListener('touchstart', (e) => {
            if (e.touches.length === 1) {
                isDragging = true;
                startX = e.touches[0].clientX - panX;
                startY = e.touches[0].clientY - panY;
            }
        }, { passive: false });
        window.addEventListener('touchend', () => { isDragging = false; });
        window.addEventListener('touchcancel', () => { isDragging = false; });
        mainArea.addEventListener('touchmove', (e) => {
            if (!isDragging || e.touches.length !== 1) return;
            e.preventDefault();
            panX = e.touches[0].clientX - startX;
            panY = e.touches[0].clientY - startY;
            applyZoom();
        }, { passive: false });

        function resetPan() { panX = 0; panY = 0; }

        function getBaseScale() {
            const winW = window.innerWidth;
            const winH = window.innerHeight - 60;
            const pageW = 794;
            const pageH = 1123;

            const scaleW = (winW - 30) / pageW;
            const scaleH = (winH - 30) / pageH;

            let scale = Math.min(scaleW, scaleH);
            if (scale > 1.0) scale = 1.0;
            return scale;
        }

        function initCarousel() {
            const pages = document.querySelectorAll('.page');
            totalPages = pages.length;
            showPage(1);
        }

        function showPage(num) {
            if (num < 1 || num > totalPages) return;
            currentPage = num;
            const pages = document.querySelectorAll('.page');
            pages.forEach((p, idx) => {
                if (idx === currentPage - 1) {
                    p.style.display = 'flex';
                    p.classList.add('active');
                } else {
                    p.style.display = 'none';
                    p.classList.remove('active');
                }
            });

            const indicator = document.getElementById('page-indicator');
            if (indicator) indicator.textContent = `${currentPage} / ${totalPages}`;

            const pBtn = document.querySelector('.prev-btn');
            const nBtn = document.querySelector('.next-btn');
            if (pBtn) pBtn.disabled = (currentPage === 1);
            if (nBtn) nBtn.disabled = (currentPage === totalPages);

            resetPan();
            baseScale = getBaseScale();
            zoom = baseScale;
            applyZoom();
        }

        function prevPage() { showPage(currentPage - 1); }
        function nextPage() { showPage(currentPage + 1); }

        function adjustZoom(delta) {
            zoom = Math.min(Math.max(0.1, zoom + delta), 3.0);
            applyZoom();
        }

        function applyZoom() {
            const scrollerH = scroller.offsetHeight;
            const visualW = 794 * zoom;
            const visualH = scrollerH * zoom;
            const winW = window.innerWidth;
            const winH = window.innerHeight - 60;

            let limitX = 0;
            if (visualW > winW) limitX = (visualW - winW) / 2 + 20;
            if (panX > limitX) panX = limitX;
            if (panX < -limitX) panX = -limitX;

            let limitY = 0;
            if (visualH > winH) limitY = (visualH - winH) / 2 + 20;
            if (panY > limitY) panY = limitY;
            if (panY < -limitY) panY = -limitY;

            scroller.style.transform = `translate(${panX}px, ${panY}px) scale(${zoom})`;
            let percent = Math.round((zoom / baseScale) * 100);
            const zv = document.getElementById('zoom-val');
            if (zv) zv.textContent = `${percent}%`;
        }

        window.addEventListener('resize', () => {
            let newBase = getBaseScale();
            if (newBase !== baseScale) {
                let ratio = zoom / baseScale;
                baseScale = newBase;
                zoom = baseScale * ratio;
                applyZoom();
            }
        });

        function updateTime() {
            const el = document.getElementById('live-time');
            if (el) el.innerHTML = new window.Date().toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' });
        }
        setInterval(updateTime, 1000); updateTime();

        function showScreenshotAlert() {
            document.getElementById('screenshot-alert').classList.add('show');
        }

        document.addEventListener('keydown', function (e) {
            if ((e.ctrlKey && (e.key === 'p' || e.key === 's' || e.key === 'u' || e.shiftKey && e.key === 'i')) || e.key === 'F12' || e.key === 'PrintScreen') {
                e.preventDefault(); showScreenshotAlert(); return false;
            }
        });
        window.addEventListener('keyup', (e) => { 
            if (e.key === 'PrintScreen') { 
                navigator.clipboard.writeText(''); 
                showScreenshotAlert(); 
            } 
        });

        function autoSplitPages() {
            const page = document.getElementById('main-report-page');
            if (!page) return;
            const examBlocks = [...page.querySelectorAll('.exam-block')];
            if (examBlocks.length <= 1 && page.offsetHeight <= 1123) return;

            const A4_MAX = 1062;
            const els = {
                header: page.querySelector('.report-header'),
                title: page.querySelector('.title-report'),
                infoBox: page.querySelector('.info-box'),
                footer: page.querySelector('.footer-group'),
            };

            let overhead = 60;
            Object.values(els).forEach(el => { if (el) overhead += el.offsetHeight + 15; });
            const availForExams = A4_MAX - overhead;

            const pagePacks = [];
            let pack = [], packH = 0;
            const blockHTML = examBlocks.map(b => b.outerHTML);
            const blockHeights = examBlocks.map(b => b.offsetHeight + 15);

            blockHeights.forEach((h, i) => {
                if (packH + h > availForExams && pack.length > 0) {
                    pagePacks.push([...pack]); pack = [i]; packH = h;
                } else {
                    pack.push(i); packH += h;
                }
            });
            if (pack.length) pagePacks.push(pack);
            if (pagePacks.length <= 1) return;

            const headerHTML = els.header?.outerHTML ?? '';
            const titleHTML = els.title?.outerHTML ?? '';
            const infoHTML = els.infoBox?.outerHTML ?? '';
            const footerHTML = els.footer?.outerHTML ?? '';

            const root = document.getElementById('document-root');
            page.remove();

            pagePacks.forEach((indices, idx) => {
                const newPage = document.createElement('div');
                newPage.className = 'page';
                const examsHTML = indices.map(i => blockHTML[i]).join('');
                newPage.innerHTML = `${headerHTML}${titleHTML}${infoHTML}<div class="report-info" style="min-height:${idx === pagePacks.length - 1 ? '400px' : 'auto'}">${examsHTML}</div>${footerHTML}`;
                root.appendChild(newPage);
            });
        }

        document.addEventListener('DOMContentLoaded', () => {
            // setTimeout ensures browser painted fully so heights are accurate
            setTimeout(() => {
                autoSplitPages();
                initCarousel();
            }, 100);
        });
    </script>
</body>

</html>