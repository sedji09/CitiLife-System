<?php
/**
 * Today's Queue (Patient List) View
 * Backend logic handled by PatientListsController.php
 */
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
        <p class="text-sm text-gray-500 mt-1">Manage approvals and today's examination queue</p>
    </div>
</div>

<?php if ($successMsg): ?>
    <div id="flash-success-alert"
        class="mt-4 rounded-xl bg-green-50 border border-green-200 px-4 py-3 flex items-center gap-3 shadow-sm transition-all">
        <i data-lucide="check-circle-2" class="w-5 h-5 text-green-500 shrink-0"></i>
        <p class="text-sm font-bold text-green-800"><?= htmlspecialchars($successMsg) ?></p>
    </div>
    <script>
        setTimeout(() => {
            const el = document.getElementById('flash-success-alert');
            if (el) {
                el.style.opacity = '0';
                el.style.transition = 'opacity 0.5s ease';
                setTimeout(() => el.remove(), 500);
            }
        }, 5000);
    </script>
<?php endif; ?>

<?php if ($errorMsg): ?>
    <div class="mt-4 rounded-lg bg-red-50 border border-red-300 p-3 flex items-center gap-3">
        <i data-lucide="alert-circle" class="w-5 h-5 text-red-600"></i>
        <p class="text-sm text-red-700 font-medium"><?= htmlspecialchars($errorMsg) ?></p>
    </div>
<?php endif; ?>

<!-- Navigation Tabs -->
<div class="mt-6 border-b border-gray-200">
    <nav class="flex gap-3">
        <a href="/<?= PROJECT_DIR ?>/index.php?role=radtech&page=patient-lists"
            class="flex items-center gap-2 px-1 py-3 text-sm font-medium <?= ($_GET['page'] ?? 'patient-lists') === 'patient-lists' ? 'text-red-600 border-b-2 border-red-600 hover:text-red-700' : 'text-gray-600 border-b-2 border-transparent hover:text-gray-700 hover:border-gray-300'; ?>">
            Today's Queue
        </a>
        <a href="/<?= PROJECT_DIR ?>/index.php?role=radtech&page=patient-approval"
            class="flex items-center gap-2 px-1 py-3 text-sm font-medium <?= ($_GET['page'] ?? 'patient-lists') === 'patient-approval' ? 'text-red-600 border-b-2 border-red-600 hover:text-red-700' : 'text-gray-500 border-b-2 border-transparent hover:text-gray-700 hover:border-gray-300'; ?>">
            Pending Approval
        </a>
    </nav>
</div>

<!-- Content -->
<div class="mt-6 flex flex-col gap-4">
    <div class="flex gap-4 items-center">
        <input type="text" id="search-input" placeholder="Search by patient name or case number..."
            class="flex-1 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm text-gray-900 outline-none focus:ring-2 focus:ring-red-500">
        <select id="filter-priority"
            class="w-48 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm outline-none focus:ring-2 focus:ring-red-500">
            <option value="All" hidden selected>Filter by Priority</option>
            <option value="All">All</option>
            <option>Routine</option>
            <option>Urgent</option>
            <option>Emergency</option>
        </select>
        <select id="sort-date"
            class="w-48 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm outline-none focus:ring-2 focus:ring-red-500">
            <option>Sort by:</option>
            <option>Newest Case</option>
            <option>Oldest Case</option>
        </select>
    </div>
</div>


<div class="rounded-xl border border-gray-300 bg-white shadow-sm mt-4 overflow-hidden">
    <div class="overflow-x-auto overflow-y-auto max-h-[480px]">
        <table class="w-full text-sm ">
            <thead class="sticky top-0 z-10">
                <tr class="border-b border-gray-200 bg-gray-50 text-gray-600">
                    <th class="text-left font-semibold px-3 py-3 whitespace-nowrap">Case No.</th>
                    <th class="text-left font-semibold px-3 py-3 whitespace-nowrap">Patient No.</th>
                    <th class="text-left font-semibold px-3 py-3 truncate max-w-[200px]">Patient Name</th>
                    <th class="text-left font-semibold px-3 py-3 truncate max-w-[150px]">Exam Type</th>
                    <th class="text-left font-semibold px-3 py-3">Priority</th>
                    <th class="text-left font-semibold px-3 py-3">Image</th>
                    <th class="text-left font-semibold px-3 py-3">Status</th>
                    <th class="text-left font-semibold px-3 py-3 min-w-[100px]">Date</th>
                    <th class="text-left font-semibold px-3 py-3 whitespace-nowrap">Actions</th>
                </tr>
            </thead>
            <tbody id="table-body" class="text-gray-800 bg-white divide-y divide-gray-100 realtime-update">
                <?php if (count($patients) === 0): ?>
                    <tr>
                        <td colspan="9" class="text-center py-8 text-gray-500">
                            No active patients found in Today's Queue.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($patients as $row): ?>
                        <?php $isReportReady = ($row['status'] === 'Report Ready'); ?>
                        <tr class="hover:bg-gray-50 transition-colors record-row"
                            data-id="<?= htmlspecialchars($row['case_number']) ?>"
                            data-name="<?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?>"
                            data-priority="<?= htmlspecialchars($row['priority']) ?>"
                            data-exam="<?= htmlspecialchars($row['exam_type']) ?>"
                            data-date="<?= htmlspecialchars($row['created_at']) ?>">
                            <td class="py-3 px-3 font-medium whitespace-nowrap"><?= htmlspecialchars($row['case_number']) ?>
                            </td>
                            <td class="py-3 px-3 font-medium whitespace-nowrap">
                                <?= htmlspecialchars($row['patient_number'] ?? 'N/A') ?>
                            </td>
                            <td class="py-3 px-3 font-medium truncate max-w-[200px]"
                                title="<?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?>">
                                <?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?>
                            </td>
                            <td class="py-3 px-3 max-w-[180px]">
                                <?php
                                $exams = array_filter(array_map('trim', explode(',', $row['exam_type'])));
                                $firstExam = reset($exams);
                                $extraCount = count($exams) - 1;
                                ?>
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <span class="font-medium text-gray-800 truncate max-w-[100px]"
                                        title="<?= htmlspecialchars($row['exam_type']) ?>"><?= htmlspecialchars($firstExam) ?></span>
                                    <?php if ($extraCount > 0): ?>
                                        <span
                                            class="inline-flex items-center rounded-full bg-gray-100 border border-gray-300 px-1.5 py-0.5 text-xs font-semibold text-gray-600 cursor-default flex-shrink-0"
                                            title="<?= htmlspecialchars($row['exam_type']) ?>">+<?= $extraCount ?></span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="py-3 px-3">
                                <?php
                                $pBorder = '1.5px solid #60a5fa';
                                $pBg = '#eff6ff';
                                $pColor = '#1d4ed8';
                                if ($row['priority'] === 'Emergency') {
                                    $pBorder = '1.5px solid #f87171';
                                    $pBg = '#fef2f2';
                                    $pColor = '#b91c1c';
                                }
                                if ($row['priority'] === 'Urgent') {
                                    $pBorder = '1.5px solid #facc15';
                                    $pBg = '#fefce8';
                                    $pColor = '#a16207';
                                }
                                if ($row['priority'] === 'Priority') {
                                    $pBorder = '1.5px solid #fb923c';
                                    $pBg = '#fff7ed';
                                    $pColor = '#c2410c';
                                }
                                ?>
                                <span
                                    class="priority-badge inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold"
                                    style="border:<?= $pBorder ?>;background-color:<?= $pBg ?>;color:<?= $pColor ?>">
                                    <?= htmlspecialchars($row['priority']) ?>
                                </span>
                            </td>
                            <td class="py-3 px-3 text-gray-500">
                                <?php if ($row['image_status'] === 'Uploaded'): ?>
                                    <div class="flex items-center gap-1 text-green-500">
                                        <i data-lucide="check" class="w-4 h-4"></i> Uploaded
                                    </div>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                            <td class="py-3 px-3">
                                <?php
                                $displayStatus = ($row['approval_status'] === 'Rejected' || $row['status'] === 'Rejected') ? 'Rejected' : $row['status'];
                                $sBorder = '1.5px solid #facc15';
                                $sBg = '#fefce8';
                                $sColor = '#a16207';
                                if ($displayStatus === 'Report Ready') {
                                    $sBorder = '1.5px solid #818cf8';
                                    $sBg = '#eef2ff';
                                    $sColor = '#4338ca';
                                }
                                if ($displayStatus === 'Under Reading') {
                                    $sBorder = '1.5px solid #60a5fa';
                                    $sBg = '#eff6ff';
                                    $sColor = '#1d4ed8';
                                }
                                if ($displayStatus === 'Completed') {
                                    $sBorder = '1.5px solid #4ade80';
                                    $sBg = '#f0fdf4';
                                    $sColor = '#15803d';
                                }
                                if ($displayStatus === 'Rejected') {
                                    $sBorder = '1.5px solid #f87171';
                                    $sBg = '#fef2f2';
                                    $sColor = '#b91c1c';
                                }
                                ?>
                                <span
                                    class="status-badge inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold"
                                    style="border:<?= $sBorder ?>;background-color:<?= $sBg ?>;color:<?= $sColor ?>">
                                    <?= htmlspecialchars($displayStatus ?: 'Pending') ?>
                                </span>
                            </td>
                            <td class="py-3 px-3 text-gray-500 text-xs whitespace-nowrap">
                                <?= date('M d, Y h:i A', strtotime($row['created_at'])) ?>
                            </td>
                            <td class="py-3 px-3 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    <!-- View button always active -->
                                    <a href="/<?= PROJECT_DIR ?>/index.php?role=radtech&page=patient-details&id=<?= $row['id'] ?>"
                                        class="text-sm font-medium text-blue-500 hover:text-blue-700 transition"
                                        title="View Case">
                                        <i data-lucide="eye"
                                            class="w-6 h-6 mr-1 bg-blue-100 px-1 py-1 rounded-md border border-blue-500"></i>
                                    </a>

                                    <?php if ($isReportReady): ?>
                                        <!-- Print Result — active when Report Ready -->
                                        <a href="javascript:void(0)"
                                            onclick="confirmAction('Confirm Print', 'Would you like to confirm printing this report?', '/<?= PROJECT_DIR ?>/app/views/pages/radtech/print-report.php?id=<?= $row['id'] ?>', 'Yes, Print', true, event)"
                                            class="text-green-500 hover:text-green-700 transition" title="Print Report">
                                            <i data-lucide="printer"
                                                class="w-6 h-6 mr-1 bg-green-100 px-1 py-1 rounded-md border border-green-500"></i>
                                        </a>

                                        <!-- Release — active when Report Ready -->
                                        <button type="button" onclick="releaseToPhoto(<?= $row['id'] ?>, this, event)"
                                            class="text-sm font-medium text-red-500 hover:text-red-700 transition"
                                            title="Release Result">
                                            <i data-lucide="send"
                                                class="w-6 h-6 mr-1 bg-red-100 px-1 py-1 rounded-md border border-red-500"></i>
                                        </button>
                                    <?php else: ?>
                                        <!-- Print — disabled -->
                                        <button class="text-gray-400 cursor-not-allowed"
                                            title="Print Report (Disabled until Radiologist submits report)" disabled>
                                            <i data-lucide="printer"
                                                class="w-6 h-6 mr-1 bg-gray-100 px-1 py-1 rounded-md border border-gray-300"></i>
                                        </button>

                                        <!-- Release — disabled -->
                                        <span class="text-gray-400 cursor-not-allowed"
                                            title="Release (Disabled until Radiologist submits report)">
                                            <i data-lucide="send"
                                                class="w-6 h-6 mr-1 bg-gray-100 px-1 py-1 rounded-md border border-gray-300"></i>
                                        </span>
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

<script>
    document.addEventListener('input', (e) => {
        if (e.target && (e.target.id === 'search-input' || e.target.id === 'filter-priority' || e.target.id === 'sort-date')) {
            applyFilters();
        }
    });

    document.addEventListener('change', (e) => {
        if (e.target && (e.target.id === 'filter-priority' || e.target.id === 'sort-date')) {
            applyFilters();
        }
    });

    function applyFilters() {
        const search = (document.getElementById('search-input')?.value || '').toLowerCase();
        const priority = document.getElementById('filter-priority')?.value || 'Filter by Priority';
        const sort = document.getElementById('sort-date')?.value || 'Sort by:';

        const tbody = document.getElementById('table-body');
        if (!tbody) return;

        let rows = Array.from(tbody.querySelectorAll('tr.record-row'));
        let visibleCount = 0;

        // Sort
        if (sort === 'Newest Case' || sort === 'Oldest Case') {
            const priorityMap = { 'Emergency': 3, 'Urgent': 2, 'Routine': 1 };

            rows.sort((a, b) => {
                // Primary Sort: Priority Level (Emergency > Urgent > Routine)
                const scoreA = priorityMap[a.dataset.priority] || 0;
                const scoreB = priorityMap[b.dataset.priority] || 0;

                if (scoreA !== scoreB) {
                    return scoreB - scoreA;
                }

                // Secondary Sort: Date
                const dateA = new Date(a.dataset.date).getTime();
                const dateB = new Date(b.dataset.date).getTime();
                return sort === 'Newest Case' ? dateB - dateA : dateA - dateB;
            });

            rows.forEach(row => tbody.appendChild(row));
        }

        // Filter
        rows.forEach(row => {
            const name = (row.dataset.name || '').toLowerCase();
            const id = (row.dataset.id || '').toLowerCase();
            const rowPriority = row.dataset.priority || '';

            const matchSearch = name.includes(search) || id.includes(search) || rowPriority.toLowerCase().includes(search);
            const matchPriority = priority === 'Filter by Priority' || priority === 'All' || priority === rowPriority;

            if (matchSearch && matchPriority) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        let emptyMsg = document.getElementById('empty-msg-row');
        if (visibleCount === 0 && rows.length > 0) {
            if (!emptyMsg) {
                emptyMsg = document.createElement('tr');
                emptyMsg.id = 'empty-msg-row';
                emptyMsg.innerHTML = `<td colspan="10" class="text-center py-8 text-gray-500">No records match your filters.</td>`;
                tbody.appendChild(emptyMsg);
            } else {
                emptyMsg.style.display = '';
            }
        } else if (emptyMsg) {
            emptyMsg.style.display = 'none';
        }
    }

    // Initial sorting and highlight logic on load
    document.addEventListener('DOMContentLoaded', () => {
        // Default Sort & Filter
        setTimeout(() => {
            const sortSelect = document.getElementById('sort-date');
            if (sortSelect && sortSelect.value === 'Sort by:') {
                sortSelect.value = 'Newest Case';
                applyFilters();
            }
        }, 100);

        // ── Highlight row from notification ───────────────────────────────
        setTimeout(() => {
            const params = new window.URLSearchParams(window.location.search);
            const highlightId = params.get('highlight');
            if (highlightId) {
                const rows = document.querySelectorAll('#table-body tr.record-row');
                let targetRow = null;
                rows.forEach(row => {
                    if ((row.dataset.id || '').toLowerCase() === highlightId.toLowerCase()) {
                        targetRow = row;
                    }
                });
                if (targetRow) {
                    const tableWrapper = targetRow.closest('.overflow-y-auto');
                    if (tableWrapper) {
                        const rowTop = targetRow.offsetTop - tableWrapper.offsetTop;
                        tableWrapper.scrollTo({ top: rowTop - 40, behavior: 'smooth' });
                    } else {
                        targetRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }

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

                    const banner = document.createElement('div');
                    banner.id = 'highlight-banner';
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
            }
        }, 150);
    });

    // Re-apply filters when real-time polling updates the table content
    document.addEventListener('realtime:updated', () => {
        applyFilters();
    });
</script>

<!-- Add html2canvas library for PDF-to-Image rendering -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>



<!-- Prominent Spinning Loader -->
<div id="release-loading-overlay"
    class="fixed inset-0 z-[9999] bg-white/80 dark:bg-slate-900/80 backdrop-blur-sm flex flex-col items-center justify-center hidden">
    <div class="animate-spin rounded-full h-16 w-16 border-b-4 border-red-600 mb-4"></div>
    <h3 class="text-xl font-bold text-gray-800 dark:text-white">Releasing Result</h3>
    <p id="release-status-text" class="text-gray-500 dark:text-gray-400 mt-2 text-center">Preparing report snapshots...
    </p>
</div>

<script>
    async function releaseToPhoto(caseId, btn, event = null) {
        if (event) event.preventDefault();
        const confirmed = await confirmAlert('Confirm Release', 'Would you like to confirm releasing this result and moving it to X-ray Patient Records?');
        if (!confirmed.isConfirmed) return;

        const baseDir = '/<?= PROJECT_DIR ?>';
        const overlay = document.getElementById('release-loading-overlay');
        const statusText = document.getElementById('release-status-text');

        // Disable button to prevent double clicks
        const originalHTML = btn.innerHTML;
        btn.disabled = true;
        btn.classList.add('opacity-50', 'cursor-not-allowed');

        // Show refined overlay
        if (overlay) overlay.classList.remove('hidden');
        if (statusText) statusText.textContent = 'Initializing report snapshot...';

        try {
            // 1. Create a hidden iframe to render the perfect print layout
            const iframe = document.createElement('iframe');
            iframe.style.position = 'fixed';
            iframe.style.top = '-10000px';
            iframe.style.left = '-10000px';
            iframe.style.width = '814px'; // Fixed A4 width for predictable rendering
            iframe.style.height = '1200px';
            iframe.style.border = 'none';

            // Use snapshot=1 to skip auto-print in the iframe
            iframe.src = `${baseDir}/app/views/pages/radtech/print-report.php?id=${caseId}&no_shadow=1&snapshot=1`;
            document.body.appendChild(iframe);

            iframe.onload = async () => {
                try {
                    const doc = iframe.contentDocument || iframe.contentWindow.document;

                    // Wait briefly for fonts/images
                    await new Promise(r => setTimeout(r, 1000));

                    const pages = doc.querySelectorAll('.report-page');
                    if (!pages.length) throw new Error("No pages found to render.");

                    // CRITICAL FIX: Expand iframe height so it is tall enough to fit ALL pages 
                    // without any internal scrollbars. This prevents html2canvas from clipping!
                    iframe.style.height = (doc.documentElement.scrollHeight + 200) + 'px';

                    // Wait another 500ms after expanding to ensure browser has repainted
                    await new Promise(r => setTimeout(r, 500));

                    let base64Images = [];
                    for (let i = 0; i < pages.length; i++) {
                        const page = pages[i];
                        if (statusText) statusText.textContent = `Capturing page ${i + 1} of ${pages.length}...`;

                        const canvas = await html2canvas(page, {
                            scale: pages.length > 5 ? 1.5 : 2, // Slightly lower scale for very large reports to prevent memory issues
                            useCORS: true,
                            backgroundColor: '#ffffff',
                            width: page.scrollWidth,
                            height: page.scrollHeight,
                            windowWidth: doc.documentElement.scrollWidth,
                            windowHeight: doc.documentElement.scrollHeight
                        });

                        // Convert Canvas to JPEG (JPEG is smaller than PNG for documents)
                        const imgData = canvas.toDataURL('image/jpeg', pages.length > 5 ? 0.8 : 0.9);
                        base64Images.push(imgData);
                    }

                    if (statusText) statusText.textContent = 'Uploading consolidated report...';

                    // Submit images to backend
                    const formData = new FormData();
                    formData.append('id', caseId);
                    formData.append('images', JSON.stringify(base64Images));

                    const response = await fetch(`${baseDir}/index.php?role=radtech&page=patient-lists&action=release_and_upload`, {
                        method: 'POST',
                        body: formData
                    });

                    const result = await response.json();

                    if (result.success) {
                        // Success! Refresh page to show success flash message
                        window.location.reload();
                    } else {
                        throw new Error(result.message || 'Server rejected the upload.');
                    }
                } catch (err) {
                    console.error(err);
                    errorAlert('Generation Failed', err.message);
                    if (overlay) overlay.classList.add('hidden');
                    btn.disabled = false;
                    btn.classList.remove('opacity-50', 'cursor-not-allowed');
                } finally {
                    iframe.remove();
                }
            };
        } catch (e) {
            console.error(e);
            errorAlert('Error', 'An unexpected error occurred during processing.');
            if (overlay) overlay.classList.add('hidden');
            btn.disabled = false;
            btn.classList.remove('opacity-50', 'cursor-not-allowed');
        }
    }
</script>