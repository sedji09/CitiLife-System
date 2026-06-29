<?php
// Centralized DB connection (included in layouts, but safe to include once globally)
require_once __DIR__ . '/../../../config/database.php';

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
$emergencyCases = $stats['stat'];
$completedCases = $stats['completed'];
$backlogCases = $stats['backlog'];

// Fetch Recent Cases
$recentCases = $caseModel->getRecentCases($branchId, $dateCondition, 5);

// Fetch Radiologists Workload
$radiologistsWorkload = $caseModel->getRadiologistsWorkload($dateCondition, $branchId);

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
      <h2 class="text-2xl font-semibold text-gray-900 tracking-tight">RadTech Dashboard</h2>
      <p class="text-sm text-gray-500 mt-1">Overview of activity for <span id="period-label"
          class="realtime-update"><?= htmlspecialchars($periodLabel) ?></span> and quick
        actions.</p>
    </div>
    <div class="flex items-center gap-2">
      <select id="filterSelect" onchange="handleFilterChange()"
        class="bg-white border border-gray-300 text-gray-700 text-sm rounded-lg focus:ring-red-500 focus:border-red-500 block w-full p-2.5 shadow-sm">
        <option value="today" <?= $filter === 'today' ? 'selected' : '' ?>>Today</option>
        <option value="weekly" <?= $filter === 'weekly' ? 'selected' : '' ?>>This Week</option>
        <option value="monthly" <?= $filter === 'monthly' ? 'selected' : '' ?>>Monthly</option>
        <option value="yearly" <?= $filter === 'yearly' ? 'selected' : '' ?>>Yearly</option>
      </select>

      <!-- Monthly Picker -->
      <div id="monthlyFilter" class="relative <?= $filter === 'monthly' ? '' : 'hidden' ?>">
        <button type="button" id="monthPickerTrigger" onclick="toggleMonthPicker()"
          class="flex items-center gap-2 bg-white border border-gray-300 text-gray-700 text-sm rounded-lg p-2.5 shadow-sm hover:border-red-400 focus:outline-none focus:ring-2 focus:ring-red-500 min-w-[140px] justify-between">
          <span id="monthPickerLabel"
            class="whitespace-nowrap"><?= date('F Y', strtotime($selectedMonth . '-01')) ?></span>
          <i data-lucide="calendar" class="w-4 h-4 text-gray-400"></i>
        </button>
        <div id="monthPickerPanel"
          class="hidden absolute top-full mt-1 right-0 bg-white border border-gray-200 rounded-xl shadow-lg z-50 p-3 w-[260px]">
          <div class="flex items-center justify-between mb-3 px-1">
            <button type="button" onclick="changePickerYear(-1)"
              class="text-gray-500 hover:text-red-600 font-bold text-lg w-7 h-7 flex items-center justify-center rounded hover:bg-gray-100">«</button>
            <span id="pickerYearLabel" class="font-semibold text-gray-800 text-sm"></span>
            <button type="button" onclick="changePickerYear(1)"
              class="text-gray-500 hover:text-red-600 font-bold text-lg w-7 h-7 flex items-center justify-center rounded hover:bg-gray-100">»</button>
          </div>
          <div id="monthGrid" class="grid grid-cols-4 gap-1"></div>
        </div>
        <input type="hidden" id="monthPicker" value="<?= htmlspecialchars($selectedMonth) ?>">
      </div>

      <!-- Yearly Picker -->
      <div id="yearlyFilter" class="relative <?= $filter === 'yearly' ? '' : 'hidden' ?>">
        <button type="button" id="yearPickerTrigger" onclick="toggleYearPicker()"
          class="flex items-center gap-2 bg-white border border-gray-300 text-gray-700 text-sm rounded-lg p-2.5 shadow-sm hover:border-red-400 focus:outline-none focus:ring-2 focus:ring-red-500 min-w-[100px] justify-between">
          <span id="yearPickerLabel" class="whitespace-nowrap"><?= htmlspecialchars($selectedYear) ?></span>
          <i data-lucide="calendar" class="w-4 h-4 text-gray-400"></i>
        </button>
        <div id="yearPickerPanel"
          class="hidden absolute top-full mt-1 right-0 bg-white border border-gray-200 rounded-xl shadow-lg z-50 p-2 w-[110px] max-h-64 overflow-y-auto">
          <div id="yearGrid" class="flex flex-col gap-1"></div>
        </div>
        <input type="hidden" id="yearPicker" value="<?= htmlspecialchars($selectedYear) ?>">
      </div>

      <script>
        function handleFilterChange() {
          const filter = document.getElementById('filterSelect').value;
          const monthFilter = document.getElementById('monthlyFilter');
          const yearFilter = document.getElementById('yearlyFilter');

          if (filter === 'monthly') {
            monthFilter.classList.remove('hidden');
            yearFilter.classList.add('hidden');
          } else if (filter === 'yearly') {
            monthFilter.classList.add('hidden');
            yearFilter.classList.remove('hidden');
          } else {
            monthFilter.classList.add('hidden');
            yearFilter.classList.add('hidden');
          }

          let url = '?role=radtech&page=dashboard&filter=' + filter;
          if (filter === 'monthly') url += '&month=' + document.getElementById('monthPicker').value;
          if (filter === 'yearly') url += '&year=' + document.getElementById('yearPicker').value;

          window.history.pushState({ path: url }, '', url);
          if (window.__APP__) window.__APP__.currentPath = url; // Ensure global polling uses new URL

          // Smooth fetch
          let fetchUrl = url + '&ajax_polling=1';
          fetch(fetchUrl)
            .then(res => res.text())
            .then(html => {
              const doc = new DOMParser().parseFromString(html, 'text/html');
              document.querySelectorAll('.realtime-update').forEach(el => {
                if (el.id) {
                  const newEl = doc.getElementById(el.id);
                  if (newEl) el.innerHTML = newEl.innerHTML;
                }
              });
              if (window.lucide) lucide.createIcons();
            })
            .catch(err => console.error(err));
        }

        const MONTH_NAMES = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        const MONTH_FULL = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

        let _pickerYear = parseInt(document.getElementById('monthPicker').value.split('-')[0]);

        function renderMonthGrid() {
          document.getElementById('pickerYearLabel').textContent = _pickerYear;
          const grid = document.getElementById('monthGrid');
          grid.innerHTML = '';
          MONTH_NAMES.forEach((name, i) => {
            const m = i + 1;
            let savedYear = parseInt(document.getElementById('monthPicker').value.split('-')[0]);
            let savedMonth = parseInt(document.getElementById('monthPicker').value.split('-')[1]);
            const isSelected = (m === savedMonth && _pickerYear === savedYear);
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.textContent = name;
            btn.className = 'text-sm rounded-lg py-1.5 text-center transition-colors ' +
              (isSelected
                ? 'bg-red-600 text-white font-semibold'
                : 'text-gray-700 hover:bg-gray-100');
            btn.onclick = () => selectMonth(m);
            grid.appendChild(btn);
          });
        }

        function selectMonth(m) {
          const mm = String(m).padStart(2, '0');
          document.getElementById('monthPicker').value = _pickerYear + '-' + mm;
          document.getElementById('monthPickerLabel').textContent = MONTH_FULL[m - 1] + ' ' + _pickerYear;
          document.getElementById('monthPickerPanel').classList.add('hidden');
          renderMonthGrid();
          handleFilterChange();
        }

        function changePickerYear(delta) {
          const newYear = _pickerYear + delta;
          if (newYear < 2000 || newYear > <?= date('Y') ?>) return;
          _pickerYear = newYear;
          renderMonthGrid();
        }

        function toggleMonthPicker() {
          const panel = document.getElementById('monthPickerPanel');
          panel.classList.toggle('hidden');
          if (!panel.classList.contains('hidden')) {
            _pickerYear = parseInt(document.getElementById('monthPicker').value.split('-')[0]);
            renderMonthGrid();
          }
        }

        let _pickerYearValue = parseInt(document.getElementById('yearPicker').value);

        function renderYearGrid() {
          const grid = document.getElementById('yearGrid');
          grid.innerHTML = '';
          for (let y = <?= date('Y') ?>; y >= 2000; y--) {
            const isSelected = (y === _pickerYearValue);
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.textContent = y;
            btn.className = 'text-sm rounded-lg py-2 px-3 text-center transition-colors w-full ' +
              (isSelected
                ? 'bg-red-600 text-white font-semibold'
                : 'text-gray-700 hover:bg-gray-100');
            btn.onclick = () => selectYear(y);
            grid.appendChild(btn);
          }
        }

        function selectYear(y) {
          _pickerYearValue = y;
          document.getElementById('yearPicker').value = y;
          document.getElementById('yearPickerLabel').textContent = y;
          document.getElementById('yearPickerPanel').classList.add('hidden');
          renderYearGrid();
          handleFilterChange();
        }

        function toggleYearPicker() {
          const panel = document.getElementById('yearPickerPanel');
          panel.classList.toggle('hidden');
          if (!panel.classList.contains('hidden')) {
            _pickerYearValue = parseInt(document.getElementById('yearPicker').value);
            renderYearGrid();
          }
        }

        document.addEventListener('click', function (e) {
          const monthFilter = document.getElementById('monthlyFilter');
          if (monthFilter && !monthFilter.contains(e.target)) {
            document.getElementById('monthPickerPanel').classList.add('hidden');
          }
          const yearFilter = document.getElementById('yearlyFilter');
          if (yearFilter && !yearFilter.contains(e.target)) {
            document.getElementById('yearPickerPanel').classList.add('hidden');
          }
        });
      </script>
    </div>
  </div>

  <!-- Stats -->
  <div id="radtech-dashboard-stats" class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-6 realtime-update">
    <a href="/<?= PROJECT_DIR ?>/index.php?role=radtech&page=patient-lists&filterDate=Today" class="rounded-xl bg-white border border-gray-200 shadow-sm p-5 hover:shadow-md hover:border-red-300 transition cursor-pointer flex flex-col h-full">
      <div class="flex items-start justify-between gap-2">
        <div>
          <p class="text-sm text-gray-500 font-medium">Total Patients</p>
          <p class="text-xs text-gray-400 mt-0.5"><?= htmlspecialchars($periodLabel) ?></p>
        </div>
        <i data-lucide="users" class="w-5 h-5 text-blue-400 shrink-0"></i>
      </div>
      <p class="text-3xl font-bold mt-auto pt-2"><?= htmlspecialchars($totalPatients) ?></p>
    </a>

    <a href="/<?= PROJECT_DIR ?>/index.php?role=radtech&page=patient-lists&status=Pending&filterDate=All" class="rounded-xl bg-white border border-gray-200 shadow-sm p-5 hover:shadow-md hover:border-red-300 transition cursor-pointer flex flex-col h-full">
      <div class="flex items-start justify-between gap-2">
        <p class="text-sm text-gray-500 font-medium">Pending</p>
        <i data-lucide="clock-3" class="w-5 h-5 text-orange-400 shrink-0"></i>
      </div>
      <p class="text-3xl font-bold mt-auto pt-2"><?= htmlspecialchars($pendingApprovals) ?></p>
    </a>

    <a href="/<?= PROJECT_DIR ?>/index.php?role=radtech&page=patient-lists&filterPriority=Urgent" class="rounded-xl bg-white border border-gray-200 shadow-sm p-5 hover:shadow-md hover:border-red-300 transition cursor-pointer flex flex-col h-full">
      <div class="flex items-start justify-between gap-2">
        <p class="text-sm text-gray-500 font-medium">Urgent Cases</p>
        <i data-lucide="chart-spline" class="w-5 h-5 text-yellow-400 shrink-0"></i>
      </div>
      <p class="text-3xl font-bold mt-auto pt-2"><?= htmlspecialchars($priorityCases) ?></p>
    </a>

    <a href="/<?= PROJECT_DIR ?>/index.php?role=radtech&page=patient-lists&filterPriority=STAT" class="rounded-xl bg-white border border-gray-200 shadow-sm p-5 hover:shadow-md hover:border-red-300 transition cursor-pointer flex flex-col h-full">
      <div class="flex items-start justify-between gap-2">
        <p class="text-sm text-gray-500 font-medium">STAT</p>
        <i data-lucide="triangle-alert" class="w-5 h-5 text-red-400 shrink-0"></i>
      </div>
      <p class="text-3xl font-bold mt-auto pt-2"><?= htmlspecialchars($emergencyCases) ?></p>
    </a>

    <a href="/<?= PROJECT_DIR ?>/index.php?role=radtech&page=xray-patient-records" class="rounded-xl bg-white border border-gray-200 shadow-sm p-5 hover:shadow-md hover:border-red-300 transition cursor-pointer flex flex-col h-full">
      <div class="flex items-start justify-between gap-2">
        <p class="text-sm text-gray-500 font-medium">Completed</p>
        <i data-lucide="check-circle" class="w-5 h-5 text-green-400 shrink-0"></i>
      </div>
      <p class="text-3xl font-bold mt-auto pt-2"><?= htmlspecialchars($completedCases) ?></p>
    </a>

    <a href="/<?= PROJECT_DIR ?>/index.php?role=radtech&page=patient-lists&filterDate=Backlog"
      class="rounded-xl bg-white border border-gray-200 shadow-sm p-5 hover:shadow-md hover:border-red-300 transition cursor-pointer flex flex-col h-full">
      <div class="flex items-start justify-between gap-2">
        <p class="text-sm text-gray-500 font-medium">Backlog</p>
        <i data-lucide="archive" class="w-5 h-5 text-purple-400 shrink-0"></i>
      </div>
      <p class="text-3xl font-bold mt-auto pt-2 text-purple-600"><?= htmlspecialchars($backlogCases) ?></p>
    </a>
  </div>

  <!-- Radiologists Status -->
  <div>
    <h3 class="font-bold text-gray-900 text-lg mb-4">Radiologist Workload Status</h3>
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 realtime-update" id="radiologist-workload">
      <?php if (empty($radiologistsWorkload)): ?>
        <p class="text-gray-500 text-sm">No active radiologists found.</p>
      <?php else: ?>
        <?php foreach ($radiologistsWorkload as $rad): ?>
          <div class="rounded-xl bg-white border border-gray-200 shadow-sm p-4 hover:shadow-md transition flex flex-col">
            <div class="flex items-center gap-3 mb-3">
              <div
                class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold shrink-0">
                <?= htmlspecialchars(strtoupper(substr($rad['radiologist_name'], 0, 1))) ?>
              </div>
              <div class="min-w-0">
                <p class="font-semibold text-gray-900 truncate" title="<?= htmlspecialchars($rad['radiologist_name']) ?>">
                  <?= htmlspecialchars($rad['radiologist_name']) ?>
                </p>
                <p class="text-xs text-gray-500 truncate">Radiologist</p>
              </div>
            </div>

            <div class="grid grid-cols-2 gap-2 mt-auto">
              <div class="bg-orange-50/50 border border-orange-100 rounded-lg p-2 text-center">
                <p class="text-xs text-orange-600/80 mb-0.5">Pending/Reading</p>
                <p class="font-bold text-lg text-orange-600"><?= htmlspecialchars($rad['active_cases']) ?></p>
              </div>
              <div class="bg-blue-50/50 border border-blue-100 rounded-lg p-2 text-center">
                <p class="text-xs text-blue-600/80 mb-0.5">Total Assigned</p>
                <p class="font-bold text-lg text-blue-600"><?= htmlspecialchars($rad['total_assigned']) ?></p>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <!-- Recent Queue -->
  <div
    class="rounded-xl bg-white border border-gray-200 shadow-sm xl:col-span-2 overflow-hidden hover:shadow-md transition">
    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
      <h3 class="font-bold text-gray-900 text-lg">Recent Cases added</h3>
      <a href="/<?= PROJECT_DIR ?>/patient-lists"
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
                  if ($case['priority'] === 'STAT')
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
                  $isOverdue = (time() - strtotime($case['created_at'])) >= 3 * 3600;
                  if ($displayStatus === 'Pending' && $isOverdue) {
                    $displayStatus = 'Overdue';
                  }

                  $sStyles = ['border' => '1.5px solid #facc15', 'background' => '#fefce8', 'color' => '#a16207'];
                  if ($displayStatus === 'Report Ready')
                    $sStyles = ['border' => '1.5px solid #818cf8', 'background' => '#eef2ff', 'color' => '#4338ca'];
                  if ($displayStatus === 'Under Reading')
                    $sStyles = ['border' => '1.5px solid #60a5fa', 'background' => '#eff6ff', 'color' => '#1d4ed8'];
                  if ($displayStatus === 'Completed')
                    $sStyles = ['border' => '1.5px solid #4ade80', 'background' => '#f0fdf4', 'color' => '#15803d'];
                  if ($displayStatus === 'Rejected' || $displayStatus === 'Overdue')
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