<?php
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../models/CaseModel.php';
require_once __DIR__ . '/../../../models/PatientModel.php';

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
    header("Location: /" . PROJECT_DIR . "/index.php?role=patient&page=xray-status");
    exit;
}

$caseId = isset($_SESSION['active_status_case_id']) ? (int) $_SESSION['active_status_case_id'] : 0;

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

$caseRow = null;
$radtechName = null;

// 2. Fetch Case Info (Backend logic)
if ($patientId) {
    if ($caseId) {
        $caseRow = $caseModel->getCaseById($caseId);
        // Security check: ensure case belongs to patient
        if ($caseRow && $caseRow['patient_id'] != $patientId) {
            $caseRow = null;
        }
    } else {
        $caseRow = $caseModel->getLatestCaseByPatient($patientId);
    }

    if ($caseRow) {
        $radtechName = $caseModel->getRadTechName($caseRow['radtech_id'] ?? null);
    }
}

$currentStep = 0;
$displayStatus = 'Pending';
$isRejected = ($userAccountStatus === 'Rejected');

if ($caseRow) {
    if (isset($caseRow['approval_status']) && $caseRow['approval_status'] === 'Rejected') {
        $currentStep = 0;
        $displayStatus = 'Rejected';
        $isRejected = true;
    } elseif ($caseRow['status'] === 'Pending') {
        if (isset($caseRow['approval_status']) && $caseRow['approval_status'] === 'Pending') {
            $currentStep = 2; // Step 1 Done. Step 2 Active waiting for approval.
            $displayStatus = 'Pending';
        } elseif (isset($caseRow['approval_status']) && $caseRow['approval_status'] === 'Approved') {
            if (isset($caseRow['image_status']) && $caseRow['image_status'] === 'Uploaded') {
                $currentStep = 4; // Step 3 Done. Step 4 Active waiting for radiologist.
                $displayStatus = 'X-ray Taken';
            } else {
                $currentStep = 3; // Step 2 Done. Step 3 Active waiting for X-ray to be taken.
                $displayStatus = 'Approved';
            }
        } else {
            $currentStep = 2;
            $displayStatus = 'Pending';
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
    } elseif ($caseRow['status'] === 'Rejected') {
        $currentStep = 0;
        $displayStatus = 'Rejected';
        $isRejected = true;
    } else {
        $currentStep = 2;
        $displayStatus = $caseRow['status'] ?: 'Pending';
    }
}


$statusColors = [
    'Pending' => ['bg' => '#FFF7ED', 'border' => '#FED7AA', 'text' => '#C2410C', 'label' => 'Pending Review'],
    'Approved' => ['bg' => '#F0FDF4', 'border' => '#BBF7D0', 'text' => '#15803D', 'label' => 'Approved – Awaiting X-ray'],
    'X-ray Taken' => ['bg' => '#EFF6FF', 'border' => '#BFDBFE', 'text' => '#1D4ED8', 'label' => 'X-ray Taken'],
    'Under Reading' => ['bg' => '#EFF6FF', 'border' => '#DBEAFE', 'text' => '#1D4ED8', 'label' => 'Under Reading by Radiologist'],
    'Report Ready' => ['bg' => '#EEF2FF', 'border' => '#C7D2FE', 'text' => '#4338CA', 'label' => 'Report Ready'], // Indigo
    'Released' => ['bg' => '#F0FDF4', 'border' => '#BBF7D0', 'text' => '#15803D', 'label' => 'Released'], // Green
    'Completed' => ['bg' => '#F0FDF4', 'border' => '#BBF7D0', 'text' => '#15803D', 'label' => 'Completed'], // Green
    'Rejected' => ['bg' => '#FEF2F2', 'border' => '#FECACA', 'text' => '#991B1B', 'label' => 'Request Rejected'],
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
];
?>

<style>
    /* Dark mode transparency for status summary box */
    body.theme-dark .status-summary-box {
        background: transparent !important;
        border-color: rgba(255, 255, 255, 0.15) !important;
    }
    body.theme-dark .status-summary-box p {
        color: #e2e8f0 !important; /* light gray/white for visibility */
    }
    body.theme-dark .status-summary-box strong {
        color: #fff !important;
    }
</style>

<div id="xray-status-container" class="space-y-4 sm:space-y-5 pb-8 max-w-3xl mx-auto realtime-update">

    <!-- Page Header -->
    <div>
        <h1 class="text-xl sm:text-2xl font-bold text-gray-900 tracking-tight">X-ray Status</h1>
        <p class="text-xs sm:text-sm text-gray-500 mt-1">Track your latest examination in real time.</p>
    </div>

    <?php if (isset($_GET['registered'])): ?>
        <!-- Success banner shown immediately after registration -->
        <div class="rounded-xl bg-green-50 border border-green-200 p-4 flex items-start gap-3">
            <i data-lucide="check-circle-2" class="w-5 h-5 text-green-600 shrink-0 mt-0.5"></i>
            <div>
                <p class="text-sm font-bold text-green-800">Registration Submitted!</p>
                <p class="text-sm text-green-700 mt-0.5">Your registration has been sent for RadTech approval. You can track
                    your case status below.</p>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!$caseRow): ?>
        <div class="rounded-2xl bg-white border border-gray-100 shadow-sm p-6 sm:p-10 text-center">
            <div class="mx-auto h-16 w-16 rounded-full bg-gray-100 flex items-center justify-center mb-4">
                <i data-lucide="inbox" class="w-8 h-8 text-gray-400"></i>
            </div>
            <h3 class="text-lg font-semibold text-gray-700 mb-2">No Case Found</h3>
            <p class="text-sm text-gray-500 mb-5">You don't have any X-ray case linked to your account yet.</p>
            <a href="?role=patient&page=registration"
                class="inline-flex items-center gap-2 rounded-xl bg-red-600 hover:bg-red-700 text-white font-semibold text-sm py-3 px-5 transition">
                <i data-lucide="plus-circle" class="w-4 h-4"></i> Register for X-ray
            </a>
        </div>
    <?php elseif ($caseRow): ?>
        
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
                <a href="/<?= PROJECT_DIR ?>/index.php?role=patient&page=view-report&ref=<?= base64_encode('CitiLife_Case_' . $caseRow['id']) ?>"
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
            <div class="px-5 py-4 border-b border-gray-100">
                <h2 class="font-bold text-gray-900">Case Information</h2>
            </div>
            <div class="p-4 sm:p-5 grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                <div class="space-y-3">
                    <div class="flex items-start gap-3">
                        <div class="h-8 w-8 rounded-lg bg-red-50 flex items-center justify-center shrink-0">
                            <i data-lucide="hash" class="w-4 h-4 text-red-500"></i>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Case Number</p>
                            <p class="text-sm font-semibold text-red-600 font-mono">
                                <?= htmlspecialchars($caseRow['case_number']) ?></p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <div class="h-8 w-8 rounded-lg bg-red-50 flex items-center justify-center shrink-0">
                            <i data-lucide="map-pin" class="w-4 h-4 text-red-500"></i>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Branch</p>
                            <p class="text-sm font-semibold text-gray-800">
                                <?= htmlspecialchars($caseRow['branch_name'] ?? $caseRow['branch'] ?? '—') ?></p>
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
                                <?= htmlspecialchars($caseRow['exam_type'] ?? '—') ?></p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <div class="h-8 w-8 rounded-lg bg-red-50 flex items-center justify-center shrink-0">
                            <i data-lucide="calendar" class="w-4 h-4 text-red-500"></i>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Date</p>
                            <p class="text-sm font-semibold text-gray-800">
                                <?= htmlspecialchars(date('F j, Y', strtotime($caseRow['created_at']))) ?></p>
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
                                            <span id="rad-activity-dot" data-case-id="<?= $caseRow['id'] ?>" class="absolute -top-0.5 -right-0.5 sm:-top-1 sm:-right-1 w-2.5 h-2.5 sm:w-3.5 sm:h-3.5 border-2 border-white rounded-full bg-gray-400 z-20 transition-colors"></span>
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
                <div class="mt-4 sm:mt-5 rounded-xl p-4 sm:p-5 border status-summary-box" style="background: <?= $sInfo['bg'] ?>; border-color: <?= $sInfo['border'] ?>">
                    <p class="text-sm font-semibold" style="color: <?= $sInfo['text'] ?>">
                        Current Status: <strong><?= htmlspecialchars($sInfo['label']) ?></strong>
                    </p>
                    <?php if ($sDesc): ?>
                        <p class="text-xs sm:text-sm mt-1 sm:mt-1.5 leading-relaxed" style="color: <?= $sInfo['text'] ?>; opacity: 0.85"><?= $sDesc ?></p>
                    <?php endif; ?>
                </div>

            </div>
        </div>

        <?php if (in_array($caseRow['status'], ['Released', 'Completed'])): ?>
        <!-- New Request CTA -->
        <div class="rounded-2xl bg-white border border-gray-100 shadow-sm p-6 text-center mt-5">
            <div class="mx-auto h-14 w-14 rounded-full bg-red-50 flex items-center justify-center mb-3">
                <i data-lucide="plus-circle" class="w-7 h-7 text-red-500"></i>
            </div>
            <h3 class="text-base font-bold text-gray-800 mb-1">Want to register a new X-ray request?</h3>
            <p class="text-sm text-gray-500 mb-4">You can submit a new X-ray examination request anytime.</p>
            <a href="?role=patient&page=registration"
                class="inline-flex items-center gap-2 rounded-xl bg-red-600 hover:bg-red-700 text-white font-bold text-sm py-3 px-6 transition shadow-sm">
                <i data-lucide="plus-circle" class="w-4 h-4"></i> New X-ray Request
            </a>
        </div>
        <?php endif; ?>

    <?php endif; ?>
</div>

<script>
    // Stealth Mode: Immediately clean the URL bar to hide all parameters from users
    if (window.history && window.history.replaceState) {
        window.history.replaceState(null, null, window.location.pathname);
    }
</script>
