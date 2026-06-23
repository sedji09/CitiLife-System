<div class="max-w-6xl mx-auto space-y-6">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">System Feedback</h1>
            <p class="text-gray-500 text-sm mt-1">Global feedback and ratings from all patients across branches.</p>
        </div>
        <div class="flex items-center gap-3">
            <form action="" method="GET" class="flex items-center gap-2">
                <input type="hidden" name="role" value="admin_central">
                <input type="hidden" name="page" value="feedback">
                <select name="branch_id" class="px-3 py-2 border border-gray-200 rounded-lg text-sm bg-white focus:border-red-500 focus:ring-1 focus:ring-red-500 outline-none transition" onchange="this.form.submit()">
                    <option value="">All Branches</option>
                    <?php foreach ($branches as $branch): ?>
                        <option value="<?= $branch['id'] ?>" <?= $filterBranchId == $branch['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($branch['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>
    </div>

    <!-- Stats Summary -->
    <?php if ($stats && $stats['total_feedback'] > 0): ?>
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <!-- Average Rating -->
        <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 flex items-center gap-4">
            <div class="p-3 bg-yellow-50 text-yellow-600 rounded-xl">
                <i data-lucide="star" class="w-6 h-6"></i>
            </div>
            <div>
                <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">Average Rating</p>
                <div class="flex items-end gap-1">
                    <span class="text-2xl font-bold text-gray-900"><?= number_format($stats['average_rating'], 1) ?></span>
                    <span class="text-sm text-gray-400 mb-1">/ 5</span>
                </div>
            </div>
        </div>
        
        <!-- Total Feedback -->
        <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 flex items-center gap-4">
            <div class="p-3 bg-blue-50 text-blue-600 rounded-xl">
                <i data-lucide="message-square" class="w-6 h-6"></i>
            </div>
            <div>
                <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">Total Reviews</p>
                <p class="text-2xl font-bold text-gray-900"><?= number_format($stats['total_feedback']) ?></p>
            </div>
        </div>

        <!-- 5 Star Count -->
        <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 flex items-center gap-4">
            <div class="p-3 bg-green-50 text-green-600 rounded-xl">
                <i data-lucide="smile" class="w-6 h-6"></i>
            </div>
            <div>
                <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">5-Star Ratings</p>
                <p class="text-2xl font-bold text-gray-900"><?= number_format($stats['five_stars']) ?></p>
            </div>
        </div>
        
        <!-- Needs Improvement (1-2 Stars) -->
        <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 flex items-center gap-4">
            <div class="p-3 bg-red-50 text-red-600 rounded-xl">
                <i data-lucide="frown" class="w-6 h-6"></i>
            </div>
            <div>
                <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">Critical Reviews</p>
                <p class="text-2xl font-bold text-gray-900"><?= number_format($stats['one_stars'] + $stats['two_stars']) ?></p>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Feedback List -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex items-center justify-between">
            <h2 class="font-bold text-gray-900">Recent Feedback</h2>
            <span class="text-xs font-medium text-gray-500 bg-white px-2 py-1 rounded-md border border-gray-200">
                <?= $filterBranchId ? 'Filtered by Branch' : 'All Branches' ?>
            </span>
        </div>
        
        <?php if (empty($feedbacks)): ?>
            <div class="p-8 text-center text-gray-500">
                <i data-lucide="inbox" class="w-12 h-12 mx-auto text-gray-300 mb-3"></i>
                <p>No feedback received yet.</p>
            </div>
        <?php else: ?>
            <div class="divide-y divide-gray-100">
                <?php foreach ($feedbacks as $fb): ?>
                    <div class="p-5 sm:p-6 hover:bg-gray-50/80 transition-colors border-b border-gray-100 last:border-0">
                        <div class="flex items-start gap-4">
                            <!-- Avatar -->
                            <?php if (!empty($fb['avatar'])): ?>
                                <img src="<?= htmlspecialchars($fb['avatar']) ?>" alt="Profile Picture" class="w-12 h-12 rounded-full object-cover shadow-sm border border-gray-200 shrink-0">
                            <?php else: ?>
                                <div class="w-12 h-12 rounded-full bg-gradient-to-br from-gray-100 to-gray-50 text-gray-600 flex items-center justify-center shrink-0 font-bold text-lg shadow-sm border border-gray-200">
                                    <?= substr($fb['first_name'] ?? '?', 0, 1) ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="flex-1 min-w-0">
                                <!-- Header Row: Name & Date & Badges -->
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-1">
                                    <div>
                                        <h4 class="font-bold text-gray-900 text-sm sm:text-base truncate">
                                            <?= htmlspecialchars(($fb['first_name'] ?? 'Anonymous') . ' ' . ($fb['last_name'] ?? '')) ?>
                                        </h4>
                                        <p class="text-xs text-gray-400 mt-0.5">
                                            <span class="font-medium text-gray-500 uppercase tracking-wide mr-1"><?= htmlspecialchars($fb['patient_number'] ?? 'N/A') ?></span>
                                            &bull; <?= date('M d, Y', strtotime($fb['created_at'])) ?> <span class="text-gray-300 mx-0.5">|</span> <?= date('h:i A', strtotime($fb['created_at'])) ?>
                                        </p>
                                    </div>
                                    
                                    <div class="flex flex-wrap items-center gap-1.5 shrink-0">
                                        <span class="px-2 py-1 rounded-md bg-red-50 text-red-700 text-[10px] font-bold uppercase tracking-wider border border-red-100 shadow-sm flex items-center gap-1">
                                            <i data-lucide="map-pin" class="w-3 h-3 text-red-500"></i><?= htmlspecialchars($fb['branch_name'] ?? 'General Branch') ?>
                                        </span>
                                        <?php if (!empty($fb['case_number'])): ?>
                                        <span class="px-2 py-1 rounded-md bg-white text-gray-600 text-[10px] font-bold uppercase tracking-wider border border-gray-200 shadow-sm flex items-center gap-1">
                                            <i data-lucide="hash" class="w-3 h-3 text-gray-400"></i><?= htmlspecialchars($fb['case_number']) ?>
                                        </span>
                                        <?php if (!empty($fb['exam_type'])): ?>
                                        <span class="px-2 py-1 rounded-md bg-white text-blue-600 text-[10px] font-bold uppercase tracking-wider border border-blue-100 shadow-sm flex items-center gap-1">
                                            <i data-lucide="activity" class="w-3 h-3 text-blue-400"></i><?= htmlspecialchars($fb['exam_type']) ?>
                                        </span>
                                        <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <!-- Stars -->
                                <div class="flex items-center gap-0.5 text-yellow-400 my-2.5">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="<?= $i <= $fb['rating'] ? 'currentColor' : 'none' ?>" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="<?= $i > $fb['rating'] ? 'text-gray-200' : '' ?>">
                                            <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                                        </svg>
                                    <?php endfor; ?>
                                </div>
                                
                                <!-- Comment -->
                                <?php if (!empty($fb['comments'])): ?>
                                    <div class="mt-3 text-sm text-gray-700 leading-relaxed bg-white border border-gray-100 p-4 rounded-xl shadow-sm relative">
                                        <?= nl2br(htmlspecialchars($fb['comments'])) ?>
                                    </div>
                                <?php else: ?>
                                    <div class="mt-3 text-xs text-gray-400 italic">
                                        No comments provided.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
