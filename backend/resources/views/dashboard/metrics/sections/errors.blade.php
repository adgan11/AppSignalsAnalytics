<div>
    <h2 class="text-xl font-semibold text-gray-900">Errors</h2>
    <p class="text-sm text-gray-600 mt-1">Crash and error activity over the last 30 days.</p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="chart-card">
        <div class="flex justify-between items-center mb-4">
            <h3 class="chart-title mb-0">Most Frequent Errors (overall)</h3>
            <span class="text-xs text-gray-400">Updated just now</span>
        </div>
        <div class="h-64">
            <canvas id="overallTopChart"></canvas>
        </div>
    </div>

    <div class="chart-card">
        <div class="flex justify-between items-center mb-4">
            <h3 class="chart-title mb-0">History of Errors (overall)</h3>
            <span class="text-xs text-gray-400">Updated just now</span>
        </div>
        <div class="h-64">
            <canvas id="overallHistoryChart"></canvas>
        </div>
    </div>

    <div class="chart-card">
        <div class="flex justify-between items-center mb-4">
            <h3 class="chart-title mb-0">Most Frequent Thrown Exception Errors</h3>
            <span class="text-xs text-gray-400">Updated just now</span>
        </div>
        <div class="h-64">
            <canvas id="thrownTopChart"></canvas>
        </div>
    </div>

    <div class="chart-card">
        <div class="flex justify-between items-center mb-4">
            <h3 class="chart-title mb-0">History of Thrown Exception Errors</h3>
            <span class="text-xs text-gray-400">Updated just now</span>
        </div>
        <div class="h-64">
            <canvas id="thrownHistoryChart"></canvas>
        </div>
    </div>

    <div class="chart-card">
        <div class="flex justify-between items-center mb-4">
            <h3 class="chart-title mb-0">Most Frequent User Input Errors</h3>
            <span class="text-xs text-gray-400">Updated just now</span>
        </div>
        <div class="h-64">
            <canvas id="userInputTopChart"></canvas>
        </div>
    </div>

    <div class="chart-card">
        <div class="flex justify-between items-center mb-4">
            <h3 class="chart-title mb-0">History of User Input Errors</h3>
            <span class="text-xs text-gray-400">Updated just now</span>
        </div>
        <div class="h-64">
            <canvas id="userInputHistoryChart"></canvas>
        </div>
    </div>

    <div class="chart-card">
        <div class="flex justify-between items-center mb-4">
            <h3 class="chart-title mb-0">Most Frequent App State Errors</h3>
            <span class="text-xs text-gray-400">Updated just now</span>
        </div>
        <div class="h-64">
            <canvas id="appStateTopChart"></canvas>
        </div>
    </div>

    <div class="chart-card">
        <div class="flex justify-between items-center mb-4">
            <h3 class="chart-title mb-0">History of App State Errors</h3>
            <span class="text-xs text-gray-400">Updated just now</span>
        </div>
        <div class="h-64">
            <canvas id="appStateHistoryChart"></canvas>
        </div>
    </div>
</div>

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        (() => {
            const initialData = @json($metricsData);
            const charts = {};
            const palette = [
                'rgb(239, 68, 68)', 'rgb(248, 113, 113)', 'rgb(251, 146, 60)',
                'rgb(251, 191, 36)', 'rgb(52, 211, 153)', 'rgb(59, 130, 246)',
                'rgb(99, 102, 241)', 'rgb(139, 92, 246)', 'rgb(168, 85, 247)', 'rgb(192, 132, 252)'
            ];

            const barOptions = {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' } },
                    y: { grid: { display: false } }
                }
            };

            const lineOptions = {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' } },
                    x: { grid: { display: false } }
                }
            };

            const updateChart = (chart, labels, data, colors) => {
                chart.data.labels = labels;
                chart.data.datasets[0].data = data;
                if (colors) {
                    chart.data.datasets[0].backgroundColor = colors;
                }
                chart.update();
            };

            const formatDateLabels = (data) => Object.keys(data).map(d => {
                const date = new Date(d);
                return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
            });

            const renderCharts = (data) => {
                const overallTopLabels = Object.keys(data.overallTop || {});
                const overallTopValues = Object.values(data.overallTop || {});
                if (!charts.overallTop) {
                    charts.overallTop = new Chart(document.getElementById('overallTopChart'), {
                        type: 'bar',
                        data: {
                            labels: overallTopLabels,
                            datasets: [{
                                data: overallTopValues,
                                backgroundColor: palette
                            }]
                        },
                        options: barOptions
                    });
                } else {
                    updateChart(charts.overallTop, overallTopLabels, overallTopValues, palette);
                }

                const overallHistoryLabels = formatDateLabels(data.overallHistory || {});
                const overallHistoryValues = Object.values(data.overallHistory || {});
                if (!charts.overallHistory) {
                    charts.overallHistory = new Chart(document.getElementById('overallHistoryChart'), {
                        type: 'line',
                        data: {
                            labels: overallHistoryLabels,
                            datasets: [{
                                data: overallHistoryValues,
                                borderColor: palette[0],
                                backgroundColor: 'rgba(239, 68, 68, 0.15)',
                                fill: true,
                                tension: 0.3
                            }]
                        },
                        options: lineOptions
                    });
                } else {
                    charts.overallHistory.data.labels = overallHistoryLabels;
                    charts.overallHistory.data.datasets[0].data = overallHistoryValues;
                    charts.overallHistory.update();
                }

                const thrownTopLabels = Object.keys(data.thrownTop || {});
                const thrownTopValues = Object.values(data.thrownTop || {});
                if (!charts.thrownTop) {
                    charts.thrownTop = new Chart(document.getElementById('thrownTopChart'), {
                        type: 'bar',
                        data: {
                            labels: thrownTopLabels,
                            datasets: [{
                                data: thrownTopValues,
                                backgroundColor: palette
                            }]
                        },
                        options: barOptions
                    });
                } else {
                    updateChart(charts.thrownTop, thrownTopLabels, thrownTopValues, palette);
                }

                const thrownHistoryLabels = formatDateLabels(data.thrownHistory || {});
                const thrownHistoryValues = Object.values(data.thrownHistory || {});
                if (!charts.thrownHistory) {
                    charts.thrownHistory = new Chart(document.getElementById('thrownHistoryChart'), {
                        type: 'line',
                        data: {
                            labels: thrownHistoryLabels,
                            datasets: [{
                                data: thrownHistoryValues,
                                borderColor: palette[1],
                                backgroundColor: 'rgba(248, 113, 113, 0.15)',
                                fill: true,
                                tension: 0.3
                            }]
                        },
                        options: lineOptions
                    });
                } else {
                    charts.thrownHistory.data.labels = thrownHistoryLabels;
                    charts.thrownHistory.data.datasets[0].data = thrownHistoryValues;
                    charts.thrownHistory.update();
                }

                const userInputTopLabels = Object.keys(data.userInputTop || {});
                const userInputTopValues = Object.values(data.userInputTop || {});
                if (!charts.userInputTop) {
                    charts.userInputTop = new Chart(document.getElementById('userInputTopChart'), {
                        type: 'bar',
                        data: {
                            labels: userInputTopLabels,
                            datasets: [{
                                data: userInputTopValues,
                                backgroundColor: palette
                            }]
                        },
                        options: barOptions
                    });
                } else {
                    updateChart(charts.userInputTop, userInputTopLabels, userInputTopValues, palette);
                }

                const userInputHistoryLabels = formatDateLabels(data.userInputHistory || {});
                const userInputHistoryValues = Object.values(data.userInputHistory || {});
                if (!charts.userInputHistory) {
                    charts.userInputHistory = new Chart(document.getElementById('userInputHistoryChart'), {
                        type: 'line',
                        data: {
                            labels: userInputHistoryLabels,
                            datasets: [{
                                data: userInputHistoryValues,
                                borderColor: palette[4],
                                backgroundColor: 'rgba(52, 211, 153, 0.15)',
                                fill: true,
                                tension: 0.3
                            }]
                        },
                        options: lineOptions
                    });
                } else {
                    charts.userInputHistory.data.labels = userInputHistoryLabels;
                    charts.userInputHistory.data.datasets[0].data = userInputHistoryValues;
                    charts.userInputHistory.update();
                }

                const appStateTopLabels = Object.keys(data.appStateTop || {});
                const appStateTopValues = Object.values(data.appStateTop || {});
                if (!charts.appStateTop) {
                    charts.appStateTop = new Chart(document.getElementById('appStateTopChart'), {
                        type: 'bar',
                        data: {
                            labels: appStateTopLabels,
                            datasets: [{
                                data: appStateTopValues,
                                backgroundColor: palette
                            }]
                        },
                        options: barOptions
                    });
                } else {
                    updateChart(charts.appStateTop, appStateTopLabels, appStateTopValues, palette);
                }

                const appStateHistoryLabels = formatDateLabels(data.appStateHistory || {});
                const appStateHistoryValues = Object.values(data.appStateHistory || {});
                if (!charts.appStateHistory) {
                    charts.appStateHistory = new Chart(document.getElementById('appStateHistoryChart'), {
                        type: 'line',
                        data: {
                            labels: appStateHistoryLabels,
                            datasets: [{
                                data: appStateHistoryValues,
                                borderColor: palette[5],
                                backgroundColor: 'rgba(59, 130, 246, 0.15)',
                                fill: true,
                                tension: 0.3
                            }]
                        },
                        options: lineOptions
                    });
                } else {
                    charts.appStateHistory.data.labels = appStateHistoryLabels;
                    charts.appStateHistory.data.datasets[0].data = appStateHistoryValues;
                    charts.appStateHistory.update();
                }
            };

            const refreshData = async () => {
                try {
                    const response = await fetch(@json(route('dashboard.metrics.data', [$project, 'errors'])), {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    if (!response.ok) {
                        return;
                    }
                    const payload = await response.json();
                    renderCharts(payload.data);
                } catch (error) {
                    console.error('Failed to refresh error metrics', error);
                }
            };

            let refreshTimer = null;
            const scheduleRefresh = () => {
                if (refreshTimer) {
                    return;
                }
                refreshTimer = setTimeout(() => {
                    refreshTimer = null;
                    refreshData();
                }, 1000);
            };

            renderCharts(initialData);

            if (window.Echo) {
                window.Echo.private('project.{{ $project->id }}.events')
                    .listen('.NewEventLogged', scheduleRefresh)
                    .listen('.NewCrashLogged', scheduleRefresh);
            }

            setInterval(refreshData, 60000);
        })();
    </script>
@endpush
