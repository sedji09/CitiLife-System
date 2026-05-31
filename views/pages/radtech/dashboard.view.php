<?php
// Centralized DB connection (included in layouts, but safe to include once globally)
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../models/CaseModel.php';
require_once __DIR__ . '/../../../models/BranchModel.php';

// Initialize the Backend Model
$caseModel = new \CaseModel($pdo);
$branchModel = new \BranchModel($pdo);

// 1. Inputs (Backend data gathering)
$branchId = $_SESSION['branch_id'] ?? 1;
$filter = $_GET['filter'] ?? 'today';
$selectedMonth = $_GET['month'] ?? date('Y-m');
$selectedYear = $_GET['year'] ?? date('Y');

// 2. Fetch Data (Backend logic)
$dateInfo = $caseModel->buildDateCondition($filter, $selectedMonth, $selectedYear);
$dateCondition = $dateInfo['condition'];
$periodLabel = $dateInfo['label'];

// Fetch Stats
$stats = $caseModel->getDashboardStats($branchId, $dateCondition);
$totalPatients = $stats['total'];
$pendingApprovals = $stats['pending'];
$priorityCases = $stats['priority'];
$emergencyCases = $stats['emergency'];
$completedCases = $stats['completed'];

// Fetch Recent Cases
$recentCases = $caseModel->getRecentCases($branchId, $dateCondition, 5);

// 3. View Logic (Now pure Frontend follows)
?>
<style>
  html.theme-dark .priority-badge,
  html.theme-dark .status-badge {
    background-color: transparent !important;
  }
</style>

<div class="space-y-6">

  <!-- Header -->
  <div class="flex items-center justify-between">
    <div>
      <h2 class="text-2xl font-bold text-gray-900 tracking-tight">RadTech Dashboard</h2>
      <p class="text-sm text-gray-500 mt-1">Overview of activity for <?= htmlspecialchars($periodLabel) ?> and quick
        actions.</p>
    </div>
    <div class="flex items-center gap-2">
      <select id="filterSelect" onchange="handleFilterChange()"
        class="bg-white border border-gray-300 text-gray-700 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 shadow-sm">
        <option value="today" <?= $filter === 'today' ? 'selected' : '' ?>>Today</option>
        <option value="weekly" <?= $filter === 'weekly' ? 'selected' : '' ?>>This Week</option>
        <option value="monthly" <?= $filter === 'monthly' ? 'selected' : '' ?>>Monthly</option>
        <option value="yearly" <?= $filter === 'yearly' ? 'selected' : '' ?>>Yearly</option>
      </select>

      <input type="month" id="monthPicker" value="<?= htmlspecialchars($selectedMonth) ?>"
        onchange="handleFilterChange()"
        class="<?= $filter === 'monthly' ? '' : 'hidden' ?> bg-white border border-gray-300 text-gray-700 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5 shadow-sm">

      <input type="number" id="yearPicker" min="2000" max="2100" value="<?= htmlspecialchars($selectedYear) ?>"
        onchange="handleFilterChange()"
        class="<?= $filter === 'yearly' ? '' : 'hidden' ?> bg-white border border-gray-300 text-gray-700 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5 shadow-sm w-24">

      <script>
        function handleFilterChange() {
          const filter = document.getElementById('filterSelect').value;
          let url = '?role=radtech&page=dashboard&filter=' + filter;
          if (filter === 'monthly') {
            url += '&month=' + document.getElementById('monthPicker').value;
          } else if (filter === 'yearly') {
            url += '&year=' + document.getElementById('yearPicker').value;
          }
          window.location.href = url;
        }
      </script>
    </div>
  </div>

  <!-- Stats -->
  <div id="radtech-dashboard-stats" class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-5 realtime-update">
    <div class="rounded-xl bg-white border border-gray-200 shadow-sm p-5 hover:shadow-md transition">
      <div class="flex items-center justify-between">
        <p class="text-sm text-gray-500">Total Patients Today</p>
        <i data-lucide="users" class="w-5 h-5 text-blue-400"></i>
      </div>
      <p class="text-3xl font-bold mt-2"><?= htmlspecialchars($totalPatients) ?></p>
    </div>

    <div class="rounded-xl bg-white border border-gray-200 shadow-sm p-5 hover:shadow-md transition">
      <div class="flex items-center justify-between">
        <p class="text-sm text-gray-500">Pending</p>
        <i data-lucide="clock-3" class="w-5 h-5 text-orange-400"></i>
      </div>
      <p class="text-3xl font-bold mt-2"><?= htmlspecialchars($pendingApprovals) ?></p>
    </div>

    <div class="rounded-xl bg-white border border-gray-200 shadow-sm p-5 hover:shadow-md transition">
      <div class="flex items-center justify-between">
        <p class="text-sm text-gray-500">Priority Cases</p>
        <i data-lucide="chart-spline" class="w-5 h-5 text-yellow-400"></i>
      </div>
      <p class="text-3xl font-bold mt-2"><?= htmlspecialchars($priorityCases) ?></p>
    </div>

    <div class="rounded-xl bg-white border border-gray-200 shadow-sm p-5 hover:shadow-md transition">
      <div class="flex items-center justify-between">
        <p class="text-sm text-gray-500">Emergencies</p>
        <i data-lucide="triangle-alert" class="w-5 h-5 text-red-400"></i>
      </div>
      <p class="text-3xl font-bold mt-2"><?= htmlspecialchars($emergencyCases) ?></p>
    </div>

    <div class="rounded-xl bg-white border border-gray-200 shadow-sm p-5 hover:shadow-md transition">
      <div class="flex items-center justify-between">
        <p class="text-sm text-gray-500">Completed</p>
        <i data-lucide="circle-check-big" class="w-5 h-5 text-green-400"></i>
      </div>
      <p class="text-3xl font-bold mt-2"><?= htmlspecialchars($completedCases) ?></p>
    </div>
  </div>

  <!-- Recent Queue -->
  <div
    class="rounded-xl bg-white border border-gray-200 shadow-sm xl:col-span-2 overflow-hidden hover:shadow-md transition">
    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
      <h3 class="font-bold text-gray-900 text-lg">Recent Cases added</h3>
      <a href="/<?= PROJECT_DIR ?>/index.php?role=radtech&page=patient-lists"
        class="text-sm font-semibold text-red-600 hover:text-red-700 hover:underline">View all</a>
    </div>

    <div class="overflow-x-auto">
      <table id="radtech-recent-cases" class="w-full text-sm realtime-update">
        <thead class="text-gray-500 bg-gray-50 border-b border-gray-200">
          <tr>
            <th class="text-left font-semibold px-6 py-3 whitespace-nowrap">Case No.</th>
            <th class="text-left font-semibold px-6 py-3 whitespace-nowrap">Patient No.</th>
            <th class="text-left font-semibold px-6 py-3">Patient Name</th>
            <th class="text-left font-semibold px-6 py-3">Exam Type</th>
            <th class="text-left font-semibold px-6 py-3">Priority</th>
            <th class="text-left font-semibold px-6 py-3">Status</th>
          </tr>
        </thead>
        <tbody class="text-gray-800 divide-y divide-gray-100">
          <?php if (count($recentCases) === 0): ?>
            <tr>
              <td colspan="6" class="px-6 py-8 text-center text-gray-500">No recent cases recorded yet.</td>
            </tr>
          <?php else: ?>
            <?php foreach ($recentCases as $case): ?>
              <tr class="hover:bg-gray-50 transition-colors">
                <td class="py-3 px-6 font-medium whitespace-nowrap"><?= htmlspecialchars($case['case_number']) ?></td>
                <td class="py-3 px-6 font-medium whitespace-nowrap">
                  <?= htmlspecialchars($case['patient_number'] ?? 'N/A') ?>
                </td>
                <td class="py-3 px-6 font-medium"><?= htmlspecialchars($case['first_name'] . ' ' . $case['last_name']) ?>
                </td>
                <td class="py-3 px-6">
                  <?php
                  $exams = array_filter(array_map('trim', explode(',', $case['exam_type'])));
                  $firstExam = reset($exams);
                  $extraCount = count($exams) - 1;
                  ?>
                  <div class="flex items-center gap-1.5">
                    <span class="text-gray-700 font-medium truncate max-w-[100px]"
                      title="<?= htmlspecialchars($case['exam_type']) ?>"><?= htmlspecialchars($firstExam) ?></span>
                    <?php if ($extraCount > 0): ?>
                      <span
                        class="inline-flex items-center rounded-full bg-gray-100 border border-gray-300 px-1.5 py-0.5 text-xs font-semibold text-gray-600 cursor-default"
                        title="<?= htmlspecialchars($case['exam_type']) ?>">+<?= $extraCount ?></span>
                    <?php endif; ?>
                  </div>
                </td>
                <td class="py-3 px-6">
                  <?php
                  $pStyles = [
                    'border' => '1.5px solid #60a5fa',
                    'background' => '#eff6ff',
                    'color' => '#1d4ed8',
                  ];
                  if ($case['priority'] === 'Emergency')
                    $pStyles = ['border' => '1.5px solid #f87171', 'background' => '#fef2f2', 'color' => '#b91c1c'];
                  if ($case['priority'] === 'Urgent')
                    $pStyles = ['border' => '1.5px solid #facc15', 'background' => '#fefce8', 'color' => '#a16207'];
                  if ($case['priority'] === 'Priority')
                    $pStyles = ['border' => '1.5px solid #fb923c', 'background' => '#fff7ed', 'color' => '#c2410c'];
                  $pStyleStr = "border:{$pStyles['border']};background-color:{$pStyles['background']};color:{$pStyles['color']}";
                  ?>
                  <span style="<?= $pStyleStr ?>"
                    class="priority-badge inline-flex items-center rounded-full px-2 py-1 text-xs font-semibold">
                    <?= htmlspecialchars($case['priority']) ?>
                  </span>
                </td>
                <td class="py-3 px-6">
                  <?php
                  $displayStatus = ($case['approval_status'] === 'Rejected' || $case['status'] === 'Rejected') ? 'Rejected' : $case['status'];
                  $sStyles = ['border' => '1.5px solid #facc15', 'background' => '#fefce8', 'color' => '#a16207'];
                  if ($displayStatus === 'Report Ready')
                    $sStyles = ['border' => '1.5px solid #818cf8', 'background' => '#eef2ff', 'color' => '#4338ca'];
                  if ($displayStatus === 'Under Reading')
                    $sStyles = ['border' => '1.5px solid #60a5fa', 'background' => '#eff6ff', 'color' => '#1d4ed8'];
                  if ($displayStatus === 'Completed')
                    $sStyles = ['border' => '1.5px solid #4ade80', 'background' => '#f0fdf4', 'color' => '#15803d'];
                  if ($displayStatus === 'Rejected')
                    $sStyles = ['border' => '1.5px solid #f87171', 'background' => '#fef2f2', 'color' => '#b91c1c'];
                  $sStyleStr = "border:{$sStyles['border']};background-color:{$sStyles['background']};color:{$sStyles['color']}";
                  ?>
                  <span style="<?= $sStyleStr ?>"
                    class="status-badge inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold">
                    <?= htmlspecialchars($displayStatus ?: 'Pending') ?>
                  </span>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>