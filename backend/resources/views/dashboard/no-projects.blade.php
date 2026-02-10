<x-guest-layout>
    <div class="min-h-screen flex items-center justify-center bg-background py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full bg-card rounded-2xl border border-border shadow-lg p-8 text-center">
            <div
                class="w-16 h-16 mx-auto rounded-xl bg-gradient-to-br from-primary-500 to-primary-600 flex items-center justify-center mb-6">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
            </div>

            <h2 class="text-2xl font-bold text-foreground mb-2">Welcome to AppSignals!</h2>
            <p class="text-muted-foreground mb-8">Create your first project to start tracking analytics.</p>

            <a href="{{ route('projects.create') }}" class="btn-primary w-full justify-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Create Your First Project
            </a>
        </div>
    </div>
</x-guest-layout>
