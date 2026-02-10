<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'appsignals:install
                            {--force : Run non-interactively and skip confirmations}
                            {--seed : Seed demo data after migrations}';

    /**
     * The console command description.
     */
    protected $description = 'Install and configure AppSignals (env, key, migrations, optional seed)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting AppSignals installation...');

        $this->checkExtensions();

        if (!$this->ensureEnvFile()) {
            return self::FAILURE;
        }

        if (!$this->ensureAppKey()) {
            return self::FAILURE;
        }

        if (!$this->checkDatabaseConnection()) {
            return self::FAILURE;
        }

        $this->info('Running database migrations...');
        $this->call('migrate', ['--force' => true]);

        if ($this->option('seed') || $this->confirmIfAllowed('Seed demo data now?', false)) {
            $this->info('Seeding demo data...');
            $this->call('db:seed', ['--force' => true]);
        }

        if ($this->confirmIfAllowed('Create storage symlink (public/storage)?', true)) {
            $this->call('storage:link');
        }

        $this->info('Installation complete.');
        $this->line('Next steps:');
        $this->line('- Configure your .env settings for production.');
        $this->line('- Build assets with: npm install && npm run build');
        $this->line('- Start queue worker and Reverb in production.');

        return self::SUCCESS;
    }

    private function checkExtensions(): void
    {
        $required = [
            'bcmath',
            'ctype',
            'fileinfo',
            'json',
            'mbstring',
            'openssl',
            'pdo',
            'tokenizer',
            'xml',
        ];

        $missing = array_filter($required, function (string $ext): bool {
            return !extension_loaded($ext);
        });

        if (!empty($missing)) {
            $this->warn('Missing PHP extensions: ' . implode(', ', $missing));
        }
    }

    private function ensureEnvFile(): bool
    {
        $envPath = base_path('.env');
        $examplePath = base_path('.env.example');

        if (File::exists($envPath)) {
            return true;
        }

        if (!File::exists($examplePath)) {
            $this->error('Missing .env.example file.');
            return false;
        }

        $this->info('Creating .env from .env.example');
        File::copy($examplePath, $envPath);

        return true;
    }

    private function ensureAppKey(): bool
    {
        $appKey = config('app.key');

        if (!empty($appKey)) {
            return true;
        }

        $this->info('Generating APP_KEY...');
        Artisan::call('key:generate', ['--force' => true]);

        return !empty(config('app.key'));
    }

    private function checkDatabaseConnection(): bool
    {
        try {
            DB::connection()->getPdo();
            $this->info('Database connection OK.');
            return true;
        } catch (\Throwable $e) {
            $this->error('Database connection failed: ' . $e->getMessage());
            $this->line('Check your DB_* settings in .env before continuing.');
            return false;
        }
    }

    private function confirmIfAllowed(string $question, bool $default): bool
    {
        if ($this->option('force')) {
            return $default;
        }

        return $this->confirm($question, $default);
    }
}
