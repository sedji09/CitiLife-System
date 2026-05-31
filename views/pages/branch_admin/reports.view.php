<?php
/**
 * reports.php - Branch Admin Reports
 */
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../models/BranchModel.php';

$branchModel = new \BranchModel($pdo);
$branchId = $_SESSION['branch_id'] ?? null;
$branchData = $branchModel->getBranchById($branchId);
$branchName = $branchData['name'] ?? 'Your Branch';

// Initial data for page load (optional, we can just let JS handle it)
?>

<style>
    /* Premium Dark Mode Hover Overrides (Vivid Glow) */
    .theme-dark #card-emergency:hover,
    .theme-dark #card-with:hover,
    .theme-dark #card-urgent:hover,
    .theme-dark #card-routine:hover,
    .theme-dark #card-without:hover {
        transition: all 0.2s ease !important;
    }

    .theme-dark #card-emergency:hover,
    .theme-dark #card-with:hover {
        background-color: rgba(239, 68, 68, 0.15) !important;
        border-color: #ef4444 !important;
        box-shadow: 0 0 0 1px #ef4444 !important;
    }
    .theme-dark #card-emergency:hover span {
        color: #fca5a5 !important; /* text-red-300 */
    }

    .theme-dark #card-urgent:hover {
        background-color: rgba(249, 115, 22, 0.15) !important;
        border-color: #f97316 !important;
        box-shadow: 0 0 0 1px #f97316 !important;
    }
    .theme-dark #card-urgent:hover span {
        color: #fdba74 !important; /* text-orange-300 */
    }

    .theme-dark #card-routine:hover,
    .theme-dark #card-without:hover {
        background-color: rgba(59, 130, 246, 0.15) !important;
        border-color: #3b82f6 !important;
        box-shadow: 0 0 0 1px #3b82f6 !important;
    }
    .theme-dark #card-routine:hover span {
        color: #93c5fd !important; /* text-blue-300 */
    }
</style>

<!-- Include Chart.js -->
<script src="/<?= PROJECT_DIR ?>/public/assets/js/chart.min.js"></script>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Reports & Analytics</h1>
            <p class="text-sm text-gray-500 mt-1">Generate and export patient statistics for
                <?= htmlspecialchars($branchName) ?>.
            </p>
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
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
        <div class="flex flex-wrap items-center gap-4">
            <div class="flex items-center gap-2">
                <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Report Type:</span>
                <select id="reportType" onchange="toggleFilterView()"
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-red-500 focus:border-red-500 block p-2">
                    <option value="monthly">Monthly</option>
                    <option value="yearly">Yearly</option>
                    <option value="range">Custom Range</option>
                </select>
            </div>

            <!-- Monthly Picker -->
            <div id="monthlyFilter" class="flex items-center gap-2">
                <input type="month" id="monthPicker" value="<?= date('Y-m') ?>" onchange="loadStats()"
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block p-2">
            </div>

            <!-- Yearly Picker -->
            <div id="yearlyFilter" class="hidden flex items-center gap-2">
                <input type="number" id="yearPicker" value="<?= date('Y') ?>" min="2020" max="2100"
                    onchange="loadStats()"
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block p-2 w-24">
            </div>

            <!-- Range Picker -->
            <div id="rangeFilter" class="hidden flex items-center gap-2">
                <input type="date" id="dateFrom" value="<?= date('Y-m-01') ?>" onchange="loadStats()"
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block p-2">
                <span class="text-gray-400">to</span>
                <input type="date" id="dateTo" value="<?= date('Y-m-t') ?>" onchange="loadStats()"
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block p-2">
            </div>

            <button onclick="loadStats()" class="ml-auto p-2 text-gray-500 hover:text-red-600 transition"
                title="Refresh Data">
                <i data-lucide="refresh-cw" class="w-5 h-5"></i>
            </button>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <span class="text-sm font-medium text-gray-500">Total Patients</span>
                <div class="p-2 bg-blue-50 rounded-lg text-blue-600">
                    <i data-lucide="users" class="w-5 h-5"></i>
                </div>
            </div>
            <div id="stat-total" class="text-3xl font-bold text-gray-900">0</div>
        </div>

        <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <span class="text-sm font-medium text-gray-500">With PhilHealth</span>
                <div class="p-2 bg-green-50 rounded-lg text-green-600">
                    <i data-lucide="shield-check" class="w-5 h-5"></i>
                </div>
            </div>
            <div id="stat-philhealth" class="text-3xl font-bold text-gray-900">0</div>
        </div>

        <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <span class="text-sm font-medium text-gray-500">Emergency</span>
                <div class="p-2 bg-red-50 rounded-lg text-red-600">
                    <i data-lucide="alert-circle" class="w-5 h-5"></i>
                </div>
            </div>
            <div id="stat-emergency" class="text-3xl font-bold text-gray-900">0</div>
        </div>

        <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <span class="text-sm font-medium text-gray-500">Urgent Cases</span>
                <div class="p-2 bg-orange-50 rounded-lg text-orange-600">
                    <i data-lucide="clock" class="w-5 h-5"></i>
                </div>
            </div>
            <div id="stat-urgent" class="text-3xl font-bold text-gray-900">0</div>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- Priority Breakdown -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden flex flex-col">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex items-center justify-between">
                <h3 class="font-bold text-gray-800">Case Priority Breakdown</h3>
                <i data-lucide="trending-up" class="w-4 h-4 text-gray-400"></i>
            </div>
            <div class="p-6 flex flex-col justify-center flex-grow">
                <div class="space-y-2">
                    <div id="card-emergency"
                        class="flex items-center justify-between p-3 bg-gray-50 rounded-xl border hover:border-red-500 hover:bg-red-50 transition-all duration-200 cursor-pointer group priority-card priority-card-red">
                        <div class="flex items-center gap-3">
                            <div class="w-3 h-3 rounded-full bg-red-500 shadow-sm shadow-red-200"></div>
                            <span
                                class="text-xs font-semibold text-gray-600 group-hover:text-red-700 transition-colors">Emergency</span>
                        </div>
                        <span id="row-emergency"
                            class="text-sm font-bold text-gray-900 group-hover:text-red-700 transition-colors">0</span>
                    </div>

                    <div id="card-urgent"
                        class="flex items-center justify-between p-3 bg-gray-50 rounded-xl border hover:border-orange-500 hover:bg-orange-50 transition-all duration-200 cursor-pointer group priority-card priority-card-orange">
                        <div class="flex items-center gap-3">
                            <div class="w-3 h-3 rounded-full bg-orange-500 shadow-sm shadow-orange-200"></div>
                            <span
                                class="text-xs font-semibold text-gray-600 group-hover:text-orange-700 transition-colors">Urgent
                                / Priority</span>
                        </div>
                        <span id="row-urgent"
                            class="text-sm font-bold text-gray-900 group-hover:text-orange-700 transition-colors">0</span>
                    </div>

                    <div id="card-routine"
                        class="flex items-center justify-between p-3 bg-gray-50 rounded-xl border hover:border-blue-500 hover:bg-blue-50 transition-all duration-200 cursor-pointer group priority-card priority-card-blue">
                        <div class="flex items-center gap-3">
                            <div class="w-3 h-3 rounded-full bg-blue-500 shadow-sm shadow-blue-200"></div>
                            <span
                                class="text-xs font-semibold text-gray-600 group-hover:text-blue-700 transition-colors">Routine
                                / Normal</span>
                        </div>
                        <span id="row-routine"
                            class="text-sm font-bold text-gray-900 group-hover:text-blue-700 transition-colors">0</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- PhilHealth Breakdown Card -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden flex flex-col">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex items-center justify-between">
                <h3 class="font-bold text-gray-800">PhilHealth Distribution</h3>
                <i data-lucide="pie-chart" class="w-4 h-4 text-gray-400"></i>
            </div>
            <div class="p-6 flex flex-col items-center flex-grow">
                <!-- Chart Container -->
                <div class="relative w-40 h-40 mb-6">
                    <canvas id="philhealthChart"></canvas>
                </div>

                <!-- Custom Legend -->
                <div class="w-full space-y-2">
                    <div id="card-with"
                        class="flex items-center justify-between p-3 bg-gray-50 rounded-xl border hover:border-red-500 hover:bg-red-50 transition-all duration-200 cursor-pointer group priority-card priority-card-red">
                        <div class="flex items-center gap-3">
                            <div class="w-3 h-3 rounded-full bg-red-500 shadow-sm shadow-red-200"></div>
                            <span
                                class="text-xs font-semibold text-gray-600 group-hover:text-red-700 transition-colors">With
                                Card</span>
                        </div>
                        <span id="label-philhealth-with"
                            class="text-sm font-bold text-gray-900 group-hover:text-red-700 transition-colors">0</span>
                    </div>

                    <div id="card-without"
                        class="flex items-center justify-between p-3 bg-gray-50 rounded-xl border hover:border-blue-500 hover:bg-blue-50 transition-all duration-200 cursor-pointer group priority-card priority-card-blue">
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

        <!-- Yearly Chart (Conditional) -->
        <div id="monthlyChartContainer"
            class="lg:col-span-2 bg-white rounded-xl border border-gray-200 shadow-sm hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                <h3 class="font-bold text-gray-800">Monthly Patient Trends</h3>
            </div>
            <div class="p-6 h-64">
                <canvas id="monthlyTrendChart"></canvas>
            </div>
        </div>

    </div>
</div>

<script src="/<?= PROJECT_DIR ?>/app/views/pages/branch_admin/reports.js?v=<?= time() ?>"></script>