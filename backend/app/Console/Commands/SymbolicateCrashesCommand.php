<?php

namespace App\Console\Commands;

use App\Models\Crash;
use App\Models\DsymFile;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

class SymbolicateCrashesCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'appsignals:symbolicate-crashes
                            {--project= : Specific project ID to symbolicate}
                            {--limit= : Limit number of crashes to process}';

    /**
     * The console command description.
     */
    protected $description = 'Symbolicate crashes using a configured command';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (!config('appsignals.symbolication.enabled')) {
            $this->warn('Symbolication is disabled. Set APPSIGNALS_SYMBOLICATION_ENABLED=true to enable.');
            return self::SUCCESS;
        }

        $command = config('appsignals.symbolication.command');
        if (!$command) {
            $this->warn('Symbolication command is not configured. Set APPSIGNALS_SYMBOLICATION_COMMAND.');
            return self::SUCCESS;
        }

        $limit = (int) ($this->option('limit') ?: config('appsignals.symbolication.batch_size'));
        $projectId = $this->option('project');

        $query = Crash::unsymbolicated()->orderBy('occurred_at');
        if ($projectId) {
            $query->where('project_id', $projectId);
        }

        $crashes = $query->limit($limit)->get();
        if ($crashes->isEmpty()) {
            $this->info('No crashes to symbolicate.');
            return self::SUCCESS;
        }

        foreach ($crashes as $crash) {
            $dsymFile = $this->findDsymForCrash($crash);
            if (!$dsymFile) {
                $this->line("Skipping crash {$crash->id}: no matching dSYM file.");
                continue;
            }

            $dsymPath = Storage::disk('local')->path($dsymFile->file_path);

            $env = [
                'APPSIGNALS_CRASH_ID' => (string) $crash->crash_id,
                'APPSIGNALS_PROJECT_ID' => (string) $crash->project_id,
                'APPSIGNALS_APP_VERSION' => (string) $crash->app_version,
                'APPSIGNALS_APP_BUILD' => (string) ($crash->app_build ?? ''),
                'APPSIGNALS_DSYM_PATH' => $dsymPath,
            ];

            $process = Process::fromShellCommandline($command, null, $env);
            $process->setTimeout(60);
            $process->setInput($crash->stack_trace);
            $process->run();

            if (!$process->isSuccessful()) {
                $this->line("Failed to symbolicate crash {$crash->id}: {$process->getErrorOutput()}");
                continue;
            }

            $output = trim($process->getOutput());
            if ($output === '') {
                $this->line("No symbolicated output for crash {$crash->id}.");
                continue;
            }

            $crash->update([
                'symbolicated_trace' => $output,
                'is_symbolicated' => true,
            ]);

            $this->line("Symbolicated crash {$crash->id}.");
        }

        return self::SUCCESS;
    }

    private function findDsymForCrash(Crash $crash): ?DsymFile
    {
        $query = DsymFile::where('project_id', $crash->project_id);

        if ($crash->app_version) {
            $query->where('app_version', $crash->app_version);
        }

        if ($crash->app_build) {
            $query->where('build_number', $crash->app_build);
        }

        $dsymFile = $query->orderByDesc('created_at')->first();
        if ($dsymFile) {
            return $dsymFile;
        }

        if ($crash->app_version) {
            return DsymFile::where('project_id', $crash->project_id)
                ->where('app_version', $crash->app_version)
                ->orderByDesc('created_at')
                ->first();
        }

        return null;
    }
}
