<?php
require_once __DIR__ . '/../../../config/database.php';

$userModel = new \UserModel($pdo);
$patientModel = new \PatientModel($pdo);
$caseModel = new \CaseModel($pdo);

require_once __DIR__ . '/../../../app/Models/FeedbackModel.php';
require_once __DIR__ . '/../../../app/Models/ResultDisputeModel.php';
require_once __DIR__ . '/../../../app/Models/ServiceModel.php';

$feedbackModel = new \FeedbackModel($pdo);
$disputeModel = new \ResultDisputeModel($pdo);
$serviceModel = new \ServiceModel($pdo);

$userId = $_SESSION['user_id'] ?? 0;
$sessionEmail = $_SESSION['email'] ?? '';
$sessionName = $_SESSION['name'] ?? '';

// 1. Get User Display Info (Avatar, Name, Initials)
$displayInfo = $userModel->getDisplayInfo($userId, $sessionName, $sessionEmail);
$displayName = $displayInfo['displayName'];
$userEmail = $sessionEmail; // Maintain variable name for compatibility

// 2. Get Linked Patient Record and User Status
$stmtU = $pdo->prepare("SELECT u.status AS user_status, p.*, b.name AS branch_name 
                       FROM users u 
                       LEFT JOIN patients p ON u.patient_id = p.id 
                       LEFT JOIN branches b ON p.branch_id = b.id
                       WHERE u.id = ?");
$stmtU->execute([$userId]);
$userPatientRow = $stmtU->fetch();

$patientRow = $userPatientRow; // For compatibility
$patientFullName = trim(($patientRow['first_name'] ?? '') . ' ' . ($patientRow['last_name'] ?? ''));
$userAccountStatus = $userPatientRow['user_status'] ?? 'Pending';
$patientId = $patientRow['id'] ?? null;

// 3. Get Patient Latest Case
$latestCase = null;
$feedbackCaseIds = [];
$disputedCaseIds = [];

if ($patientId) {
    $latestCase = $caseModel->getLatestCaseByPatient($patientId);
    $feedbackCaseIds = $feedbackModel->getPatientFeedbackCaseIds($patientId);
    $patientDisputes = $disputeModel->getDisputesByPatient($patientId);
    $disputedCaseIds = array_column($patientDisputes, 'case_id');
}

// 4. Fetch RadTech name if assigned
$radtechName = '';
if ($latestCase && !empty($latestCase['radtech_id'])) {
    $stmtR = $pdo->prepare("SELECT name FROM users WHERE id = ?");
    $stmtR->execute([$latestCase['radtech_id']]);
    $radtechName = $stmtR->fetchColumn() ?: '';
}

// 5. Exam Prices for Payment Modal
$examPrices = [];
foreach ($serviceModel->getActiveServices() as $srv) {
    $serviceName = $srv['exam_type'] ?? ($srv['name'] ?? '');
    if (!empty($serviceName)) {
        $examPrices[strtolower(trim($serviceName))] = [
            'price' => (float)($srv['price'] ?? 0),
            'discount' => (float)($srv['philhealth_discount'] ?? 0)
        ];
    }
}
$examPricesJson = json_encode($examPrices);

// Status step mapping
$statusSteps = [
    'Pending' => 2,
    'Approved' => 3,
    'X-ray Taken' => 4,
    'Under Reading' => 4,
    'Report Ready' => 5,
    'Released' => 6,
    'Completed' => 6,
];

function getPatientNumber($patientRow, $allCases)
{
    if ($patientRow) {
        return $patientRow['patient_number'] ?? 'PX-' . date('Y') . '-' . str_pad($patientRow['id'], 5, '0', STR_PAD_LEFT);
    }
    return 'PX-' . date('Y') . '-00000';
}

$currentStep = 0;
$displayStatus = 'Pending';
$isRejected = ($userAccountStatus === 'Rejected');

if ($latestCase) {
    $recordType = $latestCase['record_type'] ?? 'Case';

    if ($latestCase['status'] === 'Rejected') {
        $currentStep = 0;
        $displayStatus = 'Rejected';
        $isRejected = true;
    } elseif ($latestCase['status'] === 'Cancelled') {
        $currentStep = 0;
        $displayStatus = 'Cancelled';
        $isRejected = true;
    } elseif ($recordType === 'Request' && $latestCase['status'] === 'Pending Approval') {
        $currentStep = 2;
        $displayStatus = 'Pending';
    } elseif ($recordType === 'Case' && $latestCase['status'] === 'Pending') {
        if (isset($latestCase['image_status']) && $latestCase['image_status'] === 'Uploaded') {
            $currentStep = 5;
            $displayStatus = 'X-ray Taken';
        } else {
            $currentStep = 4;
            $displayStatus = 'Approved';
        }
    } elseif ($latestCase['status'] === 'Pending Payment') {
        $currentStep = 2;
        $displayStatus = 'Pending Payment';
    } elseif ($latestCase['status'] === 'Payment Verifying') {
        $currentStep = 2;
        $displayStatus = 'Payment Verifying';
    } elseif ($latestCase['status'] === 'Payment Verified') {
        $currentStep = 3;
        $displayStatus = 'Payment Verified';
    } elseif ($latestCase['status'] === 'Under Reading') {
        $currentStep = 5;
        $displayStatus = 'Under Reading';
    } elseif ($latestCase['status'] === 'Report Ready') {
        $currentStep = 6;
        $displayStatus = 'Report Ready';
    } elseif (in_array($latestCase['status'], ['Released', 'Completed'])) {
        $currentStep = 7;
        $displayStatus = $latestCase['status'];
    } else {
        $currentStep = 2;
        $displayStatus = $latestCase['status'] ?: 'Pending';
    }
}

$steps = [
    1 => 'Registered',
    2 => 'Payment',
    3 => 'Approved by RadTech',
    4 => 'X-ray Taken',
    5 => 'Under Reading',
    6 => 'Ready for Release',
    7 => 'Released',
];

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
    'Payment Verified' => 'Your payment has been verified. Awaiting final approval.',
    'Approved' => 'Your request has been approved. Please proceed to the X-ray room for the examination.',
    'X-ray Taken' => 'Your X-ray images have been captured and are being prepared for expert reading.',
    'Under Reading' => 'Your X-ray images have been captured and sent to the Radiologist for interpretation. Once the reading is complete, it will be marked as Ready for Release.',
    'Report Ready' => 'Your X-ray report is ready. Please visit the branch to collect your results.',
    'Released' => 'Your X-ray report has been released. You can now view your report result below.',
    'Completed' => 'Your X-ray examination has been completed. You can view your report result below.',
    'Rejected' => 'Your request has been rejected. Please contact the clinic for more details or submit a new request.',
    'Cancelled' => 'You have cancelled this request.',
];

// --- QUEUE MANAGEMENT LOGIC ---
$showQueueBoard = false;
$patientQueueNumber = '--';
$peopleAhead = 0;
$statAhead = 0;

if ($latestCase && isset($latestCase['record_type']) && $latestCase['record_type'] === 'Case') {
    if (in_array($latestCase['status'], ['Pending', 'Under Reading']) && (isset($latestCase['image_status']) && $latestCase['image_status'] === 'Uploaded')) {
        $showQueueBoard = true;

        $branchId = $latestCase['branch_id'];
        $caseDate = date('Y-m-d', strtotime($latestCase['created_at']));

        $stmtNum = $pdo->prepare("SELECT COUNT(*) + 1 FROM cases WHERE DATE(created_at) = ? AND id < ?");
        $stmtNum->execute([$caseDate, $latestCase['id']]);
        $patientQueueNumber = str_pad($stmtNum->fetchColumn(), 2, '0', STR_PAD_LEFT);

        $stmtQueue = $pdo->prepare("
            SELECT id, priority, status
            FROM cases
            WHERE image_status = 'Uploaded'
            AND status IN ('Under Reading', 'Pending')
            AND DATE(created_at) = ?
            ORDER BY 
                CASE WHEN status = 'Under Reading' THEN 1 ELSE 2 END,
                CASE WHEN priority = 'STAT' THEN 1 ELSE 2 END, 
                created_at ASC 
        ");
        $stmtQueue->execute([$caseDate]);
        $queueList = $stmtQueue->fetchAll();

        foreach ($queueList as $qCase) {
            if ($qCase['id'] == $latestCase['id']) {
                break;
            }
            $peopleAhead++;
            if ($qCase['priority'] === 'STAT') {
                $statAhead++;
            }
        }
    }

    $caseStatus = $latestCase['status'];
    $caseImageStatus = $latestCase['image_status'] ?? '';

    if (in_array($caseStatus, ['Report Ready', 'Completed', 'Released'])) {
        $estimatedTimeDisplay = 'Completed';
    } elseif ($caseImageStatus === 'Uploaded' && in_array($caseStatus, ['Pending', 'Under Reading'])) {
        $baseMins = $peopleAhead * 20;
        $startMins = max(0, $baseMins);
        $endMins = $startMins + 60;

        $startTime = date('g:i A', strtotime("+$startMins minutes"));
        $endTime = date('g:i A', strtotime("+$endMins minutes"));

        $estimatedTimeDisplay = "$startTime &ndash; $endTime";
    } else {
        $estimatedTimeDisplay = '&mdash;';
    }
}
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

    /* Custom Alert Overlay */
    .custom-alert-overlay {
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, 0.6);
        backdrop-filter: blur(4px);
        z-index: 9999;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 16px;
        opacity: 0;
        visibility: hidden;
        transition: all 0.2s ease-in-out;
    }

    .custom-alert-overlay.show {
        opacity: 1;
        visibility: visible;
    }

    .custom-alert-box {
        background: #ffffff;
        border-radius: 20px;
        max-width: 400px;
        width: 100%;
        padding: 24px;
        text-align: center;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
        transform: scale(0.95);
        transition: transform 0.2s ease-in-out;
    }

    .custom-alert-overlay.show .custom-alert-box {
        transform: scale(1);
    }

    .custom-alert-icon-container {
        width: 56px;
        height: 56px;
        border-radius: 50%;
        background: #FEF2F2;
        color: #DC2626;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 16px auto;
    }

    .custom-alert-title {
        font-size: 18px;
        font-weight: 700;
        color: #1E293B;
        margin-bottom: 8px;
    }

    .custom-alert-text {
        font-size: 13px;
        color: #64748B;
        line-height: 1.5;
        margin-bottom: 20px;
    }

    .custom-alert-buttons-container {
        display: flex;
        gap: 8px;
    }

    .custom-alert-btn {
        flex: 1;
        padding: 10px 16px;
        border-radius: 12px;
        font-size: 14px;
        font-weight: 600;
        background: #DC2626;
        color: #ffffff;
        border: none;
        cursor: pointer;
        transition: background 0.15s ease;
    }

    .custom-alert-btn:hover {
        background: #B91C1C;
    }

    .custom-alert-btn-secondary {
        flex: 1;
        padding: 10px 16px;
        border-radius: 12px;
        font-size: 14px;
        font-weight: 600;
        background: #F1F5F9;
        color: #475569;
        border: none;
        cursor: pointer;
        transition: background 0.15s ease;
    }

    .custom-alert-btn-secondary:hover {
        background: #E2E8F0;
    }

    /* ===== QUEUE BOARD ANIMATIONS ===== */
    .qb-live-dot {
        animation: qbLivePulse 1.5s ease-in-out infinite;
    }

    @keyframes qbLivePulse {
        0%, 100% {
            opacity: 1;
            box-shadow: 0 0 0 0 rgba(74, 222, 128, 0.4);
        }
        50% {
            opacity: 0.6;
            box-shadow: 0 0 0 4px rgba(74, 222, 128, 0);
        }
    }
</style>

<div class="pb-8 max-w-3xl mx-auto realtime-update" id="patient-dashboard-main-container">

    <!-- Success banner if newly registered/requested -->
    <?php if (isset($_GET['registered']) && $_GET['registered'] == 1 && !isset($_GET['ajax_polling'])): ?>
        <div id="success-banner" class="mb-5 rounded-2xl bg-emerald-50 border border-emerald-200 p-4 flex items-center gap-3 transition-all duration-500 shadow-sm">
            <div class="h-10 w-10 rounded-xl bg-emerald-100 flex items-center justify-center shrink-0 text-emerald-600">
                <i data-lucide="check-circle-2" class="w-6 h-6"></i>
            </div>
            <div class="flex-1">
                <h4 class="text-sm font-bold text-emerald-900">Request Submitted Successfully!</h4>
                <p class="text-xs text-emerald-700 mt-0.5">Your X-ray request has been received. Please check your case details below.</p>
            </div>
            <button type="button" onclick="dismissSuccessBanner()" class="text-emerald-500 hover:text-emerald-700 p-1 rounded-lg transition-colors">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
        <script>
            (function() {
                // Check if already dismissed in this session
                if (sessionStorage.getItem('registered_banner_dismissed') === '1') {
                    const b = document.getElementById('success-banner');
                    if (b) b.remove();
                    return;
                }

                // Remove ?registered=1 from URL bar and __APP__.currentPath so AJAX polling doesn't keep requesting it
                if (window.history && window.history.replaceState) {
                    const cleanUrl = new URL(window.location.href);
                    cleanUrl.searchParams.delete('registered');
                    window.history.replaceState(null, '', cleanUrl.toString());
                }
                if (window.__APP__ && window.__APP__.currentPath) {
                    try {
                        const u = new URL(window.__APP__.currentPath, window.location.origin);
                        u.searchParams.delete('registered');
                        window.__APP__.currentPath = u.pathname + u.search;
                    } catch (e) {}
                }

                window.dismissSuccessBanner = function() {
                    sessionStorage.setItem('registered_banner_dismissed', '1');
                    const banner = document.getElementById('success-banner');
                    if (banner) {
                        banner.style.opacity = '0';
                        banner.style.transform = 'translateY(-10px)';
                        setTimeout(function () { banner.remove(); }, 500);
                    }
                };

                // Auto dismiss after 5 seconds
                setTimeout(window.dismissSuccessBanner, 5000);
            })();
        </script>
    <?php endif; ?>

    <!-- Welcome banner -->
    <div class="mb-5" style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 16px;">
        <div>
            <h1 class="text-xl sm:text-2xl font-semibold text-gray-900 tracking-tight">Welcome back,
                <?= htmlspecialchars($displayName) ?>
            </h1>
            <p class="text-xs sm:text-sm text-gray-500 mt-0.5">Here's an overview of your X-ray examination status.</p>
        </div>
    </div>

    <?php if ($patientRow): ?>
        <!-- Patient Profile Card -->
        <div class="mb-5 rounded-2xl bg-white border border-gray-100 shadow-sm p-5">
            <div class="flex items-center gap-4 mb-4">
                <?php if (!empty($displayInfo['avatar'])): ?>
                    <img src="<?= htmlspecialchars($displayInfo['avatar']) ?>" alt="Profile"
                        class="h-12 w-12 rounded-full object-cover shrink-0 border border-red-100 shadow-sm">
                <?php else: ?>
                    <div class="h-12 w-12 rounded-full bg-red-100 flex items-center justify-center shrink-0">
                        <i data-lucide="user" class="w-6 h-6 text-red-600"></i>
                    </div>
                <?php endif; ?>
                <div>
                    <h2 class="font-bold text-gray-900"><?= htmlspecialchars($patientFullName ?: $displayName) ?></h2>
                    <p class="text-xs text-red-600 font-mono">#
                        <?= htmlspecialchars($patientRow['patient_number'] ?? 'PAT-UNKNOWN') ?>
                    </p>
                </div>
            </div>
            <?php
            $branch = $patientRow['branch_name'] ?? '—';
            if (empty($branch) || $branch === '—') {
                if ($latestCase) {
                    $branch = $latestCase['branch_name'] ?? ($latestCase['branch'] ?? '—');
                }
            }
            $branch = $branch ?: '—';
            ?>
            <div class="flex flex-wrap gap-x-6 gap-y-2 text-sm text-gray-600">
                <div class="flex items-center gap-1.5">
                    <i data-lucide="map-pin" class="w-4 h-4 text-red-400 shrink-0"></i>
                    <?= htmlspecialchars($branch) ?>
                </div>
                <?php if (!empty($patientRow['contact_number'])): ?>
                    <div class="flex items-center gap-1.5">
                        <i data-lucide="phone" class="w-4 h-4 text-red-400 shrink-0"></i>
                        <?= htmlspecialchars($patientRow['contact_number']) ?>
                    </div>
                <?php endif; ?>
                <?php if (!empty($userEmail)): ?>
                    <div class="flex items-center gap-1.5">
                        <i data-lucide="mail" class="w-4 h-4 text-red-400 shrink-0"></i>
                        <?= htmlspecialchars($userEmail) ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Request New X-ray Button -->
        <a href="<?= PROJECT_DIR ? '/' . PROJECT_DIR . '/' : '/' ?>registration"
            class="mb-5 flex items-center justify-center gap-2 w-full rounded-xl bg-red-600 hover:bg-red-700 text-white font-bold text-sm py-3.5 px-4 transition shadow-sm">
            <i data-lucide="plus-circle" class="w-5 h-5"></i> New X-ray Request
        </a>
    <?php else: ?>
        <!-- No patient linked – show registration CTA -->
        <div class="mb-5 rounded-2xl bg-white border border-gray-100 shadow-sm p-8 text-center">
            <div class="mx-auto h-16 w-16 rounded-full bg-red-100 flex items-center justify-center mb-4">
                <i data-lucide="user-plus" class="w-8 h-8 text-red-600"></i>
            </div>
            <h3 class="text-lg font-semibold text-gray-700 mb-2">Get Started</h3>
            <p class="text-sm text-gray-500 mb-5">Register as a patient to request X-ray examinations and track your results.</p>
            <a href="<?= PROJECT_DIR ? '/' . PROJECT_DIR . '/' : '/' ?>registration"
                class="inline-flex items-center gap-2 rounded-xl bg-red-600 hover:bg-red-700 text-white font-semibold text-sm py-3 px-5 transition">
                <i data-lucide="plus-circle" class="w-4 h-4"></i> Register as Patient
            </a>
        </div>
    <?php endif; ?>

    <?php if ($showQueueBoard): ?>
        <div class="mb-5 w-full" id="patient-dashboard-queue-board"
            style="background: #ffffff; border-radius: 12px; padding: 12px 16px; color: #1e293b; display: flex; flex-direction: column; gap: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.08), 0 0 0 1px rgba(0,0,0,0.06);">

            <div style="display: flex; align-items: stretch; gap: 0;">
                <!-- Your Queue # -->
                <div style="flex: 1; text-align: center; padding: 4px 10px 4px 2px; display: flex; flex-direction: column;">
                    <div
                        style="font-size: 7px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #94a3b8; margin-bottom: 2px; white-space: nowrap;">
                        Your Queue #</div>
                    <div
                        style="font-size: 24px; font-weight: 800; line-height: 1; color: #1e293b; font-variant-numeric: tabular-nums;">
                        <?= $patientQueueNumber ?></div>
                </div>

                <!-- Divider -->
                <div
                    style="width: 1px; align-self: center; height: 32px; background: linear-gradient(180deg, transparent, #e2e8f0, transparent); flex-shrink: 0;">
                </div>

                <!-- People Ahead -->
                <div
                    style="flex: 1; text-align: center; padding: 4px 2px 4px 10px; display: flex; flex-direction: column; justify-content: center;">
                    <div
                        style="font-size: 7px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #94a3b8; margin-bottom: 2px; white-space: nowrap;">
                        PEOPLE AHEAD OF YOU</div>
                    <div
                        style="font-size: 24px; font-weight: 800; line-height: 1; color: #dc2626; font-variant-numeric: tabular-nums;">
                        <?= str_pad($peopleAhead, 2, '0', STR_PAD_LEFT) ?></div>
                    <?php if ($statAhead > 0): ?>
                        <div style="margin-top: 4px;"><span
                                style="font-size: 7px; font-weight: 700; letter-spacing: 0.5px; color: #dc2626; background: #fef2f2; border: 1px solid #fecaca; padding: 2px 6px; border-radius: 4px; white-space: nowrap;">Includes
                                <?= $statAhead ?> STAT Case(s)</span></div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Estimated Reporting Time -->
            <?php if (!empty($estimatedTimeDisplay)): ?>
                <div style="height: 1px; width: 100%; background: linear-gradient(90deg, transparent, #e2e8f0, transparent);">
                </div>

                <div class="text-center" id="patient-dashboard-estimated-time">
                    <div style="font-size: 11px; font-weight: 700; color: #1e293b; margin-bottom: 2px;">Estimated Reporting Time
                    </div>
                    <div style="font-size: 16px; font-weight: 800; color: #dc2626;"><?= $estimatedTimeDisplay ?></div>
                    <?php if ($estimatedTimeDisplay !== 'Completed' && $estimatedTimeDisplay !== '&mdash;'): ?>
                        <div style="font-size: 10px; color: #64748b; font-style: italic; margin-top: 2px;">Based on current
                            Radiologist queue</div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if ($latestCase): ?>
        <?php
        $sInfo = $statusColors[$displayStatus] ?? ['bg' => '#F9FAFB', 'border' => '#E5E7EB', 'text' => '#374151', 'label' => $displayStatus];
        $sDesc = $statusDescriptions[$displayStatus] ?? '';
        $contacts = array_filter([$latestCase['branch_contact'] ?? '', $latestCase['branch_contact_2'] ?? '', $latestCase['branch_contact_3'] ?? '']);
        $caseStatusVal = $latestCase['status'];
        $canCancel = in_array($displayStatus, ['Pending', 'Pending Payment']);
        $isPendingPayment = ($caseStatusVal === 'Pending Payment');
        $isCompletedOrReleased = in_array($caseStatusVal, ['Released', 'Completed']);
        $isExpired = strtotime($latestCase['created_at']) < strtotime('-3 months');
        $isExpired30Days = strtotime($latestCase['created_at']) < strtotime('-30 days');
        ?>

        <!-- Latest X-ray Status Card -->
        <div id="patient-dashboard-latest-status"
            class="mb-5 rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden">
            <div class="flex items-center justify-between px-4 sm:px-5 py-3.5 sm:py-4 border-b border-gray-100 gap-2">
                <h2 class="font-bold text-gray-900 text-sm sm:text-base whitespace-nowrap">Latest X-ray Status</h2>
                <span class="inline-flex items-center gap-1.5 rounded-full border px-2.5 sm:px-3 py-0.5 sm:py-1 text-[11px] sm:text-xs font-semibold whitespace-nowrap shrink-0 shadow-2xs"
                    style="background: <?= $sInfo['bg'] ?>; border-color: <?= $sInfo['border'] ?>; color: <?= $sInfo['text'] ?>">
                    <span class="w-1.5 h-1.5 rounded-full shrink-0" style="background: <?= $sInfo['text'] ?>"></span>
                    <?= htmlspecialchars($displayStatus) ?>
                </span>
            </div>

            <div class="p-4 sm:p-5">
                <!-- Step Progress Bar -->
                <?php if (!$isRejected): ?>
                    <div class="flex items-start justify-between gap-0.5 sm:gap-1 w-full pt-2 pb-2">
                        <?php foreach ($steps as $num => $stepLabel): ?>
                            <?php
                            $done = $num < $currentStep || ($num === count($steps) && $currentStep === count($steps));
                            $active = $num === $currentStep && $currentStep !== count($steps);
                            $pending = $num > $currentStep;
                            ?>
                            <div class="flex flex-col items-center gap-1 sm:gap-1.5 flex-1 min-w-0 px-0.5">
                                <div class="relative flex items-center w-full">
                                    <?php
                                    $nextNum = $num + 1;
                                    $nextDone = $nextNum < $currentStep || ($nextNum === count($steps) && $currentStep === count($steps));
                                    $nextActive = $nextNum === $currentStep && $currentStep !== count($steps);
                                    ?>
                                    <?php if ($num > 1): ?>
                                        <div
                                            class="absolute left-0 right-1/2 top-1/2 -translate-y-1/2 h-0.5 <?= $done ? 'bg-green-500' : ($active ? 'bg-red-500' : 'bg-gray-200') ?>">
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($num < count($steps)): ?>
                                        <div
                                            class="absolute left-1/2 right-0 top-1/2 -translate-y-1/2 h-0.5 <?= $nextDone ? 'bg-green-500' : ($nextActive ? 'bg-red-500' : 'bg-gray-200') ?>">
                                        </div>
                                    <?php endif; ?>
                                    <div class="relative z-10 mx-auto h-7 w-7 sm:h-9 sm:w-9 rounded-full flex items-center justify-center text-xs sm:text-sm font-bold border-2 transition shrink-0
                                <?php if ($done): ?>bg-green-500 border-green-500 text-white
                                <?php elseif ($active): ?>bg-red-500 border-red-500 text-white ring-2 sm:ring-4 ring-red-100
                                <?php else: ?>bg-white border-gray-200 text-gray-400<?php endif; ?>">
                                        <?php if ($num === 5 && ($latestCase['status'] ?? '') === 'Under Reading'): ?>
                                            <span id="rad-activity-dot" data-case-id="<?= $latestCase['id'] ?>"
                                                class="absolute -top-0.5 -right-0.5 sm:-top-1 sm:-right-1 w-2.5 h-2.5 sm:w-3.5 sm:h-3.5 border-2 border-white rounded-full bg-gray-400 z-20 transition-colors"></span>
                                        <?php endif; ?>
                                        <?php if ($done): ?>
                                            <i data-lucide="check" class="w-3.5 h-3.5 sm:w-4 sm:h-4 stroke-[2.5]"></i>
                                        <?php else: ?>
                                            <?= $num ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <span
                                    class="text-center text-[8.5px] sm:text-[11px] leading-[1.15] font-medium <?= $done || $active ? 'text-gray-800 font-semibold' : 'text-gray-400' ?> w-full px-0.5 break-words">
                                    <?= htmlspecialchars($stepLabel) ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
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

                <!-- Separator Divider -->
                <hr class="border-t border-gray-200" style="margin-top: 24px; margin-bottom: 24px; border-color: #e5e7eb;">

                <!-- Case Information Details Grid -->
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
                                    <?= htmlspecialchars($latestCase['case_number']) ?>
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
                                    <?= htmlspecialchars($latestCase['branch_name'] ?? ($latestCase['branch'] ?? '—')) ?>
                                </p>
                            </div>
                        </div>

                        <!-- Radiologic Technologist (if assigned) -->
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
                                    <?= htmlspecialchars($latestCase['exam_type'] ?? '—') ?>
                                </p>
                            </div>
                        </div>

                        <!-- Branch Contact -->
                        <div class="flex items-center gap-3">
                            <div class="h-9 w-9 rounded-xl bg-red-50 flex items-center justify-center shrink-0">
                                <i data-lucide="phone-call" class="w-4 h-4 text-red-500"></i>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Branch Contact</p>
                                <?php if (!empty($contacts)): ?>
                                    <button type="button"
                                        onclick='showContactOptions(<?= htmlspecialchars(json_encode(array_values($contacts)), ENT_QUOTES, 'UTF-8') ?>)'
                                        class="mt-0.5 inline-flex items-center justify-center px-2.5 py-1 bg-white hover:bg-gray-50 border border-gray-300 text-gray-700 hover:text-red-600 text-xs font-semibold rounded-lg transition shadow-2xs active:scale-95">
                                        View Numbers
                                    </button>
                                <?php else: ?>
                                    <p class="text-sm font-semibold text-gray-400">&mdash;</p>
                                <?php endif; ?>
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
                                    <?= htmlspecialchars(date('F j, Y', strtotime($latestCase['created_at']))) ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($canCancel || $isPendingPayment || $isCompletedOrReleased): ?>
                <!-- Footer / Action Buttons -->
                <div class="px-3 sm:px-5 py-2.5 sm:py-3.5 bg-gray-50 border-t border-gray-100 flex items-center justify-end gap-1.5 sm:gap-3">
                    <?php if ($canCancel): ?>
                        <button type="button"
                            onclick="cancelCase(<?= $latestCase['id'] ?>, '<?= htmlspecialchars($latestCase['case_number']) ?>')"
                            class="inline-flex items-center justify-center px-2.5 sm:px-4 py-2 sm:py-2.5 bg-white hover:bg-gray-50 border border-gray-300 hover:border-gray-400 text-gray-500 hover:text-gray-700 text-xs sm:text-sm font-medium rounded-xl transition-all shadow-2xs active:scale-95 whitespace-nowrap">
                            Cancel Request
                        </button>
                    <?php endif; ?>

                    <?php if ($isPendingPayment): ?>
                        <button type="button"
                            onclick="openPaymentModal(<?= $latestCase['id'] ?>, <?= $latestCase['amount_due'] ?? 0 ?>, <?= $latestCase['original_price'] ?? ($latestCase['amount_due'] ?? 0) ?>, <?= $latestCase['philhealth_discount'] ?? 0 ?>, '<?= htmlspecialchars($latestCase['gcash_qr_path'] ?? '') ?>', '<?= htmlspecialchars(addslashes($latestCase['exam_type'] ?? 'X-ray Exam')) ?>')"
                            class="inline-flex items-center justify-center px-3.5 sm:px-5 py-2 sm:py-2.5 bg-red-600 hover:bg-red-700 text-white text-xs sm:text-sm font-bold rounded-xl transition-all shadow-sm hover:shadow active:scale-95 whitespace-nowrap">
                            Pay Now
                        </button>
                    <?php endif; ?>

                    <?php if ($isCompletedOrReleased): ?>
                        <?php if (!in_array($latestCase['id'], $disputedCaseIds) && !$isExpired30Days): ?>
                            <button type="button"
                                onclick="openDisputeModal(<?= $latestCase['id'] ?>, <?= htmlspecialchars(json_encode($latestCase['case_number']), ENT_QUOTES, 'UTF-8') ?>, <?= htmlspecialchars(json_encode($latestCase['exam_type'] ?? 'General Exam'), ENT_QUOTES, 'UTF-8') ?>)"
                                class="inline-flex items-center justify-center px-2.5 sm:px-4 py-2 sm:py-2.5 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 text-xs sm:text-sm font-semibold rounded-xl transition-all shadow-sm active:scale-95 whitespace-nowrap">
                                Report an Error
                            </button>
                        <?php elseif (in_array($latestCase['id'], $disputedCaseIds)): ?>
                            <span class="inline-flex items-center justify-center px-2.5 sm:px-4 py-2 sm:py-2.5 bg-orange-50 border border-orange-200 text-orange-600 text-xs sm:text-sm font-semibold rounded-xl whitespace-nowrap">
                                Error Reported
                            </span>
                        <?php endif; ?>

                        <?php if (!in_array($latestCase['id'], $feedbackCaseIds)): ?>
                            <button type="button"
                                onclick="openFeedbackModal(<?= $latestCase['id'] ?>, <?= htmlspecialchars(json_encode($latestCase['case_number']), ENT_QUOTES, 'UTF-8') ?>, <?= htmlspecialchars(json_encode($latestCase['exam_type'] ?? 'General Exam'), ENT_QUOTES, 'UTF-8') ?>)"
                                class="inline-flex items-center justify-center px-2.5 sm:px-4 py-2 sm:py-2.5 bg-white border border-yellow-400 hover:bg-yellow-500 hover:text-white text-yellow-600 text-xs sm:text-sm font-semibold rounded-xl transition-all shadow-sm active:scale-95 whitespace-nowrap">
                                Rate
                            </button>
                        <?php endif; ?>

                        <?php
                        $onClickAttr = $isExpired ? 'onclick="showExpiredAlert(event, ' . htmlspecialchars(json_encode(array_values($contacts)), ENT_QUOTES, 'UTF-8') . ')"' : '';
                        ?>
                        <a href="<?= PROJECT_DIR ? '/' . PROJECT_DIR . '/' : '/' ?>case-status?<?= !empty($latestCase['is_request_only']) ? 'request_id=' : 'case_id=' ?><?= $latestCase['id'] ?>"
                            <?= $onClickAttr ?>
                            class="inline-flex items-center justify-center px-3 sm:px-4 py-2 sm:py-2.5 bg-green-600 hover:bg-green-700 text-white text-xs sm:text-sm font-bold rounded-xl transition-all shadow-sm active:scale-95 whitespace-nowrap">
                            View Status
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

</div>

<!-- Custom Expiry Alert Modal -->
<div class="custom-alert-overlay" id="expired-alert-modal">
    <div class="custom-alert-box">
        <div class="custom-alert-icon-container">
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20 13c0 5-3.5 7.5-7.66 9.7a1 1 0 0 1-.68 0C7.5 20.5 4 18 4 13V6a1 1 0 0 1 .76-.97l8-2a1 1 0 0 1 .48 0l8 2A1 1 0 0 1 20 6z" fill="currentColor" opacity="0.15" />
                <path d="M20 13c0 5-3.5 7.5-7.66 9.7a1 1 0 0 1-.68 0C7.5 20.5 4 18 4 13V6a1 1 0 0 1 .76-.97l8-2a1 1 0 0 1 .48 0l8 2A1 1 0 0 1 20 6z" />
                <circle cx="12" cy="11" r="3" />
                <path d="M12 14v4" />
            </svg>
        </div>
        <h3 class="custom-alert-title">Result Access Expired</h3>
        <p class="custom-alert-text">This result has exceeded the 3-month availability period. Please contact the clinic for assistance.</p>
        <div class="custom-alert-buttons-container">
            <a id="expired-alert-contact-btn" href="#" class="custom-alert-btn-secondary"
                style="text-decoration:none; display:none; justify-content:center; align-items:center;">Contact Us</a>
            <button class="custom-alert-btn"
                onclick="document.getElementById('expired-alert-modal').classList.remove('show')">Close</button>
        </div>
    </div>
</div>

<!-- Payment Modal -->
<div id="paymentModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex min-h-screen items-center justify-center p-4 text-center sm:p-0 py-8">
        <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" aria-hidden="true" onclick="closePaymentModal()"></div>
        
        <div id="paymentModalContainer" class="relative transform overflow-hidden rounded-2xl bg-white text-left align-bottom shadow-xl w-full max-w-md p-6 sm:p-8 sm:my-8 transition-none">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-gray-900">Payment Options</h3>
                <button type="button" onclick="closePaymentModal()" class="text-gray-400 hover:text-gray-600 bg-gray-50 hover:bg-gray-100 p-1.5 rounded-lg transition">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            
            <!-- Itemized Price Breakdown -->
            <div class="mb-6 rounded-2xl overflow-hidden shadow-sm border border-gray-100">
                <div class="bg-gray-50 px-5 py-3 border-b border-gray-100 flex items-center gap-2">
                    <i data-lucide="receipt" class="w-4 h-4 text-gray-500"></i>
                    <span class="text-xs font-bold text-gray-700 uppercase tracking-wider">Payment Breakdown</span>
                </div>
                <div class="p-5 bg-white space-y-3" id="paymentBreakdownContainer">
                </div>
                <div class="px-5 py-4 bg-red-50/50 border-t border-red-100 flex items-center justify-between">
                    <span class="text-sm font-bold text-black">Total Amount</span>
                    <span id="paymentAmountDisplay" class="text-sm font-extrabold text-red-600 font-mono tracking-tight">₱0.00</span>
                </div>
            </div>
            
            <form id="paymentForm" method="POST" action="<?php echo rtrim('/' . PROJECT_DIR, '/'); ?>/app/api/submit_payment.php" enctype="multipart/form-data">
                <input type="hidden" name="case_id" id="paymentCaseId" value="">
                <input type="hidden" name="amount" id="paymentAmount" value="">
                
                <div class="mb-5">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Select Payment Method</label>
                    <div class="flex flex-col sm:flex-row gap-3 sm:gap-4">
                        <label class="flex items-center gap-2 cursor-pointer p-3 border rounded-lg hover:bg-gray-50 w-full transition">
                            <input type="radio" name="payment_method" id="paymentMethodCash" value="Cash" class="form-radio text-red-600 focus:ring-red-500" onchange="togglePaymentMethod('Cash')" required>
                            <span class="font-medium text-gray-800">Cash</span>
                        </label>
                        <label id="gcashOptionWrapper" class="flex items-center gap-2 cursor-pointer p-3 border rounded-lg hover:bg-gray-50 w-full transition">
                            <input type="radio" name="payment_method" value="GCash" class="form-radio text-red-600 focus:ring-red-500" onchange="togglePaymentMethod('GCash')" required>
                            <span class="font-medium text-gray-800">Cashless (GCash)</span>
                        </label>
                    </div>
                </div>

                <!-- Cash Notice Section -->
                <div id="cashSection" class="hidden mb-5 p-5 rounded-xl bg-amber-50 border border-amber-200 text-black space-y-2 shadow-sm">
                    <div class="flex items-center gap-2 font-bold text-amber-900 text-base">
                        <i data-lucide="info" class="w-5 h-5 text-amber-600"></i> Cash Payment Reminder
                    </div>
                    <p class="text-sm leading-relaxed text-gray-800">Please pay the exact amount due at the clinic cashier or front desk before your X-ray exam.</p>
                    <p id="cashDiscountNotice" class="hidden text-xs italic text-red-600 font-medium mt-2"></p>
                </div>

                <div id="gcashSection" class="hidden mb-6">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 lg:gap-8 bg-blue-50/40 border border-blue-100 rounded-2xl p-5 lg:p-6 mb-2">
                        <!-- Left Column: QR Code Container -->
                        <div class="flex flex-col">
                            <div class="mb-4 text-[#0a2540] flex items-center gap-2">
                                <i data-lucide="smartphone" class="w-5 h-5 text-blue-600"></i>
                                <h4 class="font-bold text-base sm:text-lg">Pay via GCash or Any E-Wallet</h4>
                            </div>

                            <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 flex-1 flex flex-col justify-center items-center">
                                <img id="qrCodeImage" src="<?= PROJECT_DIR ? '/' . PROJECT_DIR . '/' : '/' ?>public/assets/images/gcash_qr.jpg" alt="GCash QR" class="w-full max-w-[220px] rounded-lg shadow-sm border border-gray-100 object-cover mb-6">
                                <a id="qrCodeDownload" href="<?= PROJECT_DIR ? '/' . PROJECT_DIR . '/' : '/' ?>public/assets/images/gcash_qr.jpg" download="Citilife_QR_Code.jpg" class="inline-flex w-full max-w-[220px] justify-center items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-4 rounded-xl transition text-sm shadow-md hover:shadow-lg mt-auto">
                                    <i data-lucide="download" class="w-4 h-4"></i> Save QR Code
                                </a>
                            </div>
                        </div>
                        
                        <!-- Right Column: Instructions & Submit -->
                        <div class="flex flex-col justify-between">
                            <div>
                                <h5 class="font-bold text-[#0a2540] text-sm mb-4 uppercase tracking-wider">Payment Instructions</h5>
                                <ol class="text-sm text-gray-700 space-y-4 mb-6 pl-0" style="list-style-type: none;">
                                    <li class="flex items-start gap-3">
                                        <span class="font-bold text-blue-600 w-5 flex-shrink-0">1</span>
                                        <span>Save the QR Code to your device using the button.</span>
                                    </li>
                                    <li class="flex items-start gap-3">
                                        <span class="font-bold text-blue-600 w-5 flex-shrink-0">2</span>
                                        <span>Open your e-wallet or mobile banking app on your device.</span>
                                    </li>
                                    <li class="flex items-start gap-3">
                                        <span class="font-bold text-blue-600 w-5 flex-shrink-0">3</span>
                                        <span>Pay via QR code.</span>
                                    </li>
                                    <li class="flex items-start gap-3">
                                        <span class="font-bold text-blue-600 w-5 flex-shrink-0">4</span>
                                        <span>Take a screenshot of the receipt and note the Reference Number.</span>
                                    </li>
                                </ol>
                            </div>

                            <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100 mt-auto">
                                <div class="mb-5 flex items-center gap-2 text-[#0a2540]">
                                    <i data-lucide="check-circle-2" class="w-5 h-5 text-green-600"></i>
                                    <h4 class="font-bold">Submit Payment Details</h4>
                                </div>
                                
                                <div class="mb-4">
                                    <label class="block text-sm text-[#0a2540] mb-2">Reference Number</label>
                                    <input type="text" name="reference_number" id="referenceNumber" class="w-full border border-gray-200 rounded-lg focus:ring-blue-500 focus:border-blue-500 text-sm py-2 sm:py-2.5 px-3" placeholder="e.g. 100023456789">
                                </div>
                                
                                <div>
                                    <label class="block text-sm text-[#0a2540] mb-2">Upload Receipt Screenshot</label>
                                    <div class="relative">
                                        <input type="file" name="payment_proof" id="paymentProof" accept="image/*" 
                                            class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" 
                                            onchange="document.getElementById('fileNameDisplay').textContent = this.files[0] ? this.files[0].name : 'No file chosen'">
                                        <div class="w-full flex items-center justify-between border border-gray-200 rounded-lg overflow-hidden bg-white">
                                            <span id="fileNameDisplay" class="px-3 py-2.5 sm:py-3 text-sm text-gray-400 truncate flex-1">No file chosen</span>
                                            <span class="bg-blue-50 text-[#0a2540] text-sm font-semibold px-4 py-2.5 sm:py-3 border-l border-gray-200 transition">Choose File</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="mt-6">
                    <button type="submit" class="appearance-none w-full flex justify-center items-center px-4 py-3 sm:py-3.5 bg-red-600 text-white text-sm sm:text-base font-bold rounded-xl hover:bg-red-700 shadow-sm transition border-none outline-none focus:ring-4 focus:ring-red-100">
                        Submit Payment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Feedback Modal -->
<div id="feedback-modal"
    class="hidden fixed inset-0 z-[100] flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-sm transition-opacity opacity-0">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg overflow-hidden transform scale-95 transition-transform duration-200"
        id="feedback-modal-content">
        <div class="px-6 py-5 border-b border-gray-100 bg-gray-50 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-red-100 text-red-600 rounded-lg">
                    <i data-lucide="message-square-plus" class="w-5 h-5"></i>
                </div>
                <div>
                    <h2 class="font-bold text-gray-900">Submit Feedback</h2>
                    <p class="text-xs text-gray-500">How was your experience with Citilife?</p>
                </div>
            </div>
            <button type="button" onclick="closeFeedbackModal()"
                class="text-gray-400 hover:text-gray-600 transition p-2 rounded-lg hover:bg-gray-100">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <div class="bg-red-50 border-b border-red-100 px-6 py-3 flex items-center gap-2 text-sm text-red-800">
            <i data-lucide="folder-open" class="w-4 h-4 text-red-600"></i>
            <span class="font-medium">Feedback for Case: <span class="font-bold text-red-700" id="feedback-case-number"></span></span>
        </div>

        <div class="p-6">
            <form id="feedback-form" class="space-y-6">
                <input type="hidden" name="case_id" id="feedback-case-id" value="">

                <!-- Star Rating -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Overall Rating <span class="text-red-500">*</span></label>
                    <div class="flex items-center gap-2" id="feedback-star-container">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <button type="button"
                                class="feedback-star-btn p-1 text-gray-300 hover:text-yellow-400 transition transform hover:scale-110"
                                data-rating="<?= $i ?>">
                                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24"
                                    fill="currentColor" stroke="currentColor" stroke-width="1" stroke-linecap="round"
                                    stroke-linejoin="round" class="w-8 h-8 pointer-events-none">
                                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" />
                                </svg>
                            </button>
                        <?php endfor; ?>
                    </div>
                    <input type="hidden" name="rating" id="feedback-rating-input" value="" required>
                    <p class="text-xs text-gray-500 mt-2" id="feedback-rating-text">Select a rating</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Additional Comments (Optional)</label>
                    <textarea name="comments" id="feedback-comments" rows="3"
                        class="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm focus:border-red-500 focus:ring-1 focus:ring-red-500 transition outline-none"
                        placeholder="Tell us more about your experience..."></textarea>
                </div>

                <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3 pt-2">
                    <button type="button" onclick="closeFeedbackModal()"
                        class="w-full sm:w-auto px-5 py-2.5 bg-gray-100 text-gray-700 font-medium rounded-xl hover:bg-gray-200 transition text-center">
                        Cancel
                    </button>
                    <button type="button" id="feedback-submit-btn" onclick="submitFeedbackForm()"
                        class="w-full sm:w-auto px-6 py-2.5 bg-red-600 text-white font-medium rounded-xl hover:bg-red-700 transition shadow-sm active:scale-[0.98] flex items-center justify-center gap-2">
                        <i data-lucide="send" class="w-4 h-4"></i>
                        <span class="whitespace-nowrap">Submit Feedback</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Dispute / Report Error Modal -->
<div id="dispute-modal"
    class="hidden fixed inset-0 z-[9999] flex items-center justify-center p-4 sm:p-6 bg-gray-900/60 backdrop-blur-sm transition-opacity opacity-0">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-lg overflow-hidden transform scale-95 transition-transform duration-200 z-[10000] flex flex-col max-h-full"
        id="dispute-modal-content">
        <div class="px-6 py-5 border-b border-gray-100 bg-red-50/50 flex flex-shrink-0 items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="p-2.5 bg-red-100 text-red-600 rounded-xl">
                    <i data-lucide="alert-triangle" class="w-5 h-5"></i>
                </div>
                <div>
                    <h2 class="font-bold text-gray-900 text-base">Report an Error / Dispute Result</h2>
                    <p class="text-xs text-gray-500">Notice an issue with your report or info? Report it to the clinic.</p>
                </div>
            </div>
            <button type="button" onclick="closeDisputeModal()"
                class="text-gray-400 hover:text-gray-600 transition p-2 rounded-lg hover:bg-gray-100">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <div class="bg-gray-50 border-b border-gray-100 px-6 py-3 flex flex-shrink-0 items-center justify-between text-xs text-gray-700">
            <div>Case #: <span class="font-bold font-mono text-red-600" id="dispute-case-number"></span></div>
            <div class="font-medium text-gray-500" id="dispute-exam-type"></div>
        </div>

        <form id="dispute-form" onsubmit="submitDisputeForm(event)" class="flex flex-col flex-1 min-h-0 overflow-hidden">
            <div class="p-6 space-y-4 overflow-y-auto flex-1 min-h-0">
                <input type="hidden" name="case_id" id="dispute-case-id">
                <input type="hidden" name="action" value="submit_dispute">

                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">What type of error is this? <span class="text-red-500">*</span></label>
                    <select name="dispute_category" id="dispute-category" onchange="toggleDisputeFields()"
                        class="w-full rounded-xl border border-gray-300 pl-2 pr-3.5 py-2.5 text-xs text-gray-900 outline-none focus:ring-2 focus:ring-red-500 bg-white">
                        <option value="" disabled selected>-- Select Category --</option>
                        <option value="demographic_error">1. Wrong Patient Info (Incorrect Name, Age, or Sex)</option>
                        <option value="findings_error">2. Wrong Body Part / Image Discrepancy (Requires Radiologist Re-examination)</option>
                        <option value="both_error">3. Both (Wrong Patient Info & Image Discrepancy)</option>
                    </select>
                </div>

                <div id="demographic-options-container" class="hidden p-4 bg-gray-50 border border-gray-200 rounded-xl space-y-3">
                    <label class="block text-xs font-bold text-gray-700">Select what needs correction: <span class="text-red-500">*</span></label>
                    <div class="grid grid-cols-2 gap-2 text-xs text-gray-700">
                        <label class="flex items-center gap-2 cursor-pointer bg-white p-2 rounded-lg border border-gray-200 hover:border-red-300 transition">
                            <input type="checkbox" id="chk-first-name" class="rounded text-red-600 focus:ring-red-500" onchange="toggleCorrectionInputs()">
                            <span class="font-medium">First Name</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer bg-white p-2 rounded-lg border border-gray-200 hover:border-red-300 transition">
                            <input type="checkbox" id="chk-last-name" class="rounded text-red-600 focus:ring-red-500" onchange="toggleCorrectionInputs()">
                            <span class="font-medium">Last Name</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer bg-white p-2 rounded-lg border border-gray-200 hover:border-red-300 transition">
                            <input type="checkbox" id="chk-age" class="rounded text-red-600 focus:ring-red-500" onchange="toggleCorrectionInputs()">
                            <span class="font-medium">Age</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer bg-white p-2 rounded-lg border border-gray-200 hover:border-red-300 transition">
                            <input type="checkbox" id="chk-sex" class="rounded text-red-600 focus:ring-red-500" onchange="toggleCorrectionInputs()">
                            <span class="font-medium">Sex</span>
                        </label>
                    </div>
                </div>

                <div id="general-description-container" class="hidden space-y-1">
                    <label class="block text-xs font-bold text-gray-700">Details of Findings Discrepancy: <span class="text-red-500">*</span></label>
                    <textarea name="findings_description" id="dispute-description" rows="3"
                        class="w-full rounded-xl border border-gray-300 p-3 text-xs text-gray-900 outline-none focus:ring-2 focus:ring-red-500"
                        placeholder="Please describe the discrepancy or issue with the radiologist's findings..."></textarea>
                </div>
            </div>

            <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex justify-end gap-3 flex-shrink-0">
                <button type="button" onclick="closeDisputeModal()"
                    class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold rounded-xl text-xs transition">
                    Cancel
                </button>
                <button type="submit" id="dispute-submit-btn"
                    class="px-5 py-2 bg-red-600 hover:bg-red-700 text-white font-bold rounded-xl text-xs transition shadow-sm flex items-center gap-1.5">
                    <i data-lucide="send" class="w-4 h-4"></i> Submit Error Report
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // Auto-hide success banner after 8 seconds
    const successBanner = document.getElementById('success-banner');
    if (successBanner) {
        setTimeout(() => {
            successBanner.style.opacity = '0';
            setTimeout(() => successBanner.remove(), 500);
        }, 8000);
    }

    // Cancel Case
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

                fetch('<?= PROJECT_DIR ? '/' . PROJECT_DIR . '/' : '/' ?>app/api/cancel_case.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ case_id: caseId })
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire('Cancelled!', 'Your request has been cancelled.', 'success').then(() => {
                                window.location.reload();
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

    // Payment Modal Logic
    const examPrices = <?= $examPricesJson ?>;

    function openPaymentModal(caseId, amount, originalPrice, philhealthDiscount, gcashQrPath, examType) {
        document.getElementById('paymentModal').classList.remove('hidden');
        document.getElementById('paymentCaseId').value = caseId;
        document.getElementById('paymentAmount').value = amount;
        
        const origPrice = parseFloat(originalPrice || amount);
        const disc = parseFloat(philhealthDiscount || 0);
        const due = parseFloat(amount || 0);

        document.getElementById('paymentAmountDisplay').innerText = '₱' + due.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        
        const breakdownContainer = document.getElementById('paymentBreakdownContainer');
        breakdownContainer.innerHTML = '';
        
        if (examType) {
            const exams = examType.split(',').map(e => e.trim());
            exams.forEach(exam => {
                const key = exam.toLowerCase();
                const examData = examPrices[key] || { price: 0, discount: 0 };
                const price = examData.price;
                
                breakdownContainer.innerHTML += `
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-500 font-medium">${exam}</span>
                        <span class="font-semibold text-gray-800 font-mono tracking-tight">₱${price.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span>
                    </div>
                `;
                
                if (disc > 0 && examData.discount > 0) {
                    breakdownContainer.innerHTML += `
                        <div class="flex items-center justify-between text-sm mt-1 mb-2">
                            <span class="text-emerald-600 font-medium">PhilHealth Discount</span>
                            <span class="font-semibold text-emerald-600 font-mono tracking-tight">-₱${examData.discount.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span>
                        </div>
                    `;
                }
            });
        } else {
            breakdownContainer.innerHTML += `
                <div class="flex items-center justify-between text-sm">
                    <span class="text-gray-500 font-medium">Regular Procedure Fee</span>
                    <span class="font-semibold text-gray-800 font-mono tracking-tight">₱${origPrice.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span>
                </div>
            `;
        }
        
        const cashDiscNotice = document.getElementById('cashDiscountNotice');
        if (disc > 0) {
            if (cashDiscNotice) {
                cashDiscNotice.classList.remove('hidden');
                cashDiscNotice.innerHTML = `Note: PhilHealth discounts have been applied. Please present your PhilHealth ID at the clinic counter.`;
            }
        } else {
            if (cashDiscNotice) cashDiscNotice.classList.add('hidden');
        }
        
        const gcashOption = document.getElementById('gcashOptionWrapper');
        if (gcashQrPath && gcashQrPath.trim() !== '') {
            if (gcashOption) gcashOption.classList.remove('hidden');
            document.getElementById('qrCodeImage').src = '<?= "/" . PROJECT_DIR . "/" ?>' + (gcashQrPath.startsWith('/') ? gcashQrPath.substring(1) : gcashQrPath);
            document.getElementById('qrCodeDownload').href = '<?= "/" . PROJECT_DIR . "/" ?>' + (gcashQrPath.startsWith('/') ? gcashQrPath.substring(1) : gcashQrPath);
        } else {
            if (gcashOption) gcashOption.classList.add('hidden');
            document.getElementById('paymentMethodCash').click();
        }
        if (window.lucide) {
            lucide.createIcons();
        }
    }
    
    function closePaymentModal() {
        document.getElementById('paymentModal').classList.add('hidden');
    }
    
    function togglePaymentMethod(method) {
        const gcashSection = document.getElementById('gcashSection');
        const cashSection = document.getElementById('cashSection');
        const proofInput = document.getElementById('paymentProof');
        const refInput = document.getElementById('referenceNumber');
        const modalContainer = document.getElementById('paymentModalContainer');
        
        if (method === 'GCash') {
            gcashSection.classList.remove('hidden');
            if (cashSection) cashSection.classList.add('hidden');
            proofInput.required = true;
            if(refInput) refInput.required = true;
            if (modalContainer) {
                modalContainer.classList.remove('max-w-md', 'sm:max-w-md');
                modalContainer.style.maxWidth = '56rem';
                modalContainer.style.transition = 'none';
            }
        } else {
            gcashSection.classList.add('hidden');
            if (cashSection) cashSection.classList.remove('hidden');
            proofInput.required = false;
            if(refInput) refInput.required = false;
            if (modalContainer) {
                modalContainer.style.maxWidth = '28rem';
            }
        }
    }

    document.addEventListener('submit', function(e) {
        if (e.target && e.target.id === 'paymentForm') {
            e.preventDefault();
            
            const form = e.target;
            const formData = new FormData(form);
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalBtnText = submitBtn.innerHTML;
            
            Swal.fire({
                title: 'Uploading...',
                text: 'Please wait while we process your payment.',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
        
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i data-lucide="loader-2" class="w-5 h-5 animate-spin"></i> Processing...';
            if (window.lucide) lucide.createIcons();

            fetch(form.action, {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) throw new Error('Network response was not ok');
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: 'Success!',
                        text: data.message,
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        document.getElementById('paymentModal').classList.add('hidden');
                        window.location.reload();
                    });
                } else {
                    Swal.fire('Error', data.message || 'An error occurred.', 'error');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnText;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire('Error', 'Failed to connect to the server. Please try again.', 'error');
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
            });
        }
    });

    // Activity Status Poller (Radiologist Typing Indicator)
    (function () {
        function pollRadiologistActivity() {
            const dot = document.getElementById('rad-activity-dot');
            if (!dot) return;

            const caseId = dot.getAttribute('data-case-id');
            if (!caseId) return;

            fetch(`<?= PROJECT_DIR ? '/' . PROJECT_DIR . '/' : '/' ?>app/api/case_activity.php?action=status&case_id=${caseId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const baseClass = "absolute -top-0.5 -right-0.5 sm:-top-1 sm:-right-1 w-2.5 h-2.5 sm:w-3.5 sm:h-3.5 border-2 border-white rounded-full z-20 transition-colors";
                        dot.innerHTML = '';
                        if (data.state === 'active') {
                            dot.className = baseClass + " bg-green-500";
                        } else if (data.state === 'idle') {
                            dot.className = baseClass + " bg-gray-400";
                        } else {
                            dot.className = baseClass + " bg-red-500";
                        }
                    }
                })
                .catch(err => console.error('Activity polling error:', err));
        }

        setInterval(pollRadiologistActivity, 2500);
        pollRadiologistActivity();
    })();
</script>