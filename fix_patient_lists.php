<?php
$file = 'c:\\xampp\\htdocs\\CitiLife-System\\views\\pages\\radtech\\patient-lists.view.php';
$content = file_get_contents($file);

// 1. Add dispute logic to top if missing
if (strpos($content, '$disputeModel = new \ResultDisputeModel($pdo);') === false) {
    $topCode = "<?php\nrequire_once __DIR__ . '/../../../config/database.php';\nrequire_once __DIR__ . '/../../../app/Models/ResultDisputeModel.php';\n\$disputeModel = new \ResultDisputeModel(\$pdo);\n\$branchId = \$_SESSION['branch_id'] ?? 1;\n\$disputes = \$disputeModel->getDisputesForClinic(\$branchId, 'radtech');\n\$pendingDisputeCount = count(array_filter(\$disputes, function(\$d) { \n    return in_array(\$d['status'], ['Pending RadTech Review', 'Pending RadTech Verification']); \n}));\n\$currentTab = \$_GET['tab'] ?? 'completed';\n";
    $content = preg_replace('/^\s*<\?php/i', $topCode, $content, 1);
}

// 2. Add Tab
if (strpos($content, 'tab=disputes') === false) {
    $tabHtml = "
        <a href=\"/<?= PROJECT_DIR ?>/index.php?role=radtech&page=patient-lists&tab=disputes\"
           class=\"flex items-center gap-2 px-1 py-3 text-sm font-medium <?= \$currentTab === 'disputes' ? 'text-red-600 border-b-2 border-red-600 hover:text-red-700' : 'text-gray-500 border-b-2 border-transparent hover:text-gray-700 hover:border-gray-300'; ?>\">
            Patient Error Reports
            <?php if (\$pendingDisputeCount > 0): ?>
                <span class=\"ml-1 inline-flex items-center justify-center px-2 py-0.5 text-xs font-bold leading-none text-white bg-red-600 rounded-full\">
                    <?= \$pendingDisputeCount ?>
                </span>
            <?php endif; ?>
        </a>
";
    $content = preg_replace('#(</nav>\s*</div>)#', $tabHtml . '$1', $content, 1);
}

// 3. Hide filters and main table if tab=disputes
if (strpos($content, '$currentTab === \'disputes\' ? \'hidden\' : \'\'') === false) {
    $content = str_replace('<div class="mt-6 flex flex-col gap-4">', '<div class="mt-6 flex flex-col gap-4 <?= $currentTab === \'disputes\' ? \'hidden\' : \'\' ?>">', $content);
    $content = preg_replace('/<div class="rounded-xl border border-gray-300 bg-white shadow-sm mt-4 overflow-hidden">/', '<div class="rounded-xl border border-gray-300 bg-white shadow-sm mt-4 overflow-hidden <?= $currentTab === \'disputes\' ? \'hidden\' : \'\' ?>">', $content, 1);
}

// 4. Add the Disputes Table and Modals
if (strpos($content, 'id="disputes-table-card"') === false) {
    $disputesHtml = <<<HTML

<div id="disputes-table-card" class="rounded-xl border border-gray-300 bg-white shadow-sm mt-4 overflow-hidden <?= \$currentTab !== 'disputes' ? 'hidden' : '' ?>">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200 text-gray-700">
                <tr>
                    <th class="text-left font-semibold px-4 py-3">Case #</th>
                    <th class="text-left font-semibold px-4 py-3">Patient</th>
                    <th class="text-left font-semibold px-4 py-3">Correction Requested</th>
                    <th class="text-left font-semibold px-4 py-3">Status</th>
                    <th class="text-left font-semibold px-4 py-3">Date</th>
                    <th class="text-right font-semibold px-4 py-3">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 realtime-update" id="disputes-table-body">
                <?php if (empty(\$disputes)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-8 text-gray-500">No patient error reports found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach (\$disputes as \$d): ?>
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="py-3 px-4 font-medium"><?= htmlspecialchars(\$d['case_number']) ?></td>
                            <td class="py-3 px-4">
                                <div class="font-medium"><?= htmlspecialchars(\$d['first_name'] . ' ' . \$d['last_name']) ?></div>
                                <div class="text-xs text-gray-500"><?= htmlspecialchars(\$d['patient_number'] ?? '') ?></div>
                            </td>
                            <td class="py-3 px-4 max-w-[200px]">
                                <div class="truncate text-xs font-medium text-amber-800 bg-amber-50 px-2 py-1 rounded inline-block" title="<?= htmlspecialchars(\$d['description']) ?>">
                                    <?= htmlspecialchars(\$d['description']) ?>
                                </div>
                            </td>
                            <td class="py-3 px-4">
                                <?php \$currStatus = \$d['status']; ?>
                                <?php if (\$currStatus === 'Pending RadTech Review'): ?>
                                    <span class="inline-flex items-center gap-1 text-xs font-semibold text-amber-700 bg-amber-50 border border-amber-200 px-2.5 py-1 rounded-lg">
                                        <i data-lucide="clock" class="w-3 h-3"></i> Patient Reviewing
                                    </span>
                                <?php elseif (\$currStatus === 'Escalated to Radiologist'): ?>
                                    <span class="inline-flex items-center gap-1 text-xs font-semibold text-purple-700 bg-purple-50 border border-purple-200 px-2.5 py-1 rounded-lg">
                                        <i data-lucide="clock" class="w-3 h-3"></i> Waiting for Radiologist
                                    </span>
                                <?php elseif (\$currStatus === 'Pending RadTech Verification'): ?>
                                    <span class="inline-flex items-center gap-1 text-xs font-semibold text-blue-700 bg-blue-50 border border-blue-200 px-2.5 py-1 rounded-lg">
                                        <i data-lucide="shield-check" class="w-3 h-3"></i> Ready for Release
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center gap-1 text-xs font-semibold text-green-700 bg-green-50 border border-green-200 px-2.5 py-1 rounded-lg">
                                        <i data-lucide="check-circle" class="w-3 h-3"></i> <?= htmlspecialchars(\$currStatus) ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="py-3 px-4 text-xs text-gray-500 whitespace-nowrap">
                                <?= date('M d, Y h:i A', strtotime(\$d['created_at'])) ?>
                            </td>
                            <td class="py-3 px-4 text-right">
                                <?php if (\$currStatus === 'Pending RadTech Review'): ?>
                                    <div class="flex items-center justify-end gap-2">
                                        <button type="button" onclick="confirmReupload(<?= \$d['case_id'] ?>)"
                                                class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold transition-all active:scale-95">
                                            <i data-lucide="upload-cloud" class="w-4 h-4"></i> Re-upload & Correct
                                        </button>
                                        <button type="button" onclick="openEscalateModal(<?= \$d['id'] ?>, '<?= htmlspecialchars(\$d['case_number']) ?>')"
                                                class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-purple-100 hover:bg-purple-200 text-purple-700 text-xs font-bold transition-all active:scale-95">
                                            <i data-lucide="forward" class="w-4 h-4"></i> Escalate to Radiologist
                                        </button>
                                        <button type="button" onclick="openResolveModal(<?= \$d['id'] ?>, '<?= htmlspecialchars(\$d['case_number']) ?>', 'Fix & Resolve Ticket', <?= htmlspecialchars(json_encode(\$d['description']), ENT_QUOTES, 'UTF-8') ?>)"
                                                class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-green-600 hover:bg-green-700 text-white text-xs font-bold shadow transition-all active:scale-95">
                                            <i data-lucide="check-circle" class="w-4 h-4"></i> Fix & Resolve
                                        </button>
                                    </div>
                                <?php elseif (\$currStatus === 'Pending RadTech Verification'): ?>
                                    <button type="button" onclick="openResolveModal(<?= \$d['id'] ?>, '<?= htmlspecialchars(\$d['case_number']) ?>', 'Final Approve & Release Amended Report', <?= htmlspecialchars(json_encode(\$d['description']), ENT_QUOTES, 'UTF-8') ?>, <?= htmlspecialchars(json_encode(\$d['old_findings'] ?? ''), ENT_QUOTES, 'UTF-8') ?>, <?= htmlspecialchars(json_encode(\$d['findings'] ?? ''), ENT_QUOTES, 'UTF-8') ?>, <?= htmlspecialchars(json_encode(\$d['old_impression'] ?? ''), ENT_QUOTES, 'UTF-8') ?>, <?= htmlspecialchars(json_encode(\$d['impression'] ?? ''), ENT_QUOTES, 'UTF-8') ?>)"
                                            class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-green-600 hover:bg-green-700 text-white text-xs font-bold shadow transition-all active:scale-95">
                                        <i data-lucide="check-circle-2" class="w-4 h-4"></i> Final Verify & Release
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
<div id="escalate-dispute-modal" class="fixed inset-0 z-[9999] hidden flex items-center justify-center bg-black/50 p-4">
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
<div id="resolve-dispute-modal" class="fixed inset-0 z-[9999] hidden flex items-center justify-center bg-black/50 p-4">
    <div class="bg-white rounded-2xl max-w-[600px] w-full p-6 shadow-xl space-y-4">
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

        <!-- Findings Comparison Box (Hidden by default) -->
        <div id="resolve-modal-findings-compare" class="hidden p-3 bg-gray-50 border border-gray-200 rounded-xl space-y-3 max-h-[40vh] overflow-y-auto">
            <div class="grid grid-cols-2 gap-3">
                <div class="space-y-1">
                    <div class="text-[11px] font-bold text-gray-600 uppercase tracking-wider">Old Result</div>
                    <div id="resolve-old-findings" class="text-xs text-gray-800 bg-white p-2.5 rounded-lg border border-gray-200 shadow-sm whitespace-pre-line min-h-[60px]"></div>
                </div>
                <div class="space-y-1">
                    <div class="text-[11px] font-bold text-green-700 uppercase tracking-wider">New Amended Result</div>
                    <div id="resolve-new-findings" class="text-xs text-green-900 bg-green-50 p-2.5 rounded-lg border border-green-200 shadow-sm whitespace-pre-line min-h-[60px]"></div>
                </div>
            </div>
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

HTML;
    $content .= "\n" . $disputesHtml;
}

// 5. Add JS functions for Modals if missing
if (strpos($content, 'function openResolveModal') === false) {
    $jsHtml = <<<JS

<script>
function openEscalateModal(id, caseNo) {
    document.getElementById('escalate-dispute-id').value = id;
    document.getElementById('escalate-notes').value = '';
    document.getElementById('escalate-dispute-modal').classList.remove('hidden');
}
function closeEscalateModal() {
    document.getElementById('escalate-dispute-modal').classList.add('hidden');
}

function openResolveModal(id, caseNo, titleText, descriptionText, oldFindings = '', newFindings = '', oldImpression = '', newImpression = '') {
    document.getElementById('resolve-dispute-id').value = id;
    document.getElementById('resolve-modal-title').innerText = titleText || 'Resolve Dispute Ticket';
    
    const detailsContainer = document.getElementById('resolve-modal-patient-details');
    const statementEl = document.getElementById('resolve-patient-statement');
    if (descriptionText) {
        let formatted = descriptionText.replace(/,\s*/g, "\\n");
        statementEl.innerText = formatted;
        detailsContainer.classList.remove('hidden');
    } else {
        statementEl.innerText = '';
        detailsContainer.classList.add('hidden');
    }

    const compareContainer = document.getElementById('resolve-modal-findings-compare');
    const oldFindingsEl = document.getElementById('resolve-old-findings');
    const newFindingsEl = document.getElementById('resolve-new-findings');
    
    if (oldFindings || newFindings || oldImpression || newImpression) {
        let oldHtml = "";
        let newHtml = "";
        
        if (oldFindings) {
            oldHtml += "**FINDINGS:**\\n" + oldFindings + "\\n\\n";
            newHtml += "**FINDINGS:**\\n" + newFindings + "\\n\\n";
        }
        if (oldImpression) {
            oldHtml += "**IMPRESSION:**\\n" + oldImpression;
            newHtml += "**IMPRESSION:**\\n" + newImpression;
        }
        
        oldFindingsEl.innerText = oldHtml.trim() || 'N/A';
        newFindingsEl.innerText = newHtml.trim() || 'N/A';
        compareContainer.classList.remove('hidden');
    } else {
        compareContainer.classList.add('hidden');
    }

    document.getElementById('resolve-dispute-modal').classList.remove('hidden');
}
function closeResolveModal() {
    document.getElementById('resolve-dispute-modal').classList.add('hidden');
}

function confirmReupload(caseId) {
    Swal.fire({
        title: 'Re-upload & Correct?',
        text: 'Makakapag-upload ka ng bagong X-ray image at mababago ang exam details para sa kasong ito.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, proceed',
        cancelButtonText: 'Cancel',
        customClass: {
            popup: 'rounded-2xl',
            confirmButton: 'rounded-xl font-bold px-4 py-2',
            cancelButton: 'rounded-xl font-semibold px-4 py-2'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = '/<?= PROJECT_DIR ?>/index.php?role=radtech&page=patient-details&id=' + caseId;
        }
    });
}

function submitEscalation(e) {
    e.preventDefault();
    const fd = new FormData(e.target);
    fetch('/<?= PROJECT_DIR ?>/app/Api/disputes.php?action=escalate_dispute', {
        method: 'POST', body: fd
    }).then(r=>r.json()).then(res=>{
        if(res.success){
            Swal.fire('Escalated', 'The ticket is now forwarded to the Radiologist.', 'success').then(()=>location.reload());
        }else{
            Swal.fire('Error', res.error, 'error');
        }
    });
}
function submitResolution(e) {
    e.preventDefault();
    const fd = new FormData(e.target);
    fetch('/<?= PROJECT_DIR ?>/app/Api/disputes.php?action=resolve_dispute', {
        method: 'POST', body: fd
    }).then(r=>r.json()).then(res=>{
        if(res.success){
            Swal.fire('Resolved', 'The dispute ticket is now resolved.', 'success').then(()=>location.reload());
        }else{
            Swal.fire('Error', res.error, 'error');
        }
    });
}

// Real-time polling for RadTech Patient Error Reports
setInterval(() => {
    const isDisputesTab = new URLSearchParams(window.location.search).get('tab') === 'disputes';
    const escModal = document.getElementById('escalate-dispute-modal');
    const resModal = document.getElementById('resolve-dispute-modal');
    const isEscalateOpen = escModal && !escModal.classList.contains('hidden');
    const isResolveOpen = resModal && !resModal.classList.contains('hidden');

    if (isDisputesTab && !isEscalateOpen && !isResolveOpen) {
        fetch('/<?= PROJECT_DIR ?>/index.php?role=radtech&page=patient-lists&tab=disputes')
            .then(res => res.text())
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                
                const newTbody = doc.getElementById('disputes-table-body');
                const oldTbody = document.getElementById('disputes-table-body');
                if (newTbody && oldTbody) {
                    oldTbody.innerHTML = newTbody.innerHTML;
                    if (window.lucide) lucide.createIcons();
                }
            })
            .catch(console.error);
    }
}, 5000);
</script>
JS;
    $content .= "\n" . $jsHtml;
}

file_put_contents($file, $content);
echo "Successfully applied Patient Error Reports fixes to patient-lists.view.php!\n";
