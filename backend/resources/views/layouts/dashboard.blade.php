<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-background">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Dashboard' }} - AppSignals Analytics</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700,800&display=swap" rel="stylesheet" />

    <script>
        (function() {
            const storedTheme = localStorage.getItem('theme');
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            const theme = storedTheme || (prefersDark ? 'dark' : 'light');
            if (theme === 'dark') {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans antialiased" x-data="{ sidebarOpen: false }">
    <div class="min-h-full">
        <!-- Mobile sidebar backdrop -->
        <div x-show="sidebarOpen" 
             x-transition:enter="transition-opacity ease-linear duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-linear duration-300"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-black/80 z-40 lg:hidden"
             @click="sidebarOpen = false">
        </div>

        <!-- Sidebar -->
        <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
               class="fixed inset-y-0 left-0 z-50 w-72 bg-background text-foreground border-r border-border transform transition-transform duration-300 ease-in-out lg:translate-x-0 lg:static lg:inset-auto flex flex-col">
            
            <!-- Logo -->
            <div class="flex items-center gap-3 px-6 h-16 border-b border-border">
                <div class="w-8 h-8 rounded-lg bg-background flex items-center justify-center">
                    <img src="{{ asset('build/assets/logo.png') }}" alt="AppSignals logo"
                        class="w-8 h-8 object-contain" />
                </div>
                <span class="text-xl font-bold text-foreground">AppSignals</span>
            </div>

            <!-- Project Selector -->
            @if(isset($projects) && $projects->count() > 0)
            <div class="px-4 py-4 border-b border-border">
                <label class="block text-xs font-medium text-muted-foreground uppercase tracking-wide mb-2">Project</label>
                <div x-data="{ open: false }" class="relative">
                    <button @click="open = !open" type="button" class="w-full flex items-center justify-between px-3 py-2 bg-muted rounded-lg border border-border text-sm font-medium text-foreground hover:bg-muted/80 transition-colors">
                        <span class="truncate">{{ $project->name ?? 'Select Project' }}</span>
                        <svg class="w-4 h-4 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="open" @click.away="open = false" class="absolute left-0 right-0 mt-1 bg-popover text-popover-foreground rounded-lg shadow-lg border border-border py-1 z-10">
                        @foreach($projects as $p)
                        <a href="{{ route('dashboard.overview', $p) }}" class="block px-4 py-2 text-sm text-foreground hover:bg-accent {{ isset($project) && $p->id === $project->id ? 'bg-accent text-foreground' : '' }}">
                            {{ $p->name }}
                        </a>
                        @endforeach
                        <hr class="my-1 border-border">
                        <a href="{{ route('projects.create') }}" class="block px-4 py-2 text-sm text-primary hover:bg-accent">
                            + New Project
                        </a>
                    </div>
                </div>
            </div>
            @endif

            <!-- Navigation -->
            <nav class="flex-1 px-4 py-4 space-y-1 overflow-y-auto">
                <a href="{{ route('dashboard') }}" class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h4v4H4V6zm0 8h4v4H4v-4zm8-8h4v4h-4V6zm0 8h4v4h-4v-4zm8-8h-4v4h4V6zm0 8h-4v4h4v-4z"/>
                    </svg>
                    <span>Apps</span>
                </a>
                @if(isset($project))
                <a href="{{ route('dashboard.overview', $project) }}" class="nav-item {{ request()->routeIs('dashboard.overview') ? 'active' : '' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    <span>Overview</span>
                </a>
                
                <a href="{{ route('dashboard.events', $project) }}" class="nav-item {{ request()->routeIs('dashboard.events') ? 'active' : '' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    <span>Events</span>
                </a>
                
                <a href="{{ route('dashboard.crashes', $project) }}" class="nav-item {{ request()->routeIs('dashboard.crashes*') ? 'active' : '' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <span>Crashes</span>
                </a>
                
                <!-- Customers Section -->
                <div class="pt-4" x-data="{ customersOpen: {{ request()->routeIs('dashboard.acquisition', 'dashboard.activation', 'dashboard.retention') ? 'true' : 'false' }} }">
                    <button @click="customersOpen = !customersOpen" class="nav-item w-full justify-between {{ request()->routeIs('dashboard.acquisition', 'dashboard.activation', 'dashboard.retention') ? 'active' : '' }}">
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                            <span>Customers</span>
                        </div>
                        <svg class="w-4 h-4 transform transition-transform" :class="customersOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="customersOpen" x-collapse class="pl-8 mt-1 space-y-1">
                        <a href="{{ route('dashboard.acquisition', $project) }}" class="nav-item-sub {{ request()->routeIs('dashboard.acquisition') ? 'active' : '' }}">
                            Acquisition
                        </a>
                        <a href="{{ route('dashboard.activation', $project) }}" class="nav-item-sub {{ request()->routeIs('dashboard.activation') ? 'active' : '' }}">
                            Activation
                        </a>
                        <a href="{{ route('dashboard.retention', $project) }}" class="nav-item-sub {{ request()->routeIs('dashboard.retention') ? 'active' : '' }}">
                            Retention
                        </a>
                    </div>
                </div>

                <a href="{{ route('dashboard.metrics', $project) }}" class="nav-item {{ request()->routeIs('dashboard.metrics') ? 'active' : '' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    <span>Metrics</span>
                </a>

                <hr class="my-4 border-border">
                
                <a href="{{ route('dashboard.settings', $project) }}" class="nav-item {{ request()->routeIs('dashboard.settings') ? 'active' : '' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <span>Settings</span>
                </a>
                @endif
            </nav>

            <!-- User Menu -->
            <div class="border-t border-border p-4">
                <div x-data="{ open: false }" class="relative">
                    <button @click="open = !open" class="flex items-center gap-3 w-full p-2 rounded-lg hover:bg-accent transition-colors">
                        <div class="w-8 h-8 rounded-full bg-muted flex items-center justify-center">
                            <span class="text-sm font-medium text-foreground">{{ substr(auth()->user()->name, 0, 1) }}</span>
                        </div>
                        <div class="flex-1 text-left">
                            <div class="text-sm font-medium text-foreground">{{ auth()->user()->name }}</div>
                            <div class="text-xs text-muted-foreground truncate">{{ auth()->user()->email }}</div>
                        </div>
                    </button>
                    <div x-show="open" @click.away="open = false" class="absolute bottom-full left-0 right-0 mb-1 bg-popover text-popover-foreground rounded-lg shadow-lg border border-border py-1">
                        <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm text-foreground hover:bg-accent">Profile</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-foreground hover:bg-accent">Sign out</button>
                        </form>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="lg:pl-72">
            <!-- Top Bar -->
            <header class="sticky top-0 z-40 bg-background/80 border-b border-border backdrop-blur">
                <div class="flex items-center justify-between h-16 px-4 sm:px-6 lg:px-8">
                    <div class="flex items-center gap-4">
                        <button @click="sidebarOpen = true" class="lg:hidden -ml-2 p-2 text-muted-foreground hover:text-foreground">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                            </svg>
                        </button>
                        <h1 class="text-xl font-semibold text-foreground">{{ $header ?? '' }}</h1>
                    </div>
                    
                    <div class="flex items-center gap-4">
                        @if(isset($project))
                        <div class="flex items-center gap-2 text-sm text-muted-foreground">
                            <span class="live-indicator"></span>
                            <span>Live</span>
                        </div>
                        @endif
                        <button type="button"
                            class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-border bg-background text-foreground shadow-sm transition hover:bg-accent"
                            x-data="{ dark: document.documentElement.classList.contains('dark') }"
                            @click="dark = !dark; window.setTheme(dark ? 'dark' : 'light')"
                            @theme-changed.window="dark = $event.detail === 'dark'">
                            <svg x-show="!dark" x-cloak class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 3v2m0 14v2m9-9h-2M5 12H3m15.364-6.364-1.414 1.414M6.05 17.95l-1.414 1.414M17.95 17.95l-1.414-1.414M6.05 6.05 4.636 4.636M12 8a4 4 0 100 8 4 4 0 000-8z" />
                            </svg>
                            <svg x-show="dark" x-cloak class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z" />
                            </svg>
                        </button>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="p-4 sm:p-6 lg:p-8">
                {{ $slot }}
            </main>
        </div>
    </div>

    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
</body>
</html>
