<?php
require_once __DIR__ . '/../../../config/database.php';

$caseModel = new \CaseModel($pdo);
$notificationModel = new \NotificationModel($pdo);
$auditLogModel = new \AuditLogModel($pdo);
$currentUserId = $_SESSION['user_id'] ?? 0;
$branchId = $_SESSION['branch_id'] ?? 1;

$successMsg = '';
$errorMsg = '';

// 1. Handle Actions (Backend Logic)
if (isset($_GET['action']) && isset($_GET['id']) && !isset($_GET['ajax_polling'])) {
    try {
        $id = (int) $_GET['id'];
        $action = $_GET['action'];
        $result = $caseModel->processCaseApproval($id, $action, $notificationModel);

        if ($result['success']) {
            $successMsg = $result['message'];

            // Log the action
            $stmtReq = $pdo->prepare("SELECT r.request_number, p.first_name, p.last_name FROM requests r JOIN patients p ON r.patient_id = p.id WHERE r.id = ?");
            $stmtReq->execute([$id]);
            $reqData = $stmtReq->fetch();
            $patientName = $reqData ? ($reqData['first_name'] . ' ' . $reqData['last_name']) : "Unknown";
            $requestNum = $reqData ? $reqData['request_number'] : $id;

            if ($action === 'approve') {
                $logAction = "Approved patient registration";
                $details = "Patient: $patientName, Request: $requestNum";
                $auditLogModel->addLog($currentUserId, $logAction, 'Patient Approvals', 'Request', $id, $details, $branchId);
                
                // Redirect straight to patient-details for immediate image upload
                $_SESSION['flash_success'] = "Patient request approved. You can now upload diagnostic images.";
                echo "<script>window.location.href = '/" . PROJECT_DIR . "/index.php?role=radtech&page=patient-details&id=" . urlencode($result['case_id']) . "';</script>";
                exit;
            } else {
                $logAction = "Rejected X-ray request";
                $details = "Request Number: $requestNum";
                $auditLogModel->addLog($currentUserId, $logAction, 'Patient Approvals', 'Request', $id, $details, $branchId);
            }
        } else {
            $errorMsg = $result['message'];
        }
    } catch (Exception $e) {
        $errorMsg = "Action failed: " . $e->getMessage();
    }
}

// Handle update success/error messages
if (isset($_GET['success']) && $_GET['success'] == 1)
    $successMsg = "Patient information updated successfully.";
if (isset($_GET['error']) && $_GET['error'] == 1)
    $errorMsg = "Failed to update patient information.";

// 2. Data Fetching (Backend Logic)
$branchId = $_SESSION['branch_id'] ?? 1;
$pendingPatients = $caseModel->getPendingCases($branchId);
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
    <div class="mt-4 rounded-lg bg-green-50 border border-green-300 p-3 flex items-center gap-3">
        <i data-lucide="check-circle" class="w-5 h-5 text-green-600"></i>
        <p class="text-sm text-green-800 font-medium"><?= htmlspecialchars($successMsg) ?></p>
    </div>
<?php endif; ?>

<?php if ($errorMsg): ?>
    <div class="mt-4 rounded-lg bg-red-50 border border-red-300 p-3 flex items-center gap-3">
        <i data-lucide="x-circle" class="w-5 h-5 text-red-600"></i>
        <p class="text-sm text-red-800 font-medium"><?= htmlspecialchars($errorMsg) ?></p>
    </div>
<?php endif; ?>

<!-- Navigation Tabs -->
<div class="mt-6 border-b border-gray-200">
    <nav class="flex gap-3">
        <a href="/<?= PROJECT_DIR ?>/index.php?role=radtech&page=patient-lists"
            class="flex items-center gap-2 px-1 py-3 text-sm font-medium <?php echo ($_GET['page'] ?? 'patient-lists') === 'patient-lists' ? 'text-red-600 border-b-2 border-red-600 hover:text-red-700' : 'text-gray-500 border-b-2 border-transparent hover:text-gray-700 hover:border-gray-300'; ?>">
            Patient Queue
        </a>
        <a href="/<?= PROJECT_DIR ?>/index.php?role=radtech&page=patient-approval"
            class="flex items-center gap-2 px-1 py-3 text-sm font-medium <?php echo ($_GET['page'] ?? 'patient-lists') === 'patient-approval' ? 'text-red-500 border-b-2 border-red-600 hover:text-red-700' : 'text-gray-600 border-b-2 border-transparent hover:text-gray-700 hover:border-gray-300'; ?>">
            Pending Approval
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
            <option value="Rejected">Rejected</option>
        </select>
        <select id="sort-date"
            class="w-48 rounded-lg border border-input bg-background px-4 py-2 text-sm outline-none focus:ring-2 focus:ring-ring">
            <option>Newest Request</option>
            <option>Oldest Request</option>
        </select>
    </div>
</div>

<div class="rounded-xl border border-gray-300 bg-card stat-card-shadow mt-4 overflow-hidden">
    <div class="overflow-x-auto overflow-y-auto max-h-[400px]">
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
                        <td colspan="6" class="text-center py-8 text-gray-500">
                            No pending approvals found.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($pendingPatients as $patient): ?>
                        <tr class="border-b hover:bg-gray-50 transition-colors record-row"
                            data-id="<?= htmlspecialchars($patient['request_number']) ?>"
                            data-name="<?= htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']) ?>"
                            data-priority="<?= htmlspecialchars($patient['priority']) ?>"
                            data-exam="<?= htmlspecialchars($patient['exam_type']) ?>"
                            data-date="<?= htmlspecialchars($patient['created_at']) ?>">
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
                                <?php else: ?>
                                    <span
                                        class="inline-flex items-center rounded-full border border-yellow-400 bg-yellow-50 px-2.5 py-1 text-xs font-semibold text-yellow-700">
                                        Pending Approval
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="py-3 px-3 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    <?php if ($patient['status'] !== 'Rejected'): ?>
                                        <a href="/<?= PROJECT_DIR ?>/index.php?role=radtech&page=patient-approval&action=approve&id=<?= $patient['id'] ?>"
                                            onclick="confirmAction('Confirm Approval', 'Would you like to confirm approving this patient and moving them to Today\'s Queue?', this.href, 'Yes, Proceed', false, event)"
                                            class="text-sm font-medium text-green-600 hover:text-green-700 transition"
                                            title="Approve">
                                            <i data-lucide="circle-check-big"
                                                class="w-6 h-6 mr-1 bg-green-100 px-1 py-1 rounded-md border border-green-500"></i>
                                        </a>
                                    <?php endif; ?>
                                    <?php if ($patient['status'] === 'Rejected'): ?>
                                        <button
                                            onclick="openViewModal(<?= $patient['id'] ?>, '<?= htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']) ?>', '<?= htmlspecialchars($patient['birthdate']) ?>', '<?= htmlspecialchars($patient['sex']) ?>', '<?= htmlspecialchars($patient['contact_number']) ?>', '<?= htmlspecialchars($patient['home_address'] ?? '') ?>', '<?= htmlspecialchars($patient['philhealth_status']) ?>', '<?= htmlspecialchars($patient['philhealth_id'] ?? '') ?>')"
                                            class="text-sm font-medium text-gray-600 hover:text-gray-700 transition" title="View">
                                            <i data-lucide="eye"
                                                class="w-6 h-6 mr-1 bg-gray-100 px-1 py-1 rounded-md border border-gray-300"></i>
                                        </button>
                                    <?php else: ?>
                                        <button
                                            onclick="openEditModal(<?= $patient['id'] ?>, '<?= htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']) ?>', '<?= htmlspecialchars($patient['birthdate']) ?>', '<?= htmlspecialchars($patient['sex']) ?>', '<?= htmlspecialchars($patient['contact_number']) ?>', '<?= htmlspecialchars($patient['home_address'] ?? '') ?>', '<?= htmlspecialchars($patient['philhealth_status']) ?>', '<?= htmlspecialchars($patient['philhealth_id'] ?? '') ?>')"
                                            class="text-sm font-medium text-blue-600 hover:text-blue-700 transition" title="Edit">
                                            <i data-lucide="edit"
                                                class="w-6 h-6 mr-1 bg-blue-100 px-1 py-1 rounded-md border border-blue-500"></i>
                                        </button>
                                    <?php endif; ?>
                                    <?php if ($patient['status'] !== 'Rejected'): ?>
                                        <a href="/<?= PROJECT_DIR ?>/index.php?role=radtech&page=patient-approval&action=reject&id=<?= $patient['id'] ?>"
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
</div>

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
                        oninput="formatPhilHealthInput(this)"
                        class="mt-1 text-sm text-gray-900 bg-gray-50 p-2 rounded w-full" placeholder="XX-XXXXXXXXX-X">
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

<script
    src="/<?= PROJECT_DIR ?>/views/pages/radtech/patient-approval.js?v=<?= filemtime(__DIR__ . '/patient-approval.js') ?>"></script>

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

                // Info banner
                const banner = document.createElement('div');
                banner.innerHTML = `<div style="display:flex;align-items:center;gap:0.5rem;"><svg xmlns='http://www.w3.org/2000/svg' width='18' height='18' fill='none' stroke='currentColor' stroke-width='2' viewBox='0 0 24 24'><circle cx='12' cy='12' r='10'/><line x1='12' y1='8' x2='12' y2='12'/><line x1='12' y1='16' x2='12.01' y2='16'/></svg><span>Navigated from notification — Case <strong>${highlightId}</strong> is highlighted below.</span></div>`;
                banner.style.cssText = 'margin-top:1rem;padding:0.75rem 1rem;border-radius:0.75rem;background:#fefce8;border:1px solid #fde047;color:#854d0e;font-size:0.875rem;font-weight:500;display:flex;align-items:center;gap:0.5rem;';
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