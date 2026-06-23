<div class="max-w-4xl mx-auto space-y-6 p-4 md:p-6 pb-24">
    <!-- Header with Back Button -->
    <div class="flex items-center gap-3">
        <a href="/<?= PROJECT_DIR ?>/index.php?role=patient&page=my-records" class="p-2 rounded-lg bg-white border border-gray-200 text-gray-600 hover:bg-gray-50 hover:text-red-600 transition shadow-sm">
            <i data-lucide="arrow-left" class="w-5 h-5"></i>
        </a>
        <div>
            <h1 class="text-2xl font-black text-gray-900 tracking-tight">Ratings & Feedback</h1>
            <p class="text-sm text-gray-500 font-medium">We value your feedback to improve our services.</p>
        </div>
    </div>

    <?php if ($successMsg): ?>
        <div class="bg-green-50 text-green-800 p-4 rounded-xl border border-green-200 flex items-start gap-3">
            <i data-lucide="check-circle" class="w-5 h-5 text-green-600 mt-0.5"></i>
            <div>
                <h4 class="font-bold text-sm">Success</h4>
                <p class="text-sm mt-1"><?= htmlspecialchars($successMsg) ?></p>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($errorMsg): ?>
        <div class="bg-red-50 text-red-800 p-4 rounded-xl border border-red-200 flex items-start gap-3">
            <i data-lucide="alert-circle" class="w-5 h-5 text-red-600 mt-0.5"></i>
            <div>
                <h4 class="font-bold text-sm">Error</h4>
                <p class="text-sm mt-1"><?= htmlspecialchars($errorMsg) ?></p>
            </div>
        </div>
    <?php endif; ?>

    <!-- Feedback Form Card -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100 bg-gray-50 flex items-center gap-3">
            <div class="p-2 bg-red-100 text-red-600 rounded-lg">
                <i data-lucide="message-square-plus" class="w-5 h-5"></i>
            </div>
            <div>
                <h2 class="font-bold text-gray-900">Submit Feedback</h2>
                <p class="text-xs text-gray-500">How was your experience with CitiLife?</p>
            </div>
        </div>
        
        <?php if (isset($caseData) && $caseData): ?>
        <div class="bg-red-50 border-b border-red-100 px-6 py-3 flex items-center gap-2 text-sm text-red-800">
            <i data-lucide="folder-open" class="w-4 h-4 text-red-600"></i>
            <span class="font-medium">Feedback for Case: <span class="font-bold text-red-700"><?= htmlspecialchars($caseData['case_number']) ?></span> (<?= htmlspecialchars($caseData['exam_type'] ?? 'General Exam') ?>)</span>
        </div>
        <?php endif; ?>

        <div class="p-6">
            <form action="/<?= PROJECT_DIR ?>/index.php?role=patient&page=feedback" method="POST" class="space-y-6">
                <input type="hidden" name="case_id" value="<?= htmlspecialchars($caseId ?? '') ?>">
                <!-- Star Rating -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Overall Rating <span class="text-red-500">*</span></label>
                    <div class="flex items-center gap-2" id="star-rating-container">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <button type="button" class="star-btn p-1 text-gray-300 hover:text-yellow-400 transition transform hover:scale-110" data-rating="<?= $i ?>">
                                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round" class="w-8 h-8 pointer-events-none">
                                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                                </svg>
                            </button>
                        <?php endfor; ?>
                    </div>
                    <input type="hidden" name="rating" id="rating-input" value="" required>
                    <p class="text-xs text-gray-500 mt-2" id="rating-text">Select a rating</p>
                </div>

                <!-- Comments -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Additional Comments (Optional)</label>
                    <textarea name="comments" rows="4" class="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm focus:border-red-500 focus:ring-1 focus:ring-red-500 transition outline-none" placeholder="Tell us more about your experience..."></textarea>
                </div>

                <div class="flex justify-end">
                    <button type="submit" name="submit_feedback" class="px-6 py-2.5 bg-red-600 text-white font-medium rounded-xl hover:bg-red-700 transition shadow-sm active:scale-[0.98] flex items-center gap-2">
                        <i data-lucide="send" class="w-4 h-4"></i>
                        Submit Feedback
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- End Feedback Form Card -->
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const stars = document.querySelectorAll('.star-btn');
        const ratingInput = document.getElementById('rating-input');
        const ratingText = document.getElementById('rating-text');
        
        const texts = {
            1: "Poor",
            2: "Fair",
            3: "Good",
            4: "Very Good",
            5: "Excellent"
        };

        function updateStars(value) {
            stars.forEach(star => {
                const rating = parseInt(star.getAttribute('data-rating'));
                if (rating <= value) {
                    star.classList.remove('text-gray-300');
                    star.classList.add('text-yellow-400');
                } else {
                    star.classList.remove('text-yellow-400');
                    star.classList.add('text-gray-300');
                }
            });
            ratingText.textContent = texts[value];
        }

        // Initialize with default value (0)
        updateStars(0);

        stars.forEach(star => {
            star.addEventListener('click', () => {
                const value = star.getAttribute('data-rating');
                ratingInput.value = value;
                updateStars(value);
            });
        });
    });
</script>
