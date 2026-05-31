<?php
/**
 * Patient Registration View (Portal)
 * Backend logic handled by RegistrationController.php
 */
?>

<div class="space-y-5 pb-8 max-w-3xl mx-auto">

    <!-- Header -->
    <div>
        <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Patient Registration</h1>
        <p class="text-sm text-gray-500 mt-1">
            Request a new X-ray examination at your preferred branch.
        </p>
    </div>

    <!-- Error -->
    <?php if ($error): ?>
        <div class="rounded-xl bg-red-50 border border-red-200 p-4 flex items-start gap-3">
            <i data-lucide="alert-circle" class="w-5 h-5 text-red-600 shrink-0 mt-0.5"></i>
            <p class="text-sm text-red-700"><?= htmlspecialchars($error) ?></p>
        </div>
    <?php endif; ?>

    <div class="flex flex-col sm:flex-row gap-5">
        <!-- Request form -->
        <div class="flex-1 rounded-2xl bg-white border border-gray-100 shadow-sm p-6">
            <div class="flex items-center gap-3 mb-5">
                <div class="h-10 w-10 rounded-full bg-red-100 flex items-center justify-center shrink-0">
                    <i data-lucide="send" class="w-5 h-5 text-red-600"></i>
                </div>
                <div>
                    <h3 class="font-bold text-gray-900">Request New X-ray</h3>
                    <p class="text-xs text-gray-500">Select your preferred branch to submit a new X-ray request.</p>
                </div>
            </div>

            <form method="POST" class="space-y-4">
                <input type="hidden" name="form_action" value="request_xray">

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Select Branch</label>
                    <select name="branch_id" required
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-900 outline-none focus:ring-2 focus:ring-red-400 focus:border-red-400">
                        <option value="" disabled selected>Select branch</option>
                        <?php foreach ($branches as $b): ?>
                            <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit"
                    class="flex items-center justify-center gap-2 w-full rounded-xl bg-red-600 hover:bg-red-700 text-white font-bold text-sm py-3 px-5 transition shadow-sm">
                    <i data-lucide="send" class="w-4 h-4"></i> Submit Request
                </button>
            </form>
        </div>


    </div>

</div>