<x-layouts.dashboard :title="'Metrics - ' . $project->name" :header="'Metrics'" :project="$project" :projects="$projects">
    @php
        $tabs = [
            'devices' => 'Devices',
            'versions' => 'Versions',
            'errors' => 'Errors',
            'localization' => 'Localization',
            'accessibility' => 'Accessibility',
        ];
    @endphp

    <div class="flex flex-col lg:flex-row gap-6">
        <aside class="lg:w-56">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-3">
                <nav class="space-y-1">
                    @foreach($tabs as $key => $label)
                        <a href="{{ route('dashboard.metrics', [$project, $key]) }}"
                           class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium {{ $section === $key ? 'bg-primary-50 text-primary-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                            <span>{{ $label }}</span>
                        </a>
                    @endforeach
                </nav>
            </div>
        </aside>

        <div class="flex-1 space-y-6">
            @includeIf("dashboard.metrics.sections.{$section}", ['metricsData' => $metricsData, 'project' => $project])
        </div>
    </div>
</x-layouts.dashboard>
