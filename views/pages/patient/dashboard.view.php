<?php
require_once __DIR__ . '/../../../config/database.php';


$userModel = new \UserModel($pdo);
$patientModel = new \PatientModel($pdo);
$caseModel = new \CaseModel($pdo);

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
$userAccountStatus = $userPatientRow['user_status'] ?? 'Pending';
$patientId = $patientRow['id'] ?? null;

// 3. Get Patient Latest Case
$latestCase = null;
if ($patientId) {
    $latestCase = $caseModel->getLatestCaseByPatient($patientId);
}

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
    1 => 'Registration',
    2 => 'Payment',
    3 => 'RadTech Verification',
    4 => 'X-ray Examination',
    5 => 'Radiologist Reading',
    6 => 'Report Finalized',
    7 => 'Released',
];

$statusColors = [
    'Pending' => ['bg' => '#FFF7ED', 'border' => '#FED7AA', 'text' => '#C2410C', 'label' => 'Pending Review'],
    'Pending Payment' => ['bg' => '#FFFBEB', 'border' => '#FDE68A', 'text' => '#D97706', 'label' => 'Pending Payment'],
    'Payment Verifying' => ['bg' => '#EFF6FF', 'border' => '#BFDBFE', 'text' => '#1D4ED8', 'label' => 'Payment Verifying'],
    'Payment Verified' => ['bg' => '#ECFDF5', 'border' => '#A7F3D0', 'text' => '#059669', 'label' => 'Payment Verified'],
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
    'Pending' => 'Your request is under review.',
    'Pending Payment' => 'Please proceed to payment.',
    'Payment Verifying' => 'Payment proof submitted. Awaiting verification.',
    'Payment Verified' => 'Payment verified. Awaiting final approval.',
    'Approved' => 'Request approved. Proceed to X-ray.',
    'X-ray Taken' => 'Images captured.',
    'Under Reading' => 'Under radiologist review.',
    'Report Ready' => 'Your X-ray report is ready. Please visit the clinic to get your results.',
    'Released' => 'Report released.',
    'Completed' => 'Your X-ray examination has been completed and the report has been released.',
    'Rejected' => 'Your request has been rejected. Please contact the clinic for more details or submit a new request.',
    'Cancelled' => 'Your request has been cancelled.',
];

// --- QUEUE MANAGEMENT LOGIC ---
$showQueueBoard = false;
$patientQueueNumber = '--';
$peopleAhead = 0;
$statAhead = 0;

if ($latestCase && isset($latestCase['record_type']) && $latestCase['record_type'] === 'Case') {
    // Show queue board ONLY if the case is active AND has been submitted to the Radiologist (Uploaded)
    if (in_array($latestCase['status'], ['Pending', 'Under Reading']) && (isset($latestCase['image_status']) && $latestCase['image_status'] === 'Uploaded')) {
        $showQueueBoard = true;

        $branchId = $latestCase['branch_id'];
        $caseDate = date('Y-m-d', strtotime($latestCase['created_at']));

        // 1. Patient's Queue Number for the day (Global across all branches)
        $stmtNum = $pdo->prepare("SELECT COUNT(*) + 1 FROM cases WHERE DATE(created_at) = ? AND id < ?");
        $stmtNum->execute([$caseDate, $latestCase['id']]);
        $patientQueueNumber = str_pad($stmtNum->fetchColumn(), 2, '0', STR_PAD_LEFT);

        // 2. People Ahead Logic (from the same day, Global across all branches)
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

    // --- ESTIMATED REPORTING TIME LOGIC ---
    $caseStatus = $latestCase['status'];
    $caseImageStatus = $latestCase['image_status'] ?? '';

    if (in_array($caseStatus, ['Report Ready', 'Completed', 'Released'])) {
        $estimatedTimeDisplay = 'Completed';
    } elseif ($caseImageStatus === 'Uploaded' && in_array($caseStatus, ['Pending', 'Under Reading'])) {
        // Calculate estimated time range: 20 mins per person ahead, 1 hour window
        $baseMins = $peopleAhead * 20;
        $startMins = max(0, $baseMins);
        $endMins = $startMins + 60;

        $startTime = date('g:i A', strtotime("+$startMins minutes"));
        $endTime = date('g:i A', strtotime("+$endMins minutes"));

        $estimatedTimeDisplay = "$startTime &ndash; $endTime";
    } else {
        // Not yet submitted to radiologist
        $estimatedTimeDisplay = '&mdash;';
    }
}
// --- END QUEUE MANAGEMENT LOGIC ---
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

    /* ===== QUEUE BOARD ANIMATIONS (keyframes can't be inlined) ===== */
    .qb-live-dot {
        animation: qbLivePulse 1.5s ease-in-out infinite;
    }

    @keyframes qbLivePulse {

        0%,
        100% {
            opacity: 1;
            box-shadow: 0 0 0 0 rgba(74, 222, 128, 0.4);
        }

        50% {
            opacity: 0.6;
            box-shadow: 0 0 0 4px rgba(74, 222, 128, 0);
        }
    }

    .qb-serving-num {
        animation: qbServingGlow 2s ease-in-out infinite;
    }

    @keyframes qbServingGlow {

        0%,
        100% {
            text-shadow: 0 0 24px rgba(239, 68, 68, 0.3);
        }

        50% {
            text-shadow: 0 0 32px rgba(239, 68, 68, 0.5);
        }
    }

    .qb-stat-banner {
        animation: qbStatFlash 2s ease-in-out infinite;
    }

    @keyframes qbStatFlash {

        0%,
        100% {
            border-color: rgba(239, 68, 68, 0.25);
        }

        50% {
            border-color: rgba(239, 68, 68, 0.5);
        }
    }

    .qb-stat-dot {
        animation: qbLivePulse 1s ease-in-out infinite;
    }
</style>

<div class="pb-8 max-w-3xl mx-auto realtime-update" id="patient-dashboard-main-container">

    <!-- Welcome banner & Queue System -->
    <div class="mb-5"
        style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 16px;">
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
                    <h2 class="font-bold text-gray-900"><?= htmlspecialchars($displayName) ?></h2>
                    <p class="text-xs text-red-600 font-mono">#
                        <?= htmlspecialchars($patientRow['patient_number'] ?? 'PAT-UNKNOWN') ?>
                    </p>
                </div>
            </div>
            <?php
            $branch = $patientRow['branch_name'] ?? '—';
            if (empty($branch) || $branch === '—') {
                // fallback: try from cases
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
        <a href="/<?= PROJECT_DIR ?>/registration"
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
            <p class="text-sm text-gray-500 mb-5">Register as a patient to request X-ray examinations and track your
                results.</p>
            <a href="/<?= PROJECT_DIR ?>/registration"
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
                <!-- Horizontal Divider -->
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
        <!-- Latest X-ray Status -->
        <div id="patient-dashboard-latest-status"
            class="mb-5 rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden">
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                <h2 class="font-bold text-gray-900">Latest X-ray Status</h2>
                <span class="text-xs font-mono text-gray-500"><?= htmlspecialchars($latestCase['case_number']) ?></span>
            </div>

            <div class="p-5">
                <!-- Step Progress Bar -->
                <?php if (!$isRejected): ?>
                    <div class="flex items-start justify-between gap-0.5 sm:gap-1 overflow-x-auto pt-2.5 pb-2">
                        <?php foreach ($steps as $num => $label): ?>
                            <?php
                            $done = $num < $currentStep || ($num === count($steps) && $currentStep === count($steps));
                            $active = $num === $currentStep && $currentStep !== count($steps);
                            $pending = $num > $currentStep;
                            ?>
                            <div class="flex flex-col items-center gap-1 sm:gap-1 flex-1 min-w-[40px] sm:min-w-[48px]">
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
                                    <div class="relative z-10 mx-auto h-7 w-7 sm:h-9 sm:w-9 rounded-full flex items-center justify-center text-xs sm:text-sm font-bold border-2 transition
                                <?php if ($done): ?>bg-green-500 border-green-500 text-white
                                <?php elseif ($active): ?>bg-red-500 border-red-500 text-white ring-2 sm:ring-4 ring-red-100
                                <?php else: ?>bg-white border-gray-200 text-gray-400<?php endif; ?>">
                                        <?php if ($num === 5 && $latestCase['status'] === 'Under Reading'): ?>
                                            <span id="rad-activity-dot" data-case-id="<?= $latestCase['id'] ?>"
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

            <!-- View Full Status button -->
            <div class="px-5 pb-5">
                <?php
                if ($latestCase['status'] === 'Rejected') {
                    $statusLink = 'my-records?tab=rejected';
                } elseif ($latestCase['status'] === 'Cancelled') {
                    $statusLink = 'xray-status?tab=cancelled';
                } elseif (in_array($latestCase['status'], ['Completed', 'Released'])) {
                    $statusLink = 'case-status?case_id=' . $latestCase['id'];
                } else {
                    $statusLink = 'xray-status';
                }
                ?>
                <a href="/<?= PROJECT_DIR ?>/<?= $statusLink ?>"
                    class="flex items-center justify-center gap-2 w-full rounded-xl bg-red-600 hover:bg-red-700 text-white font-bold text-sm py-3 px-4 transition shadow-sm">
                    View Full Status <i data-lucide="arrow-right" class="w-4 h-4"></i>
                </a>
            </div>
        </div>
    <?php endif; ?>

</div>

<script>
    // Activity Status Poller (Radiologist Typing Indicator)
    (function () {
        function pollRadiologistActivity() {
            const dot = document.getElementById('rad-activity-dot');
            if (!dot) return;

            const caseId = dot.getAttribute('data-case-id');
            if (!caseId) return;

            fetch(`/<?= PROJECT_DIR ?>/app/api/case_activity.php?action=status&case_id=${caseId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const baseClass = "absolute -top-0.5 -right-0.5 sm:-top-1 sm:-right-1 w-2.5 h-2.5 sm:w-3.5 sm:h-3.5 border-2 border-white rounded-full z-20 transition-colors";
                        if (data.state === 'active') {
                            dot.className = baseClass + " bg-green-500 animate-ping";
                            dot.innerHTML = `<span class="absolute inline-flex h-full w-full rounded-full bg-green-500"></span>`;
                        } else if (data.state === 'idle') {
                            dot.className = baseClass + " bg-gray-400";
                            dot.innerHTML = '';
                        } else {
                            dot.className = baseClass + " bg-red-500";
                            dot.innerHTML = '';
                        }
                    }
                })
                .catch(err => console.error('Activity polling error:', err));
        }

        setInterval(pollRadiologistActivity, 2500);
        pollRadiologistActivity();
    })();
</script>