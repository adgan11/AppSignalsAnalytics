<x-dashboard-layout>
    <x-slot name="header">Overview</x-slot>
    <x-slot name="title">Overview - {{ $project->name }}</x-slot>

    <!-- Time Range Selector -->
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-2">
            <span class="text-sm text-gray-500">Showing data for</span>
            <div x-data="{ open: false }" class="relative">
                <button @click="open = !open" class="btn-secondary">
                    Last {{ $days }} days
                    <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="open" @click.away="open = false" class="absolute left-0 mt-1 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-10 w-36">
                    <a href="?days=7" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 {{ $days == 7 ? 'bg-primary-50 text-primary-700' : '' }}">Last 7 days</a>
                    <a href="?days=30" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 {{ $days == 30 ? 'bg-primary-50 text-primary-700' : '' }}">Last 30 days</a>
                    <a href="?days=90" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 {{ $days == 90 ? 'bg-primary-50 text-primary-700' : '' }}">Last 90 days</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div x-data="{ 
            stats: { 
                total_events: {{ $stats['total_events'] }},
                unique_users: {{ $stats['unique_users'] }},
                unique_sessions: {{ $stats['unique_sessions'] }},
                crash_count: {{ $stats['crash_count'] }}
            } 
        }" 
        x-init="
            if (window.Echo) {
                window.Echo.private('project.{{ $project->id }}.events')
                    .listen('.NewEventLogged', (e) => {
                        console.log('Event received:', e);
                        stats.total_events += e.count;
                    });
            }
        "
        class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="stat-card">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Total Events</p>
                    <p class="text-3xl font-bold text-gray-900 mt-1" x-text="stats.total_events.toLocaleString()">{{ number_format($stats['total_events']) }}</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-blue-50 flex items-center justify-center">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="stat-card">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Unique Users</p>
                    <p class="text-3xl font-bold text-gray-900 mt-1" x-text="stats.unique_users.toLocaleString()">{{ number_format($stats['unique_users']) }}</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-green-50 flex items-center justify-center">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="stat-card">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Sessions</p>
                    <p class="text-3xl font-bold text-gray-900 mt-1" x-text="stats.unique_sessions.toLocaleString()">{{ number_format($stats['unique_sessions']) }}</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-purple-50 flex items-center justify-center">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="stat-card">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Crashes</p>
                    <p class="text-3xl font-bold mt-1" :class="stats.crash_count > 0 ? 'text-red-600' : 'text-gray-900'" x-text="stats.crash_count.toLocaleString()">{{ number_format($stats['crash_count']) }}</p>
                </div>
                <div class="w-12 h-12 rounded-xl flex items-center justify-center" :class="stats.crash_count > 0 ? 'bg-red-50' : 'bg-gray-50'">
                    <svg class="w-6 h-6" :class="stats.crash_count > 0 ? 'text-red-600' : 'text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <!-- Events Chart -->
        <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Events Over Time</h3>
            <div class="h-64">
                <canvas id="eventsChart"></canvas>
            </div>
        </div>

        <!-- Top Events -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Top Events</h3>
            <div class="space-y-3">
                @forelse($topEvents as $event)
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-700 truncate mr-2">{{ $event->event_name }}</span>
                    <span class="text-sm font-medium text-gray-900">{{ number_format($event->count) }}</span>
                </div>
                @empty
                <p class="text-sm text-gray-500">No events yet</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Recent Crashes -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-900">Recent Crashes</h3>
            <a href="{{ route('dashboard.crashes', $project) }}" class="text-sm text-primary-600 hover:text-primary-700">View all →</a>
        </div>
        <div class="divide-y divide-gray-100">
            @forelse($recentCrashes as $crash)
            <div class="px-6 py-4 hover:bg-gray-50 transition-colors">
                <div class="flex items-start justify-between">
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-red-600 truncate">{{ $crash->exception_type }}</p>
                        <p class="text-sm text-gray-500 truncate mt-1">{{ $crash->exception_message ?? 'No message' }}</p>
                        <div class="flex items-center gap-4 mt-2 text-xs text-gray-400">
                            <span>{{ $crash->device_model }}</span>
                            <span>v{{ $crash->app_version }}</span>
                        </div>
                    </div>
                    <span class="text-xs text-gray-400 whitespace-nowrap ml-4">{{ $crash->occurred_at->diffForHumans() }}</span>
                </div>
            </div>
            @empty
            <div class="px-6 py-12 text-center">
                <svg class="w-12 h-12 mx-auto text-green-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="mt-4 text-sm text-gray-500">No crashes reported. Great job! 🎉</p>
            </div>
            @endforelse
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const chartData = @json($chartData);
            const labels = Object.keys(chartData);
            const data = Object.values(chartData);

            new Chart(document.getElementById('eventsChart'), {
                type: 'line',
                data: {
                    labels: labels.map(d => new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric' })),
                    datasets: [{
                        label: 'Events',
                        data: data,
                        borderColor: 'rgb(14, 165, 233)',
                        backgroundColor: 'rgba(14, 165, 233, 0.1)',
                        fill: true,
                        tension: 0.4,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            ticks: { maxTicksLimit: 7 }
                        },
                        y: {
                            beginAtZero: true,
                            grid: { color: 'rgba(0,0,0,0.05)' },
                            ticks: {
                                callback: function(value) {
                                    return formatNumber(value);
                                }
                            }
                        }
                    }
                }
            });
        });
    </script>
</x-dashboard-layout>

