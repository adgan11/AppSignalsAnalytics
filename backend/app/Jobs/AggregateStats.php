<?php

namespace App\Jobs;

use App\Models\DailyStat;
use App\Models\Event;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AggregateStats implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected int $projectId,
        protected string $date
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $date = Carbon::parse($this->date)->startOfDay();
        $nextDay = $date->copy()->addDay();

        Log::info("Aggregating stats for project {$this->projectId} on {$date->toDateString()}");

        // Aggregate events by event_name
        $stats = Event::where('project_id', $this->projectId)
            ->where('event_timestamp', '>=', $date)
            ->where('event_timestamp', '<', $nextDay)
            ->select([
                'event_name',
                DB::raw('COUNT(*) as event_count'),
                DB::raw('COUNT(DISTINCT user_id) as unique_users'),
                DB::raw('COUNT(DISTINCT device_id) as unique_devices'),
                DB::raw('COUNT(DISTINCT session_id) as unique_sessions'),
            ])
            ->groupBy('event_name')
            ->get();

        foreach ($stats as $stat) {
            DailyStat::updateOrCreate(
                [
                    'project_id' => $this->projectId,
                    'date' => $date,
                    'event_name' => $stat->event_name,
                    'country_code' => null,
                    'device_model' => null,
                    'app_version' => null,
                ],
                [
                    'event_count' => $stat->event_count,
                    'unique_users' => $stat->unique_users,
                    'unique_devices' => $stat->unique_devices,
                    'unique_sessions' => $stat->unique_sessions,
                ]
            );
        }

        // Also aggregate by country for geographic breakdown
        $countryStats = Event::where('project_id', $this->projectId)
            ->where('event_timestamp', '>=', $date)
            ->where('event_timestamp', '<', $nextDay)
            ->whereNotNull('country_code')
            ->select([
                'event_name',
                'country_code',
                DB::raw('COUNT(*) as event_count'),
                DB::raw('COUNT(DISTINCT user_id) as unique_users'),
                DB::raw('COUNT(DISTINCT device_id) as unique_devices'),
                DB::raw('COUNT(DISTINCT session_id) as unique_sessions'),
            ])
            ->groupBy('event_name', 'country_code')
            ->get();

        foreach ($countryStats as $stat) {
            DailyStat::updateOrCreate(
                [
                    'project_id' => $this->projectId,
                    'date' => $date,
                    'event_name' => $stat->event_name,
                    'country_code' => $stat->country_code,
                    'device_model' => null,
                    'app_version' => null,
                ],
                [
                    'event_count' => $stat->event_count,
                    'unique_users' => $stat->unique_users,
                    'unique_devices' => $stat->unique_devices,
                    'unique_sessions' => $stat->unique_sessions,
                ]
            );
        }

        Log::info("Completed stats aggregation for project {$this->projectId}");
    }
}
