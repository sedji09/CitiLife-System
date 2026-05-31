<?php
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../models/CaseModel.php';
require_once __DIR__ . '/../../../models/PatientModel.php';
require_once __DIR__ . '/../../../models/BranchModel.php';

$caseModel = new \CaseModel($pdo);
$patientModel = new \PatientModel($pdo);
$branchModel = new \BranchModel($pdo);

$userId = $_SESSION['user_id'] ?? 0;
$allCases = [];

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

// 2. Fetch Cases (Backend logic)
if ($patientRow && isset($patientRow['patient_number'])) {
    $allCases = $caseModel->getPatientHistory($patientRow['patient_number']);
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

    <!-- Header -->
    <div>
        <h1 class="text-xl sm:text-2xl font-bold text-gray-900 tracking-tight">My Records</h1>
        <p class="text-xs sm:text-sm text-gray-500 mt-1">All your X-ray records, medical history, and digital results in
            one place.</p>
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
                    <a href="?role=patient&page=registration"
                        class="inline-flex items-center gap-2 rounded-xl bg-red-600 hover:bg-red-700 text-white font-semibold text-sm py-3 px-5 transition">
                        <i data-lucide="plus-circle" class="w-4 h-4"></i> Register for X-ray
                    </a>
                </div>
            <?php else: ?>

                <!-- Search & Filters (RadTech Style) -->
                <div class="mb-4 sm:mb-6 flex flex-col md:flex-row gap-2 sm:gap-3 md:items-center">
                    <div class="relative flex-1">
                        <input type="text" id="record-search-input" placeholder="Search records (Case # or Exam)..."
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
                                        <th class="px-5 py-3 font-semibold whitespace-nowrap">Case #</th>
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
                                        $branchName = $c['branch_name'] ?? $c['branch'] ?? '—';
                                        ?>
                                        <tr class="hover:bg-gray-100 transition-colors record-row"
                                            data-id="<?= htmlspecialchars($c['case_number']) ?>"
                                            data-exam="<?= htmlspecialchars($c['exam_type'] ?? '') ?>"
                                            data-branch="<?= htmlspecialchars($branchName) ?>"
                                            data-date="<?= htmlspecialchars($c['created_at']) ?>">
                                            <td
                                                class="px-5 py-3.5 font-mono text-xs font-semibold text-red-600 whitespace-nowrap">
                                                <?= htmlspecialchars($c['case_number']) ?>
                                            </td>
                                            <td
                                                class="px-5 py-3.5 text-gray-800 font-medium whitespace-nowrap truncate max-w-[150px] lg:max-w-[300px]">
                                                <?= htmlspecialchars($c['exam_type'] ?? '—') ?>
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
                                                    <a href="?role=patient&page=xray-status&case_id=<?= $c['id'] ?>"
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

                                                    <?php if (in_array($c['status'], ['Released', 'Completed'])): ?>
                                                        <!-- View Report -->
                                                        <a href="?role=patient&page=view-report&ref=<?= base64_encode('CitiLife_Case_' . $c['id']) ?>"
                                                            class="group transition-all" title="View Report">
                                                            <div
                                                                class="hidden lg:flex items-center justify-center p-2 rounded-lg bg-green-50 border border-green-200 text-green-600 hover:bg-green-600 hover:text-white transition-colors shadow-sm">
                                                                <i data-lucide="file-text" class="w-4 h-4"></i>
                                                            </div>
                                                            <span
                                                                class="lg:hidden inline-flex items-center gap-1.5 text-green-600 hover:text-green-800 text-xs font-semibold transition">
                                                                <i data-lucide="file-text" class="w-3.5 h-3.5"></i> View Report
                                                            </span>
                                                        </a>
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
                            $branchName = $c['branch_name'] ?? $c['branch'] ?? '—';
                            ?>
                            <div class="bg-white border border-gray-100 rounded-2xl p-4 shadow-sm space-y-3 record-card"
                                data-id="<?= htmlspecialchars($c['case_number']) ?>"
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
                                <div class="text-sm font-bold text-gray-900"><?= htmlspecialchars($c['exam_type'] ?? '—') ?>
                                </div>
                                <div class="flex flex-wrap items-center gap-4 text-xs text-gray-500">
                                    <span class="flex items-center gap-1.5">
                                        <i data-lucide="calendar" class="w-3.5 h-3.5"></i>
                                        <?= htmlspecialchars(date('M j, Y', strtotime($c['created_at']))) ?>
                                    </span>
                                    <span class="flex items-center gap-1.5">
                                        <i data-lucide="map-pin" class="w-3.5 h-3.5"></i>
                                        <?= htmlspecialchars($c['branch_name'] ?? $c['branch'] ?? '—') ?>
                                    </span>
                                </div>
                                <div class="flex items-center justify-between pt-2 border-t border-gray-50">
                                    <a href="?role=patient&page=xray-status&case_id=<?= $c['id'] ?>"
                                        class="inline-flex items-center gap-1.5 text-gray-600 hover:text-red-600 text-xs font-bold transition">
                                        <i data-lucide="activity" class="w-3.5 h-3.5"></i> View Status
                                    </a>
                                    <?php if (in_array($c['status'], ['Released', 'Completed'])): ?>
                                        <a href="?role=patient&page=view-report&ref=<?= base64_encode('CitiLife_Case_' . $c['id']) ?>"
                                            class="inline-flex items-center gap-1.5 text-green-600 hover:text-green-800 text-xs font-bold transition">
                                            <i data-lucide="file-text" class="w-3.5 h-3.5"></i> View Report
                                        </a>
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
        <?php if (!empty($allCases)): ?>
            <script src="app/views/pages/patient/my-records.js"></script>
        <?php endif; ?>
    </div>