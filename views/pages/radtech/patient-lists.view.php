<?php
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../app/Models/ResultDisputeModel.php';
$disputeModel = new \ResultDisputeModel($pdo);
$branchId = $_SESSION['branch_id'] ?? 1;
$disputes = $disputeModel->getDisputesForClinic($branchId, 'radtech');
$pendingDisputeCount = count(array_filter($disputes, function ($d) {
    return in_array($d['status'], ['Issue Reported', 'For RadTech Review', 'Pending RadTech Review', 'Correction in Progress', 'Pending RadTech Verification']);
}));
$currentTab = $_GET['tab'] ?? 'completed';
if (!empty($_GET['dispute_id']) || !empty($_GET['highlight_dispute_id'])) {
    $currentTab = 'disputes';
}
$hlTarget = $_GET['highlight'] ?? $_GET['highlight_case'] ?? $_GET['case_number'] ?? $_GET['case_id'] ?? '';
if ($hlTarget && empty($_GET['tab'])) {
    $normTarget = strtolower(str_replace([' ', '-', '_'], '', $hlTarget));
    foreach ($disputes as $d) {
        $cNorm = strtolower(str_replace([' ', '-', '_'], '', $d['case_number'] ?? ''));
        $dIdNorm = (string) ($d['id'] ?? '');
        $cIdNorm = (string) ($d['case_id'] ?? '');
        $pNorm = strtolower(str_replace([' ', '-', '_'], '', $d['patient_number'] ?? ''));
        if ($normTarget === $cNorm || $normTarget === $dIdNorm || $normTarget === $cIdNorm || $normTarget === $pNorm) {
            $currentTab = 'disputes';
            break;
        }
    }
}

/**
 * Patient Queue (Patient List) View
 * Backend logic handled by PatientListsController.php
 */
?>
<style>
    html.theme-dark .priority-badge,
    html.theme-dark .status-badge {
        background-color: transparent !important;
    }

    div:where(.swal2-container),
    .swal2-container {
        z-index: 999999 !important;
    }

    .custom-gray-scroll {
        scrollbar-width: thin;
        scrollbar-color: #cbd5e1 transparent;
    }

    .custom-gray-scroll::-webkit-scrollbar {
        width: 6px;
        height: 6px;
    }

    .custom-gray-scroll::-webkit-scrollbar-track {
        background: transparent;
    }

    .custom-gray-scroll::-webkit-scrollbar-thumb {
        background-color: #cbd5e1;
        border-radius: 9999px;
    }

    .custom-gray-scroll::-webkit-scrollbar-thumb:hover {
        background-color: #94a3b8;
    }
</style>


<!-- Header -->
<div class="flex items-center justify-between">
    <div>
        <h2 class="text-xl font-semibold text-gray-900">Patient List</h2>
        <p class="text-sm text-gray-500 mt-1">Manage patient requests and today's examination queue</p>
    </div>
</div>

<?php if ($successMsg): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof Swal !== 'undefined') {
                const sMsg = <?= json_encode($successMsg) ?>;
                let alertTitle = 'Success!';
                if (sMsg.toLowerCase().includes('released')) {
                    alertTitle = 'Report Released';
                } else if (sMsg.toLowerCase().includes('resolved') || sMsg.toLowerCase().includes('amendment')) {
                    alertTitle = 'Dispute Resolved';
                } else if (sMsg.toLowerCase().includes('regist')) {
                    alertTitle = 'Registration Successful';
                }
                Swal.fire({
                    icon: 'success',
                    title: alertTitle,
                    text: sMsg,
                    showConfirmButton: true,
                    confirmButtonColor: '#10b981',
                    timer: 3500,
                    timerProgressBar: true,
                    customClass: {
                        popup: 'rounded-3xl border-0 shadow-2xl',
                        confirmButton: 'rounded-xl px-6 py-2.5 font-bold text-sm'
                    }
                });
            } else if (typeof toast === 'function') {
                toast(<?= json_encode($successMsg) ?>, 'success');
            }
        });
        setTimeout(() => {
            const el = document.getElementById('flash-success-alert');
            if (el) {
                el.style.opacity = '0';
                el.style.transition = 'opacity 0.5s ease';
                setTimeout(() => el.remove(), 500);
            }
        });
    </script>
<?php endif; ?>

<?php if ($errorMsg): ?>
    <div class="mt-4 rounded-lg bg-red-50 border border-red-300 p-3 flex items-center gap-3">
        <i data-lucide="alert-circle" class="w-5 h-5 text-red-600"></i>
        <p class="text-sm text-red-700 font-medium"><?= htmlspecialchars($errorMsg) ?></p>
    </div>
<?php endif; ?>

<!-- Navigation Tabs -->
<div class="mt-6 border-b border-gray-200">
    <nav class="flex gap-3">
        <a id="tab-btn-queue" href="<?= PROJECT_DIR ? '/' . PROJECT_DIR . '/' : '/' ?>index.php?role=radtech&page=patient-lists"
            class="flex items-center gap-2 px-1 py-3 text-sm font-medium <?= (($_GET['page'] ?? 'patient-lists') === 'patient-lists' && $currentTab !== 'disputes') ? 'text-red-600 border-b-2 border-red-600 hover:text-red-700' : 'text-gray-600 border-b-2 border-transparent hover:text-gray-700 hover:border-gray-300'; ?>">
            Patient Queue
        </a>
        <a id="tab-btn-approval" href="<?= PROJECT_DIR ? '/' . PROJECT_DIR . '/' : '/' ?>index.php?role=radtech&page=patient-approval"
            class="flex items-center gap-2 px-1 py-3 text-sm font-medium <?= ($_GET['page'] ?? 'patient-lists') === 'patient-approval' ? 'text-red-600 border-b-2 border-red-600 hover:text-red-700' : 'text-gray-500 border-b-2 border-transparent hover:text-gray-700 hover:border-gray-300'; ?>">
            Patient Requests
        </a>

        <a id="tab-btn-disputes" href="<?= PROJECT_DIR ? '/' . PROJECT_DIR . '/' : '/' ?>index.php?role=radtech&page=patient-lists&tab=disputes"
            class="flex items-center gap-2 px-1 py-3 text-sm font-medium <?= $currentTab === 'disputes' ? 'text-red-600 border-b-2 border-red-600 hover:text-red-700' : 'text-gray-500 border-b-2 border-transparent hover:text-gray-700 hover:border-gray-300'; ?>">
            Correction Requests
            <?php if ($pendingDisputeCount > 0): ?>
                <span id="radtech-disputes-tab-badge"
                    class="ml-1 tab-circle-badge bg-red-100 text-red-700 border border-red-200"
                    style="width: 26px; height: 26px; min-width: 26px; min-height: 26px; border-radius: 9999px; display: inline-flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 700; line-height: 1; flex-shrink: 0;"
                    title="<?= $pendingDisputeCount ?>">
                    <?= $pendingDisputeCount > 99 ? '99+' : $pendingDisputeCount ?>
                </span>
            <?php endif; ?>
        </a>
    </nav>
</div>

<!-- Content -->
<div id="queue-controls-bar" class="mt-6 flex flex-col gap-4 <?= $currentTab === 'disputes' ? 'hidden' : '' ?>">
    <div class="flex gap-4 items-center">
        <?php $defaultSearch = $_GET['search'] ?? ''; ?>
        <input type="text" id="search-input" placeholder="Search by patient name or case number..."
            value="<?= htmlspecialchars($defaultSearch) ?>"
            class="flex-1 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm text-gray-900 outline-none focus:ring-2 focus:ring-red-500">
        <?php $defaultPriorityFilter = $_GET['filterPriority'] ?? 'All'; ?>
        <select id="filter-priority"
            class="w-40 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm outline-none focus:ring-2 focus:ring-red-500">
            <option value="All" <?= $defaultPriorityFilter === 'All' ? 'selected' : '' ?>>All Priorities</option>
            <option <?= $defaultPriorityFilter === 'Routine' ? 'selected' : '' ?>>Routine</option>
            <option <?= $defaultPriorityFilter === 'Urgent' ? 'selected' : '' ?>>Urgent</option>
            <option <?= $defaultPriorityFilter === 'STAT' ? 'selected' : '' ?>>STAT</option>
        </select>
        <?php $defaultStatusFilter = $_GET['filterStatus'] ?? 'All'; ?>
        <select id="filter-status"
            class="w-40 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm outline-none focus:ring-2 focus:ring-red-500">
            <option value="All" <?= $defaultStatusFilter === 'All' ? 'selected' : '' ?>>All Statuses</option>
            <option value="For Revision" <?= $defaultStatusFilter === 'For Revision' ? 'selected' : '' ?>>For Revision
            </option>
            <option value="Pending" <?= $defaultStatusFilter === 'Pending' ? 'selected' : '' ?>>Pending</option>
            <option value="Under Reading" <?= $defaultStatusFilter === 'Under Reading' ? 'selected' : '' ?>>Under Reading
            </option>
            <option value="Report Ready" <?= $defaultStatusFilter === 'Report Ready' ? 'selected' : '' ?>>Report Ready
            </option>
            <option value="Completed" <?= $defaultStatusFilter === 'Completed' ? 'selected' : '' ?>>Completed</option>
            <option value="Overdue" <?= $defaultStatusFilter === 'Overdue' ? 'selected' : '' ?>>Overdue</option>
        </select>
        <?php $hasHighlight = !empty($_GET['highlight']) || !empty($_GET['highlight_case']) || !empty($_GET['case_id']); ?>
        <?php $defaultDateFilter = $_GET['filterDate'] ?? ($hasHighlight ? 'All' : 'Today'); ?>
        <select id="filter-date"
            class="w-40 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm outline-none focus:ring-2 focus:ring-red-500">
            <option value="All" <?= $defaultDateFilter === 'All' ? 'selected' : '' ?>>All Dates</option>
            <option value="Today" <?= $defaultDateFilter === 'Today' ? 'selected' : '' ?>>Today's Cases</option>
            <option value="Backlog" <?= $defaultDateFilter === 'Backlog' ? 'selected' : '' ?>>Backlogs</option>
        </select>
        <select id="sort-date"
            class="w-40 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm outline-none focus:ring-2 focus:ring-red-500">
            <option>Newest Case</option>
            <option>Oldest Case</option>
        </select>
    </div>
</div>


<div id="queue-table-card"
    class="rounded-xl border border-gray-300 bg-white shadow-sm mt-4 overflow-hidden <?= $currentTab === 'disputes' ? 'hidden' : '' ?>">
    <div class="overflow-x-auto">
        <table class="w-full text-sm ">
            <thead class="sticky top-0 z-10">
                <tr class="border-b border-gray-200 bg-gray-50 text-gray-600">
                    <th class="text-left font-semibold px-3 py-3 whitespace-nowrap">Case No.</th>
                    <th class="text-left font-semibold px-3 py-3 whitespace-nowrap">Patient No.</th>
                    <th class="text-left font-semibold px-3 py-3 truncate max-w-[200px]">Patient Name</th>
                    <th class="text-left font-semibold px-3 py-3 truncate max-w-[150px]">Exam Type</th>
                    <th class="text-left font-semibold px-3 py-3">Priority</th>
                    <th class="text-left font-semibold px-3 py-3">Image</th>
                    <th class="text-left font-semibold px-3 py-3">Status</th>
                    <th class="text-left font-semibold px-3 py-3 min-w-[100px]">Date</th>
                    <th class="text-left font-semibold px-3 py-3 whitespace-nowrap">Actions</th>
                </tr>
            </thead>
            <tbody id="table-body" class="text-gray-800 bg-white divide-y divide-gray-100 realtime-update">
                <?php if (count($patients) === 0): ?>
                    <tr>
                        <td colspan="9" class="text-center py-8 text-gray-500">
                            No active patients found in the Queue.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($patients as $row): ?>
                        <?php
                        // Safeguard: Never render error report / dispute workflow cases in Patient Queue
                        if (in_array($row['status'], ['Issue Reported', 'For RadTech Review', 'Pending RadTech Review', 'Correction in Progress', 'Correction Completed', 'Pending RadTech Verification', 'Resolved'], true)) {
                            continue;
                        }

                        $patFullName = formatFullName($row);
                        $isReportReady = ($row['status'] === 'Report Ready');
                        $isToday = (date('Y-m-d', strtotime($row['created_at'])) === date('Y-m-d'));

                        if (($row['status'] ?? '') === 'For Revision') {
                            $displayStatus = 'For Revision';
                        } elseif ($row['approval_status'] === 'Rejected' || $row['status'] === 'Rejected') {
                            $displayStatus = 'Rejected';
                        } else {
                            $displayStatus = $row['status'];
                        }
                        $isOverdue = (time() - strtotime($row['created_at'])) >= 3 * 3600;
                        if ($displayStatus === 'Pending' && $isOverdue) {
                            $displayStatus = 'Overdue';
                        }

                        $initialDisplay = '';
                        if (!$hasHighlight) {
                            if ($defaultDateFilter === 'Today' && !$isToday)
                                $initialDisplay = 'display: none;';
                            if ($defaultDateFilter === 'Backlog' && $isToday)
                                $initialDisplay = 'display: none;';
                            if ($defaultPriorityFilter !== 'All' && $defaultPriorityFilter !== $row['priority'])
                                $initialDisplay = 'display: none;';
                            if ($defaultStatusFilter !== 'All' && $defaultStatusFilter !== $displayStatus)
                                $initialDisplay = 'display: none;';

                            $sLower = strtolower($defaultSearch);
                            if ($sLower !== '') {
                                $nMatch = strpos(strtolower($patFullName), $sLower) !== false;
                                $cMatch = strpos(strtolower($row['case_number']), $sLower) !== false;
                                $pMatch = strpos(strtolower($row['patient_number'] ?? ''), $sLower) !== false;
                                if (!$nMatch && !$cMatch && !$pMatch) {
                                    $initialDisplay = 'display: none;';
                                }
                            }
                        }
                        ?>
                        <tr class="hover:bg-gray-50 transition-colors record-row" style="<?= $initialDisplay ?>"
                            data-id="<?= htmlspecialchars($row['case_number']) ?>" data-case-id="<?= (int) $row['id'] ?>"
                            data-patient="<?= htmlspecialchars($row['patient_number'] ?? '') ?>"
                            data-name="<?= htmlspecialchars($patFullName) ?>"
                            data-priority="<?= htmlspecialchars($row['priority']) ?>"
                            data-exam="<?= htmlspecialchars($row['exam_type']) ?>"
                            data-date="<?= htmlspecialchars($row['created_at']) ?>"
                            data-status="<?= htmlspecialchars($displayStatus ?: 'Pending') ?>"
                            data-is-today="<?= $isToday ? 'true' : 'false' ?>">
                            <td class="py-3 px-3 font-medium whitespace-nowrap">
                                <?= htmlspecialchars($row['case_number']) ?>
                            </td>
                            <td class="py-3 px-3 font-medium whitespace-nowrap">
                                <?= htmlspecialchars($row['patient_number'] ?? 'N/A') ?>
                            </td>
                            <td class="py-3 px-3 font-medium truncate max-w-[200px]"
                                title="<?= htmlspecialchars($patFullName) ?>">
                                <?= htmlspecialchars($patFullName) ?>
                            </td>
                            <td class="py-3 px-3 max-w-[180px]">
                                <?php
                                $exams = array_filter(array_map('trim', explode(',', $row['exam_type'])));
                                $firstExam = reset($exams);
                                $extraCount = count($exams) - 1;
                                ?>
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <span class="font-medium text-gray-800 truncate max-w-[100px]"
                                        title="<?= htmlspecialchars($row['exam_type']) ?>"><?= htmlspecialchars($firstExam) ?></span>
                                    <?php if ($extraCount > 0): ?>
                                        <span
                                            class="inline-flex items-center rounded-full bg-gray-100 border border-gray-300 px-1.5 py-0.5 text-xs font-semibold text-gray-600 cursor-default flex-shrink-0"
                                            title="<?= htmlspecialchars($row['exam_type']) ?>">+<?= $extraCount ?></span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="py-3 px-3">
                                <?php
                                $pBorder = '1.5px solid #60a5fa';
                                $pBg = '#eff6ff';
                                $pColor = '#1d4ed8';
                                if ($row['priority'] === 'STAT') {
                                    $pBorder = '1.5px solid #f87171';
                                    $pBg = '#fef2f2';
                                    $pColor = '#b91c1c';
                                }
                                if ($row['priority'] === 'Urgent') {
                                    $pBorder = '1.5px solid #facc15';
                                    $pBg = '#fefce8';
                                    $pColor = '#a16207';
                                }
                                if ($row['priority'] === 'Priority') {
                                    $pBorder = '1.5px solid #fb923c';
                                    $pBg = '#fff7ed';
                                    $pColor = '#c2410c';
                                }
                                ?>
                                <span
                                    class="priority-badge inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold"
                                    style="border:<?= $pBorder ?>;background-color:<?= $pBg ?>;color:<?= $pColor ?>">
                                    <?= htmlspecialchars($row['priority']) ?>
                                </span>
                            </td>
                            <td class="py-3 px-3 text-gray-500">
                                <?php if ($row['image_status'] === 'Uploaded'): ?>
                                    <div class="flex items-center gap-1 text-green-500">
                                        <i data-lucide="check" class="w-4 h-4"></i> Uploaded
                                    </div>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                            <td class="py-3 px-3 whitespace-nowrap">
                                <div class="flex flex-col items-start gap-1">
                                    <?php
                                    // $displayStatus logic moved up to row attributes for searchability
                                    $sBorder = '1.5px solid #facc15';
                                    $sBg = '#fefce8';
                                    $sColor = '#a16207';
                                    if ($displayStatus === 'Report Ready') {
                                        $sBorder = '1.5px solid #818cf8';
                                        $sBg = '#eef2ff';
                                        $sColor = '#4338ca';
                                    }
                                    if ($displayStatus === 'Under Reading') {
                                        $sBorder = '1.5px solid #60a5fa';
                                        $sBg = '#eff6ff';
                                        $sColor = '#1d4ed8';
                                    }
                                    if ($displayStatus === 'Completed') {
                                        $sBorder = '1.5px solid #4ade80';
                                        $sBg = '#f0fdf4';
                                        $sColor = '#15803d';
                                    }
                                    if ($displayStatus === 'Rejected' || $displayStatus === 'Overdue' || $displayStatus === 'For Revision') {
                                        $sBorder = '1.5px solid #f87171';
                                        $sBg = '#fef2f2';
                                        $sColor = '#b91c1c';
                                    }
                                    ?>
                                    <span
                                        class="status-badge inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold"
                                        style="border:<?= $sBorder ?>;background-color:<?= $sBg ?>;color:<?= $sColor ?>">
                                        <?= htmlspecialchars($displayStatus ?: 'Pending') ?>
                                    </span>
                                </div>
                            </td>
                            <td class="py-3 px-3 text-gray-500 text-xs whitespace-nowrap">
                                <div class="flex flex-col gap-1 items-start">
                                    <span><?= date('M d, Y', strtotime($row['created_at'])) ?> <br> <span
                                            class="opacity-70"><?= date('h:i A', strtotime($row['created_at'])) ?></span></span>
                                    <?php if (!$isToday): ?>
                                        <span
                                            class="inline-block rounded bg-red-100 px-1.5 py-0.5 text-[10px] font-bold text-red-700 border border-red-200"
                                            title="This case was carried over from a previous day">BACKLOG</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="py-3 px-3 whitespace-nowrap">
                                <div class="flex items-center gap-1.5">
                                    <!-- View button always active -->
                                    <a href="<?= PROJECT_DIR ? '/' . PROJECT_DIR . '/' : '/' ?>index.php?role=radtech&page=patient-details&id=<?= $row['id'] ?>&from=queue"
                                        class="p-1.5 rounded-md border border-blue-500 bg-blue-100 text-blue-600 hover:bg-blue-600 hover:text-white hover:border-blue-600 transition shadow-sm inline-flex items-center justify-center cursor-pointer"
                                        title="View Case">
                                        <i data-lucide="eye" class="w-4 h-4"></i>
                                    </a>

                                    <?php if ($isReportReady): ?>
                                        <?php if (empty($row['released']) || (int) $row['released'] === 0): ?>
                                            <!-- Re-edit — active when Report Ready and not yet released -->
                                            <button type="button" onclick="triggerReEdit(<?= $row['id'] ?>, this, event)"
                                                class="p-1.5 rounded-md border border-amber-500 bg-amber-100 text-amber-600 hover:bg-amber-600 hover:text-white hover:border-amber-600 transition shadow-sm inline-flex items-center justify-center cursor-pointer"
                                                title="Allow Radiologist to Re-edit (Revert to Draft)">
                                                <i data-lucide="edit-3" class="w-4 h-4"></i>
                                            </button>
                                        <?php endif; ?>

                                        <!-- Print Result — active when Report Ready -->
                                        <a href="javascript:void(0)"
                                            onclick="confirmAction('Confirm Print', 'Would you like to confirm printing this report?', '<?= url('print-report?id=' . $row['id']) ?>', 'Yes, Print', true, event)"
                                            class="p-1.5 rounded-md border border-green-500 bg-green-100 text-green-600 hover:bg-green-600 hover:text-white hover:border-green-600 transition shadow-sm inline-flex items-center justify-center cursor-pointer"
                                            title="Print Report">
                                            <i data-lucide="printer" class="w-4 h-4"></i>
                                        </a>

                                        <!-- Release — active when Report Ready -->
                                        <button type="button" onclick="releaseToPhoto(<?= $row['id'] ?>, this, event)"
                                            class="p-1.5 rounded-md border border-red-500 bg-red-100 text-red-600 hover:bg-red-600 hover:text-white hover:border-red-600 transition shadow-sm inline-flex items-center justify-center cursor-pointer"
                                            title="Release Result">
                                            <i data-lucide="send" class="w-4 h-4"></i>
                                        </button>
                                    <?php else: ?>
                                        <!-- Print — disabled -->
                                        <button
                                            class="p-1.5 rounded-md border border-gray-300 bg-gray-50 text-gray-400 cursor-not-allowed opacity-60 shadow-sm inline-flex items-center justify-center"
                                            title="Print Report (Disabled until Radiologist submits report)" disabled>
                                            <i data-lucide="printer" class="w-4 h-4"></i>
                                        </button>

                                        <!-- Release — disabled -->
                                        <span
                                            class="p-1.5 rounded-md border border-gray-300 bg-gray-50 text-gray-400 cursor-not-allowed opacity-60 shadow-sm inline-flex items-center justify-center"
                                            title="Release (Disabled until Radiologist submits report)">
                                            <i data-lucide="send" class="w-4 h-4"></i>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination Controls for Main Table -->
    <div class="flex flex-col sm:flex-row items-center justify-between border-t border-gray-200 bg-gray-50 px-6 py-4 gap-4"
        id="main-pagination-container" style="display: flex;">
        <span id="main-record-count" class="text-xs text-gray-500 font-medium">
            Showing <span id="main-start">0</span> to <span id="main-end">0</span> of <span id="main-total"
                class="font-semibold text-gray-800">0</span> records
        </span>
        <div class="flex items-center flex-wrap gap-1.5" id="main-pagination-controls">
        </div>
    </div>
</div>

<script>
    function getQueueInputs() {
        return {
            searchInput: document.getElementById('search-input'),
            filterPriority: document.getElementById('filter-priority'),
            filterStatus: document.getElementById('filter-status'),
            filterDate: document.getElementById('filter-date'),
            sortDate: document.getElementById('sort-date'),
            tbody: document.getElementById('table-body')
        };
    }

    function saveQueueState() {
        const { searchInput, filterPriority, filterStatus, filterDate, sortDate } = getQueueInputs();
        if (searchInput) sessionStorage.setItem('Citilife_radtechQueue_search', searchInput.value);
        if (filterPriority) sessionStorage.setItem('Citilife_radtechQueue_priority', filterPriority.value);
        if (filterStatus) sessionStorage.setItem('Citilife_radtechQueue_status', filterStatus.value);
        if (filterDate) sessionStorage.setItem('Citilife_radtechQueue_date', filterDate.value);
        if (sortDate) sessionStorage.setItem('Citilife_radtechQueue_sort', sortDate.value);
        sessionStorage.setItem('Citilife_radtechQueue_page', currentMainPage);
    }

    function restoreFiltersFromSession() {
        const { searchInput, filterPriority, filterStatus, filterDate, sortDate } = getQueueInputs();
        const urlParams = new window.URLSearchParams(window.location.search);
        const hasHighlightParam = urlParams.has('highlight') || urlParams.has('highlight_case') || urlParams.has('case_id') || !!pendingQueueHighlight || !!window.__hasExecutedHighlight;

        if (hasHighlightParam) {
            // When navigating from a notification, reset all filters so the target row is guaranteed visible
            if (filterPriority) filterPriority.value = 'All';
            if (filterStatus) filterStatus.value = 'All';
            if (filterDate) filterDate.value = 'All';
            if (searchInput) searchInput.value = '';
            sessionStorage.removeItem('Citilife_radtechQueue_priority');
            sessionStorage.removeItem('Citilife_radtechQueue_status');
            sessionStorage.removeItem('Citilife_radtechQueue_date');
            sessionStorage.removeItem('Citilife_radtechQueue_search');
            return;
        }

        // Priority filter
        if (urlParams.has('filterPriority')) {
            if (filterPriority) filterPriority.value = urlParams.get('filterPriority');
        } else if (filterPriority) {
            const savedPriority = sessionStorage.getItem('Citilife_radtechQueue_priority');
            if (savedPriority) filterPriority.value = savedPriority;
        }

        // Status filter
        if (urlParams.has('filterStatus') || urlParams.has('status')) {
            const sParam = urlParams.get('filterStatus') || urlParams.get('status');
            if (filterStatus) filterStatus.value = sParam;
        } else if (filterStatus) {
            const savedStatus = sessionStorage.getItem('Citilife_radtechQueue_status');
            if (savedStatus) filterStatus.value = savedStatus;
        }

        // Date filter
        if (urlParams.has('filterDate')) {
            if (filterDate) filterDate.value = urlParams.get('filterDate');
        } else if (filterDate) {
            const savedDate = sessionStorage.getItem('Citilife_radtechQueue_date');
            if (savedDate) filterDate.value = savedDate;
        }

        // Search input
        if (urlParams.has('search')) {
            if (searchInput) searchInput.value = urlParams.get('search');
        } else if (searchInput) {
            const savedSearch = sessionStorage.getItem('Citilife_radtechQueue_search');
            if (savedSearch !== null) searchInput.value = savedSearch;
        }

        // Sort date
        if (sortDate) {
            const savedSort = sessionStorage.getItem('Citilife_radtechQueue_sort');
            if (savedSort) sortDate.value = savedSort;
        }

        // Page
        const savedPage = parseInt(sessionStorage.getItem('Citilife_radtechQueue_page'));
        if (savedPage && savedPage > 0) {
            currentMainPage = savedPage;
        }
    }

    document.addEventListener('input', (e) => {
        if (e.target && e.target.id === 'search-input') {
            currentMainPage = 1;
            saveQueueState();
            applyFilters();
        }
    });

    document.addEventListener('change', (e) => {
        if (e.target && (e.target.id === 'filter-priority' || e.target.id === 'filter-status' || e.target.id === 'filter-date' || e.target.id === 'sort-date')) {
            currentMainPage = 1;
            saveQueueState();
            applyFilters();
        }
    });

    function applyFilters(targetTbody = null) {
        const { searchInput, filterPriority, filterStatus, filterDate, sortDate, tbody: defaultTbody } = getQueueInputs();
        const tbody = targetTbody || defaultTbody;
        if (!tbody) return;

        const search = (searchInput?.value || '').toLowerCase().trim();
        const priority = filterPriority?.value || '<?= htmlspecialchars($defaultPriorityFilter) ?>';
        const status = filterStatus?.value || '<?= htmlspecialchars($defaultStatusFilter) ?>';
        const dateFilter = filterDate?.value || '<?= htmlspecialchars($defaultDateFilter) ?>';
        const sort = sortDate?.value || 'Newest Case';

        let rows = Array.from(tbody.querySelectorAll('tr.record-row'));
        let visibleCount = 0;

        // Sort
        if (sort === 'Newest Case' || sort === 'Oldest Case') {
            rows.sort((a, b) => {
                // Priority Weight: STAT > Urgent/Priority > Routine
                const getPriorityWeight = (prio) => {
                    const p = (prio || '').toUpperCase();
                    if (p === 'STAT') return 3;
                    if (p === 'URGENT' || p === 'PRIORITY') return 2;
                    return 1;
                };

                const weightA = getPriorityWeight(a.dataset.priority);
                const weightB = getPriorityWeight(b.dataset.priority);

                // Sort by priority first (highest weight first)
                if (weightA !== weightB) {
                    return weightB - weightA;
                }

                // If same priority, sort by date
                const dateA = new Date(a.dataset.date).getTime();
                const dateB = new Date(b.dataset.date).getTime();
                return sort === 'Newest Case' ? dateB - dateA : dateA - dateB;
            });

            rows.forEach(row => tbody.appendChild(row));
        }

        // Filter
        let matchedRows = [];
        rows.forEach(row => {
            const name = (row.dataset.name || '').toLowerCase();
            const id = (row.dataset.id || '').toLowerCase();
            const patient = (row.dataset.patient || '').toLowerCase();
            const rowPriority = row.dataset.priority || '';
            const rowStatus = (row.dataset.status || '').toLowerCase();
            const isToday = row.dataset.isToday === 'true';

            const matchSearch = !search || name.includes(search) || id.includes(search) || patient.includes(search) || rowPriority.toLowerCase().includes(search) || rowStatus.includes(search);
            const matchPriority = priority === 'Filter by Priority' || priority === 'All' || priority === rowPriority;
            const matchStatus = status === 'Filter by Status' || status === 'All' || rowStatus === status.toLowerCase();

            let matchDate = true;
            if (dateFilter === 'Today') matchDate = isToday;
            if (dateFilter === 'Backlog') matchDate = !isToday;

            if (matchSearch && matchPriority && matchStatus && matchDate) {
                matchedRows.push(row);
                row.setAttribute('data-matched', 'true');
                visibleCount++;
            } else {
                row.style.display = 'none';
                row.removeAttribute('data-matched');
            }
        });

        let emptyMsg = document.getElementById('empty-msg-row');
        if (visibleCount === 0 && rows.length > 0) {
            if (!emptyMsg) {
                emptyMsg = document.createElement('tr');
                emptyMsg.id = 'empty-msg-row';
                emptyMsg.innerHTML = `<td colspan="10" class="text-center py-8 text-gray-500">No records match your filters.</td>`;
                tbody.appendChild(emptyMsg);
            } else {
                emptyMsg.style.display = '';
            }
        } else if (emptyMsg) {
            emptyMsg.style.display = 'none';
        }

        paginateMain(matchedRows, tbody);
    }

    // Main Pagination Logic
    let currentMainPage = 1;
    const mainItemsPerPage = 7;

    function paginateMain(matchedRows, targetTbody = null) {
        const { tbody: defaultTbody } = getQueueInputs();
        const tbody = targetTbody || defaultTbody;
        if (!tbody) return;

        const totalRecords = matchedRows.length;
        let totalPages = Math.ceil(totalRecords / mainItemsPerPage);

        if (currentMainPage > totalPages && totalPages > 0) currentMainPage = totalPages;
        if (totalPages === 0) currentMainPage = 1;

        const start = (currentMainPage - 1) * mainItemsPerPage;
        const end = Math.min(start + mainItemsPerPage, totalRecords);

        // Hide all matched rows first, then only show the ones in the current page
        matchedRows.forEach((row, idx) => {
            row.style.display = (idx >= start && idx < end) ? '' : 'none';
        });

        renderMainPaginationControls(totalPages, totalRecords, start, end);
    }

    function renderMainPaginationControls(totalPages, totalRecords, startIdx, endIdx) {
        const container = document.getElementById('main-pagination-container');
        const controls = document.getElementById('main-pagination-controls');
        const startSpan = document.getElementById('main-start');
        const endSpan = document.getElementById('main-end');
        const totalSpan = document.getElementById('main-total');

        if (!container || !controls) return;

        container.style.display = 'flex';
        controls.innerHTML = '';

        startSpan.innerText = totalRecords > 0 ? startIdx + 1 : 0;
        endSpan.innerText = endIdx;
        totalSpan.innerText = totalRecords;

        function createButton(label, page, disabled, isActive = false) {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.innerHTML = label;

            if (isActive) {
                btn.className = "px-3 py-1.5 rounded-lg bg-red-600 text-xs font-bold text-white shadow-sm border border-red-600";
            } else {
                btn.className = "px-3 py-1.5 rounded-lg border border-gray-300 bg-white text-xs font-semibold text-gray-700 hover:bg-red-50 hover:text-red-600 hover:border-red-200 focus:outline-none focus:ring-2 focus:ring-red-400 transition disabled:opacity-40 disabled:cursor-not-allowed shadow-sm";
            }

            if (disabled) {
                btn.disabled = true;
                if (!isActive) btn.classList.add('opacity-40', 'cursor-not-allowed');
            } else {
                btn.onclick = () => {
                    currentMainPage = page;
                    saveQueueState();
                    applyFilters();
                };
            }
            return btn;
        }

        controls.appendChild(createButton('&lsaquo; Prev', currentMainPage - 1, currentMainPage === 1));

        function createEllipsis() {
            const span = document.createElement('span');
            span.className = "px-2 py-1 text-xs text-gray-400 font-semibold select-none";
            span.innerHTML = "...";
            return span;
        }

        if (totalPages <= 5) {
            for (let i = 1; i <= totalPages; i++) {
                controls.appendChild(createButton(i, i, false, i === currentMainPage));
            }
        } else {
            controls.appendChild(createButton(1, 1, false, 1 === currentMainPage));
            if (currentMainPage > 3) controls.appendChild(createEllipsis());

            let startPage = Math.max(2, currentMainPage - 1);
            let endPage = Math.min(totalPages - 1, currentMainPage + 1);

            if (currentMainPage === 1) endPage = 3;
            if (currentMainPage === totalPages) startPage = totalPages - 2;

            for (let i = startPage; i <= endPage; i++) {
                controls.appendChild(createButton(i, i, false, i === currentMainPage));
            }

            if (currentMainPage < totalPages - 2) controls.appendChild(createEllipsis());
            controls.appendChild(createButton(totalPages, totalPages, false, totalPages === currentMainPage));
        }

        controls.appendChild(createButton('Next &rsaquo;', currentMainPage + 1, currentMainPage >= totalPages));
    }

    let pendingQueueHighlight = new window.URLSearchParams(window.location.search).get('highlight') ||
        new window.URLSearchParams(window.location.search).get('highlight_case') ||
        new window.URLSearchParams(window.location.search).get('case_id');
    let highlightHandled = false;

    function initPatientQueue() {
        const currentTabParam = (new window.URLSearchParams(window.location.search)).get('tab');
        if (currentTabParam === 'disputes') return false;

        const highlightId = pendingQueueHighlight ||
            new window.URLSearchParams(window.location.search).get('highlight') ||
            new window.URLSearchParams(window.location.search).get('highlight_case') ||
            new window.URLSearchParams(window.location.search).get('case_id');

        if (highlightId && !highlightHandled) {
            const { searchInput, filterPriority, filterStatus, filterDate, tbody } = getQueueInputs();
            if (searchInput) searchInput.value = '';
            if (filterPriority) filterPriority.value = 'All';
            if (filterStatus) filterStatus.value = 'All';
            if (filterDate) filterDate.value = 'All';
            applyFilters();

            const rows = Array.from((tbody || document).querySelectorAll('tr.record-row'));
            let targetRow = null;
            const norm = str => (str || '').toLowerCase().replace(/[\s\-_]/g, '');
            const hlNorm = norm(highlightId);

            // 1. Exact match first
            for (const row of rows) {
                const cNum = norm(row.dataset.id || '');
                const cId = norm(row.dataset.caseId || '');
                const pNum = norm(row.dataset.patient || '');
                if (cNum === hlNorm || cId === hlNorm || pNum === hlNorm) {
                    targetRow = row;
                    break;
                }
            }

            // 2. Substring/prefix fallback if not found
            if (!targetRow) {
                for (const row of rows) {
                    const cNum = norm(row.dataset.id || '');
                    const cId = norm(row.dataset.caseId || '');
                    const pNum = norm(row.dataset.patient || '');
                    if ((cNum && hlNorm.includes(cNum)) || (hlNorm && cNum.includes(hlNorm)) ||
                        (pNum && hlNorm.includes(pNum)) || (hlNorm && pNum.includes(hlNorm))) {
                        targetRow = row;
                        break;
                    }
                }
            }

            if (targetRow) {
                highlightHandled = true;
                window.__hasExecutedHighlight = true;

                // Re-query matched rows in current sorted order from tbody
                const matchedRows = Array.from((tbody || document).querySelectorAll('tr.record-row')).filter(r => r.hasAttribute('data-matched'));
                const targetIndex = matchedRows.indexOf(targetRow);
                if (targetIndex !== -1) {
                    currentMainPage = Math.floor(targetIndex / mainItemsPerPage) + 1;
                    saveQueueState();
                    paginateMain(matchedRows);
                }

                targetRow.style.display = '';

                setTimeout(() => {
                    const tableWrapper = targetRow.closest('.overflow-y-auto');
                    if (tableWrapper) {
                        const rowTop = targetRow.offsetTop - tableWrapper.offsetTop;
                        tableWrapper.scrollTo({ top: Math.max(0, rowTop - 40), behavior: 'smooth' });
                    } else {
                        targetRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }

                    targetRow.classList.add('transition-all', 'duration-300', 'ring-2', 'ring-amber-400', 'ring-offset-1');
                    targetRow.style.transition = 'background-color 0.4s ease';
                    targetRow.style.backgroundColor = '#fef08a';
                    setTimeout(() => {
                        targetRow.style.backgroundColor = '#fde047';
                        setTimeout(() => {
                            targetRow.style.backgroundColor = '#fef08a';
                            setTimeout(() => {
                                targetRow.style.backgroundColor = '#fde047';
                                setTimeout(() => {
                                    targetRow.style.transition = 'background-color 1.5s ease';
                                    targetRow.style.backgroundColor = '';
                                    targetRow.classList.remove('ring-2', 'ring-amber-400', 'ring-offset-1');
                                }, 400);
                            }, 300);
                        }, 300);
                    }, 200);
                }, 50);

                // Show standard top banner
                if (typeof window.showHighlightBanner === 'function') {
                    window.showHighlightBanner(highlightId);
                }

                // Clean up URL after successful match
                pendingQueueHighlight = null;
                try {
                    const cleanUrl = new URL(window.location.href);
                    cleanUrl.searchParams.delete('highlight');
                    cleanUrl.searchParams.delete('highlight_case');
                    cleanUrl.searchParams.delete('case_id');
                    cleanUrl.searchParams.delete('is_new');
                    window.history.replaceState({}, document.title, cleanUrl.toString());
                    if (window.__APP__) {
                        window.__APP__.currentPath = cleanUrl.pathname + cleanUrl.search;
                    }
                } catch (e) { }
                return;
            }
        }

        restoreFiltersFromSession();
        applyFilters();
    }

    // Initialize cleanly
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            initPatientQueue();
        });
    } else {
        initPatientQueue();
    }

    window.addEventListener('pageshow', () => {
        if (!highlightHandled) {
            initPatientQueue();
        }
    });

    // Re-apply filters when real-time polling updates the table content
    document.addEventListener('realtime:updated', () => {
        applyFilters();
    });
</script>

<!-- Add html2canvas library for PDF-to-Image rendering -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>



<!-- Prominent Spinning Loader -->
<div id="release-loading-overlay"
    class="fixed inset-0 z-[9999] bg-white/80 dark:bg-slate-900/80 backdrop-blur-sm flex flex-col items-center justify-center hidden">
    <div id="release-spinner-container">
        <div class="animate-spin rounded-full h-16 w-16 border-4 border-red-600 border-t-transparent mb-4"></div>
    </div>
    <div id="release-success-icon" class="hidden mb-4">
        <div
            class="w-16 h-16 rounded-full bg-green-100 dark:bg-green-900/40 text-green-600 dark:text-green-400 flex items-center justify-center shadow-lg shadow-green-500/20">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-9 h-9 stroke-[3]" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="20 6 9 17 4 12"></polyline>
            </svg>
        </div>
    </div>
    <h3 id="release-title-text" class="text-xl font-bold text-gray-800 dark:text-white">Releasing Result</h3>
    <p id="release-status-text" class="text-gray-500 dark:text-gray-400 mt-2 text-center font-medium">Preparing the
        results...</p>
</div>

<script>
    async function triggerReEdit(caseId, btn, event = null) {
        if (event) event.preventDefault();

        let reason = '';
        if (typeof Swal !== 'undefined') {
            const { value: formValues, isConfirmed } = await Swal.fire({
                title: 'Allow Radiologist to Re-edit?',
                html: `
                    <div class="text-left text-sm text-gray-600 mb-3">
                        This will revert the report to draft status (Under Reading) so the radiologist can update findings and impressions.
                    </div>
                    <div class="text-left mb-2">
                        <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">
                            Notes <span class="text-red-500 font-bold">*</span>:
                        </label>
                        <textarea id="swal-reedit-reason" rows="3" class="w-full text-sm border border-gray-300 rounded-xl p-3 transition resize-none font-sans" style="outline: none !important; box-shadow: none !important;" placeholder="Enter notes for the radiologist on what needs to be changed..."></textarea>
                        <p id="swal-reedit-warn" class="hidden text-xs text-red-500 mt-1">Please provide a note for the radiologist.</p>
                    </div>
                `,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, Allow Re-edit',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#d97706',
                cancelButtonColor: '#6b7280',
                customClass: {
                    popup: 'rounded-2xl p-5 max-w-lg font-sans',
                    confirmButton: 'rounded-xl px-5 py-2.5 text-sm font-semibold shadow-sm',
                    cancelButton: 'rounded-xl px-5 py-2.5 text-sm font-semibold'
                },
                didOpen: () => {
                    const ta = document.getElementById('swal-reedit-reason');
                    const warn = document.getElementById('swal-reedit-warn');
                    if (!ta) return;
                    ta.focus();
                    ta.addEventListener('focus', () => {
                        ta.style.borderColor = '#d97706';
                        if (warn) warn.classList.add('hidden');
                    });
                    ta.addEventListener('blur', () => {
                        if (!ta.value.trim()) {
                            ta.style.borderColor = '#ef4444';
                            if (warn) warn.classList.remove('hidden');
                        } else {
                            ta.style.borderColor = '#d1d5db';
                        }
                    });
                    ta.addEventListener('input', () => {
                        if (ta.value.trim()) {
                            ta.style.borderColor = '#d97706';
                            if (warn) warn.classList.add('hidden');
                        }
                    });
                },
                preConfirm: () => {
                    const ta = document.getElementById('swal-reedit-reason');
                    const warn = document.getElementById('swal-reedit-warn');
                    if (ta && !ta.value.trim()) {
                        ta.style.borderColor = '#ef4444';
                        if (warn) warn.classList.remove('hidden');
                        return false;
                    }
                    return ta ? ta.value.trim() : '';
                }
            });

            if (!isConfirmed) return;
            reason = formValues || '';
        } else {
            const promptResult = prompt('Allow Radiologist to Re-edit? Add a note (optional):');
            if (promptResult === null) return;
            reason = promptResult.trim();
        }

        const originalHTML = btn ? btn.innerHTML : '';
        if (btn) {
            btn.disabled = true;
            btn.classList.add('opacity-50', 'cursor-not-allowed');
        }

        try {
            const baseDir = '<?= defined("PROJECT_DIR") && PROJECT_DIR ? "/" . PROJECT_DIR : "" ?>';
            const res = await fetch(`${baseDir}/radtech/re-edit-case`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `case_id=${encodeURIComponent(caseId)}&reason=${encodeURIComponent(reason)}`
            });

            const data = await res.json();
            if (data.success) {
                if (typeof Swal !== 'undefined') {
                    await Swal.fire({
                        icon: 'success',
                        title: 'Reverted to Draft',
                        text: data.message || 'Report reverted to draft successfully.',
                        timer: 1800,
                        showConfirmButton: false,
                        customClass: { popup: 'rounded-2xl' }
                    });
                } else {
                    alert(data.message || 'Report reverted to draft successfully.');
                }
                window.location.reload();
            } else {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Action Failed',
                        text: data.message || 'Could not revert report.',
                        customClass: { popup: 'rounded-2xl' }
                    });
                } else {
                    alert(data.message || 'Could not revert report.');
                }
                if (btn) {
                    btn.disabled = false;
                    btn.classList.remove('opacity-50', 'cursor-not-allowed');
                    btn.innerHTML = originalHTML;
                }
            }
        } catch (err) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Network Error',
                    text: 'A network error occurred. Please try again.',
                    customClass: { popup: 'rounded-2xl' }
                });
            } else {
                alert('A network error occurred. Please try again.');
            }
            if (btn) {
                btn.disabled = false;
                btn.classList.remove('opacity-50', 'cursor-not-allowed');
                btn.innerHTML = originalHTML;
            }
        }
    }

    async function releaseToPhoto(caseId, btn, event = null) {
        if (event) event.preventDefault();
        const confirmed = await confirmAlert('Confirm Release', 'Would you like to confirm releasing this result and moving it to X-ray Patient Records?');
        if (!confirmed.isConfirmed) return;

        const baseDir = '<?= (defined("PROJECT_DIR") && PROJECT_DIR) ? "/" . PROJECT_DIR : "" ?>';
        const overlay = document.getElementById('release-loading-overlay');
        const statusText = document.getElementById('release-status-text');

        // Disable button to prevent double clicks
        const originalHTML = btn.innerHTML;
        btn.disabled = true;
        btn.classList.add('opacity-50', 'cursor-not-allowed');

        // Show refined overlay
        if (overlay) overlay.classList.remove('hidden');
        if (statusText) statusText.textContent = 'Initializing report snapshot...';

        try {
            // 1. Create a hidden iframe to render the perfect print layout
            const iframe = document.createElement('iframe');
            iframe.style.position = 'fixed';
            iframe.style.top = '-10000px';
            iframe.style.left = '-10000px';
            iframe.style.width = '814px'; // Fixed A4 width for predictable rendering
            iframe.style.height = '1200px';
            iframe.style.border = 'none';

            // Use snapshot=1 to skip auto-print in the iframe
            iframe.src = `${baseDir}/print-report?id=${caseId}&no_shadow=1&snapshot=1`;
            document.body.appendChild(iframe);

            iframe.onload = async () => {
                try {
                    const doc = iframe.contentDocument || iframe.contentWindow.document;

                    // Wait briefly for fonts/images
                    await new Promise(r => setTimeout(r, 1000));

                    const pages = doc.querySelectorAll('.report-page');
                    if (!pages.length) throw new Error("No pages found to render.");

                    // CRITICAL FIX: Expand iframe height so it is tall enough to fit ALL pages 
                    // without any internal scrollbars. This prevents html2canvas from clipping!
                    iframe.style.height = (doc.documentElement.scrollHeight + 200) + 'px';

                    // Wait another 500ms after expanding to ensure browser has repainted
                    await new Promise(r => setTimeout(r, 500));

                    let base64Images = [];
                    for (let i = 0; i < pages.length; i++) {
                        const page = pages[i];
                        if (statusText) statusText.textContent = `Processing page ${i + 1} of ${pages.length}...`;

                        const canvas = await html2canvas(page, {
                            scale: pages.length > 5 ? 1.5 : 2, // Slightly lower scale for very large reports to prevent memory issues
                            useCORS: true,
                            backgroundColor: '#ffffff',
                            width: page.scrollWidth,
                            height: page.scrollHeight,
                            windowWidth: doc.documentElement.scrollWidth,
                            windowHeight: doc.documentElement.scrollHeight
                        });

                        // Convert Canvas to JPEG (JPEG is smaller than PNG for documents)
                        const imgData = canvas.toDataURL('image/jpeg', pages.length > 5 ? 0.8 : 0.9);
                        base64Images.push(imgData);
                    }

                    if (statusText) statusText.textContent = 'Uploading consolidated report...';

                    // Submit images to backend
                    const formData = new FormData();
                    formData.append('id', caseId);
                    formData.append('images', JSON.stringify(base64Images));

                    const response = await fetch(`${baseDir}/patient-lists?action=release_and_upload`, {
                        method: 'POST',
                        body: formData
                    });

                    const resText = await response.text();
                    let result;
                    try {
                        result = JSON.parse(resText);
                    } catch (jsonErr) {
                        console.error("Server response was not valid JSON:", resText);
                        throw new Error('Server returned invalid response format: ' + (resText ? resText.substring(0, 100) : 'Empty response'));
                    }

                    if (result.success) {
                        const spinner = document.getElementById('release-spinner-container');
                        const successIcon = document.getElementById('release-success-icon');
                        const titleEl = document.getElementById('release-title-text');
                        const statusTextEl = document.getElementById('release-status-text');

                        if (spinner) spinner.classList.add('hidden');
                        if (successIcon) successIcon.classList.remove('hidden');
                        if (titleEl) {
                            titleEl.textContent = 'Result Released!';
                            titleEl.className = 'text-xl font-bold text-green-600 dark:text-green-400';
                        }
                        if (statusTextEl) {
                            statusTextEl.textContent = 'Case moved to X-ray Patient Records.';
                            statusTextEl.className = 'text-gray-600 dark:text-gray-300 mt-2 text-center font-medium';
                        }

                        await new Promise(r => setTimeout(r, 1200));
                        window.location.reload();
                    } else {
                        throw new Error(result.message || 'Server rejected the upload.');
                    }
                } catch (err) {
                    console.error(err);
                    errorAlert('Generation Failed', err.message);
                    if (overlay) overlay.classList.add('hidden');
                    btn.disabled = false;
                    btn.classList.remove('opacity-50', 'cursor-not-allowed');
                } finally {
                    iframe.remove();
                }
            };
        } catch (e) {
            console.error(e);
            errorAlert('Error', 'An unexpected error occurred during processing.');
            if (overlay) overlay.classList.add('hidden');
            btn.disabled = false;
            btn.classList.remove('opacity-50', 'cursor-not-allowed');
        }
    }
</script>

<!-- Disputes Search and Filter Controls -->
<div class="mt-6 flex flex-col gap-4 <?= $currentTab !== 'disputes' ? 'hidden' : '' ?>" id="disputes-controls-bar">
    <div class="flex gap-4 items-center">
        <input type="text" id="disputes-search-input" placeholder="Search by patient name or case number..."
            class="flex-1 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm text-gray-900 outline-none focus:ring-2 focus:ring-red-500">

        <select id="disputes-filter-category"
            class="w-52 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm outline-none focus:ring-2 focus:ring-red-500">
            <option value="All">All Corrections</option>
            <option value="Patient Info Error">Patient Info Error</option>
            <option value="Typographical Error">Typographical Error</option>
            <option value="Template Rename">Template Rename</option>
            <option value="Info & Rename">Info & Rename</option>
            <option value="Typo & Info">Typo & Info</option>
            <option value="Exam Details Error">Exam Details Error</option>
            <option value="Other Concern">Other Concern</option>
        </select>

        <select id="disputes-sort-date"
            class="w-44 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm outline-none focus:ring-2 focus:ring-red-500">
            <option value="Newest">Newest Request</option>
            <option value="Oldest">Oldest Request</option>
        </select>
    </div>
</div>

<div id="disputes-table-card"
    class="rounded-xl border border-gray-300 bg-white shadow-sm mt-4 overflow-hidden <?= $currentTab !== 'disputes' ? 'hidden' : '' ?>">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200 text-gray-700">
                <tr>
                    <th class="text-left font-semibold px-4 py-3">Case #</th>
                    <th class="text-left font-semibold px-4 py-3">Patient</th>
                    <th class="text-left font-semibold px-4 py-3">Correction Requested</th>
                    <th class="text-left font-semibold px-4 py-3">Status</th>
                    <th class="text-left font-semibold px-4 py-3">Date</th>
                    <th class="text-left font-semibold px-4 py-3">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 realtime-update" id="disputes-table-body">
                <?php if (empty($disputes)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-8 text-gray-500">No correction requests found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($disputes as $d): ?>
                        <?php
                        $fullText = $d['description'] ?? '';
                        $cat = $d['dispute_category'] ?? '';
                        $catLabel = 'Correction Request';
                        $catBadgeClass = 'text-amber-700 bg-amber-50 border-amber-200';
                        $catIcon = 'alert-circle';

                        if ($cat === 'findings_error') {
                            $catLabel = 'Typographical Error';
                            $catBadgeClass = 'text-purple-700 bg-purple-50 border-purple-200';
                            $catIcon = 'file-text';
                        } elseif ($cat === 'demographic_error') {
                            $catLabel = 'Patient Info Error';
                            $catBadgeClass = 'text-sky-700 bg-sky-50 border-sky-200';
                            $catIcon = 'user';
                        } elseif ($cat === 'template_error') {
                            $catLabel = 'Template Rename';
                            $catBadgeClass = 'text-purple-700 bg-purple-50 border-purple-200';
                            $catIcon = 'edit-2';
                        } elseif ($cat === 'both_error') {
                            $catLabel = 'Typo & Info';
                            $catBadgeClass = 'text-rose-700 bg-rose-50 border-rose-200';
                            $catIcon = 'alert-triangle';
                        } elseif ($cat === 'both_template_error') {
                            $catLabel = 'Info & Rename';
                            $catBadgeClass = 'text-indigo-700 bg-indigo-50 border-indigo-200';
                            $catIcon = 'layers';
                        } elseif ($cat === 'other' || $cat === 'other_error') {
                            $catLabel = 'Other Concern';
                            $catBadgeClass = 'text-gray-700 bg-gray-50 border-gray-200';
                            $catIcon = 'help-circle';
                        } elseif ($cat === 'exam_details_error') {
                            $catLabel = 'Exam Details Error';
                            $catBadgeClass = 'text-amber-700 bg-amber-50 border-amber-200';
                            $catIcon = 'clipboard';
                        } else {
                            $descLower = strtolower($fullText);
                            if (strpos($descLower, 'template rename') !== false && (strpos($descLower, 'patient info') !== false || strpos($descLower, 'name') !== false)) {
                                $catLabel = 'Info & Rename';
                                $catBadgeClass = 'text-indigo-700 bg-indigo-50 border-indigo-200';
                                $catIcon = 'layers';
                            } elseif (strpos($descLower, 'template rename') !== false || strpos($descLower, 'correct template') !== false) {
                                $catLabel = 'Template Rename';
                                $catBadgeClass = 'text-purple-700 bg-purple-50 border-purple-200';
                                $catIcon = 'edit-2';
                            } elseif ((strpos($descLower, 'findings') !== false || strpos($descLower, 'typo') !== false) && strpos($descLower, 'patient info') !== false) {
                                $catLabel = 'Typo & Info';
                                $catBadgeClass = 'text-rose-700 bg-rose-50 border-rose-200';
                            } elseif (strpos($descLower, 'findings') !== false || strpos($descLower, 'typo') !== false) {
                                $catLabel = 'Typographical Error';
                                $catBadgeClass = 'text-purple-700 bg-purple-50 border-purple-200';
                                $catIcon = 'file-text';
                            } elseif (strpos($descLower, 'patient info') !== false || strpos($descLower, 'name') !== false) {
                                $catLabel = 'Patient Info Error';
                                $catBadgeClass = 'text-sky-700 bg-sky-50 border-sky-200';
                                $catIcon = 'user';
                            } elseif (strpos($descLower, 'other') !== false) {
                                $catLabel = 'Other Concern';
                                $catBadgeClass = 'text-gray-700 bg-gray-50 border-gray-200';
                                $catIcon = 'help-circle';
                            }
                        }

                        $disputePatName = formatFullName($d);
                        $disputePayload = [
                            'id' => $d['id'],
                            'case_number' => $d['case_number'],
                            'patient_name' => $disputePatName,
                            'patient_number' => $d['patient_number'] ?? '',
                            'description' => $fullText,
                            'status' => $d['status'],
                            'category' => $d['dispute_category'] ?? '',
                            'created_at' => date('M d, Y h:i A', strtotime($d['created_at']))
                        ];
                        $jsonPayload = htmlspecialchars(json_encode($disputePayload), ENT_QUOTES, 'UTF-8');
                        ?>
                        <tr class="hover:bg-gray-50 transition-colors dispute-table-row"
                            data-id="<?= htmlspecialchars($d['case_number']) ?>" data-dispute-id="<?= $d['id'] ?>"
                            data-case="<?= htmlspecialchars($d['case_number']) ?>"
                            data-name="<?= htmlspecialchars($disputePatName) ?>"
                            data-patient-number="<?= htmlspecialchars($d['patient_number'] ?? '') ?>"
                            data-correction="<?= htmlspecialchars($catLabel) ?>"
                            data-date="<?= htmlspecialchars($d['created_at']) ?>"
                            data-timestamp="<?= strtotime($d['created_at']) ?: 0 ?>">
                            <td class="py-3 px-4 font-medium"><?= htmlspecialchars($d['case_number']) ?></td>
                            <td class="py-3 px-4">
                                <div class="font-medium"><?= htmlspecialchars($disputePatName) ?></div>
                                <div class="text-xs text-gray-500"><?= htmlspecialchars($d['patient_number'] ?? '') ?></div>
                            </td>
                            <td class="py-3 px-4 max-w-[220px] align-middle">
                                <div class="flex flex-col items-start gap-1">
                                    <span
                                        class="inline-block text-[11px] font-bold px-2.5 py-0.5 rounded-full border <?= $catBadgeClass ?> shadow-2xs">
                                        <?= htmlspecialchars($catLabel) ?>
                                    </span>
                                    <button type="button" onclick='openDisputeDetailsModal(<?= $jsonPayload ?>)'
                                        class="text-xs text-amber-700 hover:text-amber-900 font-semibold hover:underline cursor-pointer select-none">
                                        See error
                                    </button>
                                </div>
                            </td>
                            <td class="py-3 px-4 whitespace-nowrap">
                                <?php $currStatus = $d['status']; ?>
                                <?php if ($currStatus === 'Issue Reported' || $currStatus === 'Pending RadTech Review'): ?>
                                    <span
                                        class="inline-block text-xs font-semibold text-rose-700 bg-rose-50 border border-rose-200 px-3 py-1 rounded-full">
                                        Issue Reported
                                    </span>
                                <?php elseif ($currStatus === 'For RadTech Review'): ?>
                                    <span
                                        class="inline-block text-xs font-semibold text-amber-700 bg-amber-50 border border-amber-200 px-3 py-1 rounded-full">
                                        For RadTech Review
                                    </span>
                                <?php elseif ($currStatus === 'Correction in Progress'): ?>
                                    <span
                                        class="inline-block text-xs font-semibold text-indigo-700 bg-indigo-50 border border-indigo-200 px-3 py-1 rounded-full">
                                        Correction in Progress
                                    </span>
                                    <?php if (!empty($d['is_amended']) && empty($d['demographics_fixed'])): ?>
                                        <span class="block text-[10px] text-amber-600 font-semibold mt-0.5 whitespace-nowrap">Report Edited · Info Pending</span>
                                    <?php elseif (!empty($d['demographics_fixed']) && empty($d['is_amended'])): ?>
                                        <span class="block text-[10px] text-indigo-600 font-semibold mt-0.5 whitespace-nowrap">Info Fixed · Edit Pending</span>
                                    <?php endif; ?>
                                <?php elseif ($currStatus === 'Correction Completed' || $currStatus === 'Pending RadTech Verification'): ?>
                                    <span
                                        class="inline-block text-xs font-semibold text-blue-700 bg-blue-50 border border-blue-200 px-3 py-1 rounded-full">
                                        Correction Completed
                                    </span>
                                <?php elseif ($currStatus === 'Resolved'): ?>
                                    <span
                                        class="inline-block text-xs font-semibold text-green-700 bg-green-50 border border-green-200 px-3 py-1 rounded-full">
                                        Resolved
                                    </span>
                                <?php else: ?>
                                    <span
                                        class="inline-block text-xs font-semibold text-green-700 bg-green-50 border border-green-200 px-3 py-1 rounded-full">
                                        <?= htmlspecialchars($currStatus) ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="py-3 px-4 text-xs text-gray-500 whitespace-nowrap">
                                <?= date('M d, Y h:i A', strtotime($d['created_at'])) ?>
                            </td>
                            <td class="py-3 px-4 whitespace-nowrap">
                                <?php
                                $currStatus = $d['status'] ?? '';
                                $isResolved = ($currStatus === 'Resolved');
                                $isDemoFixed = !empty($d['demographics_fixed']);
                                $isPendingVerify = in_array($currStatus, ['Correction Completed', 'Pending RadTech Verification']);

                                $hasPatientInfo = ($cat === 'demographic_error' || $cat === 'both_error' || $cat === 'both_template_error' || $catLabel === 'Patient Info Error' || $catLabel === 'Typo & Info' || $catLabel === 'Info & Rename' || stripos($fullText, 'First Name:') !== false || stripos($fullText, 'Last Name:') !== false || stripos($fullText, 'Wrong Patient Info') !== false);
                                $hasFindingsOrTypo = ($cat === 'findings_error' || $cat === 'both_error' || $cat === 'template_error' || $cat === 'both_template_error' || $cat === 'other' || $cat === 'other_error' || $catLabel === 'Typo & Info' || $catLabel === 'Typographical Error' || $catLabel === 'Template Rename' || $catLabel === 'Info & Rename' || $catLabel === 'Other Concern' || stripos($fullText, 'Typographical Error') !== false || stripos($fullText, 'Findings') !== false || stripos($fullText, 'Template Rename') !== false || stripos($fullText, 'Other Concern') !== false);

                                $showDemoBtn = $hasPatientInfo && !$isDemoFixed && !$isResolved && !$isPendingVerify;
                                $showAmendBtn = ($hasFindingsOrTypo || !$hasPatientInfo) && !$isResolved && ($cat !== 'demographic_error') && !$isPendingVerify;

                                $amendBtnTitle = ($cat === 'template_error') ? 'Rename X-ray Template' : (($cat === 'both_template_error') ? 'Patient Info & Rename X-ray Template' : 'Edit / Amend Findings Report');

                                $dispPayload = [
                                    'id' => $d['id'],
                                    'case_id' => $d['case_id'],
                                    'case_number' => $d['case_number'],
                                    'patient_number' => $d['patient_number'] ?? '',
                                    'description' => $fullText,
                                    'first_name' => $d['first_name'] ?? '',
                                    'last_name' => $d['last_name'] ?? '',
                                    'middle_name' => $d['middle_name'] ?? '',
                                    'age' => $d['age'] ?? '',
                                    'sex' => $d['sex'] ?? '',
                                    'user_account_name' => $d['user_account_name'] ?? '',
                                    'category' => $d['dispute_category'] ?? '',
                                    'status' => $d['status'],
                                    'findings' => $d['findings'] ?? '',
                                    'impression' => $d['impression'] ?? '',
                                    'old_findings' => $d['old_findings'] ?? '',
                                    'old_impression' => $d['old_impression'] ?? '',
                                    'exam_type' => $d['exam_type'] ?? ''
                                ];
                                $dispJson = htmlspecialchars(json_encode($dispPayload), ENT_QUOTES, 'UTF-8');
                                ?>

                                <?php if ($isResolved || (!$showDemoBtn && !$showAmendBtn && !$isPendingVerify)): ?>
                                    <span class="text-xs text-gray-400 italic font-normal select-none">No action needed</span>
                                <?php else: ?>
                                    <div class="flex items-center gap-1.5">
                                        <?php if ($isPendingVerify): ?>
                                            <!-- Action: Verify & Release (Blue icon only with tooltip) -->
                                            <button type="button" onclick='openVerifyReleaseModal(<?= $dispJson ?>)'
                                                class="p-1.5 rounded-md border border-blue-500 bg-blue-100 text-blue-600 hover:bg-blue-600 hover:text-white hover:border-blue-600 transition shadow-sm inline-flex items-center justify-center cursor-pointer"
                                                title="Verify &amp; Release Case">
                                                <i data-lucide="check-check" class="w-4 h-4"></i>
                                            </button>
                                        <?php else: ?>
                                            <?php if ($showDemoBtn): ?>
                                                <?php $demoNeedsAction = !empty($d['is_amended']) && empty($d['demographics_fixed']); ?>
                                                <!-- Action for Patient Info: Fix Demographics Modal (Green icon only with tooltip) -->
                                                <button type="button" onclick='openFixDemographicsModal(<?= $dispJson ?>)'
                                                    class="p-1.5 rounded-md border border-green-500 bg-green-100 text-green-600 hover:bg-green-600 hover:text-white hover:border-green-600 transition shadow-sm inline-flex items-center justify-center cursor-pointer relative"
                                                    title="<?= $demoNeedsAction ? 'Fix Patient Information to Resolve Request' : 'Fix &amp; Resolve Patient Information' ?>">
                                                    <i data-lucide="clipboard-check" class="w-4 h-4"></i>
                                                    <?php if ($demoNeedsAction): ?>
                                                        <span class="absolute -top-1 -right-1 flex h-2 w-2">
                                                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                                                            <span class="relative inline-flex rounded-full h-2 w-2 bg-green-600"></span>
                                                        </span>
                                                    <?php endif; ?>
                                                </button>
                                            <?php endif; ?>

                                            <?php if ($showAmendBtn): ?>
                                                <!-- Action for Typo / Template / Findings: Edit / Amend Mode (Amber icon only with tooltip) -->
                                                <a href="<?= url('patient-details?role=radtech&id=' . (int) $d['case_id'] . '&from=disputes&dispute_id=' . (int) $d['id']) ?>"
                                                    class="p-1.5 rounded-md border border-amber-500 bg-amber-100 text-amber-600 hover:bg-amber-600 hover:text-white hover:border-amber-600 transition shadow-sm inline-flex items-center justify-center cursor-pointer"
                                                    title="<?= !empty($d['is_amended']) ? 'Report Already Edited (Click to re-edit if needed)' : htmlspecialchars($amendBtnTitle) ?>">
                                                    <i data-lucide="edit-3" class="w-4 h-4"></i>
                                                </a>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination Controls for Disputes -->
    <div class="flex flex-col sm:flex-row items-center justify-between border-t border-gray-200 bg-gray-50 px-6 py-4 gap-4"
        id="disputes-pagination-container" style="display: flex;">
        <span id="disputes-record-count" class="text-xs text-gray-500 font-medium">
            Showing <span id="disputes-start">0</span> to <span id="disputes-end">0</span> of <span id="disputes-total"
                class="font-semibold text-gray-800">0</span> records
        </span>
        <div class="flex items-center flex-wrap gap-1.5" id="disputes-pagination-controls">
        </div>
    </div>
</div>

<script>
    // Disputes Pagination, Search, and Filter Logic
    let currentDisputesPage = 1;
    const disputesItemsPerPage = 7;

    function applyDisputesFilter(targetTbody = null) {
        const tbody = targetTbody || document.getElementById('disputes-table-body');
        if (!tbody) return;

        const searchInput = document.getElementById('disputes-search-input');
        const filterCat = document.getElementById('disputes-filter-category');
        const sortDate = document.getElementById('disputes-sort-date');

        const searchVal = (searchInput?.value || '').toLowerCase().trim();
        const catVal = filterCat?.value || 'All';
        const sortVal = sortDate?.value || 'Newest';

        let rows = Array.from(tbody.querySelectorAll('tr.dispute-table-row'));
        if (rows.length === 0) {
            renderDisputesPaginationControls(0, 0, 0, 0, []);
            return;
        }

        // 1. Sort rows by date (Newest / Oldest)
        rows.sort((a, b) => {
            const timeA = parseInt(a.dataset.timestamp || '0', 10);
            const timeB = parseInt(b.dataset.timestamp || '0', 10);
            return sortVal === 'Newest' ? timeB - timeA : timeA - timeB;
        });
        rows.forEach(r => tbody.appendChild(r));

        // 2. Filter rows
        let matchedRows = [];
        rows.forEach(row => {
            const caseNum = (row.dataset.case || row.dataset.id || '').toLowerCase();
            const patientName = (row.dataset.name || '').toLowerCase();
            const patientNum = (row.dataset.patientNumber || '').toLowerCase();
            const correctionType = (row.dataset.correction || '').trim();

            const matchesSearch = !searchVal ||
                caseNum.includes(searchVal) ||
                patientName.includes(searchVal) ||
                patientNum.includes(searchVal);
            const matchesCategory = (catVal === 'All') || (correctionType === catVal);

            if (matchesSearch && matchesCategory) {
                matchedRows.push(row);
            } else {
                row.style.display = 'none';
            }
        });

        // 3. Handle empty state row if no matched results
        let emptyFilterRow = tbody.querySelector('#disputes-empty-filter-row');
        if (matchedRows.length === 0) {
            if (!emptyFilterRow) {
                emptyFilterRow = document.createElement('tr');
                emptyFilterRow.id = 'disputes-empty-filter-row';
                emptyFilterRow.className = 'disputes-empty-filter-row';
                emptyFilterRow.innerHTML = '<td colspan="6" class="text-center py-8 text-gray-500">No matching correction requests found.</td>';
                tbody.appendChild(emptyFilterRow);
            }
            emptyFilterRow.style.display = '';
        } else {
            if (emptyFilterRow) emptyFilterRow.style.display = 'none';
        }

        // 4. Paginate matched rows
        paginateDisputesFiltered(matchedRows, tbody);
    }

    function paginateDisputesFiltered(matchedRows, targetTbody = null) {
        const totalRecords = matchedRows.length;
        let totalPages = Math.ceil(totalRecords / disputesItemsPerPage);

        if (currentDisputesPage > totalPages && totalPages > 0) currentDisputesPage = totalPages;
        if (totalPages === 0) currentDisputesPage = 1;

        const start = (currentDisputesPage - 1) * disputesItemsPerPage;
        const end = Math.min(start + disputesItemsPerPage, totalRecords);

        matchedRows.forEach((row, idx) => {
            row.style.display = (idx >= start && idx < end) ? '' : 'none';
        });

        renderDisputesPaginationControls(totalPages, totalRecords, start, end, matchedRows);
    }

    function paginateDisputes(targetTbody = null) {
        applyDisputesFilter(targetTbody);
    }

    function renderDisputesPaginationControls(totalPages, totalRecords, startIdx, endIdx, matchedRows = null) {
        const container = document.getElementById('disputes-pagination-container');
        const controls = document.getElementById('disputes-pagination-controls');
        const startSpan = document.getElementById('disputes-start');
        const endSpan = document.getElementById('disputes-end');
        const totalSpan = document.getElementById('disputes-total');

        if (!container || !controls) return;

        container.style.display = 'flex';
        controls.innerHTML = '';

        startSpan.innerText = totalRecords > 0 ? startIdx + 1 : 0;
        endSpan.innerText = endIdx;
        totalSpan.innerText = totalRecords;

        function createButton(label, page, disabled, isActive = false) {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.innerHTML = label;

            if (isActive) {
                btn.className = "px-3 py-1.5 rounded-lg bg-red-600 text-xs font-bold text-white shadow-sm border border-red-600";
            } else {
                btn.className = "px-3 py-1.5 rounded-lg border border-gray-300 bg-white text-xs font-semibold text-gray-700 hover:bg-red-50 hover:text-red-600 hover:border-red-200 focus:outline-none focus:ring-2 focus:ring-red-400 transition disabled:opacity-40 disabled:cursor-not-allowed shadow-sm";
            }

            if (disabled) {
                btn.disabled = true;
            } else {
                btn.onclick = () => {
                    currentDisputesPage = page;
                    try { sessionStorage.setItem('radtech_disputes_page', page); } catch (e) { }
                    if (matchedRows) {
                        paginateDisputesFiltered(matchedRows);
                    } else {
                        paginateDisputes();
                    }
                    const tableContainer = document.querySelector('#disputes-table-card .overflow-x-auto');
                    if (tableContainer) tableContainer.scrollTo({ top: 0, behavior: 'smooth' });
                };
            }
            return btn;
        }

        function createEllipsis() {
            const span = document.createElement('span');
            span.className = "px-2 py-1 text-xs text-gray-400 font-semibold select-none";
            span.innerHTML = "...";
            return span;
        }

        controls.appendChild(createButton('&lsaquo; Back', currentDisputesPage - 1, currentDisputesPage === 1));

        if (totalPages <= 5) {
            for (let i = 1; i <= totalPages; i++) {
                controls.appendChild(createButton(i, i, false, i === currentDisputesPage));
            }
        } else {
            controls.appendChild(createButton(1, 1, false, 1 === currentDisputesPage));
            if (currentDisputesPage > 3) controls.appendChild(createEllipsis());

            let startPage = Math.max(2, currentDisputesPage - 1);
            let endPage = Math.min(totalPages - 1, currentDisputesPage + 1);

            if (currentDisputesPage === 1) endPage = 3;
            if (currentDisputesPage === totalPages) startPage = totalPages - 2;

            for (let i = startPage; i <= endPage; i++) {
                controls.appendChild(createButton(i, i, false, i === currentDisputesPage));
            }

            if (currentDisputesPage < totalPages - 2) controls.appendChild(createEllipsis());
            controls.appendChild(createButton(totalPages, totalPages, false, totalPages === currentDisputesPage));
        }

        controls.appendChild(createButton('Next &rsaquo;', currentDisputesPage + 1, currentDisputesPage >= totalPages));
    }

    // Event listeners for disputes search, filter, and sorting
    document.addEventListener('input', (e) => {
        if (e.target && e.target.id === 'disputes-search-input') {
            currentDisputesPage = 1;
            applyDisputesFilter();
        }
    });

    document.addEventListener('change', (e) => {
        if (e.target && (e.target.id === 'disputes-filter-category' || e.target.id === 'disputes-sort-date')) {
            currentDisputesPage = 1;
            applyDisputesFilter();
        }
    });

    let disputesHighlightHandled = false;

    function handleDisputesHighlight(passedTarget = null) {
        const params = new URLSearchParams(window.location.search);
        if (params.get('tab') !== 'disputes') return false;
        if (disputesHighlightHandled) return true;

        // Prioritize specific case number or explicit highlight first over generic numeric dispute ID
        const targetVal = passedTarget ||
            params.get('highlight_case') ||
            params.get('highlight') ||
            params.get('case_number') ||
            params.get('highlight_dispute_id') ||
            params.get('dispute_id');

        if (!targetVal) return false;

        // Ensure filters/sorting are active so matched rows are in expected order
        applyDisputesFilter();

        const rows = Array.from(document.querySelectorAll('#disputes-table-body tr.dispute-table-row'));
        let targetRow = null;
        const norm = str => (str || '').toLowerCase().replace(/[\s\-_]/g, '');
        const targetNorm = norm(targetVal);

        // 1. Strict exact match on Case Number (e.g. GAP2026-00287)
        for (const r of rows) {
            const cCase = norm(r.dataset.case || r.dataset.id || '');
            if (cCase === targetNorm) {
                targetRow = r;
                break;
            }
        }

        // 2. Strict exact match on Dispute ID (e.g. 68)
        if (!targetRow) {
            for (const r of rows) {
                const dId = norm(r.dataset.disputeId || '');
                if (dId === targetNorm) {
                    targetRow = r;
                    break;
                }
            }
        }

        // 3. Strict exact match on Patient Number
        if (!targetRow) {
            for (const r of rows) {
                const pNum = norm(r.dataset.patientNumber || '');
                if (pNum === targetNorm) {
                    targetRow = r;
                    break;
                }
            }
        }

        if (targetRow) {
            disputesHighlightHandled = true;

            // Clear any lingering highlights on ALL rows so only 1 row is ever highlighted
            rows.forEach(r => {
                r.classList.remove('ring-2', 'ring-amber-400', 'ring-offset-1');
                r.style.backgroundColor = '';
            });

            // Find targetRow in the currently displayed / matched rows
            const matchedRows = rows.filter(r => r.style.display !== 'none' || r === targetRow);
            const rowIndex = matchedRows.indexOf(targetRow);
            if (rowIndex !== -1) {
                currentDisputesPage = Math.floor(rowIndex / disputesItemsPerPage) + 1;
                try { sessionStorage.setItem('radtech_disputes_page', currentDisputesPage); } catch (e) { }
                paginateDisputesFiltered(matchedRows);
            }

            if (typeof window.showHighlightBanner === 'function') {
                const friendlyLabel = targetRow.dataset.case || targetRow.dataset.id || targetVal;
                window.showHighlightBanner(targetVal, friendlyLabel);
            }

            setTimeout(() => {
                targetRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
                targetRow.classList.add('transition-all', 'duration-300', 'ring-2', 'ring-amber-400', 'ring-offset-1');
                targetRow.style.backgroundColor = '#fef08a';

                let flashCount = 0;
                const flashInterval = setInterval(() => {
                    flashCount++;
                    targetRow.style.backgroundColor = (flashCount % 2 === 1) ? '#fde047' : '#fef08a';
                    if (flashCount >= 6) {
                        clearInterval(flashInterval);
                        setTimeout(() => {
                            targetRow.style.transition = 'background-color 2s ease, box-shadow 2s ease';
                            targetRow.style.backgroundColor = '';
                            targetRow.classList.remove('ring-2', 'ring-amber-400', 'ring-offset-1');
                        }, 1200);
                    }
                }, 250);

                try {
                    const cleanUrl = new URL(window.location.href);
                    cleanUrl.searchParams.delete('dispute_id');
                    cleanUrl.searchParams.delete('highlight_dispute_id');
                    cleanUrl.searchParams.delete('highlight_case');
                    cleanUrl.searchParams.delete('highlight');
                    cleanUrl.searchParams.delete('is_new');
                    window.history.replaceState({}, document.title, cleanUrl.toString());
                } catch (e) { }
            }, 150);

            return true;
        }
        return false;
    }

    function switchTab(tab) {
        const queueControls = document.getElementById('queue-controls-bar');
        const queueCard = document.getElementById('queue-table-card');
        const disputesControls = document.getElementById('disputes-controls-bar');
        const disputesCard = document.getElementById('disputes-table-card');
        const tabBtnQueue = document.getElementById('tab-btn-queue');
        const tabBtnDisputes = document.getElementById('tab-btn-disputes');

        if (tab === 'disputes') {
            if (queueControls) queueControls.classList.add('hidden');
            if (queueCard) queueCard.classList.add('hidden');
            if (disputesControls) disputesControls.classList.remove('hidden');
            if (disputesCard) disputesCard.classList.remove('hidden');
            if (tabBtnQueue) {
                tabBtnQueue.className = 'flex items-center gap-2 px-1 py-3 text-sm font-medium text-gray-600 border-b-2 border-transparent hover:text-gray-700 hover:border-gray-300';
            }
            if (tabBtnDisputes) {
                tabBtnDisputes.className = 'flex items-center gap-2 px-1 py-3 text-sm font-medium text-red-600 border-b-2 border-red-600 hover:text-red-700';
            }
            try {
                const cleanUrl = new URL(window.location.href);
                cleanUrl.searchParams.set('tab', 'disputes');
                window.history.replaceState({}, document.title, cleanUrl.toString());
            } catch (e) {}
        } else {
            if (queueControls) queueControls.classList.remove('hidden');
            if (queueCard) queueCard.classList.remove('hidden');
            if (disputesControls) disputesControls.classList.add('hidden');
            if (disputesCard) disputesCard.classList.add('hidden');
            if (tabBtnQueue) {
                tabBtnQueue.className = 'flex items-center gap-2 px-1 py-3 text-sm font-medium text-red-600 border-b-2 border-red-600 hover:text-red-700';
            }
            if (tabBtnDisputes) {
                tabBtnDisputes.className = 'flex items-center gap-2 px-1 py-3 text-sm font-medium text-gray-500 border-b-2 border-transparent hover:text-gray-700 hover:border-gray-300';
            }
            try {
                const cleanUrl = new URL(window.location.href);
                cleanUrl.searchParams.delete('tab');
                window.history.replaceState({}, document.title, cleanUrl.toString());
            } catch (e) {}
        }
    }
    window.switchTab = switchTab;

    window.handlePageHighlight = function (targetId) {
        const isDisputes = (new window.URLSearchParams(window.location.search)).get('tab') === 'disputes';
        const norm = str => (str || '').toLowerCase().replace(/[\s\-_]/g, '');
        const tNorm = norm(targetId);

        if (isDisputes) {
            if (handleDisputesHighlight(targetId)) return true;
            // Cross-tab fallback: check if target is in Patient Queue
            const queueRows = Array.from(document.querySelectorAll('#table-body tr.record-row'));
            const matchQueue = queueRows.some(r => norm(r.dataset.id) === tNorm || norm(r.dataset.caseId) === tNorm || norm(r.dataset.patient) === tNorm);
            if (matchQueue) {
                switchTab('queue');
                return initPatientQueue(targetId);
            }
            return false;
        } else {
            // Check if target is in Patient Queue first
            if (initPatientQueue(targetId)) return true;
            // Cross-tab fallback: check if target is in Correction Requests
            const disputeRows = Array.from(document.querySelectorAll('#disputes-table-body tr.dispute-table-row'));
            const matchDispute = disputeRows.some(r => norm(r.dataset.case) === tNorm || norm(r.dataset.id) === tNorm || norm(r.dataset.disputeId) === tNorm || norm(r.dataset.patientNumber) === tNorm);
            if (matchDispute) {
                switchTab('disputes');
                return handleDisputesHighlight(targetId);
            }
            return false;
        }
    };

    document.addEventListener('DOMContentLoaded', () => {
        try {
            sessionStorage.setItem('radtech_last_table_url', window.location.href);
            const isDisputesTab = (new URLSearchParams(window.location.search)).get('tab') === 'disputes';
            const hasHighlight = (new URLSearchParams(window.location.search)).has('highlight') ||
                (new URLSearchParams(window.location.search)).has('highlight_case') ||
                (new URLSearchParams(window.location.search)).has('dispute_id');

            if (isDisputesTab && !hasHighlight) {
                const savedPage = parseInt(sessionStorage.getItem('radtech_disputes_page') || '1', 10);
                if (savedPage > 1) {
                    currentDisputesPage = savedPage;
                }
            } else if (hasHighlight) {
                currentDisputesPage = 1;
            }
        } catch (e) { }

        setTimeout(() => {
            paginateDisputes();
            handleDisputesHighlight();
        }, 100);
    });

    function formatDisputeDescriptionHtml(desc, scope = 'all') {
        if (!desc) return '<span class="text-xs text-gray-400 italic">No description provided.</span>';

        const clean = desc.replace(/\r\n/g, '\n').trim();
        let findings = '';
        let demographics = '';
        let templateRename = '';
        let other = '';

        const findingsMatch = clean.match(/Findings Note:\s*([\s\S]*?)(?=(Wrong Patient Info:|Exam Details Note:|Demographics Note:|Template Rename Request:|$))/i);
        if (findingsMatch && findingsMatch[1].trim()) {
            findings = findingsMatch[1].trim().replace(/^•\s*/gm, '').trim();
        }

        const demoMatch = clean.match(/(?:Wrong Patient Info:|Demographics Note:)\s*([\s\S]*?)(?=(Findings Note:|Exam Details Note:|Template Rename Request:|$))/i);
        if (demoMatch && demoMatch[1].trim()) {
            demographics = demoMatch[1].trim().replace(/^•\s*/gm, '').trim();
        }

        const tplMatch = clean.match(/(?:Template Rename Request:|Exam Details Note:)\s*([\s\S]*?)(?=(Findings Note:|Wrong Patient Info:|Demographics Note:|Other Concern Note:|$))/i);
        if (tplMatch && tplMatch[1].trim()) {
            templateRename = tplMatch[1].trim().replace(/^•\s*/gm, '').trim();
        }

        if (!findings && !demographics && !templateRename) {
            other = clean;
        }

        let html = '<div class="space-y-1.5">';

        if (findings && (scope === 'all' || scope === 'findings')) {
            html += `
                <div class="flex items-start gap-2 text-xs py-0.5">
                    <span class="font-bold text-gray-800 shrink-0 uppercase text-[10px] bg-gray-100 border border-gray-300 px-1.5 py-0.5 rounded">Findings</span>
                    <span class="text-gray-800 font-medium leading-relaxed">${findings}</span>
                </div>`;
        }

        if (demographics && (scope === 'all' || scope === 'demographics')) {
            const badges = demographics.split(/,|\n/).map(s => s.trim().replace(/^•\s*/, '')).filter(Boolean);
            html += `
                <div class="flex items-center gap-2 text-xs py-0.5">
                    <span class="font-bold text-blue-800 shrink-0 uppercase text-[10px] bg-blue-100 border border-blue-200 px-1.5 py-0.5 rounded">Patient Info</span>
                    <div class="flex flex-wrap gap-1">
                        ${badges.map(b => `<span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-semibold bg-blue-50 text-blue-800 border border-blue-200">${b}</span>`).join('')}
                    </div>
                </div>`;
        }

        if (templateRename && (scope === 'all' || scope === 'template')) {
            html += `
                <div class="flex items-start gap-2 text-xs py-0.5">
                    <span class="font-bold text-purple-800 shrink-0 uppercase text-[10px] bg-purple-100 border border-purple-200 px-1.5 py-0.5 rounded">Template Rename</span>
                    <span class="text-gray-800 font-medium leading-relaxed">${templateRename}</span>
                </div>`;
        }

        if (other && (!findings || scope !== 'demographics') && (!demographics || scope !== 'findings')) {
            html += `<div class="text-xs text-gray-800 font-medium whitespace-pre-line leading-relaxed py-0.5">${other.trim()}</div>`;
        }

        html += '</div>';
        return html;
    }

    function openDisputeDetailsModal(data) {
        if (!data) return;

        // Subtitle with Case # and Patient Name
        const subEl = document.getElementById('ddm-subtitle');
        if (subEl) {
            subEl.innerHTML = `Case <span class="font-bold text-gray-800">#${data.case_number}</span>` +
                (data.patient_name ? ` &bull; <span class="font-semibold text-gray-700">${data.patient_name}</span>` : '') +
                (data.patient_number ? ` <span class="text-gray-400">(${data.patient_number})</span>` : '');
        }

        // Meta Bar (Status & Date)
        const metaBar = document.getElementById('ddm-meta-bar');
        if (metaBar) {
            let statusBadge = '';
            const st = data.status || '';
            if (st === 'Pending RadTech Review' || st === 'For RadTech Review' || st === 'Issue Reported') {
                statusBadge = '<span class="inline-block font-bold text-amber-700 bg-amber-50 border border-amber-200 px-3 py-1 rounded-full text-[11px] shadow-2xs">For RadTech Review</span>';
            } else if (st === 'Correction in Progress' || st === 'Escalated to Radiologist') {
                statusBadge = '<span class="inline-block font-bold text-indigo-700 bg-indigo-50 border border-indigo-200 px-3 py-1 rounded-full text-[11px] shadow-2xs">Correction in Progress</span>';
            } else if (st === 'Correction Completed' || st === 'Pending RadTech Verification') {
                statusBadge = '<span class="inline-block font-bold text-blue-700 bg-blue-50 border border-blue-200 px-3 py-1 rounded-full text-[11px] shadow-2xs">Correction Completed</span>';
            } else {
                statusBadge = `<span class="inline-block font-bold text-green-700 bg-green-50 border border-green-200 px-3 py-1 rounded-full text-[11px] shadow-2xs">${st || 'Resolved'}</span>`;
            }

            const dateStr = data.created_at || '';
            metaBar.innerHTML = `
                <div class="flex items-center gap-2">
                    <span class="font-semibold text-gray-500">Status:</span>
                    ${statusBadge}
                </div>
                ${dateStr ? `<span class="text-[11px] text-gray-500 font-medium bg-white px-2.5 py-1 rounded-lg border border-gray-200 shadow-2xs flex items-center gap-1"><i data-lucide="calendar" class="w-3 h-3 text-gray-400"></i> ${dateStr}</span>` : ''}
            `;
        }

        // Details Breakdown Cards
        const container = document.getElementById('ddm-content-container');
        if (container) {
            const desc = (data.description || '').replace(/\r\n/g, '\n').trim();
            let findings = '';
            let demographics = '';
            let templateRename = '';
            let other = '';

            const findingsMatch = desc.match(/(?:Findings Note:|Typographical Error Note:)\s*([\s\S]*?)(?=(Wrong Patient Info:|Exam Details Note:|Demographics Note:|Other Concern Note:|Template Rename Request:|$))/i);
            if (findingsMatch && findingsMatch[1].trim()) {
                findings = findingsMatch[1].trim().replace(/^•\s*/gm, '').trim();
            }

            const demoMatch = desc.match(/(?:Wrong Patient Info:|Demographics Note:)\s*([\s\S]*?)(?=(Findings Note:|Typographical Error Note:|Exam Details Note:|Other Concern Note:|Template Rename Request:|$))/i);
            if (demoMatch && demoMatch[1].trim()) {
                demographics = demoMatch[1].trim().replace(/^•\s*/gm, '').trim();
            }

            const templateMatch = desc.match(/(?:Template Rename Request:|Exam Details Note:)\s*([\s\S]*?)(?=(Findings Note:|Typographical Error Note:|Wrong Patient Info:|Demographics Note:|Other Concern Note:|$))/i);
            if (templateMatch && templateMatch[1].trim()) {
                templateRename = templateMatch[1].trim();
            }

            const otherMatch = desc.match(/(?:Other Concern Note:|Other Note:)\s*([\s\S]*?)(?=(Findings Note:|Typographical Error Note:|Wrong Patient Info:|Demographics Note:|Exam Details Note:|Template Rename Request:|$))/i);
            if (otherMatch && otherMatch[1].trim()) {
                other = otherMatch[1].trim().replace(/^•\s*/gm, '').trim();
            }

            if (!findings && !demographics && !templateRename && !other) {
                other = desc;
            }

            let cardsHtml = '';

            if (demographics) {
                const badges = demographics.split(/,|\n/).map(s => s.trim().replace(/^•\s*/, '')).filter(Boolean);
                cardsHtml += `
                    <div class="p-4 bg-sky-50/80 border border-sky-200 rounded-xl space-y-2.5 shadow-2xs">
                        <div class="flex items-center gap-2 text-xs font-bold text-sky-900 uppercase tracking-wider">
                            <i data-lucide="user-x" class="w-4 h-4 text-sky-600"></i>
                            <span>Reported Incorrect Patient Info:</span>
                        </div>
                        <div class="flex flex-wrap gap-1.5 pt-0.5">
                            ${badges.map(b => `
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-bold bg-white text-sky-800 border border-sky-300 shadow-2xs">
                                    <i data-lucide="tag" class="w-3.5 h-3.5 text-sky-500"></i>
                                    ${b}
                                </span>
                            `).join('')}
                        </div>
                        <p class="text-[11px] text-sky-700 leading-relaxed pt-0.5 font-normal">
                            The patient reported incorrect personal details (e.g., name, age, or sex). Click the green <strong>Fix Patient Information</strong> button in the Actions column to update and correct these details.
                        </p>
                    </div>
                `;
            }

            if (templateRename) {
                cardsHtml += `
                    <div class="p-4 bg-purple-50/80 border border-purple-200 rounded-xl space-y-2 shadow-2xs">
                        <div class="flex items-center gap-2 text-xs font-bold text-purple-900 uppercase tracking-wider">
                            <i data-lucide="file-edit" class="w-4 h-4 text-purple-700"></i>
                            <span>Template Rename Request:</span>
                        </div>
                        <div class="p-3 bg-white border border-purple-200/80 rounded-lg text-xs text-gray-800 font-medium leading-relaxed whitespace-pre-line shadow-2xs">${templateRename.trim()}</div>
                        <p class="text-[11px] text-purple-700 leading-relaxed font-normal">
                            The patient requested to correct the exam title or template on the printed result. Click the amber <strong>Edit / Amend Report</strong> button to update it.
                        </p>
                    </div>
                `;
            }

            if (findings) {
                cardsHtml += `
                    <div class="p-4 bg-gray-50 border border-gray-200 rounded-xl space-y-2 shadow-2xs">
                        <div class="flex items-center gap-2 text-xs font-bold text-gray-900 uppercase tracking-wider">
                            <i data-lucide="file-text" class="w-4 h-4 text-gray-800"></i>
                            <span>Reported Typographical / Findings Error:</span>
                        </div>
                        <div class="p-3 bg-white border border-gray-200 rounded-lg text-xs text-gray-900 font-medium leading-relaxed whitespace-pre-line shadow-2xs">${findings.trim()}</div>
                        <p class="text-[11px] text-gray-700 leading-relaxed font-normal">
                            The patient reported a typographical or text error in the findings/impression. Click the amber <strong>Edit / Amend Report</strong> button to correct the report.
                        </p>
                    </div>
                `;
            }

            if (other) {
                cardsHtml += `
                    <div class="p-4 bg-gray-50 border border-gray-200 rounded-xl space-y-2 shadow-2xs">
                        <div class="flex items-center gap-2 text-xs font-bold text-gray-700 uppercase tracking-wider">
                            <i data-lucide="message-square" class="w-4 h-4 text-gray-500"></i>
                            <span>Reported Concern / Error Details:</span>
                        </div>
                        <div class="p-3 bg-white border border-gray-200 rounded-lg text-xs text-gray-800 font-medium leading-relaxed whitespace-pre-line shadow-2xs">${other.trim()}</div>
                    </div>
                `;
            }

            if (!cardsHtml) {
                cardsHtml = '<div class="p-4 bg-gray-50 border border-gray-200 rounded-xl text-xs text-gray-500 italic text-center">No specific description provided.</div>';
            }

            container.innerHTML = cardsHtml;
        }

        const modal = document.getElementById('dispute-details-modal');
        if (modal) {
            modal.classList.remove('hidden');
        }
        if (window.lucide) window.lucide.createIcons();
    }

    function closeDisputeDetailsModal() {
        const modal = document.getElementById('dispute-details-modal');
        if (modal) {
            modal.classList.add('hidden');
        }
    }

    document.addEventListener('realtime:beforeUpdate', (e) => {
        const newEl = e.detail.newEl;
        if (newEl && newEl.id === 'disputes-table-body') {
            paginateDisputes(newEl);
        }
        if (newEl && newEl.id === 'table-body') {
            applyFilters(newEl);
        }
    });
</script>

<!-- DISPUTE / CORRECTION DETAILS MODAL -->
<div id="dispute-details-modal"
    class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/50 backdrop-blur-xs p-4"
    onclick="if(event.target === this) closeDisputeDetailsModal()">
    <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl space-y-4">
        <!-- Header -->
        <div class="flex items-start justify-between border-b border-gray-100 pb-3">
            <div class="flex items-center gap-3">
                <div
                    class="w-10 h-10 rounded-xl bg-amber-50 border border-amber-200 text-amber-600 flex items-center justify-center shrink-0 shadow-2xs">
                    <i data-lucide="file-warning" class="w-5 h-5"></i>
                </div>
                <div>
                    <h3 class="font-bold text-gray-900 text-base">Correction Request Details</h3>
                    <p id="ddm-subtitle" class="text-xs text-gray-500 mt-0.5 font-medium"></p>
                </div>
            </div>
            <button onclick="closeDisputeDetailsModal()"
                class="text-gray-400 hover:text-gray-600 p-1.5 rounded-lg hover:bg-gray-100 transition-colors cursor-pointer">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <!-- Meta Summary Bar -->
        <div id="ddm-meta-bar"
            class="flex flex-wrap items-center justify-between gap-2 p-2.5 bg-gray-50 rounded-xl border border-gray-200/80 text-xs">
            <!-- Populated via JS: Status badge & timestamp -->
        </div>

        <!-- Detailed Cards Section -->
        <div id="ddm-content-container" class="space-y-3 max-h-80 overflow-y-auto pr-0.5">
            <!-- Populated via JS with clean structured cards -->
        </div>

        <!-- Modal Footer -->
        <div class="pt-3 border-t border-gray-100 flex items-center justify-end">
            <button type="button" onclick="closeDisputeDetailsModal()"
                class="px-5 py-2 text-xs font-bold text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl transition-colors cursor-pointer shadow-2xs">
                Close
            </button>
        </div>
    </div>
</div>



<!-- FIX DEMOGRAPHICS MODAL (Side-by-Side Old vs New Name Comparison) -->
<div id="fix-demographics-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/50 p-4">
    <div class="bg-white rounded-2xl max-w-2xl w-full p-6 shadow-xl space-y-4 max-h-[95vh] overflow-y-auto">
        <!-- Header -->
        <div class="flex items-center justify-between border-b border-gray-200 pb-3">
            <div>
                <h3 class="font-bold text-gray-900 text-base flex items-center gap-2">
                    <i data-lucide="clipboard-check" class="w-5 h-5 text-green-600"></i>
                    Fix Patient Demographics
                </h3>
                <p id="fix-modal-subtitle" class="text-xs text-gray-500 mt-0.5 font-medium"></p>
            </div>
            <button onclick="closeFixDemographicsModal()" class="text-gray-400 hover:text-gray-600 cursor-pointer">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <!-- Patient Requested Corrections Box -->
        <div class="px-4 py-3 bg-amber-50/80 border border-amber-200 rounded-xl space-y-1">
            <div class="text-[11px] font-bold text-amber-800 uppercase tracking-wider flex items-center gap-1.5">
                <i data-lucide="alert-circle" class="w-4 h-4 text-amber-600"></i>
                Reported Incorrect Info:
            </div>
            <div id="fix-patient-statement" class="text-xs text-amber-950 font-medium pl-5 leading-relaxed"></div>
        </div>

        <!-- Side-by-Side Old vs New Demographics Comparison Box -->
        <div class="space-y-2.5">
            <div class="text-[11px] font-bold text-gray-700 uppercase tracking-wider flex items-center gap-1.5">
                <i data-lucide="user-check" class="w-4 h-4 text-gray-600"></i>
                Demographics Verification &amp; Comparison:
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <!-- Left Box: Old Record -->
                <div
                    class="bg-white border border-gray-200 rounded-xl p-4 shadow-2xs space-y-3 max-h-64 overflow-y-auto custom-gray-scroll">
                    <div class="pb-2 border-b border-gray-100">
                        <span class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Old Record
                            (Report)</span>
                    </div>

                    <div id="row-fix-old-first-name">
                        <div class="text-[10px] font-bold text-gray-500 uppercase tracking-wide">First Name:</div>
                        <div id="fix-old-first-name"
                            class="mt-1 text-xs font-semibold text-gray-800 bg-white p-2.5 rounded-xl border border-gray-200 shadow-2xs">
                            —</div>
                    </div>

                    <div id="row-fix-old-last-name">
                        <div class="text-[10px] font-bold text-gray-500 uppercase tracking-wide">Last Name:</div>
                        <div id="fix-old-last-name"
                            class="mt-1 text-xs font-semibold text-gray-800 bg-white p-2.5 rounded-xl border border-gray-200 shadow-2xs">
                            —</div>
                    </div>

                    <div id="row-fix-old-age" class="hidden">
                        <div class="text-[10px] font-bold text-gray-500 uppercase tracking-wide">Age:</div>
                        <div id="fix-old-age"
                            class="mt-1 text-xs font-semibold text-gray-800 bg-white p-2.5 rounded-xl border border-gray-200 shadow-2xs">
                            —</div>
                    </div>

                    <div id="row-fix-old-sex" class="hidden">
                        <div class="text-[10px] font-bold text-gray-500 uppercase tracking-wide">Sex:</div>
                        <div id="fix-old-sex"
                            class="mt-1 text-xs font-semibold text-gray-800 bg-white p-2.5 rounded-xl border border-gray-200 shadow-2xs">
                            —</div>
                    </div>
                </div>

                <!-- Right Box: New / Updated Info -->
                <div
                    class="bg-green-50/40 border border-green-300 rounded-xl p-4 shadow-2xs space-y-3 max-h-64 overflow-y-auto custom-gray-scroll">
                    <div class="pb-2 border-b border-green-100">
                        <span class="text-[11px] font-bold text-green-700 uppercase tracking-wider">New / Updated
                            Info</span>
                    </div>

                    <div id="row-fix-new-first-name">
                        <div class="text-[10px] font-bold text-green-800 uppercase tracking-wide">First Name:</div>
                        <div id="fix-new-first-name"
                            class="mt-1 text-xs font-semibold text-green-950 bg-white p-2.5 rounded-xl border border-green-200 shadow-2xs">
                            —</div>
                    </div>

                    <div id="row-fix-new-last-name">
                        <div class="text-[10px] font-bold text-green-800 uppercase tracking-wide">Last Name:</div>
                        <div id="fix-new-last-name"
                            class="mt-1 text-xs font-semibold text-green-950 bg-white p-2.5 rounded-xl border border-green-200 shadow-2xs">
                            —</div>
                    </div>

                    <div id="row-fix-new-age" class="hidden">
                        <div class="text-[10px] font-bold text-green-800 uppercase tracking-wide">Age:</div>
                        <div id="fix-new-age"
                            class="mt-1 text-xs font-semibold text-green-950 bg-white p-2.5 rounded-xl border border-green-200 shadow-2xs">
                            —</div>
                    </div>

                    <div id="row-fix-new-sex" class="hidden">
                        <div class="text-[10px] font-bold text-green-800 uppercase tracking-wide">Sex:</div>
                        <div id="fix-new-sex"
                            class="mt-1 text-xs font-semibold text-green-950 bg-white p-2.5 rounded-xl border border-green-200 shadow-2xs">
                            —</div>
                    </div>
                </div>
            </div>

            <!-- Success Alert Banner -->
            <div id="fix-demo-alert"
                class="hidden p-3 rounded-lg bg-green-50 border border-green-200 text-xs text-green-800 font-semibold flex items-center gap-2 shadow-2xs">
                <i data-lucide="check-circle" class="w-4 h-4 text-green-600 shrink-0"></i>
                <span>Patient record successfully updated with new information. You can now confirm and resolve this
                    ticket.</span>
            </div>
        </div>

        <input type="hidden" id="fix-demo-dispute-id">

        <!-- Footer Action Buttons -->
        <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-200">
            <button type="button" onclick="closeFixDemographicsModal()"
                class="px-4 py-2 text-xs font-semibold text-gray-600 hover:bg-gray-100 rounded-xl cursor-pointer transition">
                Cancel
            </button>

            <!-- Fix & Resolve Button -->
            <button type="button" id="btn-apply-fix-demo" onclick="applyFixDemographics()"
                class="inline-flex items-center gap-1.5 px-5 py-2 text-xs font-bold text-white bg-green-600 hover:bg-green-700 rounded-xl shadow-xs transition cursor-pointer active:scale-95">
                <i data-lucide="check" class="w-3.5 h-3.5"></i>
                Fix &amp; Resolve
            </button>
        </div>
    </div>
</div>

<!-- VERIFY & RELEASE MODAL (Comprehensive Amendments Overview) -->
<div id="verify-release-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/50 p-4">
    <div class="bg-white rounded-2xl max-w-2xl w-full p-6 shadow-xl space-y-4 max-h-[95vh] overflow-y-auto">
        <!-- Header -->
        <div class="flex items-center justify-between border-b border-gray-200 pb-3">
            <div>
                <h3 class="font-bold text-gray-900 text-base flex items-center gap-2">
                    <i data-lucide="shield-check" class="w-5 h-5 text-green-600"></i>
                    Final Verify &amp; Release Report
                </h3>
                <p id="verify-modal-subtitle" class="text-xs text-gray-500 mt-0.5 font-medium"></p>
            </div>
            <button onclick="closeVerifyReleaseModal()" class="text-gray-400 hover:text-gray-600 cursor-pointer">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <!-- Section 1: Patient's Reported Issue -->
        <div class="p-4 bg-amber-50 border border-amber-200 rounded-xl space-y-1.5">
            <div class="text-[11px] font-bold text-amber-800 uppercase tracking-wider flex items-center gap-1.5">
                <i data-lucide="alert-circle" class="w-4 h-4 text-amber-600"></i>
                Patient's Reported Issue:
            </div>
            <div id="verify-patient-statement"
                class="text-xs text-amber-950 font-medium whitespace-pre-line leading-relaxed pl-1"></div>
        </div>

        <!-- Section 3: Side-by-Side Demographics Comparison (Shown if patient info correction requested) -->
        <div id="verify-demographics-container" class="space-y-2.5 hidden">
            <div class="text-[11px] font-bold text-gray-700 uppercase tracking-wider flex items-center gap-1.5">
                <i data-lucide="user-check" class="w-4 h-4 text-gray-600"></i>
                Demographics Verification &amp; Comparison:
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <!-- Left Box: Old Record -->
                <div
                    class="bg-white border border-gray-200 rounded-xl p-4 shadow-2xs space-y-3 max-h-64 overflow-y-auto custom-gray-scroll">
                    <div class="pb-2 border-b border-gray-100">
                        <span class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Old Record
                            (Report)</span>
                    </div>

                    <div id="row-ver-old-first-name">
                        <div class="text-[10px] font-bold text-gray-500 uppercase tracking-wide">First Name:</div>
                        <div id="ver-old-first-name"
                            class="mt-1 text-xs font-semibold text-gray-800 bg-white p-2.5 rounded-xl border border-gray-200 shadow-2xs">
                            —</div>
                    </div>

                    <div id="row-ver-old-last-name">
                        <div class="text-[10px] font-bold text-gray-500 uppercase tracking-wide">Last Name:</div>
                        <div id="ver-old-last-name"
                            class="mt-1 text-xs font-semibold text-gray-800 bg-white p-2.5 rounded-xl border border-gray-200 shadow-2xs">
                            —</div>
                    </div>

                    <div id="row-ver-old-age" class="hidden">
                        <div class="text-[10px] font-bold text-gray-500 uppercase tracking-wide">Age:</div>
                        <div id="ver-old-age"
                            class="mt-1 text-xs font-semibold text-gray-800 bg-white p-2.5 rounded-xl border border-gray-200 shadow-2xs">
                            —</div>
                    </div>

                    <div id="row-ver-old-sex" class="hidden">
                        <div class="text-[10px] font-bold text-gray-500 uppercase tracking-wide">Sex:</div>
                        <div id="ver-old-sex"
                            class="mt-1 text-xs font-semibold text-gray-800 bg-white p-2.5 rounded-xl border border-gray-200 shadow-2xs">
                            —</div>
                    </div>
                </div>

                <!-- Right Box: New / Updated Info -->
                <div
                    class="bg-green-50/40 border border-green-300 rounded-xl p-4 shadow-2xs space-y-3 max-h-64 overflow-y-auto custom-gray-scroll">
                    <div class="pb-2 border-b border-green-100">
                        <span class="text-[11px] font-bold text-green-700 uppercase tracking-wider">New / Updated
                            Info</span>
                    </div>

                    <div id="row-ver-new-first-name">
                        <div class="text-[10px] font-bold text-green-800 uppercase tracking-wide">First Name:</div>
                        <div id="ver-new-first-name"
                            class="mt-1 text-xs font-semibold text-green-950 bg-white p-2.5 rounded-xl border border-green-200 shadow-2xs">
                            —</div>
                    </div>

                    <div id="row-ver-new-last-name">
                        <div class="text-[10px] font-bold text-green-800 uppercase tracking-wide">Last Name:</div>
                        <div id="ver-new-last-name"
                            class="mt-1 text-xs font-semibold text-green-950 bg-white p-2.5 rounded-xl border border-green-200 shadow-2xs">
                            —</div>
                    </div>

                    <div id="row-ver-new-age" class="hidden">
                        <div class="text-[10px] font-bold text-green-800 uppercase tracking-wide">Age:</div>
                        <div id="ver-new-age"
                            class="mt-1 text-xs font-semibold text-green-950 bg-white p-2.5 rounded-xl border border-green-200 shadow-2xs">
                            —</div>
                    </div>

                    <div id="row-ver-new-sex" class="hidden">
                        <div class="text-[10px] font-bold text-green-800 uppercase tracking-wide">Sex:</div>
                        <div id="ver-new-sex"
                            class="mt-1 text-xs font-semibold text-green-950 bg-white p-2.5 rounded-xl border border-green-200 shadow-2xs">
                            —</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 4: Side-by-Side Findings Comparison Box -->
        <div id="verify-findings-container" class="space-y-2.5 hidden">
            <div class="text-[11px] font-bold text-gray-700 uppercase tracking-wider flex items-center gap-1.5">
                <i data-lucide="file-text" class="w-4 h-4 text-gray-700"></i>
                Amended Findings &amp; Impression Comparison:
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div class="space-y-1.5">
                    <div class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">
                        Old Result
                    </div>
                    <div id="verify-old-findings"
                        class="text-xs text-gray-800 bg-white p-4 rounded-xl border border-gray-200 shadow-2xs max-h-56 overflow-y-auto overscroll-contain leading-relaxed custom-gray-scroll">
                    </div>
                </div>
                <div class="space-y-1.5">
                    <div class="text-[11px] font-bold text-green-700 uppercase tracking-wider">
                        New Amended Result
                    </div>
                    <div id="verify-new-findings"
                        class="text-xs text-green-950 bg-green-50/50 p-4 rounded-xl border border-green-300 shadow-2xs max-h-56 overflow-y-auto overscroll-contain leading-relaxed custom-gray-scroll">
                    </div>
                </div>
            </div>
        </div>

        <input type="hidden" id="verify-release-dispute-id">

        <!-- Footer Action Buttons -->
        <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-200">
            <button type="button" onclick="closeVerifyReleaseModal()"
                class="px-4 py-2 text-xs font-semibold text-gray-600 hover:bg-gray-100 rounded-xl cursor-pointer transition">
                Cancel
            </button>

            <button type="button" onclick="submitAmendedRelease()"
                class="inline-flex items-center gap-1.5 px-5 py-2 text-xs font-bold text-white bg-green-600 hover:bg-green-700 rounded-xl shadow-xs transition cursor-pointer">
                <i data-lucide="check-circle-2" class="w-3.5 h-3.5"></i>
                Confirm &amp; Release Amended Report
            </button>
        </div>
    </div>
</div>

<!-- RADTECH EDIT / AMEND MODAL (Findings, DICOM Name, Template Corrections) -->
<div id="radtech-amend-modal"
    class="fixed inset-0 z-50 hidden flex items-start justify-center bg-black/55 backdrop-blur-sm p-4 overflow-y-auto">
    <div class="bg-white rounded-2xl w-full max-w-2xl shadow-2xl my-6 border border-gray-100 overflow-hidden">

        <!-- ── Modal Header ── -->
        <div
            class="flex items-center justify-between px-5 py-4 bg-gradient-to-r from-amber-50 to-orange-50 border-b border-amber-100">
            <div class="flex items-center gap-3 min-w-0">
                <div
                    class="shrink-0 w-9 h-9 rounded-xl bg-amber-500 flex items-center justify-center text-white shadow-sm">
                    <i data-lucide="edit-3" class="w-4 h-4"></i>
                </div>
                <div class="min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <h3 class="font-bold text-gray-900 text-sm leading-tight">Edit / Amend Report</h3>
                        <!-- Dynamic step badge injected by JS -->
                        <span id="amend-step-badge"
                            class="inline-flex items-center gap-1 px-2 py-0.5 text-[10px] font-bold rounded-full bg-blue-100 text-blue-700 border border-blue-200 shrink-0">
                            <span class="w-1.5 h-1.5 rounded-full bg-blue-500 inline-block animate-pulse"></span>
                            <span id="amend-step-text">Correction in Progress</span>
                        </span>
                    </div>
                    <p class="text-[11px] text-gray-500 mt-0.5 truncate">
                        Case: <span id="amend-modal-case-number" class="font-mono font-semibold text-red-600"></span>
                        <span class="mx-1 text-gray-300">|</span>
                        <span id="amend-modal-patient-info" class="text-gray-600"></span>
                        <span class="mx-1 text-gray-300">|</span>
                        <span id="amend-modal-exam-type" class="text-gray-500 italic"></span>
                    </p>
                </div>
            </div>
            <button type="button" onclick="closeRadTechAmendModal()"
                class="shrink-0 ml-2 w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:text-gray-700 hover:bg-white/70 transition cursor-pointer">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>

        <!-- ── Reported Issue Banner (collapsible) ── -->
        <div id="amend-reported-issue-container" class="hidden">
            <div class="flex items-start gap-2.5 px-5 py-3 bg-rose-50 border-b border-rose-100">
                <i data-lucide="alert-triangle" class="w-4 h-4 text-rose-500 mt-0.5 shrink-0"></i>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap mb-0.5">
                        <span class="text-[10px] font-extrabold text-rose-700 uppercase tracking-wider">Patient Reported
                            Issue</span>
                        <span id="amend-reported-category"
                            class="px-1.5 py-0.5 text-[9px] font-bold bg-rose-200 text-rose-800 rounded uppercase tracking-wide"></span>
                    </div>
                    <p id="amend-reported-description"
                        class="text-xs text-rose-900 leading-relaxed whitespace-pre-line"></p>
                </div>
            </div>
        </div>

        <!-- ── Form Body ── -->
        <form id="radtech-amend-form" class="px-5 py-4 space-y-4" onsubmit="event.preventDefault()">
            <input type="hidden" id="amend-case-id" value="">
            <input type="hidden" id="amend-dispute-id" value="">

            <!-- Findings & Impression -->
            <div>
                <div class="flex items-center gap-1.5 mb-2.5">
                    <i data-lucide="file-text" class="w-3.5 h-3.5 text-amber-500"></i>
                    <span class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Findings &amp;
                        Impression</span>
                </div>
                <div class="space-y-2.5">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Findings Report</label>
                        <textarea id="amend-findings" rows="4"
                            class="w-full text-xs font-mono p-3 rounded-lg border border-gray-200 bg-gray-50 focus:bg-white focus:border-amber-400 focus:ring-2 focus:ring-amber-100 outline-none leading-relaxed resize-y transition"
                            placeholder="Enter or amend findings…"></textarea>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Impression</label>
                        <textarea id="amend-impression" rows="2"
                            class="w-full text-xs font-mono p-3 rounded-lg border border-gray-200 bg-gray-50 focus:bg-white focus:border-amber-400 focus:ring-2 focus:ring-amber-100 outline-none leading-relaxed resize-y transition"
                            placeholder="Enter or amend impression…"></textarea>
                    </div>
                </div>
            </div>

            <div class="border-t border-dashed border-gray-200"></div>

            <!-- Patient Name / DICOM -->
            <div>
                <div class="flex items-center gap-1.5 mb-2.5">
                    <i data-lucide="user" class="w-3.5 h-3.5 text-blue-500"></i>
                    <span class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Patient Name &amp;
                        DICOM</span>
                    <span class="text-[10px] text-gray-400 ml-1">Fix name typos for header and printed result</span>
                </div>
                <div class="grid grid-cols-3 gap-2.5">
                    <div>
                        <label class="block text-[11px] font-semibold text-gray-600 mb-1">First Name <span
                                class="text-red-500">*</span></label>
                        <input type="text" id="amend-first-name"
                            class="w-full text-xs p-2.5 rounded-lg border border-gray-200 bg-gray-50 focus:bg-white focus:border-blue-400 focus:ring-2 focus:ring-blue-100 outline-none transition">
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-gray-600 mb-1">Middle Name</label>
                        <input type="text" id="amend-middle-name"
                            class="w-full text-xs p-2.5 rounded-lg border border-gray-200 bg-gray-50 focus:bg-white focus:border-blue-400 focus:ring-2 focus:ring-blue-100 outline-none transition">
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-gray-600 mb-1">Last Name <span
                                class="text-red-500">*</span></label>
                        <input type="text" id="amend-last-name"
                            class="w-full text-xs p-2.5 rounded-lg border border-gray-200 bg-gray-50 focus:bg-white focus:border-blue-400 focus:ring-2 focus:ring-blue-100 outline-none transition">
                    </div>
                </div>
            </div>

            <div class="border-t border-dashed border-gray-200"></div>

            <!-- Exam Name, Template, Notes -->
            <div>
                <div class="flex items-center gap-1.5 mb-2.5">
                    <i data-lucide="layout-template" class="w-3.5 h-3.5 text-purple-500"></i>
                    <span class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">Exam &amp;
                        Template</span>
                    <span class="text-[10px] text-gray-400 ml-1">Correct exam name on printed template</span>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                    <div>
                        <label class="block text-[11px] font-semibold text-gray-600 mb-1">Examination Name <span
                                class="text-red-500">*</span></label>
                        <input type="text" id="amend-exam-type" placeholder="e.g. Chest PA"
                            class="w-full text-xs p-2.5 rounded-lg border border-gray-200 bg-gray-50 focus:bg-white focus:border-purple-400 focus:ring-2 focus:ring-purple-100 outline-none transition">
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-gray-600 mb-1">Report Template</label>
                        <select id="amend-template"
                            class="w-full text-xs p-2.5 rounded-lg border border-gray-200 bg-gray-50 focus:bg-white focus:border-purple-400 focus:ring-2 focus:ring-purple-100 outline-none transition">
                            <option value="General Standard">General Standard</option>
                            <option value="Standard Chest">Standard Chest</option>
                            <option value="Extremities">Extremities</option>
                            <option value="Spine">Spine</option>
                            <option value="Pelvis">Pelvis &amp; Hips</option>
                            <option value="Abdomen">Abdomen</option>
                            <option value="Pediatric">Pediatric</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-gray-600 mb-1">Audit Note</label>
                        <input type="text" id="amend-notes" placeholder="e.g. Fixed name typo"
                            class="w-full text-xs p-2.5 rounded-lg border border-gray-200 bg-gray-50 focus:bg-white focus:border-purple-400 focus:ring-2 focus:ring-purple-100 outline-none transition">
                    </div>
                </div>
            </div>

            <!-- Amendment History -->
            <div id="amend-history-container" class="hidden">
                <div class="flex items-center gap-1.5 mb-2">
                    <i data-lucide="history" class="w-3 h-3 text-gray-400"></i>
                    <span class="text-[10px] font-bold text-gray-500 uppercase tracking-wider">Previous
                        Amendments</span>
                </div>
                <div id="amend-history-list" class="space-y-1.5 max-h-28 overflow-y-auto"></div>
            </div>
        </form>

        <!-- ── Footer ── -->
        <div class="flex items-center justify-between gap-3 px-5 py-3.5 bg-gray-50 border-t border-gray-100">
            <button type="button" onclick="closeRadTechAmendModal()"
                class="text-xs font-semibold text-gray-500 hover:text-gray-700 hover:bg-gray-100 px-3 py-2 rounded-lg transition cursor-pointer">
                Cancel
            </button>
            <div class="flex items-center gap-2">
                <button type="button" onclick="submitRadTechAmendment('save_only')"
                    class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-bold text-blue-700 bg-blue-50 hover:bg-blue-100 border border-blue-200 rounded-lg transition cursor-pointer shadow-sm">
                    <i data-lucide="save" class="w-3.5 h-3.5"></i>
                    Save Draft
                </button>
                <button type="button" onclick="submitRadTechAmendment('save_and_release')"
                    class="inline-flex items-center gap-1.5 px-5 py-2 text-xs font-bold text-white bg-green-600 hover:bg-green-700 rounded-lg shadow-sm transition cursor-pointer">
                    <i data-lucide="check-circle" class="w-3.5 h-3.5"></i>
                    Save &amp; Release
                </button>
            </div>
        </div>

    </div>
</div>


<script>
    let currentFixData = null;
    let currentVerifyData = null;

    // Helper: Ensure age is always an age number (e.g. 23), converting from birthdate if a date was stored
    function parseAgeOrBirthdateToAge(val) {
        if (!val) return '';
        const trimmed = String(val).trim();
        const dateMatch = trimmed.match(/^(\d{4})[-\/](\d{1,2})[-\/](\d{1,2})/);
        if (dateMatch) {
            const bdate = new Date(parseInt(dateMatch[1], 10), parseInt(dateMatch[2], 10) - 1, parseInt(dateMatch[3], 10));
            if (!isNaN(bdate.getTime())) {
                const today = new Date();
                let a = today.getFullYear() - bdate.getFullYear();
                const m = today.getMonth() - bdate.getMonth();
                if (m < 0 || (m === 0 && today.getDate() < bdate.getDate())) a--;
                return String(Math.max(0, a));
            }
        }
        const numMatch = trimmed.match(/(\d+)/);
        return numMatch ? numMatch[1] : '';
    }

    // ── FIX DEMOGRAPHICS MODAL LOGIC ──
    function openFixDemographicsModal(data) {
        currentFixData = data;
        document.getElementById('fix-demo-dispute-id').value = data.id || '';
        document.getElementById('fix-modal-subtitle').innerText = 'Case #: ' + (data.case_number || 'N/A') + ' | Patient #: ' + (data.patient_number || 'N/A');

        // Statement: Render clean bulleted text directly below the heading
        const descText = data.description || '';
        const statementEl = document.getElementById('fix-patient-statement');

        let demoNote = '';
        const clean = descText.replace(/\r\n/g, '\n').trim();
        const demoMatch = clean.match(/(?:Wrong Patient Info:|Demographics Note:)\s*([\s\S]*?)(?=(Findings Note:|Exam Details Note:|Typographical Error Note:|Other Concern Note:|$))/i);
        if (demoMatch && demoMatch[1].trim()) {
            demoNote = demoMatch[1].trim().replace(/^•\s*/gm, '').trim();
        }
        if (!demoNote) {
            demoNote = clean;
        }
        const items = demoNote.split(/\n/).map(s => {
            let line = s.trim().replace(/^•\s*/, '');
            // If line is Age with a date string, convert to age
            const ageLineMatch = line.match(/^(Age|Birthdate):\s*(.+)$/i);
            if (ageLineMatch) {
                const parsedAge = parseAgeOrBirthdateToAge(ageLineMatch[2]);
                if (parsedAge) {
                    return `Age: ${parsedAge} yrs old`;
                }
            }
            return line;
        }).filter(Boolean);

        if (items.length > 0) {
            statementEl.innerHTML = items.map(it => `<div>• ${it}</div>`).join('');
        } else {
            statementEl.innerHTML = `<span class="text-xs text-amber-800 italic">No specific fields reported</span>`;
        }

        // Extract patient typed corrections from description
        const fnMatch = clean.match(/First Name:\s*([^\n\r,•]+)/i);
        const lnMatch = clean.match(/Last Name:\s*([^\n\r,•]+)/i);
        const ageMatch = clean.match(/(?:Age|Birthdate):\s*([^\n\r,•]+)/i);
        const sexMatch = clean.match(/(?:Sex|Gender):\s*([^\n\r,•]+)/i);

        const typedFn = fnMatch ? fnMatch[1].trim() : '';
        const typedLn = lnMatch ? lnMatch[1].trim() : '';
        const typedAge = ageMatch ? parseAgeOrBirthdateToAge(ageMatch[1]) : '';
        const typedSex = sexMatch ? sexMatch[1].trim() : '';

        // Old Record (Current in database)
        const oldFn = data.first_name || '';
        const oldLn = data.last_name || '';
        const oldAge = data.age ? `${data.age} yrs old` : 'N/A';
        const oldSex = data.sex || 'N/A';

        // New / Updated Info (what patient typed into report, fallback to current if not specified)
        const newFn = typedFn || oldFn;
        const newLn = typedLn || oldLn;
        const newAge = typedAge ? `${typedAge} yrs old` : oldAge;
        const newSex = typedSex || oldSex;

        // Cache resolved values on currentFixData so applyFixDemographics uses them
        currentFixData.resolved_first_name = newFn;
        currentFixData.resolved_last_name = newLn;
        currentFixData.resolved_age = typedAge || (data.age ? String(data.age) : '');
        currentFixData.resolved_sex = typedSex || data.sex || '';

        function safeSetText(id, text) {
            const el = document.getElementById(id);
            if (el) el.innerText = text;
        }

        safeSetText('fix-old-first-name', oldFn || 'N/A');
        safeSetText('fix-old-last-name', oldLn || 'N/A');
        safeSetText('fix-old-age', oldAge);
        safeSetText('fix-old-sex', oldSex);

        safeSetText('fix-new-first-name', newFn || 'N/A');
        safeSetText('fix-new-last-name', newLn || 'N/A');
        safeSetText('fix-new-age', newAge);
        safeSetText('fix-new-sex', newSex);

        // Filter which rows to display based on what the patient reported
        const descLower = descText.toLowerCase();
        const hasFirstName = Boolean(typedFn) || descLower.includes('first name');
        const hasLastName = Boolean(typedLn) || descLower.includes('last name');
        const hasAge = Boolean(typedAge) || descLower.includes('age');
        const hasSex = Boolean(typedSex) || descLower.includes('sex') || descLower.includes('gender');

        const hasAnySpecific = hasFirstName || hasLastName || hasAge || hasSex;

        function setRowVisibility(id, visible) {
            const el = document.getElementById(id);
            if (el) {
                if (visible) el.classList.remove('hidden');
                else el.classList.add('hidden');
            }
        }

        setRowVisibility('row-fix-old-first-name', hasFirstName || !hasAnySpecific);
        setRowVisibility('row-fix-new-first-name', hasFirstName || !hasAnySpecific);

        setRowVisibility('row-fix-old-last-name', hasLastName || !hasAnySpecific);
        setRowVisibility('row-fix-new-last-name', hasLastName || !hasAnySpecific);

        setRowVisibility('row-fix-old-age', hasAge);
        setRowVisibility('row-fix-new-age', hasAge);

        setRowVisibility('row-fix-old-sex', hasSex);
        setRowVisibility('row-fix-new-sex', hasSex);

        // Reset button states
        const btnApply = document.getElementById('btn-apply-fix-demo');
        const alertEl = document.getElementById('fix-demo-alert');
        if (alertEl) alertEl.classList.add('hidden');

        if (btnApply) {
            btnApply.disabled = false;
            btnApply.className = "inline-flex items-center gap-1.5 px-5 py-2 text-xs font-bold text-white bg-green-600 hover:bg-green-700 rounded-xl shadow-xs transition cursor-pointer active:scale-95";
            btnApply.innerHTML = '<i data-lucide="check" class="w-3.5 h-3.5"></i> Fix &amp; Resolve';
        }

        document.getElementById('fix-demographics-modal').classList.remove('hidden');
        if (window.lucide) lucide.createIcons();
    }

    function closeFixDemographicsModal() {
        document.getElementById('fix-demographics-modal').classList.add('hidden');
    }

    function applyFixDemographics() {
        if (!currentFixData) return;
        const disputeId = currentFixData.id;
        const firstName = currentFixData.resolved_first_name || currentFixData.first_name || '';
        const lastName = currentFixData.resolved_last_name || currentFixData.last_name || '';
        const middleName = currentFixData.middle_name || '';
        const age = currentFixData.resolved_age || '';
        const sex = currentFixData.resolved_sex || '';

        Swal.fire({
            title: 'Fix & Resolve?',
            text: 'Are you sure you want to apply these demographic corrections and resolve this ticket?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#16a34a',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Yes, Fix & Resolve',
            cancelButtonText: 'Cancel',
            customClass: {
                container: '!z-[999999]',
                popup: 'rounded-2xl',
                confirmButton: 'rounded-xl font-bold px-4 py-2',
                cancelButton: 'rounded-xl font-semibold px-4 py-2'
            }
        }).then((result) => {
            if (!result.isConfirmed) return;

            const btnApply = document.getElementById('btn-apply-fix-demo');
            if (btnApply) {
                btnApply.disabled = true;
                btnApply.innerHTML = '<i data-lucide="loader-2" class="w-3.5 h-3.5 animate-spin"></i> Resolving...';
            }
            if (window.lucide) lucide.createIcons();

            const fd = new FormData();
            fd.append('dispute_id', disputeId);
            fd.append('first_name', firstName);
            fd.append('last_name', lastName);
            fd.append('middle_name', middleName);
            if (age) {
                fd.append('age', age);
            }
            if (sex) {
                fd.append('sex', sex);
            }

            fetch('<?= PROJECT_DIR ? '/' . PROJECT_DIR . '/' : '/' ?>app/Api/disputes.php?action=update_patient_demographics', {
                method: 'POST',
                body: fd
            })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        closeFixDemographicsModal();
                        if (res.both_pending_escalate) {
                            Swal.fire({
                                title: 'Demographics Corrected!',
                                text: 'Patient demographic information has been updated. Please proceed to Edit / Amend Report for the remaining findings issue.',
                                icon: 'info',
                                showConfirmButton: false,
                                timer: 1500,
                                customClass: { container: '!z-[999999]', popup: 'rounded-2xl' }
                            }).then(() => location.reload());
                        } else {
                            Swal.fire({
                                title: 'Resolved!',
                                text: 'Patient demographic corrections applied and resolved.',
                                icon: 'success',
                                showConfirmButton: false,
                                timer: 1500,
                                customClass: { container: '!z-[999999]', popup: 'rounded-2xl' }
                            }).then(() => location.reload());
                        }
                    } else {
                        if (btnApply) {
                            btnApply.disabled = false;
                            btnApply.className = "inline-flex items-center gap-1.5 px-5 py-2 text-xs font-bold text-white bg-green-600 hover:bg-green-700 rounded-xl shadow-xs transition cursor-pointer active:scale-95";
                            btnApply.innerHTML = '<i data-lucide="check" class="w-3.5 h-3.5"></i> Fix &amp; Resolve';
                        }
                        Swal.fire({
                            title: 'Error',
                            text: res.message || 'Failed to update record.',
                            icon: 'error',
                            customClass: { container: '!z-[999999]', popup: 'rounded-2xl' }
                        });
                        if (window.lucide) lucide.createIcons();
                    }
                })
                .catch(err => {
                    if (btnApply) {
                        btnApply.disabled = false;
                        btnApply.className = "inline-flex items-center gap-1.5 px-5 py-2 text-xs font-bold text-white bg-green-600 hover:bg-green-700 rounded-xl shadow-xs transition cursor-pointer active:scale-95";
                        btnApply.innerHTML = '<i data-lucide="check" class="w-3.5 h-3.5"></i> Fix &amp; Resolve';
                    }
                    Swal.fire({
                        title: 'Error',
                        text: 'Connection error occurred.',
                        icon: 'error',
                        customClass: { container: '!z-[999999]', popup: 'rounded-2xl' }
                    });
                    if (window.lucide) lucide.createIcons();
                });
        });
    }

    // ── VERIFY & RELEASE MODAL LOGIC ──
    function openVerifyReleaseModal(data) {
        currentVerifyData = data;
        document.getElementById('verify-release-dispute-id').value = data.id || '';
        document.getElementById('verify-modal-subtitle').innerText = 'Case #: ' + (data.case_number || 'N/A') + ' | Patient #: ' + (data.patient_number || 'N/A');

        // Statement
        document.getElementById('verify-patient-statement').innerHTML = formatDisputeDescriptionHtml(data.description);

        // Demographics comparison logic
        const descText = data.description || '';
        const cleanDesc = descText.replace(/\r\n/g, '\n');
        const descLower = descText.toLowerCase();
        const cat = data.category || '';

        const fnMatch = cleanDesc.match(/First Name:\s*([^\n\r,•]+)/i);
        const lnMatch = cleanDesc.match(/Last Name:\s*([^\n\r,•]+)/i);
        const ageMatch = cleanDesc.match(/(?:Age|Birthdate):\s*([^\n\r,•]+)/i);
        const sexMatch = cleanDesc.match(/(?:Sex|Gender):\s*([^\n\r,•]+)/i);

        const typedFn = fnMatch ? fnMatch[1].trim() : '';
        const typedLn = lnMatch ? lnMatch[1].trim() : '';
        const typedAge = ageMatch ? parseAgeOrBirthdateToAge(ageMatch[1]) : '';
        const typedSex = sexMatch ? sexMatch[1].trim() : '';

        const hasFirstName = Boolean(typedFn) || descLower.includes('first name');
        const hasLastName = Boolean(typedLn) || descLower.includes('last name');
        const hasAge = Boolean(typedAge) || descLower.includes('age');
        const hasSex = Boolean(typedSex) || descLower.includes('sex') || descLower.includes('gender');
        const hasWrongInfoHeading = descLower.includes('wrong patient info');

        const hasDemoChanges = (cat === 'demographic_error' || cat === 'both_error' || cat === 'both_template_error' || hasFirstName || hasLastName || hasAge || hasSex || hasWrongInfoHeading);

        const demoCont = document.getElementById('verify-demographics-container');
        if (hasDemoChanges) {
            const oldFn = data.first_name || '';
            const oldLn = data.last_name || '';
            const oldAge = data.age ? `${data.age} yrs old` : 'N/A';
            const oldSex = data.sex || 'N/A';

            const newFn = typedFn || oldFn;
            const newLn = typedLn || oldLn;
            const newAge = typedAge ? `${typedAge} yrs old` : oldAge;
            const newSex = typedSex || oldSex;

            function safeSetVer(id, text) {
                const el = document.getElementById(id);
                if (el) el.innerText = text;
            }

            safeSetVer('ver-old-first-name', oldFn || 'N/A');
            safeSetVer('ver-old-last-name', oldLn || 'N/A');
            safeSetVer('ver-old-age', oldAge);
            safeSetVer('ver-old-sex', oldSex);

            safeSetVer('ver-new-first-name', newFn || 'N/A');
            safeSetVer('ver-new-last-name', newLn || 'N/A');
            safeSetVer('ver-new-age', newAge);
            safeSetVer('ver-new-sex', newSex);

            // Dynamic field row filtering
            const hasAnySpecific = hasFirstName || hasLastName || hasAge || hasSex;

            function setVerRow(id, visible) {
                const el = document.getElementById(id);
                if (el) {
                    if (visible) el.classList.remove('hidden');
                    else el.classList.add('hidden');
                }
            }

            setVerRow('row-ver-old-first-name', hasFirstName || !hasAnySpecific);
            setVerRow('row-ver-new-first-name', hasFirstName || !hasAnySpecific);

            setVerRow('row-ver-old-last-name', hasLastName || !hasAnySpecific);
            setVerRow('row-ver-new-last-name', hasLastName || !hasAnySpecific);

            setVerRow('row-ver-old-age', hasAge);
            setVerRow('row-ver-new-age', hasAge);

            setVerRow('row-ver-old-sex', hasSex);
            setVerRow('row-ver-new-sex', hasSex);

            demoCont.classList.remove('hidden');
        } else {
            demoCont.classList.add('hidden');
        }

        // Findings comparison
        const findingsCont = document.getElementById('verify-findings-container');
        const oldFindings = data.old_findings || '';
        const newFindings = data.findings || '';
        const oldImpression = data.old_impression || '';
        const newImpression = data.impression || '';
        const examType = data.exam_type || '';

        // If only patient info/demographics or template rename was reported, do NOT show findings comparison
        const isPureDemographicOrTemplate = (cat === 'demographic_error') || (cat === 'template_error') || (cat === 'both_template_error') ||
            (!descLower.includes('findings') && !descLower.includes('impression') && !descLower.includes('reading') && !descLower.includes('typo') && cat !== 'both_error');

        const hasFindings = !isPureDemographicOrTemplate && (cat === 'findings_error' || cat === 'exam_details_error' || cat === 'both_error' || cat === 'other' || Boolean(oldFindings));

        if (hasFindings && (oldFindings || newFindings)) {
            function parseToHtml(fJson, iJson, fallbackKey, isNew = false) {
                let fObj = {}, iObj = {};
                try { fObj = JSON.parse(fJson || "{}"); } catch (e) { if (fJson) fObj[fallbackKey || "RESULT"] = fJson; }
                try { iObj = JSON.parse(iJson || "{}"); } catch (e) { if (iJson) iObj[fallbackKey || "RESULT"] = iJson; }

                let html = "";
                let keys = Array.from(new Set([...Object.keys(fObj), ...Object.keys(iObj)]));

                keys.forEach((key, index) => {
                    html += `<div class="${index > 0 ? 'mt-3.5 pt-3.5 border-t border-gray-200' : ''}">`;
                    if (key) {
                        const headerClass = isNew
                            ? 'font-bold text-xs text-green-700 uppercase mb-2.5 pb-1.5 border-b border-green-200'
                            : 'font-bold text-xs text-gray-700 uppercase mb-2.5 pb-1.5 border-b border-gray-200';
                        html += `<div class="${headerClass}">${key}</div>`;
                    }

                    let findingsText = "";
                    let impressionText = "";

                    if (fObj[key]) {
                        if (typeof fObj[key] === 'object' && fObj[key] !== null) {
                            findingsText = fObj[key].findings || "";
                            if (fObj[key].impression) impressionText = fObj[key].impression;
                        } else {
                            findingsText = fObj[key];
                        }
                    }

                    if (iObj[key]) {
                        if (typeof iObj[key] === 'object' && iObj[key] !== null) {
                            if (!impressionText) impressionText = iObj[key].impression || "";
                        } else {
                            if (!impressionText) impressionText = iObj[key];
                        }
                    }

                    if (findingsText) html += `<div class="mb-2.5"><div class="font-bold text-gray-600 text-[10px] uppercase">FINDINGS:</div><p class="mt-0.5 text-gray-700 whitespace-pre-line">${findingsText}</p></div>`;
                    if (impressionText) html += `<div class="mb-1"><div class="font-bold text-gray-600 text-[10px] uppercase">IMPRESSION:</div><p class="mt-0.5 text-gray-700 whitespace-pre-line">${impressionText}</p></div>`;

                    html += `</div>`;
                });
                return html || '<p class="text-xs text-gray-400 italic">No findings available.</p>';
            }

            document.getElementById('verify-old-findings').innerHTML = parseToHtml(oldFindings, oldImpression, examType, false);
            document.getElementById('verify-new-findings').innerHTML = parseToHtml(newFindings, newImpression, examType, true);
            findingsCont.classList.remove('hidden');
        } else {
            findingsCont.classList.add('hidden');
        }

        document.getElementById('verify-release-modal').classList.remove('hidden');
        if (window.lucide) lucide.createIcons();
    }

    function closeVerifyReleaseModal() {
        document.getElementById('verify-release-modal').classList.add('hidden');
    }

    function submitAmendedRelease() {
        let disputeId = (currentVerifyData && currentVerifyData.id)
            ? currentVerifyData.id
            : document.getElementById('verify-release-dispute-id')?.value;

        if (!disputeId) {
            Swal.fire({
                title: 'Error',
                text: 'Dispute record ID not found.',
                icon: 'error',
                customClass: { container: '!z-[999999]' }
            });
            return;
        }

        Swal.fire({
            title: 'Release Amended Report?',
            text: 'The amended radiological report will be finalized and officially released to the patient.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#16a34a',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Yes, Release Report',
            cancelButtonText: 'Cancel',
            customClass: {
                container: '!z-[999999]',
                popup: 'rounded-2xl',
                confirmButton: 'rounded-xl font-bold px-4 py-2',
                cancelButton: 'rounded-xl font-semibold px-4 py-2'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const btn = document.querySelector('#verify-release-modal button[onclick="submitAmendedRelease()"]');
                if (btn) {
                    btn.disabled = true;
                    btn.innerHTML = '<i data-lucide="loader-2" class="w-3.5 h-3.5 animate-spin"></i> Releasing...';
                }

                const fd = new FormData();
                fd.append('dispute_id', disputeId);

                fetch('<?= PROJECT_DIR ? '/' . PROJECT_DIR . '/' : '/' ?>app/Api/disputes.php?action=resolve_dispute', {
                    method: 'POST', body: fd
                })
                    .then(r => r.json())
                    .then(res => {
                        closeVerifyReleaseModal();
                        if (res.success) {
                            Swal.fire({
                                title: 'Released!',
                                text: 'Amended report has been verified and released to the patient.',
                                icon: 'success',
                                showConfirmButton: false,
                                timer: 1500,
                                customClass: { container: '!z-[999999]', popup: 'rounded-2xl' }
                            }).then(() => location.reload());
                        } else {
                            if (btn) {
                                btn.disabled = false;
                                btn.innerHTML = '<i data-lucide="check-circle-2" class="w-3.5 h-3.5"></i> Confirm &amp; Release Amended Report';
                                if (window.lucide) lucide.createIcons();
                            }
                            Swal.fire({
                                title: 'Error',
                                text: res.message || 'An error occurred.',
                                icon: 'error',
                                customClass: { container: '!z-[999999]' }
                            });
                        }
                    })
                    .catch(err => {
                        if (btn) {
                            btn.disabled = false;
                            btn.innerHTML = '<i data-lucide="check-circle-2" class="w-3.5 h-3.5"></i> Confirm &amp; Release Amended Report';
                            if (window.lucide) lucide.createIcons();
                        }
                        Swal.fire({
                            title: 'Error',
                            text: 'Connection or server error occurred.',
                            icon: 'error',
                            customClass: { container: '!z-[999999]' }
                        });
                    });
            }
        });
    }

    function confirmReupload(caseId) {
        Swal.fire({
            title: 'Re-upload & Correct?',
            text: 'You can upload a new X-ray image and update the exam details for this case.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Yes, proceed',
            cancelButtonText: 'Cancel',
            customClass: {
                popup: 'rounded-2xl',
                confirmButton: 'rounded-xl font-bold px-4 py-2',
                cancelButton: 'rounded-xl font-semibold px-4 py-2'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = '<?= PROJECT_DIR ? '/' . PROJECT_DIR . '/' : '/' ?>index.php?role=radtech&page=patient-details&id=' + caseId + '&from=disputes';
            }
        });
    }


    function submitResolution(e) {
        if (e && e.preventDefault) e.preventDefault();
        const disputeId = document.getElementById('resolve-dispute-id').value;
        if (!disputeId) return;

        const fd = new FormData();
        fd.append('dispute_id', disputeId);

        // If in demographics mode, pass the names as well
        const firstName = document.getElementById('resolve-first-name')?.value;
        const lastName = document.getElementById('resolve-last-name')?.value;
        const middleName = document.getElementById('resolve-middle-name')?.value;
        if (firstName) fd.append('first_name', firstName);
        if (lastName) fd.append('last_name', lastName);
        if (middleName) fd.append('middle_name', middleName);

        fetch('<?= PROJECT_DIR ? '/' . PROJECT_DIR . '/' : '/' ?>app/Api/disputes.php?action=resolve_dispute', {
            method: 'POST', body: fd
        }).then(r => r.json()).then(res => {
            closeResolveModal();
            if (res.success) {
                Swal.fire('Resolved', 'The dispute ticket is now resolved and the case has been updated.', 'success').then(() => location.reload());
            } else {
                Swal.fire('Error', res.message || 'An error occurred.', 'error');
            }
        });
    }

    // Real-time polling for RadTech Patient Lists & Correction Requests
    let lastDisputesRawHtml = document.getElementById('disputes-table-body')?.innerHTML.trim() || '';
    let lastQueueRawHtml = document.getElementById('table-body')?.innerHTML.trim() || '';

    setInterval(() => {
        if (document.visibilityState === 'hidden') return;

        const isDisputesTab = new URLSearchParams(window.location.search).get('tab') === 'disputes';
        const fixModal = document.getElementById('fix-demographics-modal');
        const verModal = document.getElementById('verify-release-modal');
        const amendModal = document.getElementById('radtech-amend-modal');
        const isFixOpen = fixModal && !fixModal.classList.contains('hidden');
        const isVerOpen = verModal && !verModal.classList.contains('hidden');
        const isAmendOpen = amendModal && !amendModal.classList.contains('hidden');

        if (isFixOpen || isVerOpen || isAmendOpen) return;

        if (isDisputesTab) {
            fetch('<?= url("patient-lists?tab=disputes&ajax_polling=1") ?>&_t=' + Date.now())
                .then(res => res.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');

                    const newTbody = doc.getElementById('disputes-table-body');
                    const oldTbody = document.getElementById('disputes-table-body');
                    if (newTbody && oldTbody) {
                        const newRawHtml = newTbody.innerHTML.trim();
                        if (newRawHtml !== lastDisputesRawHtml) {
                            lastDisputesRawHtml = newRawHtml;
                            oldTbody.innerHTML = newRawHtml;
                            applyDisputesFilter();
                            if (window.lucide) lucide.createIcons();
                        }
                    }

                    const newBadge = doc.getElementById('radtech-disputes-tab-badge');
                    const curBadge = document.getElementById('radtech-disputes-tab-badge');
                    if (newBadge && curBadge) {
                        curBadge.innerHTML = newBadge.innerHTML;
                        if (newBadge.title) curBadge.title = newBadge.title;
                    } else if (!newBadge && curBadge) {
                        curBadge.remove();
                    }
                })
                .catch(() => { });
        } else {
            fetch('<?= url("patient-lists?ajax_polling=1") ?>&_t=' + Date.now())
                .then(res => res.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');

                    const newTbody = doc.getElementById('table-body');
                    const oldTbody = document.getElementById('table-body');
                    if (newTbody && oldTbody) {
                        const newRawHtml = newTbody.innerHTML.trim();
                        if (newRawHtml !== lastQueueRawHtml) {
                            lastQueueRawHtml = newRawHtml;
                            oldTbody.innerHTML = newRawHtml;
                            if (typeof applyFilters === 'function') {
                                applyFilters();
                            }
                            if (window.lucide) lucide.createIcons();
                        }
                    }

                    const newBadge = doc.getElementById('radtech-disputes-tab-badge');
                    const curBadge = document.getElementById('radtech-disputes-tab-badge');
                    if (newBadge && curBadge) {
                        curBadge.innerHTML = newBadge.innerHTML;
                        if (newBadge.title) curBadge.title = newBadge.title;
                    } else if (!newBadge && curBadge) {
                        curBadge.remove();
                    }
                })
                .catch(() => { });
        }
    }, 3000);
    window.PROJECT_DIR = <?= json_encode(PROJECT_DIR) ?>;
</script>
<script src="<?= PROJECT_DIR ? '/' . PROJECT_DIR . '/' : '/' ?>public/assets/js/radtech-amend.js"></script>