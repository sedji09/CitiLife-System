/**
 * reports.js - Admin Central Reports Logic
 */

let philhealthChart = null;
let trendChart = null;

document.addEventListener('DOMContentLoaded', function() {
    initCharts();
    loadStats();
    
    // Add event listeners for Enter key on inputs
    const inputs = document.querySelectorAll('input[type="month"], input[type="number"], input[type="date"]');
    inputs.forEach(input => {
        input.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') loadStats();
        });
    });
});

/**
 * Initialize Chart.js instances
 */
function initCharts() {
    // 1. PhilHealth Doughnut
    const phCtx = document.getElementById('philhealthChart').getContext('2d');
    philhealthChart = new Chart(phCtx, {
        type: 'doughnut',
        data: {
            labels: ['With Card', 'Without Card'],
            datasets: [{
                data: [0, 0],
                backgroundColor: ['#EF4444', '#3b82f6'], // Red and Blue
                borderWidth: 0,
                cutout: '75%',
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(item) {
                            const val = item.raw;
                            return ` ${item.label}: ${Number(val).toLocaleString()}`;
                        }
                    }
                }
            }
        }
    });

    // 2. Trend Chart (Bar with Gradient)
    const trendCtx = document.getElementById('trendChart').getContext('2d');
    const trendGradient = trendCtx.createLinearGradient(0, 0, 0, 250);
    trendGradient.addColorStop(0, 'rgba(29, 78, 216, 0.9)'); // Blue 700
    trendGradient.addColorStop(1, 'rgba(37, 99, 235, 0.1)'); // Blue 600

    trendChart = new Chart(trendCtx, {
        type: 'bar',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            datasets: [{
                label: 'Registrations',
                data: Array(12).fill(0),
                backgroundColor: trendGradient,
                hoverBackgroundColor: 'rgba(29, 78, 216, 1)',
                borderRadius: 4,
                barPercentage: 0.6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { 
                    beginAtZero: true, 
                    ticks: { precision: 0, font: { size: 11 } },
                    grid: { color: 'rgba(0,0,0,0.03)', borderDash: [5, 5] }
                },
                x: { 
                    grid: { display: false },
                    ticks: { font: { weight: '600', size: 11 } }
                }
            },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#1f2937',
                    padding: 10,
                    bodyFont: { size: 13, weight: 'bold' }
                }
            }
        }
    });
}

/**
 * Update spinner
 */
async function loadStats() {
    // ...
}

/**
 * Toggle visibility of branch selection area
 */
function toggleBranchSelection() {
    const mode = document.getElementById('branchMode').value;
    document.getElementById('branchSelectionArea').classList.toggle('hidden', mode !== 'selected');
}

/**
 * Toggle visibility of date filter components
 */
function toggleFilterView() {
    const type = document.getElementById('reportType').value;
    document.getElementById('monthlyFilter').classList.toggle('hidden', type !== 'monthly');
    document.getElementById('yearlyFilter').classList.toggle('hidden', type !== 'yearly');
    document.getElementById('rangeFilter').classList.toggle('hidden', type !== 'range');
}

/**
 * Gather filter values and dates
 */
function getFilters() {
    const type = document.getElementById('reportType').value;
    const mode = document.getElementById('branchMode').value;
    
    // 1. Gather Dates
    let from, to;
    if (type === 'monthly') {
        const val = document.getElementById('monthPicker').value;
        const [y, m] = val.split('-');
        from = `${y}-${m}-01`;
        to = `${y}-${m}-${new Date(y, m, 0).getDate()}`;
    } else if (type === 'yearly') {
        const y = document.getElementById('yearPicker').value;
        from = `${y}-01-01`;
        to = `${y}-12-31`;
    } else {
        from = document.getElementById('dateFrom').value;
        to = document.getElementById('dateTo').value;
    }

    // 2. Gather Branch IDs
    let branchIds = "";
    if (mode === 'selected') {
        const checked = document.querySelectorAll('input[name="branch_ids"]:checked');
        branchIds = Array.from(checked).map(cb => cb.value).join(',');
    }

    return { from, to, branchIds };
}

/**
 * Fetch and update dashboard data
 */
async function loadStats() {
    const { from, to, branchIds } = getFilters();
    const tableBody = document.getElementById('branchStatsTable');

    // Show loading state in table
    tableBody.innerHTML = `
        <tr>
            <td colspan="6" class="px-6 py-10 text-center text-gray-500 bg-white">
                <div class="flex flex-col items-center gap-2">
                    <div class="animate-spin h-5 w-5 border-2 border-indigo-600 border-t-transparent rounded-full"></div>
                    <span>Fetching data...</span>
                </div>
            </td>
        </tr>`;

    try {
        const url = `${window.__APP__.basePath}/index.php?role=admin_central&page=reports&ajax_generate=1&date_from=${from}&date_to=${to}&branches=${branchIds}`;
        const response = await fetch(url);
        const result = await response.json();

        if (result.success) {
            renderDashboard(result.data, result.trends);
        } else {
            tableBody.innerHTML = `<tr><td colspan="6" class="px-6 py-10 text-center text-red-500 font-medium">${result.message || 'Error occurred'}</td></tr>`;
        }
    } catch (e) {
        console.error(e);
        tableBody.innerHTML = `<tr><td colspan="6" class="px-6 py-10 text-center text-red-500 font-medium">Network error. Please try again.</td></tr>`;
    }
}

/**
 * Render stats to DOM and Charts
 */
function renderDashboard(data, trends) {
    const summary = {
        total: 0, philhealth: 0, emergency: 0, urgent: 0, routine: 0, withoutPH: 0
    };

    let tableHtml = "";

    if (!data || data.length === 0) {
        tableHtml = `<tr><td colspan="6" class="px-6 py-10 text-center text-gray-400">No data found for the selected criteria.</td></tr>`;
    } else {
        data.forEach(row => {
            const total = parseInt(row.total_patients || 0);
            const ph = parseInt(row.with_philhealth || 0);
            const wph = parseInt(row.without_philhealth || 0);
            const emg = parseInt(row.emergency_count || 0);
            const urg = parseInt(row.urgent_count || 0);
            const rtn = parseInt(row.routine_count || 0);

            summary.total += total;
            summary.philhealth += ph;
            summary.withoutPH += wph;
            summary.emergency += emg;
            summary.urgent += urg;
            summary.routine += rtn;

            tableHtml += `
            <tr class="hover:bg-gray-50 transition border-b border-gray-100 last:border-0">
                <td class="px-6 py-4 font-semibold text-gray-900">${row.branch_name}</td>
                <td class="px-6 py-4 text-center font-bold text-gray-800">${total.toLocaleString()}</td>
                <td class="px-6 py-4 text-center">
                    <span class="px-2.5 py-1 rounded-full ${emg > 0 ? 'bg-red-50 text-red-700 font-bold' : 'bg-gray-50 text-gray-400'} text-xs">${emg.toLocaleString()}</span>
                </td>
                <td class="px-6 py-4 text-center font-medium text-orange-600">${urg.toLocaleString()}</td>
                <td class="px-6 py-4 text-center text-gray-500">${rtn.toLocaleString()}</td>
                <td class="px-6 py-4 text-center text-blue-600 font-medium">${ph.toLocaleString()}</td>
            </tr>`;
        });
    }

    // Update Summary Cards
    document.getElementById('stat-total').innerText = summary.total.toLocaleString();
    document.getElementById('stat-philhealth').innerText = summary.philhealth.toLocaleString();
    document.getElementById('stat-emergency').innerText = summary.emergency.toLocaleString();
    document.getElementById('stat-urgent').innerText = summary.urgent.toLocaleString();

    // Update PhilHealth Legend
    document.getElementById('label-philhealth-with').innerText = summary.philhealth.toLocaleString();
    document.getElementById('label-philhealth-without').innerText = summary.withoutPH.toLocaleString();

    // Update Table
    document.getElementById('branchStatsTable').innerHTML = tableHtml;

    // Update PhilHealth Chart
    if (philhealthChart) {
        philhealthChart.data.datasets[0].data = [summary.philhealth, summary.withoutPH];
        philhealthChart.update();
    }

    // Update Trend Chart (Only for single branch)
    const trendContainer = document.getElementById('trendChartContainer');
    if (trends && trends.length > 0) {
        trendContainer.classList.remove('hidden');
        const counts = Array(12).fill(0);
        trends.forEach(row => {
            counts[Number(row.month_num) - 1] = Number(row.count);
        });
        trendChart.data.datasets[0].data = counts;
        trendChart.update();
    } else {
        trendContainer.classList.add('hidden');
    }

    // Refresh Lucide Icons for dynamic content (if any used)
    if (window.lucide) lucide.createIcons();
}

/**
 * Trigger export redirect
 */
function exportReport(format) {
    const { from, to, branchIds } = getFilters();
    const typeKey = format === 'pdf' ? 'export_pdf' : 'export_excel';
    
    const url = `${window.__APP__.basePath}/index.php?role=admin_central&page=reports&${typeKey}=1&date_from=${from}&date_to=${to}&branches=${branchIds}`;
    window.location.href = url;
}
