<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled Tasks
|--------------------------------------------------------------------------
*/

// Aggregate daily stats every hour
Schedule::command('appsignals:aggregate-stats')->hourly();

// Cleanup old events based on retention policy
Schedule::command('appsignals:cleanup-events')->daily();

// Process pending crash symbolication
Schedule::command('appsignals:symbolicate-crashes')->everyFiveMinutes();
