<?php
/**
 * Record Request View
 * Now cleaner and focused on UI.
 */
?>
<style>
    /* Hide the outer dashboard scrollbar for this page to keep it clean */
    .patient-main-content {
        overflow-y: hidden !important;
    }
</style>


<!-- Content -->
<div class="flex-1 p-4 lg:p-6 relative">

    <?php if ($successMsg): ?>
        <div class="mb-4 rounded-lg bg-green-50 border border-green-300 p-4 flex items-center gap-3">
            <i data-lucide="check-circle-2" class="w-6 h-6 text-green-600"></i>
            <p class="text-sm text-green-800 font-medium"><?= htmlspecialchars($successMsg) ?></p>
        </div>
    <?php endif; ?>
    <?php if ($errorMsg): ?>
        <div class="mb-4 rounded-lg bg-red-50 border border-red-300 p-4 text-sm text-red-700">
            <?= htmlspecialchars($errorMsg) ?>
        </div>
    <?php endif; ?>

    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold text-gray-900">Record Request</h2>
                <p class="text-sm text-gray-500 mt-1">Manage external record requests and monitoring</p>
            </div>
            <button onclick="document.getElementById('requestModal').classList.remove('hidden')"
                class="inline-flex items-center rounded-lg bg-red-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition shadow-sm">
                <i data-lucide="send" class="w-4 h-4 mr-2"></i> New Record Request
            </button>
        </div>
    </div>

    <div id="record-request-stats" class="grid grid-cols-1 gap-5 sm:grid-cols-3 mt-6 realtime-update">
        <div class="rounded-xl bg-white border border-gray-200 p-6 shadow-sm">
            <div class="flex items-center gap-2">
                <i data-lucide="users" class="w-5 h-5 text-blue-500"></i>
                <p class="text-sm font-medium text-gray-500">Total Requests</p>
            </div>
            <p class="text-3xl font-bold mt-3 text-gray-900"><?= $totalRequests ?></p>
        </div>
        <div class="rounded-xl bg-white border border-gray-200 p-6 shadow-sm">
            <div class="flex items-center gap-2">
                <i data-lucide="clock-3" class="w-5 h-5 text-orange-500"></i>
                <p class="text-sm font-medium text-gray-500">Pending Requests</p>
            </div>
            <p class="text-3xl font-bold mt-3 text-gray-900"><?= $pendingRequests ?></p>
        </div>
        <div class="rounded-xl bg-white border border-gray-200 p-6 shadow-sm">
            <div class="flex items-center gap-2">
                <i data-lucide="check-circle" class="w-5 h-5 text-green-500"></i>
                <p class="text-sm font-medium text-gray-500">Approved Requests</p>
            </div>
            <p class="text-3xl font-bold mt-3 text-gray-900"><?= $approvedRequests ?></p>
        </div>
    </div>

    <div class="mt-8 flex flex-col gap-4">
        <div class="flex gap-4 items-center">
            <input type="text" id="search-input" placeholder="Search case records..."
                class="w-72 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm text-gray-900 outline-none focus:ring-2 focus:ring-red-500">
            <select id="filter-branch"
                class="w-48 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm outline-none focus:ring-2 focus:ring-red-500">
                <option>Filter by Branch</option>
                <option>All</option>
                <option>Gapan</option>
                <option>Bongabon</option>
                <option>Peñaranda</option>
                <option>General Tinio</option>
                <option>Sto Domingo</option>
                <option>San Antonio</option>
                <option>Pantabangan</option>
            </select>
            <select id="sort-date"
                class="w-48 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm outline-none focus:ring-2 focus:ring-red-500">
                <option>Sort by:</option>
                <option>Newest Request</option>
                <option>Oldest Request</option>
            </select>
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white mt-5 shadow-sm">
        <div class="overflow-x-auto overflow-y-auto custom-scrollbar relative" style="max-height: 400px !important;">
            <table class="w-full text-sm border-separate border-spacing-0">
                <thead class="sticky top-0 z-10">
                    <tr class="bg-gray-50 text-gray-600 border-b border-gray-200">
                        <th class="text-left font-semibold px-6 py-4 border-b border-gray-200 bg-gray-50">Case No.</th>
                        <th class="text-left font-semibold px-6 py-4 border-b border-gray-200 bg-gray-50">Patient Name</th>
                        <th class="text-left font-semibold px-6 py-4 border-b border-gray-200 bg-gray-50">Exam Type</th>
                        <th class="text-left font-semibold px-6 py-4 border-b border-gray-200 bg-gray-50">Form Branch</th>
                        <th class="text-left font-semibold px-6 py-4 border-b border-gray-200 bg-gray-50">Date Requested</th>
                        <th class="text-left font-semibold px-6 py-4 border-b border-gray-200 bg-gray-50">Status</th>
                        <th class="text-left font-semibold px-6 py-4 border-b border-gray-200 bg-gray-50">Action</th>
                    </tr>
                </thead>
                <tbody id="table-body" class="text-gray-800 divide-y divide-gray-100 realtime-update">
                    <?php if (count($requests) === 0): ?>
                        <tr>
                            <td colspan="7" class="text-center py-8 text-gray-500">No record requests found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($requests as $req): ?>
                            <tr class="hover:bg-gray-50 transition record-row"
                                data-id="<?= htmlspecialchars($req['patient_no']) ?>"
                                data-name="<?= htmlspecialchars($req['patient_name']) ?>"
                                data-branch="<?= htmlspecialchars($req['request_branch']) ?>"
                                data-date="<?= htmlspecialchars($req['created_at']) ?>">
                                <td class="px-6 py-4 font-medium"><?= htmlspecialchars($req['patient_no']) ?></td>
                                <td class="px-6 py-4 font-medium"><?= htmlspecialchars($req['patient_name']) ?></td>
                                <td class="px-6 py-4">
                                    <?php
                                    $exams = array_filter(array_map('trim', explode(',', $req['exam_type'])));
                                    $firstExam = reset($exams);
                                    $extraCount = count($exams) - 1;
                                    ?>
                                    <div class="flex items-center gap-1.5">
                                        <span class="font-medium text-gray-800 truncate max-w-[100px]"
                                            title="<?= htmlspecialchars($req['exam_type']) ?>"><?= htmlspecialchars($firstExam) ?></span>
                                        <?php if ($extraCount > 0): ?>
                                            <span
                                                class="inline-flex items-center rounded-full bg-gray-100 border border-gray-300 px-1.5 py-0.5 text-xs font-semibold text-gray-600 cursor-default"
                                                title="<?= htmlspecialchars($req['exam_type']) ?>">+<?= $extraCount ?></span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-gray-600"><?= htmlspecialchars($req['request_branch']) ?></td>
                                <td class="px-6 py-4 text-gray-500 text-xs">
                                    <?= date('M d, Y h:i A', strtotime($req['created_at'])) ?>
                                </td>
                                <td class="px-6 py-4">
                                    <?php
                                    $sColor = 'yellow';
                                    if ($req['status'] === 'Approved')
                                        $sColor = 'green';
                                    if ($req['status'] === 'Denied')
                                        $sColor = 'red';
                                    ?>
                                    <span
                                        class="inline-flex items-center rounded-full bg-<?= $sColor ?>-50 px-2.5 py-1 text-xs font-semibold text-<?= $sColor ?>-700 border border-<?= $sColor ?>-200">
                                        <?= htmlspecialchars($req['status']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <a href="index.php?role=radtech&page=view-record-request&id=<?= $req['id'] ?>"
                                        class="<?= $req['status'] === 'Pending' ? 'text-gray-500 hover:text-gray-700' : 'text-blue-600 hover:text-blue-800' ?> focus:outline-none transition inline-flex items-center gap-1 text-sm font-medium">
                                        <i data-lucide="eye" class="w-4 h-4"></i>
                                        <?= $req['status'] === 'Pending' ? 'Awaiting' : 'View' ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Overlay -->
<div id="requestModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 z-50 flex items-center justify-center p-4 hidden">
    <!-- Modal Content -->
    <div
        class="bg-white rounded-2xl shadow-xl w-full max-w-2xl overflow-hidden animate-in fade-in zoom-in duration-200">
        <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
            <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                <i data-lucide="file-search" class="w-5 h-5 text-red-600"></i> Record Request Form
            </h3>
            <button type="button"
                onclick="resetRequestModal(); document.getElementById('requestModal').classList.add('hidden')"
                class="text-gray-400 hover:text-gray-600 transition">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <form id="recordRequestForm" action="" method="POST" class="px-6 py-6"
            onsubmit="this.querySelector('#realSubmitBtn').disabled = false;">

            <!-- STEP 1: SEARCH -->
            <div id="step-1-search">
                <p class="text-sm text-gray-600 mb-5 text-center px-4">Search for an existing patient case to pull up
                    their records before submitting a formal request to another branch.</p>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 mb-5">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5 flex items-center gap-1.5">
                            <i data-lucide="user" class="w-4 h-4 text-gray-400"></i> Patient Name <span
                                class="text-red-500">*</span>
                        </label>
                        <input type="text" id="search_patient_name" placeholder="e.g. Juan Dela Cruz"
                            class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm focus:ring-2 focus:ring-red-500 outline-none transition shadow-sm placeholder:text-gray-400">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5 flex items-center gap-1.5">
                            <i data-lucide="map-pin" class="w-4 h-4 text-gray-400"></i> Target Branch <span
                                class="text-red-500">*</span>
                        </label>
                        <select id="search_request_branch"
                            class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm focus:ring-2 focus:ring-red-500 outline-none transition shadow-sm">
                            <option value="" disabled selected>-- Select Branch --</option>
                            <option value="Gapan">Gapan</option>
                            <option value="Bongabon">Bongabon</option>
                            <option value="Peñaranda">Peñaranda</option>
                            <option value="General Tinio">General Tinio</option>
                            <option value="Sto Domingo">Sto Domingo</option>
                            <option value="San Antonio">San Antonio</option>
                            <option value="Pantabangan">Pantabangan</option>
                        </select>
                    </div>
                </div>

                <!-- Action Bar -->
                <div class="flex justify-end pt-2 border-t border-gray-100">
                    <button type="button" id="btn-search-cases"
                        class="inline-flex items-center gap-2 rounded-xl bg-gray-900 px-6 py-2.5 text-sm font-bold text-white hover:bg-gray-800 transition shadow-sm focus:ring-2 focus:ring-gray-900 focus:ring-offset-2">
                        <i data-lucide="search" class="w-4 h-4"></i> Search Records
                    </button>
                </div>

                <!-- Results Output -->
                <div id="search-results-container" class="hidden mt-6">
                    <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3">Matching Records Found
                    </h4>
                    <div id="search-results-list" class="space-y-2 max-h-[220px] overflow-y-auto pr-2 custom-scrollbar">
                        <!-- Populated by JS -->
                    </div>
                </div>
            </div>

            <!-- STEP 2: SUMMARY & SUBMIT (Hidden initially) -->
            <div id="step-2-details" class="hidden animate-in slide-in-from-right-4 duration-300">
                <!-- Back Button -->
                <button type="button" id="btn-back-search"
                    class="inline-flex items-center gap-1.5 text-sm font-semibold text-gray-500 hover:text-gray-800 mb-4 transition">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i> Back to search
                </button>

                <!-- Hidden inputs for actual form submission -->
                <input type="hidden" name="patient_no" id="modal_patient_no">
                <input type="hidden" name="patient_name" id="modal_patient_name">
                <input type="hidden" name="exam_type" id="modal_exam_type">
                <input type="hidden" name="request_branch" id="modal_request_branch">
                <input type="hidden" name="submit_request" value="1">

                <!-- Display selected info -->
                <div class="p-4 rounded-xl bg-blue-50 border border-blue-100 mb-5 relative overflow-hidden">
                    <div class="absolute top-0 left-0 w-1 h-full bg-blue-500"></div>
                    <p class="text-[10px] uppercase font-bold tracking-wider text-blue-500 mb-1.5">Selected Record for
                        Request</p>
                    <p class="font-bold text-blue-900 text-lg mb-1" id="display_selected_name"></p>
                    <div
                        class="flex items-center gap-3 text-xs font-medium text-blue-800 bg-blue-100/50 inline-flex px-2 py-1 rounded">
                        <span>Patient No: <span id="display_selected_patientno" class="font-bold"></span></span>
                        <span class="w-1 h-1 rounded-full bg-blue-300"></span>
                        <span>Case No: <span id="display_selected_caseno" class="font-bold"></span></span>
                    </div>
                    <p class="text-xs font-semibold text-blue-700 mt-2 flex items-center gap-1.5">
                        <i data-lucide="file-scan" class="w-3.5 h-3.5"></i> <span id="display_selected_exam"></span>
                    </p>
                    <p class="text-xs font-semibold text-blue-500 mt-1 flex items-center gap-1.5">
                        <i data-lucide="calendar" class="w-3.5 h-3.5"></i> X-ray Date: <span id="display_selected_date"></span>
                    </p>
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Reason for Request <span
                            class="text-gray-400 font-normal text-xs">(Required)</span></label>
                    <textarea name="reason" rows="3" required
                        placeholder="Type the reason why these records are being requested..."
                        class="w-full rounded-lg border border-gray-300 bg-white px-4 py-3 text-sm focus:ring-2 focus:ring-red-500 outline-none transition resize-none shadow-sm"></textarea>
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                    <button type="button"
                        onclick="resetRequestModal(); document.getElementById('requestModal').classList.add('hidden')"
                        class="px-5 py-2.5 rounded-lg border border-gray-300 bg-white text-sm font-semibold text-gray-700 hover:bg-gray-50 focus:ring-2 focus:ring-gray-200 transition shadow-sm">
                        Cancel
                    </button>
                    <!-- Hidden real submit button -->
                    <button type="submit" id="realSubmitBtn" class="hidden"></button>
                    <button type="button" id="btn-final-submit"
                        onclick="confirmAction('Submit Request', 'Would you like to confirm submitting this record request?', () => document.getElementById('realSubmitBtn').click(), 'Yes, Submit', false, event)"
                        class="px-6 py-2.5 rounded-lg bg-red-600 text-white text-sm font-bold hover:bg-red-700 focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition shadow-sm flex items-center gap-2">
                        <i data-lucide="send" class="w-4 h-4"></i> Submit Request
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>


<script src="/<?= PROJECT_DIR ?>/app/views/pages/radtech/record-request.js?v=<?= time() ?>"></script>