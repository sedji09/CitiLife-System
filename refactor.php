<?php
$file = __DIR__ . '/views/pages/it_admin/dashboard.view.php';
$lines = file($file);

// Extract System Health (lines 121-252, 0-indexed: 120-251)
$systemHealth = array_slice($lines, 120, 252 - 120 + 1);

// Extract Quick Actions (lines 257-290, 0-indexed: 256-289)
$quickActions = array_slice($lines, 256, 290 - 256 + 1);

// Extract Recent Activities (lines 298-338, 0-indexed: 297-337)
$recentActivities = array_slice($lines, 297, 338 - 297 + 1);

// Extract Security Alerts (lines 342-389, 0-indexed: 341-388)
$securityAlerts = array_slice($lines, 341, 389 - 341 + 1);

// Construct new layout
$newLayout = "    <!-- Main Content Layout (Columns) -->\n";
$newLayout .= "    <div class=\"noc-dashboard-row items-stretch\">\n";

$newLayout .= "        <!-- Left Column -->\n";
$newLayout .= "        <div class=\"noc-dashboard-row-main flex flex-col gap-6\">\n";
$newLayout .= implode("", $systemHealth);
$newLayout .= implode("", $recentActivities);
$newLayout .= "        </div>\n";

$newLayout .= "        <!-- Right Column -->\n";
$newLayout .= "        <div class=\"noc-dashboard-row-side flex flex-col gap-6\">\n";
$newLayout .= implode("", $quickActions);
$newLayout .= implode("", $securityAlerts);
$newLayout .= "        </div>\n";
$newLayout .= "    </div>\n";

// Replace lines 115-393 (0-indexed: 114-392)
array_splice($lines, 114, 393 - 114 + 1, $newLayout);

file_put_contents($file, implode("", $lines));
echo "Refactored successfully!";
