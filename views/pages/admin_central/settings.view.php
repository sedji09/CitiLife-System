<?php
/**
 * Settings View (Central Admin)
 * Handles global system configuration.
 */
?>

<main class="flex-1 overflow-y-auto p-4 lg:p-6">
    <div class="mx-auto max-w-4xl space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">System Configuration</h1>
                <p class="text-sm text-gray-500">Manage global settings and branding for the entire clinic network</p>
            </div>
            <div class="flex items-center gap-2">
                <div
                    class="px-3 py-1 bg-blue-50 text-blue-600 rounded-full text-[10px] font-bold uppercase tracking-widest border border-blue-100">
                    Administrator Access
                </div>
            </div>
        </div>

        <?php if ($success): ?>
            <div id="statusAlert"
                class="rounded-xl bg-green-50 border border-green-200 p-4 animate-in fade-in slide-in-from-top-2 duration-300">
                <div class="flex items-center gap-3">
                    <i data-lucide="check-circle-2" class="w-5 h-5 text-green-600"></i>
                    <p class="text-sm font-medium text-green-800"><?= htmlspecialchars($success) ?></p>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div id="statusAlert"
                class="rounded-xl bg-red-50 border border-red-200 p-4 animate-in fade-in slide-in-from-top-2 duration-300">
                <div class="flex items-center gap-3">
                    <i data-lucide="alert-circle" class="w-5 h-5 text-red-600"></i>
                    <p class="text-sm font-medium text-red-800"><?= htmlspecialchars($error) ?></p>
                </div>
            </div>
        <?php endif; ?>

        <form id="settingsForm" action="" method="POST" enctype="multipart/form-data" class="space-y-6">
            <input type="hidden" name="action" value="update_settings">

            <!-- General Branding Card -->
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="p-6 border-b border-gray-100 bg-gray-50/30">
                    <div class="flex items-center gap-3">
                        <div class="h-10 w-10 rounded-xl bg-blue-50 flex items-center justify-center text-blue-600">
                            <i data-lucide="palette" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-gray-900">System Branding</h3>
                            <p class="text-xs text-gray-500">Customize the application name and clinic logo</p>
                        </div>
                    </div>
                </div>

                <div class="p-6 space-y-6">
                    <!-- System Name -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-start">
                        <div class="space-y-1">
                            <label class="text-sm font-bold text-gray-700">System Display Name</label>
                            <p class="text-[11px] text-gray-400">Used in titles and browser tabs</p>
                        </div>
                        <div class="md:col-span-2">
                            <input type="text" name="system_name" value="<?= htmlspecialchars($currentSystemName) ?>"
                                class="w-full px-4 py-2.5 rounded-xl border border-gray-200 bg-stone-50 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/10 focus:border-blue-500 transition-all">
                        </div>
                    </div>

                    <hr class="border-gray-50">

                    <!-- Logo Upload -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-start">
                        <div class="space-y-1">
                            <label class="text-sm font-bold text-gray-700">Clinic Logo</label>
                            <p class="text-[11px] text-gray-400">Recommended size: 500x500px (PNG)</p>
                        </div>
                        <div class="md:col-span-2">
                            <div class="flex flex-col sm:flex-row items-center gap-6">
                                <!-- Current Logo Preview -->
                                <div
                                    class="h-32 w-32 rounded-2xl border-2 border-dashed border-gray-200 bg-gray-50/50 flex items-center justify-center overflow-hidden group">
                                    <?php if ($currentLogo): ?>
                                        <img src="<?= '/' . PROJECT_DIR . '/' . $currentLogo ?>"
                                            class="h-full w-full object-contain p-2 transition-transform group-hover:scale-110">
                                    <?php else: ?>
                                        <i data-lucide="image-plus" class="w-8 h-8 text-gray-300"></i>
                                    <?php endif; ?>
                                </div>
                                <!-- Upload Input -->
                                <div class="flex-1 w-full">
                                    <label class="block">
                                        <span class="sr-only">Choose logo</span>
                                        <input type="file" name="clinic_logo" accept="image/*" class="block w-full text-sm text-gray-500
                                                        file:mr-4 file:py-2.5 file:px-4
                                                        file:rounded-xl file:border-0
                                                        file:text-sm file:font-bold
                                                        file:bg-blue-50 file:text-blue-700
                                                        hover:file:bg-blue-100 transition-all cursor-pointer">
                                    </label>
                                    <p class="mt-2 text-[10px] text-gray-400 italic">Changing the logo will update it
                                        across all branch reports and logins.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- System Status Card -->
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="p-6 border-b border-gray-100 bg-gray-50/30">
                    <div class="flex items-center gap-3">
                        <div class="h-10 w-10 rounded-xl bg-red-50 flex items-center justify-center text-red-600">
                            <i data-lucide="power" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-gray-900">System Status</h3>
                            <p class="text-xs text-gray-500">Temporarily close the portal for patients requesting X-rays</p>
                        </div>
                    </div>
                </div>

                <div class="p-6 space-y-6">
                    <!-- Status Toggle -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                        <div class="space-y-1">
                            <label class="text-sm font-bold text-gray-700">System State</label>
                            <p class="text-[11px] text-gray-400">Toggle whether the system is open or closed.</p>
                        </div>
                        <div class="md:col-span-2">
                            <div class="flex items-center gap-3">
                                <span class="text-sm font-bold transition-colors <?= $systemStatus !== 'closed' ? 'text-gray-900' : 'text-gray-400' ?>" id="labelOpen">Open</span>
                                
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="system_status" value="closed" class="sr-only" id="systemStatusToggle" <?= $systemStatus === 'closed' ? 'checked' : '' ?>>
                                    <div id="toggleBg" class="relative w-14 h-7 rounded-full transition-colors duration-300 <?= $systemStatus === 'closed' ? 'bg-red-600' : 'bg-gray-200' ?>">
                                        <div id="toggleKnob" class="absolute bg-white rounded-full shadow-sm transition-transform duration-300" 
                                             style="top: 2px; left: 2px; height: 24px; width: 24px; <?= $systemStatus === 'closed' ? 'transform: translateX(28px);' : 'transform: translateX(0);' ?>"></div>
                                    </div>
                                </label>
                                
                                <span class="text-sm font-bold transition-colors <?= $systemStatus === 'closed' ? 'text-red-600' : 'text-gray-400' ?>" id="labelClosed">Closed</span>
                            </div>
                        </div>
                    </div>

                    <div id="closedSettingsWrapper" class="space-y-6 <?= $systemStatus === 'closed' ? 'block' : 'hidden' ?>">
                        <hr class="border-gray-50">

                        <!-- Closed Branches Selection -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-start">
                            <div class="space-y-1">
                                <label class="text-sm font-bold text-gray-700">Closed Branches</label>
                                <p class="text-[11px] text-gray-400">Select which branches are closed. Or select 'All Branches'.</p>
                            </div>
                            <div class="md:col-span-2">
                                <div class="border border-gray-200 rounded-xl overflow-hidden bg-white shadow-inner max-h-48 overflow-y-auto">
                                    <label class="flex items-center gap-3 p-3 border-b border-gray-100 hover:bg-gray-50 cursor-pointer transition-colors">
                                        <input type="checkbox" name="closed_branches[]" value="all" <?= in_array('all', $closedBranchesArr) ? 'checked' : '' ?> class="rounded border-gray-300 text-red-600 focus:ring-red-500 w-4 h-4 cursor-pointer">
                                        <span class="text-sm font-bold text-red-600">All Branches</span>
                                    </label>
                                    <?php foreach ($branches as $b): ?>
                                        <label class="flex items-center gap-3 p-3 border-b border-gray-100 hover:bg-gray-50 cursor-pointer transition-colors last:border-0">
                                            <input type="checkbox" name="closed_branches[]" value="<?= $b['id'] ?>" <?= in_array((string)$b['id'], $closedBranchesArr) && !in_array('all', $closedBranchesArr) ? 'checked' : '' ?> class="rounded border-gray-300 text-red-600 focus:ring-red-500 w-4 h-4 cursor-pointer">
                                            <span class="text-sm font-medium text-gray-700"><?= htmlspecialchars($b['name']) ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                                <p class="text-[10px] text-gray-500 mt-2">Check the branches that should be temporarily closed.</p>
                                <p id="branchErrorMsg" class="text-xs text-red-600 font-bold mt-1 hidden"><i data-lucide="alert-circle" class="w-3 h-3 inline-block mr-1"></i>Please select at least one branch to close.</p>
                            </div>
                        </div>

                        <!-- Closed Announcement -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-start">
                            <div class="space-y-1">
                                <label class="text-sm font-bold text-gray-700">Announcement Message</label>
                                <p class="text-[11px] text-gray-400">Message to show to patients.</p>
                            </div>
                            <div class="md:col-span-2">
                                <textarea name="closed_message" rows="3" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 bg-stone-50 text-sm focus:outline-none focus:ring-2 focus:ring-red-500/10 focus:border-red-500 transition-all placeholder:text-gray-400" placeholder="e.g. We are temporarily closed for maintenance..." <?= $systemStatus === 'closed' ? 'required' : '' ?>><?= htmlspecialchars($closedMessage) ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Maintenance & Security (Placeholders or Future proofing)
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden opacity-60 pointer-events-none grayscale">
                <div class="p-6 border-b border-gray-100 bg-gray-50/30 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="h-10 w-10 rounded-xl bg-orange-50 flex items-center justify-center text-orange-600">
                            <i data-lucide="shield-alert" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-gray-900">Security & Maintenance</h3>
                            <p class="text-xs text-gray-500 tracking-tight">System-wide security policies</p>
                        </div>
                    </div>
                    <span class="px-2 py-0.5 bg-gray-100 text-gray-500 rounded text-[9px] font-bold uppercase tracking-wider">Coming Soon</span>
                </div>
                <div class="p-6 flex items-center justify-center h-24">
                    <p class="text-xs text-gray-400 font-medium">Automatic Logout and Backup settings will be available in the next update.</p>
                </div>
            </div> -->

            <!-- Action Buttons -->
            <div class="flex items-center justify-end gap-3 pt-6">
                <button type="reset"
                    class="px-6 py-2.5 rounded-xl border border-gray-200 text-sm font-bold text-gray-600 hover:bg-gray-50 transition-all">
                    Reset Changes
                </button>
                <button type="submit"
                    class="px-8 py-2.5 rounded-xl bg-blue-600 text-sm font-bold text-white hover:bg-blue-700 shadow-lg shadow-blue-200 transition-all active:scale-95 flex items-center gap-2">
                    <i data-lucide="save" class="w-4 h-4"></i>
                    Apply Global Settings
                </button>
            </div>
        </form>
    </div>
</main>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (window.lucide) window.lucide.createIcons();

        // Auto-dismiss alerts
        const alert = document.getElementById('statusAlert');
        if (alert) {
            setTimeout(() => {
                alert.style.transition = "opacity 0.5s ease";
                alert.style.opacity = "0";
                setTimeout(() => alert.remove(), 500);
            }, 3000);
        }

        // Toggle System Status Wrapper
        const systemStatusToggle = document.getElementById('systemStatusToggle');
        const labelOpen = document.getElementById('labelOpen');
        const labelClosed = document.getElementById('labelClosed');
        const closedSettingsWrapper = document.getElementById('closedSettingsWrapper');
        const toggleBg = document.getElementById('toggleBg');
        const toggleKnob = document.getElementById('toggleKnob');

        if (systemStatusToggle) {
            // Function to update visual state
            const updateToggleUI = (isChecked, isUserAction = false) => {
                const msgBox = document.querySelector('textarea[name="closed_message"]');
                const masterCheckbox = document.querySelector('input[name="closed_branches[]"][value="all"]');
                const childCheckboxes = document.querySelectorAll('input[name="closed_branches[]"]:not([value="all"])');

                if (isChecked) {
                    labelOpen.classList.replace('text-gray-900', 'text-gray-400');
                    labelClosed.classList.replace('text-gray-400', 'text-red-600');
                    
                    closedSettingsWrapper.classList.remove('hidden');
                    // Add red background, remove gray
                    toggleBg.classList.remove('bg-gray-200');
                    toggleBg.classList.add('bg-red-600');
                    // Move knob
                    toggleKnob.style.transform = 'translateX(28px)';
                    
                    if (msgBox) msgBox.setAttribute('required', 'required');
                } else {
                    labelOpen.classList.replace('text-gray-400', 'text-gray-900');
                    labelClosed.classList.replace('text-red-600', 'text-gray-400');
                    
                    closedSettingsWrapper.classList.add('hidden');
                    // Add gray background, remove red
                    toggleBg.classList.remove('bg-red-600');
                    toggleBg.classList.add('bg-gray-200');
                    // Move knob back
                    toggleKnob.style.transform = 'translateX(0)';
                    
                    // Clear fields and remove required validation if it's open
                    if (msgBox) msgBox.removeAttribute('required');
                    
                    if (isUserAction) {
                        if (masterCheckbox) masterCheckbox.checked = false;
                        if (childCheckboxes) childCheckboxes.forEach(cb => cb.checked = false);
                        if (msgBox) msgBox.value = '';
                    }
                }
            };

            // Set initial state
            updateToggleUI(systemStatusToggle.checked, false);

            systemStatusToggle.addEventListener('change', function () {
                updateToggleUI(this.checked, true);
            });
        }

        // Select All Branches Logic
        const allBranchesCheckbox = document.querySelector('input[name="closed_branches[]"][value="all"]');
        const branchCheckboxes = document.querySelectorAll('input[name="closed_branches[]"]:not([value="all"])');

        if (allBranchesCheckbox && branchCheckboxes.length > 0) {
            // Function to update the master checkbox state
            const updateAllCheckboxState = () => {
                const allChecked = Array.from(branchCheckboxes).every(cb => cb.checked);
                allBranchesCheckbox.checked = allChecked;
            };

            // If "All Branches" is initially checked, ensure all others are checked visually
            if (allBranchesCheckbox.checked) {
                branchCheckboxes.forEach(cb => cb.checked = true);
            } else {
                updateAllCheckboxState();
            }

            // Listen for clicks on the "All Branches" checkbox
            allBranchesCheckbox.addEventListener('change', function() {
                branchCheckboxes.forEach(cb => {
                    cb.checked = this.checked;
                });
            });

            // Listen for clicks on individual branch checkboxes
            branchCheckboxes.forEach(cb => {
                cb.addEventListener('change', updateAllCheckboxState);
            });
        }
        
        // Form Validation for Branch Selection
        const settingsForm = document.getElementById('settingsForm');
        const branchErrorMsg = document.getElementById('branchErrorMsg');
        
        if (settingsForm && systemStatusToggle) {
            settingsForm.addEventListener('submit', function(e) {
                if (systemStatusToggle.checked) {
                    const anyBranchChecked = Array.from(document.querySelectorAll('input[name="closed_branches[]"]')).some(cb => cb.checked);
                    if (!anyBranchChecked) {
                        e.preventDefault();
                        if (branchErrorMsg) {
                            branchErrorMsg.classList.remove('hidden');
                            // Refresh Lucide icons in case the icon was hidden initially
                            if (typeof lucide !== 'undefined') {
                                lucide.createIcons();
                            }
                        }
                    } else {
                        if (branchErrorMsg) {
                            branchErrorMsg.classList.add('hidden');
                        }
                    }
                }
            });
            
            // Hide error when they check a branch
            branchCheckboxes.forEach(cb => {
                cb.addEventListener('change', () => {
                     if (branchErrorMsg && cb.checked) {
                         branchErrorMsg.classList.add('hidden');
                     }
                });
            });
            if (allBranchesCheckbox) {
                allBranchesCheckbox.addEventListener('change', () => {
                     if (branchErrorMsg && allBranchesCheckbox.checked) {
                         branchErrorMsg.classList.add('hidden');
                     }
                });
            }
        }
    });
</script>