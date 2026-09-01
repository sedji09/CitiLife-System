<?php
/**
 * Patient Portal - Services & Pricing View
 * Backend logic handled by ServicesPricingController.php
 */
?>

<style>
    body.theme-dark .category-block {
        background-color: #1e293b !important;
        border-color: #334155 !important;
    }
    body.theme-dark .category-header {
        background-color: #0f172a !important;
        border-bottom-color: #334155 !important;
    }
    body.theme-dark .category-icon-box {
        background-color: rgba(220, 38, 38, 0.25) !important;
        border-color: rgba(220, 38, 38, 0.4) !important;
        color: #f87171 !important;
    }
    body.theme-dark .category-icon-box svg {
        color: #f87171 !important;
        stroke: #f87171 !important;
    }
    body.theme-dark .category-title {
        color: #f8fafc !important;
    }
    body.theme-dark .category-exam-count {
        background-color: #1e293b !important;
        border-color: #334155 !important;
        color: #94a3b8 !important;
    }
</style>

<div id="patient-services-container" class="space-y-6 pb-8 max-w-4xl mx-auto">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white tracking-tight">Services & Pricing</h1>
            <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 mt-1">Here is a list of our available X-ray examinations and their corresponding prices.</p>
        </div>
    </div>

    <!-- Search & Category Filter Bar -->
    <div class="flex flex-col md:flex-row gap-2 sm:gap-3 md:items-center mb-6">
        <div class="relative flex-1 w-full">
            <input type="text" id="patientServiceSearch" oninput="filterPatientServices()"
                placeholder="Search procedure name (e.g. Chest PA, Skull, Foot)..."
                class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 pl-10 pr-4 py-2.5 text-xs sm:text-sm text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-all shadow-sm">
            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                <i data-lucide="search" class="w-4 h-4 text-gray-400 dark:text-gray-500"></i>
            </div>
        </div>
    </div>

    <!-- Services Grid by Category -->
    <div id="servicesCategoryGrid" class="space-y-5">
        <?php if (empty($groupedServices)): ?>
            <div
                class="rounded-2xl bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/80 shadow-sm p-10 text-center flex flex-col items-center justify-center">
                <i data-lucide="activity" class="w-10 h-10 text-gray-300 dark:text-gray-600 mx-auto mb-3 shrink-0"></i>
                <h3 class="text-base font-bold text-gray-700 dark:text-gray-200">No Services Available</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">Service rates are currently being updated. Please check back soon.</p>
            </div>
        <?php else: ?>
            <div id="noSearchMatchMsg"
                class="hidden rounded-2xl bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/80 shadow-sm p-10 text-center flex flex-col items-center justify-center">
                <i data-lucide="search-x" class="w-10 h-10 text-gray-300 dark:text-gray-600 mx-auto mb-3 shrink-0"></i>
                <h3 class="text-base font-bold text-gray-800 dark:text-gray-200 mb-1">No Matching Procedures Found</h3>
                <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400">Try adjusting your search terms or procedures name.</p>
            </div>

            <?php foreach ($groupedServices as $category => $services): ?>
                <div class="category-block bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden transition-all duration-200"
                    data-category="<?= htmlspecialchars(strtolower($category)) ?>">
                    <!-- Category Header -->
                    <div class="category-header px-5 py-3.5 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div
                                class="category-icon-box w-8 h-8 rounded-xl bg-red-100 border border-red-200 flex items-center justify-center text-red-600 shrink-0 shadow-sm">
                                <svg class="w-4 h-4 text-red-600 block shrink-0" xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                                    stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M22 12h-4l-3 9L9 3l-3 9H2" />
                                </svg>
                            </div>
                            <h2 class="category-title font-bold text-gray-900 text-base tracking-tight leading-none">
                                <?= htmlspecialchars($category) ?> X-Rays
                            </h2>
                        </div>
                        <span class="category-exam-count text-[11px] font-semibold text-gray-500 bg-gray-100 px-2.5 py-0.5 rounded-full border border-gray-200/60">
                            <?= count($services) ?> <?= count($services) === 1 ? 'Exam' : 'Exams' ?>
                        </span>
                    </div>

                    <!-- Procedures List -->
                    <div class="divide-y divide-gray-100 dark:divide-gray-700/60">
                        <?php foreach ($services as $service): ?>
                            <div class="service-item-row px-5 py-3.5 flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-700/40 transition-colors"
                                data-exam="<?= htmlspecialchars(strtolower($service['exam_type'])) ?>">
                                <div class="flex items-center gap-3">
                                    <span class="w-2 h-2 rounded-full bg-red-500 dark:bg-red-400 shrink-0 inline-block shadow-[0_0_8px_rgba(239,68,68,0.5)]"></span>
                                    <span class="text-sm font-semibold text-gray-800 dark:text-gray-100 leading-tight">
                                        <?= htmlspecialchars($service['exam_type']) ?>
                                    </span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="text-base font-bold text-gray-900 dark:text-white font-mono tracking-tight">
                                        ₱ <?= number_format($service['price'], 2) ?>
                                    </span>
                                    <a href="<?= PROJECT_DIR ? '/' . PROJECT_DIR . '/' : '/' ?>registration" title="Request this exam"
                                        class="p-1.5 rounded-lg text-gray-400 hover:text-red-600 dark:text-gray-400 dark:hover:text-red-400 hover:bg-gray-100 dark:hover:bg-gray-700/60 transition-colors inline-flex items-center justify-center shrink-0">
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

    // Real-time Pricing Updates
    let isPricingPolling = false;
    async function pollPricingUpdates() {
        if (isPricingPolling) return;
        isPricingPolling = true;
        try {
            const url = new URL(window.location.href);
            url.searchParams.set('ajax_polling', '1');
            const response = await fetch(url.toString());
            if (!response.ok) throw new Error('Network response was not ok');

            const html = await response.text();
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');

            const newGrid = doc.getElementById('servicesCategoryGrid');
            const currentGrid = document.getElementById('servicesCategoryGrid');

            if (newGrid && currentGrid) {
                if (newGrid.innerHTML !== currentGrid.innerHTML) {
                    currentGrid.innerHTML = newGrid.innerHTML;

                    filterPatientServices();

                    if (window.lucide) {
                        window.lucide.createIcons();
                    }
                }
            }

            const newCat = doc.getElementById('patientCategoryFilter');
            const currentCat = document.getElementById('patientCategoryFilter');
            if (newCat && currentCat && newCat.innerHTML !== currentCat.innerHTML) {
                const selected = currentCat.value;
                currentCat.innerHTML = newCat.innerHTML;
                currentCat.value = selected;
            }
        } catch (error) {
            console.error('Failed to poll pricing updates:', error);
        } finally {
            isPricingPolling = false;
        }
    }

    // Poll every 5 seconds
    setInterval(pollPricingUpdates, 5000);
</script>