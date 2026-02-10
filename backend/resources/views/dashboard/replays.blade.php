<x-dashboard-layout>
    <x-slot name="header">Session Replays</x-slot>
    <x-slot name="title">Session Replays - {{ $project->name }}</x-slot>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-6">
        <form method="GET" action="{{ route('dashboard.replays', $project) }}" class="flex flex-wrap gap-4 items-end">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs font-medium text-gray-500 uppercase mb-1">User ID</label>
                <input type="text" name="user_id" value="{{ request('user_id') }}" placeholder="Search by user ID"
                    class="w-full rounded-lg border-gray-300 text-sm focus:border-primary-500 focus:ring-primary-500">
            </div>
            <div class="flex gap-2">
                <button type="submit" class="btn-primary">Filter</button>
                <a href="{{ route('dashboard.replays', $project) }}" class="btn-secondary">Reset</a>
            </div>
        </form>
    </div>

    <!-- Replays Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse($replays as $replay)
            <a href="{{ route('dashboard.replay-player', [$project, $replay->session_id]) }}"
                class="block bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition-all group">
                <!-- Preview Placeholder -->
                <div class="aspect-[16/10] bg-gradient-to-br from-gray-100 to-gray-200 flex items-center justify-center">
                    <div class="text-center">
                        <div
                            class="w-16 h-16 mx-auto rounded-full bg-white/50 flex items-center justify-center group-hover:bg-primary-500 transition-colors">
                            <svg class="w-8 h-8 text-gray-400 group-hover:text-white transition-colors" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <p class="mt-2 text-sm text-gray-500">{{ $replay->frames_count }} frames</p>
                    </div>
                </div>
                <div class="p-4">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-900 truncate">
                            {{ $replay->user_id ?? 'Anonymous' }}
                        </span>
                        @if($replay->duration_seconds)
                            <span class="text-xs text-gray-500">{{ gmdate('i:s', $replay->duration_seconds) }}</span>
                        @endif
                    </div>
                    <div class="mt-1 flex items-center gap-2 text-xs text-gray-400">
                        <span>{{ $replay->started_at->format('M d, Y H:i') }}</span>
                    </div>
                </div>
            </a>
        @empty
            <div class="col-span-full bg-white rounded-xl shadow-sm border border-gray-100 p-12 text-center">
                <svg class="w-16 h-16 mx-auto text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <h3 class="mt-4 text-lg font-medium text-gray-900">No session replays yet</h3>
                <p class="mt-2 text-gray-500">Session replays will appear here once users start your app with replay
                    enabled.</p>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($replays->hasPages())
        <div class="mt-6">
            {{ $replays->withQueryString()->links() }}
        </div>
    @endif
</x-dashboard-layout>