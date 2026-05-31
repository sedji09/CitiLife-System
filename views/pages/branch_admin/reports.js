/**
 * reports.js - Branch Admin Reports Logic
 */

let philhealthChart = null;
let trendChart = null;

document.addEventListener('DOMContentLoaded', function() {
    loadStats();
    
    // Initialize PhilHealth Doughnut Chart
    const ctx = document.getElementById('philhealthChart').getContext('2d');
    philhealthChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['With Card', 'Without Card'],
            datasets: [{
                data: [0, 0],
                backgroundColor: ['#EF4444', '#3B82F6'],
                borderWidth: 0,
                cutout: '75%'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: { enabled: true }
            }
        }
    });

    // Initialize Trend Chart (initially hidden or empty)
    const trendCtx = document.getElementById('monthlyTrendChart').getContext('2d');
    trendChart = new Chart(trendCtx, {
        type: 'line',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            datasets: [{
                label: 'Patients',
                data: Array(12).fill(0),
                borderColor: '#EF4444',
                backgroundColor: 'rgba(239, 68, 68, 0.1)',
                fill: true,
                tension: 0.4,
                pointRadius: 4,
                pointBackgroundColor: '#EF4444'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { beginAtZero: true, ticks: { precision: 0 } },
                x: { grid: { display: false } }
            },
            plugins: {
                legend: { display: false }
            }
        }
    });
});

function toggleFilterView() {
    const type = document.getElementById('reportType').value;
    document.getElementById('monthlyFilter').classList.toggle('hidden', type !== 'monthly');
    document.getElementById('yearlyFilter').classList.toggle('hidden', type !== 'yearly');
    document.getElementById('rangeFilter').classList.toggle('hidden', type !== 'range');
    
    // Monthly chart container only shows for Yearly view
    document.getElementById('monthlyChartContainer').classList.toggle('hidden', type !== 'yearly');
    
    loadStats();
}

function getQueryDates() {
    const type = document.getElementById('reportType').value;
    let from, to;

    if (type === 'monthly') {
        const val = document.getElementById('monthPicker').value; // YYYY-MM
        const parts = val.split('-');
        const year = parts[0];
        const month = parts[1];
        from = `${year}-${month}-01`;
        
        // Get last day of month
        const lastDay = new Date(year, month, 0).getDate();
        to = `${year}-${month}-${lastDay}`;
    } else if (type === 'yearly') {
        const year = document.getElementById('yearPicker').value;
        from = `${year}-01-01`;
        to = `${year}-12-31`;
    } else {
        from = document.getElementById('dateFrom').value;
        to = document.getElementById('dateTo').value;
    }

    return { from, to };
}

async function loadStats() {
    const { from, to } = getQueryDates();
    const type = document.getElementById('reportType').value;
    const includeMonthly = type === 'yearly' ? '&include_monthly=1' : '';

    try {
        const response = await fetch(`${window.__APP__.basePath}/index.php?role=branch_admin&page=reports&ajax_get_stats=1&from=${from}&to=${to}${includeMonthly}`);
        const result = await response.json();

        if (result.success) {
            updateUI(result.data, result.monthly);
        } else {
            console.error('Failed to load stats:', result.message);
        }
    } catch (error) {
        console.error('Error fetching stats:', error);
    }
}

function updateUI(data, monthlyData) {
    if (!data) return;

    // Summary Cards
    document.getElementById('stat-total').innerText = Number(data.total_patients).toLocaleString();
    document.getElementById('stat-philhealth').innerText = Number(data.with_philhealth).toLocaleString();
    document.getElementById('stat-emergency').innerText = Number(data.emergency_count).toLocaleString();
    document.getElementById('stat-urgent').innerText = Number(data.urgent_count).toLocaleString();

    // Table Rows
    document.getElementById('row-emergency').innerText = Number(data.emergency_count).toLocaleString();
    document.getElementById('row-urgent').innerText = Number(data.urgent_count).toLocaleString();
    document.getElementById('row-routine').innerText = Number(data.routine_count).toLocaleString();

    // PhilHealth Labels
    const phWithVal = Number(data.with_philhealth || 0);
    const phWithoutVal = Number(data.without_philhealth || 0);

    document.getElementById('label-philhealth-with').innerText = phWithVal.toLocaleString();
    document.getElementById('label-philhealth-without').innerText = phWithoutVal.toLocaleString();

    // Charts
    if (philhealthChart) {
        philhealthChart.data.datasets[0].data = [data.with_philhealth || 0, data.without_philhealth || 0];
        philhealthChart.update();
    }

    if (monthlyData && trendChart) {
        const counts = Array(12).fill(0);
        monthlyData.forEach(row => {
            counts[Number(row.month_num) - 1] = Number(row.count);
        });
        trendChart.data.datasets[0].data = counts;
        trendChart.update();
    }
}

function exportReport(format) {
    const { from, to } = getQueryDates();
    const exportParam = format === 'pdf' ? 'export_pdf' : 'export_excel';
    
    // Redirect to the export endpoint
    window.location.href = `${window.__APP__.basePath}/index.php?role=branch_admin&page=reports&${exportParam}=1&from=${from}&to=${to}`;
}
