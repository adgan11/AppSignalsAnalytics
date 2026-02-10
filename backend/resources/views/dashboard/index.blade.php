<x-layouts.dashboard :title="'Dashboard'" :header="'Apps'" :project="null" :projects="$projects">
    <div class="space-y-8">
        <section class="relative overflow-hidden rounded-lg border border-border bg-card/70 p-6 shadow-sm reveal">
            <div class="pointer-events-none absolute -right-24 -top-24 h-56 w-56 rounded-full bg-primary/10 blur-3xl"></div>
            <div class="pointer-events-none absolute -left-20 -bottom-16 h-40 w-40 rounded-full bg-primary/20 blur-2xl"></div>
            <div class="relative flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <p class="text-xs uppercase tracking-widest text-muted-foreground">Organization</p>
                    <h2 class="mt-2 text-3xl font-semibold text-foreground">{{ $orgName }}</h2>
                    <p class="mt-2 text-sm text-muted-foreground">{{ $projects->count() }} {{ \Illuminate\Support\Str::plural('App', $projects->count()) }}</p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{{ route('projects.create') }}" class="btn-primary">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Create new app or website
                    </a>
                </div>
            </div>
        </section>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach($projectCards as $card)
                @php
                    $project = $card['project'];
                    $delayClass = 'reveal-delay-' . (($loop->index % 4) + 1);
                @endphp
                <a href="{{ route('dashboard.overview', $project) }}"
                    class="stat-card reveal {{ $delayClass }} block group transition-transform hover:-translate-y-0.5"
                    aria-label="Open {{ $project->name }} dashboard">
                    <div class="flex items-start justify-between gap-4">
                        <div class="space-y-1">
                            <div class="text-lg font-semibold text-foreground group-hover:text-primary transition-colors">
                                {{ $project->name }}
                            </div>
                            <p class="text-xs uppercase tracking-wide text-muted-foreground">
                                {{ $project->platform ? ucfirst($project->platform) : 'App' }}
                            </p>
                        </div>
                        @if($project->bundle_id)
                            <span class="badge bg-muted text-muted-foreground max-w-[180px] truncate">{{ $project->bundle_id }}</span>
                        @endif
                    </div>

                    <div class="mt-6 grid grid-cols-2 gap-4">
                        <div>
                            <div class="metric-label">Total Events</div>
                            <div class="metric-large">{{ number_format($card['total_events']) }}</div>
                        </div>
                        <div>
                            <div class="metric-label">Unique Users</div>
                            <div class="metric-large">{{ number_format($card['unique_users']) }}</div>
                        </div>
                        <div>
                            <div class="metric-label">Sessions</div>
                            <div class="metric-large">{{ number_format($card['sessions']) }}</div>
                        </div>
                        <div>
                            <div class="metric-label">Crashes</div>
                            <div class="metric-large">{{ number_format($card['crashes']) }}</div>
                        </div>
                    </div>
                </a>
            @endforeach
        </section>
    </div>
</x-layouts.dashboard>
