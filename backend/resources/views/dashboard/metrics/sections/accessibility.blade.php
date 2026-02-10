<div>
    <h2 class="text-xl font-semibold text-gray-900">Accessibility</h2>
    <p class="text-sm text-gray-600 mt-1">Accessibility settings reported in the last 30 days.</p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="chart-card">
        <div class="flex justify-between items-center mb-4">
            <h3 class="chart-title mb-0">Preferred Content Size ("Dynamic Type")</h3>
            <span class="text-xs text-gray-400">Updated just now</span>
        </div>
        <div class="h-64">
            <canvas id="preferredContentSizeChart"></canvas>
        </div>
    </div>

    <div class="chart-card">
        <div class="flex justify-between items-center mb-4">
            <h3 class="chart-title mb-0">Bold Text Usage</h3>
            <span class="text-xs text-gray-400">Updated just now</span>
        </div>
        <div class="h-64">
            <canvas id="boldTextChart"></canvas>
        </div>
    </div>

    <div class="chart-card">
        <div class="flex justify-between items-center mb-4">
            <h3 class="chart-title mb-0">Reduce Motion Usage</h3>
            <span class="text-xs text-gray-400">Updated just now</span>
        </div>
        <div class="h-64">
            <canvas id="reduceMotionChart"></canvas>
        </div>
    </div>

    <div class="chart-card">
        <div class="flex justify-between items-center mb-4">
            <h3 class="chart-title mb-0">Reduce Transparency Usage</h3>
            <span class="text-xs text-gray-400">Updated just now</span>
        </div>
        <div class="h-64">
            <canvas id="reduceTransparencyChart"></canvas>
        </div>
    </div>

    <div class="chart-card">
        <div class="flex justify-between items-center mb-4">
            <h3 class="chart-title mb-0">Darker System Colors Usage</h3>
            <span class="text-xs text-gray-400">Updated just now</span>
        </div>
        <div class="h-64">
            <canvas id="darkerSystemColorsChart"></canvas>
        </div>
    </div>

    <div class="chart-card">
        <div class="flex justify-between items-center mb-4">
            <h3 class="chart-title mb-0">Differentiate Without Color Usage</h3>
            <span class="text-xs text-gray-400">Updated just now</span>
        </div>
        <div class="h-64">
            <canvas id="differentiateWithoutColorChart"></canvas>
        </div>
    </div>

    <div class="chart-card lg:col-span-1">
        <div class="flex justify-between items-center mb-4">
            <h3 class="chart-title mb-0">Inverted Colors Usage</h3>
            <span class="text-xs text-gray-400">Updated just now</span>
        </div>
        <div class="h-64">
            <canvas id="invertColorsChart"></canvas>
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
                const contentLabels = Object.keys(data.preferredContentSize || {});
                const contentValues = Object.values(data.preferredContentSize || {});
                if (!charts.preferredContentSize) {
                    charts.preferredContentSize = new Chart(document.getElementById('preferredContentSizeChart'), {
                        type: 'doughnut',
                        data: {
                            labels: contentLabels,
                            datasets: [{
                                data: contentValues,
                                backgroundColor: palette
                            }]
                        },
                        options: doughnutOptions
                    });
                } else {
                    updateChart(charts.preferredContentSize, contentLabels, contentValues, palette);
                }

                const boldLabels = Object.keys(data.boldText || {});
                const boldValues = Object.values(data.boldText || {});
                if (!charts.boldText) {
                    charts.boldText = new Chart(document.getElementById('boldTextChart'), {
                        type: 'doughnut',
                        data: {
                            labels: boldLabels,
                            datasets: [{
                                data: boldValues,
                                backgroundColor: palette
                            }]
                        },
                        options: doughnutOptions
                    });
                } else {
                    updateChart(charts.boldText, boldLabels, boldValues, palette);
                }

                const motionLabels = Object.keys(data.reduceMotion || {});
                const motionValues = Object.values(data.reduceMotion || {});
                if (!charts.reduceMotion) {
                    charts.reduceMotion = new Chart(document.getElementById('reduceMotionChart'), {
                        type: 'doughnut',
                        data: {
                            labels: motionLabels,
                            datasets: [{
                                data: motionValues,
                                backgroundColor: palette
                            }]
                        },
                        options: doughnutOptions
                    });
                } else {
                    updateChart(charts.reduceMotion, motionLabels, motionValues, palette);
                }

                const transparencyLabels = Object.keys(data.reduceTransparency || {});
                const transparencyValues = Object.values(data.reduceTransparency || {});
                if (!charts.reduceTransparency) {
                    charts.reduceTransparency = new Chart(document.getElementById('reduceTransparencyChart'), {
                        type: 'doughnut',
                        data: {
                            labels: transparencyLabels,
                            datasets: [{
                                data: transparencyValues,
                                backgroundColor: palette
                            }]
                        },
                        options: doughnutOptions
                    });
                } else {
                    updateChart(charts.reduceTransparency, transparencyLabels, transparencyValues, palette);
                }

                const darkerLabels = Object.keys(data.darkerSystemColors || {});
                const darkerValues = Object.values(data.darkerSystemColors || {});
                if (!charts.darkerSystemColors) {
                    charts.darkerSystemColors = new Chart(document.getElementById('darkerSystemColorsChart'), {
                        type: 'doughnut',
                        data: {
                            labels: darkerLabels,
                            datasets: [{
                                data: darkerValues,
                                backgroundColor: palette
                            }]
                        },
                        options: doughnutOptions
                    });
                } else {
                    updateChart(charts.darkerSystemColors, darkerLabels, darkerValues, palette);
                }

                const diffLabels = Object.keys(data.differentiateWithoutColor || {});
                const diffValues = Object.values(data.differentiateWithoutColor || {});
                if (!charts.differentiateWithoutColor) {
                    charts.differentiateWithoutColor = new Chart(document.getElementById('differentiateWithoutColorChart'), {
                        type: 'doughnut',
                        data: {
                            labels: diffLabels,
                            datasets: [{
                                data: diffValues,
                                backgroundColor: palette
                            }]
                        },
                        options: doughnutOptions
                    });
                } else {
                    updateChart(charts.differentiateWithoutColor, diffLabels, diffValues, palette);
                }

                const invertLabels = Object.keys(data.invertColors || {});
                const invertValues = Object.values(data.invertColors || {});
                if (!charts.invertColors) {
                    charts.invertColors = new Chart(document.getElementById('invertColorsChart'), {
                        type: 'doughnut',
                        data: {
                            labels: invertLabels,
                            datasets: [{
                                data: invertValues,
                                backgroundColor: palette
                            }]
                        },
                        options: doughnutOptions
                    });
                } else {
                    updateChart(charts.invertColors, invertLabels, invertValues, palette);
                }
            };

            const refreshData = async () => {
                try {
                    const response = await fetch(@json(route('dashboard.metrics.data', [$project, 'accessibility'])), {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    if (!response.ok) {
                        return;
                    }
                    const payload = await response.json();
                    renderCharts(payload.data);
                } catch (error) {
                    console.error('Failed to refresh accessibility metrics', error);
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
