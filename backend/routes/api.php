<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\IngestController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\ApiKeyController;
use App\Http\Controllers\Api\AnalyticsController;
use App\Http\Controllers\Api\CrashController;
use App\Http\Controllers\Api\ReplayController;

/*
|--------------------------------------------------------------------------
| SDK Ingestion Routes (API Key Auth)
|--------------------------------------------------------------------------
*/
Route::prefix('v1')->middleware(['api.key', 'throttle:ingest'])->group(function () {
    // Event ingestion
    Route::post('/ingest', [IngestController::class, 'ingest']);

    // Crash report submission
    Route::post('/crash', [IngestController::class, 'crash']);

    // Session replay frames
    Route::post('/replay', [IngestController::class, 'replay']);
});

/*
|--------------------------------------------------------------------------
| Dashboard API Routes (Sanctum Auth)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    // Projects
    Route::apiResource('projects', ProjectController::class)->names([
        'index' => 'api.projects.index',
        'store' => 'api.projects.store',
        'show' => 'api.projects.show',
        'update' => 'api.projects.update',
        'destroy' => 'api.projects.destroy',
    ]);

    // API Keys
    Route::get('projects/{project}/api-keys', [ApiKeyController::class, 'index']);
    Route::post('projects/{project}/api-keys', [ApiKeyController::class, 'store']);
    Route::delete('api-keys/{apiKey}', [ApiKeyController::class, 'destroy']);

    // Analytics
    Route::get('projects/{project}/overview', [AnalyticsController::class, 'overview']);
    Route::get('projects/{project}/events', [AnalyticsController::class, 'events']);
    Route::post('projects/{project}/funnel', [AnalyticsController::class, 'funnel']);

    // Crashes
    Route::get('projects/{project}/crashes', [CrashController::class, 'index']);
    Route::get('projects/{project}/crashes/{crash}', [CrashController::class, 'show']);
    Route::post('projects/{project}/dsyms', [CrashController::class, 'uploadDsym']);

    // Session Replays
    Route::get('projects/{project}/replays', [ReplayController::class, 'index']);
    Route::get('projects/{project}/replays/{sessionId}', [ReplayController::class, 'show']);
});
