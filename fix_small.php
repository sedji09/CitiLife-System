<?php
$file = 'c:\\xampp\\htdocs\\CitiLife-System\\views\\pages\\radtech\\patient-lists.view.php';
$content = file_get_contents($file);

$oldButtons = <<<HTML
                                    <div class="flex items-center justify-end gap-2">
                                        <button type="button" onclick="confirmReupload(<?= \$d['case_id'] ?>)"
                                                title="Re-upload & Correct"
                                                class="w-9 h-9 rounded-lg border border-blue-200 bg-blue-50 hover:bg-blue-100 text-blue-600 flex items-center justify-center transition-all active:scale-95 shadow-sm">
                                            <i data-lucide="upload-cloud" class="w-5 h-5"></i>
                                        </button>
                                        <button type="button" onclick="openEscalateModal(<?= \$d['id'] ?>, '<?= htmlspecialchars(\$d['case_number']) ?>')"
                                                title="Escalate to Radiologist"
                                                class="w-9 h-9 rounded-lg border border-red-200 bg-red-50 hover:bg-red-100 text-red-600 flex items-center justify-center transition-all active:scale-95 shadow-sm">
                                            <i data-lucide="forward" class="w-5 h-5"></i>
                                        </button>
                                        <button type="button" onclick="openResolveModal(<?= \$d['id'] ?>, '<?= htmlspecialchars(\$d['case_number']) ?>', 'Fix & Resolve Ticket', <?= htmlspecialchars(json_encode(\$d['description']), ENT_QUOTES, 'UTF-8') ?>)"
                                                title="Fix & Resolve"
                                                class="w-9 h-9 rounded-lg border border-green-200 bg-green-50 hover:bg-green-100 text-green-600 flex items-center justify-center transition-all active:scale-95 shadow-sm">
                                            <i data-lucide="check-circle-2" class="w-5 h-5"></i>
                                        </button>
                                    </div>
HTML;

$newButtons = <<<HTML
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button type="button" onclick="confirmReupload(<?= \$d['case_id'] ?>)" title="Re-upload & Correct" class="text-blue-500 hover:text-blue-700 transition active:scale-95">
                                            <i data-lucide="upload-cloud" class="w-6 h-6 bg-blue-50 px-1 py-1 rounded-md border border-blue-400"></i>
                                        </button>
                                        <button type="button" onclick="openEscalateModal(<?= \$d['id'] ?>, '<?= htmlspecialchars(\$d['case_number']) ?>')" title="Escalate to Radiologist" class="text-red-500 hover:text-red-700 transition active:scale-95">
                                            <i data-lucide="forward" class="w-6 h-6 bg-red-50 px-1 py-1 rounded-md border border-red-400"></i>
                                        </button>
                                        <button type="button" onclick="openResolveModal(<?= \$d['id'] ?>, '<?= htmlspecialchars(\$d['case_number']) ?>', 'Fix & Resolve Ticket', <?= htmlspecialchars(json_encode(\$d['description']), ENT_QUOTES, 'UTF-8') ?>)" title="Fix & Resolve" class="text-green-500 hover:text-green-700 transition active:scale-95">
                                            <i data-lucide="check-circle-2" class="w-6 h-6 bg-green-50 px-1 py-1 rounded-md border border-green-400"></i>
                                        </button>
                                    </div>
HTML;

// Replace for Pending RadTech Review
$content = str_replace($oldButtons, $newButtons, $content);

// For Pending RadTech Verification
$oldVerify = <<<HTML
                                        <button type="button" onclick="openResolveModal(<?= \$d['id'] ?>, '<?= htmlspecialchars(\$d['case_number']) ?>', 'Final Approve & Release Amended Report', <?= htmlspecialchars(json_encode(\$d['description']), ENT_QUOTES, 'UTF-8') ?>, <?= htmlspecialchars(json_encode(\$d['old_findings'] ?? ''), ENT_QUOTES, 'UTF-8') ?>, <?= htmlspecialchars(json_encode(\$d['new_findings'] ?? ''), ENT_QUOTES, 'UTF-8') ?>, <?= htmlspecialchars(json_encode(\$d['old_impression'] ?? ''), ENT_QUOTES, 'UTF-8') ?>, <?= htmlspecialchars(json_encode(\$d['new_impression'] ?? ''), ENT_QUOTES, 'UTF-8') ?>)"
                                                class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold shadow transition-all active:scale-95">
                                            <i data-lucide="file-check-2" class="w-4 h-4"></i> Final Verify & Release
                                        </button>
HTML;

$newVerify = <<<HTML
                                        <div class="flex items-center justify-end">
                                            <button type="button" onclick="openResolveModal(<?= \$d['id'] ?>, '<?= htmlspecialchars(\$d['case_number']) ?>', 'Final Approve & Release Amended Report', <?= htmlspecialchars(json_encode(\$d['description']), ENT_QUOTES, 'UTF-8') ?>, <?= htmlspecialchars(json_encode(\$d['old_findings'] ?? ''), ENT_QUOTES, 'UTF-8') ?>, <?= htmlspecialchars(json_encode(\$d['new_findings'] ?? ''), ENT_QUOTES, 'UTF-8') ?>, <?= htmlspecialchars(json_encode(\$d['old_impression'] ?? ''), ENT_QUOTES, 'UTF-8') ?>, <?= htmlspecialchars(json_encode(\$d['new_impression'] ?? ''), ENT_QUOTES, 'UTF-8') ?>)"
                                                    title="Final Verify & Release" class="text-indigo-500 hover:text-indigo-700 transition active:scale-95">
                                                <i data-lucide="file-check-2" class="w-6 h-6 bg-indigo-50 px-1 py-1 rounded-md border border-indigo-400"></i>
                                            </button>
                                        </div>
HTML;
$content = str_replace($oldVerify, $newVerify, $content);

file_put_contents($file, $content);
echo "Fixed smaller icons.";
