<div>
    <h2 class="text-xl font-semibold text-gray-900">Versions</h2>
    <p class="text-sm text-gray-600 mt-1">App, build, and SDK versions seen in the last 30 days.</p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="chart-card">
        <div class="flex justify-between items-center mb-4">
            <h3 class="chart-title mb-0">App Versions</h3>
            <span class="text-xs text-gray-400">Updated just now</span>
        </div>
        <div class="h-64">
            <canvas id="appVersionsChart"></canvas>
        </div>
    </div>

    <div class="chart-card">
        <div class="flex justify-between items-center mb-4">
            <h3 class="chart-title mb-0">Build Numbers</h3>
            <span class="text-xs text-gray-400">Updated just now</span>
        </div>
        <div class="h-64">
            <canvas id="buildNumbersChart"></canvas>
        </div>
    </div>

    <div class="chart-card">
        <div class="flex justify-between items-center mb-4">
            <h3 class="chart-title mb-0">SDK Versions</h3>
            <span class="text-xs text-gray-400">Updated just now</span>
        </div>
        <div class="h-64">
            <canvas id="sdkVersionsChart"></canvas>
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
                'rgb(59, 130, 246)', 'rgb(99, 102, 241)', 'rgb(139, 92, 246)',
                'rgb(168, 85, 247)', 'rgb(192, 132, 252)', 'rgb(216, 180, 254)',
                'rgb(233, 213, 255)', 'rgb(245, 208, 254)', 'rgb(251, 207, 232)', 'rgb(252, 231, 243)'
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

            const updateChart = (chart, labels, data, colors) => {
                chart.data.labels = labels;
                chart.data.datasets[0].data = data;
                if (colors) {
                    chart.data.datasets[0].backgroundColor = colors;
                }
                chart.update();
            };

            const renderCharts = (data) => {
                const appLabels = Object.keys(data.appVersions || {});
                const appValues = Object.values(data.appVersions || {});
                if (!charts.appVersions) {
                    charts.appVersions = new Chart(document.getElementById('appVersionsChart'), {
                        type: 'bar',
                        data: {
                            labels: appLabels,
                            datasets: [{
                                data: appValues,
                                backgroundColor: palette
                            }]
                        },
                        options: barOptions
                    });
                } else {
                    updateChart(charts.appVersions, appLabels, appValues, palette);
                }

                const buildLabels = Object.keys(data.buildNumbers || {});
                const buildValues = Object.values(data.buildNumbers || {});
                if (!charts.buildNumbers) {
                    charts.buildNumbers = new Chart(document.getElementById('buildNumbersChart'), {
                        type: 'bar',
                        data: {
                            labels: buildLabels,
                            datasets: [{
                                data: buildValues,
                                backgroundColor: palette
                            }]
                        },
                        options: barOptions
                    });
                } else {
                    updateChart(charts.buildNumbers, buildLabels, buildValues, palette);
                }

                const sdkLabels = Object.keys(data.sdkVersions || {});
                const sdkValues = Object.values(data.sdkVersions || {});
                if (!charts.sdkVersions) {
                    charts.sdkVersions = new Chart(document.getElementById('sdkVersionsChart'), {
                        type: 'bar',
                        data: {
                            labels: sdkLabels,
                            datasets: [{
                                data: sdkValues,
                                backgroundColor: palette
                            }]
                        },
                        options: barOptions
                    });
                } else {
                    updateChart(charts.sdkVersions, sdkLabels, sdkValues, palette);
                }
            };

            const refreshData = async () => {
                try {
                    const response = await fetch(@json(route('dashboard.metrics.data', [$project, 'versions'])), {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    if (!response.ok) {
                        return;
                    }
                    const payload = await response.json();
                    renderCharts(payload.data);
                } catch (error) {
                    console.error('Failed to refresh version metrics', error);
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
                    .listen('.NewEventLogged', scheduleRefresh);
            }

            setInterval(refreshData, 60000);
        })();
    </script>
@endpush
