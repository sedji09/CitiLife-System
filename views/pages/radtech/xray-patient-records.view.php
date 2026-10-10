<?php
require_once __DIR__ . '/../../../config/database.php';

$caseModel = new \CaseModel($pdo);
$branchId = $_SESSION['branch_id'] ?? 1;

// Fetch released records (Backend logic)
$records = $caseModel->getReleasedRecords($branchId);
?>

<!-- Header -->
<div class="flex items-center justify-between">
    <div>
        <h2 class="text-xl font-semibold text-gray-900">Completed Records</h2>
        <p class="text-sm text-gray-500 mt-1">Manage completed examination records, result claiming, and view reports</p>
    </div>
</div>

<div class="mt-6 flex gap-4 items-center">
    <input type="text" id="search-input" placeholder="Search patient records (Name or ID)..."
        class="flex-1 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm text-gray-900 outline-none focus:ring-2 focus:ring-red-500 shadow-2xs">

    <select id="filter-claim-status"
        class="w-48 shrink-0 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm outline-none focus:ring-2 focus:ring-red-500 shadow-2xs cursor-pointer">
        <option value="all">All Claim Status</option>
        <option value="unclaimed">🟡 Unclaimed</option>
        <option value="claimed">🟢 Claimed</option>
    </select>

    <select id="sort-date"
        class="w-40 shrink-0 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm outline-none focus:ring-2 focus:ring-red-500 shadow-2xs cursor-pointer">
        <option>Newest Case</option>
        <option>Oldest Case</option>
    </select>
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
                    <th class="text-left font-semibold px-3 py-3 whitespace-nowrap">Report Status</th>
                    <th class="text-left font-semibold px-3 py-3 whitespace-nowrap">Claim Status</th>
                    <th class="text-left font-semibold px-3 py-3">Date</th>
                    <th class="text-left font-semibold px-3 py-3 whitespace-nowrap">Actions</th>
                </tr>
            </thead>
            <tbody id="table-body" class="text-gray-800 bg-white divide-y divide-gray-100">
                <?php if (count($records) === 0): ?>
                    <tr>
                        <td colspan="8" class="text-center py-8 text-gray-500">
                            No completed records found.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($records as $row):
                        $patFullName = formatFullName($row);
                        $isClaimed = !empty($row['is_claimed']) && (int) $row['is_claimed'] === 1;
                        ?>
                        <tr class="hover:bg-gray-50 transition-colors record-row"
                            data-id="<?= htmlspecialchars($row['case_number']) ?>"
                            data-case-id="<?= $row['id'] ?>"
                            data-patient="<?= htmlspecialchars($row['patient_number'] ?? '') ?>"
                            data-name="<?= htmlspecialchars($patFullName) ?>"
                            data-exam="<?= htmlspecialchars($row['exam_type']) ?>"
                            data-claimed="<?= $isClaimed ? 'claimed' : 'unclaimed' ?>"
                            data-date="<?= htmlspecialchars($row['created_at']) ?>">
                            <td class="py-3 px-3 whitespace-nowrap">
                                <div class="font-medium"><?= htmlspecialchars($row['case_number']) ?></div>
                            </td>
                            <td class="py-3 px-3 whitespace-nowrap">
                                <div class="font-medium"><?= htmlspecialchars($row['patient_number'] ?? 'N/A') ?></div>
                            </td>
                            <td class="py-3 px-3 truncate max-w-[200px]" title="<?= htmlspecialchars($patFullName) ?>">
                                <div class="font-medium truncate">
                                    <?= htmlspecialchars($patFullName) ?>
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
                            <td class="py-3 px-3 whitespace-nowrap">
                                <?php if (!empty($row['is_amended']) && (int) $row['is_amended'] === 1): ?>
                                    <span
                                        class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-300"
                                        title="This record has been edited">
                                        <i data-lucide="edit-3" class="w-3 h-3"></i> Edited
                                    </span>
                                <?php else: ?>
                                    <span
                                        class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        Released
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="py-3 px-3 whitespace-nowrap">
                                <?php if ($isClaimed): ?>
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-300 shadow-2xs">
                                        Claimed
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-800 border border-amber-300 shadow-2xs">
                                        Unclaimed
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="py-3 px-3">
                                <div class="text-sm text-gray-500"><?= date('F d, Y', strtotime($row['created_at'])) ?></div>
                            </td>
                            <td class="py-3 px-3 whitespace-nowrap">
                                <div class="flex items-center gap-1.5">
                                    <!-- View -->
                                    <a href="<?= url('records-history?id=' . $row['id']) ?>"
                                        class="p-1.5 rounded-md border border-blue-500 bg-blue-100 text-blue-600 hover:bg-blue-600 hover:text-white hover:border-blue-600 transition shadow-sm inline-flex items-center justify-center cursor-pointer"
                                        title="View Record">
                                        <i data-lucide="eye" class="w-4 h-4"></i>
                                    </a>

                                    <!-- Claim Action Button -->
                                    <?php if ($isClaimed): ?>
                                        <button type="button"
                                            onclick='openClaimDetailsModal(<?= htmlspecialchars(json_encode([
                                                'case_id' => $row['id'],
                                                'case_number' => $row['case_number'],
                                                'patient_name' => $patFullName,
                                                'claimed_at' => !empty($row['claimed_at']) ? date('F d, Y h:i A', strtotime($row['claimed_at'])) : '—',
                                                'claimed_by' => $row['claimed_by'] ?: $patFullName,
                                                'claimed_relationship' => $row['claimed_relationship'] ?: 'Self',
                                                'claimed_id_presented' => $row['claimed_id_presented'] ?: 'None Specified',
                                                'claimed_notes' => $row['claimed_notes'] ?: 'No additional notes'
                                            ]), ENT_QUOTES, 'UTF-8') ?>)'
                                            class="p-1.5 rounded-md border border-emerald-500 bg-emerald-100 text-emerald-600 hover:bg-emerald-600 hover:text-white hover:border-emerald-600 transition shadow-sm inline-flex items-center justify-center cursor-pointer"
                                            title="Claimed: <?= !empty($row['claimed_at']) ? date('M d, Y', strtotime($row['claimed_at'])) : '' ?><?= !empty($row['claimed_by']) ? ' (' . htmlspecialchars($row['claimed_by']) . ')' : '' ?> - Click to view/revert">
                                            <i data-lucide="clipboard-check" class="w-4 h-4"></i>
                                        </button>
                                    <?php else: ?>
                                        <button type="button"
                                            onclick="openMarkClaimModal(<?= $row['id'] ?>, '<?= htmlspecialchars(addslashes($row['case_number'])) ?>', '<?= htmlspecialchars(addslashes($patFullName)) ?>', '<?= htmlspecialchars(addslashes($row['examination_type'] ?? 'X-ray')) ?>')"
                                            class="p-1.5 rounded-md border border-amber-500 bg-amber-100 text-amber-600 hover:bg-amber-600 hover:text-white hover:border-amber-600 transition shadow-sm inline-flex items-center justify-center cursor-pointer"
                                            title="Mark Result as Claimed">
                                            <i data-lucide="clipboard-check" class="w-4 h-4"></i>
                                        </button>
                                    <?php endif; ?>

                                    <?php
                                    $isReverted = !empty($row['re_edit_reason'])
                                        || ($row['report_status'] ?? '') === 'Draft'
                                        || in_array($row['status'], ['Under Reading', 'Pending', 'For Revision', 'Rejected', 'Cancelled']);
                                    $isReportAvailable = in_array($row['status'], ['Report Ready', 'Completed', 'Released']) && !$isReverted;
                                    ?>

                                    <?php if ($isReportAvailable): ?>
                                        <!-- Print -->
                                        <a href="javascript:void(0)"
                                            onclick="confirmAction('Confirm Print', 'Would you like to confirm printing this report?', '<?= url('print-report?ref=' . generateReportToken($row['id'])) ?>', 'Yes, Print', true, event)"
                                            class="p-1.5 rounded-md border border-green-500 bg-green-100 text-green-600 hover:bg-green-600 hover:text-white hover:border-green-600 transition shadow-sm inline-flex items-center justify-center cursor-pointer"
                                            title="Print Report">
                                            <i data-lucide="printer" class="w-4 h-4"></i>
                                        </a>

                                        <!-- Download PDF -->
                                        <a href="javascript:void(0)"
                                            onclick="confirmAction('Confirm Download', 'Would you like to save this report as PDF?', '<?= url('print-report?ref=' . generateReportToken($row['id']) . '&download=true') ?>', 'Yes, Download', true, event)"
                                            class="p-1.5 rounded-md border border-purple-500 bg-purple-100 text-purple-600 hover:bg-purple-600 hover:text-white hover:border-purple-600 transition shadow-sm inline-flex items-center justify-center cursor-pointer"
                                            title="Download PDF">
                                            <i data-lucide="download" class="w-4 h-4"></i>
                                        </a>
                                    <?php else: ?>
                                        <!-- Disabled Print -->
                                        <button
                                            class="p-1.5 rounded-md border border-gray-300 bg-gray-50 text-gray-400 cursor-not-allowed opacity-60 shadow-sm inline-flex items-center justify-center"
                                            title="Print Report (Disabled: <?= !empty($row['re_edit_reason']) ? 'Report returned to Radiologist for re-edit' : 'Available after Radiologist submits report' ?>)"
                                            disabled>
                                            <i data-lucide="printer" class="w-4 h-4"></i>
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

<!-- ========================================== -->
<!-- NATIVE MODAL: MARK RESULT AS CLAIMED       -->
<!-- ========================================== -->
<div id="modalMarkClaim" class="fixed inset-0 z-50 hidden overflow-y-auto bg-gray-900/60 backdrop-blur-xs transition-opacity duration-200" onclick="if(event.target === this) closeMarkClaimModal()">
    <div class="flex min-h-screen items-center justify-center p-3 sm:p-4 text-center">
        <div class="relative w-full max-w-lg rounded-2xl bg-white text-left shadow-2xl transition-all border border-gray-100 overflow-hidden my-auto animate-in fade-in zoom-in-95 duration-150">
            <!-- Header -->
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between bg-white shrink-0">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-red-50 text-red-600 flex items-center justify-center border border-red-100 shrink-0">
                        <i data-lucide="clipboard-check" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-gray-900 leading-tight">Mark Result as Claimed</h3>
                        <p class="text-xs text-gray-500">Verify receiver and confirm physical result release</p>
                    </div>
                </div>
                <button type="button" onclick="closeMarkClaimModal()" class="text-gray-400 hover:text-gray-600 p-1.5 rounded-lg hover:bg-gray-100 transition cursor-pointer">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <!-- Form -->
            <form id="formMarkClaim" onsubmit="submitMarkClaim(event)" class="p-5 space-y-3.5">
                <input type="hidden" id="claim_modal_case_id">

                <!-- Summary Info Card -->
                <div class="p-3 bg-gray-50/80 border border-gray-200/80 rounded-xl shadow-2xs">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400 block">Case Number</span>
                    <div class="text-sm font-black text-gray-900 font-mono tracking-tight" id="claim_modal_case_no">—</div>
                    <div class="text-xs font-semibold text-gray-700 flex items-center gap-1.5 mt-0.5 truncate">
                        <i data-lucide="user" class="w-3.5 h-3.5 text-gray-400 shrink-0"></i>
                        <span class="truncate" id="claim_modal_pat_name">—</span>
                    </div>
                </div>

                <!-- Verification Tip -->
                <div class="flex items-center gap-2 p-2.5 bg-blue-50/70 border border-blue-100 rounded-xl text-xs text-blue-800">
                    <i data-lucide="shield-check" class="w-4 h-4 text-blue-600 shrink-0"></i>
                    <span>Please verify receiver ID before handing over the official printed film & envelope.</span>
                </div>

                <!-- Receiver Type Selector -->
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                        Who is claiming? <span class="text-red-500">*</span>
                    </label>
                    <div class="grid grid-cols-2 gap-2">
                        <button type="button" id="btn-type-self" onclick="setClaimReceiverType('Self')" class="flex items-center justify-center gap-2 py-2 px-3 rounded-xl border-2 border-red-500 bg-red-50 text-red-700 font-bold text-xs transition cursor-pointer shadow-2xs">
                            <i data-lucide="user-check" class="w-3.5 h-3.5"></i> Patient (Self)
                        </button>
                        <button type="button" id="btn-type-rep" onclick="setClaimReceiverType('Representative')" class="flex items-center justify-center gap-2 py-2 px-3 rounded-xl border border-gray-200 bg-white text-gray-600 font-semibold text-xs hover:bg-gray-50 transition cursor-pointer">
                            <i data-lucide="users" class="w-3.5 h-3.5"></i> Representative
                        </button>
                    </div>
                    <input type="hidden" id="claim_receiver_type" value="Self">
                </div>

                <!-- Receiver Name & Relationship -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                            Claimed By (Full Name) <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="claim_receiver_name" required class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-xs sm:text-sm text-gray-900 outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition shadow-2xs font-medium" placeholder="Full name of receiver">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                            Relationship <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="claim_relationship" value="Self" disabled class="w-full rounded-xl border border-gray-300 bg-gray-100/70 px-3 py-2 text-xs sm:text-sm text-gray-900 outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition shadow-2xs disabled:cursor-not-allowed font-medium" placeholder="e.g. Spouse, Parent, Child">
                    </div>
                </div>

                <!-- Valid ID (Representative Only) -->
                <div id="claim_id_wrapper" class="hidden space-y-2.5">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                            Valid ID Presented <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="claim_id_type" class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-xs sm:text-sm text-gray-900 outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition shadow-2xs font-medium" placeholder="e.g. PhilHealth, Driver's License, National ID">
                        <!-- Quick Chips -->
                        <div class="flex flex-wrap gap-1 mt-1.5">
                            <button type="button" onclick="document.getElementById('claim_id_type').value = 'National ID (PhilSys)'" class="text-[10px] font-semibold text-gray-600 bg-gray-100 hover:bg-gray-200 px-2 py-0.5 rounded-md transition cursor-pointer">National ID</button>
                            <button type="button" onclick="document.getElementById('claim_id_type').value = 'PhilHealth ID'" class="text-[10px] font-semibold text-gray-600 bg-gray-100 hover:bg-gray-200 px-2 py-0.5 rounded-md transition cursor-pointer">PhilHealth</button>
                            <button type="button" onclick="document.getElementById('claim_id_type').value = 'Driver\'s License'" class="text-[10px] font-semibold text-gray-600 bg-gray-100 hover:bg-gray-200 px-2 py-0.5 rounded-md transition cursor-pointer">Driver's License</button>
                            <button type="button" onclick="document.getElementById('claim_id_type').value = 'UMID'" class="text-[10px] font-semibold text-gray-600 bg-gray-100 hover:bg-gray-200 px-2 py-0.5 rounded-md transition cursor-pointer">UMID</button>
                            <button type="button" onclick="document.getElementById('claim_id_type').value = 'Passport'" class="text-[10px] font-semibold text-gray-600 bg-gray-100 hover:bg-gray-200 px-2 py-0.5 rounded-md transition cursor-pointer">Passport</button>
                            <button type="button" onclick="document.getElementById('claim_id_type').value = 'Company / School ID'" class="text-[10px] font-semibold text-gray-600 bg-gray-100 hover:bg-gray-200 px-2 py-0.5 rounded-md transition cursor-pointer">Company ID</button>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                            ID Number <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="claim_id_number" class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-xs sm:text-sm text-gray-900 outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition shadow-2xs font-medium" placeholder="e.g. 1234-5678-9012">
                    </div>
                </div>

                <!-- Footer Buttons -->
                <div class="pt-3 border-t border-gray-100 flex items-center justify-end gap-2.5">
                    <button type="button" onclick="closeMarkClaimModal()" class="px-4 py-2 text-xs sm:text-sm font-semibold text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl transition cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" id="btnSubmitClaim" class="inline-flex items-center gap-1.5 px-5 py-2 text-xs sm:text-sm font-bold text-white bg-red-600 hover:bg-red-700 rounded-xl shadow-sm transition active:scale-95 cursor-pointer">
                        <i data-lucide="check" class="w-4 h-4"></i> Confirm Claim
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- NATIVE MODAL: RESULT CLAIM DETAILS         -->
<!-- ========================================== -->
<div id="modalClaimDetails" class="fixed inset-0 z-50 hidden overflow-y-auto bg-gray-900/60 backdrop-blur-xs transition-opacity duration-200" onclick="if(event.target === this) closeClaimDetailsModal()">
    <div class="flex min-h-screen items-center justify-center p-3 sm:p-4 text-center">
        <div class="relative w-full max-w-md rounded-2xl bg-white text-left shadow-2xl transition-all border border-gray-100 overflow-hidden my-auto animate-in fade-in zoom-in-95 duration-150">
            <!-- Header -->
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between bg-white shrink-0">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center border border-emerald-100 shrink-0">
                        <i data-lucide="shield-check" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-gray-900 leading-tight">Result Claim Details</h3>
                        <p class="text-xs text-gray-500">Physical result pickup verification record</p>
                    </div>
                </div>
                <button type="button" onclick="closeClaimDetailsModal()" class="text-gray-400 hover:text-gray-600 p-1.5 rounded-lg hover:bg-gray-100 transition cursor-pointer">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <!-- Body -->
            <div class="p-5 space-y-3.5">
                <!-- Summary Card -->
                <div class="p-3.5 bg-emerald-50/80 border border-emerald-200/80 rounded-xl flex items-center justify-between gap-3 shadow-2xs">
                    <div class="flex items-center gap-2.5 min-w-0">
                        <div class="h-9 w-9 rounded-lg bg-emerald-100 border border-emerald-200 flex items-center justify-center text-emerald-700 shrink-0">
                            <i data-lucide="check-circle-2" class="w-5 h-5"></i>
                        </div>
                        <div class="min-w-0">
                            <span class="text-[10px] font-bold text-emerald-800 bg-emerald-100 px-2 py-0.5 rounded-md uppercase tracking-wide" id="detail_case_no">Case #—</span>
                            <p class="text-sm font-bold text-gray-900 mt-0.5 truncate" id="detail_pat_name">—</p>
                        </div>
                    </div>
                    <div class="text-right shrink-0">
                        <span class="text-xs font-bold text-emerald-800 block" id="detail_claimed_at">—</span>
                    </div>
                </div>

                <!-- Info List Card -->
                <div class="bg-gray-50/80 border border-gray-200/80 rounded-xl p-3.5 space-y-2.5">
                    <div class="flex items-center justify-between border-b border-gray-200/60 pb-2">
                        <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Claimed By</span>
                        <span class="text-xs sm:text-sm font-bold text-gray-900" id="detail_claimed_by">—</span>
                    </div>
                    <div class="flex items-center justify-between border-b border-gray-200/60 pb-2" id="detail_row_rel">
                        <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Relationship</span>
                        <span class="text-xs font-semibold text-gray-800 bg-white border border-gray-200 px-2 py-0.5 rounded-md shadow-2xs" id="detail_relationship">—</span>
                    </div>
                    <div class="flex items-center justify-between border-b border-gray-200/60 pb-2" id="detail_row_id">
                        <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">ID Presented</span>
                        <span class="text-xs font-semibold text-gray-800" id="detail_id_presented">—</span>
                    </div>
                    <div class="flex flex-col gap-1 pt-0.5" id="detail_row_notes">
                        <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Pickup Notes</span>
                        <p class="text-xs text-gray-700 bg-white p-2.5 rounded-lg border border-gray-200/80 italic leading-relaxed shadow-2xs" id="detail_notes">—</p>
                    </div>
                </div>

                <!-- Footer Actions -->
                <div class="pt-3 border-t border-gray-100 flex items-center justify-end">
                    <button type="button" onclick="closeClaimDetailsModal()" class="px-5 py-2 text-xs sm:text-sm font-semibold text-gray-700 bg-white hover:bg-gray-50 border border-gray-300 rounded-xl transition shadow-2xs cursor-pointer active:scale-95">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script
    src="<?= PROJECT_DIR ? '/' . PROJECT_DIR . '/' : '/' ?>views/pages/radtech/xray-patient-records.js?v=<?= time() ?>"></script>