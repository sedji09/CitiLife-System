<?php
/**
 * Payment Verifications View for Branch Admin
 */
?>



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
            <?php if (count($pendingPayments) > 0): ?>
                <span class="inline-flex items-center justify-center bg-red-100 text-red-600 text-xs font-bold px-2 py-0.5 rounded-full">
                    <?= count($pendingPayments) ?>
                </span>
            <?php endif; ?>
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
                    <input type="text" id="pendingSearchInput" placeholder="Search Request # or Patient Name..." class="w-full pl-9 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-red-500/20 focus:border-red-500 outline-none transition-all">
                </div>
                
                <select id="pendingSortSelect" class="w-48 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-red-500/20 focus:border-red-500 outline-none bg-white">
                    <option value="new">Newest First</option>
                    <option value="old">Oldest First</option>
                </select>
            </div>
        </div>
        <div class="mt-4 rounded-xl border border-gray-300 bg-white shadow-sm overflow-hidden">
            <div class="overflow-x-auto overflow-y-auto max-h-[600px]">
                <table class="w-full text-left text-sm text-gray-600 relative">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-500 font-semibold sticky top-0 z-10 shadow-sm">
                        <tr>
                            <th class="px-6 py-4">Request / Patient</th>
                            <th class="px-6 py-4">Amount & Ref #</th>
                            <th class="px-6 py-4">Date Submitted</th>
                            <th class="px-6 py-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100" id="pendingTableBody">
                        <?php if (empty($pendingPayments)): ?>
                            <tr>
                                <td colspan="4" class="p-12 text-center text-gray-500">No records match your filters.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($pendingPayments as $payment): ?>
                            <tr class="hover:bg-gray-50 transition pending-row"
                                data-search="<?= htmlspecialchars(strtolower($payment['request_number'] . ' ' . $payment['first_name'] . ' ' . $payment['last_name'])) ?>"
                                data-date="<?= strtotime($payment['created_at']) ?>">
                                <td class="px-6 py-4">
                                    <div class="font-bold text-gray-900"><?= htmlspecialchars($payment['request_number']) ?></div>
                                    <div class="text-xs text-gray-500"><?= htmlspecialchars($payment['first_name'] . ' ' . $payment['last_name']) ?></div>
                                    <div class="text-xs text-blue-600 mt-1"><?= htmlspecialchars($payment['exam_type']) ?></div>
                                    <?php if (!empty($payment['philhealth_status']) && $payment['philhealth_status'] === 'With PhilHealth Card'): ?>
                                        <div class="mt-1 inline-flex items-center gap-1 text-[11px] font-semibold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">
                                            <i data-lucide="shield-check" class="w-3 h-3 text-emerald-600"></i>
                                            PhilHealth: <?= htmlspecialchars($payment['philhealth_id'] ?: 'Card Holder') ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if ((float)($payment['discount_amount'] ?? 0) > 0): ?>
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs text-gray-400 line-through font-mono">₱<?= number_format($payment['original_amount'], 2) ?></span>
                                            <span class="font-bold text-red-600 font-mono">₱<?= number_format($payment['amount'], 2) ?></span>
                                        </div>
                                        <div class="text-[11px] font-semibold text-emerald-700 bg-emerald-50 inline-block px-1.5 py-0.5 rounded mt-0.5">
                                            -₱<?= number_format($payment['discount_amount'], 2) ?> PhilHealth Discount
                                        </div>
                                    <?php else: ?>
                                        <div class="font-bold text-red-600 font-mono">₱<?= number_format($payment['amount'], 2) ?></div>
                                    <?php endif; ?>
                                    <div class="text-xs text-gray-500 font-mono mt-1">Method: <?= htmlspecialchars($payment['payment_method']) ?></div>
                                    <?php if ($payment['payment_method'] === 'GCash' && $payment['reference_number']): ?>
                                        <div class="text-xs text-gray-500 font-mono mt-1">Ref: <?= htmlspecialchars($payment['reference_number']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-gray-500">
                                    <?= date('M d, Y h:i A', strtotime($payment['created_at'])) ?>
                                </td>
                                <td class="px-6 py-4 space-x-2">
                                    <?php if ($payment['payment_method'] === 'GCash'): ?>
                                        <button type="button" onclick="viewReceipt('<?= htmlspecialchars($payment['proof_of_payment_path'] ?? '') ?>', '<?= htmlspecialchars($payment['reference_number'] ?? 'N/A') ?>', <?= (float)($payment['original_amount'] ?? $payment['amount']) ?>, <?= (float)($payment['discount_amount'] ?? 0) ?>, <?= (float)$payment['amount'] ?>)" class="inline-flex items-center gap-1.5 rounded-lg bg-gray-100 px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-200">
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
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="px-6 py-4 border-t border-gray-200 flex flex-col md:flex-row items-center justify-between gap-4 bg-gray-50/50" id="pending-pagination-container">
                <span class="text-sm text-gray-600" id="pending-pagination-info">
                    Showing page <span class="font-semibold text-gray-800">1</span> of <span class="font-semibold text-gray-800">1</span>
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
                    <input type="text" id="historySearchInput" placeholder="Search Request # or Patient Name..." class="w-full pl-9 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-red-500/20 focus:border-red-500 outline-none transition-all">
                </div>
                
                <select id="historySortSelect" class="w-48 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-red-500/20 focus:border-red-500 outline-none bg-white">
                    <option value="new">Newest First</option>
                    <option value="old">Oldest First</option>
                </select>
            </div>
        </div>
        
        <div class="mt-4 rounded-xl border border-gray-300 bg-white shadow-sm overflow-hidden">
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
                    <tbody class="divide-y divide-gray-100" id="historyTableBody">
                        <?php if (empty($paymentHistory)): ?>
                            <tr>
                                <td colspan="4" class="p-12 text-center text-gray-500">No records match your filters.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($paymentHistory as $payment): ?>
                            <tr class="hover:bg-gray-50 transition history-row" 
                                data-search="<?= htmlspecialchars(strtolower($payment['request_number'] . ' ' . $payment['first_name'] . ' ' . $payment['last_name'])) ?>"
                                data-date="<?= strtotime($payment['updated_at']) ?>">
                                <td class="px-6 py-4">
                                    <div class="font-bold text-gray-900"><?= htmlspecialchars($payment['request_number']) ?></div>
                                    <div class="text-xs text-gray-500"><?= htmlspecialchars($payment['first_name'] . ' ' . $payment['last_name']) ?></div>
                                    <?php if (!empty($payment['philhealth_status']) && $payment['philhealth_status'] === 'With PhilHealth Card'): ?>
                                        <div class="mt-1 inline-flex items-center gap-1 text-[11px] font-semibold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">
                                            <i data-lucide="shield-check" class="w-3 h-3 text-emerald-600"></i> PhilHealth
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if ((float)($payment['discount_amount'] ?? 0) > 0): ?>
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs text-gray-400 line-through font-mono">₱<?= number_format($payment['original_amount'], 2) ?></span>
                                            <span class="font-bold text-gray-900 font-mono">₱<?= number_format($payment['amount'], 2) ?></span>
                                        </div>
                                        <div class="text-[11px] font-semibold text-emerald-700 bg-emerald-50 inline-block px-1.5 py-0.5 rounded mt-0.5">
                                            -₱<?= number_format($payment['discount_amount'], 2) ?> PhilHealth
                                        </div>
                                    <?php else: ?>
                                        <div class="font-bold text-gray-900 font-mono">₱<?= number_format($payment['amount'], 2) ?></div>
                                    <?php endif; ?>
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
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="px-6 py-4 border-t border-gray-200 flex flex-col md:flex-row items-center justify-between gap-4 bg-gray-50/50" id="history-pagination-container">
                <span class="text-sm text-gray-600" id="history-pagination-info">
                    Showing page <span class="font-semibold text-gray-800">1</span> of <span class="font-semibold text-gray-800">1</span>
                </span>
                <div class="flex items-center flex-wrap gap-1.5" id="history-pagination-controls">
                    <!-- JS will render pagination here -->
                </div>
            </div>
        </div>
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
                <div class="mb-4 shrink-0 bg-blue-50 border border-blue-100 rounded-xl p-4 flex flex-col md:flex-row md:items-center justify-between gap-3">
                    <div>
                        <span class="text-xs font-semibold text-blue-600 uppercase tracking-wider block mb-1">Provided Reference Number</span>
                        <strong id="modal-ref-number" class="text-xl font-mono text-blue-900 tracking-tight"></strong>
                    </div>
                    <div class="text-xs text-blue-700 bg-blue-100/50 px-3 py-1.5 rounded-lg">
                        Please verify this matches the receipt below
                    </div>
                </div>

                <!-- Price Breakdown in Modal -->
                <div class="mb-4 shrink-0 bg-white border border-gray-200/80 rounded-xl p-3.5 space-y-1.5 text-xs">
                    <div class="flex items-center justify-between text-gray-600" id="receiptModalOrigRow">
                        <span>Original Procedure Price:</span>
                        <span id="receiptModalOrigAmount" class="font-semibold font-mono text-gray-800">₱0.00</span>
                    </div>
                    <div class="flex items-center justify-between text-emerald-700 font-semibold" id="receiptModalDiscRow">
                        <span class="flex items-center gap-1.5"><i data-lucide="shield-check" class="w-3.5 h-3.5 text-emerald-600"></i> PhilHealth Discount:</span>
                        <span id="receiptModalDiscAmount" class="font-bold font-mono">-₱0.00</span>
                    </div>
                    <div class="pt-1.5 border-t border-gray-100 flex items-center justify-between text-sm">
                        <span class="font-bold text-gray-900">Total Paid Amount:</span>
                        <span id="receiptModalNetAmount" class="font-extrabold text-red-600 font-mono text-base">₱0.00</span>
                    </div>
                </div>
                
                <!-- Receipt Image Container (Scrollable) -->
                <div class="bg-gray-200/50 rounded-xl p-4 flex justify-center items-start border border-gray-200 shadow-inner overflow-y-auto flex-1 min-h-[35vh] max-h-[50vh]">
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

    // ── Search, Sort & JS Pagination Logic ──
    document.addEventListener('DOMContentLoaded', () => {
        function initTable(prefix, rowClass) {
            const searchInput = document.getElementById(prefix + 'SearchInput');
            const sortSelect = document.getElementById(prefix + 'SortSelect');
            const tableBody = document.getElementById(prefix + 'TableBody');
            const paginationContainer = document.getElementById(prefix + '-pagination-container');
            const paginationControls = document.getElementById(prefix + '-pagination-controls');
            const paginationInfo = document.getElementById(prefix + '-pagination-info');

            if (!tableBody) return;

            let allRows = Array.from(tableBody.querySelectorAll('.' + rowClass));
            let filteredRows = [];
            let currentPage = 1;
            const itemsPerPage = 5;

            function updateTable() {
                const searchTerm = searchInput ? searchInput.value.toLowerCase() : '';
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
                    tableBody.innerHTML = '<tr><td colspan="4" class="p-12 text-center text-gray-500">No records match your filters.</td></tr>';
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
        }

        initTable('history', 'history-row');
        initTable('pending', 'pending-row');
    });

    function viewReceipt(path, refNumber, origAmount, discAmount, netAmount) {
        if (!path) {
            alert('No receipt image available.');
            return;
        }
        
        let imageSrc = '<?= "/" . PROJECT_DIR . "/" ?>' + (path.startsWith('/') ? path.substring(1) : path);
        document.getElementById('modal-receipt-img').src = imageSrc;
        document.getElementById('modal-ref-number').textContent = refNumber;
        
        const orig = parseFloat(origAmount || netAmount || 0);
        const disc = parseFloat(discAmount || 0);
        const net = parseFloat(netAmount || 0);

        document.getElementById('receiptModalOrigAmount').textContent = '₱' + orig.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        document.getElementById('receiptModalDiscAmount').textContent = '-₱' + disc.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        document.getElementById('receiptModalNetAmount').textContent = '₱' + net.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        
        const origRow = document.getElementById('receiptModalOrigRow');
        const discRow = document.getElementById('receiptModalDiscRow');
        if (disc > 0) {
            if (origRow) origRow.classList.remove('hidden');
            if (discRow) discRow.classList.remove('hidden');
        } else {
            if (origRow) origRow.classList.add('hidden');
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
