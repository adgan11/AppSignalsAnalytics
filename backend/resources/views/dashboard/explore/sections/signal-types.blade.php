@php
    $ranges = [
        '24h' => 'Last 24 hours',
        '7d' => 'Last 7 days',
        '30d' => 'Last 30 days',
        '90d' => 'Last 90 days',
    ];
@endphp

<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-lg font-semibold text-gray-900">Signal Types</h2>
            <p class="text-sm text-gray-500">Understand which signals are most active in {{ $rangeLabel }}.</p>
        </div>
        <form method="GET" action="{{ route('dashboard.explore', [$project, 'signal-types']) }}" class="flex flex-wrap items-center gap-2">
            <label class="text-xs font-medium text-gray-500 uppercase">Range</label>
            <select name="range" class="rounded-lg border-gray-300 text-sm focus:border-primary-500 focus:ring-primary-500">
                @foreach($ranges as $key => $label)
                    <option value="{{ $key }}" {{ $range === $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn-secondary">Apply</button>
        </form>
    </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
    <div class="stat-card">
        <p class="text-sm text-gray-500">Total Signals</p>
        <p class="text-3xl font-bold text-gray-900 mt-2">{{ number_format($totalSignals) }}</p>
    </div>
    <div class="stat-card">
        <p class="text-sm text-gray-500">Active Users</p>
        <p class="text-3xl font-bold text-gray-900 mt-2">{{ number_format($totalUsers) }}</p>
    </div>
    <div class="stat-card">
        <p class="text-sm text-gray-500">Signal Types</p>
        <p class="text-3xl font-bold text-gray-900 mt-2">{{ number_format($signalTypes->count()) }}</p>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead>
                <tr>
                    <th class="table-header">Signal Name</th>
                    <th class="table-header">Signals</th>
                    <th class="table-header">Users</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($signalTypes as $signal)
                    <tr class="hover:bg-gray-50">
                        <td class="table-cell">
                            <span class="text-sm font-medium text-gray-900">{{ $signal->event_name }}</span>
                        </td>
                        <td class="table-cell">{{ number_format($signal->signal_count) }}</td>
                        <td class="table-cell">{{ number_format($signal->user_count) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-6 py-12 text-center text-gray-500">No signals yet in this range.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
