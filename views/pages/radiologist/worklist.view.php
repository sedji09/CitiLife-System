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
        <div class="relative flex-1 min-w-[220px] group">
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
        $initialIsRelease = (($_GET['tab'] ?? '') === 'release' || in_array($lowerStatusUrl, ['completed', 'completed_today', 'report ready', 'report_ready']));
        if ($initialIsRelease) {
            if (in_array($lowerStatusUrl, ['completed', 'completed_today'])) {
                $normalizedStatus = 'Completed';
            } elseif (in_array($lowerStatusUrl, ['report ready', 'report_ready'])) {
                $normalizedStatus = 'Report Ready';
            }
        } else {
            if ($lowerStatusUrl === 'overdue') {
                $normalizedStatus = 'Overdue';
            } elseif (in_array($lowerStatusUrl, ['under reading', 'under_reading', 'in progress', 'inprogress'])) {
                $normalizedStatus = 'In Progress';
            } elseif (in_array($lowerStatusUrl, ['for revision', 'for_revision'])) {
                $normalizedStatus = 'For Revision';
            } elseif ($lowerStatusUrl === 'pending') {
                $normalizedStatus = 'Pending';
            }
        }
        ?>
        <select id="filterStatus"
            class="w-28 lg:w-32 shrink-0 px-2.5 py-2.5 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-red-500/10 focus:border-red-500 text-xs sm:text-sm bg-white shadow-sm font-normal text-gray-600 cursor-pointer">
            <option value="" <?= $normalizedStatus === '' ? 'selected' : '' ?>>All Statuses</option>
            <?php if ($initialIsRelease): ?>
                <option value="Report Ready" <?= $normalizedStatus === 'Report Ready' ? 'selected' : '' ?>>Report Ready</option>
                <option value="Completed" <?= $normalizedStatus === 'Completed' ? 'selected' : '' ?>>Completed</option>
            <?php else: ?>
                <option value="For Revision" <?= $normalizedStatus === 'For Revision' ? 'selected' : '' ?>>For Revision</option>
                <option value="Pending" <?= $normalizedStatus === 'Pending' ? 'selected' : '' ?>>Pending</option>
                <option value="In Progress" <?= $normalizedStatus === 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                <option value="Overdue" <?= $normalizedStatus === 'Overdue' ? 'selected' : '' ?>>Overdue</option>
            <?php endif; ?>
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
        } elseif ($initialIsRelease) {
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
                                    <a href="<?= url('case-review?id=' . $row['id'] . '&branch_id=' . $row['branch_id']) ?>"
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
                            $rawRelStatus = $row['status'] ?? 'Report Ready';
                            ?>
                            <?php $pFullName = formatFullName($row); ?>
                            <tr class="hover:bg-white/10 transition-colors release-record-row cursor-pointer"
                                data-id="<?= htmlspecialchars($row['case_number']) ?>"
                                data-case-id="<?= htmlspecialchars($row['id'] ?? '') ?>"
                                data-branch="<?= htmlspecialchars($row['branch_name']) ?>"
                                data-priority="<?= htmlspecialchars($row['priority']) ?>" data-stat="<?= $isEmergency ?>"
                                data-pweight="<?= $pWeight ?>" data-is-today="<?= $isToday ? 'true' : 'false' ?>"
                                data-status="<?= htmlspecialchars($rawRelStatus) ?>"
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
                                    <a href="<?= url('case-review?id=' . $row['id'] . '&branch_id=' . $row['branch_id']) ?>"
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
    // Tab States: completely isolated between Pending Worklist and Pending Release
    let currentActiveTab = 'worklist';
    const tabStates = {
        worklist: {
            search: '',
            branch: '',
            priority: '',
            status: '',
            date: 'Today',
            sort: 'date_desc',
            page: 1
        },
        release: {
            search: '',
            branch: '',
            priority: '',
            status: '',
            date: 'All',
            sort: 'date_desc',
            page: 1
        }
    };

    // Forward declaration of switchRadTab so inline onclick="switchRadTab(...)" is never undefined
    window.switchRadTab = function (tab) {
        currentActiveTab = tab;
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

        // Dynamically update status dropdown options based on the active tab
        function updateStatusDropdown(tab, selectedVal = '') {
            if (!filterStatus) return;

            if (tab === 'release') {
                filterStatus.innerHTML = `
                    <option value="">All Statuses</option>
                    <option value="Report Ready">Report Ready</option>
                    <option value="Completed">Completed</option>
                `;
            } else {
                filterStatus.innerHTML = `
                    <option value="">All Statuses</option>
                    <option value="For Revision">For Revision</option>
                    <option value="Pending">Pending</option>
                    <option value="In Progress">In Progress</option>
                    <option value="Overdue">Overdue</option>
                `;
            }

            filterStatus.value = selectedVal || '';

            if (filterStatus._customSelect) {
                if (typeof filterStatus._customSelect.buildOptions === 'function') {
                    filterStatus._customSelect.buildOptions();
                }
                if (typeof filterStatus._customSelect.sync === 'function') {
                    filterStatus._customSelect.sync();
                }
            }
        }

        // Save current tab's inputs to its dedicated tabState & sessionStorage
        function saveCurrentTabInputs() {
            if (!tabStates[currentActiveTab]) return;

            tabStates[currentActiveTab].search = searchInput ? searchInput.value : '';
            tabStates[currentActiveTab].branch = filterBranch ? filterBranch.value : '';
            tabStates[currentActiveTab].priority = filterPriority ? filterPriority.value : '';
            tabStates[currentActiveTab].status = filterStatus ? filterStatus.value : '';
            tabStates[currentActiveTab].date = filterDate ? filterDate.value : (currentActiveTab === 'release' ? 'All' : 'Today');
            tabStates[currentActiveTab].sort = sortOption ? sortOption.value : 'date_desc';
            tabStates[currentActiveTab].page = (currentActiveTab === 'worklist') ? currentPage : currentReleasePage;

            try {
                sessionStorage.setItem('Citilife_radWorklist_state_' + currentActiveTab, JSON.stringify(tabStates[currentActiveTab]));
            } catch (e) {}
        }

        // Backward compatibility
        function saveWorklistState() {
            saveCurrentTabInputs();
        }

        // Apply a specific tab's filters to the DOM inputs
        function applyTabInputs(tab) {
            const st = tabStates[tab] || {
                search: '',
                branch: '',
                priority: '',
                status: '',
                date: tab === 'release' ? 'All' : 'Today',
                sort: 'date_desc',
                page: 1
            };

            if (searchInput) searchInput.value = st.search || '';
            if (filterBranch) filterBranch.value = st.branch || '';
            if (filterPriority) filterPriority.value = st.priority || '';
            if (filterDate) filterDate.value = st.date || (tab === 'release' ? 'All' : 'Today');
            if (sortOption) sortOption.value = st.sort || 'date_desc';

            if (tab === 'worklist') {
                currentPage = st.page || 1;
            } else {
                currentReleasePage = st.page || 1;
            }

            updateStatusDropdown(tab, st.status || '');

            // Sync all custom select triggers
            [filterBranch, filterPriority, filterDate, sortOption].forEach(sel => {
                if (sel && sel._customSelect && typeof sel._customSelect.sync === 'function') {
                    sel._customSelect.sync();
                }
            });
        }

        let isInitialized = false;

        // Full Tab Switching function
        window.switchRadTab = function (tab, skipSave = false) {
            if (tab !== 'worklist' && tab !== 'release') tab = 'worklist';

            // 1. If switching away from an existing active tab, save its current filter state ONLY IF already initialized
            if (!skipSave && isInitialized && currentActiveTab && tabStates[currentActiveTab] && searchInput) {
                saveCurrentTabInputs();
            }

            // 2. Set new active tab
            currentActiveTab = tab;
            sessionStorage.setItem('Citilife_radWorklist_tab', tab);
            try {
                window.history.replaceState(null, document.title, window.location.pathname);
                sessionStorage.setItem('Citilife_last_worklist_url', window.location.pathname);
            } catch (e) {}

            // 3. Tab button styles and card visibility
            const workCard = document.getElementById('worklist-table-card');
            const relCard = document.getElementById('release-table-card');
            const workBtn = document.getElementById('tab-rad-worklist-btn');
            const relBtn = document.getElementById('tab-rad-release-btn');
            const worklistTitle = document.getElementById('worklist-title');
            const worklistSubtitle = document.getElementById('worklist-subtitle');

            if (tab === 'release') {
                if (workCard) workCard.classList.add('hidden');
                if (relCard) relCard.classList.remove('hidden');

                if (relBtn) relBtn.className = "pb-3 px-2 text-sm font-bold border-b-2 border-red-600 text-red-600 transition flex items-center gap-2";
                if (workBtn) workBtn.className = "pb-3 px-2 text-sm font-semibold border-b-2 border-transparent text-gray-500 hover:text-gray-700 transition flex items-center gap-2";
            } else {
                if (workCard) workCard.classList.remove('hidden');
                if (relCard) relCard.classList.add('hidden');

                if (workBtn) workBtn.className = "pb-3 px-2 text-sm font-bold border-b-2 border-red-600 text-red-600 transition flex items-center gap-2";
                if (relBtn) relBtn.className = "pb-3 px-2 text-sm font-semibold border-b-2 border-transparent text-gray-500 hover:text-gray-700 transition flex items-center gap-2";
            }

            // 4. Load & apply the target tab's own isolated filters to the controls
            applyTabInputs(tab);

            // 5. Update header title & subtitle with the branch for this tab
            const curBranch = filterBranch ? filterBranch.value : '';
            if (worklistTitle && worklistSubtitle) {
                if (tab === 'release') {
                    if (curBranch) {
                        worklistTitle.innerText = "Pending Release - " + curBranch;
                        worklistSubtitle.innerText = "Cases with completed readings awaiting release for " + curBranch + " branch";
                    } else {
                        worklistTitle.innerText = "Pending Release";
                        worklistSubtitle.innerText = "Cases with completed readings awaiting release across all branches";
                    }
                } else {
                    if (curBranch) {
                        worklistTitle.innerText = "Worklist - " + curBranch;
                        worklistSubtitle.innerText = "Manage pending cases for " + curBranch + " branch";
                    } else {
                        worklistTitle.innerText = "Worklist";
                        worklistSubtitle.innerText = "Manage pending cases across all branches";
                    }
                }
            }

            // 6. Update only the active tab's table
            if (tab === 'release') {
                if (typeof updateReleaseTable === 'function') {
                    updateReleaseTable();
                }
            } else {
                if (typeof updateTable === 'function') {
                    updateTable();
                }
            }

            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        };

        function restoreWorklistState() {
            // 1. Load saved states from sessionStorage if available
            try {
                const savedWl = sessionStorage.getItem('Citilife_radWorklist_state_worklist');
                if (savedWl) {
                    const parsed = JSON.parse(savedWl);
                    if (parsed && typeof parsed === 'object') {
                        Object.assign(tabStates.worklist, parsed);
                    }
                }
            } catch (e) {}

            try {
                const savedRel = sessionStorage.getItem('Citilife_radWorklist_state_release');
                if (savedRel) {
                    const parsed = JSON.parse(savedRel);
                    if (parsed && typeof parsed === 'object') {
                        Object.assign(tabStates.release, parsed);
                    }
                }
            } catch (e) {}

            // 2. Inspect URL parameters
            const params = new URLSearchParams(window.location.search);
            const hasHighlight = params.has('highlight_case') || params.has('highlight') || params.has('case_id');

            let initialTab = 'worklist';
            if (params.get('tab') === 'release' || params.get('status') === 'completed_today' || params.get('status') === 'Report Ready') {
                initialTab = 'release';
            } else if (params.get('tab') === 'worklist') {
                initialTab = 'worklist';
            } else {
                const savedTab = sessionStorage.getItem('Citilife_radWorklist_tab');
                if (savedTab === 'release' || savedTab === 'worklist') {
                    initialTab = savedTab;
                }
            }

            if (hasHighlight) {
                tabStates[initialTab].date = 'All';
                tabStates[initialTab].search = '';
                tabStates[initialTab].priority = '';
                tabStates[initialTab].status = '';
                if (params.has('branch')) {
                    tabStates[initialTab].branch = params.get('branch');
                }
            } else {
                if (params.has('branch')) {
                    const rawB = (params.get('branch') || '').trim();
                    tabStates[initialTab].branch = (rawB.toLowerCase() === 'all') ? '' : rawB;
                }
                if (params.has('priority')) {
                    const rawP = (params.get('priority') || '').trim().toUpperCase();
                    if (rawP === 'STAT') tabStates[initialTab].priority = 'STAT';
                    else if (rawP === 'URGENT') tabStates[initialTab].priority = 'Urgent';
                    else if (rawP === 'ROUTINE') tabStates[initialTab].priority = 'Routine';
                    else tabStates[initialTab].priority = '';
                }
                if (params.has('status') || params.has('filterStatus')) {
                    const rawSt = (params.get('status') || params.get('filterStatus') || '').trim();
                    const lowerSt = rawSt.toLowerCase();
                    let mappedSt = '';
                    if (initialTab === 'release') {
                        if (lowerSt === 'completed' || lowerSt === 'completed_today') mappedSt = 'Completed';
                        else if (lowerSt === 'report ready' || lowerSt === 'report_ready') mappedSt = 'Report Ready';
                    } else {
                        if (lowerSt === 'overdue') mappedSt = 'Overdue';
                        else if (['under reading', 'under_reading', 'in progress', 'inprogress'].includes(lowerSt)) mappedSt = 'In Progress';
                        else if (['for revision', 'for_revision'].includes(lowerSt)) mappedSt = 'For Revision';
                        else if (lowerSt === 'pending') mappedSt = 'Pending';
                    }
                    tabStates[initialTab].status = mappedSt;
                }
                if (params.has('date') || params.has('filterDate')) {
                    const rawD = (params.get('date') || params.get('filterDate') || '').trim().toLowerCase();
                    if (rawD === 'backlog') tabStates[initialTab].date = 'Backlog';
                    else if (rawD === 'today') tabStates[initialTab].date = 'Today';
                    else if (rawD === 'all') tabStates[initialTab].date = 'All';
                }
                if (params.has('sort')) {
                    tabStates[initialTab].sort = params.get('sort');
                }
                if (params.has('search')) {
                    tabStates[initialTab].search = params.get('search');
                }
            }

            // 3. Switch to initial tab and populate inputs (skipSave = true so we don't wipe out loaded state!)
            window.switchRadTab(initialTab, true);
            isInitialized = true;
            saveCurrentTabInputs();

            // Re-sync all custom selects after custom-select.js has initialized
            setTimeout(() => {
                [filterBranch, filterPriority, filterStatus, filterDate, sortOption].forEach(sel => {
                    if (sel && sel._customSelect) {
                        if (typeof sel._customSelect.buildOptions === 'function') {
                            sel._customSelect.buildOptions();
                        }
                        if (typeof sel._customSelect.sync === 'function') {
                            sel._customSelect.sync();
                        }
                    }
                });
            }, 80);

            try {
                sessionStorage.setItem('Citilife_last_worklist_url', window.location.pathname);
            } catch (e) {}
        }

        window.addEventListener('beforeunload', () => {
            if (isInitialized) saveCurrentTabInputs();
        });
        window.addEventListener('pagehide', () => {
            if (isInitialized) saveCurrentTabInputs();
        });

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
            const statusValue = filterStatus ? filterStatus.value : '';
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

                const rowStatus = row.dataset.status || '';
                const matchesStatus = statusValue === '' || rowStatus === statusValue;

                if (matchesSearch && matchesBranch && matchesPriority && matchesDate && matchesStatus) {
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

        // Reset to page 1 on filter/sort change and update active table only
        function onFilterSortChange() {
            if (currentActiveTab === 'worklist') {
                currentPage = 1;
                if (tabStates.worklist) tabStates.worklist.page = 1;
                saveCurrentTabInputs();
                updateTable();
            } else {
                currentReleasePage = 1;
                if (tabStates.release) tabStates.release.page = 1;
                saveCurrentTabInputs();
                updateReleaseTable();
            }
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
                const rowBranch = targetRow.dataset.branch || '';

                if (isReleaseTab) {
                    if (rowBranch && tabStates.release) tabStates.release.branch = rowBranch;
                    if (tabStates.release) {
                        tabStates.release.date = 'All';
                        tabStates.release.priority = '';
                        tabStates.release.status = '';
                        tabStates.release.search = '';
                    }
                    if (typeof switchRadTab === 'function') switchRadTab('release');
                    const branchVal = filterBranch ? filterBranch.value : '';
                    const validRelRows = Array.from(document.querySelectorAll('.release-record-row')).filter(r => !branchVal || r.dataset.branch === branchVal);
                    const index = validRelRows.indexOf(targetRow);
                    currentReleasePage = index >= 0 ? Math.floor(index / RELEASE_ROWS_PER_PAGE) + 1 : 1;
                    if (tabStates.release) tabStates.release.page = currentReleasePage;
                    saveCurrentTabInputs();
                    updateReleaseTable();
                } else {
                    if (rowBranch && tabStates.worklist) tabStates.worklist.branch = rowBranch;
                    if (tabStates.worklist) {
                        tabStates.worklist.date = 'All';
                        tabStates.worklist.priority = '';
                        tabStates.worklist.status = '';
                        tabStates.worklist.search = '';
                    }
                    if (typeof switchRadTab === 'function') switchRadTab('worklist');
                    const branchVal = filterBranch ? filterBranch.value : '';
                    const validMainRows = Array.from(document.querySelectorAll('.record-row')).filter(r => !branchVal || r.dataset.branch === branchVal);
                    const index = validMainRows.indexOf(targetRow);
                    currentPage = index >= 0 ? Math.floor(index / ROWS_PER_PAGE) + 1 : 1;
                    if (tabStates.worklist) tabStates.worklist.page = currentPage;
                    saveCurrentTabInputs();
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

                    try {
                        window.history.replaceState(null, document.title, window.location.pathname);
                    } catch (e) {}
                }, 200);
            }
        }

        // Restore saved filters, page, and active tab from session or URL
        restoreWorklistState();

        handleHighlight();

        // Clean URL address bar so ?branch=... &highlight_case=... are never left exposed
        try {
            if (window.history && window.history.replaceState) {
                window.history.replaceState(null, document.title, window.location.pathname);
            }
        } catch (e) {}

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
            fetch(window.location.pathname, { cache: 'no-store' })
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
                            if (currentActiveTab === 'worklist') {
                                updateTable();
                            }
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
                            if (currentActiveTab === 'release') {
                                updateReleaseTable();
                            }
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