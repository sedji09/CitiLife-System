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
$stats = $caseModel->getRadiologistStats($dateCondition, $radiologistId, 'all');
$emergencyCases = $stats['emergencyCases'];
$totalPending = $stats['totalPending'];

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

// Handle AJAX updates
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    if (ob_get_length())
        ob_clean();
    header('Content-Type: application/json');
    echo json_encode([
        'emergencyCases' => $emergencyCases,
        'totalPending' => $totalPending,
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
                    
                    window.history.pushState({path: url}, '', url);
                    
                    if (typeof fetchDashboardData === 'function') {
                        fetchDashboardData();
                    }
                }
            </script>
        </div>
    </div>

    <!-- Stats -->
    <div id="radio-dashboard-top-stats" class="grid grid-cols-1 gap-4 sm:grid-cols-2 realtime-update">
        <!-- Card 1 -->
        <a href="/<?= PROJECT_DIR ?>/worklist?priority=STAT"
            class="block cursor-pointer flex items-center gap-4 bg-white p-4 rounded-xl border border-red-200 shadow-sm hover:shadow-md transition decoration-none">
            <div id="stat-count"
                class="bg-red-100 text-red-600 font-bold text-lg w-10 h-10 flex items-center justify-center rounded-lg">
                <?= $emergencyCases ?>
            </div>
            <div>
                <p class="text-xs text-gray-500">Pending STAT Cases</p>
                <p class="text-sm font-semibold text-gray-800">Across all branches</p>
            </div>
        </a>

        <!-- Card 2 -->
        <a href="/<?= PROJECT_DIR ?>/worklist"
            class="block cursor-pointer flex items-center gap-4 bg-white p-4 rounded-xl border border-gray-200 shadow-sm hover:shadow-md transition decoration-none">
            <div id="pending-count"
                class="bg-gray-100 text-gray-700 font-bold text-lg w-10 h-10 flex items-center justify-center rounded-lg">
                <?= $totalPending ?>
            </div>
            <div>
                <p class="text-xs text-gray-500">Total Pending</p>
                <p class="text-sm font-semibold text-gray-800">All branches combined</p>
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
                                    document.getElementById('stat-count').innerText = data.emergencyCases;
                                    document.getElementById('pending-count').innerText = data.totalPending;
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

        // Polling for real-time updates every 5 seconds
        setInterval(fetchDashboardData, 5000);
    });
</script>