<?php
/**
 * Service Pricing View (Central Admin)
 * Backend logic handled by ServicePricingController.php
 */
?>

<main class="flex-1 overflow-y-auto p-4 lg:p-6">
    <div class="mx-auto max-w-6xl space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">Service Pricing & X-Ray Exams</h1>
                <p class="text-sm text-gray-500">Manage diagnostic X-ray procedures, categories, and public landing page prices</p>
            </div>
            <button type="button" onclick="openAddServiceModal()"
                class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-red-700 shadow-sm transition-all active:scale-95">
                <i data-lucide="plus-circle" class="w-4 h-4"></i>
                Add New Procedure
            </button>
        </div>

        <?php
        $activeCount = 0;
        $inactiveCount = 0;
        $philhealthCoveredCount = 0;
        $totalCount = count($services);
        foreach ($services as $s) {
            if ($s['status'] === 'active') $activeCount++;
            else $inactiveCount++;
            if (!empty($s['is_philhealth_covered'])) $philhealthCoveredCount++;
        }
        ?>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-2">
            <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Active Services</p>
                    <h3 class="text-2xl font-bold text-gray-900"><?= $activeCount ?></h3>
                </div>
                <div class="p-3 bg-green-50 text-green-600 rounded-lg">
                    <i data-lucide="check-circle-2" class="w-6 h-6"></i>
                </div>
            </div>
            <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">PhilHealth Covered</p>
                    <h3 class="text-2xl font-bold text-gray-900"><?= $philhealthCoveredCount ?></h3>
                </div>
                <div class="p-3 bg-emerald-50 text-emerald-600 rounded-lg">
                    <i data-lucide="shield-check" class="w-6 h-6"></i>
                </div>
            </div>
            <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Inactive (Hidden)</p>
                    <h3 class="text-2xl font-bold text-gray-900"><?= $inactiveCount ?></h3>
                </div>
                <div class="p-3 bg-amber-50 text-amber-600 rounded-lg">
                    <i data-lucide="eye-off" class="w-6 h-6"></i>
                </div>
            </div>
            <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Total Procedures</p>
                    <h3 class="text-2xl font-bold text-gray-900"><?= $totalCount ?></h3>
                </div>
                <div class="p-3 bg-indigo-50 text-indigo-600 rounded-lg">
                    <i data-lucide="tag" class="w-6 h-6"></i>
                </div>
            </div>
        </div>

        <?php if (!empty($success)): ?>
            <div id="statusAlert"
                class="rounded-xl bg-green-50 border border-green-200 p-4 animate-in fade-in slide-in-from-top-2 duration-300">
                <div class="flex items-center gap-3">
                    <i data-lucide="check-circle-2" class="w-5 h-5 text-green-600"></i>
                    <p class="text-sm font-medium text-green-800"><?= htmlspecialchars($success) ?></p>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div id="statusAlert"
                class="rounded-xl bg-red-50 border border-red-200 p-4 animate-in fade-in slide-in-from-top-2 duration-300">
                <div class="flex items-center gap-3">
                    <i data-lucide="alert-circle" class="w-5 h-5 text-red-600"></i>
                    <p class="text-sm font-medium text-red-800"><?= htmlspecialchars($error) ?></p>
                </div>
            </div>
        <?php endif; ?>

        <!-- Search & Filters -->
        <div class="flex flex-col md:flex-row gap-3 items-center">
            <div class="relative flex-1 w-full">
                <input type="text" id="serviceSearch" oninput="filterAndSortServices()"
                    placeholder="Search procedure name or category..."
                    class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-gray-200 bg-white text-sm focus:outline-none focus:ring-2 focus:ring-red-500/10 focus:border-red-500 transition-all shadow-sm">
                <i data-lucide="search" class="absolute left-3.5 top-3 w-4 h-4 text-gray-400"></i>
            </div>
            <div class="flex gap-3 w-full md:w-auto">
                <select id="categoryFilter" onchange="filterAndSortServices()"
                    class="flex-1 md:w-48 px-3 py-2.5 rounded-xl border border-gray-200 bg-white text-sm focus:outline-none focus:ring-2 focus:ring-red-500/10 focus:border-red-500 transition-all shadow-sm">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
                    <?php endforeach; ?>
                </select>
                <select id="statusFilter" onchange="filterAndSortServices()"
                    class="flex-1 md:w-40 px-3 py-2.5 rounded-xl border border-gray-200 bg-white text-sm focus:outline-none focus:ring-2 focus:ring-red-500/10 focus:border-red-500 transition-all shadow-sm">
                    <option value="">All Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
        </div>

        <!-- Services Table Card -->
        <div id="services-table-card" class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden mb-12">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50/80 border-b border-gray-200">
                            <th class="px-6 py-4 text-[13px] font-semibold text-gray-500">Exam Procedure</th>
                            <th class="px-6 py-4 text-[13px] font-semibold text-gray-500">Category</th>
                            <th class="px-6 py-4 text-[13px] font-semibold text-gray-500">Price (PHP)</th>
                            <th class="px-6 py-4 text-[13px] font-semibold text-gray-500">PhilHealth Coverage</th>
                            <th class="px-6 py-4 text-[13px] font-semibold text-gray-500">Status</th>
                            <th class="px-6 py-4 text-[13px] font-semibold text-gray-500 text-left">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="servicesTableBody" class="divide-y divide-gray-100">
                        <?php if (empty($services)): ?>
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                    <div class="flex flex-col items-center gap-2">
                                        <i data-lucide="tag" class="w-10 h-10 text-gray-200"></i>
                                        <p>No X-Ray services found.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <!-- No Results Placeholder Row -->
                            <tr id="noResultsRow" class="hidden">
                                <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                    <div class="flex flex-col items-center gap-3">
                                        <div class="h-16 w-16 bg-gray-50 rounded-full flex items-center justify-center mb-2">
                                            <i data-lucide="search-x" class="w-8 h-8 text-gray-300"></i>
                                        </div>
                                        <h3 class="text-sm font-bold text-gray-800">No matching procedures</h3>
                                        <p class="text-xs text-gray-500">Try adjusting your search query or filters.</p>
                                    </div>
                                </td>
                            </tr>
                            <?php foreach ($services as $s): ?>
                                <tr class="hover:bg-gray-50/30 transition-colors group service-row"
                                    data-exam="<?= htmlspecialchars(strtolower($s['exam_type'])) ?>"
                                    data-category="<?= htmlspecialchars(strtolower($s['category'])) ?>"
                                    data-status="<?= htmlspecialchars($s['status']) ?>">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="h-8 w-8 rounded-lg bg-red-100 flex items-center justify-center text-red-700 font-bold text-[11px]">
                                                <i data-lucide="activity" class="w-4 h-4"></i>
                                            </div>
                                            <span class="text-sm font-bold text-gray-800 tracking-tight">
                                                 <?= htmlspecialchars($s['exam_type']) ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center rounded-md bg-stone-100 px-2.5 py-1 text-xs font-semibold text-stone-700">
                                            <?= htmlspecialchars($s['category']) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="text-sm font-bold text-gray-900 font-mono">
                                            ₱<?= number_format($s['price'], 2) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?php if (!empty($s['is_philhealth_covered'])): ?>
                                            <span class="text-sm font-semibold text-emerald-600">
                                                ₱<?= number_format($s['philhealth_discount'], 2) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-sm font-medium text-gray-400">
                                                -
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?php
                                        $badgeClass = $s['status'] === 'active'
                                            ? 'bg-green-50 text-green-600 ring-green-100'
                                            : 'bg-gray-100 text-gray-500 ring-gray-200';
                                        ?>
                                        <span class="inline-flex items-center rounded-full px-3 py-1 text-[11px] font-bold ring-1 ring-inset <?= $badgeClass ?>">
                                            <?= ucfirst(htmlspecialchars($s['status'])) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex items-center justify-start gap-1.5">
                                            <?php if ($s['status'] === 'active'): ?>
                                                <form action="" method="POST" class="inline">
                                                    <input type="hidden" name="action" value="toggle-status">
                                                    <input type="hidden" name="service_id" value="<?= $s['id'] ?>">
                                                    <input type="hidden" name="new_status" value="inactive">
                                                    <button type="submit"
                                                        class="p-1.5 rounded-md border border-gray-200 bg-white text-gray-400 hover:text-amber-500 hover:border-amber-200 hover:bg-amber-50 transition shadow-sm"
                                                        title="Deactivate (Hide from landing page)">
                                                        <i data-lucide="eye-off" class="w-4 h-4"></i>
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <form action="" method="POST" class="inline">
                                                    <input type="hidden" name="action" value="toggle-status">
                                                    <input type="hidden" name="service_id" value="<?= $s['id'] ?>">
                                                    <input type="hidden" name="new_status" value="active">
                                                    <button type="submit"
                                                        class="p-1.5 rounded-md border border-gray-200 bg-white text-gray-400 hover:text-green-500 hover:border-green-200 hover:bg-green-50 transition shadow-sm"
                                                        title="Activate (Show on landing page)">
                                                        <i data-lucide="eye" class="w-4 h-4"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>

                                            <button type="button"
                                                onclick="openEditModal(<?= htmlspecialchars(json_encode($s)) ?>)"
                                                class="p-1.5 rounded-md border border-blue-100 bg-blue-50 text-blue-500 hover:bg-blue-100 transition shadow-sm"
                                                title="Edit Procedure">
                                                <i data-lucide="edit-3" class="w-4 h-4"></i>
                                            </button>

                                            <button type="button"
                                                onclick="confirmDeleteService(<?= $s['id'] ?>, '<?= htmlspecialchars($s['exam_type'], ENT_QUOTES) ?>')"
                                                class="p-1.5 rounded-md border border-gray-200 bg-white text-gray-400 hover:text-red-500 hover:border-red-200 hover:bg-red-50 transition shadow-sm"
                                                title="Delete Procedure">
                                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Footer -->
            <div class="flex flex-col sm:flex-row items-center justify-between border-t border-gray-200 bg-gray-50 px-6 py-4 gap-4">
                <div class="text-xs text-gray-500">
                    Showing <span id="startIndex" class="font-semibold text-gray-800">0</span> to <span id="endIndex" class="font-semibold text-gray-800">0</span> of <span
                        id="totalRecords" class="font-semibold text-gray-800">0</span> records
                </div>
                <div class="flex items-center flex-wrap gap-1.5" id="paginationControls">
                    <!-- Dynamic pagination -->
                </div>
            </div>
        </div>
    </div>
</main>

<!-- ADD SERVICE MODAL -->
<div id="addServiceModal"
    class="hidden fixed inset-0 z-[100] bg-black/50 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden transform transition-all animate-in zoom-in-95 duration-200">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
            <h3 class="text-lg font-bold text-gray-900">Add New X-Ray Service</h3>
            <button type="button" onclick="closeAddServiceModal()"
                class="text-gray-400 hover:text-gray-600 transition p-1">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <form action="" method="POST" class="p-6 space-y-4" autocomplete="off">
            <input type="hidden" name="action" value="create">
            
            <div>
                <label for="category" class="block text-sm font-semibold text-gray-700 mb-1.5">Category</label>
                <select id="category" name="category" onchange="toggleCustomCategory('add')" required
                    class="w-full px-3 py-2.5 rounded-xl border border-gray-200 bg-stone-50 text-sm focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-all">
                    <option value="">-- Select Category --</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
                    <?php endforeach; ?>
                    <option value="__new__">+ Create New Category...</option>
                </select>
            </div>

            <div id="add_custom_category_wrapper" class="hidden">
                <label for="custom_category" class="block text-sm font-semibold text-gray-700 mb-1.5">New Category Name</label>
                <input type="text" id="custom_category" name="custom_category" placeholder="e.g., Skull & Facial"
                    class="w-full px-3 py-2.5 rounded-xl border border-gray-200 bg-stone-50 text-sm focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-all">
            </div>

            <div>
                <label for="exam_type" class="block text-sm font-semibold text-gray-700 mb-1.5">Exam Procedure Name</label>
                <input type="text" id="exam_type" name="exam_type" required placeholder="e.g. Chest PA / Skull AP/L"
                    class="w-full px-3 py-2.5 rounded-xl border border-gray-200 bg-stone-50 text-sm focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-all">
            </div>

            <div>
                <label for="price" class="block text-sm font-semibold text-gray-700 mb-1.5">Price (PHP)</label>
                <div class="relative">
                    <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-500 font-bold text-sm pointer-events-none select-none">₱</span>
                    <input type="number" step="0.01" min="0" id="price" name="price" required placeholder="450.00"
                        class="w-full pl-9 pr-4 py-2.5 rounded-xl border border-gray-200 bg-stone-50 text-sm focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-all">
                </div>
            </div>

            <!-- PhilHealth Coverage Toggle -->
            <div class="rounded-xl border border-gray-200 bg-stone-50/50 p-4 space-y-3">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-semibold text-gray-800">PhilHealth Covered</p>
                        <p class="text-xs text-gray-500 mt-0.5">Enable if this exam is eligible for PhilHealth discount</p>
                    </div>
                    <label class="flex items-center cursor-pointer">
                        <input type="checkbox" id="is_philhealth_covered" name="is_philhealth_covered" value="1" onchange="togglePhilhealthDiscount('add')" class="sr-only">
                        <div id="add_toggle_track" class="w-11 h-6 flex-shrink-0 bg-gray-200 rounded-full border-2 border-transparent transition-colors duration-200 inline-flex items-center">
                            <div id="add_toggle_knob" class="w-5 h-5 bg-white rounded-full shadow-sm transition-transform duration-200" style="transform: translateX(0px);"></div>
                        </div>
                    </label>
                </div>

                <div id="add_philhealth_discount_wrapper" class="hidden pt-2 border-t border-gray-200/80">
                    <label for="philhealth_discount" class="block text-xs font-bold uppercase tracking-wider text-gray-600 mb-1.5">PhilHealth Discount Amount (PHP)</label>
                    <div class="relative">
                        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-500 font-bold text-sm pointer-events-none select-none">₱</span>
                        <input type="number" step="0.01" min="0" id="philhealth_discount" name="philhealth_discount" placeholder="200.00"
                            class="w-full pl-9 pr-4 py-2.5 rounded-xl border border-gray-200 bg-white text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all">
                    </div>
                    <p class="text-[11px] text-gray-500 mt-1">Deducted from the total payable amount for patients with a PhilHealth card.</p>
                </div>
            </div>

            <div>
                <label for="status" class="block text-sm font-semibold text-gray-700 mb-1.5">Status</label>
                <select id="status" name="status"
                    class="w-full px-3 py-2.5 rounded-xl border border-gray-200 bg-stone-50 text-sm focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-all">
                    <option value="active">Active (Visible on Landing Page)</option>
                    <option value="inactive">Inactive (Hidden)</option>
                </select>
            </div>

            <div class="pt-4 border-t border-gray-100 flex gap-3">
                <button type="button" onclick="closeAddServiceModal()"
                    class="flex-1 py-2.5 rounded-xl border border-gray-200 text-sm font-bold text-gray-600 hover:bg-gray-50 transition-colors">
                    Cancel
                </button>
                <button type="submit"
                    class="flex-1 py-2.5 rounded-xl bg-red-600 text-sm font-bold text-white hover:bg-red-700 shadow-sm shadow-red-200 transition-all active:scale-95">
                    Save Procedure
                </button>
            </div>
        </form>
    </div>
</div>

<!-- EDIT SERVICE MODAL -->
<div id="editServiceModal"
    class="hidden fixed inset-0 z-[100] bg-black/50 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden transform transition-all animate-in zoom-in-95 duration-200">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
            <h3 class="text-lg font-bold text-gray-900">Edit X-Ray Service</h3>
            <button type="button" onclick="closeEditServiceModal()"
                class="text-gray-400 hover:text-gray-600 transition p-1">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <form action="" method="POST" class="p-6 space-y-4" autocomplete="off">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="service_id" id="edit_service_id">
            
            <div>
                <label for="edit_category" class="block text-sm font-semibold text-gray-700 mb-1.5">Category</label>
                <select id="edit_category" name="category" onchange="toggleCustomCategory('edit')" required
                    class="w-full px-3 py-2.5 rounded-xl border border-gray-200 bg-stone-50 text-sm focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-all">
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
                    <?php endforeach; ?>
                    <option value="__new__">+ Create New Category...</option>
                </select>
            </div>

            <div id="edit_custom_category_wrapper" class="hidden">
                <label for="edit_custom_category" class="block text-sm font-semibold text-gray-700 mb-1.5">New Category Name</label>
                <input type="text" id="edit_custom_category" name="custom_category" placeholder="e.g., Skull & Facial"
                    class="w-full px-3 py-2.5 rounded-xl border border-gray-200 bg-stone-50 text-sm focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-all">
            </div>

            <div>
                <label for="edit_exam_type" class="block text-sm font-semibold text-gray-700 mb-1.5">Exam Procedure Name</label>
                <input type="text" id="edit_exam_type" name="exam_type" required
                    class="w-full px-3 py-2.5 rounded-xl border border-gray-200 bg-stone-50 text-sm focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-all">
            </div>

            <div>
                <label for="edit_price" class="block text-sm font-semibold text-gray-700 mb-1.5">Price (PHP)</label>
                <div class="relative">
                    <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-500 font-bold text-sm pointer-events-none select-none">₱</span>
                    <input type="number" step="0.01" min="0" id="edit_price" name="price" required
                        class="w-full pl-9 pr-4 py-2.5 rounded-xl border border-gray-200 bg-stone-50 text-sm focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-all">
                </div>
            </div>

            <!-- PhilHealth Coverage Toggle -->
            <div class="rounded-xl border border-gray-200 bg-stone-50/50 p-4 space-y-3">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-semibold text-gray-800">PhilHealth Covered</p>
                        <p class="text-xs text-gray-500 mt-0.5">Enable if this exam is eligible for PhilHealth discount</p>
                    </div>
                    <label class="flex items-center cursor-pointer">
                        <input type="checkbox" id="edit_is_philhealth_covered" name="is_philhealth_covered" value="1" onchange="togglePhilhealthDiscount('edit')" class="sr-only">
                        <div id="edit_toggle_track" class="w-11 h-6 flex-shrink-0 bg-gray-200 rounded-full border-2 border-transparent transition-colors duration-200 inline-flex items-center">
                            <div id="edit_toggle_knob" class="w-5 h-5 bg-white rounded-full shadow-sm transition-transform duration-200" style="transform: translateX(0px);"></div>
                        </div>
                    </label>
                </div>

                <div id="edit_philhealth_discount_wrapper" class="hidden pt-2 border-t border-gray-200/80">
                    <label for="edit_philhealth_discount" class="block text-xs font-bold uppercase tracking-wider text-gray-600 mb-1.5">PhilHealth Discount Amount (PHP)</label>
                    <div class="relative">
                        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-500 font-bold text-sm pointer-events-none select-none">₱</span>
                        <input type="number" step="0.01" min="0" id="edit_philhealth_discount" name="philhealth_discount" placeholder="200.00"
                            class="w-full pl-9 pr-4 py-2.5 rounded-xl border border-gray-200 bg-white text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all">
                    </div>
                    <p class="text-[11px] text-gray-500 mt-1">Deducted from the total payable amount for patients with a PhilHealth card.</p>
                </div>
            </div>

            <div>
                <label for="edit_status" class="block text-sm font-semibold text-gray-700 mb-1.5">Status</label>
                <select id="edit_status" name="status"
                    class="w-full px-3 py-2.5 rounded-xl border border-gray-200 bg-stone-50 text-sm focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-all">
                    <option value="active">Active (Visible on Landing Page)</option>
                    <option value="inactive">Inactive (Hidden)</option>
                </select>
            </div>

            <div class="pt-4 border-t border-gray-100 flex gap-3">
                <button type="button" onclick="closeEditServiceModal()"
                    class="flex-1 py-2.5 rounded-xl border border-gray-200 text-sm font-bold text-gray-600 hover:bg-gray-50 transition-colors">
                    Cancel
                </button>
                <button type="submit"
                    class="flex-1 py-2.5 rounded-xl bg-blue-600 text-sm font-bold text-white hover:bg-blue-700 shadow-sm shadow-blue-200 transition-all active:scale-95">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function togglePhilhealthDiscount(prefix) {
        const checkboxId = prefix === 'add' ? 'is_philhealth_covered' : 'edit_is_philhealth_covered';
        const wrapperId = prefix === 'add' ? 'add_philhealth_discount_wrapper' : 'edit_philhealth_discount_wrapper';
        const inputId   = prefix === 'add' ? 'philhealth_discount' : 'edit_philhealth_discount';
        const trackId   = prefix === 'add' ? 'add_toggle_track' : 'edit_toggle_track';
        const knobId    = prefix === 'add' ? 'add_toggle_knob' : 'edit_toggle_knob';

        const checkbox = document.getElementById(checkboxId);
        const wrapper = document.getElementById(wrapperId);
        const input = document.getElementById(inputId);
        const track = document.getElementById(trackId);
        const knob = document.getElementById(knobId);

        if (checkbox && checkbox.checked) {
            wrapper.classList.remove('hidden');
            input.required = true;
            if (track) { track.classList.remove('bg-gray-200'); track.style.backgroundColor = '#dc2626'; }
            if (knob) { knob.style.transform = 'translateX(20px)'; }
        } else {
            wrapper.classList.add('hidden');
            input.required = false;
            if (track) { track.style.backgroundColor = ''; track.classList.add('bg-gray-200'); }
            if (knob) { knob.style.transform = 'translateX(0px)'; }
        }
    }

    function applyModalTheme(modalId) {
        const isDark = document.documentElement.classList.contains('theme-dark');
        const modal = document.getElementById(modalId);
        if (!modal) return;

        const card = modal.querySelector(':scope > div');
        const header = card ? card.querySelector(':scope > div:first-child') : null;
        const title = header ? header.querySelector('h3') : null;
        const inputs = modal.querySelectorAll('input, select');
        const labels = modal.querySelectorAll('label');
        const footer = modal.querySelector('form > div:last-child');
        const cancelBtn = footer ? footer.querySelector('button:first-of-type') : null;

        if (isDark) {
            if (card) card.style.backgroundColor = '#1e293b';
            if (header) { header.style.backgroundColor = '#1e293b'; header.style.borderBottomColor = '#334155'; }
            if (title) title.style.color = '#f1f5f9';
            labels.forEach(l => { l.style.color = '#cbd5e1'; });
            inputs.forEach(i => { i.style.backgroundColor = '#0f172a'; i.style.borderColor = '#475569'; i.style.color = '#f1f5f9'; });
            if (footer) footer.style.borderTopColor = '#334155';
            if (cancelBtn) { cancelBtn.style.borderColor = '#64748b'; cancelBtn.style.color = '#f1f5f9'; cancelBtn.style.backgroundColor = 'transparent'; }
        } else {
            if (card) card.style.backgroundColor = '';
            if (header) { header.style.backgroundColor = ''; header.style.borderBottomColor = ''; }
            if (title) title.style.color = '';
            labels.forEach(l => { l.style.color = ''; });
            inputs.forEach(i => { i.style.backgroundColor = ''; i.style.borderColor = ''; i.style.color = ''; });
            if (footer) footer.style.borderTopColor = '';
            if (cancelBtn) { cancelBtn.style.borderColor = ''; cancelBtn.style.color = ''; cancelBtn.style.backgroundColor = ''; }
        }
    }

    function toggleCustomCategory(prefix) {
        const select = document.getElementById(prefix === 'add' ? 'category' : 'edit_category');
        const wrapper = document.getElementById(prefix === 'add' ? 'add_custom_category_wrapper' : 'edit_custom_category_wrapper');
        const input = document.getElementById(prefix === 'add' ? 'custom_category' : 'edit_custom_category');

        if (select.value === '__new__') {
            wrapper.classList.remove('hidden');
            input.required = true;
        } else {
            wrapper.classList.add('hidden');
            input.required = false;
            input.value = '';
        }
    }

    function openAddServiceModal() {
        document.getElementById('exam_type').value = '';
        document.getElementById('price').value = '';
        document.getElementById('category').value = '';
        document.getElementById('status').value = 'active';
        document.getElementById('is_philhealth_covered').checked = false;
        document.getElementById('philhealth_discount').value = '';
        togglePhilhealthDiscount('add');
        toggleCustomCategory('add');

        document.getElementById('addServiceModal').classList.remove('hidden');
        applyModalTheme('addServiceModal');
    }

    function closeAddServiceModal() {
        document.getElementById('addServiceModal').classList.add('hidden');
    }

    function openEditModal(service) {
        document.getElementById('edit_service_id').value = service.id;
        document.getElementById('edit_exam_type').value = service.exam_type;
        document.getElementById('edit_price').value = service.price;
        document.getElementById('edit_status').value = service.status;
        
        const isCovered = (parseInt(service.is_philhealth_covered) === 1);
        document.getElementById('edit_is_philhealth_covered').checked = isCovered;
        document.getElementById('edit_philhealth_discount').value = isCovered ? parseFloat(service.philhealth_discount || 0).toFixed(2) : '';
        togglePhilhealthDiscount('edit');
        
        const categorySelect = document.getElementById('edit_category');
        let exists = false;
        for (let i = 0; i < categorySelect.options.length; i++) {
            if (categorySelect.options[i].value === service.category) {
                categorySelect.selectedIndex = i;
                exists = true;
                break;
            }
        }
        if (!exists) {
            categorySelect.value = '__new__';
            document.getElementById('edit_custom_category').value = service.category;
        }
        toggleCustomCategory('edit');

        document.getElementById('editServiceModal').classList.remove('hidden');
        applyModalTheme('editServiceModal');
    }

    function closeEditServiceModal() {
        document.getElementById('editServiceModal').classList.add('hidden');
    }

    async function confirmDeleteService(id, name) {
        const result = typeof confirmAlert === 'function' 
            ? await confirmAlert('Delete Procedure', `Are you sure you want to delete "${name}"?`, 'Yes, Delete')
            : { isConfirmed: confirm(`Are you sure you want to delete "${name}"?`) };

        if (result.isConfirmed) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '';

            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'delete';

            const idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = 'service_id';
            idInput.value = id;

            form.appendChild(actionInput);
            form.appendChild(idInput);
            document.body.appendChild(form);
            form.submit();
        }
    }

    // Pagination State
    let currentPage = parseInt(sessionStorage.getItem('CitiLife_servicePricing_page')) || 1;
    const itemsPerPage = 8;

    function filterAndSortServices(resetPage = true) {
        if (resetPage) currentPage = 1;

        const serviceSearch = document.getElementById('serviceSearch');
        const categoryFilter = document.getElementById('categoryFilter');
        const statusFilter = document.getElementById('statusFilter');

        const query = (serviceSearch ? serviceSearch.value : '').toLowerCase();
        const category = categoryFilter ? categoryFilter.value.toLowerCase() : '';
        const status = statusFilter ? statusFilter.value.toLowerCase() : '';

        let rows = Array.from(document.querySelectorAll('.service-row'));

        let visibleCount = 0;
        rows.forEach(row => {
            const rowExam = (row.dataset.exam || "").toLowerCase();
            const rowCategory = (row.dataset.category || "").toLowerCase();
            const rowStatus = (row.dataset.status || "").toLowerCase();

            const matchesSearch = rowExam.includes(query) || rowCategory.includes(query);
            const matchesCategory = category === "" || rowCategory === category;
            const matchesStatus = status === "" || rowStatus === status;

            if (matchesSearch && matchesCategory && matchesStatus) {
                row.classList.remove('hidden');
                visibleCount++;
            } else {
                row.classList.add('hidden');
            }
        });

        const noResultsRow = document.getElementById('noResultsRow');
        if (noResultsRow) {
            if (visibleCount === 0) {
                noResultsRow.classList.remove('hidden');
            } else {
                noResultsRow.classList.add('hidden');
            }
        }

        updatePagination(rows.filter(r => !r.classList.contains('hidden')));
    }

    function renderPaginationControls(totalPages) {
        const container = document.getElementById('paginationControls');
        if (!container) return;
        container.innerHTML = '';

        function createButton(label, page, disabled, isActive = false) {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.innerHTML = label;
            
            if (isActive) {
                btn.className = "px-3 py-1.5 rounded-lg bg-red-600 text-xs font-bold text-white shadow-sm border border-red-600";
            } else {
                btn.className = "px-3 py-1.5 rounded-lg border border-gray-300 bg-white text-xs font-semibold text-gray-700 hover:bg-red-50 hover:text-red-600 hover:border-red-200 focus:outline-none focus:ring-2 focus:ring-red-400 transition disabled:opacity-40 disabled:cursor-not-allowed shadow-sm";
            }
            
            if (disabled) {
                btn.disabled = true;
            } else {
                btn.onclick = () => {
                    currentPage = page;
                    filterAndSortServices(false);
                    const card = document.getElementById('services-table-card');
                    if (card) {
                        card.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                };
            }
            return btn;
        }

        function createEllipsis() {
            const span = document.createElement('span');
            span.className = "px-2 py-1 text-xs text-gray-400 font-semibold select-none";
            span.innerText = '...';
            return span;
        }

        container.appendChild(createButton('&laquo; First', 1, currentPage <= 1));
        container.appendChild(createButton('&lsaquo; Back', currentPage - 1, currentPage <= 1));

        if (totalPages <= 7) {
            for (let i = 1; i <= totalPages; i++) {
                container.appendChild(createButton(i, i, false, i == currentPage));
            }
        } else {
            if (currentPage <= 4) {
                for (let i = 1; i <= 5; i++) {
                    container.appendChild(createButton(i, i, false, i == currentPage));
                }
                container.appendChild(createEllipsis());
                container.appendChild(createButton(totalPages, totalPages, false, totalPages == currentPage));
            } else if (currentPage >= totalPages - 3) {
                container.appendChild(createButton(1, 1, false, 1 == currentPage));
                container.appendChild(createEllipsis());
                for (let i = totalPages - 4; i <= totalPages; i++) {
                    container.appendChild(createButton(i, i, false, i == currentPage));
                }
            } else {
                container.appendChild(createButton(1, 1, false, 1 == currentPage));
                container.appendChild(createEllipsis());
                container.appendChild(createButton(currentPage - 1, currentPage - 1, false, false));
                container.appendChild(createButton(currentPage, currentPage, false, true));
                container.appendChild(createButton(currentPage + 1, currentPage + 1, false, false));
                container.appendChild(createEllipsis());
                container.appendChild(createButton(totalPages, totalPages, false, false));
            }
        }

        container.appendChild(createButton('Next &rsaquo;', currentPage + 1, currentPage >= totalPages));
        container.appendChild(createButton('Last &raquo;', totalPages, currentPage >= totalPages));
    }

    function updatePagination(visibleRows) {
        const totalRecords = visibleRows.length;
        const totalPages = Math.ceil(totalRecords / itemsPerPage) || 1;

        if (currentPage > totalPages) currentPage = totalPages;
        if (currentPage < 1) currentPage = 1;

        sessionStorage.setItem('CitiLife_servicePricing_page', currentPage);

        const startIdx = (currentPage - 1) * itemsPerPage;
        const endIdx = Math.min(startIdx + itemsPerPage, totalRecords);

        visibleRows.forEach((row, index) => {
            if (index >= startIdx && index < endIdx) {
                row.classList.remove('hidden');
            } else {
                row.classList.add('hidden');
            }
        });

        const startIndexEl = document.getElementById('startIndex');
        const endIndexEl = document.getElementById('endIndex');
        const totalRecordsEl = document.getElementById('totalRecords');

        if (startIndexEl) startIndexEl.innerText = totalRecords === 0 ? 0 : startIdx + 1;
        if (endIndexEl) endIndexEl.innerText = endIdx;
        if (totalRecordsEl) totalRecordsEl.innerText = totalRecords;

        renderPaginationControls(totalPages);
    }

    document.addEventListener('DOMContentLoaded', () => {
        if (window.lucide) window.lucide.createIcons();
        filterAndSortServices(false);

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
