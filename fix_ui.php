<?php
$file = 'c:\\xampp\\htdocs\\CitiLife-System\\views\\pages\\radtech\\patient-lists.view.php';
$content = file_get_contents($file);

// Fix truncate
$oldTruncate = '<td class="py-3 px-4 max-w-[200px]">';
$newTruncate = '<td class="py-3 px-4 max-w-[250px] overflow-hidden">';
$content = str_replace($oldTruncate, $newTruncate, $content);

$oldTruncate2 = '<div class="truncate text-xs font-medium text-amber-800 bg-amber-50 px-2 py-1 rounded inline-block"';
$newTruncate2 = '<div class="truncate block w-full text-xs font-medium text-amber-800 bg-amber-50 px-2 py-1.5 rounded"';
$content = str_replace($oldTruncate2, $newTruncate2, $content);


// Replace Buttons Block for "Pending RadTech Review"
// Since this is a bit complex, let's use a regex or string replacement.
$oldButtons = <<<HTML
                                        <button type="button" onclick="openReuploadModal(<?= \$d['id'] ?>, '<?= htmlspecialchars(\$d['case_number']) ?>')"
                                                class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold transition-all active:scale-95">
                                            <i data-lucide="upload-cloud" class="w-4 h-4"></i> Re-upload & Correct
                                        </button>
                                        <button type="button" onclick="openEscalateModal(<?= \$d['id'] ?>, '<?= htmlspecialchars(\$d['case_number']) ?>')"
                                                class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 text-xs font-bold transition-all shadow-sm active:scale-95">
                                            <i data-lucide="forward" class="w-4 h-4 text-purple-600"></i> Escalate to Radiologist
                                        </button>
                                        <button type="button" onclick="openResolveModal(<?= \$d['id'] ?>, '<?= htmlspecialchars(\$d['case_number']) ?>', 'Fix & Resolve Ticket', <?= htmlspecialchars(json_encode(\$d['description']), ENT_QUOTES, 'UTF-8') ?>)"
                                                class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-green-600 hover:bg-green-700 text-white text-xs font-bold shadow transition-all active:scale-95">
                                            <i data-lucide="check-circle" class="w-4 h-4"></i> Fix & Resolve
                                        </button>
HTML;

$newButtons = <<<HTML
                                        <div class="flex items-center justify-end gap-2">
                                            <button type="button" onclick="openReuploadModal(<?= \$d['id'] ?>, '<?= htmlspecialchars(\$d['case_number']) ?>')"
                                                    title="Re-upload & Correct"
                                                    class="w-9 h-9 rounded-lg border border-blue-200 bg-blue-50 hover:bg-blue-100 text-blue-600 flex items-center justify-center transition-all active:scale-95 shadow-sm">
                                                <i data-lucide="upload-cloud" class="w-4 h-4"></i>
                                            </button>
                                            <button type="button" onclick="openEscalateModal(<?= \$d['id'] ?>, '<?= htmlspecialchars(\$d['case_number']) ?>')"
                                                    title="Escalate to Radiologist"
                                                    class="w-9 h-9 rounded-lg border border-red-200 bg-red-50 hover:bg-red-100 text-red-600 flex items-center justify-center transition-all active:scale-95 shadow-sm">
                                                <i data-lucide="forward" class="w-4 h-4"></i>
                                            </button>
                                            <button type="button" onclick="openResolveModal(<?= \$d['id'] ?>, '<?= htmlspecialchars(\$d['case_number']) ?>', 'Fix & Resolve Ticket', <?= htmlspecialchars(json_encode(\$d['description']), ENT_QUOTES, 'UTF-8') ?>)"
                                                    title="Fix & Resolve"
                                                    class="w-9 h-9 rounded-lg border border-green-200 bg-green-50 hover:bg-green-100 text-green-600 flex items-center justify-center transition-all active:scale-95 shadow-sm">
                                                <i data-lucide="check-circle-2" class="w-4 h-4"></i>
                                            </button>
                                        </div>
HTML;

$content = str_replace($oldButtons, $newButtons, $content);

file_put_contents($file, $content);
echo "Fixed UI.";
