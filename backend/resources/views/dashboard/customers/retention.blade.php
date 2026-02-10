<x-layouts.dashboard :title="'Retention - ' . $project->name" :header="'Retention'" :project="$project"
    :projects="$projects">

    <div class="mb-6">
        <p class="text-gray-600">Analyze how often users return to your app and their engagement patterns over time.
            Returning users are those who have used your app on multiple days.</p>
    </div>

    <!-- Summary Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="stat-card">
            <div class="metric-label">Total Users</div>
            <div class="metric-large" data-stat="totalUsers">{{ number_format($totalUsers) }}</div>
        </div>
        <div class="stat-card">
            <div class="metric-label">Returning Users</div>
            <div class="metric-large" data-stat="totalReturning">{{ number_format($totalReturning) }}</div>
        </div>
        <div class="stat-card">
            <div class="metric-label">Retention Rate</div>
            <div class="metric-large text-green-600" data-stat="retentionRate">{{ $retentionRate }}%</div>
        </div>
    </div>

    <!-- Returning Users Charts -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Hourly Returning Users -->
        <div class="chart-card">
            <div class="flex justify-between items-center mb-4">
                <h3 class="chart-title mb-0">Hourly Returning Users (last 24h)</h3>
                <span class="text-xs text-gray-400">Updated just now</span>
            </div>
            <div class="h-64">
                <canvas id="hourlyReturningChart"></canvas>
            </div>
        </div>

        <!-- Daily Returning Users -->
        <div class="chart-card">
            <div class="flex justify-between items-center mb-4">
                <h3 class="chart-title mb-0">Daily Returning Users</h3>
                <span class="text-xs text-gray-400">Updated just now</span>
            </div>
            <div class="h-64">
                <canvas id="dailyReturningChart"></canvas>
            </div>
        </div>

        <!-- Weekly Returning Users -->
        <div class="chart-card">
            <div class="flex justify-between items-center mb-4">
                <h3 class="chart-title mb-0">Weekly Returning Users (last 3 months)</h3>
                <span class="text-xs text-gray-400">Updated just now</span>
            </div>
            <div class="h-64">
                <canvas id="weeklyReturningChart"></canvas>
            </div>
        </div>

        <!-- Monthly Returning Users -->
        <div class="chart-card">
            <div class="flex justify-between items-center mb-4">
                <h3 class="chart-title mb-0">Monthly Returning Users (past year)</h3>
                <span class="text-xs text-gray-400">Updated just now</span>
            </div>
            <div class="h-64">
                <canvas id="monthlyReturningChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Time Pattern Section -->
    <h2 class="text-lg font-semibold text-gray-900 mb-4">Time Patterns</h2>
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- By Hour of Day -->
        <div class="chart-card">
            <div class="flex justify-between items-center mb-4">
                <h3 class="chart-title mb-0">Returning Users by Hour of Day</h3>
                <span class="text-xs text-gray-400">Updated just now</span>
            </div>
            <div class="h-64">
                <canvas id="returnByHourChart"></canvas>
            </div>
        </div>

        <!-- By Day of Week -->
        <div class="chart-card">
            <div class="flex justify-between items-center mb-4">
                <h3 class="chart-title mb-0">Returning Users by Day of Week</h3>
                <span class="text-xs text-gray-400">Updated just now</span>
            </div>
            <div class="h-64">
                <canvas id="returnByDayOfWeekChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Device & Platform Distribution -->
    <h2 class="text-lg font-semibold text-gray-900 mb-4">Retention by Device & Platform</h2>
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Device Distribution -->
        <div class="chart-card">
            <div class="flex justify-between items-center mb-4">
                <h3 class="chart-title mb-0">Returning Users by Device Type</h3>
                <span class="text-xs text-gray-400">Updated just now</span>
            </div>
            <div class="h-64">
                <canvas id="deviceDistributionChart"></canvas>
            </div>
        </div>

        <!-- Country Distribution -->
        <div class="chart-card">
            <div class="flex justify-between items-center mb-4">
                <h3 class="chart-title mb-0">Returning Users by Region</h3>
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
                primary: 'rgb(168, 85, 247)',
                primaryLight: 'rgba(168, 85, 247, 0.1)',
                palette: [
                    'rgb(168, 85, 247)', 'rgb(192, 132, 252)', 'rgb(216, 180, 254)',
                    'rgb(233, 213, 255)', 'rgb(245, 208, 254)', 'rgb(251, 207, 232)',
                    'rgb(59, 130, 246)', 'rgb(99, 102, 241)', 'rgb(139, 92, 246)', 'rgb(34, 197, 94)'
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
                const hourlyLabels = formatHourlyLabels(data.hourlyReturning);
                const hourlyValues = Object.values(data.hourlyReturning);
                if (!charts.hourlyReturning) {
                    charts.hourlyReturning = new Chart(document.getElementById('hourlyReturningChart'), {
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
                    updateChart(charts.hourlyReturning, hourlyLabels, hourlyValues);
                }

                const dailyLabels = formatDailyLabels(data.dailyReturning);
                const dailyValues = Object.values(data.dailyReturning);
                if (!charts.dailyReturning) {
                    charts.dailyReturning = new Chart(document.getElementById('dailyReturningChart'), {
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
                    updateChart(charts.dailyReturning, dailyLabels, dailyValues);
                }

                const weeklyLabels = formatDailyLabels(data.weeklyReturning);
                const weeklyValues = Object.values(data.weeklyReturning);
                if (!charts.weeklyReturning) {
                    charts.weeklyReturning = new Chart(document.getElementById('weeklyReturningChart'), {
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
                    updateChart(charts.weeklyReturning, weeklyLabels, weeklyValues);
                }

                const monthlyLabels = formatMonthlyLabels(data.monthlyReturning);
                const monthlyValues = Object.values(data.monthlyReturning);
                if (!charts.monthlyReturning) {
                    charts.monthlyReturning = new Chart(document.getElementById('monthlyReturningChart'), {
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
                    updateChart(charts.monthlyReturning, monthlyLabels, monthlyValues);
                }

                const hourData = hourLabels.map((_, i) => data.returnByHour[i] || 0);
                if (!charts.returnByHour) {
                    charts.returnByHour = new Chart(document.getElementById('returnByHourChart'), {
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
                    updateChart(charts.returnByHour, hourLabels, hourData, chartColors.primary);
                }

                const dayData = [1, 2, 3, 4, 5, 6, 7].map(d => data.returnByDayOfWeek[d] || 0);
                if (!charts.returnByDayOfWeek) {
                    charts.returnByDayOfWeek = new Chart(document.getElementById('returnByDayOfWeekChart'), {
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
                    updateChart(charts.returnByDayOfWeek, dayLabels, dayData, chartColors.primary);
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
                    const response = await fetch(@json(route('dashboard.retention.data', $project)), {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    if (!response.ok) {
                        return;
                    }
                    const payload = await response.json();
                    renderCharts(payload.data);
                    updateStats(payload.data);
                } catch (error) {
                    console.error('Failed to refresh retention metrics', error);
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
