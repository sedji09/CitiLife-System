<?php
/**
 * Payment Verifications View for Branch Admin
 */
?>



<?php if ($successMsg): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: <?= json_encode($successMsg) ?>,
                    showConfirmButton: false,
                    timer: 2500,
                    customClass: { popup: 'rounded-3xl border-0 shadow-2xl' }
                });
            } else if (typeof toast === 'function') {
                toast(<?= json_encode($successMsg) ?>, 'success');
            }
        });
    </script>
<?php endif; ?>

<?php if ($errorMsg): ?>
    <div class="mb-6 rounded-lg bg-red-50 p-4 border border-red-200 flex items-start gap-3">
        <i data-lucide="alert-circle" class="w-5 h-5 text-red-600 shrink-0"></i>
        <p class="text-sm text-red-800"><?= htmlspecialchars($errorMsg) ?></p>
    </div>
<?php endif; ?>

<!-- Header -->
<div class="flex items-center justify-between">
    <div>
        <h2 class="text-xl font-semibold text-gray-900">Payment Verifications</h2>
        <p class="text-sm text-gray-500 mt-1">Review and approve patient payments before proceeding to X-ray.</p>
    </div>
</div>

<!-- Tabs -->
<div class="mt-6 border-b border-gray-200">
    <nav class="flex gap-6">
        <a href="javascript:void(0)" onclick="switchTab('pending')" id="tab-pending"
            class="flex items-center gap-2 px-1 py-3 text-sm font-medium transition-all duration-200 <?= $activeTab === 'pending' ? 'text-red-600 border-b-2 border-red-600 active-tab' : 'text-gray-500 border-b-2 border-transparent hover:text-gray-700 hover:border-gray-300' ?>">
            Pending Verification
            <?php
            $pendingCountVal = count($pendingPayments);
            $pendingDisplay = $pendingCountVal > 99 ? '99+' : $pendingCountVal;
            ?>
            <span id="pendingBadgeCount"
                class="<?= $pendingCountVal > 0 ? '' : 'hidden' ?> tab-circle-badge bg-red-100 text-red-600 border border-red-200"
                style="width: 26px; height: 26px; min-width: 26px; min-height: 26px; border-radius: 9999px; display: inline-flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 700; line-height: 1; flex-shrink: 0;"
                title="<?= $pendingCountVal ?>">
                <?= $pendingDisplay ?>
            </span>
        </a>
        <a href="javascript:void(0)" onclick="switchTab('history')" id="tab-history"
            class="flex items-center gap-2 px-1 py-3 text-sm font-medium transition-all duration-200 <?= $activeTab === 'history' ? 'text-red-600 border-b-2 border-red-600 active-tab' : 'text-gray-500 border-b-2 border-transparent hover:text-gray-700 hover:border-gray-300' ?>">
            History
        </a>
    </nav>
</div>

<!-- Content -->
<div>

    <!-- Pending Tab -->
    <div id="content-pending" class="<?= $activeTab === 'pending' ? '' : 'hidden' ?>">

        <!-- Search and Filters -->
        <div class="mt-6 flex flex-col gap-4">
            <div class="flex gap-4 items-center w-full">
                <div class="relative w-full max-w-md">
                    <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"></i>
                    <input type="text" id="pendingSearchInput" placeholder="Search Request # or Patient Name..."
                        class="w-full pl-9 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-red-500/20 focus:border-red-500 outline-none transition-all">
                </div>

                <select id="pendingSortSelect"
                    class="w-48 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-red-500/20 focus:border-red-500 outline-none bg-white">
                    <option value="new">Newest First</option>
                    <option value="old">Oldest First</option>
                </select>
            </div>
        </div>
        <div class="mt-4 rounded-xl border border-gray-300 bg-white shadow-sm overflow-hidden">
            <div class="overflow-x-auto overflow-y-auto max-h-[600px]">
                <table class="w-full text-left text-sm relative">
                    <thead class="bg-gray-50 text-gray-600 font-semibold sticky top-0 z-10 border-b border-gray-200">
                        <tr>
                            <th class="px-3 py-3 font-semibold text-left whitespace-nowrap">Request / Patient</th>
                            <th class="px-3 py-3 font-semibold text-left whitespace-nowrap">Exam Type</th>
                            <th class="px-3 py-3 font-semibold text-left whitespace-nowrap">PhilHealth Number</th>
                            <th class="px-3 py-3 font-semibold text-left whitespace-nowrap">Amount & Ref #</th>
                            <th class="px-3 py-3 font-semibold text-left whitespace-nowrap">Date Submitted</th>
                            <th class="px-3 py-3 font-semibold text-left whitespace-nowrap">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-800 bg-white divide-y divide-gray-100" id="pendingTableBody">
                        <?php if (empty($pendingPayments)): ?>
                            <tr data-static-empty="1">
                                <td colspan="6" class="p-12 text-center text-gray-500">No pending payments to verify.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($pendingPayments as $payment): ?>
                                <tr class="hover:bg-gray-50 transition pending-row"
                                    data-search="<?= htmlspecialchars(strtolower($payment['request_number'] . ' ' . $payment['first_name'] . ' ' . $payment['last_name'])) ?>"
                                    data-request-number="<?= htmlspecialchars($payment['request_number']) ?>"
                                    data-id="<?= htmlspecialchars($payment['id']) ?>"
                                    data-date="<?= strtotime($payment['created_at']) ?>">
                                    <td class="py-3 px-3">
                                        <div class="font-medium text-gray-900"><?= htmlspecialchars($payment['request_number']) ?>
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            <?= htmlspecialchars($payment['first_name'] . ' ' . $payment['last_name']) ?></div>
                                    </td>
                                    <td class="py-3 px-3">
                                        <div class="text-sm font-medium text-gray-700">
                                            <?= htmlspecialchars($payment['exam_type']) ?></div>
                                    </td>
                                    <td class="py-3 px-3">
                                        <?php if (!empty($payment['philhealth_status']) && $payment['philhealth_status'] === 'With PhilHealth Card'): ?>
                                            <div class="text-sm font-medium text-gray-900">
                                                <?= htmlspecialchars($payment['philhealth_id'] ?: 'Card Holder') ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-xs text-gray-400 italic">None</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3 px-3">
                                        <div class="font-semibold text-red-600">
                                            ₱<?= number_format($payment['amount'], 2) ?></div>
                                        <div class="text-xs text-gray-500 mt-0.5">Method:
                                            <?= htmlspecialchars($payment['payment_method']) ?></div>
                                        <?php if ($payment['payment_method'] === 'GCash' && $payment['reference_number']): ?>
                                            <div class="text-xs text-gray-500 mt-0.5">Ref:
                                                <?= htmlspecialchars($payment['reference_number']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3 px-3 text-gray-500">
                                        <?= date('M d, Y h:i A', strtotime($payment['created_at'])) ?>
                                    </td>
                                    <td class="py-3 px-3 whitespace-nowrap">
                                        <div class="flex items-center gap-1.5">
                                            <?php if ($payment['payment_method'] === 'GCash'): ?>
                                                <button type="button" title="View Receipt"
                                                    onclick="viewReceipt('<?= htmlspecialchars($payment['proof_of_payment_path'] ?? '') ?>', '<?= htmlspecialchars($payment['reference_number'] ?? 'N/A') ?>', <?= (float) ($payment['original_amount'] ?? $payment['amount']) ?>, <?= (float) ($payment['discount_amount'] ?? 0) ?>, <?= (float) $payment['amount'] ?>, '<?= htmlspecialchars($payment['exam_type'] ?? 'Exam') ?>')"
                                                    class="inline-flex items-center justify-center p-1.5 rounded-md bg-blue-100 border border-blue-500 text-blue-600 hover:bg-blue-600 hover:text-white hover:border-blue-600 transition shadow-sm">
                                                    <i data-lucide="image" class="w-4 h-4"></i>
                                                </button>
                                            <?php endif; ?>
                                            <form method="POST" class="inline-block m-0">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="payment_id" value="<?= $payment['id'] ?>">
                                                <input type="hidden" name="action" value="verify">
                                                <button type="button" title="Verify Payment"
                                                    onclick="confirmAction(this.form, 'verify')"
                                                    class="inline-flex items-center justify-center p-1.5 rounded-md border border-green-500 bg-green-100 text-green-600 hover:bg-green-600 hover:text-white hover:border-green-600 transition shadow-sm cursor-pointer">
                                                    <i data-lucide="check" class="w-4 h-4 stroke-[2.5]"></i>
                                                </button>
                                            </form>
                                            <form method="POST" class="inline-block m-0">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="payment_id" value="<?= $payment['id'] ?>">
                                                <input type="hidden" name="action" value="reject">
                                                <button type="button" title="Reject Payment"
                                                    onclick="confirmAction(this.form, 'reject')"
                                                    class="inline-flex items-center justify-center p-1.5 rounded-md border border-red-500 bg-red-100 text-red-600 hover:bg-red-600 hover:text-white hover:border-red-600 transition shadow-sm cursor-pointer">
                                                    <i data-lucide="x" class="w-4 h-4 stroke-[2.5]"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="px-6 py-4 border-t border-gray-200 flex flex-col md:flex-row items-center justify-between gap-4 bg-gray-50/50"
                id="pending-pagination-container">
                <span class="text-sm text-gray-600" id="pending-pagination-info">
                    Showing page <span class="font-semibold text-gray-800">1</span> of <span
                        class="font-semibold text-gray-800">1</span>
                </span>
                <div class="flex items-center flex-wrap gap-1.5" id="pending-pagination-controls">
                    <!-- JS will render pagination here -->
                </div>
            </div>
        </div>
    </div>

    <!-- History Tab -->
    <div id="content-history" class="<?= $activeTab === 'history' ? '' : 'hidden' ?>">

        <!-- Search and Filters -->
        <div class="mt-6 flex flex-col gap-4">
            <div class="flex gap-4 items-center w-full">
                <div class="relative w-full max-w-md">
                    <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"></i>
                    <input type="text" id="historySearchInput" placeholder="Search Request # or Patient Name..."
                        class="w-full pl-9 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-red-500/20 focus:border-red-500 outline-none transition-all">
                </div>

                <select id="historySortSelect"
                    class="w-48 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-red-500/20 focus:border-red-500 outline-none bg-white">
                    <option value="new">Newest First</option>
                    <option value="old">Oldest First</option>
                </select>
            </div>
        </div>

        <div class="mt-4 rounded-xl border border-gray-300 bg-white shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-50 text-gray-600 font-semibold border-b border-gray-200">
                        <tr>
                            <th class="px-3 py-3 font-semibold text-left whitespace-nowrap">Request / Patient</th>
                            <th class="px-3 py-3 font-semibold text-left whitespace-nowrap">Exam Type</th>
                            <th class="px-3 py-3 font-semibold text-left whitespace-nowrap">PhilHealth Number</th>
                            <th class="px-3 py-3 font-semibold text-left whitespace-nowrap">Amount & Ref #</th>
                            <th class="px-3 py-3 font-semibold text-left whitespace-nowrap">Status</th>
                            <th class="px-3 py-3 font-semibold text-left whitespace-nowrap">Date Processed</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-800 bg-white divide-y divide-gray-100" id="historyTableBody">
                        <?php if (empty($paymentHistory)): ?>
                            <tr data-static-empty="1">
                                <td colspan="6" class="p-12 text-center text-gray-500">No payment history found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($paymentHistory as $payment): ?>
                                <tr class="hover:bg-gray-50 transition history-row"
                                    data-search="<?= htmlspecialchars(strtolower($payment['request_number'] . ' ' . $payment['first_name'] . ' ' . $payment['last_name'])) ?>"
                                    data-request-number="<?= htmlspecialchars($payment['request_number']) ?>"
                                    data-id="<?= htmlspecialchars($payment['id']) ?>"
                                    data-date="<?= strtotime($payment['updated_at']) ?>">
                                    <td class="py-3 px-3">
                                        <div class="font-medium text-gray-900"><?= htmlspecialchars($payment['request_number']) ?>
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            <?= htmlspecialchars($payment['first_name'] . ' ' . $payment['last_name']) ?></div>
                                    </td>
                                    <td class="py-3 px-3">
                                        <div class="text-sm font-medium text-gray-700">
                                            <?= htmlspecialchars($payment['exam_type']) ?></div>
                                    </td>
                                    <td class="py-3 px-3">
                                        <?php if (!empty($payment['philhealth_status']) && $payment['philhealth_status'] === 'With PhilHealth Card'): ?>
                                            <div class="text-sm font-medium text-gray-900">
                                                <?= htmlspecialchars($payment['philhealth_id'] ?: 'Card Holder') ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-xs text-gray-400 italic">None</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3 px-3">
                                        <div class="font-semibold text-gray-900">
                                            ₱<?= number_format($payment['amount'], 2) ?></div>
                                        <div class="text-xs text-gray-500 mt-0.5">Method:
                                            <?= htmlspecialchars($payment['payment_method']) ?></div>
                                        <?php if ($payment['payment_method'] === 'GCash' && $payment['reference_number']): ?>
                                            <div class="text-xs text-gray-500 mt-0.5">Ref:
                                                <?= htmlspecialchars($payment['reference_number']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3 px-3">
                                        <?php if ($payment['status'] === 'Verified'): ?>
                                            <span
                                                class="inline-flex items-center gap-1.5 rounded-full bg-green-50 px-2.5 py-1 text-xs font-medium text-green-700 border border-green-400">
                                                <i data-lucide="check-circle" class="w-3.5 h-3.5"></i> Verified
                                            </span>
                                        <?php else: ?>
                                            <span
                                                class="inline-flex items-center gap-1.5 rounded-full bg-red-50 px-2.5 py-1 text-xs font-medium text-red-700 border border-red-400">
                                                <i data-lucide="x-circle" class="w-3.5 h-3.5"></i> Rejected
                                            </span>
                                            <?php if (!empty($payment['rejection_reason'])): ?>
                                                <div class="text-xs text-red-600 mt-1.5 max-w-xs break-words" title="<?= htmlspecialchars($payment['rejection_reason']) ?>">
                                                    <span class="font-semibold">Reason:</span> <?= htmlspecialchars($payment['rejection_reason']) ?>
                                                </div>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 text-gray-500">
                                        <?= date('M d, Y h:i A', strtotime($payment['updated_at'])) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="px-6 py-4 border-t border-gray-200 flex flex-col md:flex-row items-center justify-between gap-4 bg-gray-50/50"
                id="history-pagination-container">
                <span class="text-sm text-gray-600" id="history-pagination-info">
                    Showing page <span class="font-semibold text-gray-800">1</span> of <span
                        class="font-semibold text-gray-800">1</span>
                </span>
                <div class="flex items-center flex-wrap gap-1.5" id="history-pagination-controls">
                    <!-- JS will render pagination here -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Receipt Modal -->
<div id="receiptModal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-gray-900/70 backdrop-blur-sm"
    aria-labelledby="modal-title" role="dialog" aria-modal="true" onclick="if(event.target === this) closeReceiptModal()">
    <div class="flex min-h-screen items-center justify-center p-3 sm:p-4 md:p-6 text-center">
        <div
            class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all w-full max-w-5xl flex flex-col"
            style="max-height: 90vh;">

            <!-- Header -->
            <div class="px-6 py-3.5 border-b border-gray-100 flex items-center justify-between bg-white shrink-0">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                        <i data-lucide="receipt" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h3 class="text-base sm:text-lg font-bold text-gray-900 leading-tight" id="modal-title">
                            Payment Receipt Verification
                        </h3>
                        <p class="text-xs text-gray-500">Cross-check patient payment details with uploaded proof</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <a id="modal-receipt-full-link" href="#" target="_blank" rel="noopener noreferrer"
                        class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-blue-700 bg-blue-50 hover:bg-blue-100 rounded-lg transition border border-blue-200"
                        title="Open full image in new tab">
                        <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                        <span>Open Full Image</span>
                    </a>
                    <button type="button" onclick="closeReceiptModal()"
                        class="text-gray-400 hover:text-gray-600 p-1.5 rounded-lg hover:bg-gray-100 transition cursor-pointer">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>
            </div>

            <!-- Content Body (2-Column Grid on Desktop) -->
            <div class="grid grid-cols-1 md:grid-cols-12 min-h-0 bg-gray-50 overflow-hidden flex-1" style="min-height: 0;">
                
                <!-- Left Column: Receipt Image Preview (7 cols on desktop) -->
                <div class="md:col-span-7 flex flex-col items-center justify-center p-3 sm:p-4 min-h-[350px] md:min-h-0 relative overflow-hidden bg-slate-100/70 border-b md:border-b-0 border-gray-200"
                    style="background-color: #f1f5f9;">
                    <div class="w-full h-full flex items-center justify-center overflow-auto rounded-xl p-2"
                        style="max-height: 68vh; scrollbar-width: thin; scrollbar-color: #cbd5e1 transparent;">
                        <img id="modal-receipt-img" src="" alt="Payment Receipt" 
                            class="rounded-xl shadow-md bg-white border border-gray-200"
                            style="max-height: 65vh; max-width: 100%; width: auto; height: auto; object-fit: contain; display: block; margin: 0 auto;">
                    </div>
                    <div class="mt-2 text-center text-[11px] text-gray-500 shrink-0">
                        <span>Tip: Click "Open Full Image" at top right to view original resolution</span>
                    </div>
                </div>

                <!-- Right Column: Verification Details Panel (5 cols on desktop) -->
                <div class="md:col-span-5 p-5 sm:p-6 flex flex-col justify-between overflow-y-auto bg-white border-t md:border-t-0 md:border-l border-gray-200 min-h-0 space-y-4"
                    style="scrollbar-width: thin; scrollbar-color: #cbd5e1 transparent;">
                    
                    <div class="space-y-4">
                        <!-- Reference Number Card -->
                        <div class="bg-gradient-to-br from-blue-50 to-indigo-50/60 border border-blue-100 rounded-xl p-4 shadow-xs">
                            <div class="flex items-center justify-between mb-1.5">
                                <span class="text-[11px] font-bold text-blue-700 uppercase tracking-wider">
                                    Provided Reference No.
                                </span>
                                <button type="button" onclick="copyReceiptRef()" 
                                    class="inline-flex items-center gap-1 text-[11px] font-medium text-blue-600 hover:text-blue-800 bg-white px-2 py-0.5 rounded border border-blue-200 shadow-2xs hover:bg-blue-50 transition cursor-pointer"
                                    id="copyRefBtn" title="Copy reference number">
                                    <i data-lucide="copy" class="w-3 h-3"></i> <span id="copyRefText">Copy</span>
                                </button>
                            </div>
                            <strong id="modal-ref-number" class="text-xl sm:text-2xl font-black text-blue-950 font-mono tracking-wider block break-all select-all"></strong>
                            <div class="mt-2 pt-2 border-t border-blue-100/80 flex items-center gap-1.5 text-xs text-blue-700">
                                <i data-lucide="info" class="w-3.5 h-3.5 shrink-0 text-blue-600"></i>
                                <span>Verify this matches the reference in the receipt.</span>
                            </div>
                        </div>

                        <!-- Price Breakdown in Modal -->
                        <div class="bg-white border border-gray-200 rounded-xl overflow-hidden shadow-xs">
                            <div class="bg-gray-50 px-4 py-2.5 border-b border-gray-100 flex items-center justify-between text-xs font-bold text-gray-700 tracking-wider">
                                <div class="flex items-center gap-1.5">
                                    <i data-lucide="calculator" class="w-3.5 h-3.5 text-gray-500"></i>
                                    <span>PAYMENT BREAKDOWN</span>
                                </div>
                            </div>
                            <div class="p-4 space-y-2.5">
                                <div class="flex items-center justify-between text-gray-700 text-sm font-semibold"
                                    id="receiptModalOrigRow">
                                    <span id="receiptModalExamType" class="text-gray-600 font-medium">Chest PA</span>
                                    <span id="receiptModalOrigAmount" class="font-bold text-gray-900">₱0.00</span>
                                </div>
                                <div class="flex items-center justify-between pl-3 text-xs text-emerald-600" id="receiptModalDiscRow">
                                    <span class="font-medium">PhilHealth Discount</span>
                                    <span id="receiptModalDiscAmount"
                                        class="font-bold text-emerald-600">-₱0.00</span>
                                </div>
                            </div>
                            <div class="bg-red-50/60 px-4 py-3 border-t border-red-100 flex items-center justify-between">
                                <span class="font-extrabold text-gray-900 text-sm tracking-wide">Total Amount Due</span>
                                <span id="receiptModalNetAmount" class="font-black text-red-600 text-base sm:text-lg">₱0.00</span>
                            </div>
                        </div>

                        <!-- Important Verification Note -->
                        <div class="bg-amber-50/80 border border-amber-200 rounded-xl p-3.5 text-xs text-amber-900 flex items-start gap-2.5">
                            <i data-lucide="alert-circle" class="w-4 h-4 text-amber-600 shrink-0 mt-0.5"></i>
                            <div class="space-y-0.5">
                                <span class="font-bold text-amber-950 uppercase tracking-wide text-[11px] block">Important Note</span>
                                <p class="text-amber-800 leading-relaxed text-xs">
                                    Make sure the <strong>Reference Number</strong> and <strong>Amount</strong> match the receipt before approving.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Footer Buttons inside side-panel -->
                    <div class="pt-3 border-t border-gray-100 flex items-center justify-end gap-2">
                        <button type="button" onclick="closeReceiptModal()"
                            class="w-full inline-flex justify-center rounded-xl bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-xs ring-1 ring-inset ring-gray-300 hover:bg-gray-50 transition cursor-pointer">
                            Close
                        </button>
                    </div>

                </div>

            </div>

        </div>
    </div>
</div>

<script>
    function switchTab(tabId) {
        document.getElementById('content-pending').classList.add('hidden');
        document.getElementById('content-history').classList.add('hidden');

        document.getElementById('tab-pending').className = 'flex items-center gap-2 px-1 py-3 text-sm font-medium transition-all duration-200 text-gray-500 border-b-2 border-transparent hover:text-gray-700 hover:border-gray-300';
        document.getElementById('tab-history').className = 'flex items-center gap-2 px-1 py-3 text-sm font-medium transition-all duration-200 text-gray-500 border-b-2 border-transparent hover:text-gray-700 hover:border-gray-300';

        document.getElementById('content-' + tabId).classList.remove('hidden');
        document.getElementById('tab-' + tabId).className = 'flex items-center gap-2 px-1 py-3 text-sm font-medium transition-all duration-200 text-red-600 border-b-2 border-red-600 active-tab';

        // Update URL slightly without reloading to remember tab
        const url = new URL(window.location);
        if (tabId === 'history') {
            url.searchParams.set('tab', 'history');
        } else {
            url.searchParams.delete('tab');
            url.searchParams.delete('search');
            url.searchParams.delete('page_num');
        }
        window.history.replaceState({}, '', url);
    }

    function escapeHtml(str) {
        if (str === null || str === undefined) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function formatCurrency(amount) {
        return Number(amount || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function createPendingRowElement(payment) {
        const tr = document.createElement('tr');
        tr.className = 'hover:bg-gray-50 transition pending-row';
        tr.dataset.search = ((payment.request_number || '') + ' ' + (payment.first_name || '') + ' ' + (payment.last_name || '')).toLowerCase();
        tr.dataset.requestNumber = payment.request_number || '';
        tr.dataset.id = payment.id || '';
        tr.dataset.date = payment.timestamp || 0;

        const philhealthHtml = (!payment.philhealth_status || payment.philhealth_status !== 'With PhilHealth Card')
            ? '<span class="text-xs text-gray-400 italic">None</span>'
            : `<div class="text-sm font-medium text-gray-900">${escapeHtml(payment.philhealth_id || 'Card Holder')}</div>`;

        const gcashRefHtml = (payment.payment_method === 'GCash' && payment.reference_number)
            ? `<div class="text-xs text-gray-500 mt-1">Ref: ${escapeHtml(payment.reference_number)}</div>`
            : '';

        let gcashBtnHtml = '';
        if (payment.payment_method === 'GCash') {
            const proof = escapeHtml(payment.proof_of_payment_path || '');
            const ref = escapeHtml(payment.reference_number || 'N/A');
            const orig = parseFloat(payment.original_amount || payment.amount || 0);
            const disc = parseFloat(payment.discount_amount || 0);
            const net = parseFloat(payment.amount || 0);
            const exam = escapeHtml(payment.exam_type || 'Exam');
            gcashBtnHtml = `<button type="button" title="View Receipt" onclick="viewReceipt('${proof}', '${ref}', ${orig}, ${disc}, ${net}, '${exam}')" class="inline-flex items-center justify-center w-7 h-7 rounded-md bg-slate-50 border border-slate-300 text-slate-600 hover:bg-slate-100 hover:border-slate-400 hover:text-slate-700 transition">
                <i data-lucide="image" class="w-4 h-4"></i>
            </button>`;
        }

        tr.innerHTML = `
            <td class="py-3 px-3">
                <div class="font-bold text-gray-900">${escapeHtml(payment.request_number)}</div>
                <div class="text-xs text-gray-500">${escapeHtml((payment.first_name || '') + ' ' + (payment.last_name || ''))}</div>
            </td>
            <td class="py-3 px-3">
                <div class="text-sm font-medium text-gray-700">${escapeHtml(payment.exam_type)}</div>
            </td>
            <td class="py-3 px-3">
                ${philhealthHtml}
            </td>
            <td class="py-3 px-3">
                <div class="font-bold text-red-600">₱${formatCurrency(payment.amount)}</div>
                <div class="text-xs text-gray-500 mt-1">Method: ${escapeHtml(payment.payment_method)}</div>
                ${gcashRefHtml}
            </td>
            <td class="py-3 px-3 text-gray-500">
                ${escapeHtml(payment.created_at_formatted)}
            </td>
            <td class="py-3 px-3">
                <div class="flex items-center gap-2">
                    ${gcashBtnHtml}
                    <form method="POST" class="inline-block m-0">
                        <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="payment_id" value="${payment.id}">
                        <input type="hidden" name="action" value="verify">
                        <button type="button" title="Verify Payment" onclick="confirmAction(this.form, 'verify')" class="inline-flex items-center justify-center w-7 h-7 rounded-md border border-green-500 bg-green-100 text-green-600 hover:bg-green-600 hover:text-white hover:border-green-600 transition cursor-pointer">
                            <i data-lucide="check" class="w-4 h-4 stroke-[2.5]"></i>
                        </button>
                    </form>
                    <form method="POST" class="inline-block m-0">
                        <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="payment_id" value="${payment.id}">
                        <input type="hidden" name="action" value="reject">
                        <button type="button" title="Reject Payment" onclick="confirmAction(this.form, 'reject')" class="inline-flex items-center justify-center w-7 h-7 rounded-md border border-red-500 bg-red-100 text-red-600 hover:bg-red-600 hover:text-white hover:border-red-600 transition cursor-pointer">
                            <i data-lucide="x" class="w-4 h-4 stroke-[2.5]"></i>
                        </button>
                    </form>
                </div>
            </td>
        `;
        return tr;
    }

    function createHistoryRowElement(payment) {
        const tr = document.createElement('tr');
        tr.className = 'hover:bg-gray-50 transition history-row';
        tr.dataset.search = ((payment.request_number || '') + ' ' + (payment.first_name || '') + ' ' + (payment.last_name || '')).toLowerCase();
        tr.dataset.requestNumber = payment.request_number || '';
        tr.dataset.id = payment.id || '';
        tr.dataset.date = payment.timestamp || 0;

        const philhealthHtml = (!payment.philhealth_status || payment.philhealth_status !== 'With PhilHealth Card')
            ? '<span class="text-xs text-gray-400 italic">None</span>'
            : `<div class="text-sm font-medium text-gray-900">${escapeHtml(payment.philhealth_id || 'Card Holder')}</div>`;

        const gcashRefHtml = (payment.payment_method === 'GCash' && payment.reference_number)
            ? `<div class="text-xs text-gray-500 mt-1">Ref: ${escapeHtml(payment.reference_number)}</div>`
            : '';

        const statusBadge = (payment.status === 'Verified')
            ? `<span class="inline-flex items-center gap-1.5 rounded-full bg-green-50 px-2.5 py-1 text-xs font-medium text-green-700 border border-green-400"><i data-lucide="check-circle" class="w-3.5 h-3.5"></i> Verified</span>`
            : `<span class="inline-flex items-center gap-1.5 rounded-full bg-red-50 px-2.5 py-1 text-xs font-medium text-red-700 border border-red-400"><i data-lucide="x-circle" class="w-3.5 h-3.5"></i> Rejected</span>`
            + (payment.rejection_reason ? `<div class="text-xs text-red-600 mt-1.5 max-w-xs break-words" title="${escapeHtml(payment.rejection_reason)}"><span class="font-semibold">Reason:</span> ${escapeHtml(payment.rejection_reason)}</div>` : '');

        tr.innerHTML = `
            <td class="py-3 px-3">
                <div class="font-bold text-gray-900">${escapeHtml(payment.request_number)}</div>
                <div class="text-xs text-gray-500">${escapeHtml((payment.first_name || '') + ' ' + (payment.last_name || ''))}</div>
            </td>
            <td class="py-3 px-3">
                <div class="text-sm font-medium text-gray-700">${escapeHtml(payment.exam_type)}</div>
            </td>
            <td class="py-3 px-3">
                ${philhealthHtml}
            </td>
            <td class="py-3 px-3">
                <div class="font-bold text-gray-900">₱${formatCurrency(payment.amount)}</div>
                <div class="text-xs text-gray-500 mt-1">Method: ${escapeHtml(payment.payment_method)}</div>
                ${gcashRefHtml}
            </td>
            <td class="py-3 px-3">
                ${statusBadge}
            </td>
            <td class="py-3 px-3 text-gray-500">
                ${escapeHtml(payment.updated_at_formatted)}
            </td>
        `;
        return tr;
    }

    // ── Search, Sort & JS Pagination Logic ──
    document.addEventListener('DOMContentLoaded', () => {
        function initTable(prefix, rowClass) {
            const searchInput = document.getElementById(prefix + 'SearchInput');
            const sortSelect = document.getElementById(prefix + 'SortSelect');
            const tableBody = document.getElementById(prefix + 'TableBody');
            const paginationContainer = document.getElementById(prefix + '-pagination-container');
            const paginationControls = document.getElementById(prefix + '-pagination-controls');
            const paginationInfo = document.getElementById(prefix + '-pagination-info');

            if (!tableBody) return null;

            // Exclude static PHP empty-state rows from JS management
            let allRows = Array.from(tableBody.querySelectorAll('.' + rowClass));
            let hasNoData = allRows.length === 0; // truly no records in DB
            let filteredRows = [];
            let currentPage = 1;
            const itemsPerPage = 7;

            function updateTable() {
                // If there are no rows at all, leave the static empty state and show pagination as disabled
                if (hasNoData || allRows.length === 0) {
                    tableBody.innerHTML = `<tr><td colspan="6" class="p-12 text-center text-gray-500">${prefix === 'pending' ? 'No pending payments to verify.' : 'No payment history found.'}</td></tr>`;
                    if (paginationContainer) paginationContainer.style.display = 'flex';
                    if (paginationInfo) paginationInfo.innerHTML = 'No records';
                    renderPagination(1);
                    return;
                }

                const searchTerm = searchInput ? searchInput.value.toLowerCase().trim() : '';
                const sortOrder = sortSelect ? sortSelect.value : 'new';

                // Filter
                filteredRows = allRows.filter(row => {
                    const searchData = row.dataset.search || '';
                    return searchData.includes(searchTerm);
                });

                // Sort
                filteredRows.sort((a, b) => {
                    const dateA = parseInt(a.dataset.date) || 0;
                    const dateB = parseInt(b.dataset.date) || 0;
                    return sortOrder === 'new' ? (dateB - dateA) : (dateA - dateB);
                });

                // Pagination Calculation
                const totalPages = Math.max(1, Math.ceil(filteredRows.length / itemsPerPage));
                if (currentPage > totalPages) currentPage = totalPages;
                if (currentPage < 1) currentPage = 1;

                // Render Rows
                tableBody.innerHTML = '';

                if (filteredRows.length === 0) {
                    tableBody.innerHTML = '<tr><td colspan="6" class="p-12 text-center text-gray-500">No records match your filters.</td></tr>';
                    if (paginationContainer) paginationContainer.style.display = 'flex';
                    if (paginationInfo) paginationInfo.innerHTML = 'No records';
                    renderPagination(1);
                    return;
                }

                const startIndex = (currentPage - 1) * itemsPerPage;
                const endIndex = startIndex + itemsPerPage;
                const currentRows = filteredRows.slice(startIndex, endIndex);

                currentRows.forEach(row => {
                    tableBody.appendChild(row);
                });

                if (window.lucide) {
                    lucide.createIcons();
                }

                // Update Pagination Info
                if (paginationContainer) paginationContainer.style.display = 'flex';
                if (paginationInfo) {
                    paginationInfo.innerHTML = `Showing page <span class="font-semibold text-gray-800">${currentPage}</span> of <span class="font-semibold text-gray-800">${totalPages}</span>`;
                }

                renderPagination(totalPages);
            }

            function renderPagination(totalPages) {
                if (!paginationControls) return;
                paginationControls.innerHTML = '';

                const createBtn = (label, pageNum, disabled = false, isActive = false) => {
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.innerHTML = label;
                    if (isActive) {
                        btn.className = 'px-3 py-1.5 rounded-lg bg-red-600 text-xs font-bold text-white shadow-sm border border-red-600';
                    } else if (disabled) {
                        btn.className = 'px-3 py-1.5 rounded-lg border border-gray-200 bg-gray-50 text-xs font-semibold text-gray-400 cursor-not-allowed shadow-sm opacity-60';
                        btn.disabled = true;
                    } else {
                        btn.className = 'px-3 py-1.5 rounded-lg border border-gray-300 bg-white text-xs font-semibold text-gray-700 hover:bg-red-50 hover:text-red-600 hover:border-red-200 focus:outline-none focus:ring-2 focus:ring-red-400 transition shadow-sm';
                        btn.onclick = () => {
                            currentPage = pageNum;
                            updateTable();
                        };
                    }
                    return btn;
                };

                const createEllipsis = () => {
                    const span = document.createElement('span');
                    span.className = 'px-2 py-1 text-xs text-gray-400 font-semibold select-none';
                    span.textContent = '...';
                    return span;
                };

                // First and Prev
                paginationControls.appendChild(createBtn('&laquo; First', 1, currentPage <= 1));
                paginationControls.appendChild(createBtn('&lsaquo; Back', currentPage - 1, currentPage <= 1));

                // Numbers
                if (totalPages <= 7) {
                    for (let i = 1; i <= totalPages; i++) {
                        paginationControls.appendChild(createBtn(i, i, false, i === currentPage));
                    }
                } else {
                    if (currentPage <= 4) {
                        for (let i = 1; i <= 5; i++) {
                            paginationControls.appendChild(createBtn(i, i, false, i === currentPage));
                        }
                        paginationControls.appendChild(createEllipsis());
                        paginationControls.appendChild(createBtn(totalPages, totalPages, false, false));
                    } else if (currentPage >= totalPages - 3) {
                        paginationControls.appendChild(createBtn(1, 1, false, false));
                        paginationControls.appendChild(createEllipsis());
                        for (let i = totalPages - 4; i <= totalPages; i++) {
                            paginationControls.appendChild(createBtn(i, i, false, i === currentPage));
                        }
                    } else {
                        paginationControls.appendChild(createBtn(1, 1, false, false));
                        paginationControls.appendChild(createEllipsis());
                        paginationControls.appendChild(createBtn(currentPage - 1, currentPage - 1, false, false));
                        paginationControls.appendChild(createBtn(currentPage, currentPage, false, true));
                        paginationControls.appendChild(createBtn(currentPage + 1, currentPage + 1, false, false));
                        paginationControls.appendChild(createEllipsis());
                        paginationControls.appendChild(createBtn(totalPages, totalPages, false, false));
                    }
                }

                // Next and Last
                paginationControls.appendChild(createBtn('Next &rsaquo;', currentPage + 1, currentPage >= totalPages));
                paginationControls.appendChild(createBtn('Last &raquo;', totalPages, currentPage >= totalPages));
            }

            function setRows(newRows) {
                allRows = newRows;
                hasNoData = allRows.length === 0;
                updateTable();
            }

            if (searchInput) searchInput.addEventListener('input', () => {
                currentPage = 1;
                updateTable();
            });

            if (sortSelect) sortSelect.addEventListener('change', () => {
                currentPage = 1;
                updateTable();
            });

            // Initial setup
            updateTable();

            return {
                updateTable,
                setRows,
                getAllRows: () => allRows,
                setPage: (p) => { currentPage = p; updateTable(); },
                getItemsPerPage: () => itemsPerPage
            };
        }

        const historyTable = initTable('history', 'history-row');
        const pendingTable = initTable('pending', 'pending-row');

        function handleHighlight(targetId) {
            if (!targetId) {
                const params = new URLSearchParams(window.location.search);
                targetId = params.get('highlight') || params.get('highlight_case') || params.get('highlight_req');
            }
            if (!targetId) return false;

            const hlLower = String(targetId).trim().toLowerCase();

            // Check Pending rows first
            const pendingRows = pendingTable.getAllRows();
            let targetIdx = pendingRows.findIndex(row => {
                const reqNum = (row.dataset.requestNumber || '').toLowerCase();
                const id = (row.dataset.id || '').toLowerCase();
                const search = (row.dataset.search || '').toLowerCase();
                return reqNum === hlLower || id === hlLower || (hlLower && reqNum.includes(hlLower)) || search.includes(hlLower);
            });

            if (targetIdx !== -1) {
                switchTab('pending');
                const pendingSearch = document.getElementById('pendingSearchInput');
                if (pendingSearch) pendingSearch.value = '';

                const targetRow = pendingRows[targetIdx];
                const pageNum = Math.floor(targetIdx / pendingTable.getItemsPerPage()) + 1;
                pendingTable.setPage(pageNum);

                setTimeout(() => {
                    targetRow.style.display = '';
                    targetRow.scrollIntoView({ behavior: 'smooth', block: 'center' });

                    targetRow.classList.add('transition-all', 'duration-300', 'ring-2', 'ring-amber-400', 'ring-offset-1');
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
                                    targetRow.classList.remove('ring-2', 'ring-amber-400', 'ring-offset-1');
                                }, 400);
                            }, 300);
                        }, 300);
                    }, 200);

                    const existingBanner = document.getElementById('highlight-banner');
                    if (existingBanner) existingBanner.remove();

                    const banner = document.createElement('div');
                    banner.id = 'highlight-banner';
                    banner.innerHTML = `<div style="display:flex;align-items:center;gap:0.5rem;"><svg xmlns='http://www.w3.org/2000/svg' width='18' height='18' fill='none' stroke='currentColor' stroke-width='2' viewBox='0 0 24 24'><circle cx='12' cy='12' r='10'/><line x1='12' y1='8' x2='12' y2='12'/><line x1='12' y1='16' x2='12.01' y2='16'/></svg><span>Navigated from notification — Request <strong>${targetId}</strong> is highlighted below.</span></div>`;
                    banner.style.cssText = 'margin:1rem 0;padding:0.65rem 1rem;border-radius:0.75rem;background:#fefce8;border:1px solid #fde047;color:#854d0e;font-size:0.875rem;font-weight:500;display:flex;align-items:center;gap:0.5rem;box-shadow:0 2px 8px rgba(245,158,11,0.08);';
                    const header = document.querySelector('h2');
                    if (header && header.parentElement) {
                        header.parentElement.insertAdjacentElement('afterend', banner);
                    }
                    setTimeout(() => {
                        banner.style.transition = 'opacity 0.5s';
                        banner.style.opacity = '0';
                        setTimeout(() => banner.remove(), 500);
                    }, 6000);
                }, 100);

                return true;
            }

            // Check History rows
            const historyRows = historyTable.getAllRows();
            let histIdx = historyRows.findIndex(row => {
                const reqNum = (row.dataset.requestNumber || '').toLowerCase();
                const id = (row.dataset.id || '').toLowerCase();
                const search = (row.dataset.search || '').toLowerCase();
                return reqNum === hlLower || id === hlLower || (hlLower && reqNum.includes(hlLower)) || search.includes(hlLower);
            });

            if (histIdx !== -1) {
                switchTab('history');
                const historySearch = document.getElementById('historySearchInput');
                if (historySearch) historySearch.value = '';

                const targetRow = historyRows[histIdx];
                const pageNum = Math.floor(histIdx / historyTable.getItemsPerPage()) + 1;
                historyTable.setPage(pageNum);

                setTimeout(() => {
                    targetRow.style.display = '';
                    targetRow.scrollIntoView({ behavior: 'smooth', block: 'center' });

                    targetRow.classList.add('transition-all', 'duration-300', 'ring-2', 'ring-amber-400', 'ring-offset-1');
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
                                    targetRow.classList.remove('ring-2', 'ring-amber-400', 'ring-offset-1');
                                }, 400);
                            }, 300);
                        }, 300);
                    }, 200);

                    const existingBanner = document.getElementById('highlight-banner');
                    if (existingBanner) existingBanner.remove();

                    const banner = document.createElement('div');
                    banner.id = 'highlight-banner';
                    banner.innerHTML = `<div style="display:flex;align-items:center;gap:0.5rem;"><svg xmlns='http://www.w3.org/2000/svg' width='18' height='18' fill='none' stroke='currentColor' stroke-width='2' viewBox='0 0 24 24'><circle cx='12' cy='12' r='10'/><line x1='12' y1='8' x2='12' y2='12'/><line x1='12' y1='16' x2='12.01' y2='16'/></svg><span>Navigated from notification — Request <strong>${targetId}</strong> is highlighted below.</span></div>`;
                    banner.style.cssText = 'margin:1rem 0;padding:0.65rem 1rem;border-radius:0.75rem;background:#fefce8;border:1px solid #fde047;color:#854d0e;font-size:0.875rem;font-weight:500;display:flex;align-items:center;gap:0.5rem;box-shadow:0 2px 8px rgba(245,158,11,0.08);';
                    const header = document.querySelector('h2');
                    if (header && header.parentElement) {
                        header.parentElement.insertAdjacentElement('afterend', banner);
                    }
                    setTimeout(() => {
                        banner.style.transition = 'opacity 0.5s';
                        banner.style.opacity = '0';
                        setTimeout(() => banner.remove(), 500);
                    }, 6000);
                }, 100);

                return true;
            }

            return false;
        }

        window.handlePageHighlight = handleHighlight;
        handleHighlight();

        // ── Real-Time Polling (Every 3 seconds) ──
        let lastPendingHash = '';
        let lastHistoryHash = '';
        let isPolling = false;

        async function pollPayments() {
            if (isPolling) return;
            // Skip replacing table if user is currently interacting with action or receipt modal
            const isSwalOpen = !!document.querySelector('.swal2-container');
            const receiptModal = document.getElementById('receiptModal');
            const isReceiptOpen = receiptModal && !receiptModal.classList.contains('hidden');

            isPolling = true;
            try {
                const fetchUrl = '<?= url("payment-verifications?ajax=1") ?>&t=' + Date.now();
                const res = await fetch(fetchUrl, { cache: 'no-store' });
                if (!res.ok) return;
                const data = await res.json();
                if (!data || !data.success) return;

                // Check pending updates
                const pendingHash = JSON.stringify(data.pending);
                if (lastPendingHash === '') {
                    lastPendingHash = pendingHash;
                } else if (pendingHash !== lastPendingHash) {
                    if (!isSwalOpen && !isReceiptOpen) {
                        lastPendingHash = pendingHash;
                        if (pendingTable) {
                            const newPendingRows = (data.pending || []).map(createPendingRowElement);
                            pendingTable.setRows(newPendingRows);
                        }
                    }
                }

                // Check history updates
                const historyHash = JSON.stringify(data.history);
                if (lastHistoryHash === '') {
                    lastHistoryHash = historyHash;
                } else if (historyHash !== lastHistoryHash) {
                    if (!isSwalOpen && !isReceiptOpen) {
                        lastHistoryHash = historyHash;
                        if (historyTable) {
                            const newHistoryRows = (data.history || []).map(createHistoryRowElement);
                            historyTable.setRows(newHistoryRows);
                        }
                    }
                }

                // Update tab badge count
                const badge = document.getElementById('pendingBadgeCount');
                if (badge) {
                    const count = parseInt(data.pendingCount) || 0;
                    badge.textContent = count > 99 ? '99+' : count;
                    badge.title = count;
                    if (count > 0) {
                        badge.classList.remove('hidden');
                    } else {
                        badge.classList.add('hidden');
                    }
                }
            } catch (err) {
                console.error('Error polling payments:', err);
            } finally {
                isPolling = false;
            }
        }

        setInterval(pollPayments, 3000);
    });

    function viewReceipt(path, refNumber, origAmount, discAmount, netAmount, examType) {
        if (!path) {
            alert('No receipt image available.');
            return;
        }

        let base = '<?= (defined("PROJECT_DIR") && PROJECT_DIR !== "") ? "/" . trim(PROJECT_DIR, "/") . "/" : "/" ?>';
        let cleanPath = path.startsWith('/') ? path.substring(1) : path;
        let imageSrc = base + cleanPath;
        
        document.getElementById('modal-receipt-img').src = imageSrc;
        const fullLink = document.getElementById('modal-receipt-full-link');
        if (fullLink) {
            fullLink.href = imageSrc;
        }

        document.getElementById('modal-ref-number').textContent = refNumber || 'N/A';
        document.getElementById('receiptModalExamType').textContent = examType || 'Exam';

        const orig = parseFloat(origAmount || netAmount || 0);
        const disc = parseFloat(discAmount || 0);
        const net = parseFloat(netAmount || 0);

        document.getElementById('receiptModalOrigAmount').textContent = '₱' + orig.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        document.getElementById('receiptModalDiscAmount').textContent = '-₱' + disc.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        document.getElementById('receiptModalNetAmount').textContent = '₱' + net.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        const discRow = document.getElementById('receiptModalDiscRow');
        if (disc > 0) {
            if (discRow) discRow.classList.remove('hidden');
        } else {
            if (discRow) discRow.classList.add('hidden');
        }

        document.getElementById('receiptModal').classList.remove('hidden');
        if (window.lucide) {
            lucide.createIcons();
        }
    }

    function closeReceiptModal() {
        document.getElementById('receiptModal').classList.add('hidden');
    }

    function copyReceiptRef() {
        const refText = (document.getElementById('modal-ref-number').textContent || '').trim();
        if (!refText || refText === 'N/A') return;
        
        navigator.clipboard.writeText(refText).then(() => {
            const textSpan = document.getElementById('copyRefText');
            if (textSpan) {
                textSpan.textContent = 'Copied!';
                setTimeout(() => {
                    textSpan.textContent = 'Copy';
                }, 1500);
            }
        }).catch(() => {});
    }

    function confirmAction(form, action) {
        // Ensure CSRF token is attached to the form
        const metaCsrf = document.querySelector('meta[name="csrf-token"]')?.content;
        if (metaCsrf && !form.querySelector('input[name="_csrf_token"]')) {
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_csrf_token';
            csrfInput.value = metaCsrf;
            form.appendChild(csrfInput);
        }

        if (action === 'verify') {
            Swal.fire({
                icon: 'warning',
                title: 'Confirm Payment Verification',
                text: 'Would you like to confirm verifying this payment? The patient will be approved and moved directly to the RadTech Patient Queue for X-ray.',
                showCancelButton: true,
                confirmButtonColor: '#16a34a',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Yes, Verify & Queue',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        } else if (action === 'reject') {
            Swal.fire({
                icon: 'warning',
                title: 'Reject Payment Confirmation',
                html: `
                    <div class="text-left">
                        <p class="text-sm text-gray-600 mb-3 leading-relaxed">The request will be returned to the patient so they can resubmit the correct reference number and receipt screenshot.</p>
                        <div class="mb-3">
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Quick Select Reason:</label>
                            <div class="flex flex-wrap gap-1.5">
                                <button type="button" class="text-xs px-2.5 py-1 rounded-lg border border-red-200 bg-red-50 hover:bg-red-100 text-red-700 font-medium transition cursor-pointer" onclick="window.setRejectReason('Invalid Reference Number (does not match receipt)')">Invalid Ref #</button>
                                <button type="button" class="text-xs px-2.5 py-1 rounded-lg border border-red-200 bg-red-50 hover:bg-red-100 text-red-700 font-medium transition cursor-pointer" onclick="window.setRejectReason('Blurry / unreadable receipt screenshot. Please upload a clear image.')">Blurry Screenshot</button>
                                <button type="button" class="text-xs px-2.5 py-1 rounded-lg border border-red-200 bg-red-50 hover:bg-red-100 text-red-700 font-medium transition cursor-pointer" onclick="window.setRejectReason('Incorrect payment amount sent. Please pay the exact amount due.')">Amount Mismatch</button>
                                <button type="button" class="text-xs px-2.5 py-1 rounded-lg border border-red-200 bg-red-50 hover:bg-red-100 text-red-700 font-medium transition cursor-pointer" onclick="window.setRejectReason('Receipt screenshot does not match CitiLife branch GCash transaction.')">Receipt Mismatch</button>
                            </div>
                        </div>
                        <div>
                            <label for="swal-rejection-reason" class="block text-xs font-bold text-gray-700 tracking-wider mb-1">Reason for Rejection <span class="text-red-500">*</span></label>
                            <textarea id="swal-rejection-reason" rows="3" class="w-full border border-gray-300 rounded-xl p-2.5 text-sm focus:border-red-500 focus:outline-none outline-none text-gray-800 transition" placeholder="State why this payment is being rejected so the patient knows what to fix..."></textarea>
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
                    window.setRejectReason = function(reason) {
                        const textarea = document.getElementById('swal-rejection-reason');
                        if (textarea) {
                            textarea.value = reason;
                            textarea.focus();
                        }
                    };
                    const textarea = document.getElementById('swal-rejection-reason');
                    if (textarea) textarea.focus();
                },
                preConfirm: () => {
                    const txtArea = document.getElementById('swal-rejection-reason');
                    const reason = (txtArea?.value || '').trim();
                    if (!reason) {
                        Swal.showValidationMessage('Please provide a reason for rejecting this payment.');
                        if (txtArea && window.FormValidator) window.FormValidator.showError(txtArea, 'Please provide a reason for rejection.');
                        if (typeof toast === 'function') toast('Please provide a reason for rejecting this payment.', 'error');
                        return false;
                    }
                    return reason;
                }
            }).then((result) => {
                if (result.isConfirmed && result.value) {
                    let reasonInput = form.querySelector('input[name="rejection_reason"]');
                    if (!reasonInput) {
                        reasonInput = document.createElement('input');
                        reasonInput.type = 'hidden';
                        reasonInput.name = 'rejection_reason';
                        form.appendChild(reasonInput);
                    }
                    reasonInput.value = result.value;
                    form.submit();
                }
            });
        }
    }
</script>