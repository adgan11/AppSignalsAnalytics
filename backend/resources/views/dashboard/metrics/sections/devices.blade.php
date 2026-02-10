<div>
    <h2 class="text-xl font-semibold text-gray-900">Devices</h2>
    <p class="text-sm text-gray-600 mt-1">Device characteristics and display settings reported in the last 30 days.</p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="chart-card">
        <div class="flex justify-between items-center mb-4">
            <h3 class="chart-title mb-0">Models</h3>
            <span class="text-xs text-gray-400">Updated just now</span>
        </div>
        <div class="h-64">
            <canvas id="deviceModelsChart"></canvas>
        </div>
    </div>

    <div class="chart-card">
        <div class="flex justify-between items-center mb-4">
            <h3 class="chart-title mb-0">System Versions</h3>
            <span class="text-xs text-gray-400">Updated just now</span>
        </div>
        <div class="h-64">
            <canvas id="systemVersionsChart"></canvas>
        </div>
    </div>

    <div class="chart-card">
        <div class="flex justify-between items-center mb-4">
            <h3 class="chart-title mb-0">Types</h3>
            <span class="text-xs text-gray-400">Updated just now</span>
        </div>
        <div class="h-64">
            <canvas id="deviceTypesChart"></canvas>
        </div>
    </div>

    <div class="chart-card">
        <div class="flex justify-between items-center mb-4">
            <h3 class="chart-title mb-0">Platforms</h3>
            <span class="text-xs text-gray-400">Updated just now</span>
        </div>
        <div class="h-64">
            <canvas id="platformsChart"></canvas>
        </div>
    </div>

    <div class="chart-card">
        <div class="flex justify-between items-center mb-4">
            <h3 class="chart-title mb-0">Screen Resolution Width</h3>
            <span class="text-xs text-gray-400">Updated just now</span>
        </div>
        <div class="h-64">
            <canvas id="screenWidthChart"></canvas>
        </div>
    </div>

    <div class="chart-card">
        <div class="flex justify-between items-center mb-4">
            <h3 class="chart-title mb-0">Screen Resolution Height</h3>
            <span class="text-xs text-gray-400">Updated just now</span>
        </div>
        <div class="h-64">
            <canvas id="screenHeightChart"></canvas>
        </div>
    </div>

    <div class="chart-card">
        <div class="flex justify-between items-center mb-4">
            <h3 class="chart-title mb-0">Color Scheme</h3>
            <span class="text-xs text-gray-400">Updated just now</span>
        </div>
        <div class="h-64">
            <canvas id="colorSchemeChart"></canvas>
        </div>
    </div>

    <div class="chart-card">
        <div class="flex justify-between items-center mb-4">
            <h3 class="chart-title mb-0">Orientation</h3>
            <span class="text-xs text-gray-400">Updated just now</span>
        </div>
        <div class="h-64">
            <canvas id="orientationChart"></canvas>
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
                const modelLabels = Object.keys(data.deviceModels || {});
                const modelValues = Object.values(data.deviceModels || {});
                if (!charts.deviceModels) {
                    charts.deviceModels = new Chart(document.getElementById('deviceModelsChart'), {
                        type: 'bar',
                        data: {
                            labels: modelLabels,
                            datasets: [{
                                data: modelValues,
                                backgroundColor: palette
                            }]
                        },
                        options: barOptions
                    });
                } else {
                    updateChart(charts.deviceModels, modelLabels, modelValues, palette);
                }

                const systemLabels = Object.keys(data.systemVersions || {});
                const systemValues = Object.values(data.systemVersions || {});
                if (!charts.systemVersions) {
                    charts.systemVersions = new Chart(document.getElementById('systemVersionsChart'), {
                        type: 'doughnut',
                        data: {
                            labels: systemLabels,
                            datasets: [{
                                data: systemValues,
                                backgroundColor: palette
                            }]
                        },
                        options: doughnutOptions
                    });
                } else {
                    updateChart(charts.systemVersions, systemLabels, systemValues, palette);
                }

                const typeLabels = Object.keys(data.deviceTypes || {});
                const typeValues = Object.values(data.deviceTypes || {});
                if (!charts.deviceTypes) {
                    charts.deviceTypes = new Chart(document.getElementById('deviceTypesChart'), {
                        type: 'bar',
                        data: {
                            labels: typeLabels,
                            datasets: [{
                                data: typeValues,
                                backgroundColor: palette
                            }]
                        },
                        options: barOptions
                    });
                } else {
                    updateChart(charts.deviceTypes, typeLabels, typeValues, palette);
                }

                const platformLabels = Object.keys(data.platforms || {});
                const platformValues = Object.values(data.platforms || {});
                if (!charts.platforms) {
                    charts.platforms = new Chart(document.getElementById('platformsChart'), {
                        type: 'doughnut',
                        data: {
                            labels: platformLabels,
                            datasets: [{
                                data: platformValues,
                                backgroundColor: palette
                            }]
                        },
                        options: doughnutOptions
                    });
                } else {
                    updateChart(charts.platforms, platformLabels, platformValues, palette);
                }

                const widthLabels = Object.keys(data.screenWidths || {});
                const widthValues = Object.values(data.screenWidths || {});
                if (!charts.screenWidth) {
                    charts.screenWidth = new Chart(document.getElementById('screenWidthChart'), {
                        type: 'bar',
                        data: {
                            labels: widthLabels,
                            datasets: [{
                                data: widthValues,
                                backgroundColor: palette
                            }]
                        },
                        options: barOptions
                    });
                } else {
                    updateChart(charts.screenWidth, widthLabels, widthValues, palette);
                }

                const heightLabels = Object.keys(data.screenHeights || {});
                const heightValues = Object.values(data.screenHeights || {});
                if (!charts.screenHeight) {
                    charts.screenHeight = new Chart(document.getElementById('screenHeightChart'), {
                        type: 'bar',
                        data: {
                            labels: heightLabels,
                            datasets: [{
                                data: heightValues,
                                backgroundColor: palette
                            }]
                        },
                        options: barOptions
                    });
                } else {
                    updateChart(charts.screenHeight, heightLabels, heightValues, palette);
                }

                const colorLabels = Object.keys(data.colorSchemes || {});
                const colorValues = Object.values(data.colorSchemes || {});
                if (!charts.colorScheme) {
                    charts.colorScheme = new Chart(document.getElementById('colorSchemeChart'), {
                        type: 'doughnut',
                        data: {
                            labels: colorLabels,
                            datasets: [{
                                data: colorValues,
                                backgroundColor: palette
                            }]
                        },
                        options: doughnutOptions
                    });
                } else {
                    updateChart(charts.colorScheme, colorLabels, colorValues, palette);
                }

                const orientationLabels = Object.keys(data.orientations || {});
                const orientationValues = Object.values(data.orientations || {});
                if (!charts.orientation) {
                    charts.orientation = new Chart(document.getElementById('orientationChart'), {
                        type: 'doughnut',
                        data: {
                            labels: orientationLabels,
                            datasets: [{
                                data: orientationValues,
                                backgroundColor: palette
                            }]
                        },
                        options: doughnutOptions
                    });
                } else {
                    updateChart(charts.orientation, orientationLabels, orientationValues, palette);
                }
            };

            const refreshData = async () => {
                try {
                    const response = await fetch(@json(route('dashboard.metrics.data', [$project, 'devices'])), {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    if (!response.ok) {
                        return;
                    }
                    const payload = await response.json();
                    renderCharts(payload.data);
                } catch (error) {
                    console.error('Failed to refresh device metrics', error);
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
