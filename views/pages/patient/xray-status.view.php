<?php
require_once __DIR__ . '/../../../config/database.php';

$caseModel = new \CaseModel($pdo);
$patientModel = new \PatientModel($pdo);

$userId = $_SESSION['user_id'] ?? 0;
$stmtU = $pdo->prepare("SELECT status FROM users WHERE id = ?");
$stmtU->execute([$userId]);
$userAccountStatus = $stmtU->fetchColumn() ?: 'Pending';

// URL parameter hiding logic
if (isset($_GET['case_id'])) {
    $cId = (int) $_GET['case_id'];
    $_SESSION['active_status_case_id'] = $cId;
    if (isset($_GET['is_new']) && $_GET['is_new'] == '1') {
        $_SESSION['active_status_is_new'] = true;
    }
    echo "<script>window.location.href = '/" . PROJECT_DIR . "/xray-status';</script>";
    exit;
}

$caseId = isset($_SESSION['active_status_case_id']) ? (int) $_SESSION['active_status_case_id'] : 0;
$isNew = isset($_SESSION['active_status_is_new']) ? (bool) $_SESSION['active_status_is_new'] : false;

// Status mapping (Frontend logic)
$statusSteps = [
    'Pending' => 2,
    'Approved' => 3,
    'X-ray Taken' => 4,
    'Under Reading' => 4,
    'Report Ready' => 5,
    'Released' => 6,
    'Completed' => 6,
];

$steps = [
    1 => 'Registered',
    2 => 'Approved by RadTech',
    3 => 'X-ray Taken',
    4 => 'Under Reading',
    5 => 'Ready for Release',
    6 => 'Released',
];

// 1. Fetch Patient Info (Backend logic)
$patientId = $_SESSION['patient_id'] ?? null;
if ($patientId) {
    $patientRow = $patientModel->getPatientById($patientId);
} else {
    $patientRow = $patientModel->getPatientByUserId($userId);
    if ($patientRow) {
        $patientId = $patientRow['id'];
        $_SESSION['patient_id'] = $patientId;
    }
}

$casesToDisplay = [];

// 2. Fetch Case Info (Backend logic)
if ($patientId) {
    $activeCases = $caseModel->getActiveCasesByPatient($patientId);

    if ($caseId) {
        $specificCase = $caseModel->getCaseById($caseId);
        if ($specificCase && $specificCase['patient_id'] == $patientId) {
            $casesToDisplay[] = $specificCase;
            unset($_SESSION['active_status_case_id']);
            unset($_SESSION['active_status_is_new']);
        }
    }

    foreach ($activeCases as $ac) {
        $found = false;
        foreach ($casesToDisplay as $c) {
            if ($c['id'] == $ac['id']) {
                $found = true;
                break;
            }
        }
        if (!$found) {
            $casesToDisplay[] = $ac;
        }
    }
}

$isRejectedGlobal = ($userAccountStatus === 'Rejected');

$statusColors = [
    'Pending' => ['bg' => '#FFF7ED', 'border' => '#FED7AA', 'text' => '#C2410C', 'label' => 'Pending Review'],
    'Approved' => ['bg' => '#F0FDF4', 'border' => '#BBF7D0', 'text' => '#15803D', 'label' => 'Approved – Awaiting X-ray'],
    'X-ray Taken' => ['bg' => '#EFF6FF', 'border' => '#BFDBFE', 'text' => '#1D4ED8', 'label' => 'X-ray Taken'],
    'Under Reading' => ['bg' => '#EFF6FF', 'border' => '#DBEAFE', 'text' => '#1D4ED8', 'label' => 'Under Reading by Radiologist'],
    'Report Ready' => ['bg' => '#EEF2FF', 'border' => '#C7D2FE', 'text' => '#4338CA', 'label' => 'Report Ready'], // Indigo
    'Released' => ['bg' => '#F0FDF4', 'border' => '#BBF7D0', 'text' => '#15803D', 'label' => 'Released'], // Green
    'Completed' => ['bg' => '#F0FDF4', 'border' => '#BBF7D0', 'text' => '#15803D', 'label' => 'Completed'], // Green
    'Rejected' => ['bg' => '#FEF2F2', 'border' => '#FECACA', 'text' => '#991B1B', 'label' => 'Request Rejected'],
    'Cancelled' => ['bg' => '#FEF2F2', 'border' => '#FECACA', 'text' => '#991B1B', 'label' => 'Request Cancelled'],
];

$statusDescriptions = [
    'Pending' => 'Your X-ray request has been received and is pending approval from the RadTech team.',
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
        /* light gray/white for visibility */
    }

    body.theme-dark .status-summary-box strong {
        color: #fff !important;
    }

</style>

<div id="xray-status-container" class="space-y-4 sm:space-y-5 pb-8 max-w-3xl mx-auto realtime-update">

    <!-- Page Header -->
    <div>
        <h1 class="text-xl sm:text-2xl font-semibold text-gray-900 tracking-tight">X-ray Status</h1>
        <p class="text-xs sm:text-sm text-gray-500 mt-1">Track your latest examination in real time.</p>
    </div>

    <?php if (isset($_GET['registered'])): ?>
        <!-- Success banner shown immediately after registration -->
        <div id="success-banner"
            class="rounded-xl bg-green-50 border border-green-200 p-4 flex items-start gap-3 transition-all duration-500">
            <i data-lucide="check-circle-2" class="w-5 h-5 text-green-600 shrink-0 mt-0.5"></i>
            <div>
                <p class="text-sm font-bold text-green-800">Registration Submitted!</p>
                <p class="text-sm text-green-700 mt-0.5">Your registration has been sent for RadTech approval. You can track
                    your case status below.</p>
            </div>
        </div>
    <?php endif; ?>

    <!-- Tabs Container -->
    <div class="border-b border-gray-200 mb-6" <?php if(empty($casesToDisplay)) echo 'style="display:none;"'; ?>>
        <nav class="-mb-px flex space-x-6 sm:space-x-8" aria-label="Tabs">
            <button type="button" onclick="switchXrayTab('active')" id="tab-active" class="whitespace-nowrap py-3 sm:py-4 px-1 border-b-2 font-medium text-sm border-red-500 text-red-600 transition-colors">
                Active Requests
            </button>
            <button type="button" onclick="switchXrayTab('cancelled')" id="tab-cancelled" class="whitespace-nowrap py-3 sm:py-4 px-1 border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 transition-colors">
                Cancelled
            </button>
        </nav>
    </div>

    <?php 
    $activeCount = 0;
    $cancelledCount = 0;
    foreach ($casesToDisplay as $c) {
        if ($c['status'] === 'Rejected') continue; // not shown on this page
        if ($c['status'] === 'Cancelled') $cancelledCount++;
        else $activeCount++;
    }
    ?>

    <div id="no-active-msg" class="rounded-2xl bg-white border border-gray-100 shadow-sm p-6 sm:p-10 text-center mb-6" <?= $activeCount > 0 ? 'style="display:none;"' : '' ?>>
        <div class="mx-auto h-16 w-16 rounded-full bg-gray-100 flex items-center justify-center mb-4">
            <i data-lucide="inbox" class="w-8 h-8 text-gray-400"></i>
        </div>
        <h3 class="text-lg font-semibold text-gray-700 mb-2">No Active Case Found</h3>
        <p class="text-sm text-gray-500 mb-5">You don't have any active X-ray case at the moment.</p>
        <a href="/<?= PROJECT_DIR ?>/registration"
            class="inline-flex items-center gap-2 rounded-xl bg-red-600 hover:bg-red-700 text-white font-semibold text-sm py-3 px-5 transition">
            <i data-lucide="plus-circle" class="w-4 h-4"></i> Register for X-ray
        </a>
    </div>

    <div id="no-cancelled-msg" class="rounded-2xl bg-white border border-gray-100 shadow-sm p-6 sm:p-10 text-center mb-6" style="display:none;">
        <div class="mx-auto h-16 w-16 rounded-full bg-gray-100 flex items-center justify-center mb-4">
            <i data-lucide="x-circle" class="w-8 h-8 text-gray-400"></i>
        </div>
        <h3 class="text-lg font-semibold text-gray-700 mb-2">No Cancelled Requests</h3>
        <p class="text-sm text-gray-500 mb-5">You don't have any cancelled requests in your history.</p>
    </div>

    <?php foreach ($casesToDisplay as $caseRow): ?>
        <?php
        // Rejected records are shown in My Records — skip them here entirely
        if ($caseRow['status'] === 'Rejected') continue;
        $isCancelledTab = ($caseRow['status'] === 'Cancelled');
        $tabClass = $isCancelledTab ? 'case-item-cancelled' : 'case-item-active';
        ?>
        <?php
        $radtechName = $caseModel->getRadTechName($caseRow['radtech_id'] ?? null);
            $currentStep = 0;
            $displayStatus = 'Pending';
            $isRejected = $isRejectedGlobal;

            $recordType = $caseRow['record_type'] ?? 'Case';

            if ($caseRow['status'] === 'Rejected') {
                $currentStep = 0;
                $displayStatus = 'Rejected';
                $isRejected = true;
            } elseif ($caseRow['status'] === 'Cancelled') {
                $currentStep = 0;
                $displayStatus = 'Cancelled';
                $isRejected = true; // Use the same UI behavior as Rejected
            } elseif ($recordType === 'Request' && $caseRow['status'] === 'Pending Approval') {
                $currentStep = 2;
                $displayStatus = 'Pending';
            } elseif ($recordType === 'Case' && $caseRow['status'] === 'Pending') {
                if (isset($caseRow['image_status']) && $caseRow['image_status'] === 'Uploaded') {
                    $currentStep = 4;
                    $displayStatus = 'X-ray Taken';
                } else {
                    $currentStep = 3;
                    $displayStatus = 'Approved';
                }
            } elseif ($caseRow['status'] === 'Under Reading') {
                $currentStep = 4;
                $displayStatus = 'Under Reading';
            } elseif ($caseRow['status'] === 'Report Ready') {
                $currentStep = 5;
                $displayStatus = 'Report Ready';
            } elseif (in_array($caseRow['status'], ['Released', 'Completed'])) {
                $currentStep = 6;
                $displayStatus = $caseRow['status'];
            } else {
                $currentStep = 2;
                $displayStatus = $caseRow['status'] ?: 'Pending';
            }
            ?>
            <div class="mb-5 status-case-wrapper <?= $tabClass ?>" data-case-id="<?= $caseRow['id'] ?>" <?= $isCancelledTab ? 'style="display:none;"' : '' ?>>
                <?php if ($isCancelledTab):
                    $contacts = array_filter([$caseRow['branch_contact'] ?? '', $caseRow['branch_contact_2'] ?? '', $caseRow['branch_contact_3'] ?? '']);
                ?>
                    <!-- Compact Cancelled Card (My Records style) -->
                    <div class="rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden">
                        <!-- Header row -->
                        <div class="px-4 py-3 bg-red-50 border-b border-red-100 flex justify-between items-center">
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-0.5 bg-red-600 text-white text-[10px] font-bold rounded-sm uppercase tracking-wide">Request No.</span>
                                <span class="font-mono text-sm font-bold text-red-900"><?= htmlspecialchars($caseRow['case_number']) ?></span>
                            </div>
                            <span class="text-red-600 font-bold text-xs uppercase tracking-wider">Cancelled</span>
                        </div>
                        <!-- Body -->
                        <div class="p-4 sm:p-5 flex gap-4 sm:gap-6 items-start">
                            <div class="w-16 h-16 sm:w-20 sm:h-20 bg-gray-100 rounded-lg flex-shrink-0 flex items-center justify-center border border-gray-200">
                                <i data-lucide="x-octagon" class="w-8 h-8 text-red-400"></i>
                            </div>
                            <div class="flex-1">
                                <h4 class="text-base sm:text-lg font-bold text-gray-900 leading-tight mb-1">
                                    <?= htmlspecialchars($caseRow['exam_type'] ?? 'To be determined') ?>
                                </h4>
                                <div class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-4 text-xs sm:text-sm text-gray-500">
                                    <span class="flex items-center gap-1.5">
                                        <i data-lucide="calendar" class="w-4 h-4"></i>
                                        <?= htmlspecialchars(date('M j, Y - h:i A', strtotime($caseRow['created_at']))) ?>
                                    </span>
                                    <span class="flex items-center gap-1.5">
                                        <i data-lucide="map-pin" class="w-4 h-4"></i>
                                        <?= htmlspecialchars($caseRow['branch_name'] ?? $caseRow['branch'] ?? '—') ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <!-- Footer -->
                        <?php if (!empty($contacts)): ?>
                        <div class="px-3 sm:px-4 py-2.5 sm:py-3 bg-gray-50 border-t border-gray-100 flex justify-end items-center gap-2 sm:gap-3">
                            <button type="button"
                                onclick='showContactOptions(<?= htmlspecialchars(json_encode(array_values($contacts)), ENT_QUOTES, 'UTF-8') ?>)'
                                class="px-3 sm:px-4 py-1.5 sm:py-2 border border-gray-300 rounded-lg text-xs sm:text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                                Contact Clinic
                            </button>
                        </div>
                        <?php endif; ?>
                    </div>

                <?php else: ?>
                    <!-- Active Request: full Case Information + Examination Progress layout -->
                    <?php if (in_array($caseRow['status'], ['Released', 'Completed'])): ?>
                        <!-- Completed / Released State Banner -->
                        <div class="rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden mb-4">
                            <div class="px-5 py-4 border-b border-gray-100 flex items-center gap-2">
                                <i data-lucide="circle-check-big" class="w-5 h-5 text-green-600"></i>
                                <h2 class="font-bold text-gray-900">X-ray Report Released</h2>
                            </div>
                            <div class="p-5 space-y-4">
                                <p class="text-sm text-gray-600">Your X-ray report for case <span
                                        class="font-mono font-semibold text-red-600"><?= htmlspecialchars($caseRow['case_number']) ?></span>
                                    has been released. You may view your result below.</p>
                                <?php
                                $isExpired = strtotime($caseRow['created_at']) < strtotime('-3 months');
                                $reportUrl = $isExpired ? 'javascript:void(0)' : '/' . PROJECT_DIR . '/view-report?ref=' . base64_encode('CitiLife_Case_' . $caseRow['id']);
                                $contacts = array_filter([$caseRow['branch_contact'] ?? '', $caseRow['branch_contact_2'] ?? '', $caseRow['branch_contact_3'] ?? '']);
                                $onClickAttr = $isExpired ? 'onclick="showExpiredAlert(event, ' . htmlspecialchars(json_encode(array_values($contacts)), ENT_QUOTES, 'UTF-8') . ')"' : '';
                                ?>
                                <a href="<?= $reportUrl ?>" <?= $onClickAttr ?>
                                    class="inline-flex items-center gap-2 rounded-xl text-white font-semibold text-sm py-3 px-6 transition shadow-sm hover:shadow-md"
                                    style="background: linear-gradient(135deg, #15803d, #16a34a);">
                                    <i data-lucide="eye" class="w-4 h-4"></i>
                                    View Report
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Case Information Card -->
                    <div class="rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden mb-4 sm:mb-5">
                        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                            <h2 class="font-bold text-gray-900">Case Information</h2>
                            <div class="flex items-center gap-2">
                                <?php
                                $contacts = array_filter([$caseRow['branch_contact'] ?? '', $caseRow['branch_contact_2'] ?? '', $caseRow['branch_contact_3'] ?? '']);
                                if (!empty($contacts)):
                                    ?>
                                    <button type="button" onclick='showContactOptions(<?= json_encode(array_values($contacts)) ?>)'
                                        class="text-xs font-semibold text-gray-600 bg-white border border-gray-200 hover:bg-gray-50 px-3 py-1.5 rounded-lg transition flex items-center gap-1.5 shadow-sm">
                                        <i data-lucide="phone" class="w-3.5 h-3.5"></i> Contact Clinic
                                    </button>
                                <?php endif; ?>
                                <?php if ($displayStatus === 'Pending'): ?>
                                    <button type="button"
                                        onclick="cancelCase(<?= $caseRow['id'] ?>, '<?= htmlspecialchars($caseRow['case_number']) ?>')"
                                        class="text-xs font-semibold text-red-600 bg-red-50 hover:bg-red-100 px-3 py-1.5 rounded-lg transition flex items-center gap-1">
                                        <i data-lucide="x" class="w-3 h-3"></i> Cancel Request
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="p-4 sm:p-5 grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                            <div class="space-y-3">
                                <div class="flex items-start gap-3">
                                    <div class="h-8 w-8 rounded-lg bg-red-50 flex items-center justify-center shrink-0">
                                        <i data-lucide="hash" class="w-4 h-4 text-red-500"></i>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500">Reference #</p>
                                        <p class="text-sm font-semibold text-red-600 font-mono">
                                            <?= htmlspecialchars($caseRow['case_number']) ?>
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-start gap-3">
                                    <div class="h-8 w-8 rounded-lg bg-red-50 flex items-center justify-center shrink-0">
                                        <i data-lucide="map-pin" class="w-4 h-4 text-red-500"></i>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500">Branch</p>
                                        <p class="text-sm font-semibold text-gray-800">
                                            <?= htmlspecialchars($caseRow['branch_name'] ?? $caseRow['branch'] ?? '—') ?>
                                        </p>
                                    </div>
                                </div>
                                <?php if ($radtechName): ?>
                                    <div class="flex items-start gap-3">
                                        <div class="h-8 w-8 rounded-lg bg-red-50 flex items-center justify-center shrink-0">
                                            <i data-lucide="user-check" class="w-4 h-4 text-red-500"></i>
                                        </div>
                                        <div>
                                            <p class="text-xs text-gray-500">Radiologic Technologist</p>
                                            <p class="text-sm font-semibold text-gray-800">RT <?= htmlspecialchars($radtechName) ?></p>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="space-y-3">
                                <div class="flex items-start gap-3">
                                    <div class="h-8 w-8 rounded-lg bg-red-50 flex items-center justify-center shrink-0">
                                        <i data-lucide="scan-line" class="w-4 h-4 text-red-500"></i>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500">Examination Type</p>
                                        <p class="text-sm font-semibold text-gray-800">
                                            <?= htmlspecialchars($caseRow['exam_type'] ?? '—') ?>
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-start gap-3">
                                    <div class="h-8 w-8 rounded-lg bg-red-50 flex items-center justify-center shrink-0">
                                        <i data-lucide="calendar" class="w-4 h-4 text-red-500"></i>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500">Date</p>
                                        <p class="text-sm font-semibold text-gray-800">
                                            <?= htmlspecialchars(date('F j, Y', strtotime($caseRow['created_at']))) ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Examination Progress -->
                    <div class="rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden">
                        <div class="px-5 py-4 border-b border-gray-100">
                            <h2 class="font-bold text-gray-900">Examination Progress</h2>
                        </div>
                        <div class="p-3 sm:p-5 space-y-4 sm:space-y-5">
                            <!-- Step tracker -->
                            <?php if (!$isRejected): ?>
                                <div class="flex items-start justify-between gap-0.5 sm:gap-1 overflow-x-auto pt-2.5 pb-2">
                                    <?php foreach ($steps as $num => $label): ?>
                                        <?php
                                        $done = $num < $currentStep || ($num === count($steps) && $currentStep === count($steps));
                                        $active = $num === $currentStep && $currentStep !== count($steps);
                                        ?>
                                        <div class="flex flex-col items-center gap-1 sm:gap-2 flex-1 min-w-[40px] sm:min-w-[56px]">
                                            <div class="relative flex items-center w-full">
                                                <?php
                                                $nextNum = $num + 1;
                                                $nextDone = $nextNum < $currentStep || ($nextNum === count($steps) && $currentStep === count($steps));
                                                $nextActive = $nextNum === $currentStep && $currentStep !== count($steps);
                                                ?>
                                                <?php if ($num > 1): ?>
                                                    <div class="absolute left-0 right-1/2 top-1/2 -translate-y-1/2 h-0.5 <?= $done ? 'bg-green-500' : ($active ? 'bg-red-500' : 'bg-gray-200') ?>"
                                                        style="z-index:0"></div>
                                                <?php endif; ?>
                                                <?php if ($num < count($steps)): ?>
                                                    <div class="absolute left-1/2 right-0 top-1/2 -translate-y-1/2 h-0.5 <?= $nextDone ? 'bg-green-500' : ($nextActive ? 'bg-red-500' : 'bg-gray-200') ?>"
                                                        style="z-index:0"></div>
                                                <?php endif; ?>
                                                <div class="relative z-10 mx-auto h-7 w-7 sm:h-10 sm:w-10 rounded-full flex items-center justify-center text-xs sm:text-sm font-bold border-2 transition
                                    <?php if ($done): ?>bg-green-500 border-green-500 text-white
                                    <?php elseif ($active): ?>bg-red-500 border-red-500 text-white ring-2 sm:ring-4 ring-red-100
                                    <?php else: ?>bg-white border-gray-200 text-gray-400<?php endif; ?>">
                                                    <?php if ($num === 4 && $caseRow['status'] === 'Under Reading'): ?>
                                                        <span id="rad-activity-dot" data-case-id="<?= $caseRow['id'] ?>"
                                                            class="absolute -top-0.5 -right-0.5 sm:-top-1 sm:-right-1 w-2.5 h-2.5 sm:w-3.5 sm:h-3.5 border-2 border-white rounded-full bg-gray-400 z-20 transition-colors"></span>
                                                    <?php endif; ?>
                                                    <?php if ($done): ?>
                                                        <i data-lucide="check" class="w-3 h-3 sm:w-4 sm:h-4"></i>
                                                    <?php else: ?>
                                                        <?= $num ?>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <span
                                                class="text-center text-[8px] sm:text-[10px] leading-tight font-medium <?= $done || $active ? 'text-gray-700' : 'text-gray-400' ?>"><?= $label ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <?php
                            $sInfo = $statusColors[$displayStatus] ?? ['bg' => '#F9FAFB', 'border' => '#E5E7EB', 'text' => '#374151', 'label' => $displayStatus];
                            $sDesc = $statusDescriptions[$displayStatus] ?? '';
                            ?>
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

                        </div>
                    </div>

                <?php endif; ?>
            </div> <!-- End status-case-wrapper -->
        <?php endforeach; ?>

        <!-- Pagination Bar -->
        <div id="xray-pagination-container" class="mb-4" style="display:none;">
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm px-4 py-3">
                <div id="xray-pagination-inner" style="display:flex; align-items:center; gap:8px; width:100%;">
                    <span id="xray-count-info" style="font-size:13px; color:#6b7280;"></span>
                    <div id="xray-pagination-nav" style="display:flex; align-items:center; gap:8px;">
                        <button type="button" id="xray-prev-btn"
                            style="width:32px;height:32px;display:flex;align-items:center;justify-content:center;border-radius:8px;border:1px solid #e5e7eb;color:#6b7280;background:white;cursor:pointer;transition:background .15s;"
                            onmouseover="if(!this.disabled)this.style.background='#f9fafb'" onmouseout="this.style.background='white'">
                            <i data-lucide="chevron-left" style="width:16px;height:16px;"></i>
                        </button>
                        <span id="xray-page-info" style="font-size:14px; font-weight:600; color:#374151; padding:0 4px; white-space:nowrap;"></span>
                        <button type="button" id="xray-next-btn"
                            style="width:32px;height:32px;display:flex;align-items:center;justify-content:center;border-radius:8px;border:1px solid #e5e7eb;color:#6b7280;background:white;cursor:pointer;transition:background .15s;"
                            onmouseover="if(!this.disabled)this.style.background='#f9fafb'" onmouseout="this.style.background='white'">
                            <i data-lucide="chevron-right" style="width:16px;height:16px;"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- New Request CTA -->
        <div class="rounded-2xl bg-white border border-gray-100 shadow-sm p-6 text-center mt-5">
            <div class="mx-auto h-14 w-14 rounded-full bg-red-50 flex items-center justify-center mb-3">
                <i data-lucide="plus-circle" class="w-7 h-7 text-red-500"></i>
            </div>
            <h3 class="text-base font-bold text-gray-800 mb-1">Want to register a new X-ray request?</h3>
            <p class="text-sm text-gray-500 mb-4">You can submit a new X-ray examination request anytime.</p>
            <a href="/<?= PROJECT_DIR ?>/registration"
                class="inline-flex items-center gap-2 rounded-xl bg-red-600 hover:bg-red-700 text-white font-bold text-sm py-3 px-6 transition shadow-sm">
                <i data-lucide="plus-circle" class="w-4 h-4"></i> New X-ray Request
            </a>
        </div>
</div>

<script>
    window.currentXrayTab = 'active';
    window.xrayTabPages = { active: 1, cancelled: 1 };
    const ITEMS_PER_PAGE = 5;

    function renderPaginationControls(totalItems, tab, rootEl) {
        const isDoc = !rootEl || rootEl === document;
        const qs = (id) => isDoc ? document.getElementById(id) : rootEl.querySelector('#' + id);

        const paginationContainer = qs('xray-pagination-container');
        if (!paginationContainer) return;

        if (totalItems <= ITEMS_PER_PAGE) {
            paginationContainer.style.display = 'none';
            return;
        }

        paginationContainer.style.display = '';
        if (window.lucide) lucide.createIcons({ root: paginationContainer });

        const totalPages = Math.max(1, Math.ceil(totalItems / ITEMS_PER_PAGE));
        const currentPage = window.xrayTabPages[tab] || 1;
        const startDisplay = (currentPage - 1) * ITEMS_PER_PAGE + 1;
        const endDisplay = Math.min(currentPage * ITEMS_PER_PAGE, totalItems);
        const pageText = `Page ${currentPage} of ${totalPages}`;
        const countText = `Showing ${startDisplay}\u2013${endDisplay} of ${totalItems} records`;

        const inner    = qs('xray-pagination-inner');
        const countEl  = qs('xray-count-info');
        const pageEl   = qs('xray-page-info');
        const prevBtn  = qs('xray-prev-btn');
        const nextBtn  = qs('xray-next-btn');
        const navEl    = qs('xray-pagination-nav');

        if (countEl)  countEl.textContent  = countText;
        if (pageEl)   pageEl.textContent   = pageText;
        if (prevBtn)  prevBtn.disabled     = currentPage <= 1;
        if (nextBtn)  nextBtn.disabled     = currentPage >= totalPages;

        // Apply responsive layout
        if (inner && navEl) {
            if (window.innerWidth >= 640) {
                // Desktop: row, count left, nav right
                inner.style.flexDirection  = 'row';
                inner.style.justifyContent = 'space-between';
                inner.style.alignItems     = 'center';
                navEl.style.marginLeft     = 'auto';
                if (countEl) { countEl.style.textAlign = 'left'; countEl.style.width = 'auto'; }
            } else {
                // Mobile: column, count top center, nav full-width
                inner.style.flexDirection  = 'column';
                inner.style.justifyContent = 'center';
                inner.style.alignItems     = 'center';
                navEl.style.marginLeft     = '0';
                navEl.style.width          = '100%';
                navEl.style.justifyContent = 'space-between';
                if (countEl) { countEl.style.textAlign = 'center'; countEl.style.width = '100%'; }
            }
        }
    }

    window.addEventListener('resize', () => {
        // Re-apply layout on resize if pagination is visible
        const container = document.getElementById('xray-pagination-container');
        if (container && container.style.display !== 'none') {
            switchXrayTab(window.currentXrayTab || 'active');
        }
    });

    function applyPagination(items, tab) {
        let currentPage = window.xrayTabPages[tab] || 1;
        const totalItems = items.length;
        const totalPages = Math.ceil(totalItems / ITEMS_PER_PAGE);
        
        if (currentPage > totalPages && totalPages > 0) {
            currentPage = totalPages;
            window.xrayTabPages[tab] = currentPage;
        }

        const startIdx = (currentPage - 1) * ITEMS_PER_PAGE;
        const endIdx = startIdx + ITEMS_PER_PAGE;

        let visibleCount = 0;
        items.forEach((el, index) => {
            if (index >= startIdx && index < endIdx) {
                el.style.display = 'block';
                visibleCount++;
            } else {
                el.style.display = 'none';
            }
        });
        return { total: totalItems, visible: visibleCount };
    }

    function switchXrayTab(tab) {
        window.currentXrayTab = tab;
        
        const activeClass = 'whitespace-nowrap py-3 sm:py-4 px-1 border-b-2 font-medium text-sm border-red-500 text-red-600 transition-colors';
        const inactiveClass = 'whitespace-nowrap py-3 sm:py-4 px-1 border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 transition-colors';
        
        const activeTabEl = document.getElementById('tab-active');
        const cancelledTabEl = document.getElementById('tab-cancelled');
        
        if (activeTabEl) activeTabEl.className = tab === 'active' ? activeClass : inactiveClass;
        if (cancelledTabEl) cancelledTabEl.className = tab === 'cancelled' ? activeClass : inactiveClass;
        
        const activeItems = document.querySelectorAll('.case-item-active');
        const cancelledItems = document.querySelectorAll('.case-item-cancelled');
        
        if (tab === 'active') {
            cancelledItems.forEach(el => el.style.display = 'none');
            const stats = applyPagination(activeItems, 'active');
            
            const noActiveMsg = document.getElementById('no-active-msg');
            const noCancelledMsg = document.getElementById('no-cancelled-msg');
            if (noActiveMsg) noActiveMsg.style.display = stats.total > 0 ? 'none' : 'block';
            if (noCancelledMsg) noCancelledMsg.style.display = 'none';
            
            renderPaginationControls(stats.total, 'active', null);
        } else {
            activeItems.forEach(el => el.style.display = 'none');
            const stats = applyPagination(cancelledItems, 'cancelled');
            
            const noActiveMsg = document.getElementById('no-active-msg');
            const noCancelledMsg = document.getElementById('no-cancelled-msg');
            if (noActiveMsg) noActiveMsg.style.display = 'none';
            if (noCancelledMsg) noCancelledMsg.style.display = stats.total > 0 ? 'none' : 'block';
            
            renderPaginationControls(stats.total, 'cancelled', null);
        }
    }

    // Restore the correct tab after a real-time DOM polling update to prevent flickering/resetting
    document.addEventListener('realtime:beforeUpdate', (e) => {
        const newEl = e.detail.newEl;
        if (!newEl) return;
        
        const tab = window.currentXrayTab || 'active';
        const activeClass = 'whitespace-nowrap py-3 sm:py-4 px-1 border-b-2 font-medium text-sm border-red-500 text-red-600 transition-colors';
        const inactiveClass = 'whitespace-nowrap py-3 sm:py-4 px-1 border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 transition-colors';
        
        const tabActiveBtn = newEl.querySelector('#tab-active');
        const tabCancelledBtn = newEl.querySelector('#tab-cancelled');
        
        if (tabActiveBtn) tabActiveBtn.className = tab === 'active' ? activeClass : inactiveClass;
        if (tabCancelledBtn) tabCancelledBtn.className = tab === 'cancelled' ? activeClass : inactiveClass;
        
        const activeItems = newEl.querySelectorAll('.case-item-active');
        const cancelledItems = newEl.querySelectorAll('.case-item-cancelled');
        const paginationContainer = newEl.querySelector('#xray-pagination-container');
        
        if (tab === 'active') {
            cancelledItems.forEach(el => el.style.display = 'none');
            const stats = applyPagination(activeItems, 'active');
            
            const noActiveMsg = newEl.querySelector('#no-active-msg');
            const noCancelledMsg = newEl.querySelector('#no-cancelled-msg');
            if (noActiveMsg) noActiveMsg.style.display = stats.total > 0 ? 'none' : 'block';
            if (noCancelledMsg) noCancelledMsg.style.display = 'none';
            
            renderPaginationControls(stats.total, 'active', newEl);
        } else {
            activeItems.forEach(el => el.style.display = 'none');
            const stats = applyPagination(cancelledItems, 'cancelled');
            
            const noActiveMsg = newEl.querySelector('#no-active-msg');
            const noCancelledMsg = newEl.querySelector('#no-cancelled-msg');
            if (noActiveMsg) noActiveMsg.style.display = 'none';
            if (noCancelledMsg) noCancelledMsg.style.display = stats.total > 0 ? 'none' : 'block';
            
            renderPaginationControls(stats.total, 'cancelled', newEl);
        }
    });

    // Single delegated click handler for pagination prev/next — bound once, never stale
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('button');
        if (!btn) return;
        const id = btn.id;
        if (id !== 'xray-prev-btn' && id !== 'xray-next-btn') return;

        e.preventDefault();
        const dir = id === 'xray-prev-btn' ? 'prev' : 'next';
        const tab = window.currentXrayTab || 'active';
        const items = tab === 'active'
            ? document.querySelectorAll('.case-item-active')
            : document.querySelectorAll('.case-item-cancelled');
        const totalPages = Math.max(1, Math.ceil(items.length / ITEMS_PER_PAGE));
        const p = window.xrayTabPages[tab] || 1;

        if (dir === 'prev' && p > 1) {
            window.xrayTabPages[tab] = p - 1;
            switchXrayTab(tab);
        } else if (dir === 'next' && p < totalPages) {
            window.xrayTabPages[tab] = p + 1;
            switchXrayTab(tab);
        }
    });

    // Stealth Mode: capture ?tab BEFORE cleaning the URL
    const _initialXrayTab = new URLSearchParams(window.location.search).get('tab');
    if (window.history && window.history.replaceState) {
        window.history.replaceState(null, null, window.location.pathname);
    }

    document.addEventListener('DOMContentLoaded', () => {
        // Use pre-captured tab param (URL is already clean by this point)
        const initialTab = (_initialXrayTab === 'cancelled') ? 'cancelled' : 'active';
        switchXrayTab(initialTab);

        const highlightCaseId = "<?= $caseId ?>";
        const isNew = <?= $isNew ? 'true' : 'false' ?>;

        if (highlightCaseId && highlightCaseId !== "0") {
            const el = document.querySelector(`.status-case-wrapper[data-case-id="${highlightCaseId}"]`);
            if (el) {
                if (isNew) {
                    const cards = el.querySelectorAll('.bg-white.shadow-sm');
                    cards.forEach(card => {
                        card.classList.add('transition-all', 'duration-300');
                        card.style.backgroundColor = '#fef08a';
                    });
                    
                    setTimeout(() => {
                        cards.forEach(card => card.style.backgroundColor = '#fde047');
                        setTimeout(() => {
                            cards.forEach(card => card.style.backgroundColor = '#fef08a');
                            setTimeout(() => {
                                cards.forEach(card => {
                                    card.style.backgroundColor = '';
                                    card.classList.remove('transition-all', 'duration-300');
                                });
                            }, 300);
                        }, 300);
                    }, 200);
                }
            }
        }
    });

    // Auto-hide success banner after 8 seconds
    const successBanner = document.getElementById('success-banner');
    if (successBanner) {
        setTimeout(() => {
            successBanner.style.opacity = '0';
            setTimeout(() => successBanner.remove(), 500);
        }, 8000);
    }
</script>

<!-- Custom Expiry Alert Modal -->
<div class="custom-alert-overlay" id="expired-alert-modal">
    <div class="custom-alert-box">
        <div class="custom-alert-icon-container">
            <!-- Shield with lock/keyhole icon -->
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path
                    d="M20 13c0 5-3.5 7.5-7.66 9.7a1 1 0 0 1-.68 0C7.5 20.5 4 18 4 13V6a1 1 0 0 1 .76-.97l8-2a1 1 0 0 1 .48 0l8 2A1 1 0 0 1 20 6z"
                    fill="currentColor" opacity="0.15" />
                <path
                    d="M20 13c0 5-3.5 7.5-7.66 9.7a1 1 0 0 1-.68 0C7.5 20.5 4 18 4 13V6a1 1 0 0 1 .76-.97l8-2a1 1 0 0 1 .48 0l8 2A1 1 0 0 1 20 6z" />
                <circle cx="12" cy="11" r="3" />
                <path d="M12 14v4" />
            </svg>
        </div>
        <h3 class="custom-alert-title">Result Access Expired</h3>
        <p class="custom-alert-text">This result has exceeded the 3-month availability period. Please contact the clinic
            for assistance</p>
        <div class="custom-alert-buttons-container">
            <a id="expired-alert-contact-btn" href="#" class="custom-alert-btn-secondary"
                style="text-decoration:none; display:none; justify-content:center; align-items:center;">Contact Us</a>
            <button class="custom-alert-btn"
                onclick="document.getElementById('expired-alert-modal').classList.remove('show')">Close</button>
        </div>
    </div>
</div>

<script>
    function showExpiredAlert(e, contacts = []) {
        if (e) {
            e.preventDefault();
            e.stopPropagation();
        }
        const contactBtn = document.getElementById('expired-alert-contact-btn');
        if (contacts && contacts.length > 0) {
            contactBtn.setAttribute('onclick', `showContactOptions(${JSON.stringify(contacts)}); document.getElementById('expired-alert-modal').classList.remove('show'); return false;`);
            contactBtn.href = "#";
            contactBtn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-1"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg> Contact Clinic';
            contactBtn.style.display = 'inline-flex';
        } else {
            contactBtn.style.display = 'none';
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

    function cancelCase(caseId, caseNumber) {
        Swal.fire({
            title: 'Cancel Request?',
            text: `Are you sure you want to cancel the request for ${caseNumber}? This action cannot be undone.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Yes, cancel it!',
            cancelButtonText: 'No, keep it',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Cancelling...',
                    html: 'Please wait',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                fetch('/<?= PROJECT_DIR ?>/app/api/cancel_case.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ case_id: caseId })
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire('Cancelled!', 'Your request has been cancelled.', 'success').then(() => {
                                window.location.href = '/<?= PROJECT_DIR ?>/xray-status';
                            });
                        } else {
                            Swal.fire('Error', data.message || 'Failed to cancel the request.', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire('Error', 'An unexpected error occurred.', 'error');
                    });
            }
        });
    }
</script>