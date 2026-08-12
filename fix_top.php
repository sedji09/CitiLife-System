<?php
$file = 'c:\\xampp\\htdocs\\CitiLife-System\\views\\pages\\radtech\\patient-lists.view.php';
$content = file_get_contents($file);

$startStr = "<?php\n/**\n * Patient Queue";
$endStr = "<!-- Navigation Tabs -->";

$start = strpos($content, $startStr);
$end = strpos($content, $endStr);

if ($start !== false && $end !== false) {
    $newTop = <<<HTML
<?php
/**
 * Patient Queue (Patient List) View
 * Backend logic handled by PatientListsController.php
 */
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../app/Models/ResultDisputeModel.php';

\$disputeModel = new \ResultDisputeModel(\$pdo);
\$branchId = \$_SESSION['branch_id'] ?? 1;
\$disputes = \$disputeModel->getDisputesForClinic(\$branchId, 'radtech');
\$pendingDisputeCount = count(array_filter(\$disputes, function(\$d) { 
    return in_array(\$d['status'], ['Pending RadTech Review', 'Pending RadTech Verification']); 
}));
\$currentTab = \$_GET['tab'] ?? 'completed';
?>
<style>
    html.theme-dark .priority-badge,
    html.theme-dark .status-badge {
        background-color: transparent !important;
    }
</style>

<!-- Header -->
<div class="flex items-center justify-between">
    <div>
        <h2 class="text-xl font-semibold text-gray-900">Patient List</h2>
        <p class="text-sm text-gray-500 mt-1">Manage approvals and active examination queue</p>
    </div>
</div>

<?php if (\$successMsg): ?>
    <div id="flash-success-alert"
        class="mt-4 rounded-xl bg-green-50 border border-green-200 px-4 py-3 flex items-center gap-3 shadow-sm transition-all">
        <i data-lucide="check-circle-2" class="w-5 h-5 text-green-500 shrink-0"></i>
        <p class="text-sm font-bold text-green-800"><?= htmlspecialchars(\$successMsg) ?></p>
    </div>
<?php endif; ?>

<?php if (\$errorMsg): ?>
    <div class="mt-4 rounded-lg bg-red-50 border border-red-300 p-3 flex items-center gap-3">
        <i data-lucide="alert-circle" class="w-5 h-5 text-red-600"></i>
        <p class="text-sm text-red-700 font-medium"><?= htmlspecialchars(\$errorMsg) ?></p>
    </div>
<?php endif; ?>

HTML;
    $content = substr_replace($content, $newTop . "\n<!-- Navigation Tabs -->", $start, $end + strlen($endStr) - $start);
    file_put_contents($file, $content);
    echo "Cleaned up top block.";
} else {
    echo "Could not find bounds.";
}
