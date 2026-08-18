<?php
/**
 * Patient Portal - Services & Pricing View
 * Backend logic handled by ServicesPricingController.php
 */
?>

<div id="patient-services-container" class="space-y-6 pb-8 max-w-4xl mx-auto">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-semibold text-gray-900 tracking-tight">Services & Pricing</h1>
            <p class="text-xs sm:text-sm text-gray-500 mt-1">Browse our complete list of available X-Ray examinations and standardized rates.</p>
        </div>
        <a href="/<?= PROJECT_DIR ?>/registration"
            class="inline-flex items-center justify-center gap-2 rounded-xl bg-red-600 hover:bg-red-700 text-white font-bold text-sm py-2.5 px-4 transition shadow-sm self-start sm:self-auto shrink-0">
            <i data-lucide="plus-circle" class="w-4 h-4 shrink-0"></i>
            Register New Request
        </a>
    </div>

    <!-- Search & Category Filter Bar -->
    <div class="flex flex-col sm:flex-row gap-3 items-center">
        <div class="relative flex-1 w-full flex items-center">
            <i data-lucide="search" class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 shrink-0 pointer-events-none"></i>
            <input type="text" id="patientServiceSearch" oninput="filterPatientServices()"
                placeholder="Search procedure name (e.g. Chest PA, Skull, Foot)..."
                class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-gray-200 bg-white text-sm focus:outline-none focus:ring-2 focus:ring-red-500/10 focus:border-red-500 transition-all shadow-sm">
        </div>
        <select id="patientCategoryFilter" onchange="filterPatientServices()"
            class="w-full sm:w-48 px-3.5 py-2.5 rounded-xl border border-gray-200 bg-white text-sm focus:outline-none focus:ring-2 focus:ring-red-500/10 focus:border-red-500 transition-all shadow-sm">
            <option value="">All Categories</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- Services Grid by Category -->
    <div id="servicesCategoryGrid" class="space-y-5">
        <?php if (empty($groupedServices)): ?>
            <div class="rounded-2xl bg-white border border-gray-100 shadow-sm p-10 text-center flex flex-col items-center justify-center">
                <i data-lucide="activity" class="w-10 h-10 text-gray-300 mx-auto mb-3 shrink-0"></i>
                <h3 class="text-base font-bold text-gray-700">No Services Available</h3>
                <p class="text-sm text-gray-500">Service rates are currently being updated. Please check back soon.</p>
            </div>
        <?php else: ?>
            <div id="noSearchMatchMsg" class="hidden rounded-2xl bg-white border border-gray-100 shadow-sm p-10 text-center flex flex-col items-center justify-center">
                <i data-lucide="search-x" class="w-10 h-10 text-gray-300 mx-auto mb-3 shrink-0"></i>
                <h3 class="text-base font-bold text-gray-800 mb-1">No Matching Procedures Found</h3>
                <p class="text-xs sm:text-sm text-gray-500">Try adjusting your search terms or selecting "All Categories".</p>
            </div>

            <?php foreach ($groupedServices as $category => $services): ?>
                <div class="category-block bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden transition-all duration-200"
                    data-category="<?= htmlspecialchars(strtolower($category)) ?>">
                    <!-- Category Header -->
                    <div class="px-5 py-3.5 bg-gray-50/80 border-b border-gray-200 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-xl bg-red-100/90 flex items-center justify-center text-red-600 shrink-0">
                                <svg class="w-4 h-4 text-red-600 block shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M22 12h-4l-3 9L9 3l-3 9H2"/>
                                </svg>
                            </div>
                            <h2 class="font-bold text-gray-900 text-base tracking-tight leading-none"><?= htmlspecialchars($category) ?> X-Rays</h2>
                        </div>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-600 shrink-0">
                            <?= count($services) ?> <?= count($services) === 1 ? 'Procedure' : 'Procedures' ?>
                        </span>
                    </div>

                    <!-- Procedures List -->
                    <div class="divide-y divide-gray-100">
                        <?php foreach ($services as $service): ?>
                            <div class="service-item-row px-5 py-3.5 flex items-center justify-between hover:bg-red-50/20 transition-colors"
                                data-exam="<?= htmlspecialchars(strtolower($service['exam_type'])) ?>">
                                <div class="flex items-center gap-3">
                                    <span class="w-2 h-2 rounded-full bg-red-500 shrink-0 inline-block"></span>
                                    <span class="text-sm font-semibold text-gray-800 leading-tight">
                                        <?= htmlspecialchars($service['exam_type']) ?>
                                    </span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="text-base font-bold text-gray-900 font-mono">
                                        ₱ <?= number_format($service['price'], 2) ?>
                                    </span>
                                    <a href="/<?= PROJECT_DIR ?>/registration"
                                        title="Request this exam"
                                        class="p-1.5 rounded-lg text-red-600 hover:bg-red-50 transition-colors inline-flex items-center justify-center shrink-0">
                                        <i data-lucide="chevron-right" class="w-4 h-4 shrink-0"></i>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Quick Registration Footer CTA -->
    <div class="rounded-2xl bg-white border border-gray-200 shadow-sm p-6 text-center">
        <h3 class="text-base font-bold text-gray-900 mb-1">Ready to schedule your examination?</h3>
        <p class="text-xs sm:text-sm text-gray-500 mb-4">Submit your registration online and choose your preferred branch location.</p>
        <a href="/<?= PROJECT_DIR ?>/registration"
            class="inline-flex items-center justify-center gap-2 rounded-xl bg-red-600 hover:bg-red-700 text-white font-bold text-sm py-3 px-6 transition shadow-sm shrink-0">
            <i data-lucide="plus-circle" class="w-4 h-4 shrink-0"></i>
            Register New X-Ray Request
        </a>
    </div>
</div>

<script>
    function filterPatientServices() {
        const searchInput = document.getElementById('patientServiceSearch');
        const categoryFilter = document.getElementById('patientCategoryFilter');
        
        const query = (searchInput ? searchInput.value : '').toLowerCase().trim();
        const selectedCat = (categoryFilter ? categoryFilter.value : '').toLowerCase().trim();

        const blocks = document.querySelectorAll('.category-block');
        const noMatchMsg = document.getElementById('noSearchMatchMsg');
        
        let totalVisibleItems = 0;

        blocks.forEach(block => {
            const blockCat = (block.dataset.category || '').toLowerCase();
            const matchesCategory = selectedCat === '' || blockCat === selectedCat;

            const items = block.querySelectorAll('.service-item-row');
            let visibleInBlock = 0;

            items.forEach(item => {
                const examName = (item.dataset.exam || '').toLowerCase();
                const matchesSearch = query === '' || examName.includes(query) || blockCat.includes(query);

                if (matchesCategory && matchesSearch) {
                    item.classList.remove('hidden');
                    visibleInBlock++;
                } else {
                    item.classList.add('hidden');
                }
            });

            if (visibleInBlock > 0) {
                block.classList.remove('hidden');
                totalVisibleItems += visibleInBlock;
            } else {
                block.classList.add('hidden');
            }
        });

        if (noMatchMsg) {
            if (totalVisibleItems === 0 && blocks.length > 0) {
                noMatchMsg.classList.remove('hidden');
            } else {
                noMatchMsg.classList.add('hidden');
            }
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        if (window.lucide) window.lucide.createIcons();
    });
</script>
