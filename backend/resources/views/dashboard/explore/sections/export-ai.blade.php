@php
    $ranges = [
        '24h' => 'Last 24 hours',
        '7d' => 'Last 7 days',
        '30d' => 'Last 30 days',
        '90d' => 'Last 90 days',
    ];
    $downloadUrl = route('dashboard.explore', [$project, 'export-ai']) . '?' . http_build_query([
        'range' => $exportRange,
        'limit' => $exportLimit,
        'download' => 1,
    ]);
@endphp

<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-lg font-semibold text-gray-900">Export for AI</h2>
            <p class="text-sm text-gray-500">Prepare a JSON payload you can share with analysis tools.</p>
        </div>
        <form method="GET" action="{{ route('dashboard.explore', [$project, 'export-ai']) }}" class="flex flex-wrap items-center gap-2">
            <label class="text-xs font-medium text-gray-500 uppercase">Range</label>
            <select name="range" class="rounded-lg border-gray-300 text-sm focus:border-primary-500 focus:ring-primary-500">
                @foreach($ranges as $key => $label)
                    <option value="{{ $key }}" {{ $exportRange === $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            <label class="text-xs font-medium text-gray-500 uppercase">Max signals</label>
            <input type="number" name="limit" value="{{ $exportLimit }}" min="50" max="1000"
                class="w-28 rounded-lg border-gray-300 text-sm focus:border-primary-500 focus:ring-primary-500">
            <button type="submit" class="btn-secondary">Update</button>
        </form>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-sm text-gray-500">{{ $exportRangeLabel }}</p>
            <p class="text-lg font-semibold text-gray-900">{{ number_format(count($exportPayload['events'])) }} signals ready</p>
        </div>
        <a href="{{ $downloadUrl }}" class="btn-primary">Download JSON</a>
    </div>

    <div class="mt-6">
        <label class="block text-xs font-medium text-gray-500 uppercase mb-2">Preview</label>
        <div class="bg-gray-50 rounded-lg border border-gray-200 p-4 text-xs font-mono overflow-auto max-h-96">
            <pre>{{ $exportPreview }}</pre>
        </div>
    </div>
</div>
