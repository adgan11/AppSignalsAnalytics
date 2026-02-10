<div>
    <h2 class="text-xl font-semibold text-gray-900">Localization</h2>
    <p class="text-sm text-gray-600 mt-1">Language and region settings reported in the last 30 days.</p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="chart-card">
        <div class="flex justify-between items-center mb-4">
            <h3 class="chart-title mb-0">Preferred Language</h3>
            <span class="text-xs text-gray-400">Updated just now</span>
        </div>
        <div class="h-64">
            <canvas id="preferredLanguageChart"></canvas>
        </div>
    </div>

    <div class="chart-card">
        <div class="flex justify-between items-center mb-4">
            <h3 class="chart-title mb-0">App Language</h3>
            <span class="text-xs text-gray-400">Updated just now</span>
        </div>
        <div class="h-64">
            <canvas id="appLanguageChart"></canvas>
        </div>
    </div>

    <div class="chart-card">
        <div class="flex justify-between items-center mb-4">
            <h3 class="chart-title mb-0">Region</h3>
            <span class="text-xs text-gray-400">Updated just now</span>
        </div>
        <div class="h-64">
            <canvas id="regionChart"></canvas>
        </div>
    </div>

    <div class="chart-card">
        <div class="flex justify-between items-center mb-4">
            <h3 class="chart-title mb-0">Layout Direction</h3>
            <span class="text-xs text-gray-400">Updated just now</span>
        </div>
        <div class="h-64">
            <canvas id="layoutDirectionChart"></canvas>
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

            const doughnutOptions = {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'right', labels: { usePointStyle: true, padding: 12 } }
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
                const preferredLabels = Object.keys(data.preferredLanguage || {});
                const preferredValues = Object.values(data.preferredLanguage || {});
                if (!charts.preferredLanguage) {
                    charts.preferredLanguage = new Chart(document.getElementById('preferredLanguageChart'), {
                        type: 'doughnut',
                        data: {
                            labels: preferredLabels,
                            datasets: [{
                                data: preferredValues,
                                backgroundColor: palette
                            }]
                        },
                        options: doughnutOptions
                    });
                } else {
                    updateChart(charts.preferredLanguage, preferredLabels, preferredValues, palette);
                }

                const appLabels = Object.keys(data.appLanguage || {});
                const appValues = Object.values(data.appLanguage || {});
                if (!charts.appLanguage) {
                    charts.appLanguage = new Chart(document.getElementById('appLanguageChart'), {
                        type: 'doughnut',
                        data: {
                            labels: appLabels,
                            datasets: [{
                                data: appValues,
                                backgroundColor: palette
                            }]
                        },
                        options: doughnutOptions
                    });
                } else {
                    updateChart(charts.appLanguage, appLabels, appValues, palette);
                }

                const regionLabels = Object.keys(data.region || {});
                const regionValues = Object.values(data.region || {});
                if (!charts.region) {
                    charts.region = new Chart(document.getElementById('regionChart'), {
                        type: 'doughnut',
                        data: {
                            labels: regionLabels,
                            datasets: [{
                                data: regionValues,
                                backgroundColor: palette
                            }]
                        },
                        options: doughnutOptions
                    });
                } else {
                    updateChart(charts.region, regionLabels, regionValues, palette);
                }

                const directionLabels = Object.keys(data.layoutDirection || {});
                const directionValues = Object.values(data.layoutDirection || {});
                if (!charts.layoutDirection) {
                    charts.layoutDirection = new Chart(document.getElementById('layoutDirectionChart'), {
                        type: 'doughnut',
                        data: {
                            labels: directionLabels,
                            datasets: [{
                                data: directionValues,
                                backgroundColor: palette
                            }]
                        },
                        options: doughnutOptions
                    });
                } else {
                    updateChart(charts.layoutDirection, directionLabels, directionValues, palette);
                }
            };

            const refreshData = async () => {
                try {
                    const response = await fetch(@json(route('dashboard.metrics.data', [$project, 'localization'])), {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    if (!response.ok) {
                        return;
                    }
                    const payload = await response.json();
                    renderCharts(payload.data);
                } catch (error) {
                    console.error('Failed to refresh localization metrics', error);
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
