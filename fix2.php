<?php
$file = 'c:/xampp/htdocs/CitiLife-System/views/pages/radtech/patient-lists.view.php';
$content = file_get_contents($file);

// Array of exact string replacements
$replacements = [
    // 155
    "data-name=\"<?= htmlspecialchars(\$row['first_name'] . ' ' . \$row['last_name']) ?>\"" =>
    "data-name=\"<?= htmlspecialchars((\$row['first_name'] ?? '') . ' ' . (\$row['last_name'] ?? '')) ?>\"",
    
    // 156
    "data-priority=\"<?= htmlspecialchars(\$row['priority']) ?>\"" =>
    "data-priority=\"<?= htmlspecialchars(\$row['priority'] ?? 'Routine') ?>\"",
    
    // 157
    "data-exam=\"<?= htmlspecialchars(\$row['exam_type']) ?>\"" =>
    "data-exam=\"<?= htmlspecialchars(\$row['exam_type'] ?? '') ?>\"",
    
    // 167
    "title=\"<?= htmlspecialchars(\$row['first_name'] . ' ' . \$row['last_name']) ?>\"" =>
    "title=\"<?= htmlspecialchars((\$row['first_name'] ?? '') . ' ' . (\$row['last_name'] ?? '')) ?>\"",
    
    // 168
    "<?= htmlspecialchars(\$row['first_name'] . ' ' . \$row['last_name']) ?>" =>
    "<?= htmlspecialchars((\$row['first_name'] ?? '') . ' ' . (\$row['last_name'] ?? '')) ?>",
    
    // 172
    "\$exams = array_filter(array_map('trim', explode(',', \$row['exam_type'])));" =>
    "\$exams = array_filter(array_map('trim', explode(',', \$row['exam_type'] ?? '')));",
    
    // 178
    "title=\"<?= htmlspecialchars(\$row['exam_type']) ?>\"><?= htmlspecialchars(\$firstExam) ?></span>" =>
    "title=\"<?= htmlspecialchars(\$row['exam_type'] ?? '') ?>\"><?= htmlspecialchars(\$firstExam ?: 'None') ?></span>",
    
    // 182
    "title=\"<?= htmlspecialchars(\$row['exam_type']) ?>\">+<?= \$extraCount ?></span>" =>
    "title=\"<?= htmlspecialchars(\$row['exam_type'] ?? '') ?>\">+<?= \$extraCount ?></span>",
    
    // 210
    "<?= htmlspecialchars(\$row['priority']) ?>" =>
    "<?= htmlspecialchars(\$row['priority'] ?? 'Routine') ?>",
    
    // 534
    "<div class=\"font-medium\"><?= htmlspecialchars(\$d['first_name'] . ' ' . \$d['last_name']) ?></div>" =>
    "<div class=\"font-medium\"><?= htmlspecialchars((\$d['first_name'] ?? '') . ' ' . (\$d['last_name'] ?? '')) ?></div>"
];

$changes = 0;
foreach ($replacements as $search => $replace) {
    if (strpos($content, $search) !== false) {
        $content = str_replace($search, $replace, $content);
        $changes++;
    } else {
        echo "Could not find: $search\n";
    }
}

if ($changes > 0) {
    file_put_contents($file, $content);
    echo "Made $changes replacements safely.\n";
} else {
    echo "No changes made.\n";
}
