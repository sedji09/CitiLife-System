<!-- IT Security Settings -->
<div class="max-w-4xl mx-auto space-y-6">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Security Policies</h1>
            <p class="text-sm text-gray-500 mt-1">Configure system-wide encryption and session protocols.</p>
        </div>
        <div class="flex items-center gap-2 px-3 py-1.5 bg-rose-50 border border-rose-100 rounded-full">
            <i data-lucide="shield-check" class="w-4 h-4 text-rose-600"></i>
            <span class="text-[10px] font-black text-rose-700 uppercase tracking-widest leading-none">Security Guard
                Active</span>
        </div>
    </div>

    <?php if ($success): ?>
        <div class="rounded-xl bg-green-50 border border-green-200 p-4 animate-in fade-in slide-in-from-top-2">
            <div class="flex items-center gap-3">
                <i data-lucide="check-circle-2" class="w-5 h-5 text-green-600"></i>
                <p class="text-sm font-bold text-green-800"><?= htmlspecialchars($success) ?></p>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="rounded-xl bg-red-50 border border-red-200 p-4 animate-in fade-in slide-in-from-top-2">
            <div class="flex items-center gap-3">
                <i data-lucide="alert-circle" class="w-5 h-5 text-red-600"></i>
                <p class="text-sm font-bold text-red-800"><?= htmlspecialchars($error) ?></p>
            </div>
        </div>
    <?php endif; ?>

    <form action="" method="POST" class="space-y-6">
        <input type="hidden" name="action" value="update_security">

        <!-- Password Policy Card -->
        <div
            class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden transition-shadow hover:shadow-md">
            <div class="px-6 py-5 border-b border-gray-100 bg-gray-50/50 flex items-center gap-4">
                <div class="p-2.5 bg-indigo-50 rounded-xl text-indigo-600">
                    <i data-lucide="lock" class="w-5 h-5"></i>
                </div>
                <div>
                    <h3 class="text-base font-bold text-gray-900">Authentication Policy</h3>
                    <p class="text-xs text-gray-500 font-medium">Control the strength requirements for user credentials.
                    </p>
                </div>
            </div>
            <div class="p-8 space-y-8">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                    <div class="space-y-1 max-w-md">
                        <label for="min_password_length" class="text-sm font-bold text-gray-800">Minimum Password
                            Length</label>
                        <p class="text-xs text-gray-500 leading-relaxed">Sets the required minimum character count for
                            new passwords and updates across all staff accounts.</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="relative w-32">
                            <input type="number" id="min_password_length" name="min_password_length"
                                value="<?= $minPassLength ?>" min="6" max="32"
                                class="w-full pl-4 pr-10 py-3 rounded-xl border border-gray-200 bg-gray-50/50 text-sm font-black focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-center">
                            <span
                                class="absolute right-3 mt-4 text-[10px] font-bold text-gray-400 uppercase tracking-tighter">Chars</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Session Policy Card -->
        <div
            class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden transition-shadow hover:shadow-md">
            <div class="px-6 py-5 border-b border-gray-100 bg-gray-50/50 flex items-center gap-4">
                <div class="p-2.5 bg-amber-50 rounded-xl text-amber-600">
                    <i data-lucide="timer" class="w-5 h-5"></i>
                </div>
                <div>
                    <h3 class="text-base font-bold text-gray-900">Session Protocols</h3>
                    <p class="text-xs text-gray-500 font-medium">Configure session activity limits and auto-logout
                        rules.</p>
                </div>
            </div>
            <div class="p-8 space-y-8">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                    <div class="space-y-1 max-w-md">
                        <label for="auto_logout_minutes" class="text-sm font-bold text-gray-800">Auto-Logout
                            Timeout</label>
                        <p class="text-xs text-gray-500 leading-relaxed">System will automatically terminate the user
                            session after the specified duration of inactivity.</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="relative w-32">
                            <select id="auto_logout_minutes" name="auto_logout_minutes"
                                class="w-full px-4 py-3 rounded-xl border border-gray-200 bg-gray-50/50 text-sm font-black focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 appearance-none text-center">
                                <option value="5" <?= $autoLogoutMins == 5 ? 'selected' : '' ?>>5 Min</option>
                                <option value="15" <?= $autoLogoutMins == 15 ? 'selected' : '' ?>>15 Min</option>
                                <option value="30" <?= $autoLogoutMins == 30 ? 'selected' : '' ?>>30 Min</option>
                                <option value="60" <?= $autoLogoutMins == 60 ? 'selected' : '' ?>>1 Hour</option>
                                <option value="120" <?= $autoLogoutMins == 120 ? 'selected' : '' ?>>2 Hours</option>
                            </select>
                            <i data-lucide="chevron-down"
                                class="absolute right-3 top-3.5 w-4 h-4 text-gray-400 pointer-events-none"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3 pt-4">
            <button type="reset"
                class="px-6 py-3 rounded-xl border border-gray-200 text-sm font-bold text-gray-600 hover:bg-gray-50 transition shadow-sm">
                Discard Changes
            </button>
            <button type="button" 
                onclick="confirmFormAction(this, 'update_security', 'Confirm Policy Update', 'Are you sure you want to apply these system-wide security changes? All staff accounts will be immediately affected.', 'action', event)"
                class="px-8 py-3 rounded-xl bg-red-600 text-sm font-bold text-white hover:bg-red-700 shadow-lg shadow-red-500/30 transition transform active:scale-95">
                Save Security Policies
            </button>
        </div>
    </form>
</div>

<script>
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
</script>