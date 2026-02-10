<x-layouts.dashboard :title="'Acquisition - ' . $project->name" :header="'Acquisition'" :project="$project"
    :projects="$projects">

    <!-- Summary Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="stat-card">
            <div class="metric-label">Users in last hour</div>
            <div class="metric-large" data-stat="usersLastHour">{{ number_format($usersLastHour) }}</div>
        </div>
        <div class="stat-card">
            <div class="metric-label">Users today</div>
            <div class="metric-large" data-stat="usersToday">{{ number_format($usersToday) }}</div>
        </div>
        <div class="stat-card">
            <div class="metric-label">Total users</div>
            <div class="metric-large" data-stat="totalUsers">{{ number_format($totalUsers) }}</div>
        </div>
    </div>

    <!-- New Users Charts -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Hourly New Users -->
        <div class="chart-card">
            <div class="flex justify-between items-center mb-4">
                <h3 class="chart-title mb-0">Hourly New Users (last 24h)</h3>
                <span class="text-xs text-gray-400">Updated just now</span>
            </div>
            <div class="h-64">
                <canvas id="hourlyNewUsersChart"></canvas>
            </div>
        </div>

        <!-- Daily New Users -->
        <div class="chart-card">
            <div class="flex justify-between items-center mb-4">
                <h3 class="chart-title mb-0">Daily New Users (last 30 days)</h3>
                <span class="text-xs text-gray-400">Updated just now</span>
            </div>
            <div class="h-64">
                <canvas id="dailyNewUsersChart"></canvas>
            </div>
        </div>

        <!-- Weekly New Users -->
        <div class="chart-card">
            <div class="flex justify-between items-center mb-4">
                <h3 class="chart-title mb-0">Weekly New Users (last 3 months)</h3>
                <span class="text-xs text-gray-400">Updated just now</span>
            </div>
            <div class="h-64">
                <canvas id="weeklyNewUsersChart"></canvas>
            </div>
        </div>

        <!-- Monthly New Users -->
        <div class="chart-card">
            <div class="flex justify-between items-center mb-4">
                <h3 class="chart-title mb-0">Monthly New Users (past year)</h3>
                <span class="text-xs text-gray-400">Updated just now</span>
            </div>
            <div class="h-64">
                <canvas id="monthlyNewUsersChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Time Pattern Section -->
    <h2 class="text-lg font-semibold text-gray-900 mb-4">Acquisition Time Patterns</h2>
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- By Hour of Day -->
        <div class="chart-card">
            <div class="flex justify-between items-center mb-4">
                <h3 class="chart-title mb-0">New Users by Hour of Day</h3>
                <span class="text-xs text-gray-400">Updated just now</span>
            </div>
            <div class="h-64">
                <canvas id="usersByHourChart"></canvas>
            </div>
        </div>

        <!-- By Day of Week -->
        <div class="chart-card">
            <div class="flex justify-between items-center mb-4">
                <h3 class="chart-title mb-0">New Users by Day of Week</h3>
                <span class="text-xs text-gray-400">Updated just now</span>
            </div>
            <div class="h-64">
                <canvas id="usersByDayOfWeekChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Device & Platform Distribution -->
    <h2 class="text-lg font-semibold text-gray-900 mb-4">Device & Platform Distribution</h2>
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Device Distribution -->
        <div class="chart-card">
            <div class="flex justify-between items-center mb-4">
                <h3 class="chart-title mb-0">New Users by Device Type</h3>
                <span class="text-xs text-gray-400">Updated just now</span>
            </div>
            <div class="h-64">
                <canvas id="deviceDistributionChart"></canvas>
            </div>
        </div>

        <!-- OS Version Distribution -->
        <div class="chart-card">
            <div class="flex justify-between items-center mb-4">
                <h3 class="chart-title mb-0">New Users by OS Version</h3>
                <span class="text-xs text-gray-400">Updated just now</span>
            </div>
            <div class="h-64">
                <canvas id="osDistributionChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Geographic & App Distribution -->
    <h2 class="text-lg font-semibold text-gray-900 mb-4">Geographic & App Distribution</h2>
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Country Distribution -->
        <div class="chart-card">
            <div class="flex justify-between items-center mb-4">
                <h3 class="chart-title mb-0">New Users by Region</h3>
                <span class="text-xs text-gray-400">Updated just now</span>
            </div>
            <div class="h-64">
                <canvas id="countryDistributionChart"></canvas>
            </div>
        </div>

        <!-- App Version Distribution -->
        <div class="chart-card">
            <div class="flex justify-between items-center mb-4">
                <h3 class="chart-title mb-0">New Users by App Version</h3>
                <span class="text-xs text-gray-400">Updated just now</span>
            </div>
            <div class="h-64">
                <canvas id="appVersionDistributionChart"></canvas>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            const initialData = @json($metrics);

            const charts = {};

            const chartColors = {
                primary: 'rgb(59, 130, 246)',
                primaryLight: 'rgba(59, 130, 246, 0.1)',
                palette: [
                    'rgb(59, 130, 246)', 'rgb(99, 102, 241)', 'rgb(139, 92, 246)',
                    'rgb(168, 85, 247)', 'rgb(192, 132, 252)', 'rgb(216, 180, 254)',
                    'rgb(233, 213, 255)', 'rgb(245, 208, 254)', 'rgb(251, 207, 232)', 'rgb(252, 231, 243)'
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
                const hourlyLabels = formatHourlyLabels(data.hourlyNewUsers);
                const hourlyValues = Object.values(data.hourlyNewUsers);
                if (!charts.hourlyNewUsers) {
                    charts.hourlyNewUsers = new Chart(document.getElementById('hourlyNewUsersChart'), {
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
                    updateChart(charts.hourlyNewUsers, hourlyLabels, hourlyValues);
                }

                const dailyLabels = formatDailyLabels(data.dailyNewUsers);
                const dailyValues = Object.values(data.dailyNewUsers);
                if (!charts.dailyNewUsers) {
                    charts.dailyNewUsers = new Chart(document.getElementById('dailyNewUsersChart'), {
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
                    updateChart(charts.dailyNewUsers, dailyLabels, dailyValues);
                }

                const weeklyLabels = formatDailyLabels(data.weeklyNewUsers);
                const weeklyValues = Object.values(data.weeklyNewUsers);
                if (!charts.weeklyNewUsers) {
                    charts.weeklyNewUsers = new Chart(document.getElementById('weeklyNewUsersChart'), {
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
                    updateChart(charts.weeklyNewUsers, weeklyLabels, weeklyValues);
                }

                const monthlyLabels = formatMonthlyLabels(data.monthlyNewUsers);
                const monthlyValues = Object.values(data.monthlyNewUsers);
                if (!charts.monthlyNewUsers) {
                    charts.monthlyNewUsers = new Chart(document.getElementById('monthlyNewUsersChart'), {
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
                    updateChart(charts.monthlyNewUsers, monthlyLabels, monthlyValues);
                }

                const hourData = hourLabels.map((_, i) => data.usersByHour[i] || 0);
                if (!charts.usersByHour) {
                    charts.usersByHour = new Chart(document.getElementById('usersByHourChart'), {
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
                    updateChart(charts.usersByHour, hourLabels, hourData, chartColors.primary);
                }

                const dayData = [1, 2, 3, 4, 5, 6, 7].map(d => data.usersByDayOfWeek[d] || 0);
                if (!charts.usersByDayOfWeek) {
                    charts.usersByDayOfWeek = new Chart(document.getElementById('usersByDayOfWeekChart'), {
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
                    updateChart(charts.usersByDayOfWeek, dayLabels, dayData, chartColors.primary);
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

                const osLabels = Object.keys(data.osDistribution);
                const osValues = Object.values(data.osDistribution);
                if (!charts.osDistribution) {
                    charts.osDistribution = new Chart(document.getElementById('osDistributionChart'), {
                        type: 'doughnut',
                        data: {
                            labels: osLabels,
                            datasets: [{
                                data: osValues,
                                backgroundColor: chartColors.palette
                            }]
                        },
                        options: defaultDoughnutOptions
                    });
                } else {
                    updateChart(charts.osDistribution, osLabels, osValues, chartColors.palette);
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

                const appVersionLabels = Object.keys(data.appVersionDistribution);
                const appVersionValues = Object.values(data.appVersionDistribution);
                if (!charts.appVersionDistribution) {
                    charts.appVersionDistribution = new Chart(document.getElementById('appVersionDistributionChart'), {
                        type: 'doughnut',
                        data: {
                            labels: appVersionLabels,
                            datasets: [{
                                data: appVersionValues,
                                backgroundColor: chartColors.palette
                            }]
                        },
                        options: defaultDoughnutOptions
                    });
                } else {
                    updateChart(charts.appVersionDistribution, appVersionLabels, appVersionValues, chartColors.palette);
                }
            };

            const updateStats = (data) => {
                document.querySelectorAll('[data-stat]').forEach((element) => {
                    const key = element.dataset.stat;
                    if (!(key in data)) {
                        return;
                    }
                    element.textContent = Number(data[key]).toLocaleString();
                });
            };

            const refreshData = async () => {
                try {
                    const response = await fetch(@json(route('dashboard.acquisition.data', $project)), {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    if (!response.ok) {
                        return;
                    }
                    const payload = await response.json();
                    renderCharts(payload.data);
                    updateStats(payload.data);
                } catch (error) {
                    console.error('Failed to refresh acquisition metrics', error);
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
