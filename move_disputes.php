<?php
$file = 'c:\\xampp\\htdocs\\CitiLife-System\\views\\pages\\radtech\\patient-lists.view.php';
$content = file_get_contents($file);

// Find the start of disputes-table-card
$start = strpos($content, '<div id="disputes-table-card"');
// Find the end of the script that I injected
$endStr = '}, 3000);' . "\n" . '</script>';
$end = strpos($content, $endStr);

if ($start !== false && $end !== false) {
    $end += strlen($endStr);
    
    // Extract it
    $disputesBlock = substr($content, $start, $end - $start);
    
    // Remove it from the top
    $content = substr_replace($content, '', $start, $end - $start);
    
    // Insert it after the main table's closing div.
    // The main table ends with:
    //         </table>
    //     </div>
    // </div>
    // We can just append it before the final <script> or at the very end.
    // But wait, there is a final <script> block at the bottom?
    // Let's just append it at the end of the file!
    $content .= "\n" . $disputesBlock . "\n";
    
    file_put_contents($file, $content);
    echo "Moved disputes block to the bottom.";
} else {
    echo "Could not find start or end.";
}
