<div class="max-w-6xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Patient Feedback</h1>
            <p class="text-gray-500 text-sm mt-1">Feedback and ratings from patients for your branch.</p>
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
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
            <h2 class="font-bold text-gray-900">Recent Feedback</h2>
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
                                <div class="w-12 h-12 rounded-full bg-gradient-to-br from-red-100 to-red-50 text-red-600 flex items-center justify-center shrink-0 font-bold text-lg shadow-sm border border-red-100">
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
                                    
                                    <?php if (!empty($fb['case_number'])): ?>
                                    <div class="flex flex-wrap items-center gap-1.5 shrink-0">
                                        <span class="px-2 py-1 rounded-md bg-white text-gray-600 text-[10px] font-bold uppercase tracking-wider border border-gray-200 shadow-sm flex items-center gap-1">
                                            <i data-lucide="hash" class="w-3 h-3 text-gray-400"></i><?= htmlspecialchars($fb['case_number']) ?>
                                        </span>
                                        <?php if (!empty($fb['exam_type'])): ?>
                                        <span class="px-2 py-1 rounded-md bg-white text-blue-600 text-[10px] font-bold uppercase tracking-wider border border-blue-100 shadow-sm flex items-center gap-1">
                                            <i data-lucide="activity" class="w-3 h-3 text-blue-400"></i><?= htmlspecialchars($fb['exam_type']) ?>
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                    <?php endif; ?>
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

        <!-- Pagination Footer -->
        <?php if (!empty($feedbacks)): ?>
            <?php
            $start = $offset + 1;
            $end = min($offset + $limit, $totalFeedbacks);
            ?>
            <div class="flex flex-col sm:flex-row items-center justify-between border-t border-gray-200 bg-gray-50 px-6 py-4 gap-4 mt-4 rounded-xl border">
                <div class="text-xs text-gray-500">
                    Showing <span class="font-semibold text-gray-800"><?= $start ?></span> to <span class="font-semibold text-gray-800"><?= $end ?></span> of <span class="font-semibold text-gray-800"><?= $totalFeedbacks ?></span> feedbacks
                </div>
                <div class="flex items-center flex-wrap gap-1.5">
                    <?php
                    $page_num = (int) ($page_num ?? 1);
                    $renderPageBtn = function ($label, $targetPage, $disabled, $isActive = false) {
                        $queryData = [];
                        $queryData['p'] = $targetPage;
                        $query = http_build_query($queryData);
                        $url = '/' . PROJECT_DIR . '/feedback?' . $query;

                        if ($isActive) {
                            return '<span class="px-3 py-1.5 rounded-lg bg-red-600 text-xs font-bold text-white shadow-sm border border-red-600">' . $label . '</span>';
                        }
                        if ($disabled) {
                            return '<span class="px-3 py-1.5 rounded-lg border border-gray-200 bg-gray-50 text-xs font-semibold text-gray-400 cursor-not-allowed shadow-sm opacity-60">' . $label . '</span>';
                        }
                        return '<a href="' . htmlspecialchars($url) . '" class="px-3 py-1.5 rounded-lg border border-gray-300 bg-white text-xs font-semibold text-gray-700 hover:bg-gray-50 hover:text-red-600 hover:border-red-200 focus:outline-none focus:ring-2 focus:ring-red-400 transition shadow-sm">' . $label . '</a>';
                    };

                    $renderEllipsis = function () {
                        return '<span class="px-2 py-1 text-xs text-gray-400 font-semibold select-none">...</span>';
                    };

                    echo $renderPageBtn('&laquo; First', 1, $page_num <= 1);
                    echo $renderPageBtn('&lsaquo; Back', $page_num - 1, $page_num <= 1);

                    if ($totalPages <= 7) {
                        for ($i = 1; $i <= $totalPages; $i++) {
                            echo $renderPageBtn($i, $i, false, $i == $page_num);
                        }
                    } else {
                        if ($page_num <= 4) {
                            for ($i = 1; $i <= 5; $i++) {
                                echo $renderPageBtn($i, $i, false, $i == $page_num);
                            }
                            echo $renderEllipsis();
                            echo $renderPageBtn($totalPages, $totalPages, false, $totalPages == $page_num);
                        } elseif ($page_num >= $totalPages - 3) {
                            echo $renderPageBtn(1, 1, false, 1 == $page_num);
                            echo $renderEllipsis();
                            for ($i = $totalPages - 4; $i <= $totalPages; $i++) {
                                echo $renderPageBtn($i, $i, false, $i == $page_num);
                            }
                        } else {
                            echo $renderPageBtn(1, 1, false, 1 == $page_num);
                            echo $renderEllipsis();
                            echo $renderPageBtn($page_num - 1, $page_num - 1, false, false);
                            echo $renderPageBtn($page_num, $page_num, false, true);
                            echo $renderPageBtn($page_num + 1, $page_num + 1, false, false);
                            echo $renderEllipsis();
                            echo $renderPageBtn($totalPages, $totalPages, false, false);
                        }
                    }

                    echo $renderPageBtn('Next &rsaquo;', $page_num + 1, $page_num >= $totalPages);
                    echo $renderPageBtn('Last &raquo;', $totalPages, $page_num >= $totalPages);
                    ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
