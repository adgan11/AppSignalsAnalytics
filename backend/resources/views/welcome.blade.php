<!DOCTYPE html>
<html lang="en" class="h-full">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>AppSignals - Mobile Analytics Platform</title>
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
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="antialiased bg-background font-sans text-foreground">
    <div class="min-h-screen">
        <!-- Header -->
        <header class="bg-background/80 border-b border-border backdrop-blur">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-background flex items-center justify-center">
                            <img src="{{ asset('build/assets/logo.png') }}" alt="AppSignals logo"
                                class="w-8 h-8 object-contain" />
                        </div>
                        <span class="text-xl font-bold text-foreground">AppSignals</span>
                    </div>
                    <div class="flex items-center gap-4">
                        @auth
                            <a href="{{ route('dashboard') }}" class="btn-primary">Dashboard</a>
                        @else
                            <a href="{{ route('login') }}" class="text-muted-foreground hover:text-foreground font-medium">Sign In</a>
                            <a href="{{ route('dashboard') }}" class="btn-primary">Dashboard</a>
                        @endauth
                    </div>
                </div>
            </div>
        </header>

        <!-- Hero -->
        <main>
            <div class="relative overflow-hidden">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24 lg:py-32">
                    <div class="text-center">
                        <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold text-gray-900 tracking-tight">
                            Self-hosted
                            <span
                                class="bg-gradient-to-r from-primary-600 to-primary-400 bg-clip-text text-transparent">Mobile
                                Analytics</span>
                        </h1>
                        <p class="mt-6 text-xl text-gray-600 max-w-3xl mx-auto">
                            Track events and catch crashes in your iOS apps. Own your data. No third-party services
                            required.
                        </p>
                        <div class="mt-10 flex items-center justify-center gap-4">
                            @auth
                                <a href="{{ route('dashboard') }}" class="btn-primary text-lg px-8 py-3">Go to Dashboard</a>
                            @else
                                <a href="{{ route('dashboard') }}" class="btn-primary text-lg px-8 py-3">Open Dashboard</a>
                                <a href="#features" class="btn-secondary text-lg px-8 py-3">Learn More</a>
                            @endauth
                        </div>
                    </div>
                </div>

                <!-- Gradient decoration -->
                <div class="absolute inset-x-0 top-0 -z-10 transform-gpu overflow-hidden blur-3xl">
                    <div
                        class="relative aspect-[1155/678] w-[36rem] -translate-x-1/2 left-1/2 bg-gradient-to-tr from-primary-200 to-primary-400 opacity-30">
                    </div>
                </div>
            </div>

            <!-- Features -->
            <section id="features" class="py-24 bg-white">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="text-center mb-16">
                        <h2 class="text-3xl font-bold text-gray-900">Everything you need for mobile analytics</h2>
                        <p class="mt-4 text-lg text-gray-600">Built for developers who care about privacy and
                            performance</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div class="bg-gray-50 rounded-2xl p-8">
                            <div class="w-12 h-12 rounded-xl bg-blue-100 flex items-center justify-center mb-6">
                                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                            </div>
                            <h3 class="text-xl font-semibold text-gray-900 mb-2">Event Tracking</h3>
                            <p class="text-gray-600">Track custom events with properties. Automatic batching and retry
                                logic. Works offline.</p>
                        </div>

                        <div class="bg-gray-50 rounded-2xl p-8">
                            <div class="w-12 h-12 rounded-xl bg-red-100 flex items-center justify-center mb-6">
                                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                            <h3 class="text-xl font-semibold text-gray-900 mb-2">Crash Reporting</h3>
                            <p class="text-gray-600">Catch exceptions and signals. Automatic grouping. dSYM
                                symbolication support.</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Integration -->
            <section class="py-24 bg-gray-50">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                        <div>
                            <h2 class="text-3xl font-bold text-gray-900 mb-6">Simple SDK Integration</h2>
                            <p class="text-lg text-gray-600 mb-6">
                                Add AppSignals to your iOS app in minutes. Swift Package Manager support included.
                            </p>
                            <ul class="space-y-3">
                                <li class="flex items-center gap-3 text-gray-700">
                                    <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                            clip-rule="evenodd" />
                                    </svg>
                                    iOS 16+ support
                                </li>
                                <li class="flex items-center gap-3 text-gray-700">
                                    <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                            clip-rule="evenodd" />
                                    </svg>
                                    Thread-safe with Swift Actors
                                </li>
                                <li class="flex items-center gap-3 text-gray-700">
                                    <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                            clip-rule="evenodd" />
                                    </svg>
                                    Automatic data compression
                                </li>
                                <li class="flex items-center gap-3 text-gray-700">
                                    <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                            clip-rule="evenodd" />
                                    </svg>
                                    App Store privacy manifest included
                                </li>
                            </ul>
                        </div>
                        <div class="bg-gray-900 rounded-2xl p-6 overflow-hidden">
                            <pre class="text-sm text-gray-100 overflow-x-auto"><code><span class="text-purple-400">import</span> <span class="text-blue-300">AppSignalsSDK</span>

<span class="text-gray-500">// Initialize in your App</span>
<span class="text-purple-400">@main</span>
<span class="text-purple-400">struct</span> <span class="text-green-300">MyApp</span>: <span class="text-blue-300">App</span> {
    <span class="text-purple-400">init</span>() {
        <span class="text-blue-300">AppSignals</span>.<span class="text-yellow-300">initialize</span>(
            apiKey: <span class="text-orange-300">"ok_live_xxx..."</span>,
            serverURL: <span class="text-orange-300">"https://your-server.com"</span>
        )
    }
}

<span class="text-gray-500">// Track events anywhere</span>
<span class="text-blue-300">AppSignals</span>.<span class="text-yellow-300">track</span>(<span class="text-orange-300">"purchase_completed"</span>, properties: [
    <span class="text-orange-300">"amount"</span>: <span class="text-cyan-300">29.99</span>,
    <span class="text-orange-300">"currency"</span>: <span class="text-orange-300">"USD"</span>
])</code></pre>
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <!-- Footer -->
        <footer class="bg-background/80 border-t border-border py-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div
                            class="w-6 h-6 rounded-md bg-gradient-to-br from-primary-500 to-primary-700 flex items-center justify-center">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                        <span class="text-gray-500">AppSignals Analytics</span>
                    </div>
                    <div class="text-gray-400 text-sm">
                        Laravel {{ app()->version() }} • PHP {{ PHP_VERSION }}
                    </div>
                </div>
            </div>
        </footer>
    </div>
</body>

</html>
