<style>
  /* Standard Mode Styles */
  .dash-card-header {
    background: rgba(249, 250, 251, 0.5) !important;
  }

  .dash-table-thead {
    position: sticky;
    top: 0;
    z-index: 10;
  }

  .dash-table-th {
    background: rgb(249, 250, 251) !important;
    position: sticky;
    top: 0;
    z-index: 10;
  }

  /* Dashboard Theme Overrides */
  .theme-dark .dash-card {
    background: #111827 !important;
    border-color: rgba(255, 255, 255, 0.05) !important;
  }

  .theme-dark .dash-card-header,
  body.theme-dark .dash-table-thead,
  body.theme-dark #branch-recent-activity thead,
  body.theme-dark #branch-audit-logs thead {
    background-color: #1e293b !important;
    background: #1e293b !important;
    border-bottom: 1px solid rgba(255, 255, 255, 0.05) !important;
    position: sticky;
    top: 0;
    z-index: 10;
  }

  body.theme-dark .dash-table-th {
    background-color: #1e293b !important;
    color: #94a3b8 !important;
    font-weight: 600 !important;
    font-size: 13px !important;
    text-transform: none !important;
    letter-spacing: normal !important;
    position: sticky;
    top: 0;
    z-index: 10;
  }

  .theme-dark .text-main {
    color: #f8fafc !important;
  }

  .theme-dark .text-muted {
    color: #94a3b8 !important;
  }

  .theme-dark .text-dim {
    color: #475569 !important;
  }

  /* Status Badge Light Mode Fix */
  .status-badge {
    border-style: solid !important;
    border-width: 1px !important;
  }

  /* Status-Specific Dark Mode Overrides (Vivid Glow) */
  .theme-dark .status-badge {
    background-color: rgba(255, 255, 255, 0.03) !important;
  }

  /* Green Status */
  .theme-dark .status-badge[class*="green"] {
    border-color: #10b981 !important;
    color: #34d399 !important;
    background-color: rgba(16, 185, 129, 0.1) !important;
    box-shadow: 0 0 0 0.5px #10b981 !important;
  }

  /* Red Status */
  .theme-dark .status-badge[class*="red"] {
    border-color: #f87171 !important;
    color: #fca5a5 !important;
    background-color: rgba(239, 68, 68, 0.1) !important;
    box-shadow: 0 0 0 0.5px #ef4444 !important;
  }

  /* Gray Status */
  .theme-dark .status-badge[class*="gray"],
  .theme-dark .status-badge[class*="slate"] {
    border-color: #64748b !important;
    color: #cbd5e1 !important;
    background-color: rgba(100, 116, 139, 0.1) !important;
  }
</style>

<div class="space-y-6">

  <!-- Header -->
  <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
    <div>
      <h2 class="text-2xl font-bold text-gray-900 tracking-tight">Branch Admin Dashboard</h2>
      <p class="text-sm text-gray-500 mt-1">Overview of branch activity and pending administrative tasks.</p>
    </div>
    <div class="flex items-center gap-2">
      <select id="filterSelect" onchange="handleFilterChange()"
        class="bg-white border border-gray-300 text-gray-700 text-sm rounded-lg focus:ring-red-500 focus:border-red-500 block p-2.5 shadow-sm outline-none">
        <option value="today" <?= ($filter ?? '') === 'today' ? 'selected' : '' ?>>Today</option>
        <option value="weekly" <?= ($filter ?? '') === 'weekly' ? 'selected' : '' ?>>This Week</option>
        <option value="monthly" <?= ($filter ?? '') === 'monthly' ? 'selected' : '' ?>>Monthly</option>
        <option value="yearly" <?= ($filter ?? '') === 'yearly' ? 'selected' : '' ?>>Yearly</option>
      </select>

      <input type="month" id="monthPicker" value="<?= htmlspecialchars($selectedMonth ?? date('Y-m')) ?>"
        onchange="handleFilterChange()"
        class="<?= ($filter ?? '') === 'monthly' ? '' : 'hidden' ?> bg-white border border-gray-300 text-gray-700 text-sm rounded-lg focus:ring-red-500 focus:border-red-500 block p-2.5 shadow-sm outline-none">

      <input type="number" id="yearPicker" min="2000" max="2100"
        value="<?= htmlspecialchars($selectedYear ?? date('Y')) ?>" onchange="handleFilterChange()"
        class="<?= ($filter ?? '') === 'yearly' ? '' : 'hidden' ?> bg-white border border-gray-300 text-gray-700 text-sm rounded-lg focus:ring-red-500 focus:border-red-500 block p-2.5 shadow-sm w-24 outline-none">

      <script>
        function handleFilterChange() {
          const filter = document.getElementById('filterSelect').value;
          let url = '?role=branch_admin&page=dashboard&filter=' + filter;
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

  <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">

    <!-- Total Patients of Branch Card -->
    <div class="rounded-xl bg-white border border-gray-200 shadow-sm p-5 hover:shadow-md transition group">
      <div class="flex items-center justify-between">
        <p class="text-sm font-semibold text-gray-500 group-hover:text-blue-600 transition">Total Patients of Branch
        </p>
        <div class="p-2 bg-blue-50 rounded-lg group-hover:bg-blue-100 transition">
          <i data-lucide="users" class="w-5 h-5 text-blue-600"></i>
        </div>
      </div>
      <p class="text-3xl font-bold mt-2 text-gray-900"><?= htmlspecialchars($branchTotalPatients ?? 0) ?></p>
      <p class="text-xs text-gray-400 mt-1">Registered in this branch</p>
    </div>

    <!-- X-ray Cases Today Card -->
    <div class="rounded-xl bg-white border border-gray-200 shadow-sm p-5 hover:shadow-md transition group">
      <div class="flex items-center justify-between">
        <p class="text-sm font-semibold text-gray-500 group-hover:text-red-600 transition">X-ray Cases
          <?= htmlspecialchars($periodLabel ?? 'Today') ?>
        </p>
        <div class="p-2 bg-red-50 rounded-lg group-hover:bg-red-100 transition">
          <i data-lucide="scan-eye" class="w-5 h-5 text-red-600"></i>
        </div>
      </div>
      <p class="text-3xl font-bold mt-2 text-gray-900"><?= htmlspecialchars($casesFilteredCount ?? 0) ?></p>
      <p class="text-xs text-gray-400 mt-1"><?= ($filter === 'today') ? date('F d, Y') : 'Based on selected filter' ?>
      </p>
    </div>


    <!--Pending Record Requests Card -->
    <a href="?role=branch_admin&page=record-requests"
      class="rounded-xl bg-white border border-gray-200 shadow-sm p-5 hover:shadow-md transition block group">
      <div class="flex items-center justify-between">
        <p class="text-sm font-semibold text-gray-500 group-hover:text-amber-600 transition">
          Pending Record Requests</p>
        <div class="p-2 bg-amber-50 rounded-lg group-hover:bg-amber-100 transition">
          <i data-lucide="file-text" class="w-5 h-5 text-amber-600"></i>
        </div>
      </div>
      <p class="text-3xl font-bold mt-2 text-gray-900"><?= htmlspecialchars($pendingRequestsCount ?? 0) ?></p>
      <p class="text-xs text-gray-400 mt-1">Pending from other branches</p>
    </a>




  </div>

  <!-- Recent Activity Section -->
  <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Left: Recent Branch Activity (X-ray Cases) -->
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden flex flex-col dash-card">
      <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between dash-card-header">
        <h3 class="font-bold text-gray-900 text-lg text-main">Recent Branch Activity</h3>
        <a href="?role=branch_admin&page=branch-xray-cases"
          class="text-xs font-bold text-red-600 hover:text-red-700">View All Cases</a>
      </div>

      <div class="overflow-auto scroll-smooth" style="height: 402px;">
        <table id="branch-recent-activity" class="w-full text-sm" style="border-collapse: separate; border-spacing: 0;">
          <thead class="dash-table-thead">
            <tr>
              <th class="px-4 py-3 text-left text-[12px] font-semibold text-gray-500 dash-table-th"
                style="position: sticky; top: 0; z-index: 20; background-color: #f9fafb; border-bottom: 1px solid #e5e7eb;">
                Case No.</th>
              <th class="px-4 py-3 text-left text-[12px] font-semibold text-gray-500 dash-table-th"
                style="position: sticky; top: 0; z-index: 20; background-color: #f9fafb; border-bottom: 1px solid #e5e7eb;">
                Patient Name</th>
              <th class="px-4 py-3 text-left text-[12px] font-semibold text-gray-500 dash-table-th"
                style="position: sticky; top: 0; z-index: 20; background-color: #f9fafb; border-bottom: 1px solid #e5e7eb;">
                Status</th>
              <th class="px-4 py-3 text-left text-[12px] font-semibold text-gray-500 dash-table-th"
                style="position: sticky; top: 0; z-index: 20; background-color: #f9fafb; border-bottom: 1px solid #e5e7eb;">
                Date</th>
            </tr>
          </thead>
          <tbody class="text-gray-800 divide-y divide-gray-100">
            <?php if (empty($recentCases)): ?>
              <tr>
                <td colspan="4" class="px-6 py-8 text-center text-gray-500 italic">No recent activity recorded.</td>
              </tr>
            <?php else: ?>
              <?php foreach ($recentCases as $case): ?>
                <tr class="hover:bg-gray-50 transition-colors dash-table-tr">
                  <td class="px-4 py-3 font-bold text-gray-900 whitespace-nowrap text-main dash-table-td">
                    <?= htmlspecialchars($case['case_number']) ?>
                  </td>
                  <td class="px-4 py-3 font-medium text-main dash-table-td">
                    <?= htmlspecialchars(($case['first_name'] ?? '') . ' ' . ($case['last_name'] ?? '')) ?>
                  </td>
                  <td class="px-4 py-3 dash-table-td">
                    <?php
                    $displayStatus = ($case['approval_status'] === 'Rejected' || $case['status'] === 'Rejected') ? 'Rejected' : $case['status'];
                    $sColor = 'gray';
                    if ($displayStatus === 'Report Ready')
                      $sColor = 'indigo';
                    elseif ($displayStatus === 'Under Reading')
                      $sColor = 'blue';
                    elseif ($displayStatus === 'Completed')
                      $sColor = 'green';
                    elseif ($displayStatus === 'Pending')
                      $sColor = 'yellow';
                    elseif ($displayStatus === 'Rejected')
                      $sColor = 'red';
                    ?>
                    <span
                      class="inline-flex items-center rounded-full border border-<?= $sColor === 'red' ? 'red-600' : $sColor . '-400' ?> bg-<?= $sColor ?>-50 px-2 py-0.5 text-[11px] font-semibold text-<?= $sColor ?>-700 status-badge">
                      <?= htmlspecialchars($displayStatus ?: 'Pending') ?>
                    </span>
                  </td>
                  <td class="px-4 py-3 whitespace-nowrap text-gray-500 text-muted dash-table-td">
                    <?= date('F d, Y', strtotime($case['created_at'])) ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Right: Audit Logs (System Activity) -->
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden flex flex-col dash-card">
      <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between dash-card-header">
        <h3 class="font-bold text-gray-900 text-lg text-main">System Audit Logs</h3>
        <a href="?role=branch_admin&page=audit-logs" class="text-xs font-bold text-red-600 hover:text-red-700">View All
          Logs</a>
      </div>

      <div class="overflow-auto scroll-smooth" style="height: 402px;">
        <table id="branch-audit-logs" class="w-full text-sm" style="border-collapse: separate; border-spacing: 0;">
          <thead class="dash-table-thead">
            <tr>
              <th class="px-4 py-3 text-left text-[12px] font-semibold text-gray-500 dash-table-th"
                style="position: sticky; top: 0; z-index: 20; background-color: #f9fafb; border-bottom: 1px solid #e5e7eb;">
                Date</th>
              <th class="px-4 py-3 text-left text-[12px] font-semibold text-gray-500 dash-table-th"
                style="position: sticky; top: 0; z-index: 20; background-color: #f9fafb; border-bottom: 1px solid #e5e7eb;">
                User</th>
              <th class="px-4 py-3 text-left text-[12px] font-semibold text-gray-500 dash-table-th"
                style="position: sticky; top: 0; z-index: 20; background-color: #f9fafb; border-bottom: 1px solid #e5e7eb;">
                Action</th>
              <th class="px-4 py-3 text-left text-[12px] font-semibold text-gray-500 dash-table-th"
                style="position: sticky; top: 0; z-index: 20; background-color: #f9fafb; border-bottom: 1px solid #e5e7eb;">
                Status</th>
            </tr>
          </thead>
          <tbody class="text-gray-800 divide-y divide-gray-100">
            <?php if (empty($recentActivity)): ?>
              <tr>
                <td colspan="4" class="px-6 py-8 text-center text-gray-500 italic">No audit logs recorded.</td>
              </tr>
            <?php else: ?>
              <?php foreach ($recentActivity as $log): ?>
                <?php
                // Data mapping logic for premium labels
                $actionLabel = $log['action'] ?? 'Activity Recorded';
                $statusLabel = 'Successful';
                $sColor = 'green';

                // Priority: Check for explicit rejections
                if (stripos($log['action'], 'Rejected') !== false || stripos($log['details'], 'Rejected') !== false) {
                  $statusLabel = 'Unsuccessful';
                  $sColor = 'red';
                } elseif ($log['module'] === 'Patient Management') {
                  $actionLabel = 'Account Registration';
                  if (strpos($log['action'], 'Registered') !== false) {
                    $statusLabel = 'Pending Approval';
                    $sColor = 'red';
                  }
                } elseif ($log['module'] === 'X-ray Case') {
                  $actionLabel = 'X-ray Examination';
                } elseif ($log['module'] === 'Record Request') {
                  $actionLabel = 'Information Request';
                  $statusLabel = 'Pending';
                  $sColor = 'red';
                } elseif (strpos($log['action'], 'Password Reset') !== false) {
                  $actionLabel = 'Password Reset';
                  $statusLabel = 'Successful';
                  $sColor = 'gray';
                }

                $rawName = $log['user_name'] ?: ($log['user_email'] ? explode('@', $log['user_email'])[0] : 'System');
                $subjectName = $rawName;

                if (!empty($log['details']) && preg_match('/(?:Name|Patient|User):\s*([^,\-]+)/i', $log['details'], $matches)) {
                  if (!empty(trim($matches[1])))
                    $subjectName = trim($matches[1]);
                }
                ?>
                <tr class="hover:bg-gray-50 transition-colors dash-table-tr">
                  <td class="px-4 py-3 text-gray-500 whitespace-nowrap text-muted dash-table-td">
                    <?= date('M j, Y', strtotime($log['created_at'])) ?>
                  </td>
                  <td class="px-4 py-3 dash-table-td max-w-[150px]">
                    <div class="font-medium text-gray-900 text-main truncate"><?= htmlspecialchars($subjectName) ?></div>
                    <div class="text-[10px] text-gray-400 text-dim truncate"><?= htmlspecialchars($log['user_email'] ?? '') ?></div>
                  </td>
                  <td class="px-4 py-3 text-gray-600 text-muted dash-table-td leading-tight">
                    <?= htmlspecialchars($actionLabel) ?>
                  </td>
                  <td class="px-4 py-3 dash-table-td">
                    <span
                      class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-<?= $sColor ?>-50 text-<?= $sColor ?>-700 border border-<?= $sColor === 'red' ? 'red-600' : $sColor . '-400' ?> status-badge">
                      <?= htmlspecialchars($statusLabel) ?>
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
</div>
</div>