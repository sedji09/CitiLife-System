<?php
$file = 'c:/xampp/htdocs/CitiLife-System/views/pages/radtech/patient-lists.view.php';
$content = file_get_contents($file);

$target = "<!-- Print — disabled -->\r\n                                        <button class=\"text-gray-400 cursor-not-allowed\"\r\n                let targetRow = null;";

$replacement = <<<EOF
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
        // Default Sort & Filter
        setTimeout(() => {
            applyFilters();
        }, 100);

        // ── Highlight row from notification ───────────────────────────────
        setTimeout(() => {
            const params = new window.URLSearchParams(window.location.search);
            const highlightId = params.get('highlight');
            if (highlightId) {
                const rows = document.querySelectorAll('#table-body tr.record-row');
                let targetRow = null;
EOF;

// Normalize line endings in replacement
$replacement = str_replace("\n", "\r\n", $replacement);
$replacement = str_replace("\r\r\n", "\r\n", $replacement);

if (strpos($content, $target) !== false) {
    $content = str_replace($target, $replacement, $content);
    file_put_contents($file, $content);
    echo "Successfully fixed!\n";
} else {
    // Try \n instead of \r\n
    $target2 = "<!-- Print — disabled -->\n                                        <button class=\"text-gray-400 cursor-not-allowed\"\n                let targetRow = null;";
    if (strpos($content, $target2) !== false) {
        $content = str_replace($target2, $replacement, $content);
        file_put_contents($file, $content);
        echo "Successfully fixed with \\n!\n";
    } else {
        echo "Target not found!\n";
    }
}
