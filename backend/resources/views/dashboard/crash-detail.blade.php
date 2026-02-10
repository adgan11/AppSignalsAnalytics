<x-dashboard-layout>
    <x-slot name="header">
        <a href="{{ route('dashboard.crashes', $project) }}" class="text-gray-400 hover:text-gray-600">Crashes</a>
        <span class="mx-2 text-gray-300">/</span>
        <span>{{ $representative->exception_type }}</span>
    </x-slot>
    <x-slot name="title">Crash Detail - {{ $project->name }}</x-slot>

    <!-- Crash Summary -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
        <div class="flex items-start justify-between">
            <div>
                <h2 class="text-xl font-bold text-red-600">{{ $representative->exception_type }}</h2>
                <p class="mt-1 text-gray-600">{{ $representative->exception_message ?? 'No message' }}</p>
            </div>
            <span class="badge badge-red text-lg px-3 py-1">{{ $crashes->count() }} crashes</span>
        </div>

        <div class="mt-6 grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div>
                <p class="text-xs text-gray-500 uppercase">Affected Devices</p>
                <p class="text-lg font-semibold text-gray-900">{{ $affectedDevices }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500 uppercase">First Seen</p>
                <p class="text-lg font-semibold text-gray-900">{{ $crashes->min('occurred_at')->format('M d, Y') }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500 uppercase">Last Seen</p>
                <p class="text-lg font-semibold text-gray-900">{{ $representative->occurred_at->diffForHumans() }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500 uppercase">Affected Versions</p>
                <p class="text-lg font-semibold text-gray-900">{{ $affectedVersions->join(', ') }}</p>
            </div>
        </div>
    </div>

    <!-- Stack Trace -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-6">
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="text-lg font-semibold text-gray-900">Stack Trace</h3>
        </div>
        <div class="p-6">
            <pre
                class="bg-gray-900 text-gray-100 p-4 rounded-lg overflow-x-auto text-sm font-mono leading-relaxed">{{ $representative->is_symbolicated ? $representative->symbolicated_trace : $representative->stack_trace }}</pre>
        </div>
    </div>

    <!-- Recent Occurrences -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="text-lg font-semibold text-gray-900">Recent Occurrences</h3>
        </div>
        <div class="divide-y divide-gray-100">
            @foreach($crashes->take(10) as $crash)
                <div class="px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="text-sm font-medium text-gray-900">{{ $crash->device_model }}</span>
                            <span class="mx-2 text-gray-300">•</span>
                            <span class="text-sm text-gray-500">{{ $crash->os_version }}</span>
                            <span class="mx-2 text-gray-300">•</span>
                            <span class="text-sm text-gray-500">v{{ $crash->app_version }}</span>
                        </div>
                        <span class="text-sm text-gray-400">{{ $crash->occurred_at->format('M d, H:i') }}</span>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</x-dashboard-layout>