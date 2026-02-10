<x-guest-layout>
    <div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-lg w-full">
            <div class="text-center mb-8">
                <div class="w-12 h-12 mx-auto rounded-lg bg-gradient-to-br from-primary-500 to-primary-700 flex items-center justify-center mb-4">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                </div>
                <h2 class="text-2xl font-bold text-gray-900">Create New Project</h2>
                <p class="text-gray-500 mt-1">Set up analytics for your iOS app</p>
            </div>

            <div class="bg-white rounded-2xl shadow-lg p-8">
                <form action="{{ route('projects.store') }}" method="POST" class="space-y-6">
                    @csrf
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Project Name</label>
                        <input type="text" name="name" value="{{ old('name') }}" placeholder="My iOS App" class="w-full rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500" required>
                        @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Bundle ID</label>
                        <input type="text" name="bundle_id" value="{{ old('bundle_id') }}" placeholder="com.company.appname" class="w-full rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500 font-mono" required pattern="[a-zA-Z0-9.-]+">
                        <p class="mt-1 text-xs text-gray-500">Your app's bundle identifier (e.g., com.example.myapp)</p>
                        @error('bundle_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Platform</label>
                        <select name="platform" class="w-full rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500">
                            <option value="ios" {{ old('platform') === 'ios' ? 'selected' : '' }}>iOS</option>
                            <option value="android" {{ old('platform') === 'android' ? 'selected' : '' }}>Android</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Timezone</label>
                        <select name="timezone" class="w-full rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500">
                            @foreach(['America/New_York', 'America/Los_Angeles', 'Europe/London', 'Europe/Berlin', 'Asia/Tokyo', 'Asia/Singapore', 'Australia/Sydney', 'UTC'] as $tz)
                            <option value="{{ $tz }}" {{ old('timezone', 'UTC') === $tz ? 'selected' : '' }}>{{ $tz }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="flex items-center justify-between pt-4">
                        <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-gray-700 text-sm">← Back to Dashboard</a>
                        <button type="submit" class="btn-primary">Create Project</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-guest-layout>

