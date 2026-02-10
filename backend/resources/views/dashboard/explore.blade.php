<x-layouts.dashboard :title="'Explore - ' . $project->name" :header="'Explore'" :project="$project" :projects="$projects">
    @php
        $sections = [
            'signal-types' => 'Signal Types',
            'parameters' => 'Parameters',
            'recent-signals' => 'Recent Signals',
            'playground' => 'Playground',
            'export-ai' => 'Export for AI',
        ];
    @endphp

    <div class="flex flex-col lg:flex-row gap-6">
        <aside class="lg:w-64">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-3">
                <nav class="space-y-1">
                    @foreach($sections as $key => $label)
                        <a href="{{ route('dashboard.explore', [$project, $key]) }}"
                           class="flex items-center justify-between px-3 py-2 rounded-lg text-sm font-medium {{ $section === $key ? 'bg-primary-50 text-primary-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                            <span>{{ $label }}</span>
                            @if($section === $key)
                                <span class="text-xs text-primary-600">Active</span>
                            @endif
                        </a>
                    @endforeach
                </nav>
            </div>
        </aside>

        <div class="flex-1 space-y-6">
            @includeIf("dashboard.explore.sections.{$section}")
        </div>
    </div>
</x-layouts.dashboard>
