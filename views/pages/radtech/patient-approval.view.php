<?php
require_once __DIR__ . '/../../../config/database.php';

$caseModel = new \CaseModel($pdo);
$notificationModel = new \NotificationModel($pdo);
$auditLogModel = new \AuditLogModel($pdo);
$currentUserId = $_SESSION['user_id'] ?? 0;
$branchId = $_SESSION['branch_id'] ?? 1;

$successMsg = $successMsg ?? '';
$errorMsg = $errorMsg ?? '';

// Handle update success/error messages
if (isset($_GET['success']) && $_GET['success'] == 1)
    $successMsg = "Patient information updated successfully.";
if (isset($_GET['error']) && !empty($_GET['error']))
    $errorMsg = htmlspecialchars($_GET['error']);

// 2. Data Fetching (Backend Logic)
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../app/Models/ResultDisputeModel.php';

$branchId = $_SESSION['branch_id'] ?? 1;
$pendingPatients = $caseModel->getPendingCases($branchId);

$disputeModel = new \ResultDisputeModel($pdo);
$disputes = $disputeModel->getDisputesForClinic($branchId, 'radtech');
$pendingDisputeCount = count(array_filter($disputes, function($d) { 
    return in_array($d['status'], ['Pending RadTech Review', 'Pending RadTech Verification']); 
}));

require_once __DIR__ . '/../../../app/Models/ServiceModel.php';
$serviceModel = new \ServiceModel($pdo);
$allServices = $serviceModel->getAllServices();
$groupedServices = [];
foreach ($allServices as $service) {
    if ($service['status'] === 'Active') {
        $groupedServices[$service['category']][] = $service;
    }
}
?>

<!-- Vanilla JS Datepicker -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/vanillajs-datepicker@1.3.4/dist/css/datepicker.min.css">
<script src="https://cdn.jsdelivr.net/npm/vanillajs-datepicker@1.3.4/dist/js/datepicker-full.min.js"></script>
<style>
    html body .datepicker-cell.selected,
    html body .datepicker-cell.selected:hover,
    html body .datepicker-picker .datepicker-cell.selected {
        background-color: #dc2626 !important;
        color: #ffffff !important;
        border-color: #dc2626 !important;
    }

    html body .datepicker-cell.today:not(.selected),
    html body .datepicker-picker .datepicker-cell.today:not(.selected) {
        background-color: #f3f4f6 !important;
        color: #111827 !important;
        font-weight: 600 !important;
        border: 1px solid #d1d5db !important;
    }

    html body .datepicker-cell.today.focused:not(.selected) {
        background-color: #e5e7eb !important;
    }
</style>

<!-- Header -->
<div class="flex items-center justify-between">
    <div>
        <h2 class="text-xl font-semibold text-gray-900">Patient List</h2>
        <p class="text-sm text-gray-500 mt-1">Manage patient requests and today's examination queue</p>
    </div>
</div>

<?php if ($successMsg): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: <?= json_encode($successMsg) ?>,
                    showConfirmButton: false,
                    timer: 2500,
                    customClass: { popup: 'rounded-3xl border-0 shadow-2xl' }
                });
            }
        });
    </script>
<?php endif; ?>

<?php if ($errorMsg): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: <?= json_encode($errorMsg) ?>,
                    customClass: {
                        popup: 'rounded-3xl border-0 shadow-2xl',
                        confirmButton: 'rounded-xl px-8 py-3 font-bold'
                    }
                });
            }
        });
    </script>
<?php endif; ?>

<!-- Navigation Tabs -->
<div class="mt-6 border-b border-gray-200">
    <nav class="flex gap-3">
        <a href="<?= PROJECT_DIR ? '/' . PROJECT_DIR . '/' : '/' ?>index.php?role=radtech&page=patient-lists"
            class="flex items-center gap-2 px-1 py-3 text-sm font-medium text-gray-600 border-b-2 border-transparent hover:text-gray-700 hover:border-gray-300">
            Patient Queue
        </a>
        <a href="<?= PROJECT_DIR ? '/' . PROJECT_DIR . '/' : '/' ?>index.php?role=radtech&page=patient-approval"
            class="flex items-center gap-2 px-1 py-3 text-sm font-medium text-red-600 border-b-2 border-red-600 hover:text-red-700">
            Patient Requests
        </a>
        <a href="<?= PROJECT_DIR ? '/' . PROJECT_DIR . '/' : '/' ?>index.php?role=radtech&page=patient-lists&tab=disputes"
            class="flex items-center gap-2 px-1 py-3 text-sm font-medium text-gray-500 border-b-2 border-transparent hover:text-gray-700 hover:border-gray-300">
            Correction Requests
            <?php if ($pendingDisputeCount > 0): ?>
                <span id="radtech-disputes-tab-badge" class="ml-1 tab-circle-badge bg-red-100 text-red-700 border border-red-200" style="width: 26px; height: 26px; min-width: 26px; min-height: 26px; border-radius: 9999px; display: inline-flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 700; line-height: 1; flex-shrink: 0;" title="<?= $pendingDisputeCount ?>">
                    <?= $pendingDisputeCount > 99 ? '99+' : $pendingDisputeCount ?>
                </span>
            <?php endif; ?>
        </a>
    </nav>
</div>

<div class="mt-6 flex flex-col gap-4">
    <div class="flex gap-4 items-center">
        <input type="text" id="search-input" placeholder="Search by patient name or request number..."
            class="flex-1 rounded-lg border border-input bg-background px-4 py-2 text-sm text-foreground outline-none focus:ring-2 focus:ring-ring">
        <select id="filter-status"
            class="w-48 rounded-lg border border-input bg-background px-4 py-2 text-sm outline-none focus:ring-2 focus:ring-ring">
            <option value="All">All Status</option>
            <option value="Pending Approval">Pending Approval</option>
            <option value="Pending Payment">Pending Payment</option>
            <option value="Payment Verified">Payment Verified</option>
        </select>
        <select id="sort-date"
            class="w-48 rounded-lg border border-input bg-background px-4 py-2 text-sm outline-none focus:ring-2 focus:ring-ring">
            <option>Newest Request</option>
            <option>Oldest Request</option>
        </select>
    </div>
</div>

<div class="rounded-xl border border-gray-300 bg-card stat-card-shadow mt-4 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="sticky top-0 z-10">
                <tr class="border-b border-gray-300 bg-gray-100 text-gray-500">
                    <th class="text-left font-medium px-3 py-3">Request #</th>
                    <th class="text-left font-medium px-3 py-3 truncate max-w-[200px]">Name</th>
                    <th class="text-left font-medium px-3 py-3">Age</th>
                    <th class="text-left font-medium px-3 py-3">Sex</th>
                    <th class="text-left font-medium px-3 py-3 whitespace-nowrap">Date & Time</th>
                    <th class="text-left font-medium px-3 py-3">Status</th>
                    <th class="text-left font-medium px-3 py-3 whitespace-nowrap">Actions</th>
                </tr>
            </thead>
            <tbody id="table-body" class="text-gray-800 bg-white realtime-update">
                <?php if (count($pendingPatients) === 0): ?>
                    <tr>
                        <td colspan="7" class="text-center py-8 text-gray-500">
                            No patient requests found.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php 
                    $apprIndex = 0;
                    foreach ($pendingPatients as $patient): 
                        $patFullName = formatFullName($patient);
                        $initialDisplay = ($apprIndex >= 8) ? 'style="display: none;"' : '';
                    ?>
                        <tr class="border-b hover:bg-gray-50 transition-colors record-row" <?= $initialDisplay ?>
                            data-id="<?= htmlspecialchars($patient['request_number']) ?>"
                            data-name="<?= htmlspecialchars($patFullName) ?>"
                            data-priority="<?= htmlspecialchars($patient['priority']) ?>"
                            data-exam="<?= htmlspecialchars($patient['exam_type']) ?>"
                            data-date="<?= htmlspecialchars($patient['created_at']) ?>">
                            <?php $apprIndex++; ?>
                            <td class="py-3 px-3 font-medium text-gray-900"><?= htmlspecialchars($patient['request_number']) ?>
                            </td>
                            <td class="py-3 px-3 font-medium truncate max-w-[200px]"
                                title="<?= htmlspecialchars($patFullName) ?>">
                                <?= htmlspecialchars($patFullName) ?>
                            </td>
                            <td class="py-3 px-3"><?= htmlspecialchars($patient['age']) ?></td>
                            <td class="py-3 px-3"><?= htmlspecialchars($patient['sex']) ?></td>
                            <td class="py-3 px-3 text-gray-500 text-xs whitespace-nowrap">
                                <?= date('M d, Y h:i A', strtotime($patient['created_at'])) ?>
                            </td>
                            <td class="py-3 px-3">
                                <?php if ($patient['status'] === 'Rejected'): ?>
                                    <span
                                        class="inline-flex items-center rounded-full border border-red-400 bg-red-50 px-2.5 py-1 text-xs font-semibold text-red-700">
                                        Rejected
                                    </span>
                                <?php elseif ($patient['status'] === 'Cancelled'): ?>
                                    <span
                                        class="inline-flex items-center rounded-full border border-gray-400 bg-gray-50 px-2.5 py-1 text-xs font-semibold text-gray-700">
                                        Cancelled
                                    </span>
                                <?php elseif ($patient['status'] === 'Pending Payment'): ?>
                                    <span
                                        class="inline-flex items-center rounded-full border border-orange-400 bg-orange-50 px-2.5 py-1 text-xs font-semibold text-orange-700">
                                        Pending Payment
                                    </span>
                                <?php elseif ($patient['status'] === 'Payment Verifying'): ?>
                                    <span
                                        class="inline-flex items-center rounded-full border border-blue-400 bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700">
                                        Payment Verifying
                                    </span>
                                <?php elseif ($patient['status'] === 'Payment Verified'): ?>
                                    <span
                                        class="inline-flex items-center rounded-full border border-green-400 bg-green-50 px-2.5 py-1 text-xs font-semibold text-green-700">
                                        Payment Verified
                                    </span>
                                <?php else: ?>
                                    <span
                                        class="inline-flex items-center rounded-full border border-yellow-400 bg-yellow-50 px-2.5 py-1 text-xs font-semibold text-yellow-700">
                                        Pending Approval
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="py-3 px-3 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    <button type="button"
                                        onclick="openViewModal(<?= $patient['id'] ?>, '<?= htmlspecialchars($patFullName) ?>', '<?= htmlspecialchars($patient['birthdate']) ?>', '<?= htmlspecialchars($patient['sex']) ?>', '<?= htmlspecialchars($patient['contact_number']) ?>', '<?= htmlspecialchars($patient['home_address'] ?? '') ?>', '<?= htmlspecialchars($patient['philhealth_status']) ?>', '<?= htmlspecialchars($patient['philhealth_id'] ?? '') ?>', '<?= htmlspecialchars($patient['philhealth_relation'] ?? '') ?>')"
                                        class="text-sm font-medium text-blue-500 hover:text-blue-700 transition cursor-pointer" title="View Patient Details">
                                        <i data-lucide="eye"
                                            class="w-6 h-6 mr-1 bg-blue-100 text-blue-500 px-1 py-1 rounded-md border border-blue-500"></i>
                                    </button>
                                    
                                    <?php if ($patient['status'] === 'Pending Approval' || $patient['status'] === 'Pending'): ?>
                                        <button type="button" onclick="openAssignModal(<?= $patient['id'] ?>, '<?= htmlspecialchars($patient['exam_type'] ?? '', ENT_QUOTES) ?>', '', '<?= htmlspecialchars($patient['philhealth_status'] ?? '', ENT_QUOTES) ?>', '<?= htmlspecialchars($patient['philhealth_id'] ?? '', ENT_QUOTES) ?>', '<?= htmlspecialchars($patient['philhealth_relation'] ?? '', ENT_QUOTES) ?>', <?= (int)$patient['patient_id'] ?>, '<?= htmlspecialchars($patFullName, ENT_QUOTES) ?>', false)"
                                            class="text-sm font-medium text-indigo-600 hover:text-indigo-700 transition cursor-pointer" title="Assign Examination">
                                            <i data-lucide="clipboard-list" class="w-6 h-6 mr-1 bg-indigo-100 px-1 py-1 rounded-md border border-indigo-500"></i>
                                        </button>
                                    <?php elseif (in_array($patient['status'], ['Pending Payment', 'Payment Verifying', 'Payment Verified'])): ?>
                                        <button type="button" onclick="openAssignModal(<?= $patient['id'] ?>, '<?= htmlspecialchars($patient['exam_type'] ?? '', ENT_QUOTES) ?>', '<?= htmlspecialchars($patient['exam_type'] ?? '', ENT_QUOTES) ?>', '<?= htmlspecialchars($patient['philhealth_status'] ?? '', ENT_QUOTES) ?>', '<?= htmlspecialchars($patient['philhealth_id'] ?? '', ENT_QUOTES) ?>', '<?= htmlspecialchars($patient['philhealth_relation'] ?? '', ENT_QUOTES) ?>', <?= (int)$patient['patient_id'] ?>, '<?= htmlspecialchars($patFullName, ENT_QUOTES) ?>', true)"
                                            class="text-sm font-medium text-gray-500 hover:text-gray-700 transition cursor-pointer" title="View Assigned Examination Details (Read-Only)">
                                            <i data-lucide="clipboard-list" class="w-6 h-6 mr-1 bg-gray-100 text-gray-400 hover:bg-gray-200 hover:text-gray-600 px-1 py-1 rounded-md border border-gray-300"></i>
                                        </button>
                                    <?php endif; ?>

                                    <?php if ($patient['status'] === 'Pending Approval' || $patient['status'] === 'Pending'): ?>
                                        <button type="button"
                                            onclick="promptRejectRequest(<?= (int)$patient['id'] ?>, '<?= htmlspecialchars($patient['request_number'] ?? ('REQ-' . str_pad($patient['id'], 5, '0', STR_PAD_LEFT)), ENT_QUOTES) ?>', '<?= htmlspecialchars($patFullName, ENT_QUOTES) ?>')"
                                            class="text-sm font-medium text-red-600 hover:text-red-700 transition cursor-pointer" title="Reject Request">
                                            <i data-lucide="circle-x"
                                                class="w-6 h-6 mr-1 bg-red-100 px-1 py-1 rounded-md border border-red-500"></i>
                                        </button>
                                    <?php elseif (in_array($patient['status'], ['Pending Payment', 'Payment Verifying', 'Payment Verified'])): ?>
                                        <button disabled
                                            class="text-sm font-medium text-gray-400 cursor-not-allowed opacity-60" title="Cannot reject request in payment status">
                                            <i data-lucide="circle-x"
                                                class="w-6 h-6 mr-1 bg-gray-100 text-gray-400 px-1 py-1 rounded-md border border-gray-300"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Pagination Controls -->
    <div class="flex flex-col sm:flex-row items-center justify-between border-t border-gray-200 bg-gray-50 px-6 py-4 gap-4" id="approval-pagination-container" style="display: flex;">
        <span id="approval-record-count" class="text-xs text-gray-500 font-medium">
            Showing <span id="approval-start">0</span> to <span id="approval-end">0</span> of <span id="approval-total" class="font-semibold text-gray-800">0</span> records
        </span>
        <div class="flex items-center flex-wrap gap-1.5" id="approval-pagination-controls">
        </div>
    </div>
</div>

<!-- Assign Exam Modal -->
<div id="assignModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50 hidden p-4">
    <div class="w-full max-w-lg max-h-[90vh] overflow-y-auto p-6 border shadow-2xl rounded-2xl bg-white">
        <div class="flex items-center gap-3 mb-4 border-b border-gray-100 pb-4">
            <div id="assignModalHeaderIcon" class="bg-indigo-100 text-indigo-600 p-2.5 rounded-lg border border-indigo-200">
                <i data-lucide="clipboard-list" class="w-6 h-6"></i>
            </div>
            <div>
                <h3 id="assignModalTitle" class="text-lg font-bold text-gray-900">Assign Examination</h3>
                <p id="assignModalSubtitle" class="text-xs text-gray-500 mt-0.5">Select procedure(s) and specify PhilHealth coverage</p>
            </div>
        </div>
        
        <div class="mb-4 flex flex-col gap-1 p-3 bg-red-50 rounded-xl border border-red-100">
            <span class="text-xs font-semibold text-red-800 uppercase tracking-wide flex items-center gap-1.5">
                <i data-lucide="user-check" class="w-4 h-4 text-red-600"></i> Patient requested body part(s):
            </span>
            <span id="assignBodyPart" class="font-bold text-gray-900 text-sm"></span>
        </div>
        
        <form method="POST" id="assignForm" action="" onsubmit="return validateAssignForm(event);" class="space-y-4">
            <!-- Exam Selector -->
            <div>
                <label id="assignExamLabel" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Select Examination Procedure(s) <span class="text-red-500" id="assignExamRequiredAsterisk">*</span></label>
                <?php 
                $examInputName = 'exam_type';
                $placeholderText = 'Select procedure(s)...';
                include basePath('views/components/exam-selector.php'); 
                ?>
                <div class="space-y-3 mt-3">
                    <div id="assignAllowedBadge" class="hidden text-xs text-indigo-700 flex items-center gap-2 bg-indigo-50/90 border border-indigo-100 p-2.5 rounded-xl shadow-2xs">
                        <i data-lucide="info" class="w-4 h-4 shrink-0 text-indigo-600"></i>
                        <span id="assignAllowedBadgeText">Choices filtered to requested body part(s)</span>
                    </div>
                    <div id="assignExamWarning" class="hidden p-3 rounded-xl bg-amber-50/90 border border-amber-200 text-amber-900 text-xs flex items-start gap-2.5 shadow-2xs">
                        <i data-lucide="alert-circle" class="w-4 h-4 text-amber-600 shrink-0 mt-0.5"></i>
                        <div class="flex-1 leading-relaxed" id="assignExamWarningText"></div>
                    </div>
                </div>
            </div>

            <!-- PhilHealth Coverage Section -->
            <div class="pt-3.5 border-t border-gray-100 space-y-3">
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider">PhilHealth Coverage</label>
                
                <div class="grid grid-cols-2 gap-3">
                    <label class="flex items-center gap-2.5 px-3.5 py-2.5 rounded-xl border border-gray-200 bg-gray-50/60 hover:bg-white cursor-pointer transition shadow-2xs has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50/70 has-[:checked]:text-blue-950 has-[:checked]:ring-1 has-[:checked]:ring-blue-500/30">
                        <input type="radio" name="philhealth_status" value="Without PhilHealth Card" id="assign_ph_without" onchange="toggleAssignPhilHealth(false)" checked class="w-4 h-4 text-blue-600 focus:ring-blue-500">
                        <span class="text-xs font-semibold">Without PhilHealth</span>
                    </label>
                    <label class="flex items-center gap-2.5 px-3.5 py-2.5 rounded-xl border border-gray-200 bg-gray-50/60 hover:bg-white cursor-pointer transition shadow-2xs has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50/70 has-[:checked]:text-blue-950 has-[:checked]:ring-1 has-[:checked]:ring-blue-500/30">
                        <input type="radio" name="philhealth_status" value="With PhilHealth Card" id="assign_ph_with" onchange="toggleAssignPhilHealth(true)" class="w-4 h-4 text-blue-600 focus:ring-blue-500">
                        <span class="text-xs font-semibold">With PhilHealth Card</span>
                    </label>
                </div>

                <!-- Conditional PhilHealth Details Box -->
                <div id="assign_philhealth_details" class="hidden p-4 sm:p-5 bg-blue-50/50 border border-blue-200/80 rounded-2xl space-y-3.5 shadow-2xs">
                    <div>
                        <label for="assign_philhealth_id" class="block text-xs font-bold text-blue-950 uppercase tracking-wider mb-1.5">PhilHealth ID Number <span class="text-red-500">*</span></label>
                        <input type="text" name="philhealth_id" id="assign_philhealth_id" inputmode="numeric" maxlength="14"
                            data-label="PhilHealth ID Number"
                            oninput="formatPhilHealthInput(this); checkAssignPhilHealthDup(); recalculateAssignPricing();"
                            placeholder="XX-XXXXXXXXX-X"
                            class="w-full text-sm font-mono text-gray-900 bg-white border border-blue-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200/70 rounded-xl px-3.5 py-2.5 outline-none transition shadow-2xs">
                    </div>
                    <div>
                        <label for="assign_philhealth_relation" class="block text-xs font-bold text-blue-950 uppercase tracking-wider mb-1.5">Patient's Relation to ID <span class="text-red-500">*</span></label>
                        <select name="philhealth_relation" id="assign_philhealth_relation" onchange="if (this.value && window.FormValidator) window.FormValidator.clearError(this); checkAssignPhilHealthDup(); recalculateAssignPricing();"
                            data-label="Patient's Relation to ID"
                            class="w-full text-sm text-gray-900 bg-white border border-blue-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200/70 rounded-xl px-3.5 py-2.5 outline-none transition shadow-2xs">
                            <option value="">-- Select relation --</option>
                            <option value="Principal Member" id="assign-opt-owner">Principal Member</option>
                            <option value="Qualified Dependent" id="assign-opt-family">Qualified Dependent</option>
                        </select>
                        <div id="assign-philhealth-msg" class="mt-2 hidden"></div>
                    </div>
                </div>
            </div>

            <input type="hidden" name="exam_price" id="assign_exam_price" value="0">
            
            <div class="flex justify-end gap-2.5 pt-3 border-t border-gray-100">
                <button type="button" id="assignCancelBtn" onclick="closeAssignModal()"
                    class="px-4 py-2 bg-gray-100 text-gray-700 text-xs font-semibold rounded-xl hover:bg-gray-200 transition cursor-pointer">Cancel</button>
                <button type="submit" id="assignSubmitBtn"
                    class="px-5 py-2 bg-indigo-600 text-white text-xs font-semibold rounded-xl hover:bg-indigo-700 transition shadow-md shadow-indigo-600/20 cursor-pointer">Assign Examination</button>
            </div>
        </form>
    </div>
</div>

<!-- Custom Confirm Modal -->


<!-- Edit Modal -->
<div id="editModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50 hidden p-4">
    <div class="w-full max-w-xl p-8 border shadow-xl rounded-2xl bg-white">
        <div class="mt-1">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Patient Information</h3>
            <div class="space-y-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Patient Name</label>
                    <input type="text" id="modalName" class="mt-1 text-sm text-gray-900 bg-gray-50 p-2 rounded w-full"
                        required>
                </div>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Birthdate</label>
                        <div class="relative mt-1">
                            <input type="text" id="modalBirthdate" readonly placeholder="Select birthdate"
                                class="text-sm text-gray-900 bg-gray-50 p-2 pr-8 rounded w-full border border-gray-200"
                                required>
                            <i data-lucide="calendar"
                                class="absolute right-2 top-2.5 w-4 h-4 text-gray-400 pointer-events-none"></i>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Sex</label>
                        <select type="text" id="modalSex"
                            class="mt-1 text-sm text-gray-900 bg-gray-50 p-2 rounded w-full" required>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Contact Number</label>
                    <input type="tel" id="modalContact"
                        class="mt-1 text-sm text-gray-900 bg-gray-50 p-2 rounded w-full border border-gray-200" required
                        maxlength="11" pattern="09[0-9]{9}"
                        oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 11);"
                        placeholder="09XXXXXXXXX">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Home Address</label>
                    <input type="text" id="modalAddress"
                        class="mt-1 text-sm text-gray-900 bg-gray-50 p-2 rounded w-full">
                </div>
            </div>
            <div class="flex justify-end gap-3 mt-4">
                <button onclick="closeEditModal()" id="modalCancelBtn"
                    class="px-4 py-2 bg-gray-500 text-white text-sm font-medium rounded-md hover:bg-gray-600">Cancel</button>
                <button onclick="saveEditModal()" type="button" id="modalOkBtn"
                    class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700">OK</button>
            </div>
        </div>
    </div>
</div>

<script>
    // ── Exam Category Mapping for Validation & Filtering ─────────────────────
    <?php
    $activeServicesList = $serviceModel->getActiveServices();
    $examCategoryMap = [];
    $servicesByCategory = [];
    foreach ($activeServicesList as $srv) {
        $examCategoryMap[$srv['exam_type']] = $srv['category'];
        $servicesByCategory[$srv['category']][] = $srv['exam_type'];
    }

    // Comprehensive body part to category alias mapping
    $bodyPartAliases = [
        'head' => ['Skull', 'Head'],
        'skull' => ['Skull', 'Head'],
        'face / nose' => ['Skull', 'Head', 'Facial'],
        'jaw' => ['Skull', 'Head', 'Mandible'],
        'chest' => ['Chest', 'Thorax', 'Lungs'],
        'abdomen' => ['Abdomen', 'Stomach'],
        'abdomen / stomach' => ['Abdomen', 'Stomach'],
        'spine' => ['Spine'],
        'neck' => ['Neck', 'Spine', 'Cervical'],
        'upper back' => ['Spine', 'Thoracic'],
        'lower back' => ['Spine', 'Lumbar'],
        'back' => ['Spine'],
        'upper extremities' => ['Upper Extremities', 'Arm'],
        'arm' => ['Upper Extremities', 'Arm'],
        'upper arm' => ['Upper Extremities', 'Arm'],
        'elbow' => ['Upper Extremities', 'Elbow'],
        'forearm' => ['Upper Extremities', 'Forearm'],
        'hand / wrist' => ['Upper Extremities', 'Hand', 'Wrist'],
        'hand' => ['Upper Extremities', 'Hand'],
        'wrist' => ['Upper Extremities', 'Wrist'],
        'shoulder' => ['Upper Extremities', 'Shoulder'],
        'lower extremities' => ['Lower Extremities', 'Leg'],
        'pelvis / hip' => ['Pelvis', 'Lower Extremities'],
        'pelvis' => ['Pelvis', 'Lower Extremities'],
        'hip' => ['Pelvis', 'Lower Extremities'],
        'thigh' => ['Lower Extremities', 'Femur'],
        'knee' => ['Lower Extremities', 'Knee'],
        'lower leg' => ['Lower Extremities', 'Leg'],
        'leg' => ['Lower Extremities', 'Leg'],
        'ankle' => ['Lower Extremities', 'Ankle'],
        'foot' => ['Lower Extremities', 'Foot']
    ];
    ?>
    window.examCategoryMap = <?= json_encode($examCategoryMap) ?>;
    window.servicesByCategory = <?= json_encode($servicesByCategory) ?>;
    window.allActiveServices = <?= json_encode($activeServicesList) ?>;
    window.bodyPartAliases = <?= json_encode($bodyPartAliases) ?>;
</script>

<script
    src="<?= PROJECT_DIR ? '/' . PROJECT_DIR . '/' : '/' ?>views/pages/radtech/patient-approval.js?v=<?= filemtime(__DIR__ . '/patient-approval.js') ?>"></script>

<script>
    // ── Vanilla JS Datepicker init ─────────────────────────────────────────────
    let modalDatePicker = null;
    document.addEventListener('DOMContentLoaded', () => {
        if (window.lucide) window.lucide.createIcons();
        const modalBirthdateInput = document.getElementById('modalBirthdate');
        if (modalBirthdateInput) {
            modalDatePicker = new Datepicker(modalBirthdateInput, {
                autohide: true,
                format: 'yyyy-mm-dd',
                todayHighlight: true,
                maxDate: new Date()
            });
        }
    });

    // ── Highlight row from notification ───────────────────────────────────────────
    document.addEventListener('DOMContentLoaded', () => {
        const params = new window.URLSearchParams(window.location.search);
        const highlightId = params.get('highlight');
        if (!highlightId) return;

        // Clean up URL to prevent polling duplication
        try {
            const cleanUrl = new URL(window.location.href);
            cleanUrl.searchParams.delete('highlight');
            window.history.replaceState({}, document.title, cleanUrl.toString());
            if (window.__APP__) {
                window.__APP__.currentPath = cleanUrl.pathname + cleanUrl.search;
            }
        } catch (e) {}

        setTimeout(() => {
            const rows = document.querySelectorAll('#table-body tr.record-row');
            let targetRow = null;
            rows.forEach(row => {
                if ((row.dataset.id || '').toLowerCase() === highlightId.toLowerCase()) {
                    targetRow = row;
                }
            });

            if (targetRow) {
                // Scroll table container to the row
                const tableWrapper = targetRow.closest('.overflow-y-auto');
                if (tableWrapper) {
                    const rowTop = targetRow.offsetTop - tableWrapper.offsetTop;
                    tableWrapper.scrollTo({ top: rowTop - 40, behavior: 'smooth' });
                } else {
                    targetRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }

                // Flash highlight animation
                targetRow.style.transition = 'background-color 0.4s ease';
                targetRow.style.backgroundColor = '#fef08a';
                setTimeout(() => {
                    targetRow.style.backgroundColor = '#fde047';
                    setTimeout(() => {
                        targetRow.style.backgroundColor = '#fef08a';
                        setTimeout(() => {
                            targetRow.style.backgroundColor = '#fde047';
                            setTimeout(() => {
                                targetRow.style.transition = 'background-color 1.5s ease';
                                targetRow.style.backgroundColor = '';
                            }, 300);
                        }, 300);
                    }, 300);
                }, 200);

                // Remove existing banner if present
                const existingBanner = document.getElementById('highlight-banner');
                if (existingBanner) existingBanner.remove();

                // Info banner
                const banner = document.createElement('div');
                banner.id = 'highlight-banner';
                banner.innerHTML = `<div style="display:flex;align-items:center;gap:0.5rem;"><svg xmlns='http://www.w3.org/2000/svg' width='18' height='18' fill='none' stroke='currentColor' stroke-width='2' viewBox='0 0 24 24'><circle cx='12' cy='12' r='10'/><line x1='12' y1='8' x2='12' y2='12'/><line x1='12' y1='16' x2='12.01' y2='16'/></svg><span>Navigated from notification — Case <strong>${highlightId}</strong> is highlighted below.</span></div>`;
                banner.style.cssText = 'margin-left:auto;padding:0.75rem 1rem;border-radius:0.75rem;background:#fefce8;border:1px solid #fde047;color:#854d0e;font-size:0.875rem;font-weight:500;display:flex;align-items:center;gap:0.5rem;';
                const header = document.querySelector('h2');
                if (header && header.parentElement) {
                    header.parentElement.insertAdjacentElement('afterend', banner);
                }
                setTimeout(() => {
                    banner.style.transition = 'opacity 0.5s';
                    banner.style.opacity = '0';
                    setTimeout(() => banner.remove(), 500);
                }, 6000);
            }
        }, 150);
    });

    // ── Reject Request with SweetAlert modal ──────────────────────────────────────
    function escapeHtmlApproval(text) {
        if (!text) return '';
        return String(text).replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
    }

    window.promptRejectRequest = function(requestId, reqNumber, patientName) {
        if (typeof Swal === 'undefined') {
            if (confirm('Reject request ' + reqNumber + ' for ' + patientName + '?')) {
                const reason = prompt('Please enter the reason for rejection:');
                if (reason && reason.trim()) {
                    submitRejectRequest(requestId, reason.trim());
                }
            }
            return;
        }

        Swal.fire({
            icon: 'warning',
            title: 'Reject X-ray Request',
            html: `
                <div class="text-left">
                    <p class="text-sm text-gray-600 mb-3 leading-relaxed">
                        Are you sure you want to reject request <strong class="text-gray-900 font-mono">${escapeHtmlApproval(reqNumber)}</strong> for <strong class="text-gray-900">${escapeHtmlApproval(patientName)}</strong>? The patient will receive a notification along with your reason.
                    </p>
                    <div class="mb-3">
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Quick Select Reason:</label>
                        <div class="flex flex-wrap gap-1.5">
                            <button type="button" class="text-xs px-2.5 py-1 rounded-lg border border-red-200 bg-red-50 hover:bg-red-100 text-red-700 font-medium transition cursor-pointer" onclick="window.setRadRejectReason('Incomplete patient information / demographic details provided.')">Incomplete Info</button>
                            <button type="button" class="text-xs px-2.5 py-1 rounded-lg border border-red-200 bg-red-50 hover:bg-red-100 text-red-700 font-medium transition cursor-pointer" onclick="window.setRadRejectReason('Missing or unclear doctor referral / prescription for X-ray.')">Missing Doctor Referral</button>
                            <button type="button" class="text-xs px-2.5 py-1 rounded-lg border border-red-200 bg-red-50 hover:bg-red-100 text-red-700 font-medium transition cursor-pointer" onclick="window.setRadRejectReason('Requested X-ray procedure is currently unavailable at this branch.')">Procedure Unavailable</button>
                            <button type="button" class="text-xs px-2.5 py-1 rounded-lg border border-red-200 bg-red-50 hover:bg-red-100 text-red-700 font-medium transition cursor-pointer" onclick="window.setRadRejectReason('Duplicate examination request submitted.')">Duplicate Request</button>
                            <button type="button" class="text-xs px-2.5 py-1 rounded-lg border border-red-200 bg-red-50 hover:bg-red-100 text-red-700 font-medium transition cursor-pointer" onclick="window.setRadRejectReason('Clinical indication / symptoms do not correspond to the requested X-ray exam.')">Indication Mismatch</button>
                        </div>
                    </div>
                    <div>
                        <label for="swal-rad-rejection-reason" class="block text-xs font-bold text-gray-700 tracking-wider mb-1">Reason for Rejection <span class="text-red-500">*</span></label>
                        <textarea id="swal-rad-rejection-reason" rows="3" class="w-full border border-gray-300 rounded-xl p-2.5 text-sm focus:border-red-500 focus:outline-none outline-none text-gray-800 transition" placeholder="State why this request is being rejected so the patient understands..."></textarea>
                    </div>
                </div>
            `,
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Confirm Rejection',
            cancelButtonText: 'Cancel',
            focusConfirm: false,
            customClass: {
                popup: 'rounded-2xl text-left'
            },
            didOpen: () => {
                window.setRadRejectReason = function(reason) {
                    const textarea = document.getElementById('swal-rad-rejection-reason');
                    if (textarea) {
                        textarea.value = reason;
                        textarea.focus();
                    }
                };
                const textarea = document.getElementById('swal-rad-rejection-reason');
                if (textarea) textarea.focus();
            },
            preConfirm: () => {
                const reason = (document.getElementById('swal-rad-rejection-reason')?.value || '').trim();
                if (!reason) {
                    Swal.showValidationMessage('Please provide a reason for rejecting this request.');
                    return false;
                }
                return reason;
            }
        }).then((result) => {
            if (result.isConfirmed && result.value) {
                submitRejectRequest(requestId, result.value);
            }
        });
    };

    function submitRejectRequest(requestId, reason) {
        const form = document.createElement('form');
        form.method = 'POST';
        const projectBase = '<?= (defined('PROJECT_DIR') && PROJECT_DIR) ? '/' . PROJECT_DIR . '/' : '/' ?>';
        form.action = projectBase + 'index.php?role=radtech&page=patient-approval&action=reject&id=' + encodeURIComponent(requestId);

        const reasonInput = document.createElement('input');
        reasonInput.type = 'hidden';
        reasonInput.name = 'rejection_reason';
        reasonInput.value = reason;
        form.appendChild(reasonInput);

        document.body.appendChild(form);
        form.submit();
    }
</script>
