<?php
$file = 'c:\\xampp\\htdocs\\CitiLife-System\\views\\pages\\radtech\\patient-lists.view.php';
$content = file_get_contents($file);

$start = strpos($content, '<div id="disputes-table-card"');
$end = strpos($content, '<!-- ESCALATE TO RADIOLOGIST MODAL -->');

if ($start !== false && $end !== false) {
    // We will replace the table card with a paginated one.
    $newCard = <<<HTML
<div id="disputes-table-card" class="rounded-xl border border-gray-300 bg-white shadow-sm mt-4 flex flex-col <?= \$currentTab !== 'disputes' ? 'hidden' : '' ?>">
    <div class="p-4 border-b border-gray-200 flex justify-between items-center bg-gray-50/50">
        <h3 class="text-sm font-bold text-gray-800">Patient Error Reports</h3>
        <div class="text-xs text-gray-500"><span id="dispute-count"><?= count(\$disputes) ?></span> reports total</div>
    </div>
    <div class="overflow-x-auto overflow-y-auto max-h-[480px]">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200 text-gray-700 sticky top-0 z-10">
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
                    <tr id="no-disputes-row">
                        <td colspan="6" class="text-center py-8 text-gray-500">No patient error reports found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach (\$disputes as \$idx => \$d): ?>
                        <tr class="hover:bg-gray-50 transition-colors dispute-row" data-index="<?= \$idx ?>">
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
    
    <!-- Pagination Controls -->
    <?php if (!empty(\$disputes) && count(\$disputes) > 3): ?>
    <div class="p-3 border-t border-gray-200 bg-gray-50 flex items-center justify-between" id="disputes-pagination">
        <div class="text-xs text-gray-500">
            Showing <span id="disp-start">1</span> to <span id="disp-end">3</span> of <?= count(\$disputes) ?> entries
        </div>
        <div class="flex gap-1">
            <button type="button" id="disp-prev" class="px-3 py-1.5 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">Previous</button>
            <button type="button" id="disp-next" class="px-3 py-1.5 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">Next</button>
        </div>
    </div>
    <?php endif; ?>
</div>

HTML;
    $content = substr_replace($content, $newCard, $start, $end - $start);
    file_put_contents($file, $content);
    echo "Updated card layout.";
} else {
    echo "Could not find start or end.";
}
