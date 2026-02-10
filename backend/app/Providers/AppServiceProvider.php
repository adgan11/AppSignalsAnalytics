<?php

namespace App\Providers;

use App\Models\Project;
use App\Policies\ProjectPolicy;
use App\Services\GeoIpService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register GeoIP service as singleton
        $this->app->singleton(GeoIpService::class, function ($app) {
            return new GeoIpService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('ingest', function (Request $request) {
            $apiKey = $request->header('X-API-Key') ?? $request->bearerToken();
            $identifier = $apiKey ?: $request->ip();
            $perMinute = (int) config('appsignals.rate_limits.ingest_per_minute');

            return Limit::perMinute($perMinute)->by($identifier);
        });

        // Register policies
        Gate::policy(Project::class, ProjectPolicy::class);

        // Share project data with dashboard layout component
        View::composer('components.dashboard-layout', function ($view) {
            if (Auth::check()) {
                $user = Auth::user();
                $projects = $user->projects()->get();

                // Try to get project from route parameter
                $project = request()->route('project');

                $view->with('projects', $projects);
                if ($project) {
                    $view->with('project', $project);
                }
            }
        });
    }
}
