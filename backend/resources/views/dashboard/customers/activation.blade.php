<x-layouts.dashboard :title="'Activation - ' . $project->name" :header="'Activation'" :project="$project"
    :projects="$projects">

    <div class="mb-6">
        <p class="text-gray-600">Monitor when users experience your app's core value for the first time. Users are
            considered "activated" when they perform meaningful actions beyond just opening the app.</p>
    </div>

    <!-- Summary Stats -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <div class="stat-card">
            <div class="metric-label">Total Activated Users</div>
            <div class="metric-large" data-stat="totalActivated">{{ number_format($totalActivated) }}</div>
        </div>
        <div class="stat-card">
            <div class="metric-label">Activation Rate</div>
            <div class="metric-large" data-stat="activationRate">{{ $activationRate }}%</div>
        </div>
    </div>

    <!-- Activated Users Charts -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Hourly Activated Users -->
        <div class="chart-card">
            <div class="flex justify-between items-center mb-4">
                <h3 class="chart-title mb-0">Hourly Activated Users (last 24h)</h3>
                <span class="text-xs text-gray-400">Updated just now</span>
            </div>
            <div class="h-64">
                <canvas id="hourlyActivatedChart"></canvas>
            </div>
        </div>

        <!-- Daily Activated Users -->
        <div class="chart-card">
            <div class="flex justify-between items-center mb-4">
                <h3 class="chart-title mb-0">Daily Activated Users</h3>
                <span class="text-xs text-gray-400">Updated just now</span>
            </div>
            <div class="h-64">
                <canvas id="dailyActivatedChart"></canvas>
            </div>
        </div>

        <!-- Weekly Activated Users -->
        <div class="chart-card">
            <div class="flex justify-between items-center mb-4">
                <h3 class="chart-title mb-0">Weekly Activated Users (last 3 months)</h3>
                <span class="text-xs text-gray-400">Updated just now</span>
            </div>
            <div class="h-64">
                <canvas id="weeklyActivatedChart"></canvas>
            </div>
        </div>

        <!-- Monthly Activated Users -->
        <div class="chart-card">
            <div class="flex justify-between items-center mb-4">
                <h3 class="chart-title mb-0">Monthly Activated Users (past year)</h3>
                <span class="text-xs text-gray-400">Updated just now</span>
            </div>
            <div class="h-64">
                <canvas id="monthlyActivatedChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Time Pattern Section -->
    <h2 class="text-lg font-semibold text-gray-900 mb-4">Activation Time Patterns</h2>
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- By Hour of Day -->
        <div class="chart-card">
            <div class="flex justify-between items-center mb-4">
                <h3 class="chart-title mb-0">Activation by Hour of Day</h3>
                <span class="text-xs text-gray-400">Updated just now</span>
            </div>
            <div class="h-64">
                <canvas id="activationByHourChart"></canvas>
            </div>
        </div>

        <!-- By Day of Week -->
        <div class="chart-card">
            <div class="flex justify-between items-center mb-4">
                <h3 class="chart-title mb-0">Activation by Day of Week</h3>
                <span class="text-xs text-gray-400">Updated just now</span>
            </div>
            <div class="h-64">
                <canvas id="activationByDayOfWeekChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Device & Platform Distribution -->
    <h2 class="text-lg font-semibold text-gray-900 mb-4">Activation by Device & Platform</h2>
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Device Distribution -->
        <div class="chart-card">
            <div class="flex justify-between items-center mb-4">
                <h3 class="chart-title mb-0">Activated Users by Device Type</h3>
                <span class="text-xs text-gray-400">Updated just now</span>
            </div>
            <div class="h-64">
                <canvas id="deviceDistributionChart"></canvas>
            </div>
        </div>

        <!-- Country Distribution -->
        <div class="chart-card">
            <div class="flex justify-between items-center mb-4">
                <h3 class="chart-title mb-0">Activated Users by Region</h3>
                <span class="text-xs text-gray-400">Updated just now</span>
            </div>
            <div class="h-64">
                <canvas id="countryDistributionChart"></canvas>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            const initialData = @json($metrics);

            const charts = {};

            const chartColors = {
                primary: 'rgb(34, 197, 94)',
                primaryLight: 'rgba(34, 197, 94, 0.1)',
                palette: [
                    'rgb(34, 197, 94)', 'rgb(74, 222, 128)', 'rgb(134, 239, 172)',
                    'rgb(187, 247, 208)', 'rgb(220, 252, 231)', 'rgb(240, 253, 244)',
                    'rgb(59, 130, 246)', 'rgb(99, 102, 241)', 'rgb(139, 92, 246)', 'rgb(168, 85, 247)'
                ]
            };

            const defaultLineOptions = {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' } },
                    x: { grid: { display: false } }
                }
            };

            const defaultBarOptions = {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' } },
                    x: { grid: { display: false } }
                }
            };

            const defaultDoughnutOptions = {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'right', labels: { usePointStyle: true, padding: 15 } }
                }
            };

            const hourLabels = Array.from({ length: 24 }, (_, i) => `${i.toString().padStart(2, '0')}:00`);
            const dayLabels = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

            const formatHourlyLabels = (data) => Object.keys(data).map(h => {
                const date = new Date(h);
                return date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
            });

            const formatDailyLabels = (data) => Object.keys(data).map(d => {
                const date = new Date(d);
                return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
            });

            const formatMonthlyLabels = (data) => Object.keys(data).map(m => {
                const [year, month] = m.split('-');
                return new Date(year, month - 1).toLocaleDateString('en-US', { month: 'short', year: '2-digit' });
            });

            const updateChart = (chart, labels, data, colors) => {
                chart.data.labels = labels;
                chart.data.datasets[0].data = data;
                if (colors) {
                    chart.data.datasets[0].backgroundColor = colors;
                }
                chart.update();
            };

            const renderCharts = (data) => {
                const hourlyLabels = formatHourlyLabels(data.hourlyActivated);
                const hourlyValues = Object.values(data.hourlyActivated);
                if (!charts.hourlyActivated) {
                    charts.hourlyActivated = new Chart(document.getElementById('hourlyActivatedChart'), {
                        type: 'line',
                        data: {
                            labels: hourlyLabels,
                            datasets: [{
                                data: hourlyValues,
                                borderColor: chartColors.primary,
                                backgroundColor: chartColors.primaryLight,
                                fill: true,
                                tension: 0.3
                            }]
                        },
                        options: defaultLineOptions
                    });
                } else {
                    updateChart(charts.hourlyActivated, hourlyLabels, hourlyValues);
                }

                const dailyLabels = formatDailyLabels(data.dailyActivated);
                const dailyValues = Object.values(data.dailyActivated);
                if (!charts.dailyActivated) {
                    charts.dailyActivated = new Chart(document.getElementById('dailyActivatedChart'), {
                        type: 'line',
                        data: {
                            labels: dailyLabels,
                            datasets: [{
                                data: dailyValues,
                                borderColor: chartColors.primary,
                                backgroundColor: chartColors.primaryLight,
                                fill: true,
                                tension: 0.3
                            }]
                        },
                        options: defaultLineOptions
                    });
                } else {
                    updateChart(charts.dailyActivated, dailyLabels, dailyValues);
                }

                const weeklyLabels = formatDailyLabels(data.weeklyActivated);
                const weeklyValues = Object.values(data.weeklyActivated);
                if (!charts.weeklyActivated) {
                    charts.weeklyActivated = new Chart(document.getElementById('weeklyActivatedChart'), {
                        type: 'line',
                        data: {
                            labels: weeklyLabels,
                            datasets: [{
                                data: weeklyValues,
                                borderColor: chartColors.primary,
                                backgroundColor: chartColors.primaryLight,
                                fill: true,
                                tension: 0.3
                            }]
                        },
                        options: defaultLineOptions
                    });
                } else {
                    updateChart(charts.weeklyActivated, weeklyLabels, weeklyValues);
                }

                const monthlyLabels = formatMonthlyLabels(data.monthlyActivated);
                const monthlyValues = Object.values(data.monthlyActivated);
                if (!charts.monthlyActivated) {
                    charts.monthlyActivated = new Chart(document.getElementById('monthlyActivatedChart'), {
                        type: 'line',
                        data: {
                            labels: monthlyLabels,
                            datasets: [{
                                data: monthlyValues,
                                borderColor: chartColors.primary,
                                backgroundColor: chartColors.primaryLight,
                                fill: true,
                                tension: 0.3
                            }]
                        },
                        options: defaultLineOptions
                    });
                } else {
                    updateChart(charts.monthlyActivated, monthlyLabels, monthlyValues);
                }

                const hourData = hourLabels.map((_, i) => data.activationByHour[i] || 0);
                if (!charts.activationByHour) {
                    charts.activationByHour = new Chart(document.getElementById('activationByHourChart'), {
                        type: 'bar',
                        data: {
                            labels: hourLabels,
                            datasets: [{
                                data: hourData,
                                backgroundColor: chartColors.primary,
                                borderRadius: 4
                            }]
                        },
                        options: defaultBarOptions
                    });
                } else {
                    updateChart(charts.activationByHour, hourLabels, hourData, chartColors.primary);
                }

                const dayData = [1, 2, 3, 4, 5, 6, 7].map(d => data.activationByDayOfWeek[d] || 0);
                if (!charts.activationByDayOfWeek) {
                    charts.activationByDayOfWeek = new Chart(document.getElementById('activationByDayOfWeekChart'), {
                        type: 'bar',
                        data: {
                            labels: dayLabels,
                            datasets: [{
                                data: dayData,
                                backgroundColor: chartColors.primary,
                                borderRadius: 4
                            }]
                        },
                        options: defaultBarOptions
                    });
                } else {
                    updateChart(charts.activationByDayOfWeek, dayLabels, dayData, chartColors.primary);
                }

                const deviceLabels = Object.keys(data.deviceDistribution);
                const deviceValues = Object.values(data.deviceDistribution);
                if (!charts.deviceDistribution) {
                    charts.deviceDistribution = new Chart(document.getElementById('deviceDistributionChart'), {
                        type: 'doughnut',
                        data: {
                            labels: deviceLabels,
                            datasets: [{
                                data: deviceValues,
                                backgroundColor: chartColors.palette
                            }]
                        },
                        options: defaultDoughnutOptions
                    });
                } else {
                    updateChart(charts.deviceDistribution, deviceLabels, deviceValues, chartColors.palette);
                }

                const countryLabels = Object.keys(data.countryDistribution);
                const countryValues = Object.values(data.countryDistribution);
                if (!charts.countryDistribution) {
                    charts.countryDistribution = new Chart(document.getElementById('countryDistributionChart'), {
                        type: 'doughnut',
                        data: {
                            labels: countryLabels,
                            datasets: [{
                                data: countryValues,
                                backgroundColor: chartColors.palette
                            }]
                        },
                        options: defaultDoughnutOptions
                    });
                } else {
                    updateChart(charts.countryDistribution, countryLabels, countryValues, chartColors.palette);
                }
            };

            const updateStats = (data) => {
                document.querySelectorAll('[data-stat]').forEach((element) => {
                    const key = element.dataset.stat;
                    if (!(key in data)) {
                        return;
                    }
                    if (key.endsWith('Rate')) {
                        element.textContent = `${data[key]}%`;
                    } else {
                        element.textContent = Number(data[key]).toLocaleString();
                    }
                });
            };

            const refreshData = async () => {
                try {
                    const response = await fetch(@json(route('dashboard.activation.data', $project)), {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    if (!response.ok) {
                        return;
                    }
                    const payload = await response.json();
                    renderCharts(payload.data);
                    updateStats(payload.data);
                } catch (error) {
                    console.error('Failed to refresh activation metrics', error);
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
            updateStats(initialData);

            if (window.Echo) {
                window.Echo.private('project.{{ $project->id }}.events')
                    .listen('.NewEventLogged', scheduleRefresh);
            }

            setInterval(refreshData, 60000);
        </script>
    @endpush

</x-layouts.dashboard>
