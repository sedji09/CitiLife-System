<?php
require_once __DIR__ . '/../../../config/database.php';

$caseModel = new \CaseModel($pdo);
$patientModel = new \PatientModel($pdo);
$branchModel = new \BranchModel($pdo);

require_once __DIR__ . '/../../../app/Models/FeedbackModel.php';
$feedbackModel = new \FeedbackModel($pdo);

$userId = $_SESSION['user_id'] ?? 0;
$allCases = [];
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
        if (in_array($c['status'], ['Completed', 'Released', 'Report Ready']) || $isRejected) {
            $allCases[] = $c;
        }
    }
    $feedbackCaseIds = $feedbackModel->getPatientFeedbackCaseIds($patientId);

    // Fetch rejected requests from the `requests` table
    $stmtReq = $pdo->prepare("SELECT r.id, r.request_number AS case_number, r.exam_type, r.created_at, r.status, b.name AS branch_name 
                              FROM requests r 
                              LEFT JOIN branches b ON r.branch_id = b.id 
                              WHERE r.patient_id = ? AND r.status = 'Rejected'");
    $stmtReq->execute([$patientId]);
    $rejectedRequests = $stmtReq->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rejectedRequests as $req) {
        $req['is_request_only'] = true; // flag to know it's not in cases table
        $req['approval_status'] = 'Rejected';
        $allCases[] = $req;
    }

    // Sort all cases by created_at DESC
    usort($allCases, function ($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });
}

$statusBadge = [
    'Pending' => ['bg' => 'bg-orange-50', 'text' => 'text-orange-700', 'border' => 'border-orange-400', 'label' => 'Pending'],
    'Approved' => ['bg' => 'bg-blue-50', 'text' => 'text-blue-700', 'border' => 'border-blue-400', 'label' => 'Approved'],
    'X-ray Taken' => ['bg' => 'bg-blue-50', 'text' => 'text-blue-700', 'border' => 'border-blue-400', 'label' => 'X-ray Taken'],
    'Under Reading' => ['bg' => 'bg-blue-50', 'text' => 'text-blue-700', 'border' => 'border-blue-400', 'label' => 'Under Reading'],
    'Report Ready' => ['bg' => 'bg-indigo-50', 'text' => 'text-indigo-700', 'border' => 'border-indigo-400', 'label' => 'Report Ready'],
    'Released' => ['bg' => 'bg-green-50', 'text' => 'text-green-700', 'border' => 'border-green-400', 'label' => 'Released'],
    'Completed' => ['bg' => 'bg-green-50', 'text' => 'text-green-700', 'border' => 'border-green-400', 'label' => 'Completed'],
    'Rejected' => ['bg' => 'bg-red-50', 'text' => 'text-red-700', 'border' => 'border-red-400', 'label' => 'Rejected'],
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

    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: #e2e8f0;
        border-radius: 10px;
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
            <p class="text-xs sm:text-sm text-gray-500 mt-1">Suriin ang iyong medical records at subaybayan ang status ng iyong mga error report.</p>
        </div>
    </div>

    <!-- Patient Navigation Tabs -->
    <div class="border-b border-gray-200">
        <nav class="flex space-x-6">
            <button type="button" id="tab-patient-records-btn" onclick="switchPatientTab('records')"
                    class="pb-3 px-1 text-sm font-bold border-b-2 border-red-600 text-red-600 transition flex items-center gap-2">
                <i data-lucide="folder" class="w-4 h-4"></i> My Medical Records
            </button>
            <button type="button" id="tab-patient-disputes-btn" onclick="switchPatientTab('disputes')"
                    class="pb-3 px-1 text-sm font-semibold border-b-2 border-transparent text-gray-500 hover:text-gray-700 transition flex items-center gap-2 relative">
                <i data-lucide="alert-triangle" class="w-4 h-4"></i> My Submitted Error Reports
                <?php if (!empty($patientDisputes)): ?>
                    <span class="ml-1 inline-flex items-center justify-center px-2 py-0.5 text-xs font-bold leading-none text-white bg-red-600 rounded-full">
                        <?= count($patientDisputes) ?>
                    </span>
                <?php endif; ?>
            </button>
        </nav>
    </div>

    <!-- Records -->
    <div id="my-records-wrapper">
        <?php if (empty($allCases)): ?>
            <div id="my-records-container" class="realtime-update">
                <div class="rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden p-6 sm:p-10 text-center">
                    <div class="mx-auto h-16 w-16 rounded-full bg-gray-100 flex items-center justify-center mb-4">
                        <i data-lucide="file-x" class="w-8 h-8 text-gray-400"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-700 mb-2">No Records Yet</h3>
                    <p class="text-sm text-gray-500 mb-5">Your X-ray examination history will appear here once you have a
                        case.
                    </p>
                    <a href="/<?= PROJECT_DIR ?>/registration"
                        class="inline-flex items-center gap-2 rounded-xl bg-red-600 hover:bg-red-700 text-white font-semibold text-sm py-3 px-5 transition">
                        <i data-lucide="plus-circle" class="w-4 h-4"></i> Register for X-ray
                    </a>
                </div>
            <?php else: ?>

                <!-- Search & Filters (RadTech Style) -->
                <div class="mb-4 sm:mb-6 flex flex-col md:flex-row gap-2 sm:gap-3 md:items-center">
                    <div class="relative flex-1">
                        <input type="text" id="record-search-input"
                            placeholder="Search records (Request / Case # or Exam)..."
                            class="w-full rounded-lg sm:rounded-xl border border-gray-200 bg-white pl-9 sm:pl-10 pr-3 sm:pr-4 py-2 sm:py-2.5 text-xs sm:text-sm text-gray-900 outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-all shadow-sm">
                        <div class="absolute inset-y-0 left-0 pl-3 sm:pl-3.5 flex items-center pointer-events-none">
                            <i data-lucide="search" class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-gray-400"></i>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-2 md:flex md:flex-nowrap md:gap-3">
                        <select id="record-branch-filter"
                            class="w-full md:w-auto min-w-[140px] rounded-lg sm:rounded-xl border border-gray-200 bg-white px-3 sm:px-4 py-2 sm:py-2.5 text-xs sm:text-sm outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-all shadow-sm">
                            <option>All Branches</option>
                            <?php
                            $branches = $branchModel->getAllBranches();
                            foreach ($branches as $b) {
                                echo "<option>" . htmlspecialchars($b['name']) . "</option>";
                            }
                            ?>
                        </select>

                        <select id="record-sort-date"
                            class="w-full md:w-auto min-w-[140px] rounded-lg sm:rounded-xl border border-gray-200 bg-white px-3 sm:px-4 py-2 sm:py-2.5 text-xs sm:text-sm outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-all shadow-sm">
                            <option>Newest Case</option>
                            <option>Oldest Case</option>
                        </select>
                    </div>
                </div>

                <!-- Auto-updating container for data -->
                <div id="my-records-container" class="realtime-update">
                    <!-- Desktop & Tablet Table (Hidden on Mobile) -->
                    <div class="hidden md:block rounded-2xl bg-white border border-gray-200 shadow-sm overflow-hidden">
                        <div class="overflow-x-auto custom-scrollbar">
                            <table class="w-full text-sm">
                                <thead
                                    class="sticky top-0 z-10 bg-gray-50 border-b border-gray-100 text-gray-500 text-left">
                                    <tr>
                                        <th class="px-5 py-3 font-semibold whitespace-nowrap">Request / Case #</th>
                                        <th class="px-5 py-3 font-semibold whitespace-nowrap">Examination</th>
                                        <th class="px-5 py-3 font-semibold whitespace-nowrap">Date</th>
                                        <th class="px-5 py-3 font-semibold whitespace-nowrap">Branch</th>
                                        <th class="px-5 py-3 font-semibold whitespace-nowrap">Status</th>
                                        <th class="px-5 py-3 font-semibold whitespace-nowrap text-center lg:text-left">
                                            Action</th>
                                    </tr>
                                </thead>
                                <tbody id="desktop-table-body" class="divide-y divide-gray-50">
                                    <?php foreach ($allCases as $c): ?>
                                        <?php
                                        $displayStatus = ($c['approval_status'] === 'Rejected') ? 'Rejected' : $c['status'];
                                        $badge = $statusBadge[$displayStatus] ?? ['bg' => 'bg-gray-50', 'text' => 'text-gray-600', 'border' => 'border-gray-200', 'label' => $displayStatus];
                                        $branchName = $c['branch_name'] ?? $c['branch'] ?? 'ΓÇö';
                                        ?>
                                        <tr class="hover:bg-gray-100 transition-colors record-row"
                                            data-id="<?= htmlspecialchars($c['case_number']) ?>" data-case-id="<?= $c['id'] ?>"
                                            data-exam="<?= htmlspecialchars($c['exam_type'] ?? '') ?>"
                                            data-branch="<?= htmlspecialchars($branchName) ?>"
                                            data-date="<?= htmlspecialchars($c['created_at']) ?>">
                                            <td
                                                class="px-5 py-3.5 font-mono text-xs font-semibold text-red-600 whitespace-nowrap">
                                                <?= htmlspecialchars($c['case_number']) ?>
                                            </td>
                                            <td
                                                class="px-5 py-3.5 text-gray-800 font-medium whitespace-nowrap truncate max-w-[150px] lg:max-w-[300px]">
                                                <?= htmlspecialchars($c['exam_type'] ?? 'ΓÇö') ?>
                                            </td>
                                            <td class="px-5 py-3.5 text-gray-600 whitespace-nowrap">
                                                <?= htmlspecialchars(date('M j, Y', strtotime($c['created_at']))) ?>
                                            </td>
                                            <td class="px-5 py-3.5 text-gray-600 whitespace-nowrap">
                                                <?= htmlspecialchars($branchName) ?>
                                            </td>
                                            <td class="px-5 py-3.5 whitespace-nowrap">
                                                <span
                                                    class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-semibold <?= $badge['bg'] ?> <?= $badge['text'] ?> <?= $badge['border'] ?>">
                                                    <?= htmlspecialchars($badge['label']) ?>
                                                </span>
                                            </td>
                                            <td class="px-5 py-3.5 whitespace-nowrap">
                                                <div class="flex items-center gap-2 justify-center lg:justify-start">
                                                    <!-- View Status -->
                                                    <a href="/<?= PROJECT_DIR ?>/case-status?<?= !empty($c['is_request_only']) ? 'request_id=' : 'case_id=' ?><?= $c['id'] ?>"
                                                        class="group transition-all" title="View Status">
                                                        <div
                                                            class="hidden lg:flex items-center justify-center p-2 rounded-lg bg-blue-50 border border-blue-200 text-blue-600 hover:bg-blue-600 hover:text-white transition-colors shadow-sm">
                                                            <i data-lucide="activity" class="w-4 h-4"></i>
                                                        </div>
                                                        <span
                                                            class="lg:hidden inline-flex items-center gap-1.5 text-gray-600 hover:text-blue-600 text-xs font-semibold transition">
                                                            <i data-lucide="activity" class="w-3.5 h-3.5"></i> View Status
                                                        </span>
                                                    </a>                                                     
                                                    <?php if (in_array($displayStatus, ['Released', 'Completed', 'Report Ready'])): ?>
                                                        <!-- View Report -->
                                                        <?php
                                                        $isExpired = strtotime($c['created_at']) < strtotime('-3 months');
                                                        $reportUrl = $isExpired ? 'javascript:void(0)' : '/' . PROJECT_DIR . '/view-report?ref=' . base64_encode('CitiLife_Case_' . $c['id']);
                                                        $contacts = array_filter([$c['branch_contact'] ?? '', $c['branch_contact_2'] ?? '', $c['branch_contact_3'] ?? '']);
                                                        $onClickAttr = $isExpired ? 'onclick="showExpiredAlert(event, ' . htmlspecialchars(json_encode(array_values($contacts)), ENT_QUOTES, 'UTF-8') . ')"' : '';
                                                        ?>
                                                        <a href="<?= $reportUrl ?>" <?= $onClickAttr ?> class="group transition-all"
                                                            title="View Report">
                                                            <div
                                                                class="hidden lg:flex items-center justify-center p-2 rounded-lg bg-green-50 border border-green-200 text-green-600 hover:bg-green-600 hover:text-white transition-colors shadow-sm">
                                                                <i data-lucide="file-text" class="w-4 h-4"></i>
                                                            </div>
                                                            <span
                                                                class="lg:hidden inline-flex items-center gap-1.5 text-green-600 hover:text-green-800 text-xs font-semibold transition">
                                                                <i data-lucide="file-text" class="w-3.5 h-3.5"></i> View Report
                                                            </span>
                                                        </a>

                                                        <!-- Report an Error / Dispute Result -->
                                                        <button type="button" onclick="openDisputeModal(<?= $c['id'] ?>, '<?= htmlspecialchars($c['case_number']) ?>', '<?= htmlspecialchars(addslashes($c['exam_type'] ?? 'General Exam')) ?>')"
                                                                class="group transition-all" title="Report an Error / Dispute Result">
                                                            <div class="hidden lg:flex items-center justify-center p-2 rounded-lg bg-red-50 border border-red-200 text-red-600 hover:bg-red-600 hover:text-white transition-colors shadow-sm">
                                                                <i data-lucide="alert-triangle" class="w-4 h-4"></i>
                                                            </div>
                                                            <span class="lg:hidden inline-flex items-center gap-1.5 text-red-600 hover:text-red-800 text-xs font-semibold transition">
                                                                <i data-lucide="alert-triangle" class="w-3.5 h-3.5"></i> Report Error
                                                            </span>
                                                        </button>



                                                        <!-- Give Feedback (Only if not yet given) -->
                                                        <?php if (!in_array($c['id'], $feedbackCaseIds)): ?>
                                                            <a href="javascript:void(0)"
                                                                onclick="openFeedbackModal(<?= $c['id'] ?>, '<?= htmlspecialchars($c['case_number']) ?>', '<?= htmlspecialchars(addslashes($c['exam_type'] ?? 'General Exam')) ?>')"
                                                                class="group transition-all" title="Give Feedback">
                                                                <div
                                                                    class="hidden lg:flex items-center justify-center p-2 rounded-lg bg-yellow-50 border border-yellow-200 text-yellow-600 hover:bg-yellow-500 hover:text-white transition-colors shadow-sm">
                                                                    <i data-lucide="star" class="w-4 h-4"></i>
                                                                </div>
                                                                <span
                                                                    class="lg:hidden inline-flex items-center gap-1.5 text-yellow-600 hover:text-yellow-700 text-xs font-semibold transition mt-1 lg:mt-0">
                                                                    <i data-lucide="star" class="w-3.5 h-3.5"></i> Feedback
                                                                </span>
                                                            </a>
                                                        <?php endif; ?>
                                                     <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Desktop Pagination Footer -->
                        <div class="px-5 py-4 bg-gray-50 border-t border-gray-100 flex items-center justify-between">
                            <span id="record-count-info" class="text-xs text-gray-500 font-medium"></span>
                            <div class="flex items-center gap-2">
                                <button id="record-prev-btn"
                                    class="p-2 rounded-lg bg-white border border-gray-200 text-gray-600 hover:bg-gray-50 transition-colors disabled:opacity-40 disabled:cursor-not-allowed flex items-center justify-center">
                                    <i data-lucide="chevron-left" class="w-4 h-4"></i>
                                </button>
                                <span id="record-page-info"
                                    class="text-xs font-bold text-gray-700 min-w-[70px] text-center"></span>
                                <button id="record-next-btn"
                                    class="p-2 rounded-lg bg-white border border-gray-200 text-gray-600 hover:bg-gray-50 transition-colors disabled:opacity-40 disabled:cursor-not-allowed flex items-center justify-center">
                                    <i data-lucide="chevron-right" class="w-4 h-4"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Mobile Card View (Hidden on Desktop & Tablet) -->
                    <div id="mobile-cards-container" class="md:hidden space-y-4">
                        <?php foreach ($allCases as $c): ?>
                            <?php
                            $displayStatus = ($c['approval_status'] === 'Rejected') ? 'Rejected' : $c['status'];
                            $badge = $statusBadge[$displayStatus] ?? ['bg' => 'bg-gray-50', 'text' => 'text-gray-600', 'border' => 'border-gray-200', 'label' => $displayStatus];
                            $branchName = $c['branch_name'] ?? $c['branch'] ?? 'ΓÇö';
                            ?>
                            <div class="bg-white border border-gray-100 rounded-2xl p-4 shadow-sm space-y-3 record-card transition-all"
                                data-id="<?= htmlspecialchars($c['case_number']) ?>" data-case-id="<?= $c['id'] ?>"
                                data-exam="<?= htmlspecialchars($c['exam_type'] ?? '') ?>"
                                data-branch="<?= htmlspecialchars($branchName) ?>"
                                data-date="<?= htmlspecialchars($c['created_at']) ?>">
                                <div class="flex items-center justify-between">
                                    <span
                                        class="font-mono text-xs font-semibold text-red-600"><?= htmlspecialchars($c['case_number']) ?></span>
                                    <span
                                        class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-semibold <?= $badge['bg'] ?> <?= $badge['text'] ?> <?= $badge['border'] ?>">
                                        <?= htmlspecialchars($badge['label']) ?>
                                    </span>
                                </div>
                                <div class="text-sm font-bold text-gray-900"><?= htmlspecialchars($c['exam_type'] ?? 'ΓÇö') ?>
                                </div>
                                <div class="flex flex-wrap items-center gap-4 text-xs text-gray-500">
                                    <span class="flex items-center gap-1.5">
                                        <i data-lucide="calendar" class="w-3.5 h-3.5"></i>
                                        <?= htmlspecialchars(date('M j, Y', strtotime($c['created_at']))) ?>
                                    </span>
                                    <span class="flex items-center gap-1.5">
                                        <i data-lucide="map-pin" class="w-3.5 h-3.5"></i>
                                        <?= htmlspecialchars($c['branch_name'] ?? $c['branch'] ?? 'ΓÇö') ?>
                                    </span>
                                </div>
                                <div class="flex items-center justify-between pt-2 border-t border-gray-50">
                                    <a href="/<?= PROJECT_DIR ?>/case-status?case_id=<?= $c['id'] ?>"
                                        class="inline-flex items-center gap-1.5 text-gray-600 hover:text-red-600 text-xs font-bold transition">
                                        <i data-lucide="activity" class="w-3.5 h-3.5"></i> View Status
                                    </a>
                                     <?php if (in_array($displayStatus, ['Released', 'Completed', 'Report Ready'])): ?>
                                        <?php
                                        $isExpired = strtotime($c['created_at']) < strtotime('-3 months');
                                        $reportUrl = $isExpired ? 'javascript:void(0)' : '/' . PROJECT_DIR . '/view-report?ref=' . base64_encode('CitiLife_Case_' . $c['id']);
                                        $contacts = array_filter([$c['branch_contact'] ?? '', $c['branch_contact_2'] ?? '', $c['branch_contact_3'] ?? '']);
                                        $onClickAttr = $isExpired ? 'onclick="showExpiredAlert(event, ' . htmlspecialchars(json_encode(array_values($contacts)), ENT_QUOTES, 'UTF-8') . ')"' : '';
                                        ?>
                                        <a href="<?= $reportUrl ?>" <?= $onClickAttr ?>
                                            class="inline-flex items-center gap-1.5 text-green-600 hover:text-green-800 text-xs font-bold transition">
                                            <i data-lucide="file-text" class="w-3.5 h-3.5"></i> View Report
                                        </a>
                                        <?php if (!in_array($c['id'], $feedbackCaseIds)): ?>
                                            <a href="javascript:void(0)"
                                                onclick="openFeedbackModal(<?= $c['id'] ?>, '<?= htmlspecialchars($c['case_number']) ?>', '<?= htmlspecialchars(addslashes($c['exam_type'] ?? 'General Exam')) ?>')"
                                                class="inline-flex items-center gap-1.5 text-yellow-600 hover:text-yellow-700 text-xs font-bold transition">
                                                <i data-lucide="star" class="w-3.5 h-3.5"></i> Feedback
                                            </a>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Mobile Pagination Footer -->
                    <div id="mobile-pagination-footer"
                        class="md:hidden mt-6 flex items-center justify-between bg-white p-4 rounded-2xl border border-gray-100 shadow-sm">
                        <button id="record-prev-btn-mob"
                            class="p-2 rounded-xl bg-gray-50 text-gray-600 hover:bg-gray-100 transition-colors disabled:opacity-30 flex items-center justify-center">
                            <i data-lucide="chevron-left" class="w-5 h-5"></i>
                        </button>
                        <div class="text-center">
                            <div id="record-page-info-mob" class="text-xs font-bold text-gray-900"></div>
                            <div id="record-count-info-mob" class="text-[10px] text-gray-500 font-medium"></div>
                        </div>
                        <button id="record-next-btn-mob"
                            class="p-2 rounded-xl bg-gray-50 text-gray-600 hover:bg-gray-100 transition-colors disabled:opacity-30 flex items-center justify-center">
                            <i data-lucide="chevron-right" class="w-5 h-5"></i>
                        </button>
                    </div>

                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- MY SUBMITTED ERROR REPORTS VIEW CONTAINER (Hidden by default) -->
    <div id="patient-disputes-wrapper" class="hidden">
        <div class="rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden p-6">
            <h3 class="text-base font-bold text-gray-900 mb-1">Status of My Error Reports / Disputes</h3>
            <p class="text-xs text-gray-500 mb-4">Monitor real-time progress and clinic action for your reported disputes here.</p>

            <?php if (empty($patientDisputes)): ?>
                <div class="py-8 text-center text-gray-500">
                    <i data-lucide="check-circle" class="w-10 h-10 text-gray-300 mx-auto mb-2"></i>
                    <p class="text-sm font-semibold">You have no submitted error reports or disputes.</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-xs text-left">
                        <thead>
                            <tr class="border-b border-gray-100 bg-gray-50 text-gray-600">
                                <th class="px-4 py-3">Case No. / Exam</th>
                                <th class="px-4 py-3">Error Category</th>
                                <th class="px-4 py-3">Submitted Details</th>
                                <th class="px-4 py-3">Current Status</th>
                                <th class="px-4 py-3">Date Submitted</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php foreach ($patientDisputes as $disp): ?>
                                <?php
                                $statusBadgeMap = [
                                    'Pending RadTech Review' => 'bg-amber-50 text-amber-700 border-amber-300',
                                    'Escalated to Radiologist' => 'bg-purple-50 text-purple-700 border-purple-300',
                                    'Pending RadTech Verification' => 'bg-blue-50 text-blue-700 border-blue-300',
                                    'Resolved' => 'bg-green-50 text-green-700 border-green-300',
                                    'Rejected' => 'bg-red-50 text-red-700 border-red-300',
                                ];
                                $bCls = $statusBadgeMap[$disp['status']] ?? 'bg-gray-50 text-gray-600 border-gray-200';
                                
                                $catMap = [
                                    'demographic_error' => 'Wrong Patient Info',
                                    'exam_details_error' => 'Wrong Body Part / Exam',
                                    'findings_error' => 'Discrepancy in Findings',
                                    'other' => 'Other Error'
                                ];
                                ?>
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-4 py-3">
                                        <div class="font-mono font-bold text-red-600"><?= htmlspecialchars($disp['case_number']) ?></div>
                                        <div class="text-gray-500 font-medium"><?= htmlspecialchars($disp['exam_type']) ?></div>
                                    </td>
                                    <td class="px-4 py-3 font-semibold text-gray-700">
                                        <?= htmlspecialchars($catMap[$disp['dispute_category']] ?? ucfirst($disp['dispute_category'])) ?>
                                    </td>
                                     <td class="px-4 py-3 text-gray-600 max-w-[200px] lg:max-w-[250px] whitespace-normal break-words" title="<?= htmlspecialchars($disp['description']) ?>">
                                         <div class="line-clamp-2 italic">"<?= htmlspecialchars($disp['description']) ?>"</div>
                                     </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-[11px] font-bold <?= $bCls ?>">
                                            <?= htmlspecialchars($disp['status']) ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-gray-500">
                                        <?= date('M j, Y h:i A', strtotime($disp['created_at'])) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
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
                            <p class="text-xs text-gray-500">How was your experience with CitiLife?</p>
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
                                            fill="currentColor" stroke="currentColor" stroke-width="1"
                                            stroke-linecap="round" stroke-linejoin="round"
                                            class="w-8 h-8 pointer-events-none">
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

            document.addEventListener('DOMContentLoaded', function () {
                const urlParams = new URLSearchParams(window.location.search);
                if (urlParams.has('expired')) {
                    showExpiredAlert();
                    // clean up the URL parameter
                    const newUrl = new URL(window.location);
                    newUrl.searchParams.delete('expired');
                    window.history.replaceState({}, document.title, newUrl.toString());
                }
            });
        </script>

        <?php if (!empty($allCases)): ?>
            <script src="/<?= PROJECT_DIR ?>/views/pages/patient/my-records.js?v=<?= time() ?>"></script>
        <?php endif; ?>

        <script>
            // ΓöÇΓöÇ Feedback Modal Logic ΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇΓöÇ
            window.openFeedbackModal = function (caseId, caseNumber, examType) {
                const modal = document.getElementById('feedback-modal');
                const content = document.getElementById('feedback-modal-content');
                if (!modal || !content) return;

                // Append modal to body to prevent z-index stacking issues
                if (modal.parentElement !== document.body) {
                    document.body.appendChild(modal);
                }

                // Reset form
                const form = document.getElementById('feedback-form');
                if (form) form.reset();
                document.getElementById('feedback-case-id').value = caseId;
                document.getElementById('feedback-rating-input').value = "";
                updateFeedbackStars(0);

                // Set text
                document.getElementById('feedback-case-number').textContent = caseNumber;

                // Show modal
                modal.classList.remove('hidden');
                // trigger reflow
                void modal.offsetWidth;
                modal.classList.remove('opacity-0');
                content.classList.remove('scale-95');
            };

            window.closeFeedbackModal = function () {
                const modal = document.getElementById('feedback-modal');
                const content = document.getElementById('feedback-modal-content');
                if (!modal || !content) return;

                modal.classList.add('opacity-0');
                content.classList.add('scale-95');
                setTimeout(() => {
                    modal.classList.add('hidden');
                }, 200);
            };

            // Star rating logic
            const feedbackTexts = {
                1: "Poor", 2: "Fair", 3: "Good", 4: "Very Good", 5: "Excellent"
            };

            function updateFeedbackStars(value) {
                const stars = document.querySelectorAll('.feedback-star-btn');
                const ratingText = document.getElementById('feedback-rating-text');

                stars.forEach(star => {
                    const rating = parseInt(star.getAttribute('data-rating'));
                    if (rating <= value) {
                        star.classList.remove('text-gray-300');
                        star.classList.add('text-yellow-400');
                    } else {
                        star.classList.remove('text-yellow-400');
                        star.classList.add('text-gray-300');
                    }
                });
                if (ratingText) {
                    ratingText.textContent = value == 0 ? "Select a rating" : feedbackTexts[value];
                }
            }

            document.addEventListener('click', function (e) {
                const starBtn = e.target.closest('.feedback-star-btn');
                if (starBtn) {
                    const value = starBtn.getAttribute('data-rating');
                    document.getElementById('feedback-rating-input').value = value;
                    updateFeedbackStars(value);
                }
            });

            window.submitFeedbackForm = function () {
                const feedbackForm = document.getElementById('feedback-form');
                if (!feedbackForm) return;

                const submitBtn = document.getElementById('feedback-submit-btn');
                const rating = document.getElementById('feedback-rating-input').value;

                if (!rating) {
                    Swal.fire({ icon: 'error', title: 'Oops...', text: 'Please provide a star rating to let us know how we did!' });
                    return;
                }

                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i> Submitting...';
                if (window.lucide) lucide.createIcons();

                const formData = new FormData(feedbackForm);

                fetch('/<?= PROJECT_DIR ?>/app/api/submit_feedback.php', {
                    method: 'POST',
                    body: formData
                })
                    .then(res => res.json())
                    .then(data => {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = '<i data-lucide="send" class="w-4 h-4"></i> Submit Feedback';
                        if (window.lucide) lucide.createIcons();

                        if (data.success) {
                            closeFeedbackModal();
                            Swal.fire({
                                icon: 'success',
                                title: 'Thank you!',
                                text: 'Your feedback has been submitted successfully.',
                                confirmButtonColor: '#dc2626',
                                customClass: {
                                    popup: 'rounded-2xl',
                                    title: 'text-xl font-bold text-gray-800'
                                }
                            }).then(() => {
                                window.location.reload();
                            });
                        } else {
                            Swal.fire({ icon: 'error', title: 'Error', text: data.error || 'Failed to submit feedback' });
                        }
                    })
                    .catch(err => {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = '<i data-lucide="send" class="w-4 h-4"></i> Submit Feedback';
                        if (window.lucide) lucide.createIcons();
                        console.error(err);
                        Swal.fire({ icon: 'error', title: 'Error', text: 'A network error occurred. Please try again.' });
                    });
            };
        </script>

        <!-- DISPUTE / REPORT ERROR MODAL -->
        <div id="dispute-modal" class="hidden fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm transition-opacity opacity-0">
            <div class="bg-white rounded-3xl shadow-2xl w-full max-w-lg overflow-hidden transform scale-95 transition-transform duration-200 z-[10000]" id="dispute-modal-content">
                <!-- Modal Header -->
                <div class="px-6 py-5 border-b border-gray-100 bg-red-50/50 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="p-2.5 bg-red-100 text-red-600 rounded-xl">
                            <i data-lucide="alert-triangle" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <h2 class="font-bold text-gray-900 text-base">Report an Error / Dispute Result</h2>
                            <p class="text-xs text-gray-500">Notice an issue with your report or info? Report it to the clinic.</p>
                        </div>
                    </div>
                    <button type="button" onclick="closeDisputeModal()" class="text-gray-400 hover:text-gray-600 transition p-2 rounded-lg hover:bg-gray-100">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                <!-- Case Info Bar -->
                <div class="bg-gray-50 border-b border-gray-100 px-6 py-3 flex items-center justify-between text-xs text-gray-700">
                    <div>Case #: <span class="font-bold font-mono text-red-600" id="dispute-case-number"></span></div>
                    <div class="font-medium text-gray-500" id="dispute-exam-type"></div>
                </div>

                <!-- Form -->
                <form id="dispute-form" onsubmit="submitDisputeForm(event)" class="p-6 space-y-4">
                    <input type="hidden" name="case_id" id="dispute-case-id">
                    <input type="hidden" name="action" value="submit_dispute">

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">What type of error is this? *</label>
                        <select name="dispute_category" id="dispute-category" required onchange="toggleDisputeFields()" class="w-full rounded-xl border border-gray-300 px-3.5 py-2.5 text-xs text-gray-900 outline-none focus:ring-2 focus:ring-red-500 bg-white">
                            <option value="">-- Select Category --</option>
                            <option value="demographic_error">1. Wrong Patient Info (Incorrect Name, Age, or Sex)</option>
                            <option value="findings_error">2. Wrong Body Part / Image Discrepancy (Requires Radiologist Re-examination)</option>
                        </select>
                    </div>

                    <!-- Dynamic Patient Info Correction Options -->
                    <div id="demographic-options-container" class="hidden p-3.5 bg-gray-50 border border-gray-200 rounded-xl space-y-3">
                        <label class="block text-xs font-bold text-gray-700">Select what needs correction: *</label>
                        
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
                                <span class="font-medium">Sex / Gender</span>
                            </label>
                        </div>

                        <!-- Dynamic Input Fields -->
                        <div id="correction-inputs-container" class="space-y-2 pt-2 border-t border-gray-200 hidden">
                            <div id="field-first-name" class="hidden">
                                <label class="block text-[11px] font-semibold text-gray-600 mb-1">Correct First Name:</label>
                                <input type="text" id="input-first-name" placeholder="Enter correct First Name" class="w-full rounded-lg border border-gray-300 px-3 py-1.5 text-xs focus:border-red-500 focus:ring-1 focus:ring-red-500 outline-none">
                            </div>
                            <div id="field-last-name" class="hidden">
                                <label class="block text-[11px] font-semibold text-gray-600 mb-1">Correct Last Name:</label>
                                <input type="text" id="input-last-name" placeholder="Enter correct Last Name" class="w-full rounded-lg border border-gray-300 px-3 py-1.5 text-xs focus:border-red-500 focus:ring-1 focus:ring-red-500 outline-none">
                            </div>
                            <div id="field-age" class="hidden">
                                <label class="block text-[11px] font-semibold text-gray-600 mb-1">Correct Age:</label>
                                <input type="number" id="input-age" placeholder="Enter correct Age" class="w-full rounded-lg border border-gray-300 px-3 py-1.5 text-xs focus:border-red-500 focus:ring-1 focus:ring-red-500 outline-none">
                            </div>
                            <div id="field-sex" class="hidden">
                                <label class="block text-[11px] font-semibold text-gray-600 mb-1">Correct Sex / Gender:</label>
                                <select id="input-sex" class="w-full rounded-lg border border-gray-300 px-3 py-1.5 text-xs focus:border-red-500 focus:ring-1 focus:ring-red-500 outline-none bg-white">
                                    <option value="">-- Select Correct Sex --</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Description Textarea Container (For non-demographic errors or additional notes) -->
                    <div id="general-description-container">
                        <label class="block text-xs font-bold text-gray-700 mb-1">Provide details of the correction needed *</label>
                        <textarea name="description" id="dispute-description" rows="3"
                            class="w-full rounded-xl border border-gray-300 px-3.5 py-2.5 text-xs text-gray-900 outline-none focus:ring-2 focus:ring-red-500"
                            placeholder="Example: 'My right arm was examined instead of left...', or explain discrepancy in findings"></textarea>
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" onclick="closeDisputeModal()" class="px-4 py-2.5 text-xs font-semibold text-gray-600 hover:bg-gray-100 rounded-xl transition">
                            Cancel
                        </button>
                        <button type="submit" id="dispute-submit-btn" class="px-5 py-2.5 text-xs font-bold text-white bg-red-600 hover:bg-red-700 rounded-xl shadow transition flex items-center gap-1.5">
                            <i data-lucide="send" class="w-4 h-4"></i> Submit Error Report
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <script>
            window.toggleDisputeFields = function() {
                const category = document.getElementById('dispute-category').value;
                const demoContainer = document.getElementById('demographic-options-container');
                const descContainer = document.getElementById('general-description-container');
                const descTextarea = document.getElementById('dispute-description');

                if (category === 'demographic_error') {
                    demoContainer.classList.remove('hidden');
                    descContainer.classList.add('hidden');
                    descTextarea.removeAttribute('required');
                } else if (category === 'exam_details_error' || category === 'findings_error') {
                    demoContainer.classList.add('hidden');
                    descContainer.classList.remove('hidden');
                    descTextarea.setAttribute('required', 'required');
                    // Reset checkboxes & fields
                    ['chk-first-name', 'chk-last-name', 'chk-age', 'chk-sex'].forEach(id => {
                        const el = document.getElementById(id);
                        if (el) el.checked = false;
                    });
                    toggleCorrectionInputs();
                } else {
                    demoContainer.classList.add('hidden');
                    descContainer.classList.remove('hidden');
                    descTextarea.removeAttribute('required');
                }
            };

            window.toggleCorrectionInputs = function() {
                const map = [
                    { chk: 'chk-first-name', field: 'field-first-name', input: 'input-first-name' },
                    { chk: 'chk-last-name', field: 'field-last-name', input: 'input-last-name' },
                    { chk: 'chk-age', field: 'field-age', input: 'input-age' },
                    { chk: 'chk-sex', field: 'field-sex', input: 'input-sex' },
                ];

                let anyChecked = false;
                const inputsContainer = document.getElementById('correction-inputs-container');

                map.forEach(item => {
                    const isChecked = document.getElementById(item.chk)?.checked;
                    const fieldEl = document.getElementById(item.field);
                    const inputEl = document.getElementById(item.input);

                    if (isChecked) {
                        anyChecked = true;
                        if (fieldEl) fieldEl.classList.remove('hidden');
                    } else {
                        if (fieldEl) fieldEl.classList.add('hidden');
                        if (inputEl) inputEl.value = '';
                    }
                });

                if (anyChecked) {
                    inputsContainer.classList.remove('hidden');
                } else {
                    inputsContainer.classList.add('hidden');
                }
            };

            window.openDisputeModal = function(caseId, caseNumber, examType) {
                const modal = document.getElementById('dispute-modal');
                const content = document.getElementById('dispute-modal-content');
                if (!modal || !content) return;

                if (modal.parentElement !== document.body) {
                    document.body.appendChild(modal);
                }

                document.getElementById('dispute-case-id').value = caseId;
                document.getElementById('dispute-case-number').textContent = caseNumber;
                document.getElementById('dispute-exam-type').textContent = examType;
                document.getElementById('dispute-category').value = '';
                const descTextarea = document.getElementById('dispute-description');
                if (descTextarea) descTextarea.value = '';

                // Reset dynamic demographic options
                ['chk-first-name', 'chk-last-name', 'chk-age', 'chk-sex'].forEach(id => {
                    const el = document.getElementById(id);
                    if (el) el.checked = false;
                });
                toggleDisputeFields();

                modal.classList.remove('hidden');
                void modal.offsetWidth;
                modal.classList.remove('opacity-0');
                content.classList.remove('scale-95');

                if (window.lucide) lucide.createIcons();
            };

            window.closeDisputeModal = function() {
                const modal = document.getElementById('dispute-modal');
                const content = document.getElementById('dispute-modal-content');
                if (!modal || !content) return;

                modal.classList.add('opacity-0');
                content.classList.add('scale-95');
                setTimeout(() => {
                    modal.classList.add('hidden');
                }, 200);
            };

            window.submitDisputeForm = function(e) {
                e.preventDefault();
                const btn = document.getElementById('dispute-submit-btn');
                const form = document.getElementById('dispute-form');
                const category = document.getElementById('dispute-category').value;
                const formData = new FormData(form);

                if (category === 'demographic_error') {
                    const items = [];
                    if (document.getElementById('chk-first-name')?.checked) {
                        const val = document.getElementById('input-first-name').value.trim();
                        if (val) items.push(`First Name: ${val}`);
                    }
                    if (document.getElementById('chk-last-name')?.checked) {
                        const val = document.getElementById('input-last-name').value.trim();
                        if (val) items.push(`Last Name: ${val}`);
                    }
                    if (document.getElementById('chk-age')?.checked) {
                        const val = document.getElementById('input-age').value.trim();
                        if (val) items.push(`Age: ${val}`);
                    }
                    if (document.getElementById('chk-sex')?.checked) {
                        const val = document.getElementById('input-sex').value;
                        if (val) items.push(`Sex: ${val}`);
                    }

                    if (items.length === 0) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Incomplete Details',
                            text: 'Pumili ng checkbox at ilagay ang tamang detalye na papabago.',
                            confirmButtonColor: '#dc2626'
                        });
                        return;
                    }
                    formData.set('description', items.join("\n"));
                }

                btn.disabled = true;
                btn.innerHTML = 'Submitting...';

                fetch('/<?= PROJECT_DIR ?>/app/api/disputes.php', {
                    method: 'POST',
                    body: formData
                })
                .then(r => r.json())
                .then(data => {
                    btn.disabled = false;
                    btn.innerHTML = '<i data-lucide="send" class="w-4 h-4"></i> Submit Error Report';
                    if (window.lucide) lucide.createIcons();

                    if (data.success) {
                        closeDisputeModal();
                        Swal.fire({
                            icon: 'success',
                            title: 'Report Submitted!',
                            text: data.message,
                            confirmButtonColor: '#dc2626',
                            customClass: { popup: 'rounded-2xl' }
                        }).then(() => {
                            window.location.href = '/<?= PROJECT_DIR ?>/my-records?tab=disputes';
                        });
                    } else {
                        Swal.fire({ icon: 'error', title: 'Error', text: data.message });
                    }
                })
                .catch(err => {
                    btn.disabled = false;
                    btn.innerHTML = '<i data-lucide="send" class="w-4 h-4"></i> Submit Error Report';
                    console.error(err);
                    Swal.fire({ icon: 'error', title: 'Error', text: 'Network connection error.' });
                });
            };

            window.switchPatientTab = function(tab) {
                const recWrap = document.getElementById('my-records-wrapper');
                const disWrap = document.getElementById('patient-disputes-wrapper');
                const recBtn = document.getElementById('tab-patient-records-btn');
                const disBtn = document.getElementById('tab-patient-disputes-btn');

                if (tab === 'records') {
                    if (recWrap) recWrap.classList.remove('hidden');
                    if (disWrap) disWrap.classList.add('hidden');

                    if (recBtn) recBtn.className = "pb-3 px-1 text-sm font-bold border-b-2 border-red-600 text-red-600 transition flex items-center gap-2";
                    if (disBtn) disBtn.className = "pb-3 px-1 text-sm font-semibold border-b-2 border-transparent text-gray-500 hover:text-gray-700 transition flex items-center gap-2 relative";
                } else {
                    if (recWrap) recWrap.classList.add('hidden');
                    if (disWrap) disWrap.classList.remove('hidden');

                    if (disBtn) disBtn.className = "pb-3 px-1 text-sm font-bold border-b-2 border-red-600 text-red-600 transition flex items-center gap-2 relative";
                    if (recBtn) recBtn.className = "pb-3 px-1 text-sm font-semibold border-b-2 border-transparent text-gray-500 hover:text-gray-700 transition flex items-center gap-2";
                }
            };

            document.addEventListener('DOMContentLoaded', () => {
                const params = new URLSearchParams(window.location.search);
                if (params.get('tab') === 'disputes') {
                    switchPatientTab('disputes');
                }
            });
        </script>
    </div>
