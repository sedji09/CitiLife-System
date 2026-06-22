<?php
require_once __DIR__ . '/../../../config/database.php';

$caseModel = new \CaseModel($pdo);
$branchModel = new \BranchModel($pdo);
$notificationModel = new \NotificationModel($pdo);

$priorityFilter = $_GET['priority'] ?? 'all';
$filter = $_GET['filter'] ?? 'today';
$selectedMonth = $_GET['month'] ?? date('Y-m');
$selectedYear = $_GET['year'] ?? date('Y');

$dateInfo = $caseModel->buildDateCondition($filter, $selectedMonth, $selectedYear);
$dateCondition = $dateInfo['condition'];
$periodLabel = $dateInfo['label'];

// 1. Fetch Stats (Backend logic)
$radiologistId = $_SESSION['user_id'] ?? null;
// Global stats — NOT date-filtered. These cards always show all unfinished pending cases.
$globalStats = $caseModel->getGlobalPendingStats($radiologistId);
$emergencyCases = $globalStats['emergencyCases'];
$totalPending = $globalStats['totalPending'];
$overdueCases = $globalStats['overdueCases'];
$completedToday = $globalStats['completedToday'];
$inProgress = $globalStats['inProgress'];
$forRevision = $globalStats['forRevision'];

// 2. Fetch Aggregated Chart Data (Backend logic)
$branchesList = $branchModel->getAllBranches();
$branchPriorityRows = $caseModel->getBranchPriorityStats($dateCondition, $radiologistId, 'all');

// Process for Chart.js (Frontend-specific formatting)
$branchStats = [];
$labels = [];
$emergencyData = [];
$urgentData = [];
$routineData = [];
$branchTotals = [];
$branchColors = [];

foreach ($branchesList as $b) {
    $branchStats[$b['id']] = [
        'name' => $b['name'],
        'STAT' => 0,
        'Urgent' => 0,
        'Routine' => 0
    ];
}

foreach ($branchPriorityRows as $row) {
    $bid = $row['branch_id'];
    if (isset($branchStats[$bid])) {
        $prio = ($row['priority'] === 'Normal' || $row['priority'] === 'Priority') ? 'Routine' : $row['priority'];
        if (isset($branchStats[$bid][$prio])) {
            $branchStats[$bid][$prio] += (int) $row['count'];
        }
    }
}

$availableColors = ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#EC4899', '#06B6D4', '#F97316', '#14B8A6', '#84CC16'];
$colorIndex = 0;

foreach ($branchStats as $stat) {
    $labels[] = $stat['name'];
    $emergencyData[] = ($priorityFilter === 'all' || $priorityFilter === 'STAT') ? $stat['STAT'] : 0;
    $urgentData[] = ($priorityFilter === 'all' || $priorityFilter === 'Urgent') ? $stat['Urgent'] : 0;
    $routineData[] = ($priorityFilter === 'all' || $priorityFilter === 'Routine') ? $stat['Routine'] : 0;

    $total = $stat['STAT'] + $stat['Urgent'] + $stat['Routine'];
    $branchTotals[] = $total;
    $branchColors[] = $availableColors[$colorIndex++ % count($availableColors)];
}

// Handle AJAX: global stat cards refresh (no date filter)
if (isset($_GET['ajax']) && $_GET['ajax'] == '1' && isset($_GET['global_stats'])) {
    if (ob_get_length())
        ob_clean();
    header('Content-Type: application/json');
    $gs = $caseModel->getGlobalPendingStats($radiologistId);
    echo json_encode([
        'emergencyCases' => $gs['emergencyCases'],
        'totalPending' => $gs['totalPending'],
        'overdueCases' => $gs['overdueCases'],
        'completedToday' => $gs['completedToday'],
        'inProgress' => $gs['inProgress'],
        'forRevision' => $gs['forRevision'],
    ]);
    exit;
}

// Handle AJAX updates (date-filtered charts)
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    if (ob_get_length())
        ob_clean();
    header('Content-Type: application/json');
    // Re-fetch chart stats using the date condition (not global)
    $chartStats = $caseModel->getRadiologistStats($dateCondition, $radiologistId, 'all');
    echo json_encode([
        'emergencyCases' => $chartStats['emergencyCases'],
        'totalPending' => $chartStats['totalPending'],
        'labels' => $labels,
        'emergencyData' => $emergencyData,
        'urgentData' => $urgentData,
        'routineData' => $routineData,
        'branchTotals' => $branchTotals,
        'branchColors' => $branchColors,
        'periodLabel' => $periodLabel
    ]);
    exit;
}
?>
<!-- Include Chart.js -->
<script src="/<?= PROJECT_DIR ?>/public/assets/js/chart.min.js"></script>

<div class="space-y-6">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 tracking-tight">Radiologist Dashboard</h2>
            <p class="text-sm text-gray-500 mt-1">Overview of patients for <span
                    id="period-label"><?= htmlspecialchars($periodLabel) ?></span> in all branches.</p>
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
                    const monthPicker = document.getElementById('monthPicker');
                    const yearPicker = document.getElementById('yearPicker');

                    if (filter === 'monthly') {
                        monthPicker.classList.remove('hidden');
                        yearPicker.classList.add('hidden');
                    } else if (filter === 'yearly') {
                        monthPicker.classList.add('hidden');
                        yearPicker.classList.remove('hidden');
                    } else {
                        monthPicker.classList.add('hidden');
                        yearPicker.classList.add('hidden');
                    }

                    let url = '?role=radiologist&page=dashboard&filter=' + filter;
                    if (filter === 'monthly') url += '&month=' + monthPicker.value;
                    if (filter === 'yearly') url += '&year=' + yearPicker.value;

                    window.history.pushState({ path: url }, '', url);

                    if (typeof fetchDashboardData === 'function') {
                        fetchDashboardData();
                    }
                }
            </script>
        </div>
    </div>

    <!-- Stats Grid: 6 cards in 2 rows of 3 -->
    <div id="radio-dashboard-top-stats" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 realtime-update">

        <!-- Card 1: Pending STAT -->
        <a href="/<?= PROJECT_DIR ?>/worklist?priority=STAT"
            class="group flex flex-col gap-2 bg-white p-4 rounded-xl border border-red-200 shadow-sm hover:shadow-md hover:border-red-400 transition-all decoration-none">
            <div class="flex items-center justify-between">
                <div class="bg-red-100 p-2 rounded-lg group-hover:bg-red-200 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-red-600" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2">
                        <path
                            d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" />
                        <line x1="12" y1="9" x2="12" y2="13" />
                        <line x1="12" y1="17" x2="12.01" y2="17" />
                    </svg>
                </div>
                <span id="stat-count" class="text-2xl font-extrabold text-red-600"><?= $emergencyCases ?></span>
            </div>
            <div>
                <p class="text-xs font-semibold text-gray-800">Pending STAT</p>
                <p class="text-[10px] text-gray-400">Across all branches</p>
            </div>
        </a>

        <!-- Card 2: Total Pending -->
        <a href="/<?= PROJECT_DIR ?>/worklist"
            class="group flex flex-col gap-2 bg-white p-4 rounded-xl border border-orange-200 shadow-sm hover:shadow-md hover:border-orange-400 transition-all decoration-none">
            <div class="flex items-center justify-between">
                <div class="bg-orange-100 p-2 rounded-lg group-hover:bg-orange-200 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-orange-600" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                        <polyline points="14 2 14 8 20 8" />
                        <line x1="16" y1="13" x2="8" y2="13" />
                        <line x1="16" y1="17" x2="8" y2="17" />
                        <polyline points="10 9 9 9 8 9" />
                    </svg>
                </div>
                <span id="pending-count" class="text-2xl font-extrabold text-orange-600"><?= $totalPending ?></span>
            </div>
            <div>
                <p class="text-xs font-semibold text-gray-800">Total Pending</p>
                <p class="text-[10px] text-gray-400">All branches</p>
            </div>
        </a>

        <!-- Card 3: Overdue -->
        <a href="/<?= PROJECT_DIR ?>/worklist?status=overdue"
            class="group flex flex-col gap-2 bg-white p-4 rounded-xl border border-red-200 shadow-sm hover:shadow-md hover:border-red-400 transition-all decoration-none">
            <div class="flex items-center justify-between">
                <div class="bg-red-100 p-2 rounded-lg group-hover:bg-red-200 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-red-600" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10" />
                        <polyline points="12 6 12 12 16 14" />
                    </svg>
                </div>
                <span id="overdue-count" class="text-2xl font-extrabold text-red-600"><?= $overdueCases ?></span>
            </div>
            <div>
                <p class="text-xs font-semibold text-gray-800">Overdue</p>
                <p class="text-[10px] text-gray-400">Unread cases</p>
            </div>
        </a>

        <!-- Card 4: In Progress -->
        <a href="/<?= PROJECT_DIR ?>/worklist?status=Under+Reading"
            class="group flex flex-col gap-2 bg-white p-4 rounded-xl border border-blue-200 shadow-sm hover:shadow-md hover:border-blue-400 transition-all decoration-none">
            <div class="flex items-center justify-between">
                <div class="bg-blue-100 p-2 rounded-lg group-hover:bg-blue-200 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-blue-600" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10" />
                        <polyline points="12 6 12 12" />
                        <path d="M12 12 L16 14" />
                        <path d="M8 6 l8 0" opacity=".3" />
                    </svg>
                </div>
                <span id="inprogress-count" class="text-2xl font-extrabold text-blue-600"><?= $inProgress ?></span>
            </div>
            <div>
                <p class="text-xs font-semibold text-gray-800">In Progress</p>
                <p class="text-[10px] text-gray-400">Under Reading Cases</p>
            </div>
        </a>

        <!-- Card 5: Completed Today -->
        <a href="/<?= PROJECT_DIR ?>/worklist?status=completed_today"
            class="group flex flex-col gap-2 bg-white p-4 rounded-xl border border-green-200 shadow-sm hover:shadow-md hover:border-green-400 transition-all decoration-none">
            <div class="flex items-center justify-between">
                <div class="bg-green-100 p-2 rounded-lg group-hover:bg-green-200 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-green-600" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
                        <polyline points="22 4 12 14.01 9 11.01" />
                    </svg>
                </div>
                <span id="completed-count" class="text-2xl font-extrabold text-green-600"><?= $completedToday ?></span>
            </div>
            <div>
                <p class="text-xs font-semibold text-gray-800">Completed Today</p>
                <p class="text-[10px] text-gray-400">Reports submitted</p>
            </div>
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Bar Chart Container -->
        <div class="lg:col-span-2 rounded-xl bg-white border border-gray-200 shadow-sm overflow-hidden">
            <!-- Chart Box Header & Filters -->
            <div
                class="flex flex-col md:flex-row items-center justify-between px-6 py-4 border-b border-gray-300 gap-4">
                <h3 class="font-bold text-gray-900 text-lg">Case Priority Overview</h3>
                <div class="flex items-center gap-2">
                    <select id="priorityFilter" onchange="fetchDashboardData()"
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 shadow-sm">
                        <option value="all" <?= $priorityFilter === 'all' ? 'selected' : '' ?>>All Priorities</option>
                        <option value="STAT" <?= $priorityFilter === 'STAT' ? 'selected' : '' ?>>STAT Only</option>
                        <option value="Urgent" <?= $priorityFilter === 'Urgent' ? 'selected' : '' ?>>Urgent Only</option>
                        <option value="Routine" <?= $priorityFilter === 'Routine' ? 'selected' : '' ?>>Routine Only
                        </option>
                    </select>

                    <script>
                        function fetchDashboardData() {
                            const priority = document.getElementById('priorityFilter').value;
                            const filter = document.getElementById('filterSelect').value;
                            const month = document.getElementById('monthPicker').value;
                            const year = document.getElementById('yearPicker').value;

                            let url = '?role=radiologist&page=dashboard&priority=' + priority + '&filter=' + filter + '&ajax=1';
                            if (filter === 'monthly' && month) url += '&month=' + month;
                            if (filter === 'yearly' && year) url += '&year=' + year;

                            fetch(url, { cache: 'no-store' })
                                .then(res => {
                                    if (!res.ok) throw new Error("Network response was not ok");
                                    return res.json();
                                })
                                .then(data => {
                                    // NOTE: stat-count and pending-count are global (not date-filtered),
                                    // so they are intentionally NOT updated here.
                                    document.getElementById('period-label').innerText = data.periodLabel;

                                    if (window.priorityChart) {
                                        window.priorityChart.data.labels = data.labels;
                                        window.priorityChart.data.datasets[0].data = data.emergencyData;
                                        window.priorityChart.data.datasets[1].data = data.urgentData;
                                        window.priorityChart.data.datasets[2].data = data.routineData;
                                        window.priorityChart.update();
                                    }

                                    if (window.branchPieChart) {
                                        window.branchPieChart.data.labels = data.labels;
                                        window.branchPieChart.data.datasets[0].data = data.branchTotals;
                                        window.branchPieChart.data.datasets[0].backgroundColor = data.branchColors;
                                        window.branchPieChart.update();
                                    }
                                })
                                .catch(err => console.error('Error fetching realtime dashboard data:', err));
                        }


                    </script>
                </div>
            </div>

            <!-- Chart Container -->
            <div class="p-6 relative w-full h-[400px]">
                <canvas id="priorityChart"></canvas>
            </div>
        </div>

        <!-- Pie Chart Container -->
        <div class="rounded-xl bg-white border border-gray-200 shadow-sm overflow-hidden flex flex-col">
            <div class="px-6 py-4 border-b border-gray-300">
                <h3 class="font-bold text-gray-900 text-lg">Patients per Branch</h3>
            </div>
            <div class="p-6 relative w-full flex-grow flex items-center justify-center min-h-[400px]">
                <canvas id="branchPieChart"></canvas>
            </div>
        </div>

    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const ctx = document.getElementById('priorityChart').getContext('2d');
        window.priorityChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($labels) ?>,
                datasets: [
                    {
                        label: 'STAT',
                        data: <?= json_encode($emergencyData) ?>,
                        backgroundColor: '#EF4444', // Red-500
                        borderRadius: 4,
                    },
                    {
                        label: 'Urgent',
                        data: <?= json_encode($urgentData) ?>,
                        backgroundColor: '#F59E0B', // Amber-500 / Yellow
                        borderRadius: 4,
                    },
                    {
                        label: 'Routine',
                        data: <?= json_encode($routineData) ?>,
                        backgroundColor: '#3B82F6', // Blue-500
                        borderRadius: 4,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                onClick: (e, activeElements, chart) => {
                    const exactElements = chart.getElementsAtEventForMode(e, 'nearest', { intersect: true }, true);
                    if (exactElements.length > 0) {
                        const firstPoint = exactElements[0];
                        const label = chart.data.labels[firstPoint.index];
                        const datasetLabel = chart.data.datasets[firstPoint.datasetIndex].label;
                        window.location.href = '/<?= PROJECT_DIR ?>/worklist?branch=' + encodeURIComponent(label) + '&priority=' + encodeURIComponent(datasetLabel);
                    }
                },
                onHover: (e, activeElements) => {
                    e.native.target.style.cursor = activeElements.length ? 'pointer' : 'default';
                },
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        padding: 12,
                        titleFont: { size: 14 },
                        bodyFont: { size: 13 },
                    }
                },
                scales: {
                    x: {
                        stacked: false, // Set to true if you want stacked bars
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        stacked: false, // Set to true if you want stacked bars
                        beginAtZero: true,
                        ticks: {
                            precision: 0 // Integer ticks only
                        },
                        grid: {
                            borderDash: [5, 5]
                        }
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'index',
                },
            }
        });

        // Initialize Pie Chart
        const pieCtx = document.getElementById('branchPieChart').getContext('2d');
        window.branchPieChart = new Chart(pieCtx, {
            type: 'doughnut', // using doughnut for better aesthetic, easily changeable to 'pie'
            data: {
                labels: <?= json_encode($labels) ?>,
                datasets: [{
                    data: <?= json_encode($branchTotals) ?>,
                    backgroundColor: <?= json_encode($branchColors) ?>,
                    borderWidth: 1,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                onClick: (e, activeElements, chart) => {
                    const exactElements = chart.getElementsAtEventForMode(e, 'nearest', { intersect: true }, true);
                    if (exactElements.length > 0) {
                        const firstPoint = exactElements[0];
                        const label = chart.data.labels[firstPoint.index]; // Branch
                        window.location.href = '/<?= PROJECT_DIR ?>/worklist?branch=' + encodeURIComponent(label);
                    }
                },
                onHover: (e, activeElements) => {
                    e.native.target.style.cursor = activeElements.length ? 'pointer' : 'default';
                },
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 10,
                            usePointStyle: true,
                            font: { size: 11 }
                        }
                    },
                    tooltip: {
                        padding: 12,
                        titleFont: { size: 14 },
                        bodyFont: { size: 13 },
                    }
                },
                cutout: '60%' // creates the inner hole to make it a doughnut
            }
        });

        // Poll chart data (date-filtered) every 5 seconds
        setInterval(fetchDashboardData, 5000);

        // Poll global stat cards separately — always without date filter
        function refreshGlobalStats() {
            fetch('?role=radiologist&page=dashboard&filter=all&ajax=1&global_stats=1', { cache: 'no-store' })
                .then(res => res.ok ? res.json() : null)
                .then(data => {
                    if (!data) return;
                    document.getElementById('stat-count').innerText = data.emergencyCases;
                    document.getElementById('pending-count').innerText = data.totalPending;
                    document.getElementById('overdue-count').innerText = data.overdueCases;
                    document.getElementById('inprogress-count').innerText = data.inProgress;
                    document.getElementById('completed-count').innerText = data.completedToday;
                    document.getElementById('revision-count').innerText = data.forRevision;
                })
                .catch(() => { });
        }
        refreshGlobalStats();
        setInterval(refreshGlobalStats, 5000);
    });
</script>