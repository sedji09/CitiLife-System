<?php
/**
 * Patient Records View (Central Admin)
 * Backend logic handled by PatientRecordsController.php
 */
?>

<!-- Vanilla JS Datepicker styles/scripts -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/vanillajs-datepicker@1.3.4/dist/css/datepicker.min.css">
<script src="https://cdn.jsdelivr.net/npm/vanillajs-datepicker@1.3.4/dist/js/datepicker-full.min.js"></script>

<style>
    /* Global override for Vanilla JS Datepicker to make selected date RED */
    html body .datepicker-cell.selected,
    html body .datepicker-cell.selected:hover,
    html body .datepicker-cell.selected.focused,
    html body .datepicker-picker .datepicker-cell.selected,
    html body .datepicker-picker .datepicker-cell.selected:hover,
    html body .datepicker-picker .datepicker-cell.selected.focused {
        background-color: #dc2626 !important;
        color: #ffffff !important;
        border-color: #dc2626 !important;
    }

    /* Remove the default TEAL background from 'today' and make it clean */
    html body .datepicker-cell.today:not(.selected),
    html body .datepicker-picker .datepicker-cell.today:not(.selected) {
        background-color: #f3f4f6 !important;
        color: #111827 !important;
        font-weight: 600 !important;
        border: 1px solid #d1d5db !important;
    }

    html body .datepicker-cell.today.focused:not(.selected),
    html body .datepicker-picker .datepicker-cell.today.focused:not(.selected) {
        background-color: #e5e7eb !important;
    }
</style>

<main class="flex-1 overflow-y-auto p-4 lg:p-6">
    <div class="mx-auto max-w-6xl space-y-6">
        <!-- Header -->
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Patient Records</h1>
            <p class="text-sm text-gray-500">Manage patient information across all branches</p>
        </div>

        <?php if ($success): ?>
            <div id="statusAlert"
                class="rounded-xl bg-green-50 border border-green-200 p-4 animate-in fade-in slide-in-from-top-2 duration-300">
                <div class="flex items-center gap-3">
                    <i data-lucide="check-circle-2" class="w-5 h-5 text-green-600"></i>
                    <p class="text-sm font-medium text-green-800"><?= htmlspecialchars($success) ?></p>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div id="statusAlert"
                class="rounded-xl bg-red-50 border border-red-200 p-4 animate-in fade-in slide-in-from-top-2 duration-300">
                <div class="flex items-center gap-3">
                    <i data-lucide="alert-circle" class="w-5 h-5 text-red-600"></i>
                    <p class="text-sm font-medium text-red-800"><?= htmlspecialchars($error) ?></p>
                </div>
            </div>
        <?php endif; ?>

        <!-- Search & Filters -->
        <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-center">
            <div class="relative md:col-span-6 w-full">
                <input type="text" id="patientSearch" oninput="filterAndSortPatients()"
                    placeholder="Search by Name or Patient ID..."
                    class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-gray-200 bg-white text-sm focus:outline-none focus:ring-2 focus:ring-red-500/10 focus:border-red-500 transition-all shadow-sm">
                <i data-lucide="search" class="absolute left-3.5 top-3 w-4 h-4 text-gray-400"></i>
            </div>
            <div class="md:col-span-3">
                <select id="branchFilter" onchange="filterAndSortPatients()"
                    class="w-full px-3 py-2.5 rounded-xl border border-gray-200 bg-white text-sm focus:outline-none focus:ring-2 focus:ring-red-500/10 focus:border-red-500 transition-all shadow-sm">
                    <option value="">All Branches</option>
                    <?php foreach ($branches as $b): ?>
                        <option value="<?= htmlspecialchars(strtolower($b['name'])) ?>"><?= htmlspecialchars($b['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="md:col-span-3">
                <select id="sortCase" onchange="filterAndSortPatients()"
                    class="w-full px-3 py-2.5 rounded-xl border border-gray-200 bg-white text-sm focus:outline-none focus:ring-2 focus:ring-red-500/10 focus:border-red-500 transition-all shadow-sm">
                    <option value="newest">Sort by Newest Case</option>
                    <option value="oldest">Sort by Oldest Case</option>
                </select>
            </div>
        </div>

        <!-- Patients Table Card -->
        <div class="rounded-xl border border-gray-300 bg-white shadow-sm mt-4 overflow-hidden mb-12">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="sticky top-0 z-10">
                        <tr class="border-b border-gray-200 bg-gray-50 text-gray-600">
                            <th class="text-left font-semibold px-3 py-3 whitespace-nowrap">Patient No.</th>
                            <th class="text-left font-semibold px-3 py-3 truncate max-w-[200px]">Patient Name</th>
                            <th class="text-left font-semibold px-3 py-3">Age</th>
                            <th class="text-left font-semibold px-3 py-3">Sex</th>
                            <th class="text-left font-semibold px-3 py-3">Branch</th>
                            <th class="text-left font-semibold px-3 py-3 whitespace-nowrap">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="patientsTableBody" class="text-gray-800 bg-white divide-y divide-gray-100">
                        <?php if (empty($patients)): ?>
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                    <div class="flex flex-col items-center gap-2">
                                        <i data-lucide="folder-x" class="w-10 h-10 text-gray-200"></i>
                                        <p>No patient records found.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <!-- No Results Placeholder Row (Dynamically toggled) -->
                            <tr id="noResultsRow" class="hidden">
                                <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                    <div class="flex flex-col items-center gap-3">
                                        <div
                                            class="h-16 w-16 bg-gray-50 rounded-full flex items-center justify-center mb-2">
                                            <i data-lucide="search-x" class="w-8 h-8 text-gray-300"></i>
                                        </div>
                                        <h3 class="text-sm font-bold text-gray-800">No matching records</h3>
                                        <p class="text-xs text-gray-500">Try adjusting your search or filters to find what
                                            you're looking for.</p>
                                    </div>
                                </td>
                            </tr>
                            <?php foreach ($patients as $p): ?>
                                <tr class="hover:bg-gray-50 transition-colors group patient-row"
                                    data-id="<?= htmlspecialchars(strtolower($p['patient_number'])) ?>"
                                    data-name="<?= htmlspecialchars(strtolower($p['first_name'] . ' ' . $p['last_name'])) ?>"
                                    data-branch="<?= htmlspecialchars(strtolower($p['branch_name'] ?? 'general')) ?>"
                                    data-case-date="<?= htmlspecialchars($p['latest_case_date'] ?? '0000-00-00 00:00:00') ?>">
                                    <td class="py-3 px-3 whitespace-nowrap text-gray-500">
                                        <div class="font-medium"><?= htmlspecialchars($p['patient_number']) ?></div>
                                    </td>
                                    <td class="py-3 px-3">
                                        <div class="group flex flex-col items-start cursor-default">
                                            <div class="font-medium text-gray-900 leading-tight">
                                                <?= htmlspecialchars($p['first_name'] . ' ' . $p['last_name']) ?>
                                            </div>
                                            <?php if ($p['latest_case_date']): ?>
                                                <span class="text-[10px] text-gray-400 font-medium tracking-tight">
                                                    Last case: <?= date('M d, Y', strtotime($p['latest_case_date'])) ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-[10px] text-gray-400 font-medium tracking-tight">No case
                                                    history</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="py-3 px-3 text-left">
                                        <span class="text-sm text-gray-600"><?= htmlspecialchars($p['age']) ?></span>
                                    </td>
                                    <td class="py-3 px-3 text-left">
                                        <span class="text-sm text-gray-600"><?= htmlspecialchars($p['sex']) ?></span>
                                    </td>
                                    <td class="py-3 px-3">
                                        <div class="text-gray-600">
                                            <?= htmlspecialchars($p['branch_name'] ?? 'General') ?>
                                        </div>
                                    </td>
                                    <td class="py-3 px-3 text-left">
                                        <div class="flex items-center justify-start gap-2">
                                            <a href="/<?= PROJECT_DIR ?>/patient-details?id=<?= $p['id'] ?>" class="opacity-80"
                                                title="View Profile">
                                                <i data-lucide="eye"
                                                    class="w-6 h-6 bg-blue-100 px-1 py-1 rounded-md border border-blue-500 text-blue-500"></i>
                                            </a>
                                            <button type="button" class="opacity-80" title="Edit Patient"
                                                onclick='openEditModal(<?= json_encode($p) ?>)'>
                                                <i data-lucide="edit-3"
                                                    class="w-6 h-6 bg-green-100 px-1 py-1 rounded-md border border-green-500 text-green-500"></i>
                                            </button>
                                            <a href="/<?= PROJECT_DIR ?>/patient-history?patient_number=<?= urlencode($p['patient_number']) ?>&source=records"
                                                class="opacity-80" title="Medical History">
                                                <i data-lucide="file-text"
                                                    class="w-6 h-6 bg-yellow-100 px-1 py-1 rounded-md border border-yellow-500 text-yellow-500"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Footer -->
            <div class="flex items-center justify-between border-t border-gray-200 bg-gray-50 px-6 py-4">
                <div class="text-xs text-gray-500 dark:text-slate-400">
                    Showing <span id="startIndex"><?= min(1, count($patients)) ?></span>-<span id="endIndex"><?= min(8, count($patients)) ?></span> of <span
                        id="totalRecords"><?= count($patients) ?></span> records
                </div>
                <div class="flex items-center gap-3">
                    <button onclick="changePage(-1)" id="prevBtn"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-red-400 transition disabled:opacity-40 disabled:cursor-not-allowed"
                        disabled>
                        <i data-lucide="chevron-left" class="w-3.5 h-3.5"></i> Previous
                    </button>
                    <span class="text-xs font-medium text-gray-600 min-w-[90px] text-center">
                        Page <span id="currentPageDisplay">1</span> of <span id="totalPagesDisplay">1</span>
                    </span>
                    <button onclick="changePage(1)" id="nextBtn"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-red-400 transition disabled:opacity-40 disabled:cursor-not-allowed"
                        disabled>
                        Next <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- EDIT PATIENT MODAL -->
<div id="editPatientModal"
    class="hidden fixed inset-0 z-[100] bg-black/50 backdrop-blur-sm flex items-center justify-center p-4">
    <div
        class="bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden transform transition-all animate-in zoom-in-95 duration-200">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
            <h3 class="text-lg font-bold text-gray-900">Edit Patient Information</h3>
            <button type="button" onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600 transition p-1">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <form action="" method="POST" class="p-6 space-y-4">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="patient_id" id="edit_patient_id">

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="first_name" class="block text-sm font-semibold text-gray-700 mb-1.5">First Name</label>
                    <input type="text" id="first_name" name="first_name" required
                        class="w-full px-4 py-2.5 rounded-xl border border-gray-200 bg-gray-50/50 text-sm focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-all">
                </div>
                <div>
                    <label for="last_name" class="block text-sm font-semibold text-gray-700 mb-1.5">Last Name</label>
                    <input type="text" id="last_name" name="last_name" required
                        class="w-full px-4 py-2.5 rounded-xl border border-gray-200 bg-gray-50/50 text-sm focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-all">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="birthdate" class="block text-sm font-semibold text-gray-700 mb-1.5">Birthdate</label>
                    <div class="relative">
                        <input type="text" id="birthdate" name="birthdate" required readonly placeholder="yyyy-mm-dd"
                            class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-gray-200 bg-gray-50/50 text-sm focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-all">
                        <i data-lucide="calendar" class="absolute left-3 top-3 w-4 h-4 text-gray-400"></i>
                    </div>
                </div>
                <div>
                    <label for="sex" class="block text-sm font-semibold text-gray-700 mb-1.5">Sex</label>
                    <select id="sex" name="sex" required
                        class="w-full px-3 py-2.5 rounded-xl border border-gray-200 bg-gray-50/50 text-sm focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-all">
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                    </select>
                </div>
            </div>

            <div>
                <label for="contact_number" class="block text-sm font-semibold text-gray-700 mb-1.5">Contact
                    Number</label>
                <div class="relative">
                    <i data-lucide="phone" class="absolute left-3 top-3 w-4 h-4 text-gray-400"></i>
                    <input type="text" id="contact_number" name="contact_number" required placeholder="09xxxxxxxxx"
                        class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-gray-200 bg-gray-50/50 text-sm focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-all">
                </div>
            </div>

            <div>
                <label for="home_address" class="block text-sm font-semibold text-gray-700 mb-1.5">Home Address</label>
                <div class="relative">
                    <i data-lucide="map-pin" class="absolute left-3 top-3 w-4 h-4 text-gray-400"></i>
                    <input type="text" id="home_address" name="home_address" placeholder="e.g. 123 Main St."
                        class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-gray-200 bg-gray-50/50 text-sm focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-all">
                </div>
            </div>

            <div class="pt-4 border-t border-gray-100 flex gap-3">
                <button type="button" onclick="closeEditModal()"
                    class="flex-1 py-2.5 rounded-xl border border-gray-200 text-sm font-bold text-gray-600 hover:bg-gray-50 transition-colors">
                    Cancel
                </button>
                <button type="submit"
                    class="flex-1 py-2.5 rounded-xl bg-red-600 text-sm font-bold text-white hover:bg-red-700 shadow-sm shadow-red-200 transition-all active:scale-95">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // Pagination State
    let currentPage = 1;
    const itemsPerPage = 8;
    let editDatePicker = null;

    function openEditModal(patient) {
        document.getElementById('edit_patient_id').value = patient.id;
        document.getElementById('first_name').value = patient.first_name;
        document.getElementById('last_name').value = patient.last_name;
        
        const birthdateInput = document.getElementById('birthdate');
        birthdateInput.value = patient.birthdate;
        if(editDatePicker) {
            editDatePicker.setDate(patient.birthdate);
        }

        document.getElementById('sex').value = patient.sex;
        document.getElementById('contact_number').value = patient.contact_number;
        document.getElementById('home_address').value = patient.home_address || '';
        document.getElementById('editPatientModal').classList.remove('hidden');
    }

    function closeEditModal() {
        document.getElementById('editPatientModal').classList.add('hidden');
    }

    function filterAndSortPatients(resetPage = true) {
        if (resetPage) currentPage = 1;

        const searchQuery = document.getElementById('patientSearch').value.toLowerCase();
        const branchFilter = document.getElementById('branchFilter').value.toLowerCase();
        const sortMode = document.getElementById('sortCase').value;
        const tableBody = document.getElementById('patientsTableBody');
        const rows = Array.from(document.querySelectorAll('.patient-row'));

        let visibleCount = 0;

        // 1. Filtering
        rows.forEach(row => {
            const id = row.dataset.id;
            const name = row.dataset.name;
            const branch = row.dataset.branch;

            const matchesSearch = id.includes(searchQuery) || name.includes(searchQuery);
            const matchesBranch = branchFilter === "" || branch === branchFilter;

            if (matchesSearch && matchesBranch) {
                row.classList.remove('hidden');
                visibleCount++;
            } else {
                row.classList.add('hidden');
            }
        });

        // Toggle No Results
        const noResultsRow = document.getElementById('noResultsRow');
        if (noResultsRow) {
            noResultsRow.classList.toggle('hidden', visibleCount > 0);
        }

        // 2. Sorting
        const visibleRows = rows.filter(r => !r.classList.contains('hidden'));
        visibleRows.sort((a, b) => {
            const dateA = new Date(a.dataset.caseDate);
            const dateB = new Date(b.dataset.caseDate);

            if (sortMode === 'newest') return dateB - dateA;
            if (sortMode === 'oldest') return dateA - dateB;
            return 0;
        });

        // Re-append to table body
        visibleRows.forEach(row => tableBody.appendChild(row));

        updatePagination(visibleRows);
    }

    function updatePagination(visibleRows) {
        const totalRecords = visibleRows.length;
        const totalPages = Math.ceil(totalRecords / itemsPerPage) || 1;

        if (currentPage > totalPages) currentPage = totalPages;
        if (currentPage < 1) currentPage = 1;

        const startIdx = (currentPage - 1) * itemsPerPage;
        const endIdx = Math.min(startIdx + itemsPerPage, totalRecords);

        // All rows in visibleRows are already not hidden by filter, but we need to hide those not on current page
        const allVisibleByFilter = visibleRows;
        document.querySelectorAll('.patient-row').forEach(r => r.classList.add('hidden')); // Hide all first

        allVisibleByFilter.forEach((row, index) => {
            if (index >= startIdx && index < endIdx) {
                row.classList.remove('hidden');
            }
        });

        // Update UI
        document.getElementById('startIndex').innerText = totalRecords === 0 ? 0 : startIdx + 1;
        document.getElementById('endIndex').innerText = endIdx;
        document.getElementById('totalRecords').innerText = totalRecords;
        document.getElementById('currentPageDisplay').innerText = currentPage;
        document.getElementById('totalPagesDisplay').innerText = totalPages;

        document.getElementById('prevBtn').disabled = (currentPage === 1);
        document.getElementById('nextBtn').disabled = (currentPage === totalPages || totalRecords === 0);
    }

    function changePage(delta) {
        currentPage += delta;
        filterAndSortPatients(false);
    }

    document.addEventListener('DOMContentLoaded', () => {
        if (window.lucide) window.lucide.createIcons();
        filterAndSortPatients(false);
        
        const bDateInput = document.getElementById('birthdate');
        if (bDateInput) {
            editDatePicker = new Datepicker(bDateInput, {
                autohide: true,
                format: 'yyyy-mm-dd',
                todayHighlight: true
            });
        }

        // Alert auto-dismiss
        const alert = document.getElementById('statusAlert');
        if (alert) {
            setTimeout(() => {
                alert.style.opacity = "0";
                setTimeout(() => alert.remove(), 500);
            }, 3000);
        }
    });
</script>