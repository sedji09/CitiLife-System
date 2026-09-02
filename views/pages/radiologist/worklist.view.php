<?php
require_once __DIR__ . '/../../../config/database.php';


$branchModel = new \BranchModel($pdo);
$caseModel = new \CaseModel($pdo);

require_once __DIR__ . '/../../../app/Models/ResultDisputeModel.php';
$disputeModel = new \ResultDisputeModel($pdo);
$radDisputes = $disputeModel->getDisputesForClinic(null, 'radiologist');
$pendingRadDisputeCount = count(array_filter($radDisputes, function($d) { return $d['status'] === 'Escalated to Radiologist'; }));

// Fetch all branches
$branchesList = $branchModel->getAllBranches();

// Fetch all pending cases (Standardized via Model)
$radiologistId = $_SESSION['user_id'] ?? null;

// Support status URL filter for dashboard card deep-links
$statusParam = $_GET['status'] ?? '';
if ($statusParam === 'overdue') {
    // Overdue: pending/under-reading for 3+ hours
    $records = $caseModel->getWorklist(null, null, ['Pending', 'Under Reading'], true, $radiologistId);
    $records = array_filter($records, function ($r) {
        return (time() - strtotime($r['created_at'])) >= 3 * 3600;
    });
    $records = array_values($records);
} elseif ($statusParam === 'completed_today') {
    $records = $caseModel->getWorklist(null, null, ['Report Ready', 'Completed'], false, $radiologistId);
    $records = array_filter($records, function ($r) {
        return !empty($r['date_completed']) && date('Y-m-d', strtotime($r['date_completed'])) === date('Y-m-d');
    });
    $records = array_values($records);
} elseif ($statusParam === 'Under Reading') {
    $records = $caseModel->getWorklist(null, null, ['Under Reading'], true, $radiologistId);
    $records = array_filter($records, function ($r) {
        return empty($r['findings']);
    });
    $records = array_values($records);
} elseif ($statusParam === 'For Revision') {
    $records = $caseModel->getWorklist(null, null, ['For Revision'], false, $radiologistId);
} elseif ($statusParam === 'pending') {
    $records = $caseModel->getWorklist(null, null, ['Pending', 'Under Reading'], true, $radiologistId);
} else {
    $records = $caseModel->getWorklist(null, null, ['Pending', 'Under Reading', 'Report Ready'], true, $radiologistId);
}

// Extract unique priorities for filters
$priorities = array_unique(array_column($records, 'priority'));
sort($priorities);
?>

<!-- Header -->
<div class="flex items-center justify-between mb-6">
    <div class="ml-5">
        <?php
        $wlTitle = 'Worklist';
        $wlSubtitle = 'Manage pending cases across all branches';
        if ($statusParam === 'overdue') {
            $wlTitle = 'Overdue Cases';
            $wlSubtitle = 'Cases waiting 3+ hours without a completed reading';
        } elseif ($statusParam === 'completed_today') {
            $wlTitle = 'Completed Reports — Today';
            $wlSubtitle = 'Reports submitted or completed today';
        } elseif ($statusParam === 'Under Reading') {
            $wlTitle = 'In Progress Cases';
            $wlSubtitle = 'Cases opened by radiologist but findings not yet submitted';
        } elseif ($statusParam === 'For Revision') {
            $wlTitle = 'Cases For Revision';
            $wlSubtitle = 'Cases flagged for editing or correction';
        } elseif ($statusParam === 'pending') {
            $wlTitle = 'Pending Cases';
            $wlSubtitle = 'Cases waiting to be read';
        }
        ?>
        <h2 id="worklist-title" class="text-2xl font-semibold text-gray-900"><?= htmlspecialchars($wlTitle) ?></h2>
        <p id="worklist-subtitle" class="text-sm text-gray-500 mt-1"><?= htmlspecialchars($wlSubtitle) ?></p>
    </div>
</div>

<!-- Navigation Tabs -->
<div class="mt-4 px-4 border-b border-gray-200">
    <nav class="flex gap-4">
        <button type="button" id="tab-rad-worklist-btn" onclick="switchRadTab('worklist')"
                class="pb-3 px-2 text-sm font-bold border-b-2 border-red-600 text-red-600 transition flex items-center gap-2">
            Pending Worklist
            <?php
            $wlCount = count($records);
            $wlDisplay = $wlCount > 99 ? '99+' : $wlCount;
            ?>
            <span id="worklist-tab-badge" class="tab-circle-badge bg-gray-100 text-gray-700 border border-gray-200" style="width: 26px; height: 26px; min-width: 26px; min-height: 26px; border-radius: 9999px; display: inline-flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 700; line-height: 1; flex-shrink: 0;" title="<?= $wlCount ?>">
                <?= $wlDisplay ?>
            </span>
        </button>
        <button type="button" id="tab-rad-disputes-btn" onclick="switchRadTab('disputes')"
                class="pb-3 px-2 text-sm font-semibold border-b-2 border-transparent text-gray-500 hover:text-gray-700 transition flex items-center gap-2 relative">
            Escalated Error Reports / Disputes
            <?php if (!empty($radDisputes)): ?>
                <?php
                $dispCount = count($radDisputes);
                $dispDisplay = $dispCount > 99 ? '99+' : $dispCount;
                ?>
                <span id="disputes-tab-badge" class="tab-circle-badge bg-red-100 text-red-700 border border-red-200" style="width: 26px; height: 26px; min-width: 26px; min-height: 26px; border-radius: 9999px; display: inline-flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 700; line-height: 1; flex-shrink: 0;" title="<?= $dispCount ?>">
                    <?= $dispDisplay ?>
                </span>
            <?php endif; ?>
        </button>
    </nav>
</div>

<!-- Controls for Pending Worklist -->
<div id="worklist-controls" class="mt-6 flex flex-col gap-4 px-4">
    <div class="flex flex-wrap gap-4 items-center">
        <!-- Search -->
        <div class="relative flex-1 min-w-[250px] group" style="position: relative; flex: 1 1 0%;">
            <div
                style="position: absolute; inset-y: 0; left: 0; padding-left: 1rem; display: flex; align-items: center; pointer-events: none; height: 100%; top: 0;">
                <i data-lucide="search" class="text-gray-400 group-hover:text-red-500 transition-colors"
                    style="width: 1.1rem; height: 1.1rem;"></i>
            </div>
            <input type="text" id="searchInput" placeholder="Search by case no, patient name, branch..."
                style="padding-left: 2.75rem !important;"
                class="block w-full pr-4 py-2.5 rounded-xl border border-gray-200 bg-white text-sm text-gray-900 outline-none focus:ring-2 focus:ring-red-500/10 focus:border-red-500 transition-all shadow-sm">
        </div>

        <!-- Filter by Branch -->
        <select id="filterBranch"
            class="w-48 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500 text-sm bg-white">
            <option value="">All Branches</option>
            <?php foreach ($branchesList as $b): ?>
                <option value="<?= htmlspecialchars($b['name']) ?>"><?= htmlspecialchars($b['name']) ?></option>
            <?php endforeach; ?>
        </select>

        <!-- Filter by Priority -->
        <select id="filterPriority"
            class="w-48 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500 text-sm bg-white">
            <option value="">All Priorities</option>
            <option value="STAT">STAT</option>
            <option value="Urgent">Urgent</option>
            <option value="Routine">Routine</option>
        </select>

        <!-- Filter by Date -->
        <?php $urlDateFilter = $_GET['date'] ?? $_GET['filterDate'] ?? (isset($_GET['highlight']) || isset($_GET['highlight_case']) || isset($_GET['status']) ? 'All' : 'Today'); ?>
        <select id="filterDate"
            class="w-48 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500 text-sm bg-white">
            <option value="All" <?= $urlDateFilter === 'All' ? 'selected' : '' ?>>All Dates</option>
            <option value="Today" <?= ($urlDateFilter === 'Today' || empty($urlDateFilter)) ? 'selected' : '' ?>>Today's Cases</option>
            <option value="Backlog" <?= $urlDateFilter === 'Backlog' ? 'selected' : '' ?>>Backlogs</option>
        </select>

        <!-- Sort by -->
        <select id="sortOption"
            class="w-48 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500 text-sm bg-white">
            <option value="date_desc">Newest Record</option>
            <option value="date_asc">Oldest Record</option>
            <option value="priority_desc">Priority (High-Low)</option>
            <option value="priority_asc">Priority (Low-High)</option>
        </select>
    </div>
</div>

<!-- Controls for Escalated Error Reports / Disputes Tab -->
<div id="disputes-controls" class="mt-6 flex flex-col gap-4 px-4 hidden">
    <div class="flex flex-wrap gap-4 items-center">
        <!-- Search -->
        <div class="relative flex-1 min-w-[250px] group" style="position: relative; flex: 1 1 0%;">
            <div
                style="position: absolute; inset-y: 0; left: 0; padding-left: 1rem; display: flex; align-items: center; pointer-events: none; height: 100%; top: 0;">
                <i data-lucide="search" class="text-gray-400 group-hover:text-red-500 transition-colors"
                    style="width: 1.1rem; height: 1.1rem;"></i>
            </div>
            <input type="text" id="disputeSearchInput" placeholder="Search dispute by case no, patient, branch, notes..."
                style="padding-left: 2.75rem !important;"
                class="block w-full pr-4 py-2.5 rounded-xl border border-gray-200 bg-white text-sm text-gray-900 outline-none focus:ring-2 focus:ring-red-500/10 focus:border-red-500 transition-all shadow-sm">
        </div>

        <!-- Filter by Correction Type (The 3 types) -->
        <select id="disputeFilterType"
            class="w-60 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500 text-sm bg-white font-medium">
            <option value="">All Correction Types</option>
            <option value="typo">Typographical / Minor Error</option>
            <option value="reupload">Re-upload Diagnostic Image</option>
            <option value="reread">Second Reading / Re-interpretation</option>
        </select>

        <!-- Filter by Branch -->
        <select id="disputeFilterBranch"
            class="w-44 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500 text-sm bg-white">
            <option value="">All Branches</option>
            <?php foreach ($branchesList as $b): ?>
                <option value="<?= htmlspecialchars($b['name']) ?>"><?= htmlspecialchars($b['name']) ?></option>
            <?php endforeach; ?>
        </select>

        <!-- Sort by -->
        <select id="disputeSortOption"
            class="w-48 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500 text-sm bg-white">
            <option value="date_desc">Newest Escalated</option>
            <option value="date_asc">Oldest Escalated</option>
            <option value="type_asc">Correction Type (A-Z)</option>
            <option value="case_asc">Case No. (A-Z)</option>
            <option value="name_asc">Patient Name (A-Z)</option>
        </select>
    </div>
</div>

<div class="px-4">
    <div id="worklist-table-card" class="rounded-xl border border-gray-300 bg-white shadow-sm mt-4 overflow-hidden">
        <div class="overflow-x-auto overflow-y-auto max-h-[600px]">
            <table class="w-full text-sm">
                <thead class="sticky top-0 z-10">
                    <tr class="border-b border-gray-200 bg-gray-50 text-gray-600">
                        <th class="text-left font-semibold px-3 py-3 whitespace-nowrap">Case No.</th>
                        <th class="text-left font-semibold px-3 py-3 whitespace-nowrap">Branch</th>
                        <th class="text-left font-semibold px-3 py-3 truncate max-w-[200px]">Patient Name</th>
                        <th class="text-left font-semibold px-3 py-3 truncate max-w-[150px]">Exam Type</th>
                        <th class="text-left font-semibold px-3 py-3">Priority</th>
                        <th class="text-left font-semibold px-3 py-3 whitespace-nowrap">Date Submitted</th>
                        <th class="text-left font-semibold px-3 py-3">Status</th>
                        <th class="text-left font-semibold px-3 py-3 whitespace-nowrap">Action</th>
                    </tr>
                </thead>
                <tbody id="worklist-tbody" class="text-gray-800 bg-white divide-y divide-gray-100">
                    <?php if (count($records) === 0): ?>
                        <tr class="empty-state-row">
                            <td colspan="8" class="text-center py-8 text-gray-500">
                                No pending cases.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($records as $row):
                            // Map Priority Weight for sorting: STAT > Urgent > Priority > Normal > Routine
                            $pWeight = 1;
                            $pUpper = strtoupper(trim($row['priority'] ?? ''));
                            if ($pUpper === 'STAT')
                                $pWeight = 5;
                            elseif ($pUpper === 'URGENT')
                                $pWeight = 4;
                            elseif ($pUpper === 'PRIORITY')
                                $pWeight = 3;
                            elseif ($pUpper === 'NORMAL')
                                $pWeight = 2;
                            else
                                $pWeight = 1;

                            $isEmergency = ($pUpper === 'STAT') ? 1 : 0;
                            $rowDate = !empty($row['radtech_submitted_at']) ? $row['radtech_submitted_at'] : $row['created_at'];
                            $isToday = (date('Y-m-d', strtotime($rowDate)) === date('Y-m-d'));
                            ?>
                            <tr class="hover:bg-white/10 transition-colors record-row cursor-pointer"
                                data-id="<?= htmlspecialchars($row['case_number']) ?>"
                                data-case-id="<?= htmlspecialchars($row['id'] ?? '') ?>"
                                data-branch="<?= htmlspecialchars($row['branch_name']) ?>"
                                data-priority="<?= htmlspecialchars($row['priority']) ?>" data-stat="<?= $isEmergency ?>"
                                data-pweight="<?= $pWeight ?>"
                                data-is-today="<?= $isToday ? 'true' : 'false' ?>"
                                data-search="<?= htmlspecialchars(strtolower($row['case_number'] . ' ' . $row['first_name'] . ' ' . $row['last_name'] . ' ' . $row['branch_name'])) ?>"
                                data-date="<?= strtotime($rowDate) ?>">
                                <td class="py-3 px-3 whitespace-nowrap">
                                    <div class="font-medium"><?= htmlspecialchars($row['case_number']) ?></div>
                                </td>
                                <td class="py-3 px-3 whitespace-nowrap">
                                    <div class="font-medium text-gray-600"><?= htmlspecialchars($row['branch_name']) ?></div>
                                </td>
                                <td class="py-3 px-3 truncate max-w-[200px]"
                                    title="<?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?>">
                                    <div class="font-medium truncate">
                                        <?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?>
                                    </div>
                                </td>
                                <td class="py-3 px-3 whitespace-nowrap text-xs text-gray-800 font-medium">
                                    <?php
                                    $pExams = array_filter(array_map('trim', explode(',', $row['exam_type'] ?? '')));
                                    $pFirstExam = reset($pExams) ?: 'General Exam';
                                    $pCount = count($pExams);
                                    ?>
                                    <div class="flex items-center gap-1.5">
                                        <span class="truncate max-w-[130px]" title="<?= htmlspecialchars($row['exam_type'] ?? '') ?>">
                                            <?= htmlspecialchars($pFirstExam) ?>
                                        </span>
                                        <?php if ($pCount > 1): ?>
                                            <span class="inline-flex items-center justify-center rounded-full bg-gray-100 border border-gray-300 px-1.5 py-0.5 text-[10px] font-bold text-gray-600 cursor-default flex-shrink-0"
                                                title="<?= htmlspecialchars($row['exam_type'] ?? '') ?>">
                                                <?= $pCount ?>+
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="py-3 px-3">
                                    <?php
                                    $pColor = 'blue';
                                    if ($row['priority'] === 'STAT')
                                        $pColor = 'red';
                                    if ($row['priority'] === 'Urgent')
                                        $pColor = 'yellow';
                                    if ($row['priority'] === 'Priority')
                                        $pColor = 'orange';
                                    ?>
                                    <span
                                        class="inline-flex items-center rounded-full border border-<?= $pColor ?>-400 bg-<?= $pColor ?>-50 px-2 py-1 text-xs font-semibold text-<?= $pColor ?>-700">
                                        <?= htmlspecialchars($row['priority']) ?>
                                    </span>
                                </td>
                                <td class="py-3 px-3 whitespace-nowrap">
                                    <div class="flex flex-col gap-1 items-start">
                                        <?php $submitDate = !empty($row['radtech_submitted_at']) ? $row['radtech_submitted_at'] : $row['created_at']; ?>
                                        <span class="text-sm text-gray-600"><?= date('M d, Y', strtotime($submitDate)) ?> <span class="opacity-70 ml-1"><?= date('h:i A', strtotime($submitDate)) ?></span></span>
                                        <?php if (!$isToday): ?>
                                            <span class="inline-block rounded bg-red-100 px-1.5 py-0.5 text-[10px] font-bold text-red-700 border border-red-200" title="This case was carried over from a previous day">BACKLOG</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="py-3 px-3">
                                    <?php
                                    $rawStatus = $row['status'] ?? 'Pending';
                                    $displayStatus = $rawStatus;
                                    $sBorder = '1.5px solid #facc15';
                                    $sBg = '#fefce8';
                                    $sColor = '#a16207';
                                    $isOverdue = (time() - strtotime($row['created_at'])) >= 3 * 3600;

                                    if ($rawStatus === 'Pending') {
                                        if ($isOverdue) {
                                            $displayStatus = 'Overdue';
                                            $sBorder = '1.5px solid #f87171';
                                            $sBg = '#fef2f2';
                                            $sColor = '#b91c1c';
                                        } else {
                                            $displayStatus = 'Pending';
                                            $sBorder = '1.5px solid #facc15';
                                            $sBg = '#fefce8';
                                            $sColor = '#a16207';
                                        }
                                    } elseif ($rawStatus === 'Under Reading') {
                                        $displayStatus = 'In Progress';
                                        $sBorder = '1.5px solid #60a5fa';
                                        $sBg = '#eff6ff';
                                        $sColor = '#1d4ed8';
                                    } elseif ($rawStatus === 'Report Ready') {
                                        $displayStatus = 'Report Ready';
                                        $sBorder = '1.5px solid #818cf8';
                                        $sBg = '#eef2ff';
                                        $sColor = '#4338ca';
                                    } elseif ($rawStatus === 'For Revision') {
                                        $displayStatus = 'For Revision';
                                        $sBorder = '1.5px solid #f87171';
                                        $sBg = '#fef2f2';
                                        $sColor = '#b91c1c';
                                    } elseif ($rawStatus === 'Completed') {
                                        $displayStatus = 'Completed';
                                        $sBorder = '1.5px solid #4ade80';
                                        $sBg = '#f0fdf4';
                                        $sColor = '#15803d';
                                    }
                                    ?>
                                    <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-semibold"
                                        style="border:<?= $sBorder ?>;background-color:<?= $sBg ?>;color:<?= $sColor ?>">
                                        <?= htmlspecialchars($displayStatus) ?>
                                    </span>
                                </td>
                                <td class="py-3 px-3 whitespace-nowrap">
                                    <a href="<?= PROJECT_DIR ? '/' . PROJECT_DIR . '/' : '/' ?>index.php?role=radiologist&page=case-review&id=<?= $row['id'] ?>&branch_id=<?= $row['branch_id'] ?>"
                                        class="inline-flex items-center rounded-lg bg-red-600 px-4 py-2 text-xs font-semibold text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 shadow-sm transition">
                                        <i data-lucide="microscope" class="w-4 h-4 mr-1"></i> Review Case
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination footer -->
        <div
            class="flex flex-col sm:flex-row items-center justify-between border-t border-gray-200 bg-gray-50 px-6 py-4 gap-4">
            <!-- Record count -->
            <span id="worklist-record-count" class="text-xs text-gray-500 font-medium"></span>

            <!-- Pagination Controls -->
            <div class="flex items-center flex-wrap gap-1.5" id="worklist-pagination-controls">
                <!-- Dynamic page buttons will be inserted here -->
            </div>
        </div>
    </div>
</div>

<!-- ESCALATED ERROR REPORTS / DISPUTES TABLE CARD (Hidden by default) -->
<div id="rad-disputes-table-card" class="hidden rounded-xl border border-gray-300 bg-white shadow-sm mt-4 overflow-hidden mx-4">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-200 bg-gray-50 text-gray-600">
                    <th class="text-left font-semibold px-4 py-3">Case / Patient</th>
                    <th class="text-left font-semibold px-4 py-3">Branch</th>
                    <th class="text-left font-semibold px-4 py-3">Exam Type</th>
                    <th class="text-left font-semibold px-4 py-3">Correction Type</th>
                    <th class="text-left font-semibold px-4 py-3">Details / Statement</th>
                    <th class="text-left font-semibold px-4 py-3">Date Escalated</th>
                    <th class="text-left font-semibold px-4 py-3">Action</th>
                </tr>
            </thead>
            <tbody id="disputes-tbody" class="divide-y divide-gray-100 bg-white">
                <?php if (count($radDisputes) === 0): ?>
                    <tr>
                        <td colspan="7" class="text-center py-8 text-gray-500">
                            No escalated error reports or disputes assigned to Radiologist.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($radDisputes as $d): ?>
                        <?php
                            $notes = $d['radtech_notes'] ?? '';
                            $typeLabel = 'General Error';
                            $typeClass = 'bg-gray-50 text-gray-700 border-gray-200';
                            $typeIcon = 'alert-circle';
                            $typeKey = 'other';

                            if (stripos($notes, 'Typographical') !== false || stripos($notes, 'typo') !== false) {
                                $typeLabel = 'Typographical Error';
                                $typeClass = 'bg-amber-50 text-amber-800 border-amber-200';
                                $typeIcon = 'type';
                                $typeKey = 'typo';
                            } elseif (stripos($notes, 'Re-uploaded') !== false || stripos($notes, 'image') !== false || stripos($notes, 'reupload') !== false) {
                                $typeLabel = 'Image Re-uploaded';
                                $typeClass = 'bg-blue-50 text-blue-800 border-blue-200';
                                $typeIcon = 'image-up';
                                $typeKey = 'reupload';
                            } elseif (stripos($notes, 'Re-reading') !== false || stripos($notes, 'Second Reading') !== false || stripos($notes, 're-interpretation') !== false || stripos($notes, 'reread') !== false) {
                                $typeLabel = 'Second Reading';
                                $typeClass = 'bg-purple-50 text-purple-800 border-purple-200';
                                $typeIcon = 'repeat';
                                $typeKey = 'reread';
                            }
                        ?>
                        <tr class="hover:bg-gray-50 transition-colors dispute-row" 
                            data-id="<?= htmlspecialchars($d['case_number']) ?>"
                            data-type="<?= $typeKey ?>"
                            data-typelabel="<?= htmlspecialchars($typeLabel) ?>"
                            data-branch="<?= htmlspecialchars($d['branch_name'] ?? 'Main') ?>"
                            data-date="<?= strtotime($d['created_at']) ?>"
                            data-case="<?= htmlspecialchars($d['case_number']) ?>"
                            data-name="<?= htmlspecialchars($d['first_name'] . ' ' . $d['last_name']) ?>"
                            data-search="<?= htmlspecialchars(strtolower($d['case_number'] . ' ' . $d['first_name'] . ' ' . $d['last_name'] . ' ' . ($d['branch_name'] ?? 'Main') . ' ' . $d['exam_type'] . ' ' . $notes . ' ' . $typeLabel)) ?>">
                            <td class="px-4 py-3.5 whitespace-nowrap">
                                <div class="font-mono text-xs font-semibold text-red-600"><?= htmlspecialchars($d['case_number']) ?></div>
                                <div class="font-bold text-gray-900"><?= htmlspecialchars($d['first_name'] . ' ' . $d['last_name']) ?></div>
                            </td>
                            <td class="px-4 py-3.5 whitespace-nowrap text-xs text-gray-600 font-medium">
                                <?= htmlspecialchars($d['branch_name'] ?? 'Main') ?>
                            </td>
                            <td class="px-4 py-3.5 whitespace-nowrap text-xs text-gray-800 font-medium">
                                <?php
                                $dExams = array_filter(array_map('trim', explode(',', $d['exam_type'] ?? '')));
                                $dFirstExam = reset($dExams) ?: 'General Exam';
                                $dCount = count($dExams);
                                ?>
                                <div class="flex items-center gap-1.5">
                                    <span class="truncate max-w-[130px]" title="<?= htmlspecialchars($d['exam_type'] ?? '') ?>">
                                        <?= htmlspecialchars($dFirstExam) ?>
                                    </span>
                                    <?php if ($dCount > 1): ?>
                                        <span class="inline-flex items-center justify-center rounded-full bg-gray-100 border border-gray-300 px-1.5 py-0.5 text-[10px] font-bold text-gray-600 cursor-default flex-shrink-0"
                                            title="<?= htmlspecialchars($d['exam_type'] ?? '') ?>">
                                            <?= $dCount ?>+
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="px-4 py-3.5 whitespace-nowrap">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[11px] font-bold border <?= $typeClass ?>">
                                    <i data-lucide="<?= $typeIcon ?>" class="w-3.5 h-3.5"></i>
                                    <?= htmlspecialchars($typeLabel) ?>
                                </span>
                            </td>
                            <td class="px-4 py-3.5 text-xs text-gray-700 max-w-[260px] lg:max-w-[320px] whitespace-normal break-words">
                                <?php
                                    $rawNotes = $d['radtech_notes'] ?? '';
                                    // Strip bracketed prefix like [Typographical Error], [New Image Re-uploaded], etc.
                                    $cleanNotes = trim(preg_replace('/^\s*\[(Typographical Error|New Image Re-uploaded|Re-reading Request|Dispute Escalation)\]\s*/i', '', $rawNotes));
                                    
                                    if (empty($cleanNotes)) {
                                        if (!empty($d['description'])) {
                                            $cleanNotes = $d['description'];
                                        } else {
                                            $cleanNotes = 'Forwarded for Radiologist review & report amendment.';
                                        }
                                    }
                                ?>
                                <div class="font-medium text-gray-800 italic">
                                    "<?= htmlspecialchars($cleanNotes) ?>"
                                </div>
                            </td>
                            <td class="px-4 py-3.5 text-xs text-gray-500 whitespace-nowrap">
                                <?= date('M j, Y h:i A', strtotime($d['created_at'])) ?>
                            </td>
                            <td class="px-4 py-3.5 whitespace-nowrap">
                                <a href="<?= PROJECT_DIR ? '/' . PROJECT_DIR . '/' : '/' ?>index.php?role=radiologist&page=case-review&id=<?= $d['case_id'] ?>&branch_id=<?= $d['branch_id'] ?>"
                                   class="inline-flex items-center rounded-lg bg-red-600 px-4 py-2 text-xs font-semibold text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 shadow-sm transition">
                                    <i data-lucide="edit" class="w-4 h-4 mr-1"></i> Amend Report
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Pagination footer for Disputes -->
    <div class="flex flex-col sm:flex-row items-center justify-between border-t border-gray-200 bg-gray-50 px-6 py-4 gap-4">
        <!-- Record count -->
        <span id="disputes-record-count" class="text-xs text-gray-500 font-medium"></span>

        <!-- Pagination Controls -->
        <div class="flex items-center flex-wrap gap-1.5" id="disputes-pagination-controls">
            <!-- Dynamic page buttons will be inserted here -->
        </div>
    </div>
</div>

<script>

    // Tab Switching for Radiologist (Worklist vs Escalated Disputes)
    window.switchRadTab = function(tab) {
        sessionStorage.setItem('Citilife_radWorklist_tab', tab);
        const workCard = document.getElementById('worklist-table-card');
        const dispCard = document.getElementById('rad-disputes-table-card');
        const workCtrls = document.getElementById('worklist-controls');
        const dispCtrls = document.getElementById('disputes-controls');
        const workBtn = document.getElementById('tab-rad-worklist-btn');
        const dispBtn = document.getElementById('tab-rad-disputes-btn');

        if (tab === 'worklist') {
            if (workCard) workCard.classList.remove('hidden');
            if (dispCard) dispCard.classList.add('hidden');
            if (workCtrls) workCtrls.classList.remove('hidden');
            if (dispCtrls) dispCtrls.classList.add('hidden');

            if (workBtn) workBtn.className = "pb-3 px-2 text-sm font-bold border-b-2 border-red-600 text-red-600 transition flex items-center gap-2";
            if (dispBtn) dispBtn.className = "pb-3 px-2 text-sm font-semibold border-b-2 border-transparent text-gray-500 hover:text-gray-700 transition flex items-center gap-2 relative";
        } else {
            if (workCard) workCard.classList.add('hidden');
            if (dispCard) dispCard.classList.remove('hidden');
            if (workCtrls) workCtrls.classList.add('hidden');
            if (dispCtrls) dispCtrls.classList.remove('hidden');

            if (dispBtn) dispBtn.className = "pb-3 px-2 text-sm font-bold border-b-2 border-red-600 text-red-600 transition flex items-center gap-2 relative";
            if (workBtn) workBtn.className = "pb-3 px-2 text-sm font-semibold border-b-2 border-transparent text-gray-500 hover:text-gray-700 transition flex items-center gap-2";
            
            if (typeof updateDisputesTable === 'function') {
                updateDisputesTable();
            }
        }
    };

    // Search, Filter, Sort, Pagination & State Persistence Logic
    document.addEventListener('DOMContentLoaded', () => {
        const searchInput = document.getElementById('searchInput');
        const filterBranch = document.getElementById('filterBranch');
        const filterPriority = document.getElementById('filterPriority');
        const filterDate = document.getElementById('filterDate');
        const sortOption = document.getElementById('sortOption');
        const worklistTbody = document.getElementById('worklist-tbody') || document.querySelector('tbody');
        let allRows = Array.from(document.querySelectorAll('tr.record-row'));

        const ROWS_PER_PAGE = 8;
        let currentPage = 1;
        
        const disputeSearchInput = document.getElementById('disputeSearchInput');
        const disputeFilterType = document.getElementById('disputeFilterType');
        const disputeFilterBranch = document.getElementById('disputeFilterBranch');
        const disputeSortOption = document.getElementById('disputeSortOption');
        const disputesTbody = document.getElementById('disputes-tbody');
        let allDisputeRows = Array.from(document.querySelectorAll('tr.dispute-row'));
        const DISPUTES_PER_PAGE = 8;
        let currentDisputesPage = 1;

        function saveWorklistState() {
            if (searchInput) sessionStorage.setItem('Citilife_radWorklist_search', searchInput.value);
            if (filterBranch) sessionStorage.setItem('Citilife_radWorklist_branch', filterBranch.value);
            if (filterPriority) sessionStorage.setItem('Citilife_radWorklist_priority', filterPriority.value);
            if (filterDate) sessionStorage.setItem('Citilife_radWorklist_date', filterDate.value);
            if (sortOption) sessionStorage.setItem('Citilife_radWorklist_sort', sortOption.value);
            sessionStorage.setItem('Citilife_radWorklist_page', currentPage);
            sessionStorage.setItem('Citilife_radWorklist_disputesPage', currentDisputesPage);
        }

        function restoreWorklistState() {
            const params = new URLSearchParams(window.location.search);
            const hasHighlight = params.has('highlight_case') || params.has('highlight') || params.has('case_id') || params.has('highlight_dispute_case');

            if (params.has('branch')) {
                if (filterBranch) filterBranch.value = params.get('branch');
            } else if (filterBranch) {
                const savedBranch = sessionStorage.getItem('Citilife_radWorklist_branch');
                if (savedBranch !== null) filterBranch.value = savedBranch;
            }

            if (params.has('priority')) {
                if (filterPriority) filterPriority.value = params.get('priority');
            } else if (filterPriority) {
                const savedPriority = sessionStorage.getItem('Citilife_radWorklist_priority');
                if (savedPriority !== null) filterPriority.value = savedPriority;
            }

            if (params.has('date') || params.has('filterDate')) {
                const dParam = params.get('date') || params.get('filterDate');
                if (filterDate) filterDate.value = dParam;
            } else if (filterDate) {
                const savedDate = sessionStorage.getItem('Citilife_radWorklist_date');
                if (savedDate !== null && !params.has('status')) filterDate.value = savedDate;
            }

            if (params.has('sort')) {
                if (sortOption) sortOption.value = params.get('sort');
            } else if (sortOption) {
                const savedSort = sessionStorage.getItem('Citilife_radWorklist_sort');
                if (savedSort !== null) sortOption.value = savedSort;
            }

            if (params.has('search')) {
                if (searchInput) searchInput.value = params.get('search');
            } else if (searchInput) {
                const savedSearch = sessionStorage.getItem('Citilife_radWorklist_search');
                if (savedSearch !== null) searchInput.value = savedSearch;
            }

            if (!hasHighlight) {
                const savedPage = parseInt(sessionStorage.getItem('Citilife_radWorklist_page'));
                if (savedPage && savedPage > 0) {
                    currentPage = savedPage;
                }
                const savedDisputesPage = parseInt(sessionStorage.getItem('Citilife_radWorklist_disputesPage'));
                if (savedDisputesPage && savedDisputesPage > 0) {
                    currentDisputesPage = savedDisputesPage;
                }
                const savedTab = sessionStorage.getItem('Citilife_radWorklist_tab');
                if (savedTab && !params.has('tab') && !params.has('status')) {
                    window.switchRadTab(savedTab);
                }
            }
        }

        function updateTable() {
            if (!searchInput || !filterBranch || !filterPriority || !sortOption || !worklistTbody) return;

            const searchTerm = searchInput.value.toLowerCase().trim();
            const branchValue = filterBranch.value;
            const priorityValue = filterPriority.value;
            const dateValue = filterDate ? filterDate.value : 'All';
            const sortValue = sortOption.value;

            // Update Title dynamically based on selected branch
            const worklistTitle = document.getElementById('worklist-title');
            const worklistSubtitle = document.getElementById('worklist-subtitle');
            if (worklistTitle && worklistSubtitle) {
                if (branchValue) {
                    worklistTitle.innerText = "Worklist - " + branchValue;
                    worklistSubtitle.innerText = "Manage pending cases for " + branchValue + " branch";
                } else {
                    worklistTitle.innerText = "Worklist";
                    worklistSubtitle.innerText = "Manage pending cases across all branches";
                }
            }

            // Accurate sorting
            allRows.sort((a, b) => {
                const dateA = parseInt(a.dataset.date) || 0;
                const dateB = parseInt(b.dataset.date) || 0;
                const weightA = parseInt(a.dataset.pweight) || 0;
                const weightB = parseInt(b.dataset.pweight) || 0;

                if (sortValue === 'date_desc') {
                    return dateB - dateA;
                } else if (sortValue === 'date_asc') {
                    return dateA - dateB;
                } else if (sortValue === 'priority_desc') {
                    if (weightB !== weightA) return weightB - weightA;
                    return dateB - dateA;
                } else if (sortValue === 'priority_asc') {
                    if (weightA !== weightB) return weightA - weightB;
                    return dateB - dateA;
                }
                return dateB - dateA;
            });

            // Reorder in DOM
            allRows.forEach(row => worklistTbody.appendChild(row));

            // Apply filtering
            let filteredRows = [];
            allRows.forEach(row => {
                const matchesSearch = row.dataset.search.includes(searchTerm);
                const matchesBranch = branchValue === '' || row.dataset.branch === branchValue;
                let rowPriority = row.dataset.priority;
                let mappedPriority = rowPriority;
                if (rowPriority === 'Normal' || rowPriority === 'Priority') {
                    mappedPriority = 'Routine';
                }
                const matchesPriority = priorityValue === '' || rowPriority === priorityValue || mappedPriority === priorityValue;

                const isToday = row.dataset.isToday === 'true';
                let matchesDate = true;
                if (dateValue === 'Today') {
                    matchesDate = isToday;
                } else if (dateValue === 'Backlog') {
                    matchesDate = !isToday;
                }

                if (matchesSearch && matchesBranch && matchesPriority && matchesDate) {
                    filteredRows.push(row);
                } else {
                    row.style.display = 'none';
                }
            });

            // Pagination calculation
            const totalPages = Math.max(1, Math.ceil(filteredRows.length / ROWS_PER_PAGE));
            if (currentPage > totalPages) currentPage = totalPages;
            if (currentPage < 1) currentPage = 1;

            const startIdx = (currentPage - 1) * ROWS_PER_PAGE;
            const endIdx = startIdx + ROWS_PER_PAGE;

            const visibleSet = new Set(filteredRows.slice(startIdx, endIdx));

            filteredRows.forEach(row => {
                row.style.display = visibleSet.has(row) ? '' : 'none';
            });

            // Handle "No records found" state
            let noRecordsRow = worklistTbody.querySelector('.no-records');
            let emptyStateRow = worklistTbody.querySelector('.empty-state-row');

            if (emptyStateRow && emptyStateRow.style.display !== 'none' && allRows.length === 0) {
                return;
            }

            if (filteredRows.length === 0 && allRows.length > 0) {
                if (!noRecordsRow) {
                    noRecordsRow = document.createElement('tr');
                    noRecordsRow.className = 'no-records';
                    noRecordsRow.innerHTML = `<td colspan="8" class="text-center py-8 text-gray-500">No matching records found.</td>`;
                    worklistTbody.appendChild(noRecordsRow);
                } else {
                    noRecordsRow.style.display = '';
                    worklistTbody.appendChild(noRecordsRow);
                }
            } else if (noRecordsRow) {
                noRecordsRow.style.display = 'none';
            }

            // Update Pagination UI
            updatePaginationUI(filteredRows.length, totalPages);
        }

        function updatePaginationUI(totalFiltered, totalPages) {
            const recordCountInfo = document.getElementById('worklist-record-count');
            const container = document.getElementById('worklist-pagination-controls');

            const startIdx = totalFiltered === 0 ? 0 : (currentPage - 1) * ROWS_PER_PAGE + 1;
            const endIdx = Math.min(currentPage * ROWS_PER_PAGE, totalFiltered);

            if (recordCountInfo) {
                recordCountInfo.innerHTML = totalFiltered === 0
                    ? 'No records'
                    : `Showing <span class="font-semibold text-gray-800">${startIdx}</span> to <span class="font-semibold text-gray-800">${endIdx}</span> of <span class="font-semibold text-gray-800">${totalFiltered}</span> record${totalFiltered !== 1 ? 's' : ''}`;
            }

            if (!container) return;
            container.innerHTML = '';

            // Helper to create a button
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
                        currentPage = page;
                        saveWorklistState();
                        updateTable();
                        const card = document.getElementById('worklist-table-card');
                        if (card) {
                            card.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        }
                    };
                }
                return btn;
            }

            // Helper to create ellipsis
            function createEllipsis() {
                const span = document.createElement('span');
                span.className = "px-2 py-1 text-xs text-gray-400 font-semibold select-none";
                span.innerText = '...';
                return span;
            }

            // First Button
            container.appendChild(createButton('&laquo; First', 1, currentPage <= 1));

            // Back Button
            container.appendChild(createButton('&lsaquo; Back', currentPage - 1, currentPage <= 1));

            // Page numbers
            if (totalPages <= 7) {
                for (let i = 1; i <= totalPages; i++) {
                    container.appendChild(createButton(i, i, false, i == currentPage));
                }
            } else {
                if (currentPage <= 4) {
                    for (let i = 1; i <= 5; i++) {
                        container.appendChild(createButton(i, i, false, i == currentPage));
                    }
                    container.appendChild(createEllipsis());
                    container.appendChild(createButton(totalPages, totalPages, false, totalPages == currentPage));
                } else if (currentPage >= totalPages - 3) {
                    container.appendChild(createButton(1, 1, false, 1 == currentPage));
                    container.appendChild(createEllipsis());
                    for (let i = totalPages - 4; i <= totalPages; i++) {
                        container.appendChild(createButton(i, i, false, i == currentPage));
                    }
                } else {
                    container.appendChild(createButton(1, 1, false, 1 == currentPage));
                    container.appendChild(createEllipsis());

                    container.appendChild(createButton(currentPage - 1, currentPage - 1, false, false));
                    container.appendChild(createButton(currentPage, currentPage, false, true));
                    container.appendChild(createButton(currentPage + 1, currentPage + 1, false, false));

                    container.appendChild(createEllipsis());
                    container.appendChild(createButton(totalPages, totalPages, false, false));
                }
            }

            // Next Button
            container.appendChild(createButton('Next &rsaquo;', currentPage + 1, currentPage >= totalPages));

            // Last Button
            container.appendChild(createButton('Last &raquo;', totalPages, currentPage >= totalPages));
        }

        window.updateDisputesTable = function() {
            if (!disputesTbody) return;

            const searchTerm = disputeSearchInput ? disputeSearchInput.value.toLowerCase().trim() : '';
            const typeValue = disputeFilterType ? disputeFilterType.value : '';
            const branchValue = disputeFilterBranch ? disputeFilterBranch.value : '';
            const sortValue = disputeSortOption ? disputeSortOption.value : 'date_desc';

            // Sorting
            allDisputeRows.sort((a, b) => {
                const dateA = parseInt(a.dataset.date) || 0;
                const dateB = parseInt(b.dataset.date) || 0;
                const typeA = (a.dataset.typelabel || '').toLowerCase();
                const typeB = (b.dataset.typelabel || '').toLowerCase();
                const caseA = (a.dataset.case || '').toLowerCase();
                const caseB = (b.dataset.case || '').toLowerCase();
                const nameA = (a.dataset.name || '').toLowerCase();
                const nameB = (b.dataset.name || '').toLowerCase();

                if (sortValue === 'date_desc') return dateB - dateA;
                if (sortValue === 'date_asc') return dateA - dateB;
                if (sortValue === 'type_asc') return typeA.localeCompare(typeB);
                if (sortValue === 'case_asc') return caseA.localeCompare(caseB);
                if (sortValue === 'name_asc') return nameA.localeCompare(nameB);
                return dateB - dateA;
            });

            // Reorder in DOM
            allDisputeRows.forEach(row => disputesTbody.appendChild(row));

            let filteredRows = [];
            allDisputeRows.forEach(row => {
                const matchesSearch = !searchTerm || row.dataset.search.includes(searchTerm);
                const matchesType = !typeValue || row.dataset.type === typeValue;
                const matchesBranch = !branchValue || row.dataset.branch === branchValue;

                if (matchesSearch && matchesType && matchesBranch) {
                    filteredRows.push(row);
                } else {
                    row.style.display = 'none';
                }
            });

            let emptyRow = disputesTbody.querySelector('.empty-disputes-row');
            if (filteredRows.length === 0 && allDisputeRows.length > 0) {
                if (!emptyRow) {
                    emptyRow = document.createElement('tr');
                    emptyRow.className = 'empty-disputes-row';
                    emptyRow.innerHTML = '<td colspan="7" class="text-center py-8 text-gray-500">No matching escalated error reports found.</td>';
                    disputesTbody.appendChild(emptyRow);
                } else {
                    emptyRow.style.display = '';
                    disputesTbody.appendChild(emptyRow);
                }
            } else if (emptyRow) {
                emptyRow.style.display = 'none';
            }

            const totalPages = Math.max(1, Math.ceil(filteredRows.length / DISPUTES_PER_PAGE));
            if (currentDisputesPage > totalPages) currentDisputesPage = totalPages;
            if (currentDisputesPage < 1) currentDisputesPage = 1;

            const startIdx = (currentDisputesPage - 1) * DISPUTES_PER_PAGE;
            const endIdx = startIdx + DISPUTES_PER_PAGE;

            filteredRows.forEach((row, idx) => {
                row.style.display = (idx >= startIdx && idx < endIdx) ? '' : 'none';
            });

            updateDisputesPaginationUI(filteredRows.length, totalPages);
            if (window.lucide) lucide.createIcons();
        };

        function updateDisputesPaginationUI(totalFiltered, totalPages) {
            const recordCountInfo = document.getElementById('disputes-record-count');
            const container = document.getElementById('disputes-pagination-controls');

            const startIdx = totalFiltered === 0 ? 0 : (currentDisputesPage - 1) * DISPUTES_PER_PAGE + 1;
            const endIdx = Math.min(currentDisputesPage * DISPUTES_PER_PAGE, totalFiltered);

            if (recordCountInfo) {
                recordCountInfo.innerHTML = totalFiltered === 0
                    ? 'No records'
                    : `Showing <span class="font-semibold text-gray-800">${startIdx}</span> to <span class="font-semibold text-gray-800">${endIdx}</span> of <span class="font-semibold text-gray-800">${totalFiltered}</span> record${totalFiltered !== 1 ? 's' : ''}`;
            }

            if (!container) return;
            container.innerHTML = '';

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
                        saveWorklistState();
                        updateDisputesTable();
                        const card = document.getElementById('rad-disputes-table-card');
                        if (card) {
                            card.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        }
                    };
                }
                return btn;
            }

            function createEllipsis() {
                const span = document.createElement('span');
                span.className = "px-2 py-1 text-xs text-gray-400 font-semibold select-none";
                span.innerText = '...';
                return span;
            }

            container.appendChild(createButton('&laquo; First', 1, currentDisputesPage <= 1));
            container.appendChild(createButton('&lsaquo; Back', currentDisputesPage - 1, currentDisputesPage <= 1));

            if (totalPages <= 7) {
                for (let i = 1; i <= totalPages; i++) {
                    container.appendChild(createButton(i, i, false, i == currentDisputesPage));
                }
            } else {
                if (currentDisputesPage <= 4) {
                    for (let i = 1; i <= 5; i++) {
                        container.appendChild(createButton(i, i, false, i == currentDisputesPage));
                    }
                    container.appendChild(createEllipsis());
                    container.appendChild(createButton(totalPages, totalPages, false, totalPages == currentDisputesPage));
                } else if (currentDisputesPage >= totalPages - 3) {
                    container.appendChild(createButton(1, 1, false, 1 == currentDisputesPage));
                    container.appendChild(createEllipsis());
                    for (let i = totalPages - 4; i <= totalPages; i++) {
                        container.appendChild(createButton(i, i, false, i == currentDisputesPage));
                    }
                } else {
                    container.appendChild(createButton(1, 1, false, 1 == currentDisputesPage));
                    container.appendChild(createEllipsis());
                    container.appendChild(createButton(currentDisputesPage - 1, currentDisputesPage - 1, false, false));
                    container.appendChild(createButton(currentDisputesPage, currentDisputesPage, false, true));
                    container.appendChild(createButton(currentDisputesPage + 1, currentDisputesPage + 1, false, false));
                    container.appendChild(createEllipsis());
                    container.appendChild(createButton(totalPages, totalPages, false, false));
                }
            }

            container.appendChild(createButton('Next &rsaquo;', currentDisputesPage + 1, currentDisputesPage >= totalPages));
            container.appendChild(createButton('Last &raquo;', totalPages, currentDisputesPage >= totalPages));
        }

        // Event listeners for Disputes tab
        if (disputeSearchInput) {
            disputeSearchInput.addEventListener('input', () => {
                currentDisputesPage = 1;
                updateDisputesTable();
            });
        }
        if (disputeFilterType) {
            disputeFilterType.addEventListener('change', () => {
                currentDisputesPage = 1;
                updateDisputesTable();
            });
        }
        if (disputeFilterBranch) {
            disputeFilterBranch.addEventListener('change', () => {
                currentDisputesPage = 1;
                updateDisputesTable();
            });
        }
        if (disputeSortOption) {
            disputeSortOption.addEventListener('change', () => {
                currentDisputesPage = 1;
                updateDisputesTable();
            });
        }

        // Reset to page 1 on filter/sort change
        function onFilterSortChange() {
            currentPage = 1;
            currentDisputesPage = 1;
            saveWorklistState();
            updateTable();
            updateDisputesTable();
        }

        if (searchInput) searchInput.addEventListener('input', onFilterSortChange);
        if (filterBranch) filterBranch.addEventListener('change', onFilterSortChange);
        if (filterPriority) filterPriority.addEventListener('change', onFilterSortChange);
        if (filterDate) filterDate.addEventListener('change', onFilterSortChange);
        if (sortOption) sortOption.addEventListener('change', onFilterSortChange);

        function handleHighlight() {
            const urlParams = new URLSearchParams(window.location.search);
            const currentTabParam = urlParams.get('tab') || urlParams.get('status');
            
            // Handle Disputes Tab
            const disputeCase = urlParams.get('highlight_dispute_case') || (currentTabParam === 'disputes' ? (urlParams.get('highlight_case') || urlParams.get('highlight') || urlParams.get('case_id')) : null);
            if (disputeCase) {
                if (typeof switchRadTab === 'function') switchRadTab('disputes');
                const dispRows = document.querySelectorAll('.dispute-row');
                const row = Array.from(dispRows).find(r => 
                    (r.dataset.id || '').toLowerCase() === disputeCase.toLowerCase() ||
                    (r.dataset.case || '').toLowerCase() === disputeCase.toLowerCase() ||
                    (r.dataset.caseId || '').toLowerCase() === disputeCase.toLowerCase() ||
                    r.innerText.toLowerCase().includes(disputeCase.toLowerCase())
                );
                if (row) {
                    const dispIndex = Array.from(dispRows).indexOf(row);
                    currentDisputesPage = Math.floor(dispIndex / DISPUTES_PER_PAGE) + 1;
                    updateDisputesTable();

                    setTimeout(() => {
                        row.style.display = '';
                        row.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        
                        // Insert notification highlight banner at the top header (like other modules)
                        const existingBanner = document.getElementById('highlight-banner');
                        if (existingBanner) existingBanner.remove();

                        const banner = document.createElement('div');
                        banner.id = 'highlight-banner';
                        banner.innerHTML = `<div style="display:flex;align-items:center;gap:0.5rem;"><svg xmlns='http://www.w3.org/2000/svg' width='18' height='18' fill='none' stroke='currentColor' stroke-width='2' viewBox='0 0 24 24'><circle cx='12' cy='12' r='10'/><line x1='12' y1='8' x2='12' y2='12'/><line x1='12' y1='16' x2='12.01' y2='16'/></svg><span>Navigated from notification — Error report for Case <strong>${disputeCase}</strong> is highlighted below.</span></div>`;
                        banner.style.cssText = 'margin-left:auto;padding:0.6rem 1rem;border-radius:0.75rem;background:#fefce8;border:1px solid #fde047;color:#854d0e;font-size:0.875rem;font-weight:500;display:flex;align-items:center;gap:0.5rem;';
                        
                        const header = document.querySelector('h2');
                        if (header && header.parentElement) {
                            header.parentElement.insertAdjacentElement('afterend', banner);
                        } else {
                            const topHeader = document.querySelector('h1') || document.querySelector('.flex.justify-between.items-center');
                            if (topHeader && topHeader.parentElement) {
                                topHeader.parentElement.insertAdjacentElement('afterend', banner);
                            }
                        }

                        setTimeout(() => {
                            banner.style.transition = 'opacity 0.5s';
                            banner.style.opacity = '0';
                            setTimeout(() => banner.remove(), 500);
                        }, 6000);

                        // Flash highlight ring and background animation
                        row.classList.add('transition-all', 'duration-300', 'ring-2', 'ring-amber-400');
                        row.style.backgroundColor = '#fef08a';
                        
                        let flashCount = 0;
                        const flashInterval = setInterval(() => {
                            flashCount++;
                            row.style.backgroundColor = (flashCount % 2 === 1) ? '#fde047' : '#fef08a';
                            if (flashCount >= 6) {
                                clearInterval(flashInterval);
                                setTimeout(() => {
                                    row.style.transition = 'background-color 2s ease, box-shadow 2s ease';
                                    row.style.backgroundColor = '';
                                    row.classList.remove('ring-2', 'ring-amber-400');
                                }, 1200);
                            }
                        }, 250);

                        const newUrl = new URL(window.location);
                        newUrl.searchParams.delete('highlight_dispute_case');
                        newUrl.searchParams.delete('highlight_case');
                        newUrl.searchParams.delete('highlight');
                        newUrl.searchParams.delete('is_new');
                        window.history.replaceState({}, document.title, newUrl.toString());
                    }, 200);
                }
            }

            // Handle Main Worklist
            const highlightCase = urlParams.get('highlight_case') || urlParams.get('highlight') || urlParams.get('case_id');
            if (highlightCase) {
                if (typeof switchRadTab === 'function') switchRadTab('worklist');
                const mainRows = document.querySelectorAll('.record-row');
                const row = Array.from(mainRows).find(r => 
                    (r.dataset.id || '').toLowerCase() === highlightCase.toLowerCase() ||
                    (r.dataset.caseId || '').toLowerCase() === highlightCase.toLowerCase()
                );
                if (row) {
                    const index = Array.from(mainRows).indexOf(row);
                    currentPage = Math.floor(index / ROWS_PER_PAGE) + 1;
                    updateTable();

                    setTimeout(() => {
                        row.style.display = '';
                        const tableWrapper = row.closest('.overflow-y-auto');
                        if (tableWrapper) {
                            tableWrapper.scrollTo({ top: row.offsetTop - tableWrapper.offsetTop - 40, behavior: 'smooth' });
                        } else {
                            row.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        }

                        row.style.transition = 'background-color 0.3s ease';
                        row.style.backgroundColor = '#fef08a';
                        setTimeout(() => {
                            row.style.backgroundColor = '#fde047';
                            setTimeout(() => {
                                row.style.backgroundColor = '#fef08a';
                                setTimeout(() => {
                                    row.style.backgroundColor = '#fde047';
                                    setTimeout(() => {
                                        row.style.transition = 'background-color 1.5s ease';
                                        row.style.backgroundColor = '';
                                    }, 400);
                                }, 400);
                            }, 400);
                        }, 200);

                        // Banner feedback
                        const existingBanner = document.getElementById('highlight-banner');
                        if (existingBanner) existingBanner.remove();

                        const banner = document.createElement('div');
                        banner.id = 'highlight-banner';
                        banner.innerHTML = `<div style="display:flex;align-items:center;gap:0.5rem;"><svg xmlns='http://www.w3.org/2000/svg' width='18' height='18' fill='none' stroke='currentColor' stroke-width='2' viewBox='0 0 24 24'><circle cx='12' cy='12' r='10'/><line x1='12' y1='8' x2='12' y2='12'/><line x1='12' y1='16' x2='12.01' y2='16'/></svg><span>Navigated from notification — Case <strong>${highlightCase}</strong> is highlighted below.</span></div>`;
                        banner.style.cssText = 'margin-left:auto;padding:0.6rem 1rem;border-radius:0.75rem;background:#fefce8;border:1px solid #fde047;color:#854d0e;font-size:0.875rem;font-weight:500;display:flex;align-items:center;gap:0.5rem;';
                        const header = document.querySelector('h2');
                        if (header && header.parentElement) {
                            header.parentElement.insertAdjacentElement('afterend', banner);
                        }
                        setTimeout(() => {
                            banner.style.transition = 'opacity 0.5s';
                            banner.style.opacity = '0';
                            setTimeout(() => banner.remove(), 500);
                        }, 6000);

                        const newUrl = new URL(window.location);
                        newUrl.searchParams.delete('highlight_case');
                        newUrl.searchParams.delete('highlight');
                        newUrl.searchParams.delete('case_id');
                        newUrl.searchParams.delete('is_new');
                        window.history.replaceState({}, document.title, newUrl.toString());
                    }, 200);
                }
            }
        }

        // Restore saved filters, page, and active tab from session
        restoreWorklistState();

        const paramsList = new window.URLSearchParams(window.location.search);
        if (paramsList.get('status') === 'disputes' || paramsList.get('tab') === 'disputes') {
            window.switchRadTab('disputes');
        }

        // Render tables
        updateTable();
        updateDisputesTable();

        handleHighlight();

        // ensure lucide icons are created if not already
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }

        // Real-time polling for Radiologist Worklist & Escalated Error Reports (every 3 seconds)
        let isSyncingWorklist = false;
        setInterval(() => {
            if (isSyncingWorklist) return;

            // Don't sync if user is actively searching or typing in search inputs
            const isTyping = document.activeElement && (
                document.activeElement === document.getElementById('disputeSearchInput') || 
                document.activeElement === document.getElementById('searchInput')
            );
            if (isTyping) return;

            isSyncingWorklist = true;
            fetch(window.location.href, { cache: 'no-store' })
                .then(res => res.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');

                    // 1. Sync Escalated Disputes Table
                    const newDisputesTbody = doc.getElementById('disputes-tbody');
                    const curDisputesTbody = document.getElementById('disputes-tbody');
                    if (newDisputesTbody && curDisputesTbody) {
                        const newContent = newDisputesTbody.innerHTML.trim();
                        const newRowCount = newDisputesTbody.querySelectorAll('tr.dispute-row').length;
                        const curRowCount = curDisputesTbody.querySelectorAll('tr.dispute-row').length;
                        if (newContent !== curDisputesTbody.innerHTML.trim() || newRowCount !== curRowCount) {
                            curDisputesTbody.innerHTML = newContent;
                            allDisputeRows = Array.from(curDisputesTbody.querySelectorAll('tr.dispute-row'));
                            updateDisputesTable();
                        }
                    }

                    // 2. Sync Disputes Tab Badge
                    const newDisputesBadge = doc.getElementById('disputes-tab-badge');
                    const curDisputesBadge = document.getElementById('disputes-tab-badge');
                    const disputesTabBtn = document.getElementById('tab-rad-disputes-btn');
                    if (newDisputesBadge && curDisputesBadge) {
                        curDisputesBadge.innerHTML = newDisputesBadge.innerHTML;
                        curDisputesBadge.className = newDisputesBadge.className;
                        if (newDisputesBadge.title) curDisputesBadge.title = newDisputesBadge.title;
                    } else if (newDisputesBadge && !curDisputesBadge && disputesTabBtn) {
                        disputesTabBtn.appendChild(newDisputesBadge);
                    } else if (!newDisputesBadge && curDisputesBadge) {
                        curDisputesBadge.remove();
                    }

                    // 3. Sync Pending Worklist Table
                    const newWorklistTbody = doc.getElementById('worklist-tbody');
                    const curWorklistTbody = document.getElementById('worklist-tbody');
                    if (newWorklistTbody && curWorklistTbody) {
                        const newWlContent = newWorklistTbody.innerHTML.trim();
                        const newWlCount = newWorklistTbody.querySelectorAll('tr.record-row').length;
                        const curWlCount = curWorklistTbody.querySelectorAll('tr.record-row').length;
                        if (newWlContent !== curWorklistTbody.innerHTML.trim() || newWlCount !== curWlCount) {
                            curWorklistTbody.innerHTML = newWlContent;
                            allRows = Array.from(curWorklistTbody.querySelectorAll('tr.record-row'));
                            updateTable();
                        }
                    }

                    // 4. Sync Worklist Tab Badge
                    const newWlBadge = doc.getElementById('worklist-tab-badge');
                    const curWlBadge = document.getElementById('worklist-tab-badge');
                    if (newWlBadge && curWlBadge) {
                        curWlBadge.innerHTML = newWlBadge.innerHTML;
                        curWlBadge.className = newWlBadge.className;
                        if (newWlBadge.title) curWlBadge.title = newWlBadge.title;
                    }

                    if (typeof lucide !== 'undefined') {
                        lucide.createIcons();
                    }
                })
                .catch(err => console.debug('Radiologist real-time sync error:', err))
                .finally(() => {
                    isSyncingWorklist = false;
                });
        }, 3000);
    });
</script>