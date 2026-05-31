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
                <h1 class="text-2xl font-bold text-gray-900">System Configuration</h1>
                <p class="text-sm text-gray-500">Manage global settings and branding for the entire clinic network</p>
            </div>
            <div class="flex items-center gap-2">
                <div class="px-3 py-1 bg-blue-50 text-blue-600 rounded-full text-[10px] font-bold uppercase tracking-widest border border-blue-100">
                    Administrator Access
                </div>
            </div>
        </div>

        <?php if ($success): ?>
            <div id="statusAlert" class="rounded-xl bg-green-50 border border-green-200 p-4 animate-in fade-in slide-in-from-top-2 duration-300">
                <div class="flex items-center gap-3">
                    <i data-lucide="check-circle-2" class="w-5 h-5 text-green-600"></i>
                    <p class="text-sm font-medium text-green-800"><?= htmlspecialchars($success) ?></p>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div id="statusAlert" class="rounded-xl bg-red-50 border border-red-200 p-4 animate-in fade-in slide-in-from-top-2 duration-300">
                <div class="flex items-center gap-3">
                    <i data-lucide="alert-circle" class="w-5 h-5 text-red-600"></i>
                    <p class="text-sm font-medium text-red-800"><?= htmlspecialchars($error) ?></p>
                </div>
            </div>
        <?php endif; ?>

        <form action="" method="POST" enctype="multipart/form-data" class="space-y-6">
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
                                <div class="h-32 w-32 rounded-2xl border-2 border-dashed border-gray-200 bg-gray-50/50 flex items-center justify-center overflow-hidden group">
                                    <?php if ($currentLogo): ?>
                                        <img src="<?= '/' . PROJECT_DIR . '/' . $currentLogo ?>" class="h-full w-full object-contain p-2 transition-transform group-hover:scale-110">
                                    <?php else: ?>
                                        <i data-lucide="image-plus" class="w-8 h-8 text-gray-300"></i>
                                    <?php endif; ?>
                                </div>
                                <!-- Upload Input -->
                                <div class="flex-1 w-full">
                                    <label class="block">
                                        <span class="sr-only">Choose logo</span>
                                        <input type="file" name="clinic_logo" accept="image/*"
                                            class="block w-full text-sm text-gray-500
                                                        file:mr-4 file:py-2.5 file:px-4
                                                        file:rounded-xl file:border-0
                                                        file:text-sm file:font-bold
                                                        file:bg-blue-50 file:text-blue-700
                                                        hover:file:bg-blue-100 transition-all cursor-pointer">
                                    </label>
                                    <p class="mt-2 text-[10px] text-gray-400 italic">Changing the logo will update it across all branch reports and logins.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Maintenance & Security (Placeholders or Future proofing) -->
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
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center justify-end gap-3 pt-6">
                <button type="reset" class="px-6 py-2.5 rounded-xl border border-gray-200 text-sm font-bold text-gray-600 hover:bg-gray-50 transition-all">
                    Reset Changes
                </button>
                <button type="submit" class="px-8 py-2.5 rounded-xl bg-blue-600 text-sm font-bold text-white hover:bg-blue-700 shadow-lg shadow-blue-200 transition-all active:scale-95 flex items-center gap-2">
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
});
</script>
