@php
    $queryTypes = [
        'timeseries' => 'Time Series',
        'events' => 'Events',
    ];
    $granularities = [
        'hour' => 'Hour',
        'day' => 'Day',
        'month' => 'Month',
    ];
    $ranges = [
        '24h' => 'Last 24 hours',
        '7d' => 'Last 7 days',
        '30d' => 'Last 30 days',
        '90d' => 'Last 90 days',
    ];
@endphp

<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6" x-data="{ mode: '{{ $playgroundMode }}' }">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-lg font-semibold text-gray-900">Playground</h2>
            <p class="text-sm text-gray-500">Build a custom query to explore your signal data.</p>
        </div>
        <div class="inline-flex rounded-lg border border-gray-200 bg-gray-50 p-1">
            <button type="button" @click="mode = 'visual'"
                :class="mode === 'visual' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500'"
                class="px-3 py-1.5 text-sm font-medium rounded-md transition">Visual Editor</button>
            <button type="button" @click="mode = 'json'"
                :class="mode === 'json' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500'"
                class="px-3 py-1.5 text-sm font-medium rounded-md transition">JSON Editor</button>
        </div>
    </div>

    <form method="GET" action="{{ route('dashboard.explore', [$project, 'playground']) }}" class="mt-6 space-y-6">
        <input type="hidden" name="mode" x-model="mode">

        <div x-show="mode === 'visual'" x-cloak>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-500 uppercase mb-1">Query Type</label>
                    <select name="query_type" class="w-full rounded-lg border-gray-300 text-sm focus:border-primary-500 focus:ring-primary-500">
                        @foreach($queryTypes as $key => $label)
                            <option value="{{ $key }}" {{ $playgroundPayload['queryType'] === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 uppercase mb-1">Granularity</label>
                    <select name="granularity" class="w-full rounded-lg border-gray-300 text-sm focus:border-primary-500 focus:ring-primary-500">
                        @foreach($granularities as $key => $label)
                            <option value="{{ $key }}" {{ $playgroundPayload['granularity'] === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 uppercase mb-1">Range</label>
                    <select name="range" class="w-full rounded-lg border-gray-300 text-sm focus:border-primary-500 focus:ring-primary-500">
                        @foreach($ranges as $key => $label)
                            <option value="{{ $key }}" {{ $playgroundPayload['range'] === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 uppercase mb-1">Signal Name</label>
                    <select name="event_name" class="w-full rounded-lg border-gray-300 text-sm focus:border-primary-500 focus:ring-primary-500">
                        <option value="">All Signals</option>
                        @foreach($eventNames as $name)
                            <option value="{{ $name }}" {{ $playgroundPayload['eventName'] === $name ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 uppercase mb-1">User ID</label>
                    <input type="text" name="user_id" value="{{ $playgroundPayload['userId'] ?? '' }}" placeholder="Optional"
                        class="w-full rounded-lg border-gray-300 text-sm focus:border-primary-500 focus:ring-primary-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 uppercase mb-1">Limit</label>
                    <input type="number" name="limit" value="{{ $playgroundPayload['limit'] }}" min="10" max="500"
                        class="w-full rounded-lg border-gray-300 text-sm focus:border-primary-500 focus:ring-primary-500">
                </div>
            </div>
        </div>

        <div x-show="mode === 'json'" x-cloak>
            <label class="block text-xs font-medium text-gray-500 uppercase mb-2">Query JSON</label>
            <textarea name="payload" rows="10"
                class="w-full rounded-lg border-gray-300 text-xs font-mono focus:border-primary-500 focus:ring-primary-500">{{ $playgroundJson }}</textarea>
            @if($playgroundError)
                <p class="mt-2 text-sm text-red-600">{{ $playgroundError }}</p>
            @endif
        </div>

        <div class="flex items-center gap-3">
            <button type="submit" class="btn-primary">Run Query</button>
            <span class="text-xs text-gray-400">{{ $playgroundRangeLabel }}</span>
        </div>
    </form>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100">
        <h3 class="text-base font-semibold text-gray-900">Query Result</h3>
    </div>

    @if($playgroundPayload['queryType'] === 'timeseries')
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead>
                    <tr>
                        <th class="table-header">Bucket</th>
                        <th class="table-header">Signals</th>
                        <th class="table-header">Users</th>
                        <th class="table-header">Signals per User</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($playgroundResults as $row)
                        @php
                            $ratio = $row->user_count > 0 ? round($row->signal_count / $row->user_count, 2) : 0;
                        @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="table-cell">{{ $row->bucket }}</td>
                            <td class="table-cell">{{ number_format($row->signal_count) }}</td>
                            <td class="table-cell">{{ number_format($row->user_count) }}</td>
                            <td class="table-cell">{{ $ratio }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-12 text-center text-gray-500">No results for this query.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead>
                    <tr>
                        <th class="table-header">Signal</th>
                        <th class="table-header">User ID</th>
                        <th class="table-header">Session</th>
                        <th class="table-header">Device</th>
                        <th class="table-header">Time</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($playgroundResults as $event)
                        <tr class="hover:bg-gray-50">
                            <td class="table-cell">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-sm font-medium bg-primary-50 text-primary-700">
                                    {{ $event->event_name }}
                                </span>
                            </td>
                            <td class="table-cell">{{ $event->user_id ?? '—' }}</td>
                            <td class="table-cell">
                                <span class="text-xs font-mono text-gray-500">{{ Str::limit($event->session_id, 12) }}</span>
                            </td>
                            <td class="table-cell">
                                <div class="text-sm">{{ $event->device_model ?? '—' }}</div>
                                <div class="text-xs text-gray-400">v{{ $event->app_version ?? '—' }}</div>
                            </td>
                            <td class="table-cell text-gray-500">
                                <div>{{ $event->event_timestamp->format('M d, Y') }}</div>
                                <div class="text-xs text-gray-400">{{ $event->event_timestamp->format('H:i:s') }}</div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-gray-500">No results for this query.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif
</div>
