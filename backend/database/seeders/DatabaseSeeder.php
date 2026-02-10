<?php

namespace Database\Seeders;

use App\Models\ApiKey;
use App\Models\Project;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if (!config('appsignals.demo_seed')) {
            $this->command?->info('Demo seed disabled. Set APPSIGNALS_DEMO_SEED=true to enable.');
            return;
        }

        // Create a demo user for testing
        $demoUser = User::firstOrCreate(
            ['email' => 'demo@appsignals.dev'],
            [
                'name' => 'Demo User',
                'password' => bcrypt('password'),
            ]
        );

        $project = Project::firstOrCreate(
            ['user_id' => $demoUser->id, 'bundle_id' => 'com.appsignals.demo'],
            [
                'name' => 'Demo iOS App',
                'platform' => 'ios',
                'timezone' => 'UTC',
            ]
        );

        $existingKey = $project->apiKeys()->first();

        if (!$existingKey) {
            $result = ApiKey::generate($project->id, 'Demo iOS Key');
            $this->command?->info("Demo API key: {$result['key']}");
        }

        $this->call(DemoMetricsSeeder::class);
    }
}
