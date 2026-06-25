<?php
/**
 * reports.php - Branch Admin Reports
 */
require_once __DIR__ . '/../../../config/database.php';

$branchModel = new \BranchModel($pdo);
$branchId = $_SESSION['branch_id'] ?? null;
$branchData = $branchModel->getBranchById($branchId);
$branchName = $branchData['name'] ?? 'Your Branch';

// Initial data for page load (optional, we can just let JS handle it)
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/vanillajs-datepicker@1.3.4/dist/css/datepicker.min.css">
<script src="https://cdn.jsdelivr.net/npm/vanillajs-datepicker@1.3.4/dist/js/datepicker-full.min.js"></script>

<style>
    /* Premium Dark Mode Hover Overrides (Vivid Glow) */
    .theme-dark #card-stat:hover,
    .theme-dark #card-with:hover,
    .theme-dark #card-urgent:hover,
    .theme-dark #card-routine:hover,
    .theme-dark #card-without:hover {
        transition: all 0.2s ease !important;
    }

    .theme-dark #card-stat:hover,
    .theme-dark #card-with:hover {
        background-color: rgba(239, 68, 68, 0.15) !important;
        border-color: #ef4444 !important;
        box-shadow: 0 0 0 1px #ef4444 !important;
    }

    .theme-dark #card-stat:hover span {
        color: #fca5a5 !important;
        /* text-red-300 */
    }

    .theme-dark #card-urgent:hover {
        background-color: rgba(249, 115, 22, 0.15) !important;
        border-color: #f97316 !important;
        box-shadow: 0 0 0 1px #f97316 !important;
    }

    .theme-dark #card-urgent:hover span {
        color: #fdba74 !important;
        /* text-orange-300 */
    }

    .theme-dark #card-routine:hover,
    .theme-dark #card-without:hover {
        background-color: rgba(59, 130, 246, 0.15) !important;
        border-color: #3b82f6 !important;
        box-shadow: 0 0 0 1px #3b82f6 !important;
    }

    .theme-dark #card-routine:hover span {
        color: #93c5fd !important;
        /* text-blue-300 */
    }

    .theme-dark #priority-total-text,
    .theme-dark #philhealth-total-text {
        color: #f8fafc !important;
    }
</style>

<!-- Include Chart.js -->
<script src="/<?= PROJECT_DIR ?>/public/assets/js/chart.min.js"></script>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900 tracking-tight">Reports & Analytics</h1>
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
            <div id="monthlyFilter" class="flex items-center gap-2 relative">
                <button type="button" id="monthPickerTrigger" onclick="toggleMonthPicker()"
                    class="flex items-center gap-2 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg p-2.5 shadow-sm hover:border-red-400 focus:outline-none focus:ring-2 focus:ring-red-500 min-w-[140px] justify-between">
                    <span id="monthPickerLabel" class="whitespace-nowrap"><?= date('F Y') ?></span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4M8 15l4 4 4-4" />
                    </svg>
                </button>
                <div id="monthPickerPanel" class="hidden absolute top-full mt-1 left-0 bg-white border border-gray-200 rounded-xl shadow-lg z-50 p-3 w-[260px]">
                    <div class="flex items-center justify-between mb-3 px-1">
                        <button type="button" onclick="changePickerYear(-1)" class="text-gray-500 hover:text-red-600 font-bold text-lg w-7 h-7 flex items-center justify-center rounded hover:bg-gray-100">«</button>
                        <span id="pickerYearLabel" class="font-semibold text-gray-800 text-sm"></span>
                        <button type="button" onclick="changePickerYear(1)" class="text-gray-500 hover:text-red-600 font-bold text-lg w-7 h-7 flex items-center justify-center rounded hover:bg-gray-100">»</button>
                    </div>
                    <div id="monthGrid" class="grid grid-cols-4 gap-1"></div>
                </div>
                <input type="hidden" id="monthPicker" value="<?= date('Y-m') ?>">
            </div>

            <!-- Yearly Picker -->
            <div id="yearlyFilter" class="hidden flex items-center gap-2 relative">
                <button type="button" id="yearPickerTrigger" onclick="toggleYearPicker()"
                    class="flex items-center gap-2 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg p-2.5 shadow-sm hover:border-red-400 focus:outline-none focus:ring-2 focus:ring-red-500 min-w-[100px] justify-between">
                    <span id="yearPickerLabel" class="whitespace-nowrap"><?= date('Y') ?></span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4M8 15l4 4 4-4" />
                    </svg>
                </button>
                <div id="yearPickerPanel" class="hidden absolute top-full mt-1 left-0 bg-white border border-gray-200 rounded-xl shadow-lg z-50 p-2 w-[110px] max-h-64 overflow-y-auto">
                    <div id="yearGrid" class="flex flex-col gap-1"></div>
                </div>
                <input type="hidden" id="yearPicker" value="<?= date('Y') ?>">
            </div>

            <!-- Range Picker (using vanillajs-datepicker) -->
            <div id="rangeFilter" class="hidden flex items-center gap-3">
                <div class="relative">
                    <input type="text" id="dateFrom" value="<?= date('Y-m-01') ?>" readonly
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-red-500 focus:border-red-500 block p-2 pl-3 pr-9 w-[130px] cursor-pointer">
                    <i data-lucide="calendar" class="absolute top-2.5 w-4 h-4 text-gray-400 pointer-events-none" style="right: 10px;"></i>
                </div>
                <span class="text-gray-400 text-sm font-medium">to</span>
                <div class="relative">
                    <input type="text" id="dateTo" value="<?= date('Y-m-t') ?>" readonly
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-red-500 focus:border-red-500 block p-2 pl-3 pr-9 w-[130px] cursor-pointer">
                    <i data-lucide="calendar" class="absolute top-2.5 w-4 h-4 text-gray-400 pointer-events-none" style="right: 10px;"></i>
                </div>
            </div>
            
            <script>
                const MONTH_NAMES = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                const MONTH_FULL = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

                let currentMonthVal = document.getElementById('monthPicker').value; // YYYY-MM
                let _pickerYear = parseInt(currentMonthVal.split('-')[0]);
                let _pickerMonth = parseInt(currentMonthVal.split('-')[1]); // 1-12

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
                    _pickerMonth = m;
                    const mm = String(m).padStart(2, '0');
                    document.getElementById('monthPicker').value = _pickerYear + '-' + mm;
                    document.getElementById('monthPickerLabel').textContent = MONTH_FULL[m - 1] + ' ' + _pickerYear;
                    document.getElementById('monthPickerPanel').classList.add('hidden');
                    renderMonthGrid();
                    loadStats();
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
                        let current = document.getElementById('monthPicker').value.split('-');
                        _pickerYear = parseInt(current[0]);
                        _pickerMonth = parseInt(current[1]);
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
                    loadStats();
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

                // Use a short delay or requestAnimationFrame to ensure Vue has finished mounting
                // and DOM elements are preserved, before attaching the Datepicker.
                setTimeout(() => {
                    const df = document.getElementById('dateFrom');
                    const dt = document.getElementById('dateTo');
                    if (df && dt && typeof Datepicker !== 'undefined') {
                        new Datepicker(df, {
                            autohide: true,
                            format: 'yyyy-mm-dd',
                            todayHighlight: true
                        });
                        new Datepicker(dt, {
                            autohide: true,
                            format: 'yyyy-mm-dd',
                            todayHighlight: true
                        });
                        
                        df.addEventListener('changeDate', loadStats);
                        dt.addEventListener('changeDate', loadStats);
                    }
                }, 100);
            </script>

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
                <span class="text-sm font-medium text-gray-500">STAT</span>
                <div class="p-2 bg-red-50 rounded-lg text-red-600">
                    <i data-lucide="alert-circle" class="w-5 h-5"></i>
                </div>
            </div>
            <div id="stat-stat" class="text-3xl font-bold text-gray-900">0</div>
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
            <div class="p-6 flex flex-col sm:flex-row items-center justify-center gap-8 flex-grow">
                <!-- Left: Chart -->
                <div class="relative w-40 h-40 flex-shrink-0">
                    <canvas id="priorityChart"></canvas>
                    <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none mt-1">
                        <span class="text-xs font-bold text-gray-400 leading-none">Total</span>
                        <span id="priority-total-text" class="text-xl font-black text-gray-900 leading-tight">0</span>
                    </div>
                </div>

                <!-- Right: Legend Cards -->
                <div class="w-full sm:w-auto flex-grow space-y-2 max-w-[260px]">
                    <div id="card-stat"
                        class="flex items-center justify-between p-3 bg-gray-50 rounded-xl border hover:border-red-500 hover:bg-red-50 transition-all duration-200 cursor-pointer group priority-card priority-card-red">
                        <div class="flex items-center gap-3">
                            <div class="w-3 h-3 rounded-full bg-red-500 shadow-sm shadow-red-200"></div>
                            <span
                                class="text-xs font-semibold text-gray-600 group-hover:text-red-700 transition-colors">STAT</span>
                        </div>
                        <span id="row-stat"
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
            <div class="p-6 flex flex-col sm:flex-row items-center justify-center gap-8 flex-grow">
                <!-- Left: Chart -->
                <div class="relative w-40 h-40 flex-shrink-0">
                    <canvas id="philhealthChart"></canvas>
                    <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none mt-1">
                        <span class="text-xs font-bold text-gray-400 leading-none">Total</span>
                        <span id="philhealth-total-text" class="text-xl font-black text-gray-900 leading-tight">0</span>
                    </div>
                </div>

                <!-- Right: Legend Cards -->
                <div class="w-full sm:w-auto flex-grow space-y-2 max-w-[260px]">
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

<script src="/<?= PROJECT_DIR ?>/views/pages/branch_admin/reports.js?v=<?= time() ?>"></script>