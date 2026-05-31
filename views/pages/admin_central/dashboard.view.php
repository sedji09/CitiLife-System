<?php
/**
 * dashboard.php (Admin Central)
 */
?>

<!-- Include Chart.js -->
<script src="/<?= PROJECT_DIR ?>/public/assets/js/chart.min.js"></script>

<div class="space-y-4 pb-10"> <!-- Compact spacing -->
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-2">
        <div>
            <h1 class="text-xl font-bold text-gray-900 tracking-tight">Admin Central Dashboard</h1>
            <p class="text-xs text-gray-500 mt-1">System-wide overview of patient and branch statistics.</p>
        </div>
        <div class="flex items-center gap-2">
            <span
                class="px-3 py-1.5 bg-red-50 text-red-700 font-semibold rounded-lg text-xs border border-red-100 flex items-center gap-2 cursor-default">
                <i data-lucide="building-2" class="w-3.5 h-3.5"></i> Overall Analytics
            </span>
        </div>
    </div>

    <!-- Summary Cards Row (5 Cards) -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
        <!-- Total Patients -->
        <div class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm hover:shadow-md transition-all">
            <div class="text-blue-500 mb-2"><i data-lucide="users" class="w-5 h-5"></i></div>
            <div class="text-2xl font-black text-gray-900"><?= number_format($dashboardData['totals']['patients']) ?>
            </div>
            <div class="text-[10px] text-gray-500 mt-0.5 font-medium uppercase tracking-wider flex items-center gap-1">
                Total Patients of All Branches
            </div>
        </div>

        <!-- Total Active Branches -->
        <div class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm hover:shadow-md transition-all">
            <div class="text-indigo-500 mb-2"><i data-lucide="building" class="w-5 h-5"></i></div>
            <div class="text-2xl font-black text-gray-900">
                <?= number_format($dashboardData['totals']['active_branches']) ?>
            </div>
            <div class="text-[10px] text-gray-500 mt-0.5 font-medium uppercase tracking-wider">Active Branches
            </div>
        </div>

        <!-- Total Active Users -->
        <div class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm hover:shadow-md transition-all">
            <div class="text-purple-500 mb-2"><i data-lucide="user-check" class="w-5 h-5"></i></div>
            <div class="text-2xl font-black text-gray-900">
                <?= number_format($dashboardData['totals']['active_users']) ?>
            </div>
            <div class="text-[10px] text-gray-500 mt-0.5 font-medium uppercase tracking-wider">Active Staff Users
            </div>
        </div>

        <!-- Emergency -->
        <div class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm hover:shadow-md transition-all">
            <div class="text-red-500 mb-2"><i data-lucide="alert-circle" class="w-5 h-5"></i></div>
            <div class="text-2xl font-black text-gray-900">
                <?= number_format($dashboardData['totals']['emergency']) ?>
            </div>
            <div class="text-[10px] text-gray-500 mt-0.5 font-medium uppercase tracking-wider">Emergency Cases
            </div>
        </div>

        <!-- Urgent -->
        <div class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm hover:shadow-md transition-all">
            <div class="text-orange-500 mb-2"><i data-lucide="clock" class="w-5 h-5"></i></div>
            <div class="text-2xl font-black text-gray-900">
                <?= number_format($dashboardData['totals']['urgent']) ?>
            </div>
            <div class="text-[10px] text-gray-500 mt-0.5 font-medium uppercase tracking-wider">Urgent Cases
            </div>
        </div>

        <!-- Routine -->
        <div class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm hover:shadow-md transition-all">
            <div class="text-green-500 mb-2"><i data-lucide="check-circle" class="w-5 h-5"></i></div>
            <div class="text-2xl font-black text-gray-900">
                <?= number_format($dashboardData['totals']['routine']) ?>
            </div>
            <div class="text-[10px] text-gray-500 mt-0.5 font-medium uppercase tracking-wider">Routine Cases
            </div>
        </div>
    </div>

    <!-- Charts Grid (2 columns, compact height) -->
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">

        <!-- Chart 1: Monthly Trend -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden flex flex-col">
            <div class="px-5 py-4 border-b border-gray-50 flex items-center justify-between">
                <div>
                    <h3 class="font-bold text-gray-900 text-sm">Monthly Registrations</h3>
                    <p class="text-[10px] text-gray-400 uppercase tracking-widest font-bold mt-1">For
                        <?= date('Y') ?>
                    </p>
                </div>
                <i data-lucide="bar-chart-3" class="w-4 h-4 text-gray-400"></i>
            </div>
            <div class="p-4 flex-grow relative h-[240px]">
                <canvas id="monthlyTrendChart"></canvas>
            </div>
        </div>

        <!-- Chart 2: Branch Distribution Doughnut -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden flex flex-col">
            <div class="px-5 py-4 border-b border-gray-50 flex items-center justify-between">
                <div>
                    <h3 class="font-bold text-gray-900 text-sm">Patient Distribution</h3>
                    <p class="text-[10px] text-gray-400 uppercase tracking-widest font-bold mt-1">Across all
                        branches
                    </p>
                </div>
                <i data-lucide="pie-chart" class="w-4 h-4 text-gray-400"></i>
            </div>
            <div class="p-4 flex flex-col sm:flex-row items-center justify-center gap-8 flex-grow">
                <!-- Left: Chart -->
                <div class="relative w-40 h-40 flex-shrink-0">
                    <canvas id="branchDistributionChart"></canvas>
                    <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none mt-1">
                        <span class="text-xs font-bold text-gray-400 leading-none">Total</span>
                        <span
                            class="text-xl font-black text-gray-900 leading-tight"><?= number_format($dashboardData['totals']['patients']) ?></span>
                    </div>
                </div>

                <!-- Right: Custom Legend -->
                <div id="patientDistributionLegend" class="grid grid-cols-2 gap-x-6 gap-y-2.5 w-full sm:w-auto">
                    <!-- Legend generated by JS -->
                </div>
            </div>
        </div>

        <!-- Chart 3: Daily X-ray Requests -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden flex flex-col">
            <div class="px-5 py-4 border-b border-gray-50 flex items-center justify-between">
                <div>
                    <h3 class="font-bold text-gray-900 text-sm">Daily X-ray Requests</h3>
                    <p class="text-[10px] text-gray-400 uppercase tracking-widest font-bold mt-1">For this week
                    </p>
                </div>
                <i data-lucide="activity" class="w-4 h-4 text-gray-400"></i>
            </div>
            <div class="p-4 flex-grow relative h-[240px]">
                <canvas id="dailyTrendChart"></canvas>
            </div>
        </div>

        <!-- Chart 4: X-ray Cases per Branch Bar -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden flex flex-col">
            <div class="px-5 py-4 border-b border-gray-50 flex items-center justify-between">
                <div>
                    <h3 class="font-bold text-gray-900 text-sm text-blaxk px-1 -mx-1 inline-block">X-ray Cases
                        per
                        Branch</h3>
                </div>
                <i data-lucide="building" class="w-4 h-4 text-gray-400"></i>
            </div>
            <div class="p-4 flex-grow relative h-[240px]">
                <canvas id="branchBarChart"></canvas>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const chartData = <?= json_encode($dashboardData['charts']) ?>;
        const totalPatients = <?= $dashboardData['totals']['patients'] ?>;

        const palette = [
            '#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6',
            '#EC4899', '#06B6D4', '#F97316', '#14B8A6', '#84CC16'
        ];

        // Premium Universal Chart Configs (Works flawlessly in Light/Dark Mode)
        const gridColor = 'rgba(148, 163, 184, 0.15)'; // Slate 400 @ 15% opacity
        const tickColor = '#94a3b8'; // Slate 400
        const labelColor = '#94a3b8';

        const commonOptions = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            interaction: { intersect: false, mode: 'index' },
        };

        // 1. Monthly Bar Chart
        const ctxMonthly = document.getElementById('monthlyTrendChart').getContext('2d');
        const barGradient = ctxMonthly.createLinearGradient(0, 0, 0, 240);
        barGradient.addColorStop(0, 'rgba(29, 78, 216, 0.9)'); // Blue 700
        barGradient.addColorStop(1, 'rgba(37, 99, 235, 0.1)'); // Blue 600

        new Chart(ctxMonthly, {
            type: 'bar',
            data: {
                labels: chartData.monthly.labels,
                datasets: [{
                    data: chartData.monthly.data,
                    backgroundColor: barGradient,
                    hoverBackgroundColor: 'rgba(29, 78, 216, 1)',
                    borderRadius: 4, barPercentage: 0.6
                }]
            },
            options: {
                ...commonOptions,
                plugins: {
                    ...commonOptions.plugins,
                    tooltip: {
                        backgroundColor: '#1e293b',
                        padding: 10,
                        bodyFont: { size: 13, weight: 'bold' },
                        titleColor: '#f8fafc',
                        bodyColor: '#f8fafc',
                        borderColor: 'rgba(255,255,255,0.05)',
                        borderWidth: 1,
                        cornerRadius: 8
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: gridColor, borderDash: [5, 5] },
                        border: { display: false },
                        ticks: { font: { size: 10 }, color: tickColor, padding: 8 }
                    },
                    x: {
                        grid: { display: false },
                        border: { display: false },
                        ticks: { font: { size: 10, weight: 'bold' }, color: labelColor }
                    }
                }
            }
        });

        // 2. Branch Distribution Doughnut
        const ctxDoughnut = document.getElementById('branchDistributionChart').getContext('2d');
        new Chart(ctxDoughnut, {
            type: 'doughnut',
            data: {
                labels: chartData.branches.labels,
                datasets: [{
                    data: chartData.branches.data,
                    backgroundColor: palette,
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false, cutout: '70%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1e293b',
                        padding: 10,
                        bodyFont: { size: 13, weight: 'bold' },
                        titleColor: '#f8fafc',
                        bodyColor: '#f8fafc',
                        borderColor: 'rgba(255,255,255,0.05)',
                        borderWidth: 1,
                        cornerRadius: 8,
                        callbacks: {
                            label: function (context) {
                                let pct = totalPatients > 0 ? Math.round((context.raw / totalPatients) * 100) : 0;
                                return ' ' + context.raw + ' patients (' + pct + '%)';
                            }
                        }
                    }
                }
            }
        });

        // 2.1 Custom Legend Generation
        const legendContainer = document.getElementById('patientDistributionLegend');
        chartData.branches.labels.forEach((label, i) => {
            const dataVal = chartData.branches.data[i];
            const color = palette[i % palette.length];
            const pct = totalPatients > 0 ? Math.round((dataVal / totalPatients) * 100) : 0;

            const legendItem = document.createElement('div');
            legendItem.className = 'flex items-center justify-between gap-4 text-sm';
            legendItem.innerHTML = `
                <div class="flex items-center gap-2">
                    <div class="w-3 h-3 rounded-full" style="background-color: ${color}"></div>
                    <span class="text-gray-600 font-medium">${label.replace(' Branch', '')}</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="font-bold text-gray-900">${dataVal}</span>
                </div>
            `;
            legendContainer.appendChild(legendItem);
        });

        // 3. Daily Line Chart
        const ctxDaily = document.getElementById('dailyTrendChart').getContext('2d');
        new Chart(ctxDaily, {
            type: 'line',
            data: {
                labels: chartData.daily.labels,
                datasets: [{
                    data: chartData.daily.data,
                    borderColor: 'rgb(37, 99, 235)', backgroundColor: 'rgba(37, 99, 235, 0.1)',
                    borderWidth: 2, pointBackgroundColor: 'rgb(37, 99, 235)', pointBorderColor: '#fff',
                    pointRadius: 4, pointHoverRadius: 6, fill: true, tension: 0.4
                }]
            },
            options: {
                ...commonOptions,
                plugins: {
                    ...commonOptions.plugins,
                    tooltip: {
                        backgroundColor: '#1e293b',
                        titleColor: '#f8fafc',
                        bodyColor: '#60a5fa',
                        borderColor: 'rgba(255,255,255,0.05)',
                        borderWidth: 1,
                        padding: 10,
                        bodyFont: { size: 13, weight: 'bold' },
                        cornerRadius: 8,
                        callbacks: { label: function (context) { return 'requests : ' + context.raw; } }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: gridColor, borderDash: [5, 5] },
                        border: { display: false },
                        ticks: { font: { size: 10 }, color: tickColor, padding: 8 }
                    },
                    x: {
                        grid: { display: false },
                        border: { display: false },
                        ticks: { font: { size: 10, weight: 'bold' }, color: labelColor }
                    }
                }
            }
        });

        // 4. Branch Bar Chart
        const ctxBranchBar = document.getElementById('branchBarChart').getContext('2d');
        const branchGradient = ctxBranchBar.createLinearGradient(0, 0, 0, 240);
        branchGradient.addColorStop(0, 'rgba(29, 78, 216, 0.9)');
        branchGradient.addColorStop(1, 'rgba(37, 99, 235, 0.1)');

        new Chart(ctxBranchBar, {
            type: 'bar',
            data: {
                labels: chartData.branches.labels.map(l => l.replace(' Branch', '')),
                datasets: [{
                    data: chartData.branches.data,
                    backgroundColor: branchGradient, hoverBackgroundColor: 'rgba(29, 78, 216, 1)',
                    borderRadius: 4, barPercentage: 0.7
                }]
            },
            options: {
                ...commonOptions,
                plugins: {
                    ...commonOptions.plugins,
                    tooltip: {
                        backgroundColor: '#1e293b',
                        titleColor: '#f8fafc',
                        bodyColor: '#60a5fa',
                        borderColor: 'rgba(255,255,255,0.05)',
                        borderWidth: 1,
                        padding: 10,
                        bodyFont: { size: 13, weight: 'bold' },
                        cornerRadius: 8,
                        callbacks: { label: function (context) { return 'cases : ' + context.raw; } }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: gridColor, borderDash: [5, 5] },
                        border: { display: false },
                        ticks: { font: { size: 10 }, color: tickColor, padding: 8, stepSize: 4 }
                    },
                    x: {
                        grid: { display: false },
                        border: { display: false },
                        ticks: { font: { size: 10, weight: 'bold' }, color: labelColor }
                    }
                }
            }
        });
    });
</script>