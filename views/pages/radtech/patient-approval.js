let currentEditId = null;

function openEditModal(id, name, age, sex, contact, philhealth, philhealthId) {
    currentEditId = id;
    document.getElementById('modalName').value = name;
    document.getElementById('modalAge').value = age;
    document.getElementById('modalSex').value = sex;
    document.getElementById('modalContact').value = contact;
    document.getElementById('modalPhilHealth').value = philhealth;
    document.getElementById('modalPhilHealthId').value = philhealthId || '';
    document.getElementById('editModal').classList.remove('hidden');
    togglePhilHealthId();
}

function closeEditModal() {
    document.getElementById('editModal').classList.add('hidden');
    currentEditId = null;
}

function togglePhilHealthId() {
    const status = document.getElementById('modalPhilHealth').value;
    const idField = document.getElementById('philHealthIdField');
    const idInput = document.getElementById('modalPhilHealthId');
    if (status === 'With PhilHealth Card') {
        idField.classList.remove('hidden');
    } else {
        idField.classList.add('hidden');
        idInput.value = '';
        idInput.setCustomValidity('');
    }
}

function formatPhilHealthInput(input) {
    // Strip everything except digits
    let digits = input.value.replace(/\D/g, '');
    // Limit to 12 digits total (2 + 9 + 1)
    digits = digits.slice(0, 12);
    // Build XX-XXXXXXXXX-X
    let formatted = '';
    if (digits.length <= 2) {
        formatted = digits;
    } else if (digits.length <= 11) {
        formatted = digits.slice(0, 2) + '-' + digits.slice(2);
    } else {
        formatted = digits.slice(0, 2) + '-' + digits.slice(2, 11) + '-' + digits.slice(11);
    }
    input.value = formatted;
    input.setCustomValidity('');
}

function saveEditModal() {
    const name = document.getElementById('modalName').value;
    const age = document.getElementById('modalAge').value;
    const sex = document.getElementById('modalSex').value;
    const contact = document.getElementById('modalContact').value;
    const philhealth = document.getElementById('modalPhilHealth').value;
    const philhealthId = document.getElementById('modalPhilHealthId').value;

    if (!name || !age || !sex || !contact) {
        toast('Please fill in all required fields', 'error');
        return;
    }

    const idInput = document.getElementById('modalPhilHealthId');
    if (philhealth === 'With PhilHealth Card') {
        const philHealthPattern = /^\d{2}-\d{9}-\d{1}$/;
        if (!philhealthId.trim()) {
            idInput.setCustomValidity('PhilHealth ID Number is required.');
            idInput.reportValidity();
            idInput.addEventListener('input', () => idInput.setCustomValidity(''), { once: true });
            return;
        } else if (!philHealthPattern.test(philhealthId.trim())) {
            idInput.setCustomValidity('Format must be XX-XXXXXXXXX-X (digits only).');
            idInput.reportValidity();
            idInput.addEventListener('input', () => idInput.setCustomValidity(''), { once: true });
            return;
        }
    }

    // Create a form and submit it
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = window.__APP__.basePath + '/app/config/update_patient.php';

    const inputs = [
        { name: 'id', value: currentEditId },
        { name: 'name', value: name },
        { name: 'age', value: age },
        { name: 'sex', value: sex },
        { name: 'contact', value: contact },
        { name: 'philhealth', value: philhealth },
        { name: 'philhealth_id', value: philhealthId, required: true }
    ];

    inputs.forEach(input => {
        const field = document.createElement('input');
        field.type = 'hidden';
        field.name = input.name;
        field.value = input.value;
        if (input.required) {
            field.required = true;
        }
        form.appendChild(field);
    });

    document.body.appendChild(form);
    form.submit();
}

document.addEventListener('input', (e) => {
    if (e.target && (e.target.id === 'search-input' || e.target.id === 'filter-priority' || e.target.id === 'sort-date')) {
        applyFilters();
    }
});

document.addEventListener('change', (e) => {
    if (e.target && (e.target.id === 'filter-priority' || e.target.id === 'sort-date')) {
        applyFilters();
    }
});

function applyFilters() {
    const search = (document.getElementById('search-input')?.value || '').toLowerCase();
    const sort = document.getElementById('sort-date')?.value || 'Sort by:';

    const tbody = document.getElementById('table-body');
    if (!tbody) return;

    let rows = Array.from(tbody.querySelectorAll('tr.record-row'));
    let visibleCount = 0;

    // Sort
    if (sort === 'Newest Request' || sort === 'Oldest Request') {
        rows.sort((a, b) => {
            const dateA = new Date(a.dataset.date).getTime();
            const dateB = new Date(b.dataset.date).getTime();
            return sort === 'Newest Request' ? dateB - dateA : dateA - dateB;
        });

        rows.forEach(row => tbody.appendChild(row));
    }

    // Filter
    rows.forEach(row => {
        const name = (row.dataset.name || '').toLowerCase();
        const id = (row.dataset.id || '').toLowerCase();

        const matchSearch = name.includes(search) || id.includes(search);

        if (matchSearch) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });

    let emptyMsg = document.getElementById('empty-msg-row');
    if (visibleCount === 0 && rows.length > 0) {
        if (!emptyMsg) {
            emptyMsg = document.createElement('tr');
            emptyMsg.id = 'empty-msg-row';
            emptyMsg.innerHTML = `<td colspan="10" class="text-center py-8 text-gray-500">No records match your filters.</td>`;
            tbody.appendChild(emptyMsg);
        } else {
            emptyMsg.style.display = '';
        }
    } else if (emptyMsg) {
        emptyMsg.style.display = 'none';
    }
}

// Initial sorting on load and re-applying filters after real-time updates
document.addEventListener('DOMContentLoaded', () => {
    setTimeout(() => {
        const sortSelect = document.getElementById('sort-date');
        if (sortSelect && sortSelect.value === 'Sort by:') {
            sortSelect.value = 'Newest Request';
            applyFilters();
        }
    }, 100);
});

// Re-apply filters when real-time polling updates the table content
document.addEventListener('realtime:updated', () => {
    applyFilters();
});
