<?php

namespace App\Console\Commands;

use App\Jobs\AggregateStats;
use App\Models\Project;
use Carbon\Carbon;
use Illuminate\Console\Command;

class AggregateStatsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'appsignals:aggregate-stats 
                            {--date= : Specific date to aggregate (YYYY-MM-DD)}
                            {--project= : Specific project ID to aggregate}';

    /**
     * The console command description.
     */
    protected $description = 'Aggregate daily statistics from raw events';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $date = $this->option('date')
            ? Carbon::parse($this->option('date'))
            : Carbon::yesterday();

        $projectId = $this->option('project');

        $this->info("Aggregating stats for {$date->toDateString()}...");

        $query = Project::query();

        if ($projectId) {
            $query->where('id', $projectId);
        }

        $projects = $query->get();

        foreach ($projects as $project) {
            $this->line("  Processing project: {$project->name}");
            AggregateStats::dispatch($project->id, $date->toDateString());
        }

        $this->info("Dispatched {$projects->count()} aggregation jobs.");

        return self::SUCCESS;
    }
}
