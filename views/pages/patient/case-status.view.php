<?php
require_once __DIR__ . '/../../../config/database.php';

$caseModel = new \CaseModel($pdo);
$patientModel = new \PatientModel($pdo);

$userId = $_SESSION['user_id'] ?? 0;
$stmtU = $pdo->prepare("SELECT status FROM users WHERE id = ?");
$stmtU->execute([$userId]);
$userAccountStatus = $stmtU->fetchColumn() ?: 'Pending';

$caseId = isset($_GET['case_id']) ? (int) $_GET['case_id'] : 0;
$reqId = isset($_GET['request_id']) ? (int) $_GET['request_id'] : 0;

$steps = [
    1 => 'Registered',
    2 => 'Payment',
    3 => 'Approved by RadTech',
    4 => 'X-ray Taken',
    5 => 'Under Reading',
    6 => 'Ready for Release',
    7 => 'Released',
];

// 1. Fetch Patient Info
$patientId = $_SESSION['patient_id'] ?? null;
if (!$patientId) {
    $patientRow = $patientModel->getPatientByUserId($userId);
    if ($patientRow) {
        $patientId = $patientRow['id'];
        $_SESSION['patient_id'] = $patientId;
    }
}

$caseRow = null;

// 2. Fetch specific case or request
if ($patientId) {
    if ($caseId) {
        $caseRow = $caseModel->getCaseById($caseId);
        if ($caseRow && $caseRow['patient_id'] != $patientId) {
            $caseRow = null; // Security check
        }
    } elseif ($reqId) {
        $stmtReq = $pdo->prepare("SELECT r.id, r.request_number AS case_number, r.exam_type, r.created_at, r.status, r.amount_due, r.original_price, r.philhealth_discount,
                                         b.name AS branch_name, b.contact_number_1 AS branch_contact, b.contact_number_2 AS branch_contact_2, b.contact_number_3 AS branch_contact_3, b.gcash_qr_path,
                                         r.patient_id, 'Pending' as approval_status, 'Request' as record_type
                                  FROM requests r 
                                  LEFT JOIN branches b ON r.branch_id = b.id 
                                  WHERE r.id = ?");
        $stmtReq->execute([$reqId]);
        $caseRow = $stmtReq->fetch(PDO::FETCH_ASSOC);
        if ($caseRow && $caseRow['patient_id'] != $patientId) {
            $caseRow = null; // Security check
        }
        if ($caseRow && $caseRow['status'] === 'Rejected') {
            $caseRow['approval_status'] = 'Rejected';
        }
    }
}

$isRejectedGlobal = ($userAccountStatus === 'Rejected');

$statusColors = [
    'Pending' => ['bg' => '#FFF7ED', 'border' => '#FED7AA', 'text' => '#C2410C', 'label' => 'Pending Review'],
    'Pending Payment' => ['bg' => '#FFFBEB', 'border' => '#FDE68A', 'text' => '#D97706', 'label' => 'Pending Payment'],
    'Payment Verifying' => ['bg' => '#EFF6FF', 'border' => '#BFDBFE', 'text' => '#1D4ED8', 'label' => 'Payment Verifying'],
    'Payment Verified' => ['bg' => '#ECFDF5', 'border' => '#A7F3D0', 'text' => '#059669', 'label' => 'Payment Verified'],
    'Approved' => ['bg' => '#F0FDF4', 'border' => '#BBF7D0', 'text' => '#15803D', 'label' => 'Approved – For Examination'],
    'X-ray Taken' => ['bg' => '#EFF6FF', 'border' => '#BFDBFE', 'text' => '#1D4ED8', 'label' => 'X-ray Taken'],
    'Under Reading' => ['bg' => '#EFF6FF', 'border' => '#DBEAFE', 'text' => '#1D4ED8', 'label' => 'Under Reading by Radiologist'],
    'Report Ready' => ['bg' => '#EEF2FF', 'border' => '#C7D2FE', 'text' => '#4338CA', 'label' => 'Report Ready'],
    'Released' => ['bg' => '#F0FDF4', 'border' => '#BBF7D0', 'text' => '#15803D', 'label' => 'Released'],
    'Completed' => ['bg' => '#F0FDF4', 'border' => '#BBF7D0', 'text' => '#15803D', 'label' => 'Completed'],
    'Rejected' => ['bg' => '#FEF2F2', 'border' => '#FECACA', 'text' => '#991B1B', 'label' => 'Request Rejected'],
    'Cancelled' => ['bg' => '#FEF2F2', 'border' => '#FECACA', 'text' => '#991B1B', 'label' => 'Request Cancelled'],
];

$statusDescriptions = [
    'Pending' => 'Your X-ray request has been received and is pending approval from the RadTech team.',
    'Pending Payment' => 'The RadTech has assigned your examination type. Please proceed to payment.',
    'Payment Verifying' => 'Your payment proof has been submitted. Please wait for the Branch Admin to verify your payment.',
    'Payment Verified' => 'Your payment has been verified. Awaiting examination schedule.',
    'Approved' => 'Your request has been approved. Please proceed to the X-ray room for the examination.',
    'X-ray Taken' => 'Your X-ray images have been captured and are being prepared for expert reading.',
    'Under Reading' => 'Your X-ray images have been captured and sent to the Radiologist for interpretation. Once the reading is complete, it will be marked as Ready for Release.',
    'Report Ready' => 'Your X-ray report is ready. Please visit the branch to collect your results.',
    'Released' => 'Your X-ray report has been released. You can now view your report result below.',
    'Completed' => 'Your X-ray examination has been completed. You can view your report result below.',
    'Rejected' => 'Your request has been rejected. Please contact the clinic for more details or submit a new request.',
    'Cancelled' => 'You have cancelled this request.',
];
?>

<style>
    /* Dark mode transparency for status summary box */
    body.theme-dark .status-summary-box {
        background: transparent !important;
        border-color: rgba(255, 255, 255, 0.15) !important;
    }
    body.theme-dark .status-summary-box p {
        color: #e2e8f0 !important;
    }
    body.theme-dark .status-summary-box strong {
        color: #fff !important;
    }
</style>

<div id="case-status-container" class="space-y-4 sm:space-y-5 pb-8 max-w-3xl mx-auto">

    <!-- Page Header -->
    <div class="flex items-center gap-4">
        <a href="javascript:history.back()" title="Back to Records"
            class="flex w-10 h-10 items-center justify-center rounded-xl bg-white border border-gray-200 shadow-sm text-gray-600 hover:bg-gray-50 hover:text-gray-900 transition-colors shrink-0">
            <i data-lucide="chevron-left" class="w-5 h-5"></i>
        </a>
        <div>
            <h1 class="text-xl sm:text-2xl font-semibold text-gray-900 tracking-tight">X-ray Status</h1>
            <p class="text-xs sm:text-sm text-gray-500 mt-1">Track your latest examination in real time.</p>
        </div>
    </div>

    <?php if (!$caseRow): ?>
        <div class="rounded-2xl bg-white border border-gray-100 shadow-sm p-6 sm:p-10 text-center">
            <div class="mx-auto h-16 w-16 rounded-full bg-gray-100 flex items-center justify-center mb-4">
                <i data-lucide="file-question" class="w-8 h-8 text-gray-400"></i>
            </div>
            <h3 class="text-lg font-semibold text-gray-700 mb-2">Case Not Found</h3>
            <p class="text-sm text-gray-500 mb-5">We could not locate the details for this case.</p>
            <a href="<?= PROJECT_DIR ? '/' . PROJECT_DIR . '/' : '/' ?>my-records"
                class="inline-flex items-center gap-2 rounded-xl bg-gray-600 hover:bg-gray-700 text-white font-semibold text-sm py-3 px-5 transition">
                <i data-lucide="arrow-left" class="w-4 h-4"></i> Return to My Records
            </a>
        </div>
    <?php elseif (($caseRow['status'] ?? '') === 'Rejected' || ($caseRow['approval_status'] ?? '') === 'Rejected'): ?>
        <!-- Rejected: show clean message and redirect to My Records -->
        <div class="rounded-2xl bg-white border border-gray-100 shadow-sm p-6 sm:p-10 text-center">
            <div class="mx-auto h-16 w-16 rounded-full bg-red-50 flex items-center justify-center mb-4">
                <i data-lucide="x-octagon" class="w-8 h-8 text-red-400"></i>
            </div>
            <h3 class="text-lg font-semibold text-gray-700 mb-2">Request Rejected</h3>
            <p class="text-sm text-gray-500 mb-5">This request has been rejected. You can view it under the <strong>Rejected</strong> tab in My Records.</p>
            <a href="<?= PROJECT_DIR ? '/' . PROJECT_DIR . '/' : '/' ?>my-records?tab=rejected"
                class="inline-flex items-center gap-2 rounded-xl bg-red-600 hover:bg-red-700 text-white font-semibold text-sm py-3 px-5 transition">
                <i data-lucide="arrow-left" class="w-4 h-4"></i> Go to My Records
            </a>
        </div>
    <?php else: ?>
        <?php
        $radtechName = $caseRow['radtech_name'] ?? $caseModel->getRadTechName($caseRow['radtech_id'] ?? null);
        $radiologistName = $caseRow['radiologist_name'] ?? null;
        $currentStep = 0;
        $displayStatus = 'Pending';
        $isRejected = $isRejectedGlobal;
        
        $statusVal = $caseRow['status'] ?? 'Pending';
        $recordType = $caseRow['record_type'] ?? ($reqId ? 'Request' : 'Case');

        if ($statusVal === 'Rejected' || ($caseRow['approval_status'] ?? '') === 'Rejected') {
            $currentStep = 0;
            $displayStatus = 'Rejected';
            $isRejected = true;
        } elseif ($statusVal === 'Cancelled') {
            $currentStep = 0;
            $displayStatus = 'Cancelled';
            $isRejected = true;
        } elseif ($recordType === 'Request' && in_array($statusVal, ['Pending Approval', 'Pending'])) {
            $currentStep = 2;
            $displayStatus = 'Pending';
        } elseif ($statusVal === 'Pending Payment') {
            $currentStep = 2;
            $displayStatus = 'Pending Payment';
        } elseif ($statusVal === 'Payment Verifying') {
            $currentStep = 2;
            $displayStatus = 'Payment Verifying';
        } elseif ($statusVal === 'Payment Verified') {
            $currentStep = 3;
            $displayStatus = 'Payment Verified';
        } elseif ($recordType === 'Case' && $statusVal === 'Pending') {
            if (isset($caseRow['image_status']) && $caseRow['image_status'] === 'Uploaded') {
                $currentStep = 5;
                $displayStatus = 'X-ray Taken';
            } else {
                $currentStep = 4;
                $displayStatus = 'Approved';
            }
        } elseif ($statusVal === 'Approved') {
            $currentStep = 4;
            $displayStatus = 'Approved';
        } elseif ($statusVal === 'X-ray Taken') {
            $currentStep = 5;
            $displayStatus = 'X-ray Taken';
        } elseif ($statusVal === 'Under Reading') {
            $currentStep = 5;
            $displayStatus = 'Under Reading';
        } elseif ($statusVal === 'Report Ready') {
            $currentStep = 6;
            $displayStatus = 'Report Ready';
        } elseif (in_array($statusVal, ['Released', 'Completed'])) {
            $currentStep = 7;
            $displayStatus = $statusVal;
        } else {
            $currentStep = 2;
            $displayStatus = $statusVal ?: 'Pending';
        }

        $sInfo = $statusColors[$displayStatus] ?? ['bg' => '#F9FAFB', 'border' => '#E5E7EB', 'text' => '#374151', 'label' => $displayStatus];
        $sDesc = $statusDescriptions[$displayStatus] ?? '';
        $contacts = array_filter([$caseRow['branch_contact'] ?? '', $caseRow['branch_contact_2'] ?? '', $caseRow['branch_contact_3'] ?? '']);
        ?>

        <!-- Unified Layout Matching Picture 4 -->
        <div class="rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden mb-8">
            <!-- Header: Examination Progress + Status Badge -->
            <div class="flex items-center justify-between px-4 sm:px-6 py-3.5 sm:py-4 border-b border-gray-100 gap-2">
                <div class="flex items-center gap-2.5">
                    <i data-lucide="activity" class="w-4 h-4 sm:w-5 sm:h-5 text-red-500"></i>
                    <h2 class="font-bold text-gray-900 text-sm sm:text-base">Examination Progress</h2>
                </div>
                <span class="inline-flex items-center gap-1.5 rounded-full border px-2.5 sm:px-3 py-0.5 sm:py-1 text-[11px] sm:text-xs font-semibold whitespace-nowrap shrink-0 shadow-2xs"
                    style="background: <?= $sInfo['bg'] ?>; border-color: <?= $sInfo['border'] ?>; color: <?= $sInfo['text'] ?>">
                    <span class="w-1.5 h-1.5 rounded-full shrink-0" style="background: <?= $sInfo['text'] ?>"></span>
                    <?= htmlspecialchars($displayStatus) ?>
                </span>
            </div>

            <div class="p-4 sm:p-6">
                <!-- 7-Step Progress Tracker -->
                <?php if (!$isRejected): ?>
                    <div class="w-full pt-2 pb-2">
                        <div class="flex items-start justify-between w-full gap-0.5 sm:gap-1">
                            <?php foreach ($steps as $num => $stepLabel): ?>
                                <?php
                                $done = $num < $currentStep || ($num === count($steps) && $currentStep === count($steps));
                                $active = $num === $currentStep && $currentStep !== count($steps);
                                $pending = $num > $currentStep;
                                ?>
                                <div class="flex flex-col items-center flex-1 min-w-0 text-center px-0.5 sm:px-0">
                                    <div class="relative flex items-center w-full">
                                        <?php
                                        $nextNum = $num + 1;
                                        $nextDone = $nextNum < $currentStep || ($nextNum === count($steps) && $currentStep === count($steps));
                                        $nextActive = $nextNum === $currentStep && $currentStep !== count($steps);
                                        ?>
                                        <?php if ($num > 1): ?>
                                            <div class="absolute left-0 right-1/2 top-1/2 -translate-y-1/2 h-0.5 <?= $done ? 'bg-green-500' : ($active ? 'bg-red-500' : 'bg-gray-200') ?>"></div>
                                        <?php endif; ?>
                                        <?php if ($num < count($steps)): ?>
                                            <div class="absolute left-1/2 right-0 top-1/2 -translate-y-1/2 h-0.5 <?= $nextDone ? 'bg-green-500' : ($nextActive ? 'bg-red-500' : 'bg-gray-200') ?>"></div>
                                        <?php endif; ?>
                                        <div class="relative z-10 mx-auto h-6 w-6 sm:h-9 sm:w-9 rounded-full flex items-center justify-center text-[10px] sm:text-xs font-bold ring-[2px] sm:ring-[3.5px] ring-white transition shrink-0
                                    <?php if ($done): ?>bg-green-500 text-white
                                    <?php elseif ($active): ?>bg-red-500 text-white
                                    <?php else: ?>bg-white border sm:border-2 border-gray-200 text-gray-400<?php endif; ?>">
                                            <?php if ($num === 5 && ($caseRow['status'] ?? '') === 'Under Reading'): ?>
                                                <span id="rad-activity-dot" data-case-id="<?= $caseRow['id'] ?? 0 ?>" class="absolute -top-0.5 -right-0.5 sm:-top-1 sm:-right-1 w-2.5 h-2.5 sm:w-3 sm:h-3 border-2 border-white rounded-full bg-gray-400 z-20 transition-colors"></span>
                                            <?php endif; ?>
                                            <?php if ($done): ?>
                                                <i data-lucide="check" class="w-3.5 h-3.5 sm:w-4 sm:h-4 stroke-[2.5]"></i>
                                            <?php else: ?>
                                                <?= $num ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <span class="mt-1.5 sm:mt-2 text-center text-[8px] sm:text-xs leading-[1.1] sm:leading-tight <?= $done || $active ? 'text-[#1f2937] font-medium' : 'text-gray-400 font-medium' ?> w-full" style="word-break: normal; overflow-wrap: normal;">
                                        <?= htmlspecialchars($stepLabel) ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Status Summary Box -->
                <div class="mt-4 sm:mt-5 rounded-xl p-4 sm:p-5 border status-summary-box"
                    style="background: <?= $sInfo['bg'] ?>; border-color: <?= $sInfo['border'] ?>">
                    <p class="text-sm font-semibold" style="color: <?= $sInfo['text'] ?>">
                        Current Status: <strong><?= htmlspecialchars($sInfo['label']) ?></strong>
                    </p>
                    <?php if ($sDesc): ?>
                        <p class="text-xs sm:text-sm mt-1 sm:mt-1.5 leading-relaxed"
                            style="color: <?= $sInfo['text'] ?>; opacity: 0.85"><?= $sDesc ?></p>
                    <?php endif; ?>
                </div>

                <!-- Case Information Section inside card -->
                <div class="mt-6 pt-6 border-t border-gray-100">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2">
                            <div class="h-7 w-7 rounded-lg bg-red-50 flex items-center justify-center">
                                <i data-lucide="clipboard-list" class="w-4 h-4 text-red-500"></i>
                            </div>
                            <h3 class="font-bold text-gray-900 text-sm sm:text-base">Case Information</h3>
                        </div>
                        <?php if (!empty($contacts)): ?>
                            <button type="button"
                                onclick='showContactOptions(<?= htmlspecialchars(json_encode(array_values($contacts)), ENT_QUOTES, 'UTF-8') ?>)'
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white hover:bg-gray-50 border border-gray-200 text-gray-700 text-xs font-semibold rounded-xl transition shadow-2xs active:scale-95">
                                <i data-lucide="phone" class="w-3.5 h-3.5 text-gray-500"></i>
                                <span>Contact Clinic</span>
                            </button>
                        <?php endif; ?>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                        <div class="space-y-4">
                            <!-- Reference Number -->
                            <div class="flex items-center gap-3">
                                <div class="h-9 w-9 rounded-xl bg-red-50 flex items-center justify-center shrink-0">
                                    <i data-lucide="hash" class="w-4 h-4 text-red-500"></i>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500">Reference #</p>
                                    <p class="text-sm font-semibold text-red-600 font-mono">
                                        <?= htmlspecialchars($caseRow['case_number']) ?>
                                    </p>
                                </div>
                            </div>

                            <!-- Branch -->
                            <div class="flex items-center gap-3">
                                <div class="h-9 w-9 rounded-xl bg-red-50 flex items-center justify-center shrink-0">
                                    <i data-lucide="map-pin" class="w-4 h-4 text-red-500"></i>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500">Branch</p>
                                    <p class="text-sm font-semibold text-gray-800">
                                        <?= htmlspecialchars($caseRow['branch_name'] ?? ($caseRow['branch'] ?? '—')) ?>
                                    </p>
                                </div>
                            </div>

                            <!-- Radiologic Technologist -->
                            <?php if ($radtechName): ?>
                                <div class="flex items-center gap-3">
                                    <div class="h-9 w-9 rounded-xl bg-red-50 flex items-center justify-center shrink-0">
                                        <i data-lucide="user-check" class="w-4 h-4 text-red-500"></i>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500">Radiologic Technologist</p>
                                        <p class="text-sm font-semibold text-gray-800">RT <?= htmlspecialchars($radtechName) ?></p>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="space-y-4">
                            <!-- Examination Type -->
                            <div class="flex items-center gap-3">
                                <div class="h-9 w-9 rounded-xl bg-red-50 flex items-center justify-center shrink-0">
                                    <i data-lucide="scan-line" class="w-4 h-4 text-red-500"></i>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500">Examination Type</p>
                                    <p class="text-sm font-semibold text-gray-800">
                                        <?= htmlspecialchars($caseRow['exam_type'] ?? '—') ?>
                                    </p>
                                </div>
                            </div>

                            <!-- Date -->
                            <div class="flex items-center gap-3">
                                <div class="h-9 w-9 rounded-xl bg-red-50 flex items-center justify-center shrink-0">
                                    <i data-lucide="calendar" class="w-4 h-4 text-red-500"></i>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500">Date</p>
                                    <p class="text-sm font-semibold text-gray-800">
                                        <?= htmlspecialchars(date('F j, Y', strtotime($caseRow['created_at']))) ?>
                                    </p>
                                </div>
                            </div>

                            <!-- Radiologist -->
                            <?php if (!empty($radiologistName)): ?>
                                <div class="flex items-center gap-3">
                                    <div class="h-9 w-9 rounded-xl bg-red-50 flex items-center justify-center shrink-0">
                                        <i data-lucide="stethoscope" class="w-4 h-4 text-red-500"></i>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500">Radiologist</p>
                                        <p class="text-sm font-semibold text-gray-800">Dr. <?= htmlspecialchars($radiologistName) ?></p>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Bottom Action Row (View Report if Released/Completed) -->
                <?php if (in_array($caseRow['status'] ?? '', ['Released', 'Completed']) || ($caseRow['approval_status'] ?? '') === 'Completed'): ?>
                    <div class="mt-6 pt-6 border-t border-gray-100 flex items-center justify-between gap-3 sm:gap-4">
                        <span class="text-[11px] sm:text-sm text-gray-600 leading-tight">Official X-ray report is available for viewing.</span>
                        <?php
                        $isExpired = strtotime($caseRow['created_at']) < strtotime('-3 months');
                        $reportUrl = $isExpired ? 'javascript:void(0)' : (PROJECT_DIR ? '/' . PROJECT_DIR . '/' : '/') . 'view-report?ref=' . base64_encode('Citilife_Case_' . $caseRow['id']);
                        $onClickAttr = $isExpired ? 'onclick="showExpiredAlert(event, ' . htmlspecialchars(json_encode(array_values($contacts)), ENT_QUOTES, 'UTF-8') . ')"' : '';
                        ?>
                        <a href="<?= $reportUrl ?>" <?= $onClickAttr ?>
                            class="inline-flex items-center justify-center gap-1.5 sm:gap-2 rounded-xl text-white font-semibold text-[11px] sm:text-xs py-2 sm:py-2.5 px-4 sm:px-5 transition shadow-sm hover:shadow-md active:scale-95 whitespace-nowrap shrink-0"
                            style="background: linear-gradient(135deg, #15803d, #16a34a);">
                            <i data-lucide="eye" class="w-3.5 h-3.5 sm:w-4 h-4"></i>
                            <span>View Report</span>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Custom Expiry Alert Modal -->
<div class="custom-alert-overlay" id="expired-alert-modal">
    <div class="custom-alert-box">
        <div class="custom-alert-icon-container">
            <!-- Shield with lock/keyhole icon -->
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20 13c0 5-3.5 7.5-7.66 9.7a1 1 0 0 1-.68 0C7.5 20.5 4 18 4 13V6a1 1 0 0 1 .76-.97l8-2a1 1 0 0 1 .48 0l8 2A1 1 0 0 1 20 6z" fill="currentColor" opacity="0.15"/>
                <path d="M20 13c0 5-3.5 7.5-7.66 9.7a1 1 0 0 1-.68 0C7.5 20.5 4 18 4 13V6a1 1 0 0 1 .76-.97l8-2a1 1 0 0 1 .48 0l8 2A1 1 0 0 1 20 6z"/>
                <circle cx="12" cy="11" r="3"/>
                <path d="M12 14v4"/>
            </svg>
        </div>
        <h3 class="custom-alert-title">Result Access Expired</h3>
        <p class="custom-alert-text">This result has exceeded the 3-month availability period. Please contact the clinic for assistance</p>
        <div class="custom-alert-buttons-container">
            <?php if (!empty($contacts)): ?>
                <button type="button" onclick='showContactOptions(<?= json_encode(array_values($contacts)) ?>); document.getElementById("expired-alert-modal").classList.remove("show");' class="custom-alert-btn-secondary" style="display:inline-flex; justify-content:center; align-items:center;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-1"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg> Contact Clinic
                </button>
            <?php else: ?>
                <button type="button" class="custom-alert-btn-secondary" style="display:none;">Contact Us</button>
            <?php endif; ?>
            <button class="custom-alert-btn" onclick="document.getElementById('expired-alert-modal').classList.remove('show')">Close</button>
        </div>
    </div>
</div>

<script>
    function showExpiredAlert(e, contacts) {
        if (e) {
            e.preventDefault();
            e.stopPropagation();
        }
        document.getElementById('expired-alert-modal').classList.add('show');
    }

    function showContactOptions(numbers) {
        if (!numbers || numbers.length === 0) return;
        
        let html = '<div class="flex flex-col gap-3 mt-2">';
        numbers.forEach(num => {
            html += `<a href="tel:${num}" class="flex items-center justify-center gap-2 p-3 rounded-xl border border-gray-200 hover:bg-red-50 hover:border-red-200 hover:text-red-600 text-gray-700 font-bold transition shadow-sm" style="text-decoration:none;">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg> 
                ${num}
            </a>`;
        });
        html += '</div>';

        Swal.fire({
            title: 'Contact Clinic',
            html: html,
            showConfirmButton: false,
            showCloseButton: true,
            didOpen: () => {
                const closeBtn = Swal.getCloseButton();
                if (closeBtn) closeBtn.blur();
            },
            customClass: {
                popup: 'rounded-2xl',
                title: 'text-xl font-bold text-gray-800',
                closeButton: '!outline-none !ring-0 !border-0 !shadow-none !text-gray-500 hover:!text-gray-800'
            }
        });
    }
</script>
