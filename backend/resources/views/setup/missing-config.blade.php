<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>AppSignals Setup Required</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-background text-foreground font-sans">
    <div class="min-h-screen flex items-center justify-center px-6 py-12">
        <div class="max-w-2xl w-full bg-white border border-gray-200 rounded-2xl shadow-sm p-8">
            <h1 class="text-2xl font-semibold text-gray-900">AppSignals is not configured yet</h1>
            <p class="mt-3 text-gray-600">Your environment is missing required configuration. Complete the steps below and reload this page.</p>

            <div class="mt-6 grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="rounded-xl border border-gray-200 p-4">
                    <p class="text-sm text-gray-500">.env file</p>
                    <p class="mt-2 font-semibold {{ $hasEnv ? 'text-green-600' : 'text-red-600' }}">
                        {{ $hasEnv ? 'Found' : 'Missing' }}
                    </p>
                </div>
                <div class="rounded-xl border border-gray-200 p-4">
                    <p class="text-sm text-gray-500">APP_KEY</p>
                    <p class="mt-2 font-semibold {{ $hasAppKey ? 'text-green-600' : 'text-red-600' }}">
                        {{ $hasAppKey ? 'Configured' : 'Missing' }}
                    </p>
                </div>
                <div class="rounded-xl border border-gray-200 p-4">
                    <p class="text-sm text-gray-500">Database</p>
                    <p class="mt-2 font-semibold {{ $hasDatabase ? 'text-green-600' : 'text-red-600' }}">
                        {{ $hasDatabase ? 'Configured' : 'Missing' }}
                    </p>
                </div>
            </div>

            <div class="mt-6">
                <h2 class="text-lg font-semibold text-gray-900">Setup steps</h2>
                <ol class="mt-3 list-decimal list-inside space-y-2 text-gray-700">
                    @foreach($steps as $step)
                        <li>{{ $step }}</li>
                    @endforeach
                </ol>
            </div>

            <div class="mt-6 rounded-xl bg-gray-50 border border-gray-200 p-4">
                <p class="text-sm font-medium text-gray-700">Recommended command</p>
                <code class="text-sm text-gray-900">php artisan appsignals:install --seed</code>
            </div>
        </div>
    </div>
</body>
</html>
