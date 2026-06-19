<?php

namespace App\Controllers\admin_central;

class DashboardController
{
    public function handle()
    {
        global $pdo;


/**
 * DashboardController.php
 * Handles the Admin Central dashboard metrics and charts.
 */


$branchModel = new \BranchModel($pdo);
$caseModel = new \CaseModel($pdo);
$userModel = new \UserModel($pdo);

// Handle Date Filter
$filter = $_GET['filter'] ?? 'today';
$startDate = date('Y-m-d');
$endDate = date('Y-m-d');

if ($filter === 'today') {
    $startDate = date('Y-m-d');
    $endDate = date('Y-m-d');
} elseif ($filter === 'this_week') {
    $startDate = date('Y-m-d', strtotime('monday this week'));
    $endDate = date('Y-m-d', strtotime('sunday this week'));
} elseif ($filter === 'monthly') {
    $startDate = date('Y-m-01');
    $endDate = date('Y-m-t');
} elseif ($filter === 'yearly') {
    $startDate = date('Y-01-01');
    $endDate = date('Y-12-31');
}

// 1. Get Overall Stats based on filter
$allTimeStats = $caseModel->getReportStats($startDate, $endDate);
$allBranchesList = $branchModel->getAllBranches();

$totalPatients = 0;
$totalEmergency = 0;
$totalUrgent = 0;
$totalRoutine = 0;
$totalPhilHealth = 0;

$branchesData = []; // For pie chart
$branchIndex = [];

// Initialize all branches with 0
foreach ($allBranchesList as $b) {
    if (!empty($b['name'])) {
        $branchIndex[$b['name']] = 0;
    }
}

// Add actual stats
foreach ($allTimeStats as $stat) {
    if (!empty($stat['branch_name'])) {
        $totalPatients += $stat['total_patients'];
        $totalEmergency += $stat['emergency_count'];
        $totalUrgent += $stat['urgent_count'];
        $totalRoutine += $stat['routine_count'];
        $totalPhilHealth += $stat['with_philhealth'];

        $branchIndex[$stat['branch_name']] = $stat['total_patients'];
    }
}

$branchNames = array_keys($branchIndex);
$branchCounts = array_values($branchIndex);

// 2. Get This Month's Trends (System-wide)
$year = $_GET['monthly_trend_year'] ?? date('Y');
$stmt = $pdo->prepare("
    SELECT MONTH(created_at) as month_num, COUNT(*) as count 
    FROM cases 
    WHERE YEAR(created_at) = ? 
    GROUP BY MONTH(created_at) 
    ORDER BY month_num ASC
");
$stmt->execute([$year]);
$monthlyDataRaw = $stmt->fetchAll();

// Fill remaining months with 0
$monthlyTrend = array_fill(1, 12, 0);
foreach ($monthlyDataRaw as $row) {
    $monthlyTrend[(int) $row['month_num']] = (int) $row['count'];
}

$monthsLabel = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

if (isset($_GET['action']) && $_GET['action'] === 'get_monthly_data') {
    header('Content-Type: application/json');
    echo json_encode([
        'labels' => $monthsLabel,
        'data' => array_values($monthlyTrend)
    ]);
    exit;
}
$monthlyTrendData = array_values($monthlyTrend);

// 3. Get This Week's Daily Trends (System-wide)
$stmtDaily = $pdo->prepare("
    SELECT DAYOFWEEK(created_at) as day_num, COUNT(*) as count 
    FROM cases 
    WHERE YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)
    GROUP BY DAYOFWEEK(created_at)
");
$stmtDaily->execute();
$dailyDataRaw = $stmtDaily->fetchAll();

$dailyTrend = array_fill(0, 7, 0);
foreach ($dailyDataRaw as $row) {
    $mysqlDay = (int) $row['day_num'];
    $mappedDay = ($mysqlDay == 1) ? 6 : $mysqlDay - 2;
    $dailyTrend[$mappedDay] = (int) $row['count'];
}
$daysLabel = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

// 4. Calculate Active Branches & Users
$activeBranchesCount = 0;
foreach ($allBranchesList as $b) {
    if (!isset($b['status']) || strtolower($b['status']) === 'active') {
        $activeBranchesCount++;
    }
}

$allStaff = $userModel->getAllStaffUsers();
$activeUsersCount = 0;
foreach ($allStaff as $u) {
    if (isset($u['status']) && strtolower($u['status']) === 'active') {
        $activeUsersCount++;
    }
}

// Pass data to view securely via variables
$dashboardData = [
    'totals' => [
        'patients' => $totalPatients,
        'stat' => $totalEmergency,
        'urgent' => $totalUrgent,
        'routine' => $totalRoutine,
        'philhealth' => $totalPhilHealth,
        'active_branches' => $activeBranchesCount,
        'active_users' => $activeUsersCount
    ],
    'charts' => [
        'branches' => [
            'labels' => $branchNames,
            'data' => $branchCounts
        ],
        'monthly' => [
            'labels' => $monthsLabel,
            'data' => $monthlyTrendData
        ],
        'daily' => [
            'labels' => $daysLabel,
            'data' => $dailyTrend
        ]
    ]
];

if (isset($_GET['ajax_main_filter']) && $_GET['ajax_main_filter'] == '1') {
    header('Content-Type: application/json');
    echo json_encode($dashboardData);
    exit;
}

        return get_defined_vars();
    }
}
