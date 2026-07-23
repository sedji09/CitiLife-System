<?php
require_once __DIR__ . '/../../../config/database.php';

$caseModel = new \CaseModel($pdo);
$branchId = $_SESSION['branch_id'] ?? 1;

// Fetch released records (Backend logic)
$records = $caseModel->getReleasedRecords($branchId);
?>

<?php
require_once __DIR__ . '/../../../app/Models/ResultDisputeModel.php';
$disputeModel = new \ResultDisputeModel($pdo);
$disputes = $disputeModel->getDisputesForClinic($branchId, 'radtech');
$pendingDisputeCount = count(array_filter($disputes, function($d) { return in_array($d['status'], ['Pending RadTech Review', 'Pending RadTech Verification']); }));
?>

<!-- Header -->
<div class="flex items-center justify-between">
    <div>
        <h2 class="text-xl font-semibold text-gray-900">X-ray Patient Records</h2>
        <p class="text-sm text-gray-500 mt-1">Manage completed records and review patient error reports</p>
    </div>
</div>

<!-- Navigation Tabs -->
<div class="mt-4 border-b border-gray-200">
    <nav class="flex space-x-6">
        <button type="button" id="tab-completed-btn" onclick="switchRadtechTab('completed')"
                class="pb-3 px-1 text-sm font-bold border-b-2 border-red-600 text-red-600 transition flex items-center gap-2">
            <i data-lucide="file-check-2" class="w-4 h-4"></i> Completed Records
        </button>
        <button type="button" id="tab-disputes-btn" onclick="switchRadtechTab('disputes')"
                class="pb-3 px-1 text-sm font-semibold border-b-2 border-transparent text-gray-500 hover:text-gray-700 transition flex items-center gap-2 relative">
            <i data-lucide="alert-circle" class="w-4 h-4"></i> Patient Error Reports / Disputes
            <?php if ($pendingDisputeCount > 0): ?>
                <span class="ml-1 inline-flex items-center justify-center px-2 py-0.5 text-xs font-bold leading-none text-white bg-red-600 rounded-full">
                    <?= $pendingDisputeCount ?>
                </span>
            <?php endif; ?>
        </button>
    </nav>
</div>

<div class="mt-6 flex flex-col gap-4">
    <div class="flex gap-4 items-center">
        <input type="text" id="search-input" placeholder="Search patient records (Name or ID)..."
            class="w-80 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm text-gray-900 outline-none focus:ring-2 focus:ring-red-500">

        <select id="sort-date"
            class="w-48 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm outline-none focus:ring-2 focus:ring-red-500">
            <option>Newest Case</option>
            <option>Oldest Case</option>
        </select>
    </div>
</div>

<div id="xray-records-table-card" class="rounded-xl border border-gray-300 bg-white shadow-sm mt-4 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="sticky top-0 z-10">
                <tr class="border-b border-gray-200 bg-gray-50 text-gray-600">
                    <th class="text-left font-semibold px-3 py-3 whitespace-nowrap">Case No.</th>
                    <th class="text-left font-semibold px-3 py-3 whitespace-nowrap">Patient No.</th>
                    <th class="text-left font-semibold px-3 py-3 truncate max-w-[200px]">Patient Name</th>
                    <th class="text-left font-semibold px-3 py-3 truncate max-w-[150px]">Exam Type</th>
                    <th class="text-left font-semibold px-3 py-3">Date</th>
                    <th class="text-left font-semibold px-3 py-3 whitespace-nowrap">Actions</th>
                </tr>
            </thead>
            <tbody id="table-body" class="text-gray-800 bg-white divide-y divide-gray-100">
                <?php if (count($records) === 0): ?>
                    <tr>
                        <td colspan="6" class="text-center py-8 text-gray-500">
                            No completed records found. Click 'Send Results' in the patient queue to move cases here.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($records as $row): ?>
                        <tr class="hover:bg-gray-50 transition-colors record-row"
                            data-id="<?= htmlspecialchars($row['case_number']) ?>"
                            data-patient="<?= htmlspecialchars($row['patient_number'] ?? '') ?>"
                            data-name="<?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?>"
                            data-exam="<?= htmlspecialchars($row['exam_type']) ?>"
                            data-date="<?= htmlspecialchars($row['created_at']) ?>">
                            <td class="py-3 px-3 whitespace-nowrap">
                                <div class="font-medium"><?= htmlspecialchars($row['case_number']) ?></div>
                            </td>
                            <td class="py-3 px-3 whitespace-nowrap">
                                <div class="font-medium"><?= htmlspecialchars($row['patient_number'] ?? 'N/A') ?></div>
                            </td>
                            <td class="py-3 px-3 truncate max-w-[200px]"
                                title="<?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?>">
                                <div class="font-medium truncate">
                                    <?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?>
                                </div>
                            </td>
                            <td class="py-3 px-3 max-w-[180px]">
                                <?php
                                $exams = array_filter(array_map('trim', explode(',', $row['exam_type'])));
                                $firstExam = reset($exams);
                                $extraCount = count($exams) - 1;
                                ?>
                                <div class="flex items-center gap-1.5">
                                    <span class="font-medium text-gray-800 truncate max-w-[100px]"
                                        title="<?= htmlspecialchars($row['exam_type']) ?>"><?= htmlspecialchars($firstExam) ?></span>
                                    <?php if ($extraCount > 0): ?>
                                        <span
                                            class="inline-flex items-center rounded-full bg-gray-100 border border-gray-300 px-1.5 py-0.5 text-xs font-semibold text-gray-600 cursor-default"
                                            title="<?= htmlspecialchars($row['exam_type']) ?>">+<?= $extraCount ?></span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="py-3 px-3">
                                <div class="text-sm text-gray-500"><?= date('F d, Y', strtotime($row['created_at'])) ?></div>
                            </td>
                            <td class="py-3 px-3 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    <!-- View -->
                                    <a href="/<?= PROJECT_DIR ?>/index.php?role=radtech&page=records-history&id=<?= $row['id'] ?>"
                                        class="text-sm font-medium text-blue-500 hover:text-blue-700 transition"
                                        title="View Record">
                                        <i data-lucide="eye"
                                            class="w-6 h-6 bg-blue-100 px-1 py-1 rounded-md border border-blue-500"></i>
                                    </a>

                                    <!-- Print -->
                                    <a href="javascript:void(0)"
                                        onclick="confirmAction('Confirm Print', 'Would you like to confirm printing this report?', '/<?= PROJECT_DIR ?>/index.php?page=print-report&id=<?= $row['id'] ?>', 'Yes, Print', true, event)"
                                        class="text-green-500 hover:text-green-700 transition" title="Print Report">
                                        <i data-lucide="printer"
                                            class="w-6 h-6 bg-green-100 px-1 py-1 rounded-md border border-green-500"></i>
                                    </a>

                                    <!-- Download PDF -->
                                    <a href="javascript:void(0)"
                                        onclick="confirmAction('Confirm Download', 'Would you like to save this report as PDF?', '/<?= PROJECT_DIR ?>/index.php?page=print-report&id=<?= $row['id'] ?>&download=true', 'Yes, Download', true, event)"
                                        class="text-red-500 hover:text-red-700 transition" title="Download PDF">
                                        <i data-lucide="download"
                                            class="w-6 h-6 bg-red-100 px-1 py-1 rounded-md border border-red-500"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination footer -->
    <div
        class="flex flex-col sm:flex-row items-center justify-between border-t border-gray-200 bg-gray-50 px-6 py-4 gap-4">
        <!-- Record count -->
        <span id="xray-record-count" class="text-xs text-gray-500 font-medium"></span>

        <!-- Pagination Controls -->
        <div class="flex items-center flex-wrap gap-1.5" id="xray-pagination-controls">
            <!-- Dynamic page buttons will be inserted here -->
        </div>
    </div>
</div>

<!-- DISPUTES / ERROR REPORTS TABLE CARD (Hidden by default) -->
<div id="disputes-table-card" class="hidden rounded-xl border border-gray-300 bg-white shadow-sm mt-4 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-200 bg-gray-50 text-gray-600">
                    <th class="text-left font-semibold px-4 py-3">Case / Patient</th>
                    <th class="text-left font-semibold px-4 py-3">Error Type</th>
                    <th class="text-left font-semibold px-4 py-3">Details / Statement</th>
                    <th class="text-left font-semibold px-4 py-3">Status</th>
                    <th class="text-left font-semibold px-4 py-3">Date Reported</th>
                    <th class="text-left font-semibold px-4 py-3">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 bg-white">
                <?php if (count($disputes) === 0): ?>
                    <tr>
                        <td colspan="6" class="text-center py-8 text-gray-500">
                            No patient error reports or disputes found.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($disputes as $d): ?>
                        <?php
                        $catLabels = [
                            'demographic_error' => 'Wrong Patient Info',
                            'exam_details_error' => 'Wrong Body Part / Exam Details',
                            'findings_error' => 'Discrepancy in Findings (Medical)',
                            'other' => 'Other Error'
                        ];
                        $catName = $catLabels[$d['dispute_category']] ?? ucfirst($d['dispute_category']);
                        $isMedicalError = ($d['dispute_category'] === 'findings_error');

                        $badgeClasses = [
                            'Pending RadTech Review' => 'bg-amber-50 text-amber-700 border-amber-300',
                            'Escalated to Radiologist' => 'bg-purple-50 text-purple-700 border-purple-300',
                            'Pending RadTech Verification' => 'bg-blue-50 text-blue-700 border-blue-300',
                            'Resolved' => 'bg-green-50 text-green-700 border-green-300',
                            'Rejected' => 'bg-gray-50 text-gray-700 border-gray-300'
                        ];
                        $statusBadgeClass = $badgeClasses[$d['status']] ?? 'bg-gray-50 text-gray-600 border-gray-200';
                        ?>
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-4 py-3.5 whitespace-nowrap">
                                <div class="font-mono text-xs font-semibold text-red-600"><?= htmlspecialchars($d['case_number']) ?></div>
                                <div class="font-bold text-gray-900"><?= htmlspecialchars($d['first_name'] . ' ' . $d['last_name']) ?></div>
                                <div class="text-xs text-gray-500"><?= htmlspecialchars($d['exam_type']) ?></div>
                            </td>
                            <td class="px-4 py-3.5 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold <?= $isMedicalError ? 'bg-red-50 text-red-700 border border-red-200' : 'bg-blue-50 text-blue-700 border border-blue-200' ?>">
                                    <?= htmlspecialchars($catName) ?>
                                </span>
                            </td>
                            <td class="px-4 py-3.5 text-xs text-gray-700 max-w-[200px] lg:max-w-[240px] whitespace-normal break-words" title="<?= htmlspecialchars($d['description']) ?>">
                                <div class="line-clamp-2 italic">"<?= htmlspecialchars($d['description']) ?>"</div>
                                <?php if ($d['radtech_notes']): ?>
                                    <div class="mt-1 text-[11px] text-purple-700 font-medium line-clamp-1">Notes: <?= htmlspecialchars($d['radtech_notes']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3.5 whitespace-nowrap">
                                <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-bold <?= $statusBadgeClass ?>">
                                    <?= htmlspecialchars($d['status']) ?>
                                </span>
                            </td>
                            <td class="px-4 py-3.5 text-xs text-gray-500 whitespace-nowrap">
                                <?= date('M j, Y h:i A', strtotime($d['created_at'])) ?>
                            </td>
                            <td class="px-4 py-3.5 whitespace-nowrap">
                                <?php if ($d['status'] === 'Pending RadTech Review'): ?>
                                    <?php if ($isMedicalError): ?>
                                        <button type="button" onclick="openEscalateModal(<?= $d['id'] ?>, '<?= htmlspecialchars($d['case_number']) ?>')"
                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold shadow-sm transition">
                                            <i data-lucide="send" class="w-3.5 h-3.5"></i> Escalate to Radiologist
                                        </button>
                                     <?php else: ?>
                                        <button type="button" onclick="openResolveModal(<?= $d['id'] ?>, '<?= htmlspecialchars($d['case_number']) ?>', 'Fix Clerical Error', <?= htmlspecialchars(json_encode($d['description']), ENT_QUOTES, 'UTF-8') ?>)"
                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-green-600 hover:bg-green-700 text-white text-xs font-bold shadow-sm transition">
                                            <i data-lucide="check-circle" class="w-3.5 h-3.5"></i> Fix & Resolve
                                        </button>
                                    <?php endif; ?>
                                <?php elseif ($d['status'] === 'Escalated to Radiologist'): ?>
                                    <span class="inline-flex items-center gap-1 text-xs font-semibold text-purple-700 bg-purple-50 border border-purple-200 px-2.5 py-1 rounded-lg">
                                        <i data-lucide="clock" class="w-3.5 h-3.5"></i> Waiting for Radiologist Amendment
                                    </span>
                                <?php elseif ($d['status'] === 'Pending RadTech Verification'): ?>
                                    <button type="button" onclick="openResolveModal(<?= $d['id'] ?>, '<?= htmlspecialchars($d['case_number']) ?>', 'Final Approve & Release Amended Report', <?= htmlspecialchars(json_encode($d['description']), ENT_QUOTES, 'UTF-8') ?>)"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-green-600 hover:bg-green-700 text-white text-xs font-bold shadow-sm transition">
                                        <i data-lucide="check-circle-2" class="w-3.5 h-3.5"></i> Final Verify & Release
                                    </button>
                                <?php else: ?>
                                    <span class="text-xs text-gray-400 italic">No action needed</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ESCALATE TO RADIOLOGIST MODAL -->
<div id="escalate-modal" class="fixed inset-0 z-[9999] hidden flex items-center justify-center bg-black/50 p-4">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl space-y-4">
        <div class="flex items-center justify-between border-b pb-3">
            <h3 class="font-bold text-gray-900 text-base">Escalate to Radiologist</h3>
            <button onclick="closeEscalateModal()" class="text-gray-400 hover:text-gray-600"><i data-lucide="x" class="w-5 h-5"></i></button>
        </div>
        <p class="text-xs text-gray-600">This medical interpretation error will be forwarded to the Radiologist to review the image and issue an Amended Report.</p>
        <form id="escalate-form" onsubmit="submitEscalation(event)" class="space-y-3">
            <input type="hidden" id="escalate-dispute-id" name="dispute_id">
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">Internal Notes for Radiologist *</label>
                <textarea id="escalate-notes" name="radtech_notes" required rows="3" placeholder="Example: Please re-examine the lung fields based on patient's feedback..."
                          class="w-full text-xs rounded-xl border border-gray-300 p-3 outline-none focus:ring-2 focus:ring-purple-500"></textarea>
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="closeEscalateModal()" class="px-4 py-2 text-xs font-semibold text-gray-600 hover:bg-gray-100 rounded-xl">Cancel</button>
                <button type="submit" class="px-5 py-2 text-xs font-bold text-white bg-purple-600 hover:bg-purple-700 rounded-xl shadow">Send to Radiologist</button>
            </div>
        </form>
    </div>
</div>

<!-- RESOLVE DISPUTE MODAL -->
<div id="resolve-modal" class="fixed inset-0 z-[9999] hidden flex items-center justify-center bg-black/50 p-4">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl space-y-4">
        <div class="flex items-center justify-between border-b pb-3">
            <h3 id="resolve-modal-title" class="font-bold text-gray-900 text-base">Resolve Dispute Ticket</h3>
            <button onclick="closeResolveModal()" class="text-gray-400 hover:text-gray-600"><i data-lucide="x" class="w-5 h-5"></i></button>
        </div>

        <!-- Patient Requested Corrections Box -->
        <div id="resolve-modal-patient-details" class="p-3 bg-amber-50 border border-amber-200 rounded-xl space-y-1">
            <div class="text-[11px] font-bold text-amber-800 flex items-center gap-1.5">
                <i data-lucide="alert-circle" class="w-3.5 h-3.5 text-amber-600"></i>
                Patient Correction Requested:
            </div>
            <div id="resolve-patient-statement" class="text-xs text-amber-950 font-medium whitespace-pre-line bg-white p-2.5 rounded-lg border border-amber-100 shadow-sm"></div>
        </div>

        <form id="resolve-form" onsubmit="submitResolution(event)" class="space-y-3">
            <input type="hidden" id="resolve-dispute-id" name="dispute_id">
            
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="closeResolveModal()" class="px-4 py-2 text-xs font-semibold text-gray-600 hover:bg-gray-100 rounded-xl">Cancel</button>
                <button type="submit" class="px-5 py-2 text-xs font-bold text-white bg-green-600 hover:bg-green-700 rounded-xl shadow">Confirm & Resolve</button>
            </div>
        </form>
    </div>
</div>

<script>
function switchRadtechTab(tab) {
    const compCard = document.getElementById('xray-records-table-card');
    const dispCard = document.getElementById('disputes-table-card');
    const filterControls = document.querySelector('.mt-6.flex.flex-col.gap-4');
    const compBtn = document.getElementById('tab-completed-btn');
    const dispBtn = document.getElementById('tab-disputes-btn');

    if (tab === 'completed') {
        if (compCard) compCard.classList.remove('hidden');
        if (dispCard) dispCard.classList.add('hidden');
        if (filterControls) filterControls.classList.remove('hidden');

        if (compBtn) compBtn.className = "pb-3 px-1 text-sm font-bold border-b-2 border-red-600 text-red-600 transition flex items-center gap-2";
        if (dispBtn) dispBtn.className = "pb-3 px-1 text-sm font-semibold border-b-2 border-transparent text-gray-500 hover:text-gray-700 transition flex items-center gap-2 relative";
    } else {
        if (compCard) compCard.classList.add('hidden');
        if (dispCard) dispCard.classList.remove('hidden');
        if (filterControls) filterControls.classList.add('hidden');

        if (dispBtn) dispBtn.className = "pb-3 px-1 text-sm font-bold border-b-2 border-red-600 text-red-600 transition flex items-center gap-2 relative";
        if (compBtn) compBtn.className = "pb-3 px-1 text-sm font-semibold border-b-2 border-transparent text-gray-500 hover:text-gray-700 transition flex items-center gap-2";
    }
}

function openEscalateModal(id, caseNo) {
    document.getElementById('escalate-dispute-id').value = id;
    document.getElementById('escalate-notes').value = '';
    document.getElementById('escalate-modal').classList.remove('hidden');
}
function closeEscalateModal() {
    document.getElementById('escalate-modal').classList.add('hidden');
}

function openResolveModal(id, caseNo, titleText, descriptionText) {
    document.getElementById('resolve-dispute-id').value = id;
    document.getElementById('resolve-modal-title').innerText = titleText || 'Resolve Dispute Ticket';
    
    const detailsContainer = document.getElementById('resolve-modal-patient-details');
    const statementEl = document.getElementById('resolve-patient-statement');
    if (descriptionText) {
        let formatted = descriptionText.replace(/,\s*/g, "\n");
        statementEl.innerText = formatted;
        detailsContainer.classList.remove('hidden');
    } else {
        statementEl.innerText = '';
        detailsContainer.classList.add('hidden');
    }

    document.getElementById('resolve-modal').classList.remove('hidden');
}
function closeResolveModal() {
    document.getElementById('resolve-modal').classList.add('hidden');
}

function submitEscalation(e) {
    e.preventDefault();
    const disputeId = document.getElementById('escalate-dispute-id').value;
    const notes = document.getElementById('escalate-notes').value;

    const fd = new FormData();
    fd.append('action', 'escalate_to_radiologist');
    fd.append('dispute_id', disputeId);
    fd.append('radtech_notes', notes);

    fetch('/<?= PROJECT_DIR ?>/app/api/disputes.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                closeEscalateModal();
                Swal.fire({
                    icon: 'success',
                    title: 'Escalated to Radiologist!',
                    text: res.message,
                    confirmButtonColor: '#9333ea',
                    customClass: { popup: 'rounded-2xl' }
                }).then(() => {
                    window.location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: res.message || 'Failed to escalate',
                    confirmButtonColor: '#dc2626',
                    customClass: { popup: 'rounded-2xl' }
                });
            }
        })
        .catch(err => {
            console.error('Escalation error:', err);
            Swal.fire({
                icon: 'error',
                title: 'Connection Error',
                text: 'Hindi maipadala ang request. Pakisubukan muli.',
                confirmButtonColor: '#dc2626',
                customClass: { popup: 'rounded-2xl' }
            });
        });
}

function submitResolution(e) {
    e.preventDefault();
    const disputeId = document.getElementById('resolve-dispute-id').value;

    const fd = new FormData();
    fd.append('action', 'resolve_dispute');
    fd.append('dispute_id', disputeId);

    fetch('/<?= PROJECT_DIR ?>/app/api/disputes.php', { method: 'POST', body: fd })
        .then(r => {
            if (!r.ok) throw new Error('HTTP error ' + r.status);
            return r.json();
        })
        .then(res => {
            if (res.success) {
                closeResolveModal();
                Swal.fire({
                    icon: 'success',
                    title: 'Dispute Resolved & Released!',
                    text: res.message,
                    confirmButtonColor: '#16a34a',
                    customClass: { popup: 'rounded-2xl' }
                }).then(() => {
                    window.location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: res.message || 'Failed to resolve dispute',
                    confirmButtonColor: '#dc2626',
                    customClass: { popup: 'rounded-2xl' }
                });
            }
        })
        .catch(err => {
            console.error('Resolution error:', err);
            Swal.fire({
                icon: 'error',
                title: 'Connection Error',
                text: 'Hindi maipadala ang request. Pakisubukan muli.',
                confirmButtonColor: '#dc2626',
                customClass: { popup: 'rounded-2xl' }
            });
        });
}

document.addEventListener('DOMContentLoaded', () => {
    const params = new URLSearchParams(window.location.search);
    if (params.get('dispute_id') || params.get('tab') === 'disputes') {
        switchRadtechTab('disputes');
    }
});
</script>

<script src="/<?= PROJECT_DIR ?>/views/pages/radtech/xray-patient-records.js?v=<?= time() ?>"></script>