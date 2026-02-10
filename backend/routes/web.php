<?php

use App\Http\Controllers\ApiKeyWebController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return view('welcome');
});

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard Home (redirects to first project or no-projects page)
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Project Management
    Route::get('/projects/create', [ProjectController::class, 'create'])->name('projects.create');
    Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
    Route::put('/projects/{project}', [ProjectController::class, 'update'])->name('projects.update');
    Route::delete('/projects/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');

    // Dashboard Pages (per project)
    Route::prefix('projects/{project}')->group(function () {
        Route::get('/', [DashboardController::class, 'overview'])->name('dashboard.overview');
        Route::get('/events', [DashboardController::class, 'events'])->name('dashboard.events');
        Route::get('/crashes', [DashboardController::class, 'crashes'])->name('dashboard.crashes');
        Route::get('/crashes/{crashGroupHash}', [DashboardController::class, 'crashDetail'])->name('dashboard.crash-detail');
        Route::get('/replays', [DashboardController::class, 'replays'])->name('dashboard.replays');
        Route::get('/replays/{sessionId}', [DashboardController::class, 'replayPlayer'])->name('dashboard.replay-player');
        Route::get('/settings', [DashboardController::class, 'settings'])->name('dashboard.settings');

        // Customers section
        Route::get('/customers/acquisition', [DashboardController::class, 'acquisition'])->name('dashboard.acquisition');
        Route::get('/customers/activation', [DashboardController::class, 'activation'])->name('dashboard.activation');
        Route::get('/customers/retention', [DashboardController::class, 'retention'])->name('dashboard.retention');
        Route::get('/customers/acquisition/data', [DashboardController::class, 'acquisitionData'])->name('dashboard.acquisition.data');
        Route::get('/customers/activation/data', [DashboardController::class, 'activationData'])->name('dashboard.activation.data');
        Route::get('/customers/retention/data', [DashboardController::class, 'retentionData'])->name('dashboard.retention.data');

        // Metrics
        Route::get('/metrics/{section}/data', [DashboardController::class, 'metricsData'])->name('dashboard.metrics.data');
        Route::get('/metrics/{section?}', [DashboardController::class, 'metrics'])->name('dashboard.metrics');

        // Explore
        Route::get('/explore/{section?}', [DashboardController::class, 'explore'])->name('dashboard.explore');

        // API Keys
        Route::post('/api-keys', [ApiKeyWebController::class, 'store'])->name('api-keys.store');
    });

    // API Key deletion (without project prefix)
    Route::delete('/api-keys/{apiKey}', [ApiKeyWebController::class, 'destroy'])->name('api-keys.destroy');

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
