<?php

namespace App\Console\Commands;

use App\Models\ApiKey;
use App\Models\Crash;
use App\Models\Event;
use App\Models\Project;
use App\Models\ReplayFrame;
use App\Models\SessionReplay;
use Database\Seeders\DemoMetricsSeeder;
use Illuminate\Console\Command;

class DemoResetCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'demo:reset
                            {--force : Run without confirmation}';

    /**
     * The console command description.
     */
    protected $description = 'Reset demo analytics data for the default demo project';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (!config('appsignals.demo_seed')) {
            $this->warn('Demo seed is disabled. Set APPSIGNALS_DEMO_SEED=true to enable.');
            return self::SUCCESS;
        }

        if (!$this->option('force')) {
            if (!$this->confirm('This will delete demo events, crashes, and replays. Continue?')) {
                return self::SUCCESS;
            }
        }

        $project = Project::where('bundle_id', 'com.appsignals.demo')->first();
        if (!$project) {
            $this->warn('Demo project not found. Run php artisan db:seed first.');
            return self::SUCCESS;
        }

        $eventsDeleted = Event::where('project_id', $project->id)->delete();
        $crashesDeleted = Crash::where('project_id', $project->id)->delete();

        $replayIds = SessionReplay::where('project_id', $project->id)->pluck('id');
        $framesDeleted = 0;
        $replaysDeleted = 0;

        if ($replayIds->isNotEmpty()) {
            $framesDeleted = ReplayFrame::whereIn('session_replay_id', $replayIds)->delete();
            $replaysDeleted = SessionReplay::whereIn('id', $replayIds)->delete();
        }

        $this->call(DemoMetricsSeeder::class);

        if (!$project->apiKeys()->exists()) {
            $result = ApiKey::generate($project->id, 'Demo iOS Key');
            $this->info("Demo API key: {$result['key']}");
        }

        $this->info(sprintf(
            'Reset complete. Deleted %d events, %d crashes, %d replays, %d replay frames.',
            $eventsDeleted,
            $crashesDeleted,
            $replaysDeleted,
            $framesDeleted
        ));

        return self::SUCCESS;
    }
}
