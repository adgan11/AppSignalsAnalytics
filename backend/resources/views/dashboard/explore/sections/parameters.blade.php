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
            <h2 class="text-lg font-semibold text-gray-900">Parameters</h2>
            <p class="text-sm text-gray-500">Explore the most common payload keys across recent signals.</p>
        </div>
        <form method="GET" action="{{ route('dashboard.explore', [$project, 'parameters']) }}" class="flex flex-wrap items-center gap-2">
            <label class="text-xs font-medium text-gray-500 uppercase">Range</label>
            <select name="range" class="rounded-lg border-gray-300 text-sm focus:border-primary-500 focus:ring-primary-500">
                @foreach($ranges as $key => $label)
                    <option value="{{ $key }}" {{ $parameterRange === $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            <label class="text-xs font-medium text-gray-500 uppercase">Max signals</label>
            <input type="number" name="limit" value="{{ $parameterLimit }}" min="200" max="5000"
                class="w-28 rounded-lg border-gray-300 text-sm focus:border-primary-500 focus:ring-primary-500">
            <button type="submit" class="btn-secondary">Apply</button>
        </form>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100 text-sm text-gray-500">
        Scanned {{ number_format($parameterEventsScanned) }} signals in {{ $parameterRangeLabel }}.
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead>
                <tr>
                    <th class="table-header">Parameter</th>
                    <th class="table-header">Occurrences</th>
                    <th class="table-header">Sample Value</th>
                    <th class="table-header">Last Seen</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($parameters as $parameter)
                    <tr class="hover:bg-gray-50">
                        <td class="table-cell">
                            <span class="text-sm font-medium text-gray-900">{{ $parameter['name'] }}</span>
                        </td>
                        <td class="table-cell">{{ number_format($parameter['count']) }}</td>
                        <td class="table-cell text-xs font-mono text-gray-500">
                            {{ $parameter['sample'] ?? '—' }}
                        </td>
                        <td class="table-cell text-gray-500">
                            {{ optional($parameter['last_seen'])->format('M d, Y H:i') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-12 text-center text-gray-500">No parameters found yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
