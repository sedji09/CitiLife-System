<?php
$pdo = require 'c:\\xampp\\htdocs\\CitiLife-System\\config\\database.php';
$stmt = $pdo->query("SELECT id, description FROM result_disputes");
$rows = $stmt->fetchAll();
$count = 0;

foreach ($rows as $row) {
    $desc = trim($row['description']);
    $originalDesc = $desc;
    
    // Replace "Info correction:" with "Wrong Patient Info:"
    if (strpos($desc, 'Info correction:') !== false) {
        $desc = str_replace('Info correction:', 'Wrong Patient Info:', $desc);
    }
    
    // Fix rows that are raw text like "Age: 23" or "First Name: Jay-R"
    // that don't have "Wrong Patient Info" or "Info correction" or "Findings Note" or "Correction requested"
    if (
        (strpos($desc, 'First Name:') !== false || strpos($desc, 'Last Name:') !== false || 
         strpos($desc, 'Age:') !== false || strpos($desc, 'Sex:') !== false) &&
        strpos($desc, 'Wrong Patient Info:') === false && 
        strpos($desc, 'Correction requested:') === false
    ) {
        // It's a raw input string like "First Name: Jay-R, Last Name: Maglaque" or "Age: 22"
        // Let's replace commas with newlines if they are on the same line
        $parts = preg_split('/(,\s*|\n)/', $desc);
        $formattedParts = [];
        foreach ($parts as $part) {
            $part = trim($part);
            if ($part) {
                // Remove existing bullet points just in case
                $part = ltrim($part, '•*- ');
                $formattedParts[] = "  • " . $part;
            }
        }
        $desc = "Wrong Patient Info:\n" . implode("\n", $formattedParts);
    }

    // Fix rows that have "Correction requested:"
    if (strpos($desc, 'Correction requested:') !== false) {
        $desc = str_replace('Correction requested:', "Wrong Patient Info:", $desc);
        $lines = explode("\n", $desc);
        $formattedLines = [];
        foreach ($lines as $line) {
            if (trim($line) === 'Wrong Patient Info:') {
                $formattedLines[] = trim($line);
            } else if (trim($line) !== '') {
                $cleanLine = ltrim(trim($line), '•*- ');
                $formattedLines[] = "  • " . $cleanLine;
            }
        }
        $desc = implode("\n", $formattedLines);
    }

    if ($desc !== $originalDesc) {
        $upd = $pdo->prepare("UPDATE result_disputes SET description = ? WHERE id = ?");
        $upd->execute([$desc, $row['id']]);
        $count++;
    }
}

echo "$count older dispute records formatted to the new style.\n";
?>
