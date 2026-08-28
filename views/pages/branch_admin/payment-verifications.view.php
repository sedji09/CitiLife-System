<?php
/**
 * Payment Verifications View for Branch Admin
 */
?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Payment Verifications</h1>
    <p class="text-sm text-gray-500 mt-1">Review and verify GCash payments submitted by patients via the portal.</p>
</div>

<?php if ($successMsg): ?>
    <div class="mb-6 rounded-lg bg-green-50 p-4 border border-green-200 flex items-start gap-3">
        <i data-lucide="check-circle" class="w-5 h-5 text-green-600 shrink-0"></i>
        <p class="text-sm text-green-800"><?= htmlspecialchars($successMsg) ?></p>
    </div>
<?php endif; ?>

<?php if ($errorMsg): ?>
    <div class="mb-6 rounded-lg bg-red-50 p-4 border border-red-200 flex items-start gap-3">
        <i data-lucide="alert-circle" class="w-5 h-5 text-red-600 shrink-0"></i>
        <p class="text-sm text-red-800"><?= htmlspecialchars($errorMsg) ?></p>
    </div>
<?php endif; ?>

<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
    <!-- Tabs -->
    <div class="border-b border-gray-200 px-6 pt-4 flex gap-6">
        <button onclick="switchTab('pending')" id="tab-pending" class="pb-3 text-sm font-semibold border-b-2 border-blue-600 text-blue-600">
            Pending Verification (<?= count($pendingPayments) ?>)
        </button>
        <button onclick="switchTab('history')" id="tab-history" class="pb-3 text-sm font-semibold border-b-2 border-transparent text-gray-500 hover:text-gray-700">
            History
        </button>
    </div>

    <!-- Pending Tab -->
    <div id="content-pending" class="p-0">
        <?php if (empty($pendingPayments)): ?>
            <div class="p-12 text-center text-gray-500">
                <i data-lucide="check-circle" class="w-12 h-12 mx-auto text-gray-300 mb-3"></i>
                <p>No pending payments to verify.</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-gray-600">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-500 font-semibold border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-4">Request / Patient</th>
                            <th class="px-6 py-4">Amount & Ref #</th>
                            <th class="px-6 py-4">Date Submitted</th>
                            <th class="px-6 py-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach ($pendingPayments as $payment): ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4">
                                    <div class="font-bold text-gray-900"><?= htmlspecialchars($payment['request_number']) ?></div>
                                    <div class="text-xs text-gray-500"><?= htmlspecialchars($payment['first_name'] . ' ' . $payment['last_name']) ?></div>
                                    <div class="text-xs text-blue-600 mt-1"><?= htmlspecialchars($payment['exam_type']) ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-bold text-red-600">₱<?= number_format($payment['amount'], 2) ?></div>
                                    <div class="text-xs text-gray-500 font-mono mt-1">Method: <?= htmlspecialchars($payment['payment_method']) ?></div>
                                    <?php if ($payment['payment_method'] === 'GCash' && $payment['reference_number']): ?>
                                        <div class="text-xs text-gray-500 font-mono mt-1">Ref: <?= htmlspecialchars($payment['reference_number']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-gray-500">
                                    <?= date('M d, Y h:i A', strtotime($payment['created_at'])) ?>
                                </td>
                                <td class="px-6 py-4 text-right space-x-2">
                                    <?php if ($payment['payment_method'] === 'GCash'): ?>
                                        <button type="button" onclick="viewReceipt('<?= htmlspecialchars($payment['proof_of_payment_path'] ?? '') ?>', '<?= htmlspecialchars($payment['reference_number'] ?? 'N/A') ?>')" class="inline-flex items-center gap-1.5 rounded-lg bg-gray-100 px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-200">
                                            <i data-lucide="image" class="w-4 h-4"></i> Receipt
                                        </button>
                                    <?php endif; ?>
                                    <form method="POST" class="inline-block">
                                        <input type="hidden" name="payment_id" value="<?= $payment['id'] ?>">
                                        <input type="hidden" name="action" value="verify">
                                        <button type="button" onclick="confirmAction(this.form, 'verify')" class="inline-flex items-center gap-1.5 rounded-lg bg-green-50 px-3 py-1.5 text-xs font-semibold text-green-700 hover:bg-green-100 border border-green-200">
                                            <i data-lucide="check" class="w-4 h-4"></i> Verify
                                        </button>
                                    </form>
                                    <form method="POST" class="inline-block">
                                        <input type="hidden" name="payment_id" value="<?= $payment['id'] ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <button type="button" onclick="confirmAction(this.form, 'reject')" class="inline-flex items-center gap-1.5 rounded-lg bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-100 border border-red-200">
                                            <i data-lucide="x" class="w-4 h-4"></i> Reject
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- History Tab -->
    <div id="content-history" class="p-0 hidden">
        <?php if (empty($paymentHistory)): ?>
            <div class="p-12 text-center text-gray-500">
                <p>No payment history found.</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-gray-600">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-500 font-semibold border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-4">Request / Patient</th>
                            <th class="px-6 py-4">Amount & Ref #</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4">Date Verified</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach ($paymentHistory as $payment): ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4">
                                    <div class="font-bold text-gray-900"><?= htmlspecialchars($payment['request_number']) ?></div>
                                    <div class="text-xs text-gray-500"><?= htmlspecialchars($payment['first_name'] . ' ' . $payment['last_name']) ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-bold text-gray-900">₱<?= number_format($payment['amount'], 2) ?></div>
                                    <div class="text-xs text-gray-500 font-mono mt-1">Method: <?= htmlspecialchars($payment['payment_method']) ?></div>
                                    <?php if ($payment['payment_method'] === 'GCash' && $payment['reference_number']): ?>
                                        <div class="text-xs text-gray-500 font-mono mt-1">Ref: <?= htmlspecialchars($payment['reference_number']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if ($payment['status'] === 'Verified'): ?>
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-green-50 px-2.5 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">
                                            <i data-lucide="check-circle" class="w-3.5 h-3.5"></i> Verified
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-red-50 px-2.5 py-1 text-xs font-medium text-red-700 ring-1 ring-inset ring-red-600/20">
                                            <i data-lucide="x-circle" class="w-3.5 h-3.5"></i> Rejected
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-gray-500">
                                    <?= date('M d, Y h:i A', strtotime($payment['updated_at'])) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Receipt Modal -->
<div id="receiptModal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-gray-900/60 backdrop-blur-sm" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex min-h-screen items-center justify-center p-4 text-center sm:p-0">
        <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-2xl flex flex-col max-h-[90vh]">
            
            <!-- Header -->
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-white shrink-0">
                <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2" id="modal-title">
                    <i data-lucide="receipt" class="w-5 h-5 text-blue-600"></i> Payment Receipt
                </h3>
                <button type="button" onclick="closeReceiptModal()" class="text-gray-400 hover:text-gray-600 transition">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            
            <!-- Content -->
            <div class="px-6 py-5 flex-1 flex flex-col bg-gray-50/50 min-h-0">
                <!-- Reference Number Badge -->
                <div class="mb-5 shrink-0 bg-blue-50 border border-blue-100 rounded-xl p-4 flex flex-col md:flex-row md:items-center justify-between gap-3">
                    <div>
                        <span class="text-xs font-semibold text-blue-600 uppercase tracking-wider block mb-1">Provided Reference Number</span>
                        <strong id="modal-ref-number" class="text-xl font-mono text-blue-900 tracking-tight"></strong>
                    </div>
                    <div class="text-xs text-blue-700 bg-blue-100/50 px-3 py-1.5 rounded-lg">
                        Please verify this matches the receipt below
                    </div>
                </div>
                
                <!-- Receipt Image Container (Scrollable) -->
                <div class="bg-gray-200/50 rounded-xl p-4 flex justify-center items-start border border-gray-200 shadow-inner overflow-y-auto flex-1 min-h-[40vh] max-h-[60vh]">
                    <img id="modal-receipt-img" src="" alt="Receipt" class="max-w-md w-full h-auto rounded-lg shadow-sm">
                </div>
            </div>
            
            <!-- Footer -->
            <div class="bg-white px-6 py-4 border-t border-gray-100 flex justify-end shrink-0">
                <button type="button" onclick="closeReceiptModal()" class="inline-flex justify-center rounded-xl bg-white px-5 py-2.5 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 transition">
                    Close Viewer
                </button>
            </div>

        </div>
    </div>
</div>

<script>
    function switchTab(tabId) {
        document.getElementById('content-pending').classList.add('hidden');
        document.getElementById('content-history').classList.add('hidden');
        
        document.getElementById('tab-pending').className = 'pb-3 text-sm font-semibold border-b-2 border-transparent text-gray-500 hover:text-gray-700';
        document.getElementById('tab-history').className = 'pb-3 text-sm font-semibold border-b-2 border-transparent text-gray-500 hover:text-gray-700';
        
        document.getElementById('content-' + tabId).classList.remove('hidden');
        document.getElementById('tab-' + tabId).className = 'pb-3 text-sm font-semibold border-b-2 border-blue-600 text-blue-600';
    }

    function viewReceipt(path, refNumber) {
        if (!path) {
            alert('No receipt image available.');
            return;
        }
        
        let imageSrc = path.startsWith('/') ? '/' + '<?= PROJECT_DIR ?>' + path : '/' + '<?= PROJECT_DIR ?>/' + path;
        // In case the path was saved with /public already, we might end up with double /public, let's clean it up:
        imageSrc = imageSrc.replace(/\/public\/public\//g, '/public/');
        document.getElementById('modal-receipt-img').src = imageSrc;
        document.getElementById('modal-ref-number').textContent = refNumber;
        document.getElementById('receiptModal').classList.remove('hidden');
    }

    function closeReceiptModal() {
        document.getElementById('receiptModal').classList.add('hidden');
    }

    function confirmAction(form, action) {
        if (action === 'verify') {
            Swal.fire({
                icon: 'warning',
                title: 'Confirm Approval',
                text: 'Would you like to confirm verifying this payment? The patient will be able to proceed to X-ray.',
                showCancelButton: true,
                confirmButtonColor: '#3b82f6',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Yes, Proceed',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        } else if (action === 'reject') {
            Swal.fire({
                icon: 'warning',
                title: 'Confirm Rejection',
                text: 'Would you like to confirm rejecting this payment? The patient will need to resubmit their proof of payment.',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Yes, Reject',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        }
    }
</script>
