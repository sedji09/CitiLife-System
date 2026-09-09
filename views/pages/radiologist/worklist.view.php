<?php
require_once __DIR__ . '/../../../config/database.php';

$branchModel = new \BranchModel($pdo);
$caseModel = new \CaseModel($pdo);

// Fetch all branches
$branchesList = $branchModel->getAllBranches();

// Fetch cases (Standardized via Model)
$radiologistId = $_SESSION['user_id'] ?? null;

// Support status URL filter for dashboard card deep-links
$statusParam = $_GET['status'] ?? '';
$tabParam = $_GET['tab'] ?? '';

// Support branch URL filter (by name or ID)
$urlBranch = $_GET['branch'] ?? '';
if (empty($urlBranch) && !empty($_GET['branch_id'])) {
    $bObj = $branchModel->getBranchById((int)$_GET['branch_id']);
    if (!empty($bObj['name'])) {
        $urlBranch = $bObj['name'];
        $_GET['branch'] = $urlBranch;
    }
}

// 1. Fetch Pending Worklist cases ('Pending', 'Under Reading', 'For Revision')
$pendingRecords = $caseModel->getWorklist(null, null, ['Pending', 'Under Reading', 'For Revision'], true, $radiologistId);

// 2. Fetch Pending Release cases ('Report Ready' awaiting release by RadTech, and completed reports)
$releaseRecords = $caseModel->getWorklist(null, null, ['Report Ready', 'Completed'], false, $radiologistId);
if ($statusParam === 'completed_today') {
    $releaseRecords = array_filter($releaseRecords, function ($r) {
        return !empty($r['date_completed']) && date('Y-m-d', strtotime($r['date_completed'])) === date('Y-m-d');
    });
    $releaseRecords = array_values($releaseRecords);
}

// Default records pointer for backward compatibility
$records = $pendingRecords;

// Extract unique priorities for filters from both lists
$allCombined = array_merge($pendingRecords, $releaseRecords);
$priorities = array_unique(array_column($allCombined, 'priority'));
sort($priorities);

// Determine initial tab
$initialTab = 'worklist';
if ($tabParam === 'release' || $statusParam === 'Report Ready' || $statusParam === 'completed_today') {
    $initialTab = 'release';
}
?>

<!-- Header -->
<div class="flex items-center justify-between mb-6">
    <div class="ml-5">
        <?php
        $dateParam = $_GET['date'] ?? $_GET['filterDate'] ?? '';
        $priorityParam = $_GET['priority'] ?? '';

        $wlTitle = ($initialTab === 'release') ? 'Pending Release' : 'Worklist';
        $wlSubtitle = ($initialTab === 'release') ? 'Cases with completed readings awaiting release' : 'Manage pending cases across all branches';
        if (!empty($urlBranch)) {
            $wlTitle = ($initialTab === 'release') ? "Pending Release - {$urlBranch}" : "Worklist - {$urlBranch}";
            $wlSubtitle = ($initialTab === 'release') ? "Cases with completed readings awaiting release for {$urlBranch} branch" : "Manage pending cases for {$urlBranch} branch";
        } elseif (strtolower($dateParam) === 'backlog') {
            $wlTitle = 'Backlog Cases';
            $wlSubtitle = 'Pending cases carried over from previous days';
        } elseif (strtoupper($priorityParam) === 'STAT') {
            $wlTitle = 'Pending STAT Cases';
            $wlSubtitle = 'High-priority STAT cases across all branches';
        } elseif ($statusParam === 'overdue') {
            $wlTitle = 'Overdue Cases';
            $wlSubtitle = 'Cases waiting 3+ hours without a completed reading';
        } elseif ($statusParam === 'completed_today') {
            $wlTitle = 'Completed Reports — Today';
            $wlSubtitle = 'Reports submitted or completed today';
        } elseif (in_array(strtolower($statusParam), ['under reading', 'under_reading', 'in progress'])) {
            $wlTitle = 'In Progress Cases';
            $wlSubtitle = 'Cases opened by radiologist but findings not yet submitted';
        } elseif (in_array(strtolower($statusParam), ['for revision', 'for_revision'])) {
            $wlTitle = 'Cases For Revision';
            $wlSubtitle = 'Cases flagged for editing or correction';
        } elseif (strtolower($statusParam) === 'pending') {
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
        <!-- Tab 1: Pending Worklist -->
        <button type="button" id="tab-rad-worklist-btn" onclick="switchRadTab('worklist')"
            class="pb-3 px-2 text-sm font-bold border-b-2 border-red-600 text-red-600 transition flex items-center gap-2">
            Pending Worklist
            <?php
            $wlCount = count($pendingRecords);
            $wlDisplay = $wlCount > 99 ? '99+' : $wlCount;
            ?>
            <span id="worklist-tab-badge" class="tab-circle-badge bg-gray-100 text-gray-700 border border-gray-200"
                style="width: 26px; height: 26px; min-width: 26px; min-height: 26px; border-radius: 9999px; display: inline-flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 700; line-height: 1; flex-shrink: 0;"
                title="<?= $wlCount ?>">
                <?= $wlDisplay ?>
            </span>
        </button>

        <!-- Tab 2: Pending Release -->
        <button type="button" id="tab-rad-release-btn" onclick="switchRadTab('release')"
            class="pb-3 px-2 text-sm font-semibold border-b-2 border-transparent text-gray-500 hover:text-gray-700 transition flex items-center gap-2">
            Pending Release
            <?php
            $relCount = count($releaseRecords);
            $relDisplay = $relCount > 99 ? '99+' : $relCount;
            ?>
            <span id="release-tab-badge" class="tab-circle-badge bg-gray-100 text-gray-700 border border-gray-200"
                style="width: 26px; height: 26px; min-width: 26px; min-height: 26px; border-radius: 9999px; display: inline-flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 700; line-height: 1; flex-shrink: 0;"
                title="<?= $relCount ?>">
                <?= $relDisplay ?>
            </span>
        </button>
    </nav>
</div>

<!-- Controls for Worklist & Pending Release -->
<div id="worklist-controls" class="relative z-30 mt-6 px-4">
    <div class="flex flex-wrap items-center gap-2.5 w-full">
        <!-- Search -->
        <div class="relative flex-1 max-w-[320px] min-w-[200px] group shrink-0">
            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                <i data-lucide="search" class="w-4 h-4 text-gray-400 group-hover:text-red-500 transition-colors"></i>
            </div>
            <input type="text" id="searchInput" placeholder="Search by case no, patient name, branch..."
                class="block w-full pl-10 pr-3 py-2.5 rounded-xl border border-gray-200 bg-white text-xs sm:text-sm text-gray-900 outline-none focus:ring-2 focus:ring-red-500/10 focus:border-red-500 transition-all shadow-sm font-normal">
        </div>

        <!-- Filter by Branch -->
        <select id="filterBranch"
            class="w-32 lg:w-36 shrink-0 px-2.5 py-2.5 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-red-500/10 focus:border-red-500 text-xs sm:text-sm bg-white shadow-sm font-normal text-gray-600 cursor-pointer">
            <option value="">All Branches</option>
            <?php foreach ($branchesList as $b): ?>
                <option value="<?= htmlspecialchars($b['name']) ?>" <?= $urlBranch === $b['name'] ? 'selected' : '' ?>><?= htmlspecialchars($b['name']) ?></option>
            <?php endforeach; ?>
        </select>

        <!-- Filter by Priority -->
        <?php
        $rawPriorityUrl = strtoupper(trim($_GET['priority'] ?? ''));
        ?>
        <select id="filterPriority"
            class="w-28 lg:w-32 shrink-0 px-2.5 py-2.5 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-red-500/10 focus:border-red-500 text-xs sm:text-sm bg-white shadow-sm font-normal text-gray-600 cursor-pointer">
            <option value="" <?= ($rawPriorityUrl === '' || $rawPriorityUrl === 'ALL') ? 'selected' : '' ?>>All Priorities</option>
            <option value="STAT" <?= $rawPriorityUrl === 'STAT' ? 'selected' : '' ?>>STAT</option>
            <option value="Urgent" <?= $rawPriorityUrl === 'URGENT' ? 'selected' : '' ?>>Urgent</option>
            <option value="Routine" <?= $rawPriorityUrl === 'ROUTINE' ? 'selected' : '' ?>>Routine</option>
        </select>

        <!-- Filter by Status -->
        <?php 
        $rawStatusUrl = $_GET['status'] ?? $_GET['filterStatus'] ?? '';
        $normalizedStatus = '';
        $lowerStatusUrl = strtolower(trim($rawStatusUrl));
        if ($lowerStatusUrl === 'overdue') {
            $normalizedStatus = 'Overdue';
        } elseif (in_array($lowerStatusUrl, ['under reading', 'under_reading', 'in progress', 'inprogress'])) {
            $normalizedStatus = 'In Progress';
        } elseif (in_array($lowerStatusUrl, ['for revision', 'for_revision'])) {
            $normalizedStatus = 'For Revision';
        } elseif ($lowerStatusUrl === 'pending') {
            $normalizedStatus = 'Pending';
        }
        ?>
        <select id="filterStatus"
            class="w-28 lg:w-32 shrink-0 px-2.5 py-2.5 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-red-500/10 focus:border-red-500 text-xs sm:text-sm bg-white shadow-sm font-normal text-gray-600 cursor-pointer">
            <option value="" <?= $normalizedStatus === '' ? 'selected' : '' ?>>All Statuses</option>
            <option value="For Revision" <?= $normalizedStatus === 'For Revision' ? 'selected' : '' ?>>For Revision</option>
            <option value="Pending" <?= $normalizedStatus === 'Pending' ? 'selected' : '' ?>>Pending</option>
            <option value="In Progress" <?= $normalizedStatus === 'In Progress' ? 'selected' : '' ?>>In Progress</option>
            <option value="Overdue" <?= $normalizedStatus === 'Overdue' ? 'selected' : '' ?>>Overdue</option>
        </select>

        <!-- Filter by Date -->
        <?php 
        $rawDateUrl = $_GET['date'] ?? $_GET['filterDate'] ?? '';
        $lowerDateUrl = strtolower(trim($rawDateUrl));
        if ($lowerDateUrl === 'backlog') {
            $urlDateFilter = 'Backlog';
        } elseif ($lowerDateUrl === 'all') {
            $urlDateFilter = 'All';
        } elseif ($lowerDateUrl === 'today') {
            $urlDateFilter = 'Today';
        } elseif (isset($_GET['highlight']) || isset($_GET['highlight_case']) || isset($_GET['status']) || isset($_GET['priority'])) {
            $urlDateFilter = 'All';
        } else {
            $urlDateFilter = 'Today';
        }
        ?>
        <select id="filterDate"
            class="w-28 lg:w-32 shrink-0 px-2.5 py-2.5 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-red-500/10 focus:border-red-500 text-xs sm:text-sm bg-white shadow-sm font-normal text-gray-600 cursor-pointer">
            <option value="All" <?= $urlDateFilter === 'All' ? 'selected' : '' ?>>All Dates</option>
            <option value="Today" <?= ($urlDateFilter === 'Today' || empty($urlDateFilter)) ? 'selected' : '' ?>>Today's Cases</option>
            <option value="Backlog" <?= $urlDateFilter === 'Backlog' ? 'selected' : '' ?>>Backlogs</option>
        </select>

        <!-- Sort by -->
        <select id="sortOption"
            class="w-36 lg:w-40 shrink-0 px-2.5 py-2.5 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-red-500/10 focus:border-red-500 text-xs sm:text-sm bg-white shadow-sm font-normal text-gray-600 cursor-pointer">
            <option value="date_desc">Newest Record</option>
            <option value="date_asc">Oldest Record</option>
            <option value="priority_desc">Priority (High-Low)</option>
            <option value="priority_asc">Priority (Low-High)</option>
        </select>
    </div>
</div>

<div class="px-4">
    <!-- TABLE CARD 1: Pending Worklist -->
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
                    <?php if (count($pendingRecords) === 0): ?>
                        <tr class="empty-state-row">
                            <td colspan="8" class="text-center py-8 text-gray-500">
                                No pending cases.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($pendingRecords as $row):
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
                                if (!empty($row['re_edit_reason'])) {
                                    $displayStatus = 'For Revision';
                                    $sBorder = '1.5px solid #f87171';
                                    $sBg = '#fef2f2';
                                    $sColor = '#b91c1c';
                                } else {
                                    $displayStatus = 'In Progress';
                                    $sBorder = '1.5px solid #60a5fa';
                                    $sBg = '#eff6ff';
                                    $sColor = '#1d4ed8';
                                }
                            } elseif ($rawStatus === 'For Revision') {
                                $displayStatus = 'For Revision';
                                $sBorder = '1.5px solid #f87171';
                                $sBg = '#fef2f2';
                                $sColor = '#b91c1c';
                            }
                            ?>
                            <?php $pFullName = formatFullName($row); ?>
                            <tr class="hover:bg-white/10 transition-colors record-row cursor-pointer"
                                data-id="<?= htmlspecialchars($row['case_number']) ?>"
                                data-case-id="<?= htmlspecialchars($row['id'] ?? '') ?>"
                                data-branch="<?= htmlspecialchars($row['branch_name']) ?>"
                                data-priority="<?= htmlspecialchars($row['priority']) ?>" data-stat="<?= $isEmergency ?>"
                                data-status="<?= htmlspecialchars($displayStatus) ?>"
                                data-pweight="<?= $pWeight ?>" data-is-today="<?= $isToday ? 'true' : 'false' ?>"
                                data-search="<?= htmlspecialchars(strtolower($row['case_number'] . ' ' . $pFullName . ' ' . $row['branch_name'] . ' ' . ($row['exam_type'] ?? ''))) ?>"
                                data-date="<?= strtotime($rowDate) ?>">
                                <td class="py-3 px-3 whitespace-nowrap">
                                    <div class="font-medium"><?= htmlspecialchars($row['case_number']) ?></div>
                                </td>
                                <td class="py-3 px-3 whitespace-nowrap">
                                    <div class="font-medium text-gray-600"><?= htmlspecialchars($row['branch_name']) ?></div>
                                </td>
                                <td class="py-3 px-3 truncate max-w-[200px]"
                                    title="<?= htmlspecialchars($pFullName) ?>">
                                    <div class="font-medium truncate">
                                        <?= htmlspecialchars($pFullName) ?>
                                    </div>
                                </td>
                                <td class="py-3 px-3 whitespace-nowrap text-xs text-gray-800 font-medium">
                                    <?php
                                    $pExams = array_filter(array_map('trim', explode(',', $row['exam_type'] ?? '')));
                                    $pFirstExam = reset($pExams) ?: 'General Exam';
                                    $pCount = count($pExams);
                                    ?>
                                    <div class="flex items-center gap-1.5">
                                        <span class="truncate max-w-[130px]"
                                            title="<?= htmlspecialchars($row['exam_type'] ?? '') ?>">
                                            <?= htmlspecialchars($pFirstExam) ?>
                                        </span>
                                        <?php if ($pCount > 1): ?>
                                            <span
                                                class="inline-flex items-center justify-center rounded-full bg-gray-100 border border-gray-300 px-1.5 py-0.5 text-[10px] font-bold text-gray-600 cursor-default flex-shrink-0"
                                                title="<?= htmlspecialchars($row['exam_type'] ?? '') ?>">
                                                <?= $pCount ?>+
                                            </span>
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
                                    } elseif ($row['priority'] === 'Urgent') {
                                        $pBorder = '1.5px solid #facc15';
                                        $pBg = '#fefce8';
                                        $pColor = '#a16207';
                                    } elseif ($row['priority'] === 'Priority') {
                                        $pBorder = '1.5px solid #fb923c';
                                        $pBg = '#fff7ed';
                                        $pColor = '#c2410c';
                                    }
                                    ?>
                                    <span
                                        class="priority-badge inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold shadow-2xs"
                                        style="border:<?= $pBorder ?>;background-color:<?= $pBg ?>;color:<?= $pColor ?>">
                                        <?= htmlspecialchars($row['priority']) ?>
                                    </span>
                                </td>
                                <td class="py-3 px-3 whitespace-nowrap">
                                    <div class="flex flex-col gap-1 items-start">
                                        <?php $submitDate = !empty($row['radtech_submitted_at']) ? $row['radtech_submitted_at'] : $row['created_at']; ?>
                                        <span class="text-sm text-gray-600"><?= date('M d, Y', strtotime($submitDate)) ?> <span
                                                class="opacity-70 ml-1"><?= date('h:i A', strtotime($submitDate)) ?></span></span>
                                        <?php if (!$isToday): ?>
                                            <span
                                                class="inline-block rounded bg-red-100 px-1.5 py-0.5 text-[10px] font-bold text-red-700 border border-red-200"
                                                title="This case was carried over from a previous day">BACKLOG</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="py-3 px-3">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold"
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

        <!-- Pagination footer for Pending Worklist -->
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

    <!-- TABLE CARD 2: Pending Release -->
    <div id="release-table-card"
        class="hidden rounded-xl border border-gray-300 bg-white shadow-sm mt-4 overflow-hidden">
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
                <tbody id="release-tbody" class="text-gray-800 bg-white divide-y divide-gray-100">
                    <?php if (count($releaseRecords) === 0): ?>
                        <tr class="empty-release-row">
                            <td colspan="8" class="text-center py-8 text-gray-500">
                                No cases pending release.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($releaseRecords as $row):
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
                            <?php $pFullName = formatFullName($row); ?>
                            <tr class="hover:bg-white/10 transition-colors release-record-row cursor-pointer"
                                data-id="<?= htmlspecialchars($row['case_number']) ?>"
                                data-case-id="<?= htmlspecialchars($row['id'] ?? '') ?>"
                                data-branch="<?= htmlspecialchars($row['branch_name']) ?>"
                                data-priority="<?= htmlspecialchars($row['priority']) ?>" data-stat="<?= $isEmergency ?>"
                                data-pweight="<?= $pWeight ?>" data-is-today="<?= $isToday ? 'true' : 'false' ?>"
                                data-search="<?= htmlspecialchars(strtolower($row['case_number'] . ' ' . $pFullName . ' ' . $row['branch_name'] . ' ' . ($row['exam_type'] ?? ''))) ?>"
                                data-date="<?= strtotime($rowDate) ?>">
                                <td class="py-3 px-3 whitespace-nowrap">
                                    <div class="font-medium"><?= htmlspecialchars($row['case_number']) ?></div>
                                </td>
                                <td class="py-3 px-3 whitespace-nowrap">
                                    <div class="font-medium text-gray-600"><?= htmlspecialchars($row['branch_name']) ?></div>
                                </td>
                                <td class="py-3 px-3 truncate max-w-[200px]"
                                    title="<?= htmlspecialchars($pFullName) ?>">
                                    <div class="font-medium truncate">
                                        <?= htmlspecialchars($pFullName) ?>
                                    </div>
                                </td>
                                <td class="py-3 px-3 whitespace-nowrap text-xs text-gray-800 font-medium">
                                    <?php
                                    $pExams = array_filter(array_map('trim', explode(',', $row['exam_type'] ?? '')));
                                    $pFirstExam = reset($pExams) ?: 'General Exam';
                                    $pCount = count($pExams);
                                    ?>
                                    <div class="flex items-center gap-1.5">
                                        <span class="truncate max-w-[130px]"
                                            title="<?= htmlspecialchars($row['exam_type'] ?? '') ?>">
                                            <?= htmlspecialchars($pFirstExam) ?>
                                        </span>
                                        <?php if ($pCount > 1): ?>
                                            <span
                                                class="inline-flex items-center justify-center rounded-full bg-gray-100 border border-gray-300 px-1.5 py-0.5 text-[10px] font-bold text-gray-600 cursor-default flex-shrink-0"
                                                title="<?= htmlspecialchars($row['exam_type'] ?? '') ?>">
                                                <?= $pCount ?>+
                                            </span>
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
                                    } elseif ($row['priority'] === 'Urgent') {
                                        $pBorder = '1.5px solid #facc15';
                                        $pBg = '#fefce8';
                                        $pColor = '#a16207';
                                    } elseif ($row['priority'] === 'Priority') {
                                        $pBorder = '1.5px solid #fb923c';
                                        $pBg = '#fff7ed';
                                        $pColor = '#c2410c';
                                    }
                                    ?>
                                    <span
                                        class="priority-badge inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold shadow-2xs"
                                        style="border:<?= $pBorder ?>;background-color:<?= $pBg ?>;color:<?= $pColor ?>">
                                        <?= htmlspecialchars($row['priority']) ?>
                                    </span>
                                </td>
                                <td class="py-3 px-3 whitespace-nowrap">
                                    <div class="flex flex-col gap-1 items-start">
                                        <?php $submitDate = !empty($row['radtech_submitted_at']) ? $row['radtech_submitted_at'] : $row['created_at']; ?>
                                        <span class="text-sm text-gray-600"><?= date('M d, Y', strtotime($submitDate)) ?> <span
                                                class="opacity-70 ml-1"><?= date('h:i A', strtotime($submitDate)) ?></span></span>
                                        <?php if (!$isToday): ?>
                                            <span
                                                class="inline-block rounded bg-red-100 px-1.5 py-0.5 text-[10px] font-bold text-red-700 border border-red-200"
                                                title="This case was carried over from a previous day">BACKLOG</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="py-3 px-3">
                                    <?php
                                    $rawRelStatus = $row['status'] ?? 'Report Ready';
                                    if ($rawRelStatus === 'Completed') {
                                        $sBorder = '1.5px solid #4ade80';
                                        $sBg = '#f0fdf4';
                                        $sColor = '#15803d';
                                    } else {
                                        $sBorder = '1.5px solid #818cf8';
                                        $sBg = '#eef2ff';
                                        $sColor = '#4338ca';
                                    }
                                    ?>
                                    <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-semibold"
                                        style="border:<?= $sBorder ?>;background-color:<?= $sBg ?>;color:<?= $sColor ?>">
                                        <?= htmlspecialchars($rawRelStatus) ?>
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

        <!-- Pagination footer for Pending Release -->
        <div
            class="flex flex-col sm:flex-row items-center justify-between border-t border-gray-200 bg-gray-50 px-6 py-4 gap-4">
            <!-- Record count -->
            <span id="release-record-count" class="text-xs text-gray-500 font-medium"></span>

            <!-- Pagination Controls -->
            <div class="flex items-center flex-wrap gap-1.5" id="release-pagination-controls">
                <!-- Dynamic page buttons will be inserted here -->
            </div>
        </div>
    </div>
</div>

<script>
    // Tab Switching for Radiologist: Pending Worklist vs Pending Release
    window.switchRadTab = function (tab) {
        sessionStorage.setItem('Citilife_radWorklist_tab', tab);
        try {
            const cleanUrl = new URL(window.location.href);
            if (tab === 'release') {
                cleanUrl.searchParams.set('tab', 'release');
            } else {
                cleanUrl.searchParams.delete('tab');
            }
            window.history.replaceState({}, document.title, cleanUrl.toString());
            sessionStorage.setItem('Citilife_last_worklist_url', cleanUrl.toString());
        } catch (e) {}
        const workCard = document.getElementById('worklist-table-card');
        const relCard = document.getElementById('release-table-card');
        const workBtn = document.getElementById('tab-rad-worklist-btn');
        const relBtn = document.getElementById('tab-rad-release-btn');
        const worklistTitle = document.getElementById('worklist-title');
        const worklistSubtitle = document.getElementById('worklist-subtitle');
        const branchValue = document.getElementById('filterBranch') ? document.getElementById('filterBranch').value : '';

        if (tab === 'release') {
            if (workCard) workCard.classList.add('hidden');
            if (relCard) relCard.classList.remove('hidden');

            if (relBtn) relBtn.className = "pb-3 px-2 text-sm font-bold border-b-2 border-red-600 text-red-600 transition flex items-center gap-2";
            if (workBtn) workBtn.className = "pb-3 px-2 text-sm font-semibold border-b-2 border-transparent text-gray-500 hover:text-gray-700 transition flex items-center gap-2";

            if (worklistTitle && worklistSubtitle) {
                if (branchValue) {
                    worklistTitle.innerText = "Pending Release - " + branchValue;
                    worklistSubtitle.innerText = "Cases with completed readings awaiting release for " + branchValue + " branch";
                } else {
                    worklistTitle.innerText = "Pending Release";
                    worklistSubtitle.innerText = "Cases with completed readings awaiting release across all branches";
                }
            }

            if (typeof updateReleaseTable === 'function') {
                updateReleaseTable();
            }
        } else {
            // tab === 'worklist'
            if (workCard) workCard.classList.remove('hidden');
            if (relCard) relCard.classList.add('hidden');

            if (workBtn) workBtn.className = "pb-3 px-2 text-sm font-bold border-b-2 border-red-600 text-red-600 transition flex items-center gap-2";
            if (relBtn) relBtn.className = "pb-3 px-2 text-sm font-semibold border-b-2 border-transparent text-gray-500 hover:text-gray-700 transition flex items-center gap-2";

            if (worklistTitle && worklistSubtitle) {
                if (branchValue) {
                    worklistTitle.innerText = "Worklist - " + branchValue;
                    worklistSubtitle.innerText = "Manage pending cases for " + branchValue + " branch";
                } else {
                    worklistTitle.innerText = "Worklist";
                    worklistSubtitle.innerText = "Manage pending cases across all branches";
                }
            }

            if (typeof updateTable === 'function') {
                updateTable();
            }
        }

        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    };

    // Search, Filter, Sort, Pagination & State Persistence Logic
    document.addEventListener('DOMContentLoaded', () => {
        const searchInput = document.getElementById('searchInput');
        const filterBranch = document.getElementById('filterBranch');
        const filterPriority = document.getElementById('filterPriority');
        const filterStatus = document.getElementById('filterStatus');
        const filterDate = document.getElementById('filterDate');
        const sortOption = document.getElementById('sortOption');

        const worklistTbody = document.getElementById('worklist-tbody');
        let allRows = Array.from(document.querySelectorAll('tr.record-row'));
        const ROWS_PER_PAGE = 7;
        let currentPage = 1;

        const releaseTbody = document.getElementById('release-tbody');
        let allReleaseRows = Array.from(document.querySelectorAll('tr.release-record-row'));
        const RELEASE_ROWS_PER_PAGE = 7;
        let currentReleasePage = 1;

        function saveWorklistState() {
            if (searchInput) sessionStorage.setItem('Citilife_radWorklist_search', searchInput.value);
            if (filterBranch) sessionStorage.setItem('Citilife_radWorklist_branch', filterBranch.value);
            if (filterPriority) sessionStorage.setItem('Citilife_radWorklist_priority', filterPriority.value);
            if (filterStatus) sessionStorage.setItem('Citilife_radWorklist_status', filterStatus.value);
            if (filterDate) sessionStorage.setItem('Citilife_radWorklist_date', filterDate.value);
            if (sortOption) sessionStorage.setItem('Citilife_radWorklist_sort', sortOption.value);
            sessionStorage.setItem('Citilife_radWorklist_page', currentPage);
            sessionStorage.setItem('Citilife_radWorklist_releasePage', currentReleasePage);
        }

        function restoreWorklistState() {
            const params = new URLSearchParams(window.location.search);
            const hasHighlight = params.has('highlight_case') || params.has('highlight') || params.has('case_id');

            if (hasHighlight) {
                if (filterDate) filterDate.value = 'All';
                if (params.has('branch')) {
                    if (filterBranch) filterBranch.value = params.get('branch');
                } else if (filterBranch && filterBranch.value) {
                    // Retain server pre-selection
                } else {
                    if (filterBranch) filterBranch.value = '';
                }
                if (filterPriority) filterPriority.value = '';
                if (filterStatus) filterStatus.value = '';
                if (searchInput) searchInput.value = '';
            } else {
                if (params.has('branch')) {
                    const rawB = (params.get('branch') || '').trim();
                    if (filterBranch) filterBranch.value = (rawB.toLowerCase() === 'all') ? '' : rawB;
                } else if (filterBranch) {
                    const savedBranch = sessionStorage.getItem('Citilife_radWorklist_branch');
                    if (savedBranch !== null) filterBranch.value = savedBranch;
                }

                if (params.has('priority')) {
                    const rawP = (params.get('priority') || '').trim();
                    const upperP = rawP.toUpperCase();
                    if (upperP === 'STAT') {
                        if (filterPriority) filterPriority.value = 'STAT';
                    } else if (upperP === 'URGENT') {
                        if (filterPriority) filterPriority.value = 'Urgent';
                    } else if (upperP === 'ROUTINE') {
                        if (filterPriority) filterPriority.value = 'Routine';
                    } else {
                        if (filterPriority) filterPriority.value = '';
                    }
                } else if (filterPriority) {
                    const savedPriority = sessionStorage.getItem('Citilife_radWorklist_priority');
                    if (savedPriority !== null) filterPriority.value = savedPriority;
                }

                if (params.has('status') || params.has('filterStatus')) {
                    const rawSt = (params.get('status') || params.get('filterStatus') || '').trim();
                    const lowerSt = rawSt.toLowerCase();
                    let mappedSt = '';
                    if (lowerSt === 'overdue') {
                        mappedSt = 'Overdue';
                    } else if (lowerSt === 'under reading' || lowerSt === 'under_reading' || lowerSt === 'in progress' || lowerSt === 'inprogress') {
                        mappedSt = 'In Progress';
                    } else if (lowerSt === 'for revision' || lowerSt === 'for_revision') {
                        mappedSt = 'For Revision';
                    } else if (lowerSt === 'pending') {
                        mappedSt = 'Pending';
                    } else {
                        mappedSt = ''; // All Statuses
                    }
                    if (filterStatus) filterStatus.value = mappedSt;
                } else if (filterStatus) {
                    const savedStatus = sessionStorage.getItem('Citilife_radWorklist_status');
                    if (savedStatus !== null) filterStatus.value = savedStatus;
                }

                if (params.has('date') || params.has('filterDate')) {
                    const rawD = (params.get('date') || params.get('filterDate') || '').trim();
                    const lowerD = rawD.toLowerCase();
                    if (lowerD === 'backlog') {
                        if (filterDate) filterDate.value = 'Backlog';
                    } else if (lowerD === 'today') {
                        if (filterDate) filterDate.value = 'Today';
                    } else if (lowerD === 'all') {
                        if (filterDate) filterDate.value = 'All';
                    } else if (filterDate) {
                        filterDate.value = rawD;
                    }
                } else if (filterDate) {
                    const savedDate = sessionStorage.getItem('Citilife_radWorklist_date');
                    if (savedDate !== null && !params.has('status') && !params.has('priority')) filterDate.value = savedDate;
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
            }

            if (!hasHighlight) {
                if (params.has('status') || params.has('priority') || params.has('date') || params.has('branch')) {
                    currentPage = 1;
                    currentReleasePage = 1;
                } else {
                    const savedPage = parseInt(sessionStorage.getItem('Citilife_radWorklist_page'));
                    if (savedPage && savedPage > 0) {
                        currentPage = savedPage;
                    }
                    const savedReleasePage = parseInt(sessionStorage.getItem('Citilife_radWorklist_releasePage'));
                    if (savedReleasePage && savedReleasePage > 0) {
                        currentReleasePage = savedReleasePage;
                    }
                }

                // Tab restoration logic
                if (params.get('tab') === 'release' || params.get('status') === 'completed_today') {
                    window.switchRadTab('release');
                } else if (params.get('tab') === 'worklist') {
                    window.switchRadTab('worklist');
                } else {
                    const savedTab = sessionStorage.getItem('Citilife_radWorklist_tab');
                    if (savedTab) {
                        window.switchRadTab(savedTab);
                    }
                }
            }

            // Sync custom select labels if already initialized
            [filterBranch, filterPriority, filterStatus, filterDate, sortOption].forEach(sel => {
                if (sel && sel._customSelect && typeof sel._customSelect.sync === 'function') {
                    sel._customSelect.sync();
                }
            });

            try {
                sessionStorage.setItem('Citilife_last_worklist_url', window.location.href);
            } catch (e) {}
        }

        // --- Pending Worklist Table Logic ---
        function updateTable() {
            if (!worklistTbody) return;

            const searchTerm = searchInput ? searchInput.value.toLowerCase().trim() : '';
            const branchValue = filterBranch ? filterBranch.value : '';
            const priorityValue = filterPriority ? filterPriority.value : '';
            const statusValue = filterStatus ? filterStatus.value : '';
            const dateValue = filterDate ? filterDate.value : 'All';
            const sortValue = sortOption ? sortOption.value : 'date_desc';

            // Update Title dynamically if currently on worklist tab
            const activeTab = sessionStorage.getItem('Citilife_radWorklist_tab') || 'worklist';
            if (activeTab === 'worklist') {
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
                const rowSearch = (row.dataset.search || '').toLowerCase();
                const matchesSearch = !searchTerm || rowSearch.includes(searchTerm);
                const matchesBranch = branchValue === '' || (row.dataset.branch || '') === branchValue;
                let rowPriority = row.dataset.priority || '';
                let mappedPriority = rowPriority;
                if (rowPriority === 'Normal' || rowPriority === 'Priority') {
                    mappedPriority = 'Routine';
                }
                const matchesPriority = priorityValue === '' || rowPriority === priorityValue || mappedPriority === priorityValue;

                const rowStatus = row.dataset.status || '';
                let matchesStatus = true;
                if (statusValue !== '') {
                    if (statusValue === 'Pending') {
                        // "Pending" includes both regular Pending and Overdue cases
                        matchesStatus = (rowStatus === 'Pending' || rowStatus === 'Overdue');
                    } else {
                        matchesStatus = (rowStatus === statusValue);
                    }
                }

                const isToday = row.dataset.isToday === 'true';
                let matchesDate = true;
                if (dateValue === 'Today') {
                    matchesDate = isToday;
                } else if (dateValue === 'Backlog') {
                    matchesDate = !isToday;
                }

                if (matchesSearch && matchesBranch && matchesPriority && matchesStatus && matchesDate) {
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
                // Keep empty state
            } else if (filteredRows.length === 0 && allRows.length > 0) {
                if (!noRecordsRow) {
                    noRecordsRow = document.createElement('tr');
                    noRecordsRow.className = 'no-records';
                    noRecordsRow.innerHTML = `<td colspan="8" class="text-center py-8 text-gray-500">No matching pending cases found.</td>`;
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
        window.updateTable = updateTable;

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

            function createEllipsis() {
                const span = document.createElement('span');
                span.className = "px-2 py-1 text-xs text-gray-400 font-semibold select-none";
                span.innerText = '...';
                return span;
            }

            container.appendChild(createButton('&laquo; First', 1, currentPage <= 1));
            container.appendChild(createButton('&lsaquo; Back', currentPage - 1, currentPage <= 1));

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

            container.appendChild(createButton('Next &rsaquo;', currentPage + 1, currentPage >= totalPages));
            container.appendChild(createButton('Last &raquo;', totalPages, currentPage >= totalPages));
        }

        // --- Pending Release Table Logic ---
        window.updateReleaseTable = function () {
            if (!releaseTbody) return;

            const searchTerm = searchInput ? searchInput.value.toLowerCase().trim() : '';
            const branchValue = filterBranch ? filterBranch.value : '';
            const priorityValue = filterPriority ? filterPriority.value : '';
            const dateValue = filterDate ? filterDate.value : 'All';
            const sortValue = sortOption ? sortOption.value : 'date_desc';

            // Update Title dynamically if currently on release tab
            const activeTab = sessionStorage.getItem('Citilife_radWorklist_tab') || 'worklist';
            if (activeTab === 'release') {
                const worklistTitle = document.getElementById('worklist-title');
                const worklistSubtitle = document.getElementById('worklist-subtitle');
                if (worklistTitle && worklistSubtitle) {
                    if (branchValue) {
                        worklistTitle.innerText = "Pending Release - " + branchValue;
                        worklistSubtitle.innerText = "Cases with completed readings awaiting release for " + branchValue + " branch";
                    } else {
                        worklistTitle.innerText = "Pending Release";
                        worklistSubtitle.innerText = "Cases with completed readings awaiting release across all branches";
                    }
                }
            }

            // Accurate sorting
            allReleaseRows.sort((a, b) => {
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
            allReleaseRows.forEach(row => releaseTbody.appendChild(row));

            // Apply filtering
            let filteredReleaseRows = [];
            allReleaseRows.forEach(row => {
                const rowSearch = (row.dataset.search || '').toLowerCase();
                const matchesSearch = !searchTerm || rowSearch.includes(searchTerm);
                const matchesBranch = branchValue === '' || (row.dataset.branch || '') === branchValue;
                let rowPriority = row.dataset.priority || '';
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
                    filteredReleaseRows.push(row);
                } else {
                    row.style.display = 'none';
                }
            });

            // Pagination calculation
            const totalPages = Math.max(1, Math.ceil(filteredReleaseRows.length / RELEASE_ROWS_PER_PAGE));
            if (currentReleasePage > totalPages) currentReleasePage = totalPages;
            if (currentReleasePage < 1) currentReleasePage = 1;

            const startIdx = (currentReleasePage - 1) * RELEASE_ROWS_PER_PAGE;
            const endIdx = startIdx + RELEASE_ROWS_PER_PAGE;

            const visibleSet = new Set(filteredReleaseRows.slice(startIdx, endIdx));

            filteredReleaseRows.forEach(row => {
                row.style.display = visibleSet.has(row) ? '' : 'none';
            });

            // Handle "No records found" state
            let noRelRecordsRow = releaseTbody.querySelector('.no-rel-records');
            let emptyRelStateRow = releaseTbody.querySelector('.empty-release-row');

            if (emptyRelStateRow && emptyRelStateRow.style.display !== 'none' && allReleaseRows.length === 0) {
                // Keep empty state
            } else if (filteredReleaseRows.length === 0 && allReleaseRows.length > 0) {
                if (!noRelRecordsRow) {
                    noRelRecordsRow = document.createElement('tr');
                    noRelRecordsRow.className = 'no-rel-records';
                    noRelRecordsRow.innerHTML = `<td colspan="8" class="text-center py-8 text-gray-500">No matching cases pending release found.</td>`;
                    releaseTbody.appendChild(noRelRecordsRow);
                } else {
                    noRelRecordsRow.style.display = '';
                    releaseTbody.appendChild(noRelRecordsRow);
                }
            } else if (noRelRecordsRow) {
                noRelRecordsRow.style.display = 'none';
            }

            // Update Pagination UI
            updateReleasePaginationUI(filteredReleaseRows.length, totalPages);
        };

        function updateReleasePaginationUI(totalFiltered, totalPages) {
            const recordCountInfo = document.getElementById('release-record-count');
            const container = document.getElementById('release-pagination-controls');

            const startIdx = totalFiltered === 0 ? 0 : (currentReleasePage - 1) * RELEASE_ROWS_PER_PAGE + 1;
            const endIdx = Math.min(currentReleasePage * RELEASE_ROWS_PER_PAGE, totalFiltered);

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
                        currentReleasePage = page;
                        saveWorklistState();
                        updateReleaseTable();
                        const card = document.getElementById('release-table-card');
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

            container.appendChild(createButton('&laquo; First', 1, currentReleasePage <= 1));
            container.appendChild(createButton('&lsaquo; Back', currentReleasePage - 1, currentReleasePage <= 1));

            if (totalPages <= 7) {
                for (let i = 1; i <= totalPages; i++) {
                    container.appendChild(createButton(i, i, false, i == currentReleasePage));
                }
            } else {
                if (currentReleasePage <= 4) {
                    for (let i = 1; i <= 5; i++) {
                        container.appendChild(createButton(i, i, false, i == currentReleasePage));
                    }
                    container.appendChild(createEllipsis());
                    container.appendChild(createButton(totalPages, totalPages, false, totalPages == currentReleasePage));
                } else if (currentReleasePage >= totalPages - 3) {
                    container.appendChild(createButton(1, 1, false, 1 == currentReleasePage));
                    container.appendChild(createEllipsis());
                    for (let i = totalPages - 4; i <= totalPages; i++) {
                        container.appendChild(createButton(i, i, false, i == currentReleasePage));
                    }
                } else {
                    container.appendChild(createButton(1, 1, false, 1 == currentReleasePage));
                    container.appendChild(createEllipsis());

                    container.appendChild(createButton(currentReleasePage - 1, currentReleasePage - 1, false, false));
                    container.appendChild(createButton(currentReleasePage, currentReleasePage, false, true));
                    container.appendChild(createButton(currentReleasePage + 1, currentReleasePage + 1, false, false));

                    container.appendChild(createEllipsis());
                    container.appendChild(createButton(totalPages, totalPages, false, false));
                }
            }

            container.appendChild(createButton('Next &rsaquo;', currentReleasePage + 1, currentReleasePage >= totalPages));
            container.appendChild(createButton('Last &raquo;', totalPages, currentReleasePage >= totalPages));
        }

        // Reset to page 1 on filter/sort change and update both tables
        function onFilterSortChange() {
            currentPage = 1;
            currentReleasePage = 1;
            saveWorklistState();
            updateTable();
            updateReleaseTable();
        }

        if (searchInput) searchInput.addEventListener('input', onFilterSortChange);
        if (filterBranch) filterBranch.addEventListener('change', onFilterSortChange);
        if (filterPriority) filterPriority.addEventListener('change', onFilterSortChange);
        if (filterStatus) filterStatus.addEventListener('change', onFilterSortChange);
        if (filterDate) filterDate.addEventListener('change', onFilterSortChange);
        if (sortOption) sortOption.addEventListener('change', onFilterSortChange);

        function handleHighlight() {
            const urlParams = new URLSearchParams(window.location.search);
            const highlightCase = urlParams.get('highlight_case') || urlParams.get('highlight') || urlParams.get('case_id');
            if (!highlightCase) return;

            // Search in Pending Worklist
            const mainRows = document.querySelectorAll('.record-row');
            let targetRow = Array.from(mainRows).find(r =>
                (r.dataset.id || '').toLowerCase() === highlightCase.toLowerCase() ||
                (r.dataset.caseId || '').toLowerCase() === highlightCase.toLowerCase()
            );

            let isReleaseTab = false;
            if (!targetRow) {
                // Search in Pending Release
                const relRows = document.querySelectorAll('.release-record-row');
                targetRow = Array.from(relRows).find(r =>
                    (r.dataset.id || '').toLowerCase() === highlightCase.toLowerCase() ||
                    (r.dataset.caseId || '').toLowerCase() === highlightCase.toLowerCase()
                );
                if (targetRow) {
                    isReleaseTab = true;
                }
            }

            if (targetRow) {
                // If branch filter is not set yet, sync it to the highlighted case's branch
                if (filterBranch && !filterBranch.value && targetRow.dataset.branch) {
                    filterBranch.value = targetRow.dataset.branch;
                    sessionStorage.setItem('Citilife_radWorklist_branch', targetRow.dataset.branch);
                }

                if (isReleaseTab) {
                    if (typeof switchRadTab === 'function') switchRadTab('release');
                    const branchVal = filterBranch ? filterBranch.value : '';
                    const validRelRows = Array.from(document.querySelectorAll('.release-record-row')).filter(r => !branchVal || r.dataset.branch === branchVal);
                    const index = validRelRows.indexOf(targetRow);
                    currentReleasePage = index >= 0 ? Math.floor(index / RELEASE_ROWS_PER_PAGE) + 1 : 1;
                    updateReleaseTable();
                } else {
                    if (typeof switchRadTab === 'function') switchRadTab('worklist');
                    const branchVal = filterBranch ? filterBranch.value : '';
                    const validMainRows = Array.from(document.querySelectorAll('.record-row')).filter(r => !branchVal || r.dataset.branch === branchVal);
                    const index = validMainRows.indexOf(targetRow);
                    currentPage = index >= 0 ? Math.floor(index / ROWS_PER_PAGE) + 1 : 1;
                    updateTable();
                }

                setTimeout(() => {
                    targetRow.style.display = '';
                    const tableWrapper = targetRow.closest('.overflow-y-auto');
                    if (tableWrapper) {
                        tableWrapper.scrollTo({ top: targetRow.offsetTop - tableWrapper.offsetTop - 40, behavior: 'smooth' });
                    } else {
                        targetRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }

                    targetRow.style.transition = 'background-color 0.3s ease';
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

        // Restore saved filters, page, and active tab from session
        restoreWorklistState();

        const paramsList = new window.URLSearchParams(window.location.search);
        if (paramsList.get('tab') === 'release' || paramsList.get('status') === 'Report Ready' || paramsList.get('status') === 'completed_today') {
            window.switchRadTab('release');
        } else if (paramsList.get('tab') === 'worklist' || paramsList.get('status') === 'pending') {
            window.switchRadTab('worklist');
        }

        // Render both tables
        updateTable();
        updateReleaseTable();

        handleHighlight();

        // Ensure lucide icons are rendered
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }

        // Real-time polling for Radiologist Worklist & Pending Release (every 3 seconds)
        let isSyncingWorklist = false;
        setInterval(() => {
            if (isSyncingWorklist) return;

            // Don't sync if user is actively searching or typing
            const isTyping = document.activeElement && (
                document.activeElement === document.getElementById('searchInput')
            );
            if (isTyping) return;

            isSyncingWorklist = true;
            fetch(window.location.href, { cache: 'no-store' })
                .then(res => res.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');

                    // 1. Sync Pending Worklist Table
                    const newWorklistTbody = doc.getElementById('worklist-tbody');
                    const curWorklistTbody = document.getElementById('worklist-tbody');
                    if (newWorklistTbody && curWorklistTbody) {
                        const newWlCount = newWorklistTbody.querySelectorAll('tr.record-row').length;
                        const curWlCount = curWorklistTbody.querySelectorAll('tr.record-row').length;
                        const newCaseIds = Array.from(newWorklistTbody.querySelectorAll('tr.record-row')).map(r => (r.dataset.id || '') + ':' + (r.dataset.status || '')).join('|');
                        const curCaseIds = allRows.map(r => (r.dataset.id || '') + ':' + (r.dataset.status || '')).join('|');
                        if (newWlCount !== curWlCount || newCaseIds !== curCaseIds) {
                            curWorklistTbody.innerHTML = newWorklistTbody.innerHTML;
                            allRows = Array.from(curWorklistTbody.querySelectorAll('tr.record-row'));
                            updateTable();
                        }
                    }

                    // 2. Sync Pending Release Table
                    const newReleaseTbody = doc.getElementById('release-tbody');
                    const curReleaseTbody = document.getElementById('release-tbody');
                    if (newReleaseTbody && curReleaseTbody) {
                        const newRelCount = newReleaseTbody.querySelectorAll('tr.release-record-row').length;
                        const curRelCount = curReleaseTbody.querySelectorAll('tr.release-record-row').length;
                        const newRelCaseIds = Array.from(newReleaseTbody.querySelectorAll('tr.release-record-row')).map(r => (r.dataset.id || '') + ':' + (r.dataset.status || '')).join('|');
                        const curRelCaseIds = allReleaseRows.map(r => (r.dataset.id || '') + ':' + (r.dataset.status || '')).join('|');
                        if (newRelCount !== curRelCount || newRelCaseIds !== curRelCaseIds) {
                            curReleaseTbody.innerHTML = newReleaseTbody.innerHTML;
                            allReleaseRows = Array.from(curReleaseTbody.querySelectorAll('tr.release-record-row'));
                            updateReleaseTable();
                        }
                    }

                    // 3. Sync Worklist Tab Badge
                    const newWlBadge = doc.getElementById('worklist-tab-badge');
                    const curWlBadge = document.getElementById('worklist-tab-badge');
                    if (newWlBadge && curWlBadge) {
                        curWlBadge.innerHTML = newWlBadge.innerHTML;
                        curWlBadge.className = newWlBadge.className;
                        if (newWlBadge.title) curWlBadge.title = newWlBadge.title;
                    }

                    // 4. Sync Release Tab Badge
                    const newRelBadge = doc.getElementById('release-tab-badge');
                    const curRelBadge = document.getElementById('release-tab-badge');
                    if (newRelBadge && curRelBadge) {
                        curRelBadge.innerHTML = newRelBadge.innerHTML;
                        curRelBadge.className = newRelBadge.className;
                        if (newRelBadge.title) curRelBadge.title = newRelBadge.title;
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