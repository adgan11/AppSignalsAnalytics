<x-dashboard-layout>
    <x-slot name="header">Events</x-slot>
    <x-slot name="title">Events - {{ $project->name }}</x-slot>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-6">
        <form method="GET" action="{{ route('dashboard.events', $project) }}" class="flex flex-wrap gap-4">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs font-medium text-gray-500 uppercase mb-1">Event Name</label>
                <select name="event_name"
                    class="w-full rounded-lg border-gray-300 text-sm focus:border-primary-500 focus:ring-primary-500">
                    <option value="">All Events</option>
                    @foreach($eventNames as $name)
                        <option value="{{ $name }}" {{ request('event_name') === $name ? 'selected' : '' }}>{{ $name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs font-medium text-gray-500 uppercase mb-1">User ID</label>
                <input type="text" name="user_id" value="{{ request('user_id') }}" placeholder="Search by user ID"
                    class="w-full rounded-lg border-gray-300 text-sm focus:border-primary-500 focus:ring-primary-500">
            </div>
            <div class="flex-1 min-w-[150px]">
                <label class="block text-xs font-medium text-gray-500 uppercase mb-1">From</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}"
                    class="w-full rounded-lg border-gray-300 text-sm focus:border-primary-500 focus:ring-primary-500">
            </div>
            <div class="flex-1 min-w-[150px]">
                <label class="block text-xs font-medium text-gray-500 uppercase mb-1">To</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}"
                    class="w-full rounded-lg border-gray-300 text-sm focus:border-primary-500 focus:ring-primary-500">
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="btn-primary">Filter</button>
                <a href="{{ route('dashboard.events', $project) }}" class="btn-secondary">Reset</a>
            </div>
        </form>
    </div>

    <!-- Events Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto" x-data="{ 
                newEvents: [],
                formatDate(ts) {
                    const date = new Date(ts);
                    return {
                        date: date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }),
                        time: date.toLocaleTimeString('en-US', { hour12: false })
                    };
                }
            }" x-init="
                if (window.Echo) {
                    window.Echo.private('project.{{ $project->id }}.events')
                        .listen('.NewEventLogged', (e) => {
                            if (e.latestEvents && e.latestEvents.length > 0) {
                                // Add new events to the top of the list
                                e.latestEvents.reverse().forEach(evt => {
                                    // Parse properties if it's a JSON string
                                    let props = evt.properties;
                                    if (typeof props === 'string') {
                                        try { props = JSON.parse(props); } catch(e) {}
                                    }
                                    evt.properties = props;
                                    newEvents.unshift(evt);
                                });
                            }
                        });
                }
            ">
            <table class="min-w-full divide-y divide-gray-200">
                <thead>
                    <tr>
                        <th class="table-header">Event</th>
                        <th class="table-header">User ID</th>
                        <th class="table-header">Session</th>
                        <th class="table-header">Device</th>
                        <th class="table-header">Properties</th>
                        <th class="table-header">Time</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <!-- Live new events -->
                    <template x-for="event in newEvents" :key="event.event_id">
                        <tr class="bg-blue-50/50 transition-colors animate-pulse-once">
                            <td class="table-cell">
                                <span
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-md text-sm font-medium bg-primary-50 text-primary-700"
                                    x-text="event.event_name"></span>
                            </td>
                            <td class="table-cell">
                                <span class="text-gray-600" x-text="event.user_id || '—'"></span>
                            </td>
                            <td class="table-cell">
                                <span class="text-xs font-mono text-gray-500"
                                    x-text="event.session_id ? event.session_id.substring(0, 12) + '...' : ''"></span>
                            </td>
                            <td class="table-cell">
                                <div class="text-sm" x-text="event.device_model || '—'"></div>
                                <div class="text-xs text-gray-400" x-text="'v' + (event.app_version || '—')"></div>
                            </td>
                            <td class="table-cell">
                                <template x-if="event.properties">
                                    <div x-data="{ open: false }">
                                        <button @click="open = !open" class="text-xs text-primary-600 hover:underline"
                                            x-text="Object.keys(event.properties || {}).length + ' properties'"></button>
                                        <div x-show="open" class="mt-2 p-2 bg-gray-50 rounded text-xs font-mono">
                                            <pre x-text="JSON.stringify(event.properties, null, 2)"></pre>
                                        </div>
                                    </div>
                                </template>
                                <template x-if="!event.properties">
                                    <span class="text-gray-400">—</span>
                                </template>
                            </td>
                            <td class="table-cell text-gray-500">
                                <div x-text="formatDate(event.event_timestamp).date"></div>
                                <div class="text-xs text-gray-400" x-text="formatDate(event.event_timestamp).time">
                                </div>
                            </td>
                        </tr>
                    </template>

                    <!-- Server rendered events -->
                    @forelse($events as $event)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="table-cell">
                                <span
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-md text-sm font-medium bg-primary-50 text-primary-700">
                                    {{ $event->event_name }}
                                </span>
                            </td>
                            <td class="table-cell">
                                <span class="text-gray-600">{{ $event->user_id ?? '—' }}</span>
                            </td>
                            <td class="table-cell">
                                <span
                                    class="text-xs font-mono text-gray-500">{{ Str::limit($event->session_id, 12) }}</span>
                            </td>
                            <td class="table-cell">
                                <div class="text-sm">{{ $event->device_model ?? '—' }}</div>
                                <div class="text-xs text-gray-400">v{{ $event->app_version ?? '—' }}</div>
                            </td>
                            <td class="table-cell">
                                @if($event->properties)
                                    <div x-data="{ open: false }">
                                        <button @click="open = !open" class="text-xs text-primary-600 hover:underline">
                                            {{ count($event->properties) }} properties
                                        </button>
                                        <div x-show="open" x-cloak class="mt-2 p-2 bg-gray-50 rounded text-xs font-mono">
                                            <pre>{{ json_encode($event->properties, JSON_PRETTY_PRINT) }}</pre>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="table-cell text-gray-500">
                                <div>{{ $event->event_timestamp->format('M d, Y') }}</div>
                                <div class="text-xs text-gray-400">{{ $event->event_timestamp->format('H:i:s') }}</div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <svg class="w-12 h-12 mx-auto text-gray-200" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                </svg>
                                <p class="mt-4 text-gray-500">No events found</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($events->hasPages())
            <div class="px-6 py-4 border-t border-gray-100">
                {{ $events->withQueryString()->links() }}
            </div>
        @endif
    </div>
</x-dashboard-layout>