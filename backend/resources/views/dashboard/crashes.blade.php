<x-dashboard-layout>
    <x-slot name="header">Crashes</x-slot>
    <x-slot name="title">Crashes - {{ $project->name }}</x-slot>

    <!-- Crash Groups -->
    <div class="space-y-4" x-data="{ 
            newGroups: [],
            updateExisting(crash) {
                const el = document.getElementById('group-' + crash.crash_group_hash);
                if (el) {
                    // Flash effect
                    el.classList.add('ring-2', 'ring-red-500', 'ring-offset-2');
                    setTimeout(() => el.classList.remove('ring-2', 'ring-red-500', 'ring-offset-2'), 1000);
                    
                    // Update count
                    const countEl = el.querySelector('.crash-count');
                    if (countEl) {
                        let count = parseInt(countEl.innerText);
                        countEl.innerText = (count + 1) + ' crashes';
                    }
                    
                    // Update time
                    const timeEl = el.querySelector('.crash-time');
                    if (timeEl) timeEl.innerText = 'Just now';
                    
                    return true;
                }
                return false;
            }
        }" x-init="
            if (window.Echo) {
                window.Echo.private('project.{{ $project->id }}.events')
                    .listen('.NewCrashLogged', (e) => {
                        if (!updateExisting(e.crash)) {
                            // Add new group if not found in current page
                            newGroups.unshift({
                                crash: e.crash,
                                count: 1,
                                devices: 1
                            });
                        }
                    });
            }
        ">

        <!-- Live new groups -->
        <template x-for="group in newGroups" :key="group.crash.crash_group_hash">
            <div
                class="bg-red-50 rounded-xl shadow-sm border border-red-100 overflow-hidden hover:shadow-md transition-shadow animate-pulse-once">
                <a :href="'/dashboard/projects/{{ $project->id }}/crashes/' + group.crash.crash_group_hash"
                    class="block p-6">
                    <div class="flex items-start justify-between">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-3">
                                <span class="badge badge-red">1 crash</span>
                                <span class="text-sm text-gray-500">1 device affected</span>
                                <span
                                    class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">New</span>
                            </div>
                            <h3 class="mt-2 text-lg font-semibold text-red-600 truncate"
                                x-text="group.crash.exception_type"></h3>
                            <p class="mt-1 text-gray-600 truncate"
                                x-text="group.crash.exception_message || 'No message'"></p>
                        </div>
                        <div class="text-right ml-6 flex-shrink-0">
                            <p class="text-xs text-gray-400">Last seen</p>
                            <p class="text-sm text-gray-600">Just now</p>
                        </div>
                    </div>
                </a>
            </div>
        </template>

        @forelse($crashGroups as $group)
            <div id="group-{{ $group->crash_group_hash }}"
                class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition-shadow transition-all duration-500">
                <a href="{{ route('dashboard.crash-detail', [$project, $group->crash_group_hash]) }}" class="block p-6">
                    <div class="flex items-start justify-between">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-3">
                                <span class="badge badge-red crash-count">{{ $group->crash_count }}
                                    {{ Str::plural('crash', $group->crash_count) }}</span>
                                <span class="text-sm text-gray-500">{{ $group->affected_devices }}
                                    {{ Str::plural('device', $group->affected_devices) }} affected</span>
                            </div>
                            <h3 class="mt-2 text-lg font-semibold text-red-600 truncate">{{ $group->exception_type }}</h3>
                            <p class="mt-1 text-gray-600 truncate">{{ $group->exception_message ?? 'No message' }}</p>
                        </div>
                        <div class="text-right ml-6 flex-shrink-0">
                            <p class="text-xs text-gray-400">Last seen</p>
                            <p class="text-sm text-gray-600 crash-time">
                                {{ \Carbon\Carbon::parse($group->last_occurred)->diffForHumans() }}
                            </p>
                        </div>
                    </div>
                    <div class="mt-4 flex items-center text-xs text-gray-400">
                        <span>First seen: {{ \Carbon\Carbon::parse($group->first_occurred)->format('M d, Y H:i') }}</span>
                    </div>
                </a>
            </div>
        @empty
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-12 text-center"
                x-show="newGroups.length === 0">
                <svg class="w-16 h-16 mx-auto text-green-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <h3 class="mt-4 text-lg font-medium text-gray-900">No crashes yet!</h3>
                <p class="mt-2 text-gray-500">Your app is running smoothly. Keep up the great work! 🎉</p>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($crashGroups->hasPages())
        <div class="mt-6">
            {{ $crashGroups->links() }}
        </div>
    @endif
</x-dashboard-layout>