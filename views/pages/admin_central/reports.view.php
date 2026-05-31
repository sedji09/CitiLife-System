<?php
/**
 * reports.php - Admin Central Reports
 */
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../models/BranchModel.php';

$branchId = $_SESSION['branch_id'] ?? null;
// $allBranches is already provided by the controller
?>

<!-- Include Chart.js -->
<script src="/<?= PROJECT_DIR ?>/public/assets/js/chart.min.js"></script>

<div class="space-y-6 pb-10">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 tracking-tight">System-Wide Analytics</h1>
            <p class="text-sm text-gray-500 mt-1">Consolidated reports and branch performance metrics.</p>
        </div>

        <!-- Export Actions -->
        <div class="flex items-center gap-2">
            <button onclick="exportReport('excel')"
                class="flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition shadow-sm font-medium text-sm">
                <i data-lucide="file-spreadsheet" class="w-4 h-4"></i>
                Excel (CSV)
            </button>
            <button onclick="exportReport('pdf')"
                class="flex items-center gap-2 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition shadow-sm font-medium text-sm">
                <i data-lucide="file-text" class="w-4 h-4"></i>
                PDF Report
            </button>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
            <!-- Branch Mode -->
            <div>
                <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Scope</label>
                <select id="branchMode" onchange="toggleBranchSelection()"
                    class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5">
                    <option value="all">All Branches</option>
                    <option value="selected">Specific Branches</option>
                </select>
            </div>

            <!-- Date Range Type -->
            <div>
                <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Period</label>
                <select id="reportType" onchange="toggleFilterView()"
                    class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5">
                    <option value="monthly">Monthly</option>
                    <option value="yearly">Yearly</option>
                    <option value="range">Custom Range</option>
                </select>
            </div>

            <!-- Dynamic Date Pickers -->
            <div class="lg:col-span-2 flex items-end gap-3">
                <div id="monthlyFilter" class="w-full">
                    <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Select
                        Month</label>
                    <input type="month" id="monthPicker" value="<?= date('Y-m') ?>"
                        class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg p-2.5">
                </div>

                <div id="yearlyFilter" class="w-full hidden">
                    <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Select
                        Year</label>
                    <input type="number" id="yearPicker" value="<?= date('Y') ?>" min="2020" max="2100"
                        class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg p-2.5">
                </div>

                <div id="rangeFilter" class="w-full hidden flex items-center gap-2">
                    <div class="flex-1">
                        <label
                            class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">From</label>
                        <input type="date" id="dateFrom" value="<?= date('Y-m-01') ?>"
                            class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg p-2.5">
                    </div>
                    <div class="flex-1">
                        <label
                            class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">To</label>
                        <input type="date" id="dateTo" value="<?= date('Y-m-t') ?>"
                            class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg p-2.5">
                    </div>
                </div>

                <button onclick="loadStats()"
                    class="h-[42px] px-6 bg-gray-900 text-white rounded-lg hover:bg-gray-800 transition shadow-sm font-medium text-sm flex items-center gap-2">
                    <i data-lucide="search" class="w-4 h-4"></i>
                    Generate
                </button>
            </div>
        </div>

        <!-- Branch Selection (Checkboxes) -->
        <div id="branchSelectionArea" class="hidden mt-6 pt-6 border-t border-gray-100">
            <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-3">Select Branches to
                Include</label>
            <div class="flex flex-wrap gap-3">
                <?php foreach ($allBranches as $branch): ?>
                    <label
                        class="flex items-center gap-2 px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg cursor-pointer hover:bg-white hover:border-blue-300 transition group">
                        <input type="checkbox" name="branch_ids" value="<?= $branch['id'] ?>"
                            class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
                        <span class="text-sm font-medium text-gray-700 group-hover:text-blue-600">
                            <?= htmlspecialchars($branch['name']) ?>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <span class="text-sm font-medium text-gray-500 tracking-tight">Total Patients</span>
                <div class="p-2 bg-blue-50 rounded-lg text-blue-600">
                    <i data-lucide="users" class="w-5 h-5"></i>
                </div>
            </div>
            <div id="stat-total" class="text-3xl font-bold text-gray-900">0</div>
        </div>

        <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <span class="text-sm font-medium text-gray-500 tracking-tight">With PhilHealth</span>
                <div class="p-2 bg-green-50 rounded-lg text-green-600">
                    <i data-lucide="shield-check" class="w-5 h-5"></i>
                </div>
            </div>
            <div id="stat-philhealth" class="text-3xl font-bold text-gray-900">0</div>
        </div>

        <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <span class="text-sm font-medium text-gray-500 tracking-tight">Emergency</span>
                <div class="p-2 bg-red-50 rounded-lg text-red-600">
                    <i data-lucide="alert-circle" class="w-5 h-5"></i>
                </div>
            </div>
            <div id="stat-emergency" class="text-3xl font-bold text-gray-900">0</div>
        </div>

        <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <span class="text-sm font-medium text-gray-500 tracking-tight">Urgent Cases</span>
                <div class="p-2 bg-orange-50 rounded-lg text-orange-600">
                    <i data-lucide="clock" class="w-5 h-5"></i>
                </div>
            </div>
            <div id="stat-urgent" class="text-3xl font-bold text-gray-900">0</div>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Detailed Branch Table -->
        <div class="lg:col-span-2 bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden flex flex-col">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex items-center justify-between">
                <div>
                    <h3 class="font-bold text-gray-800">Branch Performance Breakdown</h3>
                    <p class="text-[10px] text-gray-500 uppercase tracking-widest font-medium">Detailed metrics per
                        location</p>
                </div>
                <i data-lucide="layout-list" class="w-4 h-4 text-gray-400"></i>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="text-xs text-gray-500 uppercase bg-gray-50 border-b border-gray-100">
                        <tr>
                            <th class="px-6 py-4 font-bold">Branch Name</th>
                            <th class="px-6 py-4 font-bold text-center">Total</th>
                            <th class="px-6 py-4 font-bold text-center">Emergency</th>
                            <th class="px-6 py-4 font-bold text-center">Urgent</th>
                            <th class="px-6 py-4 font-bold text-center">Routine</th>
                            <th class="px-6 py-4 font-bold text-center">PhilHealth</th>
                        </tr>
                    </thead>
                    <tbody id="branchStatsTable" class="divide-y divide-gray-100">
                        <!-- Dynamic Rows -->
                        <tr>
                            <td colspan="6" class="px-6 py-10 text-center text-gray-500 bg-white">
                                <div class="flex flex-col items-center gap-2">
                                    <div
                                        class="animate-spin h-5 w-5 border-2 border-blue-600 border-t-transparent rounded-full">
                                    </div>
                                    <span>Loading branch statistics...</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Distribution Card -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden flex flex-col">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex items-center justify-between">
                <div>
                    <h3 class="font-bold text-gray-800">PhilHealth Coverage</h3>
                    <p class="text-[10px] text-gray-500 uppercase tracking-widest font-medium">Across all selected scope
                    </p>
                </div>
                <i data-lucide="pie-chart" class="w-4 h-4 text-gray-400"></i>
            </div>
            <div class="p-6 flex flex-col items-center flex-grow">
                <!-- Chart Container -->
                <div class="relative w-40 h-40 mb-6">
                    <canvas id="philhealthChart"></canvas>
                </div>

                <!-- Custom Legend -->
                <div class="w-full space-y-2">
                    <div
                        class="flex items-center justify-between p-3 bg-gray-50 rounded-xl border hover:border-red-500 hover:bg-red-50 transition-all duration-200 cursor-pointer group">
                        <div class="flex items-center gap-3">
                            <div class="w-3 h-3 rounded-full bg-red-500 shadow-sm shadow-red-200"></div>
                            <span
                                class="text-xs font-semibold text-gray-600 group-hover:text-red-700 transition-colors">With
                                Card</span>
                        </div>
                        <span id="label-philhealth-with"
                            class="text-sm font-bold text-gray-900 group-hover:text-red-700 transition-colors">0</span>
                    </div>

                    <div
                        class="flex items-center justify-between p-3 bg-gray-50 rounded-xl border hover:border-blue-500 hover:bg-blue-50 transition-all duration-200 cursor-pointer group">
                        <div class="flex items-center gap-3">
                            <div class="w-3 h-3 rounded-full bg-blue-500 shadow-sm shadow-blue-200"></div>
                            <span
                                class="text-xs font-semibold text-gray-600 group-hover:text-blue-700 transition-colors">Without
                                Card</span>
                        </div>
                        <span id="label-philhealth-without"
                            class="text-sm font-bold text-gray-900 group-hover:text-blue-700 transition-colors">0</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Trend Chart (Always centered or spans) -->
        <div id="trendChartContainer" class="lg:col-span-3 bg-white rounded-xl border border-gray-200 shadow-sm hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex items-center justify-between">
                <div>
                    <h3 class="font-bold text-gray-800">Monthly Registration Trend</h3>
                    <p class="text-[10px] text-gray-500 uppercase tracking-widest font-medium">Year-to-date data for the
                        selected branch</p>
                </div>
                <i data-lucide="line-chart" class="w-4 h-4 text-gray-400"></i>
            </div>
            <div class="p-6 h-[300px]">
                <canvas id="trendChart"></canvas>
            </div>
        </div>

    </div>
</div>

<script src="/<?= PROJECT_DIR ?>/app/views/pages/admin_central/reports.js?v=<?= time() ?>"></script>