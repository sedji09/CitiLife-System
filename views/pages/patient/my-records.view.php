<?php
require_once __DIR__ . '/../../../config/database.php';

$caseModel = new \CaseModel($pdo);
$patientModel = new \PatientModel($pdo);
$branchModel = new \BranchModel($pdo);

require_once __DIR__ . '/../../../app/Models/FeedbackModel.php';
$feedbackModel = new \FeedbackModel($pdo);



$userId = $_SESSION['user_id'] ?? 0;
$completedCases = [];
$rejectedCases = [];
$cancelledCases = [];
$feedbackCaseIds = [];

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

// 2. Fetch Cases & Disputes (Backend logic)
$patientDisputes = [];
if ($patientRow && isset($patientRow['patient_number'])) {
    require_once __DIR__ . '/../../../app/Models/ResultDisputeModel.php';
    $disputeModel = new \ResultDisputeModel($pdo);
    $patientDisputes = $disputeModel->getDisputesByPatient($patientId);

    $rawCases = $caseModel->getPatientHistory($patientRow['patient_number']);
    foreach ($rawCases as $c) {
        $isRejected = (isset($c['approval_status']) && $c['approval_status'] === 'Rejected') || (isset($c['status']) && $c['status'] === 'Rejected');
        $isCancelled = (isset($c['status']) && $c['status'] === 'Cancelled');
        if ($isRejected) {
            $rejectedCases[] = $c;
        } elseif ($isCancelled) {
            $cancelledCases[] = $c;
        } elseif (in_array($c['status'], ['Completed', 'Released'])) {
            $completedCases[] = $c;
        }
    }
    $feedbackCaseIds = $feedbackModel->getPatientFeedbackCaseIds($patientId);
    $disputedCaseIds = array_column($patientDisputes, 'case_id');
    $disputesByCaseId = [];
    foreach ($patientDisputes as $disp) {
        $cid = (int) $disp['case_id'];
        if (!isset($disputesByCaseId[$cid])) {
            $disputesByCaseId[$cid] = $disp;
        }
    }

    $stmtReq = $pdo->prepare("SELECT r.id, r.request_number AS case_number, r.exam_type, r.created_at, r.status, b.name AS branch_name, 
                                     b.contact_number_1 AS branch_contact, b.contact_number_2 AS branch_contact_2, b.contact_number_3 AS branch_contact_3
                              FROM requests r 
                              LEFT JOIN branches b ON r.branch_id = b.id 
                              WHERE r.patient_id = ? AND r.status IN ('Rejected', 'Cancelled')");
    $stmtReq->execute([$patientId]);
    $inactiveRequests = $stmtReq->fetchAll(PDO::FETCH_ASSOC);

    foreach ($inactiveRequests as $req) {
        $req['is_request_only'] = true;
        if ($req['status'] === 'Rejected') {
            $req['approval_status'] = 'Rejected';
            $rejectedCases[] = $req;
        } elseif ($req['status'] === 'Cancelled') {
            $cancelledCases[] = $req;
        }
    }

    $sortByDateDesc = function ($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    };
    usort($completedCases, $sortByDateDesc);
    usort($rejectedCases, $sortByDateDesc);
    usort($cancelledCases, $sortByDateDesc);
}

$statusBadge = [
    'Pending' => ['bg' => 'bg-orange-50', 'text' => 'text-orange-700', 'border' => 'border-orange-400', 'label' => 'Pending'],
    'Pending Approval' => ['bg' => 'bg-orange-50', 'text' => 'text-orange-700', 'border' => 'border-orange-400', 'label' => 'Pending'],
    'Pending Payment' => ['bg' => 'bg-amber-50', 'text' => 'text-amber-700', 'border' => 'border-amber-400', 'label' => 'Pending Payment'],
    'Payment Verifying' => ['bg' => 'bg-blue-50', 'text' => 'text-blue-700', 'border' => 'border-blue-400', 'label' => 'Payment Verifying'],
    'Payment Verified' => ['bg' => 'bg-emerald-50', 'text' => 'text-emerald-700', 'border' => 'border-emerald-400', 'label' => 'Payment Verified'],
    'Approved' => ['bg' => 'bg-blue-50', 'text' => 'text-blue-700', 'border' => 'border-blue-400', 'label' => 'Approved'],
    'X-ray Taken' => ['bg' => 'bg-blue-50', 'text' => 'text-blue-700', 'border' => 'border-blue-400', 'label' => 'X-ray Taken'],
    'Under Reading' => ['bg' => 'bg-blue-50', 'text' => 'text-blue-700', 'border' => 'border-blue-400', 'label' => 'Under Reading'],
    'Report Ready' => ['bg' => 'bg-indigo-50', 'text' => 'text-indigo-700', 'border' => 'border-indigo-400', 'label' => 'Report Ready'],
    'Released' => ['bg' => 'bg-green-50', 'text' => 'text-green-700', 'border' => 'border-green-400', 'label' => 'Released'],
    'Completed' => ['bg' => 'bg-green-50', 'text' => 'text-green-700', 'border' => 'border-green-400', 'label' => 'Completed'],
    'Edited' => ['bg' => 'bg-green-50', 'text' => 'text-green-700', 'border' => 'border-green-400', 'label' => 'Edited'],
    'Rejected' => ['bg' => 'bg-red-50', 'text' => 'text-red-700', 'border' => 'border-red-400', 'label' => 'Rejected'],
    'Cancelled' => ['bg' => 'bg-red-50', 'text' => 'text-red-700', 'border' => 'border-red-400', 'label' => 'Cancelled'],
];
?>

<style>
    /* Custom scrollbar for premium feel */
    .custom-scrollbar::-webkit-scrollbar {
        width: 6px;
        height: 6px;
    }

    .custom-scrollbar::-webkit-scrollbar-track {
        background: transparent;
    }

    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background: #cbd5e1;
    }

    /* Medyo dark na grey hover for Laptop/Desktop */
    .record-row {
        transition: all 0.2s ease;
    }

    body.theme-dark .record-row:hover {
        background-color: #2d3748 !important;
        /* Slightly lighter than background */
    }
</style>

<div class="space-y-4 sm:space-y-5 pb-8 max-w-5xl mx-auto">

    <!-- Header & Navigation Tabs -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-xl sm:text-2xl font-semibold text-gray-900 tracking-tight">My Records & Reports</h1>
            <p class="text-xs sm:text-sm text-gray-500 mt-1">View your completed and rejected X-ray examination records,
                and track the status of your error reports.</p>
        </div>
    </div>

    <!-- Patient Navigation Tabs -->
    <div class="border-b border-gray-200">
        <nav class="flex overflow-x-auto flex-nowrap custom-scrollbar">
            <button type="button" id="tab-patient-completed-btn" onclick="switchPatientTab('completed')"
                class="whitespace-nowrap py-3 px-5 text-sm font-bold border-b-2 border-red-600 text-red-600 transition text-center">
                Completed Records
            </button>
            <button type="button" id="tab-patient-rejected-btn" onclick="switchPatientTab('rejected')"
                class="whitespace-nowrap py-3 px-5 text-sm font-semibold border-b-2 border-transparent text-gray-500 hover:text-gray-700 transition text-center">
                Rejected Records
            </button>
            <button type="button" id="tab-patient-cancelled-btn" onclick="switchPatientTab('cancelled')"
                class="whitespace-nowrap py-3 px-5 text-sm font-semibold border-b-2 border-transparent text-gray-500 hover:text-gray-700 transition text-center">
                Cancelled Requests
            </button>
            <button type="button" id="tab-patient-disputes-btn" onclick="switchPatientTab('disputes')"
                class="whitespace-nowrap py-3 px-5 text-sm font-semibold border-b-2 border-transparent text-gray-500 hover:text-gray-700 transition text-center">
                Error Reports
            </button>
        </nav>
    </div>

    <!-- Records -->
    <div id="my-records-wrapper">
        <!-- COMPLETED RECORDS TAB -->
        <div id="tab-completed-content">
            <?php if (empty($completedCases)): ?>
                <div class="rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden p-6 sm:p-10 text-center">
                    <div class="mx-auto h-16 w-16 rounded-full bg-gray-100 flex items-center justify-center mb-4">
                        <i data-lucide="file-x" class="w-8 h-8 text-gray-400"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-700 mb-2">No Completed Records</h3>
                    <p class="text-sm text-gray-500 mb-5">Your completed X-ray examination history will appear here.</p>
                    <a href="<?= PROJECT_DIR ? '/' . PROJECT_DIR . '/' : '/' ?>registration"
                        class="inline-flex items-center gap-2 rounded-xl bg-red-600 hover:bg-red-700 text-white font-semibold text-sm py-3 px-5 transition">
                        <i data-lucide="plus-circle" class="w-4 h-4"></i> Register for X-ray
                    </a>
                </div>
            <?php else: ?>
                <!-- Search & Filters -->
                <div class="mb-4 sm:mb-6 flex flex-col md:flex-row gap-2 sm:gap-3 md:items-center">
                    <div class="relative flex-1">
                        <input type="text" id="completed-search-input"
                            placeholder="Search records (Request / Case # or Exam)..."
                            class="w-full rounded-lg sm:rounded-xl border border-gray-200 bg-white pl-9 sm:pl-10 pr-3 sm:pr-4 py-2 sm:py-2.5 text-xs sm:text-sm text-gray-900 outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-all shadow-sm">
                        <div class="absolute inset-y-0 left-0 pl-3 sm:pl-3.5 flex items-center pointer-events-none">
                            <i data-lucide="search" class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-gray-400"></i>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-2 md:flex md:flex-nowrap md:gap-3">
                        <select id="completed-branch-filter"
                            class="w-full md:w-auto min-w-[140px] rounded-lg sm:rounded-xl border border-gray-200 bg-white px-3 sm:px-4 py-2 sm:py-2.5 text-xs sm:text-sm outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-all shadow-sm">
                            <option>All Branches</option>
                            <?php foreach ($branchModel->getAllBranches() as $b): ?>
                                <option><?= htmlspecialchars($b['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select id="completed-sort-date"
                            class="w-full md:w-auto min-w-[140px] rounded-lg sm:rounded-xl border border-gray-200 bg-white px-3 sm:px-4 py-2 sm:py-2.5 text-xs sm:text-sm outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-all shadow-sm">
                            <option>Newest Case</option>
                            <option>Oldest Case</option>
                        </select>
                    </div>
                </div>

                <!-- Info Banner -->
                <div class="mb-4 bg-blue-50 border border-blue-100 rounded-xl p-3 flex items-start gap-3">
                    <i data-lucide="info" class="w-5 h-5 text-blue-500 mt-0.5 flex-shrink-0"></i>
                    <p class="text-xs sm:text-sm text-blue-800">
                        <strong>Note:</strong> You can only request corrections for your X-ray records within <strong>30
                            days</strong> from the examination date. The "Request Correction" option is automatically
                        disabled for older records.
                    </p>
                </div>

                <!-- Completed Cards Container -->
                <div id="completed-cards-container" class="space-y-4">
                    <?php foreach ($completedCases as $c): ?>
                        <?php
                        $caseDispute = $disputesByCaseId[$c['id']] ?? null;
                        $isAmendedCase = (!empty($c['is_amended']) && (int) $c['is_amended'] === 1) || ($caseDispute && $caseDispute['status'] === 'Resolved');
                        $displayStatus = $isAmendedCase ? 'Edited' : $c['status'];
                        $badge = $statusBadge[$displayStatus] ?? ['bg' => 'bg-gray-50', 'text' => 'text-gray-600', 'border' => 'border-gray-200', 'label' => $displayStatus];
                        $branchName = $c['branch_name'] ?? $c['branch'] ?? '—';
                        ?>
                        <div class="completed-card bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm hover:shadow-md transition-shadow"
                            data-id="<?= htmlspecialchars($c['case_number']) ?>" data-case-id="<?= $c['id'] ?>"
                            data-exam="<?= htmlspecialchars($c['exam_type'] ?? '') ?>"
                            data-branch="<?= htmlspecialchars($branchName) ?>"
                            data-date="<?= htmlspecialchars($c['created_at']) ?>">

                            <!-- Card Header -->
                            <div class="px-4 py-3 bg-gray-50 border-b border-gray-100 flex justify-between items-center">
                                <div class="flex items-center gap-2">
                                    <span
                                        class="px-2 py-0.5 bg-red-600 text-white text-[10px] font-bold rounded-sm uppercase tracking-wide">Case
                                        No.</span>
                                    <span
                                        class="font-mono text-sm font-bold text-gray-800"><?= htmlspecialchars($c['case_number']) ?></span>
                                </div>
                                <span
                                    class="inline-flex items-center rounded-full border px-2.5 py-1 text-[11px] font-semibold <?= $badge['bg'] ?> <?= $badge['text'] ?> <?= $badge['border'] ?>">
                                    <?= htmlspecialchars($badge['label']) ?>
                                </span>
                            </div>

                            <!-- Card Body -->
                            <div class="p-4 sm:p-5 flex gap-4 sm:gap-6 items-start">
                                <div
                                    class="w-16 h-16 sm:w-20 sm:h-20 bg-gray-100 rounded-lg flex-shrink-0 flex items-center justify-center border border-gray-200">
                                    <i data-lucide="activity" class="w-8 h-8 text-gray-400"></i>
                                </div>
                                <div class="flex-1">
                                    <h4 class="text-base sm:text-lg font-bold text-gray-900 leading-tight mb-1">
                                        <?= htmlspecialchars($c['exam_type'] ?? '—') ?>
                                    </h4>
                                    <div
                                        class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-4 text-xs sm:text-sm text-gray-500">
                                        <span class="flex items-center gap-1.5">
                                            <i data-lucide="calendar" class="w-4 h-4"></i>
                                            <?= htmlspecialchars(date('M j, Y - h:i A', strtotime($c['created_at']))) ?>
                                        </span>
                                        <span class="flex items-center gap-1.5">
                                            <i data-lucide="map-pin" class="w-4 h-4"></i> <?= htmlspecialchars($branchName) ?>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Card Footer -->
                            <div
                                class="px-3 sm:px-4 py-2.5 sm:py-3 bg-gray-50 border-t border-gray-100 flex items-center justify-end gap-1.5 sm:gap-3">
                                <?php
                                $isExpired30Days = strtotime($c['created_at']) < strtotime('-30 days');
                                if (!in_array($c['id'], $disputedCaseIds) && !$isExpired30Days && !$isAmendedCase):
                                    ?>
                                    <button type="button"
                                        onclick="openDisputeModal(<?= $c['id'] ?>, <?= htmlspecialchars(json_encode($c['case_number']), ENT_QUOTES, 'UTF-8') ?>, <?= htmlspecialchars(json_encode($c['exam_type'] ?? 'General Exam'), ENT_QUOTES, 'UTF-8') ?>)"
                                        class="inline-flex items-center justify-center px-2.5 sm:px-4 py-2 sm:py-2.5 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 text-xs sm:text-sm font-semibold rounded-xl transition-all shadow-sm active:scale-95 whitespace-nowrap">
                                        Request Correction
                                    </button>
                                <?php elseif (in_array($c['id'], $disputedCaseIds) && !$isAmendedCase): ?>
                                    <span
                                        class="inline-flex items-center justify-center px-2.5 sm:px-4 py-2 sm:py-2.5 bg-orange-50 border border-orange-200 text-orange-600 text-xs sm:text-sm font-semibold rounded-xl whitespace-nowrap">
                                        Correction Requested
                                    </span>
                                <?php endif; ?>

                                <?php if (!in_array($c['id'], $feedbackCaseIds)): ?>
                                    <button type="button"
                                        onclick="openFeedbackModal(<?= $c['id'] ?>, <?= htmlspecialchars(json_encode($c['case_number']), ENT_QUOTES, 'UTF-8') ?>, <?= htmlspecialchars(json_encode($c['exam_type'] ?? 'General Exam'), ENT_QUOTES, 'UTF-8') ?>)"
                                        class="inline-flex items-center justify-center px-2.5 sm:px-4 py-2 sm:py-2.5 bg-white border border-yellow-400 hover:bg-yellow-500 hover:text-white text-yellow-600 text-xs sm:text-sm font-semibold rounded-xl transition-all shadow-sm active:scale-95 whitespace-nowrap">
                                        Rate
                                    </button>
                                <?php endif; ?>

                                <?php
                                $isExpired = strtotime($c['created_at']) < strtotime('-3 months');
                                $contacts = array_filter([$c['branch_contact'] ?? '', $c['branch_contact_2'] ?? '', $c['branch_contact_3'] ?? '']);
                                $reportUrl = $isExpired ? 'javascript:void(0)' : (PROJECT_DIR ? '/' . PROJECT_DIR . '/' : '/') . 'view-report?ref=' . base64_encode('Citilife_Case_' . $c['id']);
                                $onClickAttr = $isExpired ? 'onclick="showExpiredAlert(event, ' . htmlspecialchars(json_encode(array_values($contacts)), ENT_QUOTES, 'UTF-8') . ')"' : '';
                                ?>
                                <a href="<?= $reportUrl ?>" <?= $onClickAttr ?>
                                    class="inline-flex items-center justify-center gap-1.5 px-3 sm:px-4 py-2 sm:py-2.5 bg-green-600 hover:bg-green-700 text-white text-xs sm:text-sm font-bold rounded-xl transition-all shadow-sm active:scale-95 whitespace-nowrap">
                                    <i data-lucide="file-text" class="w-4 h-4"></i>
                                    View Report
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <div id="completed-pagination"
                    class="mt-6 flex flex-col sm:flex-row items-center justify-between bg-white p-4 rounded-xl border border-gray-100 shadow-sm gap-4">
                    <span id="completed-count-info"
                        class="text-xs sm:text-sm text-gray-500 font-medium order-2 sm:order-1"></span>
                    <div class="flex items-center gap-2 order-1 sm:order-2 w-full sm:w-auto justify-between sm:justify-end">
                        <button id="completed-prev-btn"
                            class="p-2 rounded-lg bg-white border border-gray-200 text-gray-600 hover:bg-gray-50 transition-colors disabled:opacity-40 disabled:cursor-not-allowed">
                            <i data-lucide="chevron-left" class="w-5 h-5"></i>
                        </button>
                        <span id="completed-page-info"
                            class="text-sm font-bold text-gray-700 min-w-[80px] text-center"></span>
                        <button id="completed-next-btn"
                            class="p-2 rounded-lg bg-white border border-gray-200 text-gray-600 hover:bg-gray-50 transition-colors disabled:opacity-40 disabled:cursor-not-allowed">
                            <i data-lucide="chevron-right" class="w-5 h-5"></i>
                        </button>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- REJECTED RECORDS TAB -->
        <div id="tab-rejected-content" class="hidden">
            <?php if (empty($rejectedCases)): ?>
                <div class="rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden p-6 sm:p-10 text-center">
                    <div class="mx-auto h-16 w-16 rounded-full bg-gray-100 flex items-center justify-center mb-4">
                        <i data-lucide="check-circle" class="w-8 h-8 text-gray-400"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-700 mb-2">No Rejected Records</h3>
                    <p class="text-sm text-gray-500">You do not have any rejected records.</p>
                </div>
            <?php else: ?>
                <!-- Search & Filters -->
                <div class="mb-4 sm:mb-6 flex flex-col md:flex-row gap-2 sm:gap-3 md:items-center">
                    <div class="relative flex-1">
                        <input type="text" id="rejected-search-input" placeholder="Search rejected records..."
                            class="w-full rounded-lg sm:rounded-xl border border-gray-200 bg-white pl-9 sm:pl-10 pr-3 sm:pr-4 py-2 sm:py-2.5 text-xs sm:text-sm text-gray-900 outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-all shadow-sm">
                        <div class="absolute inset-y-0 left-0 pl-3 sm:pl-3.5 flex items-center pointer-events-none">
                            <i data-lucide="search" class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-gray-400"></i>
                        </div>
                    </div>
                </div>

                <div id="rejected-cards-container" class="space-y-4">
                    <?php foreach ($rejectedCases as $c): ?>
                        <?php
                        $branchName = $c['branch_name'] ?? $c['branch'] ?? '—';
                        $contacts = array_filter([$c['branch_contact'] ?? '', $c['branch_contact_2'] ?? '', $c['branch_contact_3'] ?? '']);
                        ?>
                        <div class="rejected-card bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm hover:shadow-md transition-shadow"
                            data-id="<?= htmlspecialchars($c['case_number']) ?>" data-case-id="<?= $c['id'] ?>"
                            data-exam="<?= htmlspecialchars($c['exam_type'] ?? '') ?>">

                            <div
                                class="px-4 py-3 bg-gray-50 dark:bg-gray-900/60 border-b border-gray-100 dark:border-gray-700/80 flex justify-between items-center">
                                <div class="flex items-center gap-2">
                                    <span
                                        class="px-2 py-0.5 bg-red-600 text-white text-[10px] font-bold rounded-sm uppercase tracking-wide">Request
                                        No.</span>
                                    <span
                                        class="font-mono text-sm font-bold text-gray-800 dark:text-gray-100"><?= htmlspecialchars($c['case_number']) ?></span>
                                </div>
                                <span
                                    class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-[11px] font-semibold bg-red-50 dark:bg-red-950/40 text-red-600 dark:text-red-400 border-red-200 dark:border-red-800/40 uppercase tracking-wider">Rejected</span>
                            </div>

                            <div class="p-4 sm:p-5 flex gap-4 sm:gap-6 items-start">
                                <div
                                    class="w-16 h-16 sm:w-20 sm:h-20 bg-gray-100 rounded-lg flex-shrink-0 flex items-center justify-center border border-gray-200">
                                    <i data-lucide="x-octagon" class="w-8 h-8 text-red-400"></i>
                                </div>
                                <div class="flex-1">
                                    <h4 class="text-base sm:text-lg font-bold text-gray-900 leading-tight mb-1">
                                        <?= htmlspecialchars($c['exam_type'] ?? '—') ?>
                                    </h4>
                                    <div
                                        class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-4 text-xs sm:text-sm text-gray-500">
                                        <span class="flex items-center gap-1.5">
                                            <i data-lucide="calendar" class="w-4 h-4"></i>
                                            <?= htmlspecialchars(date('M j, Y - h:i A', strtotime($c['created_at']))) ?>
                                        </span>
                                        <span class="flex items-center gap-1.5">
                                            <i data-lucide="map-pin" class="w-4 h-4"></i> <?= htmlspecialchars($branchName) ?>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div
                                class="px-3 sm:px-4 py-2.5 sm:py-3 bg-gray-50 border-t border-gray-100 flex flex-wrap justify-end items-center gap-2 sm:gap-3">
                                <button type="button"
                                    onclick='showContactOptions(<?= htmlspecialchars(json_encode(array_values($contacts)), ENT_QUOTES, 'UTF-8') ?>)'
                                    class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-white border border-gray-300 hover:bg-gray-50 hover:border-gray-400 text-gray-500 hover:text-gray-700 text-xs sm:text-sm font-medium rounded-xl transition-all shadow-sm active:scale-95 whitespace-nowrap">
                                    <i data-lucide="phone" class="w-4 h-4 text-gray-500 shrink-0"></i> <span>Contact
                                        Clinic</span>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <div id="rejected-pagination"
                    class="mt-6 flex flex-col sm:flex-row items-center justify-between bg-white p-4 rounded-xl border border-gray-100 shadow-sm gap-4">
                    <span id="rejected-count-info"
                        class="text-xs sm:text-sm text-gray-500 font-medium order-2 sm:order-1"></span>
                    <div class="flex items-center gap-2 order-1 sm:order-2 w-full sm:w-auto justify-between sm:justify-end">
                        <button id="rejected-prev-btn"
                            class="p-2 rounded-lg bg-white border border-gray-200 text-gray-600 hover:bg-gray-50 transition-colors disabled:opacity-40 disabled:cursor-not-allowed">
                            <i data-lucide="chevron-left" class="w-5 h-5"></i>
                        </button>
                        <span id="rejected-page-info"
                            class="text-sm font-bold text-gray-700 min-w-[80px] text-center"></span>
                        <button id="rejected-next-btn"
                            class="p-2 rounded-lg bg-white border border-gray-200 text-gray-600 hover:bg-gray-50 transition-colors disabled:opacity-40 disabled:cursor-not-allowed">
                            <i data-lucide="chevron-right" class="w-5 h-5"></i>
                        </button>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- CANCELLED REQUESTS TAB -->
        <div id="tab-cancelled-content" class="hidden">
            <?php if (empty($cancelledCases)): ?>
                <div class="rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden p-6 sm:p-10 text-center">
                    <div class="mx-auto h-16 w-16 rounded-full bg-gray-100 flex items-center justify-center mb-4">
                        <i data-lucide="check-circle" class="w-8 h-8 text-gray-400"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-700 mb-2">No Cancelled Requests</h3>
                    <p class="text-sm text-gray-500">You do not have any cancelled requests.</p>
                </div>
            <?php else: ?>
                <!-- Search & Filters -->
                <div class="mb-4 sm:mb-6 flex flex-col md:flex-row gap-2 sm:gap-3 md:items-center">
                    <div class="relative flex-1">
                        <input type="text" id="cancelled-search-input" placeholder="Search cancelled requests..."
                            class="w-full rounded-lg sm:rounded-xl border border-gray-200 bg-white pl-9 sm:pl-10 pr-3 sm:pr-4 py-2 sm:py-2.5 text-xs sm:text-sm text-gray-900 outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-all shadow-sm">
                        <div class="absolute inset-y-0 left-0 pl-3 sm:pl-3.5 flex items-center pointer-events-none">
                            <i data-lucide="search" class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-gray-400"></i>
                        </div>
                    </div>
                </div>

                <div id="cancelled-cards-container" class="space-y-4">
                    <?php foreach ($cancelledCases as $c): ?>
                        <?php
                        $branchName = $c['branch_name'] ?? $c['branch'] ?? '—';
                        $contacts = array_filter([$c['branch_contact'] ?? '', $c['branch_contact_2'] ?? '', $c['branch_contact_3'] ?? '']);
                        ?>
                        <div class="cancelled-card bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm hover:shadow-md transition-shadow"
                            data-id="<?= htmlspecialchars($c['case_number']) ?>" data-case-id="<?= $c['id'] ?>"
                            data-exam="<?= htmlspecialchars($c['exam_type'] ?? '') ?>">

                            <div
                                class="px-4 py-3 bg-gray-50 dark:bg-gray-900/60 border-b border-gray-100 dark:border-gray-700/80 flex justify-between items-center">
                                <div class="flex items-center gap-2">
                                    <span
                                        class="px-2 py-0.5 bg-red-600 text-white text-[10px] font-bold rounded-sm uppercase tracking-wide">Request
                                        No.</span>
                                    <span
                                        class="font-mono text-sm font-bold text-gray-800 dark:text-gray-100"><?= htmlspecialchars($c['case_number']) ?></span>
                                </div>
                                <span
                                    class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-[11px] font-semibold bg-gray-100 dark:bg-gray-700/60 text-gray-600 dark:text-gray-300 border-gray-200 dark:border-gray-600 uppercase tracking-wider">Cancelled</span>
                            </div>

                            <div class="p-4 sm:p-5 flex gap-4 sm:gap-6 items-start">
                                <div
                                    class="w-16 h-16 sm:w-20 sm:h-20 bg-gray-100 rounded-lg flex-shrink-0 flex items-center justify-center border border-gray-200">
                                    <i data-lucide="ban" class="w-8 h-8 text-red-400"></i>
                                </div>
                                <div class="flex-1">
                                    <h4 class="text-base sm:text-lg font-bold text-gray-900 leading-tight mb-1">
                                        <?= htmlspecialchars($c['exam_type'] ?? '—') ?>
                                    </h4>
                                    <div
                                        class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-4 text-xs sm:text-sm text-gray-500">
                                        <span class="flex items-center gap-1.5">
                                            <i data-lucide="calendar" class="w-4 h-4"></i>
                                            <?= htmlspecialchars(date('M j, Y - h:i A', strtotime($c['created_at']))) ?>
                                        </span>
                                        <span class="flex items-center gap-1.5">
                                            <i data-lucide="map-pin" class="w-4 h-4"></i> <?= htmlspecialchars($branchName) ?>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <?php if (!empty($contacts)): ?>
                                <div
                                    class="px-3 sm:px-4 py-2.5 sm:py-3 bg-gray-50 border-t border-gray-100 flex flex-wrap justify-end items-center gap-2 sm:gap-3">
                                    <button type="button"
                                        onclick='showContactOptions(<?= htmlspecialchars(json_encode(array_values($contacts)), ENT_QUOTES, 'UTF-8') ?>)'
                                        class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-white border border-gray-300 hover:bg-gray-50 hover:border-gray-400 text-gray-500 hover:text-gray-700 text-xs sm:text-sm font-medium rounded-xl transition-all shadow-sm active:scale-95 whitespace-nowrap">
                                        <i data-lucide="phone" class="w-4 h-4 text-gray-500 shrink-0"></i> <span>Contact
                                            Clinic</span>
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <div id="cancelled-pagination"
                    class="mt-6 flex flex-col sm:flex-row items-center justify-between bg-white p-4 rounded-xl border border-gray-100 shadow-sm gap-4">
                    <span id="cancelled-count-info"
                        class="text-xs sm:text-sm text-gray-500 font-medium order-2 sm:order-1"></span>
                    <div class="flex items-center gap-2 order-1 sm:order-2 w-full sm:w-auto justify-between sm:justify-end">
                        <button id="cancelled-prev-btn"
                            class="p-2 rounded-lg bg-white border border-gray-200 text-gray-600 hover:bg-gray-50 transition-colors disabled:opacity-40 disabled:cursor-not-allowed">
                            <i data-lucide="chevron-left" class="w-5 h-5"></i>
                        </button>
                        <span id="cancelled-page-info"
                            class="text-sm font-bold text-gray-700 min-w-[80px] text-center"></span>
                        <button id="cancelled-next-btn"
                            class="p-2 rounded-lg bg-white border border-gray-200 text-gray-600 hover:bg-gray-50 transition-colors disabled:opacity-40 disabled:cursor-not-allowed">
                            <i data-lucide="chevron-right" class="w-5 h-5"></i>
                        </button>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- ERROR REPORTS TAB -->
        <div id="tab-disputes-content" class="hidden">

            <?php if (empty($patientDisputes)): ?>
                <div class="rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden p-6 sm:p-10 text-center">
                    <div class="mx-auto h-16 w-16 rounded-full bg-gray-100 flex items-center justify-center mb-4">
                        <i data-lucide="check-circle" class="w-8 h-8 text-gray-400"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-700 mb-2">No Error Reports</h3>
                    <p class="text-sm text-gray-500">You have no submitted error reports or disputes.</p>
                </div>
            <?php else: ?>
                <div id="disputes-cards-container" class="space-y-4">
                    <?php foreach ($patientDisputes as $disp): ?>
                        <?php
                        $statusBadgeMap = [
                            'Issue Reported' => 'bg-amber-50 text-amber-700 border-amber-300',
                            'Pending RadTech Review' => 'bg-amber-50 text-amber-700 border-amber-300',
                            'For RadTech Review' => 'bg-amber-50 text-amber-700 border-amber-300',
                            'Correction in Progress' => 'bg-indigo-50 text-indigo-700 border-indigo-300',
                            'Correction Completed' => 'bg-blue-50 text-blue-700 border-blue-300',
                            'Pending RadTech Verification' => 'bg-blue-50 text-blue-700 border-blue-300',
                            'Resolved' => 'bg-green-50 text-green-700 border-green-300',
                            'Rejected' => 'bg-red-50 text-red-700 border-red-300',
                        ];
                        $bCls = $statusBadgeMap[$disp['status']] ?? 'bg-gray-50 text-gray-600 border-gray-200';

                        $catMap = [
                            'demographic_error' => 'Wrong Patient Info',
                            'exam_details_error' => 'Wrong Body Part / Exam',
                            'findings_error' => 'Typographical Error in Report',
                            'both_error' => 'Wrong Patient Info & Typographical Error',
                            'other' => 'Other Concern',
                            'other_error' => 'Other Concern'
                        ];
                        ?>
                        <div id="dispute-card-<?= $disp['id'] ?>" data-id="<?= $disp['id'] ?>"
                            class="dispute-card bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm hover:shadow-md transition-shadow">
                            <div class="px-4 py-3 bg-gray-50 border-b border-gray-100 flex justify-between items-center">
                                <div class="flex items-center gap-2">
                                    <span
                                        class="px-2 py-0.5 bg-red-600 text-white text-[10px] font-bold rounded-sm uppercase tracking-wide">Case
                                        No.</span>
                                    <span
                                        class="font-mono text-sm font-bold text-gray-800"><?= htmlspecialchars($disp['case_number']) ?></span>
                                </div>
                                <span
                                    class="inline-flex items-center rounded-full border px-2.5 py-1 text-[11px] font-bold <?= $bCls ?>">
                                    <?php
                                    $dispStatus = $disp['status'];
                                    if ($dispStatus === 'Escalated to Radiologist') {
                                        $dispStatus = 'Correction in Progress';
                                    }
                                    ?>
                                    <?= htmlspecialchars($dispStatus) ?>
                                </span>
                            </div>

                            <div class="p-4 sm:p-5 flex gap-4 sm:gap-6 items-start">
                                <div
                                    class="w-12 h-12 sm:w-16 sm:h-16 bg-red-50 rounded-lg flex-shrink-0 flex items-center justify-center border border-red-100">
                                    <i data-lucide="alert-triangle" class="w-6 h-6 text-red-500"></i>
                                </div>
                                <div class="flex-1">
                                    <h4 class="text-sm font-bold text-gray-900 mb-1">
                                        <span class="text-gray-500 font-medium mr-2">Category:</span>
                                        <?= htmlspecialchars($catMap[$disp['dispute_category']] ?? ucfirst($disp['dispute_category'])) ?>
                                    </h4>
                                    <div class="text-sm text-gray-700 mb-3">
                                        <span class="text-gray-500 font-medium mr-1 block mb-1">Details of Correction:</span>
                                        <div class="font-medium text-gray-800 bg-gray-50 p-3 rounded-lg border border-gray-100">
                                            <?= nl2br(htmlspecialchars(trim($disp['description']))) ?></div>
                                    </div>
                                    <div class="flex flex-col sm:flex-row gap-1 sm:gap-4 text-xs text-gray-500">
                                        <span class="flex items-center gap-1.5">
                                            <i data-lucide="calendar" class="w-3.5 h-3.5"></i> Submitted:
                                            <?= date('M j, Y h:i A', strtotime($disp['created_at'])) ?>
                                        </span>
                                        <span class="flex items-center gap-1.5">
                                            <i data-lucide="file-text" class="w-3.5 h-3.5"></i> Exam: <span
                                                class="font-bold text-gray-700"><?= htmlspecialchars($disp['exam_type']) ?></span>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <div id="disputes-pagination"
                    class="mt-6 flex flex-col sm:flex-row items-center justify-between bg-white p-4 rounded-xl border border-gray-100 shadow-sm gap-4">
                    <span id="disputes-count-info"
                        class="text-xs sm:text-sm text-gray-500 font-medium order-2 sm:order-1"></span>
                    <div class="flex items-center gap-2 order-1 sm:order-2 w-full sm:w-auto justify-between sm:justify-end">
                        <button id="disputes-prev-btn"
                            class="p-2 rounded-lg bg-white border border-gray-200 text-gray-600 hover:bg-gray-50 transition-colors disabled:opacity-40 disabled:cursor-not-allowed">
                            <i data-lucide="chevron-left" class="w-5 h-5"></i>
                        </button>
                        <span id="disputes-page-info"
                            class="text-sm font-bold text-gray-700 min-w-[80px] text-center"></span>
                        <button id="disputes-next-btn"
                            class="p-2 rounded-lg bg-white border border-gray-200 text-gray-600 hover:bg-gray-50 transition-colors disabled:opacity-40 disabled:cursor-not-allowed">
                            <i data-lucide="chevron-right" class="w-5 h-5"></i>
                        </button>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
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
            <p class="custom-alert-text">This result has exceeded the 3-month availability period. Please contact
                the clinic for assistance</p>
            <div class="custom-alert-buttons-container">
                <a id="expired-alert-contact-btn" href="#" class="custom-alert-btn-secondary"
                    style="text-decoration:none; display:none; justify-content:center; align-items:center;">Contact
                    Us</a>
                <button class="custom-alert-btn"
                    onclick="document.getElementById('expired-alert-modal').classList.remove('show')">Close</button>
            </div>
        </div>
    </div>

    <!-- Feedback Modal -->
    <div id="feedback-modal"
        class="hidden fixed inset-0 z-[100] flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-sm transition-opacity opacity-0">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg overflow-hidden transform scale-95 transition-transform duration-200"
            id="feedback-modal-content">
            <!-- Modal Header -->
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

            <!-- Case Info Bar -->
            <div class="bg-red-50 border-b border-red-100 px-6 py-3 flex items-center gap-2 text-sm text-red-800">
                <i data-lucide="folder-open" class="w-4 h-4 text-red-600"></i>
                <span class="font-medium">Feedback for Case: <span class="font-bold text-red-700"
                        id="feedback-case-number"></span></span>
            </div>

            <!-- Form -->
            <div class="p-6">
                <form id="feedback-form" class="space-y-6">
                    <input type="hidden" name="case_id" id="feedback-case-id" value="">

                    <!-- Star Rating -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Overall Rating <span
                                class="text-red-500">*</span></label>
                        <div class="flex items-center gap-2" id="feedback-star-container">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <button type="button"
                                    class="feedback-star-btn p-1 text-gray-300 hover:text-yellow-400 transition transform hover:scale-110"
                                    data-rating="<?= $i ?>">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24"
                                        fill="currentColor" stroke="currentColor" stroke-width="1" stroke-linecap="round"
                                        stroke-linejoin="round" class="w-8 h-8 pointer-events-none">
                                        <polygon
                                            points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" />
                                    </svg>
                                </button>
                            <?php endfor; ?>
                        </div>
                        <input type="hidden" name="rating" id="feedback-rating-input" value="" required>
                        <p class="text-xs text-gray-500 mt-2" id="feedback-rating-text">Select a rating</p>
                    </div>

                    <!-- Comments -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Additional Comments
                            (Optional)</label>
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

    <script>
        function showExpiredAlert(e, contacts = []) {
            if (e) {
                e.preventDefault();
                e.stopPropagation();
            }
            const modal = document.getElementById('expired-alert-modal');
            if (modal) modal.classList.add('show');
        }
    </script>

    <div id="dispute-modal"
        class="hidden fixed inset-0 z-[9999] flex items-center justify-center p-4 sm:p-6 bg-gray-900/60 backdrop-blur-sm transition-opacity opacity-0">
        <div class="bg-white rounded-3xl shadow-2xl w-full max-w-lg overflow-hidden transform scale-95 transition-transform duration-200 z-[10000] flex flex-col max-h-full"
            id="dispute-modal-content">
            <!-- Modal Header -->
            <div
                class="px-6 py-5 border-b border-gray-100 bg-red-50/50 flex flex-shrink-0 items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="p-2.5 bg-red-100 text-red-600 rounded-xl">
                        <i data-lucide="alert-triangle" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h2 class="font-bold text-gray-900 text-base">Report an Error / Dispute Result</h2>
                        <p class="text-xs text-gray-500">Notice an issue with your report or info? Report it to the
                            clinic.</p>
                    </div>
                </div>
                <button type="button" onclick="closeDisputeModal()"
                    class="text-gray-400 hover:text-gray-600 transition p-2 rounded-lg hover:bg-gray-100">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <!-- Case Info Bar -->
            <div
                class="bg-gray-50 border-b border-gray-100 px-6 py-3 flex flex-shrink-0 items-center justify-between text-xs text-gray-700">
                <div>Case #: <span class="font-bold font-mono text-red-600" id="dispute-case-number"></span></div>
                <div class="font-medium text-gray-500" id="dispute-exam-type"></div>
            </div>

            <!-- Form -->
            <form id="dispute-form" onsubmit="submitDisputeForm(event)"
                class="flex flex-col flex-1 min-h-0 overflow-hidden">
                <!-- Scrollable Body -->
                <div class="p-6 space-y-4 overflow-y-auto custom-scrollbar flex-1 min-h-0">
                    <input type="hidden" name="case_id" id="dispute-case-id">
                    <input type="hidden" name="action" value="submit_dispute">

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">What type of error is this? <span
                                class="text-red-500">*</span></label>
                        <select name="dispute_category" id="dispute-category" onchange="toggleDisputeFields()"
                            class="w-full rounded-xl border border-gray-300 pl-2 pr-3.5 py-2.5 text-xs text-gray-900 outline-none focus:ring-2 focus:ring-red-500 bg-white">
                            <option value="" disabled selected>-- Select Category --</option>
                            <option value="demographic_error">1. Wrong Patient Info (Incorrect Name, Age, or Sex)
                            </option>
                            <option value="findings_error">2. Typographical Error in Report (Findings or Impression)
                            </option>
                            <option value="template_error">3. Rename X-ray Template / Body Part (e.g. Left or Right)
                            </option>
                            <option value="both_error">4. Both (Wrong Patient Info &amp; Typographical Error)</option>
                            <option value="both_template_error">5. Both (Wrong Patient Info &amp; Rename X-ray Template)
                            </option>
                            <option value="other">6. Others (Please specify error/concern)</option>
                        </select>
                    </div>

                    <!-- Dynamic Patient Info Correction Options -->
                    <div id="demographic-options-container"
                        class="hidden p-4 bg-gray-50 border border-gray-200 rounded-xl space-y-3">
                        <label class="block text-xs font-bold text-gray-700">Select what needs correction: <span
                                class="text-red-500">*</span></label>

                        <div class="grid grid-cols-2 gap-2 text-xs text-gray-700">
                            <label
                                class="flex items-center gap-2 cursor-pointer bg-white p-2 rounded-lg border border-gray-200 hover:border-red-300 transition">
                                <input type="checkbox" id="chk-first-name"
                                    class="rounded text-red-600 focus:ring-red-500" onchange="toggleCorrectionInputs()">
                                <span class="font-medium">First Name</span>
                            </label>
                            <label
                                class="flex items-center gap-2 cursor-pointer bg-white p-2 rounded-lg border border-gray-200 hover:border-red-300 transition">
                                <input type="checkbox" id="chk-last-name"
                                    class="rounded text-red-600 focus:ring-red-500" onchange="toggleCorrectionInputs()">
                                <span class="font-medium">Last Name</span>
                            </label>
                            <label
                                class="flex items-center gap-2 cursor-pointer bg-white p-2 rounded-lg border border-gray-200 hover:border-red-300 transition">
                                <input type="checkbox" id="chk-age" class="rounded text-red-600 focus:ring-red-500"
                                    onchange="toggleCorrectionInputs()">
                                <span class="font-medium">Age</span>
                            </label>
                            <label
                                class="flex items-center gap-2 cursor-pointer bg-white p-2 rounded-lg border border-gray-200 hover:border-red-300 transition">
                                <input type="checkbox" id="chk-sex" class="rounded text-red-600 focus:ring-red-500"
                                    onchange="toggleCorrectionInputs()">
                                <span class="font-medium">Sex / Gender</span>
                            </label>
                        </div>

                        <!-- Patient Input Fields for Corrections -->
                        <div id="correction-inputs-container"
                            class="mt-3 space-y-2.5 hidden pt-2 border-t border-gray-200/70">
                            <p class="text-[11px] font-semibold text-gray-500">Enter your correct details below:</p>

                            <!-- First Name Input -->
                            <div id="field-correct-first-name" class="hidden">
                                <label class="block text-[11px] font-bold text-gray-700 mb-1">
                                    Correct First Name <span class="text-red-500">*</span>
                                </label>
                                <input type="text" id="input-correct-first-name" placeholder="Enter correct First Name"
                                    class="w-full rounded-xl border border-gray-300 px-3 py-2 text-xs text-gray-900 outline-none focus:ring-2 focus:ring-red-500 bg-white">
                            </div>

                            <!-- Last Name Input -->
                            <div id="field-correct-last-name" class="hidden">
                                <label class="block text-[11px] font-bold text-gray-700 mb-1">
                                    Correct Last Name <span class="text-red-500">*</span>
                                </label>
                                <input type="text" id="input-correct-last-name" placeholder="Enter correct Last Name"
                                    class="w-full rounded-xl border border-gray-300 px-3 py-2 text-xs text-gray-900 outline-none focus:ring-2 focus:ring-red-500 bg-white">
                            </div>

                            <!-- Age Input (Calendar) -->
                            <div id="field-correct-age" class="hidden">
                                <label class="block text-[11px] font-bold text-gray-700 mb-1">
                                    Correct Age <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <input type="text" id="input-correct-birthdate" readonly
                                        placeholder="Select birthdate"
                                        class="w-full rounded-xl border border-gray-300 pl-9 pr-3 py-2 text-xs text-gray-900 outline-none focus:ring-2 focus:ring-red-500 bg-white cursor-pointer transition">
                                    <i data-lucide="calendar"
                                        class="w-4 h-4 text-gray-400 absolute left-3 top-2.5 pointer-events-none"></i>
                                </div>
                                <input type="hidden" id="input-correct-age">
                                <div id="preview-calculated-age"
                                    class="hidden mt-1.5 text-[11px] text-gray-600 flex items-center gap-1.5 font-medium bg-gray-100/90 px-2.5 py-1 rounded-lg border border-gray-200">
                                    <span class="text-gray-500">Age:</span>
                                    <span id="val-calculated-age" class="font-bold text-red-600"></span>
                                </div>
                            </div>

                            <!-- Sex / Gender Input -->
                            <div id="field-correct-sex" class="hidden">
                                <label class="block text-[11px] font-bold text-gray-700 mb-1">
                                    Correct Sex / Gender <span class="text-red-500">*</span>
                                </label>
                                <select id="input-correct-sex"
                                    class="w-full rounded-xl border border-gray-300 px-3 py-2 text-xs text-gray-900 outline-none focus:ring-2 focus:ring-red-500 bg-white">
                                    <option value="" disabled selected>-- Select Correct Sex / Gender --</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Dynamic X-ray Template Rename Options Container -->
                    <div id="template-rename-options-container"
                        class="hidden p-4 sm:p-5 bg-gray-50 border border-gray-200 rounded-2xl space-y-3.5">
                        <div class="flex flex-wrap items-center justify-between gap-3 pb-1">
                            <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider">
                                X-RAY TEMPLATE / EXAM NAME <span class="text-red-500">*</span>
                            </label>
                            <div class="flex items-center gap-2">
                                <span class="text-[11px] text-gray-400 font-medium">Quick Side:</span>
                                <button type="button" onclick="setTemplateSide('Left')"
                                    class="px-3 py-1 text-xs font-semibold bg-white hover:bg-gray-100 hover:text-red-600 active:scale-95 text-gray-700 rounded-lg border border-gray-300 transition shadow-2xs cursor-pointer">Left</button>
                                <button type="button" onclick="setTemplateSide('Right')"
                                    class="px-3 py-1 text-xs font-semibold bg-white hover:bg-gray-100 hover:text-red-600 active:scale-95 text-gray-700 rounded-lg border border-gray-300 transition shadow-2xs cursor-pointer">Right</button>
                                <button type="button" onclick="setTemplateSide('Bilateral')"
                                    class="px-3 py-1 text-xs font-semibold bg-white hover:bg-gray-100 hover:text-red-600 active:scale-95 text-gray-700 rounded-lg border border-gray-300 transition shadow-2xs cursor-pointer">Bilateral</button>
                            </div>
                        </div>

                        <div class="pt-0.5">
                            <input type="text" id="input-correct-template" placeholder="e.g. Left Knee AP / Lateral"
                                class="w-full text-sm font-semibold px-4 py-2.5 rounded-xl border border-gray-300 bg-white focus:border-red-500 focus:ring-2 focus:ring-red-100 outline-none transition shadow-2xs">
                        </div>

                        <div class="text-xs text-gray-500 flex items-center gap-2.5 pt-1">
                            <span class="text-gray-400 font-medium">Current record:</span>
                            <span
                                class="font-semibold text-gray-800 bg-white px-3 py-1 rounded-lg border border-gray-200 shadow-2xs"
                                id="dispute-current-exam-text">Foot</span>
                        </div>
                    </div>

                    <!-- Description Textarea Container -->
                    <div id="general-description-container" class="hidden">
                        <label id="dispute-description-label" class="block text-xs font-bold text-gray-700 mb-1">Provide
                            details of the correction needed <span class="text-red-500">*</span></label>
                        <textarea name="description" id="dispute-description" rows="3"
                            class="w-full rounded-xl border border-gray-300 p-3 text-xs text-gray-900 outline-none focus:ring-2 focus:ring-red-500"
                            placeholder="Please describe what is incorrect or needs correction..."></textarea>
                    </div>
                </div>

                <!-- Fixed Footer -->
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex flex-shrink-0 justify-end gap-2">
                    <button type="button" onclick="closeDisputeModal()"
                        class="px-4 py-2.5 text-xs font-semibold text-gray-600 hover:bg-gray-100 rounded-xl transition">
                        Cancel
                    </button>
                    <button type="submit" id="dispute-submit-btn"
                        class="px-5 py-2.5 text-xs font-bold text-white bg-red-600 hover:bg-red-700 rounded-xl shadow transition flex items-center gap-1.5">
                        <i data-lucide="send" class="w-4 h-4"></i> Submit Error Report
                    </button>
                </div>
            </form>
        </div>
    </div>
    <script>
        // Move modals to body to prevent stacking context constraints from the dashboard layout
        document.addEventListener('DOMContentLoaded', () => {
            const modals = ['dispute-modal', 'feedback-modal', 'expired-alert-modal'];
            modals.forEach(id => {
                const el = document.getElementById(id);
                if (el) {
                    document.body.appendChild(el);
                }
            });
        });
    </script>
</div>