<x-dashboard-layout>
    <x-slot name="header">Settings</x-slot>
    <x-slot name="title">Settings - {{ $project->name }}</x-slot>

    @php
        $tabs = [
            'general' => 'General',
            'setup' => 'Setup',
        ];
        $currentTab = request()->query('tab', 'general');
        if (!array_key_exists($currentTab, $tabs)) {
            $currentTab = 'general';
        }
        $settings = $project->settings ?? [];
        $appStoreCategories = [
            '' => 'Not specified',
            'business' => 'Business',
            'education' => 'Education',
            'entertainment' => 'Entertainment',
            'finance' => 'Finance',
            'games' => 'Games',
            'health_fitness' => 'Health & Fitness',
            'lifestyle' => 'Lifestyle',
            'medical' => 'Medical',
            'music' => 'Music',
            'navigation' => 'Navigation',
            'news' => 'News',
            'photo_video' => 'Photo & Video',
            'productivity' => 'Productivity',
            'shopping' => 'Shopping',
            'social_networking' => 'Social Networking',
            'sports' => 'Sports',
            'travel' => 'Travel',
            'utilities' => 'Utilities',
            'weather' => 'Weather',
        ];
    @endphp

    <div class="space-y-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-2 flex flex-wrap gap-2">
            @foreach($tabs as $key => $label)
                <a href="{{ route('dashboard.settings', ['project' => $project, 'tab' => $key]) }}"
                    class="px-4 py-2 rounded-lg text-sm font-medium {{ $currentTab === $key ? 'bg-primary-50 text-primary-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>

        @if($currentTab === 'setup')
            <div class="bg-white rounded-xl shadow-sm border border-gray-100">
                <div class="p-6">
                    <h3 class="text-2xl font-semibold text-gray-900">Set up {{ $project->name }}</h3>
                    <p class="text-gray-600 mt-2">Use these identifiers and keys to connect your app to AppSignals.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white rounded-xl shadow-sm border border-gray-100">
                    <div class="px-6 py-4 border-b border-gray-100">
                        <h3 class="text-lg font-semibold text-gray-900">App Identifiers</h3>
                    </div>
                    <div class="p-6 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Project ID</label>
                            <input type="text" value="{{ $project->id }}" readonly
                                class="w-full rounded-lg border-gray-300 bg-gray-50 text-gray-600 font-mono">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Bundle ID</label>
                            <input type="text" value="{{ $project->bundle_id }}" readonly
                                class="w-full rounded-lg border-gray-300 bg-gray-50 text-gray-600 font-mono">
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-gray-100">
                    <div class="px-6 py-4 border-b border-gray-100">
                        <h3 class="text-lg font-semibold text-gray-900">Server Endpoints</h3>
                    </div>
                    <div class="p-6 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Server URL</label>
                            <input type="text" value="{{ url('/') }}" readonly
                                class="w-full rounded-lg border-gray-300 bg-gray-50 text-gray-600 font-mono">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Ingest Endpoint</label>
                            <input type="text" value="{{ url('/api/v1/ingest') }}" readonly
                                class="w-full rounded-lg border-gray-300 bg-gray-50 text-gray-600 font-mono">
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100" x-data="{ showModal: false, newKey: null }">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">API Keys</h3>
                        <p class="text-sm text-gray-500 mt-1">Rotate keys by creating a new key and revoking the old one
                            after your apps are updated.</p>
                    </div>
                    <button @click="showModal = true" class="btn-primary">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        New API Key
                    </button>
                </div>

                <div class="divide-y divide-gray-100">
                    @forelse($apiKeys as $key)
                        <div class="px-6 py-4 flex items-center justify-between">
                            <div>
                                <p class="font-medium text-gray-900">{{ $key->name }}</p>
                                <p class="text-sm text-gray-500 font-mono">{{ $key->key_prefix }}••••••••••••</p>
                                <p class="text-xs text-gray-400 mt-1">
                                    Created {{ $key->created_at->diffForHumans() }}
                                    @if($key->last_used_at)
                                        • Last used {{ $key->last_used_at->diffForHumans() }}
                                    @endif
                                </p>
                            </div>
                            <form action="{{ route('api-keys.destroy', $key) }}" method="POST"
                                onsubmit="return confirm('Are you sure? This will invalidate the API key.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                    class="text-red-600 hover:text-red-700 text-sm font-medium">Revoke</button>
                            </form>
                        </div>
                    @empty
                        <div class="px-6 py-12 text-center">
                            <svg class="w-12 h-12 mx-auto text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                            </svg>
                            <p class="mt-4 text-gray-500">No API keys yet. Create one to start sending events.</p>
                        </div>
                    @endforelse
                </div>

                <div x-show="showModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
                    <div class="flex items-center justify-center min-h-screen p-4">
                        <div class="fixed inset-0 bg-gray-500/75 transition-opacity" @click="showModal = false"></div>

                        <div class="relative bg-white rounded-xl shadow-xl max-w-md w-full p-6"
                            @click.away="showModal = false">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Create New API Key</h3>

                            <div x-show="!newKey">
                                <form action="{{ route('api-keys.store', $project) }}" method="POST"
                                    @submit.prevent="createKey">
                                    @csrf
                                    <div class="mb-4">
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Key Name</label>
                                        <input type="text" name="name" id="key-name" placeholder="e.g., Production iOS"
                                            class="w-full rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500"
                                            required>
                                    </div>
                                    <div class="flex justify-end gap-3">
                                        <button type="button" @click="showModal = false"
                                            class="btn-secondary">Cancel</button>
                                        <button type="submit" class="btn-primary">Create Key</button>
                                    </div>
                                </form>
                            </div>

                            <div x-show="newKey" x-cloak>
                                <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-4">
                                    <p class="text-sm text-green-800 font-medium mb-2">API Key created! Copy it now - it
                                        won't be shown again.</p>
                                    <div class="bg-white rounded p-3 font-mono text-sm break-all" x-text="newKey"></div>
                                </div>
                                <div class="flex justify-end">
                                    <button @click="showModal = false; newKey = null; location.reload()"
                                        class="btn-primary">Done</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <script>
                    function createKey(event) {
                        const form = event.target;
                        const name = document.getElementById('key-name').value;

                        fetch('{{ route('api-keys.store', $project) }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({ name: name })
                        })
                            .then(res => res.json())
                            .then(data => {
                                Alpine.evaluate(form.closest('[x-data]'), 'newKey = "' + data.data.key + '"');
                            });
                    }
                </script>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Swift SDK</h3>
                        <p class="text-sm text-gray-500 mt-1">Add the SDK using Swift Package Manager.</p>
                    </div>
                    <div
                        class="w-12 h-12 rounded-xl bg-orange-100 text-orange-600 font-semibold flex items-center justify-center">
                        Swift
                    </div>
                </div>
                <div class="p-6 space-y-4">
                    <div class="bg-gray-900 rounded-lg p-4">
                        <pre class="text-sm text-gray-100"><code>import AppSignalsSDK

@main
struct YourApp: App {
    init() {
        AppSignals.initialize(
            apiKey: "YOUR_API_KEY",
            serverURL: "{{ url('/') }}"
        )
    }

    var body: some Scene {
        WindowGroup {
            ContentView()
        }
    }
}</code></pre>
                    </div>

                </div>
            </div>
        @else
            <form action="{{ route('projects.update', $project) }}" method="POST" class="space-y-6">
                @csrf
                @method('PUT')

                <div class="bg-white rounded-xl shadow-sm border border-gray-100">
                    <div class="px-6 py-4 border-b border-gray-100">
                        <h3 class="text-lg font-semibold text-gray-900">App Information</h3>
                        <p class="text-sm text-gray-500 mt-1">Additional metadata about your app.</p>
                    </div>
                    <div class="p-6 grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Platform</label>
                            <select name="platform"
                                class="w-full rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500">
                                <option value="ios" {{ old('platform', $project->platform) === 'ios' ? 'selected' : '' }}>iOS</option>
                                <option value="android" {{ old('platform', $project->platform) === 'android' ? 'selected' : '' }}>Android</option>
                            </select>
                        </div>

                        <div class="lg:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                            <textarea name="settings[description]" rows="4"
                                class="w-full rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500">{{ old('settings.description', $settings['description'] ?? '') }}</textarea>
                            <p class="mt-1 text-xs text-gray-500">A brief description of your app (optional).</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">App Store Link</label>
                            <input type="url" name="settings[app_store_link]"
                                value="{{ old('settings.app_store_link', $settings['app_store_link'] ?? '') }}"
                                placeholder="https://apps.apple.com/app/id123456789"
                                class="w-full rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Website URL</label>
                            <input type="url" name="settings[website_url]"
                                value="{{ old('settings.website_url', $settings['website_url'] ?? '') }}"
                                placeholder="https://example.com"
                                class="w-full rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">App Icon URL</label>
                            <input type="url" name="settings[app_icon_url]"
                                value="{{ old('settings.app_icon_url', $settings['app_icon_url'] ?? '') }}"
                                placeholder="https://example.com/icon.png"
                                class="w-full rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">App Store Category</label>
                            <select name="settings[app_store_category]"
                                class="w-full rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500">
                                @foreach($appStoreCategories as $value => $label)
                                    <option value="{{ $value }}" {{ old('settings.app_store_category', $settings['app_store_category'] ?? '') === $value ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-gray-100">
                    <div class="px-6 py-4 border-b border-gray-100">
                        <h3 class="text-lg font-semibold text-gray-900">Project Settings</h3>
                    </div>
                    <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Project Name</label>
                            <input type="text" name="name" value="{{ old('name', $project->name) }}"
                                class="w-full rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Bundle ID</label>
                            <input type="text" value="{{ $project->bundle_id }}" disabled
                                class="w-full rounded-lg border-gray-300 bg-gray-50 text-gray-500">
                            <p class="mt-1 text-xs text-gray-500">Bundle ID cannot be changed</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Timezone</label>
                            <select name="timezone"
                                class="w-full rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500">
                                @foreach(timezone_identifiers_list() as $tz)
                                    <option value="{{ $tz }}" {{ old('timezone', $project->timezone) === $tz ? 'selected' : '' }}>{{ $tz }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Data Retention (days)</label>
                            <input type="number" name="data_retention_days"
                                value="{{ old('data_retention_days', $project->data_retention_days ?? 90) }}" min="7" max="365"
                                class="w-full rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500">
                        </div>
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="btn-primary">Save Changes</button>
                </div>
            </form>

            <div class="bg-white rounded-xl shadow-sm border border-red-200">
                <div class="px-6 py-4 border-b border-red-100">
                    <h3 class="text-lg font-semibold text-red-600">Danger Zone</h3>
                </div>
                <div class="p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="font-medium text-gray-900">Delete Project</p>
                            <p class="text-sm text-gray-500">Permanently delete this project and all associated data.</p>
                        </div>
                        <form action="{{ route('projects.destroy', $project) }}" method="POST"
                            onsubmit="return confirm('Are you absolutely sure? This will delete ALL events, crashes, and replays. This action cannot be undone.')">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-lg font-medium text-sm text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-colors">
                                Delete Project
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-dashboard-layout>
