<?php
require_once __DIR__ . '/../../../config/database.php';

require_once __DIR__ . '/../../../models/UserModel.php';
require_once __DIR__ . '/../../../models/PatientModel.php';
require_once __DIR__ . '/../../../models/CaseModel.php';

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
    if (isset($latestCase['approval_status']) && $latestCase['approval_status'] === 'Rejected') {
        $currentStep = 0;
        $displayStatus = 'Rejected';
        $isRejected = true;
    } elseif ($latestCase['status'] === 'Pending') {
        if (isset($latestCase['approval_status']) && $latestCase['approval_status'] === 'Pending') {
            $currentStep = 2; // Step 1 Done. Step 2 Active waiting for approval.
            $displayStatus = 'Pending';
        } elseif (isset($latestCase['approval_status']) && $latestCase['approval_status'] === 'Approved') {
            if (isset($latestCase['image_status']) && $latestCase['image_status'] === 'Uploaded') {
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
    } elseif ($latestCase['status'] === 'Under Reading') {
        $currentStep = 4;
        $displayStatus = 'Under Reading';
    } elseif ($latestCase['status'] === 'Report Ready') {
        $currentStep = 5;
        $displayStatus = 'Report Ready';
    } elseif (in_array($latestCase['status'], ['Released', 'Completed'])) {
        $currentStep = 6;
        $displayStatus = $latestCase['status'];
    } elseif ($latestCase['status'] === 'Rejected') {
        $currentStep = 0;
        $displayStatus = 'Rejected';
        $isRejected = true;
    } else {
        $currentStep = 2;
        $displayStatus = $latestCase['status'] ?: 'Pending';
    }
}


$steps = [
    1 => 'Registered',
    2 => 'Approved by RadTech',
    3 => 'X-ray Taken',
    4 => 'Under Reading',
    5 => 'Ready for Release',
    6 => 'Released',
];

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
    'X-ray Taken' => 'Your X-ray images have been captured and are being prepared for reading.',
    'Under Reading' => 'Your X-ray is being reviewed. You will be notified once results are ready for release.',
    'Report Ready' => 'Your X-ray report is ready. Please visit the branch to collect your results.',
    'Released' => 'Your X-ray report has been released. You may now collect a copy at the branch.',
    'Completed' => 'Your X-ray examination has been completed and the report has been released.',
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
        color: #e2e8f0 !important;
    }

    body.theme-dark .status-summary-box strong {
        color: #fff !important;
    }
</style>

<div class="space-y-5 pb-8 max-w-3xl mx-auto">

    <!-- Welcome banner -->
    <div>
        <h1 class="text-xl sm:text-2xl font-bold text-gray-900 tracking-tight">Welcome back,
            <?= htmlspecialchars($displayName) ?>
        </h1>
        <p class="text-xs sm:text-sm text-gray-500 mt-0.5">Here's an overview of your X-ray examination status.</p>
    </div>

    <?php if ($patientRow): ?>
        <!-- Patient Profile Card -->
        <div class="rounded-2xl bg-white border border-gray-100 shadow-sm p-5">
            <div class="flex items-center gap-4 mb-4">
                <div class="h-12 w-12 rounded-full bg-red-100 flex items-center justify-center shrink-0">
                    <i data-lucide="user" class="w-6 h-6 text-red-600"></i>
                </div>
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
        <a href="?role=patient&page=registration"
            class="flex items-center justify-center gap-2 w-full rounded-xl bg-red-600 hover:bg-red-700 text-white font-bold text-sm py-3.5 px-4 transition shadow-sm">
            <i data-lucide="plus-circle" class="w-5 h-5"></i> New X-ray Request
        </a>
    <?php else: ?>
        <!-- No patient linked – show registration CTA -->
        <div class="rounded-2xl bg-white border border-gray-100 shadow-sm p-8 text-center">
            <div class="mx-auto h-16 w-16 rounded-full bg-red-100 flex items-center justify-center mb-4">
                <i data-lucide="user-plus" class="w-8 h-8 text-red-600"></i>
            </div>
            <h3 class="text-lg font-semibold text-gray-700 mb-2">Get Started</h3>
            <p class="text-sm text-gray-500 mb-5">Register as a patient to request X-ray examinations and track your
                results.</p>
            <a href="?role=patient&page=registration"
                class="inline-flex items-center gap-2 rounded-xl bg-red-600 hover:bg-red-700 text-white font-semibold text-sm py-3 px-5 transition">
                <i data-lucide="plus-circle" class="w-4 h-4"></i> Register as Patient
            </a>
        </div>
    <?php endif; ?>

    <?php if ($latestCase): ?>
        <!-- Latest X-ray Status -->
        <div id="patient-dashboard-latest-status"
            class="rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden realtime-update">
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
                                        <?php if ($num === 4 && $latestCase['status'] === 'Under Reading'): ?>
                                            <span id="rad-activity-dot" data-case-id="<?= $latestCase['id'] ?>"
                                                class="absolute -top-0.5 -right-0.5 sm:-top-1 sm:-right-1 w-2.5 h-2.5 sm:w-3 sm:h-3 border-2 border-white rounded-full bg-gray-400 z-20 transition-colors"></span>
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
                <a href="?role=patient&page=xray-status&case_id=<?= $latestCase['id'] ?>"
                    class="flex items-center justify-center gap-2 w-full rounded-xl bg-red-600 hover:bg-red-700 text-white font-bold text-sm py-3 px-4 transition shadow-sm">
                    View Full Status <i data-lucide="arrow-right" class="w-4 h-4"></i>
                </a>
            </div>
        </div>
    <?php endif; ?>

</div>