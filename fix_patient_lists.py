import sys

file_path = 'c:/xampp/htdocs/CitiLife-System/views/pages/radtech/patient-lists.view.php'
with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

target = """                                        <!-- Print — disabled -->
                                        <button class="text-gray-400 cursor-not-allowed"
                let targetRow = null;"""

replacement = """                                        <!-- Print — disabled -->
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
                let targetRow = null;"""

if target in content:
    content = content.replace(target, replacement)
    with open(file_path, 'w', encoding='utf-8') as f:
        f.write(content)
    print('Successfully restored the missing lines!')
else:
    print('Target not found! Check the file contents.')
