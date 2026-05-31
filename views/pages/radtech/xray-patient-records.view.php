<?php
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../models/CaseModel.php';

$caseModel = new \CaseModel($pdo);
$branchId = $_SESSION['branch_id'] ?? 1;

// Fetch released records (Backend logic)
$records = $caseModel->getReleasedRecords($branchId);
?>

<!-- Header -->
<div class="flex items-center justify-between">
    <div>
        <h2 class="text-xl font-semibold text-gray-900">X-ray Patient Records</h2>
        <p class="text-sm text-gray-500 mt-1">Manage and view completed X-ray patient records</p>
    </div>
</div>


<div class="mt-6 flex flex-col gap-4">
    <div class="flex gap-4 items-center">
        <input type="text" id="search-input" placeholder="Search patient records (Name or ID)..."
            class="flex-1 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm text-gray-900 outline-none focus:ring-2 focus:ring-red-500">
        <select id="filter-exam"
            class="w-48 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm outline-none focus:ring-2 focus:ring-red-500">
            <option>Filter by Exam Type</option>
            <option>All</option>
            <option>Chest PA</option>
            <option>Abdominal X-ray</option>
            <option>Extremity X-ray</option>
            <option>Skull X-ray</option>
            <option>Lumbar Spine</option>
            <option>Pelvis</option>
        </select>
        <select id="sort-date"
            class="w-48 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm outline-none focus:ring-2 focus:ring-red-500">
            <option>Sort by:</option>
            <option>Newest Case</option>
            <option>Oldest Case</option>
        </select>
    </div>
</div>

<div class="rounded-xl border border-gray-300 bg-white shadow-sm mt-4 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="sticky top-0 z-10">
                <tr class="border-b border-gray-200 bg-gray-50 text-gray-600">
                    <th class="text-left font-semibold px-3 py-3 whitespace-nowrap">Case No.</th>
                    <th class="text-left font-semibold px-3 py-3 whitespace-nowrap">Patient No.</th>
                    <th class="text-left font-semibold px-3 py-3 truncate max-w-[200px]">Patient Name</th>
                    <th class="text-left font-semibold px-3 py-3 truncate max-w-[150px]">Exam Type</th>
                    <th class="text-left font-semibold px-3 py-3">Date</th>
                    <th class="text-left font-semibold px-3 py-3 whitespace-nowrap">Actions</th>
                </tr>
            </thead>
            <tbody id="table-body" class="text-gray-800 bg-white divide-y divide-gray-100">
                <?php if (count($records) === 0): ?>
                    <tr>
                        <td colspan="6" class="text-center py-8 text-gray-500">
                            No completed records found. Click 'Send Results' in the patient queue to move cases here.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($records as $row): ?>
                        <tr class="hover:bg-gray-50 transition-colors record-row"
                            data-id="<?= htmlspecialchars($row['case_number']) ?>"
                            data-name="<?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?>"
                            data-exam="<?= htmlspecialchars($row['exam_type']) ?>"
                            data-date="<?= htmlspecialchars($row['created_at']) ?>">
                            <td class="py-3 px-3 whitespace-nowrap">
                                <div class="font-medium"><?= htmlspecialchars($row['case_number']) ?></div>
                            </td>
                            <td class="py-3 px-3 whitespace-nowrap">
                                <div class="font-medium"><?= htmlspecialchars($row['patient_number'] ?? 'N/A') ?></div>
                            </td>
                            <td class="py-3 px-3 truncate max-w-[200px]"
                                title="<?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?>">
                                <div class="font-medium truncate">
                                    <?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?>
                                </div>
                            </td>
                            <td class="py-3 px-3 max-w-[180px]">
                                <?php
                                $exams = array_filter(array_map('trim', explode(',', $row['exam_type'])));
                                $firstExam = reset($exams);
                                $extraCount = count($exams) - 1;
                                ?>
                                <div class="flex items-center gap-1.5">
                                    <span class="font-medium text-gray-800 truncate max-w-[100px]"
                                        title="<?= htmlspecialchars($row['exam_type']) ?>"><?= htmlspecialchars($firstExam) ?></span>
                                    <?php if ($extraCount > 0): ?>
                                        <span
                                            class="inline-flex items-center rounded-full bg-gray-100 border border-gray-300 px-1.5 py-0.5 text-xs font-semibold text-gray-600 cursor-default"
                                            title="<?= htmlspecialchars($row['exam_type']) ?>">+<?= $extraCount ?></span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="py-3 px-3">
                                <div class="text-sm text-gray-500"><?= date('F d, Y', strtotime($row['created_at'])) ?></div>
                            </td>
                            <td class="py-3 px-3 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    <!-- View -->
                                    <a href="/<?= PROJECT_DIR ?>/index.php?role=radtech&page=records-history&id=<?= $row['id'] ?>"
                                        class="text-sm font-medium text-blue-500 hover:text-blue-700 transition"
                                        title="View Record">
                                        <i data-lucide="eye"
                                            class="w-6 h-6 bg-blue-100 px-1 py-1 rounded-md border border-blue-500"></i>
                                    </a>

                                    <!-- Print -->
                                    <a href="javascript:void(0)"
                                        onclick="confirmAction('Confirm Print', 'Would you like to confirm printing this report?', '/<?= PROJECT_DIR ?>/app/views/pages/radtech/print-report.php?id=<?= $row['id'] ?>', 'Yes, Print', true, event)"
                                        class="text-green-500 hover:text-green-700 transition" title="Print Report">
                                        <i data-lucide="printer"
                                            class="w-6 h-6 bg-green-100 px-1 py-1 rounded-md border border-green-500"></i>
                                    </a>

                                    <!-- Download PDF -->
                                    <a href="javascript:void(0)"
                                        onclick="confirmAction('Confirm Download', 'Would you like to save this report as PDF?', '/<?= PROJECT_DIR ?>/app/views/pages/radtech/print-report.php?id=<?= $row['id'] ?>&download=true', 'Yes, Download', true, event)"
                                        class="text-red-500 hover:text-red-700 transition" title="Download PDF">
                                        <i data-lucide="download"
                                            class="w-6 h-6 bg-red-100 px-1 py-1 rounded-md border border-red-500"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination footer -->
    <div class="flex items-center justify-between border-t border-gray-200 bg-gray-50 px-4 py-3">
        <!-- Record count -->
        <span id="xray-record-count" class="text-xs text-gray-500"></span>

        <!-- Prev / Page info / Next -->
        <div class="flex items-center gap-3">
            <button id="xray-prev-btn"
                class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-red-400 transition disabled:opacity-40 disabled:cursor-not-allowed"
                disabled>
                <i data-lucide="chevron-left" class="w-3.5 h-3.5"></i> Previous
            </button>

            <span id="xray-page-info" class="text-xs font-medium text-gray-600 min-w-[90px] text-center">Page 1 of
                1</span>

            <button id="xray-next-btn"
                class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-red-400 transition disabled:opacity-40 disabled:cursor-not-allowed"
                disabled>
                Next <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
            </button>
        </div>
    </div>
</div>

<script src="/<?= PROJECT_DIR ?>/app/views/pages/radtech/xray-patient-records.js"></script>