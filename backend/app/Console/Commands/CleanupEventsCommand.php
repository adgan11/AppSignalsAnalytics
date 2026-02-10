<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Models\Project;
use App\Models\Crash;
use App\Models\ReplayFrame;
use App\Models\SessionReplay;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CleanupEventsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'appsignals:cleanup-events 
                            {--dry-run : Show what would be deleted without actually deleting}';

    /**
     * The console command description.
     */
    protected $description = 'Delete events, crashes, and replays older than the project retention period';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No data will be deleted');
        }

        $projects = Project::all();
        $totalDeleted = [
            'events' => 0,
            'crashes' => 0,
            'replays' => 0,
            'frames' => 0,
        ];

        foreach ($projects as $project) {
            $cutoffDate = Carbon::now()->subDays($project->data_retention_days);

            $eventCount = Event::where('project_id', $project->id)
                ->where('event_timestamp', '<', $cutoffDate)
                ->count();

            if ($eventCount > 0) {
                $this->line("Project '{$project->name}': {$eventCount} events older than {$cutoffDate->toDateString()}");

                if (!$dryRun) {
                    // Delete in chunks to avoid lock timeouts
                    Event::where('project_id', $project->id)
                        ->where('event_timestamp', '<', $cutoffDate)
                        ->chunkById(1000, function ($events) {
                            Event::whereIn('id', $events->pluck('id'))->delete();
                        });

                    $totalDeleted['events'] += $eventCount;
                }
            }

            $crashCount = Crash::where('project_id', $project->id)
                ->where('occurred_at', '<', $cutoffDate)
                ->count();

            if ($crashCount > 0) {
                $this->line("Project '{$project->name}': {$crashCount} crashes older than {$cutoffDate->toDateString()}");

                if (!$dryRun) {
                    Crash::where('project_id', $project->id)
                        ->where('occurred_at', '<', $cutoffDate)
                        ->chunkById(1000, function ($crashes) {
                            Crash::whereIn('id', $crashes->pluck('id'))->delete();
                        });

                    $totalDeleted['crashes'] += $crashCount;
                }
            }

            $replayQuery = SessionReplay::where('project_id', $project->id)
                ->where('started_at', '<', $cutoffDate);
            $replayCount = $replayQuery->count();

            if ($replayCount > 0) {
                $this->line("Project '{$project->name}': {$replayCount} session replays older than {$cutoffDate->toDateString()}");

                if (!$dryRun) {
                    $replayQuery->chunkById(200, function ($replays) use (&$totalDeleted) {
                        $replayIds = $replays->pluck('id');
                        $frameCount = ReplayFrame::whereIn('session_replay_id', $replayIds)->count();

                        ReplayFrame::whereIn('session_replay_id', $replayIds)->delete();
                        SessionReplay::whereIn('id', $replayIds)->delete();

                        $totalDeleted['frames'] += $frameCount;
                        $totalDeleted['replays'] += $replayIds->count();
                    });
                }
            }
        }

        if ($dryRun) {
            $this->info(sprintf(
                'Would delete %d events, %d crashes, %d replays, and %d replay frames total.',
                $totalDeleted['events'],
                $totalDeleted['crashes'],
                $totalDeleted['replays'],
                $totalDeleted['frames']
            ));
        } else {
            $this->info(sprintf(
                'Deleted %d events, %d crashes, %d replays, and %d replay frames total.',
                $totalDeleted['events'],
                $totalDeleted['crashes'],
                $totalDeleted['replays'],
                $totalDeleted['frames']
            ));
        }

        return self::SUCCESS;
    }
}
