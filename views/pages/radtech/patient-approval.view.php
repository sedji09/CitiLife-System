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
        <p class="text-sm text-gray-500 mt-1">Manage approvals and today's examination queue</p>
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
            Pending Approval
        </a>
        <a href="<?= PROJECT_DIR ? '/' . PROJECT_DIR . '/' : '/' ?>index.php?role=radtech&page=patient-lists&tab=disputes"
            class="flex items-center gap-2 px-1 py-3 text-sm font-medium text-gray-500 border-b-2 border-transparent hover:text-gray-700 hover:border-gray-300">
            Patient Error Reports
            <?php if ($pendingDisputeCount > 0): ?>
                <span class="ml-1 tab-circle-badge text-white bg-red-600" style="width: 26px; height: 26px; min-width: 26px; min-height: 26px; border-radius: 9999px; display: inline-flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 700; line-height: 1; flex-shrink: 0;" title="<?= $pendingDisputeCount ?>">
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
                            No pending approvals found.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php 
                    $apprIndex = 0;
                    foreach ($pendingPatients as $patient): 
                        $initialDisplay = ($apprIndex >= 8) ? 'style="display: none;"' : '';
                    ?>
                        <tr class="border-b hover:bg-gray-50 transition-colors record-row" <?= $initialDisplay ?>
                            data-id="<?= htmlspecialchars($patient['request_number']) ?>"
                            data-name="<?= htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']) ?>"
                            data-priority="<?= htmlspecialchars($patient['priority']) ?>"
                            data-exam="<?= htmlspecialchars($patient['exam_type']) ?>"
                            data-date="<?= htmlspecialchars($patient['created_at']) ?>">
                            <?php $apprIndex++; ?>
                            <td class="py-3 px-3 font-mono text-gray-600"><?= htmlspecialchars($patient['request_number']) ?>
                            </td>
                            <td class="py-3 px-3 font-medium truncate max-w-[200px]"
                                title="<?= htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']) ?>">
                                <?= htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']) ?>
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
                                    <?php if (in_array($patient['status'], ['Rejected', 'Cancelled'])): ?>
                                        <button
                                            onclick="openViewModal(<?= $patient['id'] ?>, '<?= htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']) ?>', '<?= htmlspecialchars($patient['birthdate']) ?>', '<?= htmlspecialchars($patient['sex']) ?>', '<?= htmlspecialchars($patient['contact_number']) ?>', '<?= htmlspecialchars($patient['home_address'] ?? '') ?>', '<?= htmlspecialchars($patient['philhealth_status']) ?>', '<?= htmlspecialchars($patient['philhealth_id'] ?? '') ?>', '<?= htmlspecialchars($patient['philhealth_relation'] ?? '') ?>')"
                                            class="text-sm font-medium text-gray-600 hover:text-gray-700 transition" title="View">
                                            <i data-lucide="eye"
                                                class="w-6 h-6 mr-1 bg-gray-100 px-1 py-1 rounded-md border border-gray-300"></i>
                                        </button>
                                    <?php else: ?>
                                        <button
                                            onclick="openEditModal(<?= $patient['id'] ?>, '<?= htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']) ?>', '<?= htmlspecialchars($patient['birthdate']) ?>', '<?= htmlspecialchars($patient['sex']) ?>', '<?= htmlspecialchars($patient['contact_number']) ?>', '<?= htmlspecialchars($patient['home_address'] ?? '') ?>', '<?= htmlspecialchars($patient['philhealth_status']) ?>', '<?= htmlspecialchars($patient['philhealth_id'] ?? '') ?>', '<?= htmlspecialchars($patient['philhealth_relation'] ?? '') ?>')"
                                            class="text-sm font-medium text-blue-600 hover:text-blue-700 transition" title="Edit">
                                            <i data-lucide="edit"
                                                class="w-6 h-6 mr-1 bg-blue-100 px-1 py-1 rounded-md border border-blue-500"></i>
                                        </button>
                                    <?php endif; ?>
                                    
                                    <?php if (in_array($patient['status'], ['Pending Approval', 'Pending Payment'])): ?>
                                        <?php if (isset($patient['is_verified']) && $patient['is_verified'] == 1): ?>
                                            <button onclick="openAssignModal(<?= $patient['id'] ?>, '<?= htmlspecialchars($patient['exam_type']) ?>', '<?= ($patient['status'] === 'Pending Payment') ? htmlspecialchars($patient['exam_type']) : '' ?>')"
                                                class="text-sm font-medium text-indigo-600 hover:text-indigo-700 transition" title="Assign Exam">
                                                <i data-lucide="clipboard-list" class="w-6 h-6 mr-1 bg-indigo-100 px-1 py-1 rounded-md border border-indigo-500"></i>
                                            </button>
                                        <?php else: ?>
                                            <button disabled class="text-sm font-medium text-gray-400 cursor-not-allowed" title="Please Edit and verify patient info first">
                                                <i data-lucide="clipboard-list" class="w-6 h-6 mr-1 bg-gray-100 px-1 py-1 rounded-md border border-gray-300"></i>
                                            </button>
                                        <?php endif; ?>
                                    <?php endif; ?>

                                        <?php if ($patient['status'] === 'Payment Verified'): ?>
                                            <a href="<?= PROJECT_DIR ? '/' . PROJECT_DIR . '/' : '/' ?>index.php?role=radtech&page=patient-approval&action=approve&id=<?= $patient['id'] ?>"
                                                onclick="confirmAction('Confirm Approval', 'Would you like to confirm approving this patient and moving them to Today\'s Queue?', this.href, 'Yes, Proceed', false, event)"
                                                class="text-sm font-medium text-green-600 hover:text-green-700 transition"
                                                title="Approve">
                                                <i data-lucide="circle-check-big"
                                                    class="w-6 h-6 mr-1 bg-green-100 px-1 py-1 rounded-md border border-green-500"></i>
                                            </a>
                                        <?php else: ?>
                                            <button disabled class="text-sm font-medium text-gray-400 cursor-not-allowed" title="Waiting for Payment Verification">
                                                <i data-lucide="circle-check-big" class="w-6 h-6 mr-1 bg-gray-100 px-1 py-1 rounded-md border border-gray-300"></i>
                                            </button>
                                        <?php endif; ?>
                                        
                                    <?php if (!in_array($patient['status'], ['Rejected', 'Cancelled', 'Payment Verified'])): ?>
                                        <a href="<?= PROJECT_DIR ? '/' . PROJECT_DIR . '/' : '/' ?>index.php?role=radtech&page=patient-approval&action=reject&id=<?= $patient['id'] ?>"
                                            onclick="confirmAction('Confirm Rejection', 'Would you like to confirm rejecting this patient registration?', this.href, 'Yes, Proceed', false, event)"
                                            class="text-sm font-medium text-red-600 hover:text-red-700 transition" title="Reject">
                                            <i data-lucide="circle-x"
                                                class="w-6 h-6 mr-1 bg-red-100 px-1 py-1 rounded-md border border-red-500"></i>
                                        </a>
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
    <div class="w-full max-w-md p-6 border shadow-2xl rounded-2xl bg-white">
        <div class="flex items-center gap-3 mb-4 border-b border-gray-100 pb-4">
            <div class="bg-indigo-100 text-indigo-600 p-2.5 rounded-lg border border-indigo-200">
                <i data-lucide="clipboard-list" class="w-6 h-6"></i>
            </div>
            <div>
                <h3 class="text-lg font-bold text-gray-900">Assign Exact Examination</h3>
                <p class="text-sm text-gray-500 mt-0.5">Select procedures for this patient request</p>
            </div>
        </div>
        
        <div class="mb-5 flex flex-col gap-1.5 p-4 bg-red-50 rounded-xl border border-red-100">
            <span class="text-xs font-semibold text-red-800 uppercase tracking-wide flex items-center gap-1.5">
                <i data-lucide="user-check" class="w-4 h-4 text-red-600"></i> Patient requested body part(s):
            </span>
            <span id="assignBodyPart" class="font-bold text-gray-900 text-base"></span>
            <div id="assignAllowedBadge" class="text-xs italic text-red-600 flex items-center gap-1.5 mt-0.5">
                <i data-lucide="info" class="w-3.5 h-3.5 shrink-0"></i>
                <span id="assignAllowedBadgeText">Choices filtered to procedures only</span>
            </div>
        </div>
        
        <form method="POST" id="assignForm" action="" onsubmit="return validateAssignForm(event);">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Select Examination Type</label>
                <?php 
                $examInputName = 'exam_type';
                $placeholderText = 'Select Exam Type...';
                include basePath('views/components/exam-selector.php'); 
                ?>
                <div id="assignExamWarning" class="hidden mt-3 p-3 rounded-xl bg-red-50 border border-red-200 text-red-900 text-xs flex items-start gap-2.5 shadow-sm">
                    <i data-lucide="alert-triangle" class="w-4 h-4 text-red-600 shrink-0 mt-0.5"></i>
                    <div class="flex-1 leading-relaxed" id="assignExamWarningText"></div>
                </div>
                <input type="hidden" name="exam_price" id="assign_exam_price" value="0">
            </div>
            
            <div class="flex justify-end gap-3 mt-6">
                <button type="button" onclick="closeAssignModal()"
                    class="px-4 py-2 bg-gray-500 text-white text-sm font-medium rounded-md hover:bg-gray-600">Cancel</button>
                <button type="submit"
                    class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">Assign & Request Payment</button>
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
                <div>
                    <label class="block text-sm font-medium text-gray-700">PhilHealth Status</label>
                    <select id="modalPhilHealth" onchange="togglePhilHealthId()"
                        class="mt-1 text-sm text-gray-900 bg-gray-50 p-2 rounded w-full" required>
                        <option value="With PhilHealth Card">With PhilHealth Card</option>
                        <option value="Without PhilHealth Card">Without PhilHealth Card</option>
                    </select>
                </div>
                <div id="philHealthIdField" class="hidden">
                    <label class="block text-sm font-medium text-gray-700">PhilHealth ID Number</label>
                    <input type="text" id="modalPhilHealthId" inputmode="numeric" maxlength="14"
                        oninput="formatPhilHealthInput(this); checkModalPhilHealthId();"
                        class="mt-1 text-sm text-gray-900 bg-gray-50 p-2 rounded w-full" placeholder="XX-XXXXXXXXX-X">
                    
                    <div id="modalPhilHealthRelationContainer" class="mt-3">
                        <label class="block text-sm font-medium text-gray-700">Patient's Relation to ID</label>
                        <select id="modalPhilHealthRelation"
                            class="mt-1 text-sm text-gray-900 bg-gray-50 p-2 rounded w-full">
                            <option value="" disabled selected>Select relation</option>
                            <option value="Principal Member" id="modal-opt-owner">Principal Member</option>
                            <option value="Qualified Dependent" id="modal-opt-family">Qualified Dependent</option>
                        </select>
                        <p id="modal-philhealth-status-msg" class="text-xs text-red-600 mt-2 hidden"></p>
                    </div>
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
                todayHighlight: true
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
</script>
