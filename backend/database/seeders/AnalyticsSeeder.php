<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\Project;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AnalyticsSeeder extends Seeder
{
    /**
     * Seed realistic analytics data for testing charts.
     */
    public function run(): void
    {
        // Get all projects and seed data for each
        $projects = Project::all();

        if ($projects->isEmpty()) {
            $this->command->warn('No projects found. Please create a project first.');
            return;
        }

        foreach ($projects as $project) {
            $this->seedProjectData($project);
        }
    }

    /**
     * Seed data for a specific project.
     */
    private function seedProjectData(Project $project): void
    {
        $this->command->info("Seeding data for project: {$project->name} (ID: {$project->id})");

        // Delete existing events for this project to avoid duplicates
        Event::where('project_id', $project->id)->delete();

        // Configuration
        $totalUsers = 200;           // Number of unique users
        $daysBack = 90;              // Seed data for last 90 days
        $eventsPerUserPerDay = 5;    // Average events per active user per day

        // Device models
        $deviceModels = [
            'iPhone 15 Pro' => 25,
            'iPhone 15' => 20,
            'iPhone 14 Pro' => 15,
            'iPhone 14' => 12,
            'iPhone 13' => 10,
            'iPhone 12' => 8,
            'iPad Pro' => 5,
            'iPad Air' => 3,
            'iPhone SE' => 2,
        ];

        // OS versions
        $osVersions = [
            '17.2' => 30,
            '17.1' => 25,
            '17.0' => 15,
            '16.7' => 12,
            '16.6' => 10,
            '16.5' => 8,
        ];

        // Countries
        $countries = [
            'US' => 35,
            'GB' => 15,
            'DE' => 12,
            'FR' => 10,
            'CA' => 8,
            'AU' => 7,
            'JP' => 5,
            'IN' => 5,
            'BR' => 3,
        ];

        // App versions
        $appVersions = [
            '2.1.0' => 40,
            '2.0.5' => 30,
            '2.0.0' => 20,
            '1.9.0' => 10,
        ];

        // Event types
        $eventTypes = [
            'session_start' => 20,
            'app_open' => 15,
            'screen_viewed' => 20,
            'button_clicked' => 15,
            'purchase_completed' => 5,
            'signup_completed' => 3,
            'feature_used' => 10,
            'settings_changed' => 5,
            'content_shared' => 4,
            'notification_received' => 3,
        ];

        // Create user pool with their first seen dates (staggered acquisition)
        $users = [];
        for ($i = 1; $i <= $totalUsers; $i++) {
            $userId = 'user_' . str_pad($i, 4, '0', STR_PAD_LEFT);
            $deviceId = 'device_' . strtoupper(substr(md5($userId), 0, 8));

            // Stagger first seen dates across the time range
            $firstSeenDaysAgo = rand(1, $daysBack);
            $firstSeen = Carbon::now()->subDays($firstSeenDaysAgo);

            // Determine if user is "returning" (appears on multiple days)
            $isReturning = rand(1, 100) <= 60; // 60% are returning users
            $activeDays = $isReturning ? rand(2, min(15, $firstSeenDaysAgo)) : 1;

            $users[] = [
                'user_id' => $userId,
                'device_id' => $deviceId,
                'first_seen' => $firstSeen,
                'active_days' => $activeDays,
                'device_model' => $this->weightedRandom($deviceModels),
                'os_version' => $this->weightedRandom($osVersions),
                'country_code' => $this->weightedRandom($countries),
                'app_version' => $this->weightedRandom($appVersions),
            ];
        }

        $events = [];
        $batchSize = 500;
        $totalEvents = 0;

        foreach ($users as $user) {
            // Generate active dates for this user
            $activeDates = [$user['first_seen']->format('Y-m-d')];

            if ($user['active_days'] > 1) {
                $daysAgoFirstSeen = (int) $user['first_seen']->diffInDays(Carbon::now());
                if ($daysAgoFirstSeen > 1) {
                    $possibleDays = range(0, $daysAgoFirstSeen - 1);
                    shuffle($possibleDays);

                    for ($d = 0; $d < min($user['active_days'] - 1, count($possibleDays)); $d++) {
                        $activeDates[] = Carbon::now()->subDays($possibleDays[$d])->format('Y-m-d');
                    }
                }
            }

            foreach ($activeDates as $dateStr) {
                $date = Carbon::parse($dateStr);
                $numEvents = rand(3, $eventsPerUserPerDay + 3);

                for ($e = 0; $e < $numEvents; $e++) {
                    $eventName = $this->weightedRandom($eventTypes);
                    $timestamp = $date->copy()->addHours(rand(6, 23))->addMinutes(rand(0, 59))->addSeconds(rand(0, 59));

                    // Skip future events
                    if ($timestamp->isFuture()) {
                        continue;
                    }

                    $events[] = [
                        'project_id' => $project->id,
                        'event_id' => (string) \Illuminate\Support\Str::uuid(),
                        'event_name' => $eventName,
                        'user_id' => $user['user_id'],
                        'device_id' => $user['device_id'],
                        'session_id' => 'sess_' . substr(md5($user['user_id'] . $dateStr), 0, 12),
                        'event_timestamp' => $timestamp,
                        'received_at' => $timestamp,
                        'device_model' => $user['device_model'],
                        'os_version' => $user['os_version'],
                        'app_version' => $user['app_version'],
                        'country_code' => $user['country_code'],
                        'properties' => json_encode($this->generateEventProperties($eventName)),
                    ];

                    // Insert in batches
                    if (count($events) >= $batchSize) {
                        Event::insert($events);
                        $totalEvents += count($events);
                        $events = [];
                    }
                }
            }
        }

        // Insert remaining events
        if (!empty($events)) {
            Event::insert($events);
            $totalEvents += count($events);
        }

        $this->command->info("  Created {$totalEvents} events for {$totalUsers} users");
    }

    /**
     * Generate random event properties based on event type.
     */
    private function generateEventProperties(string $eventName): array
    {
        $properties = [];

        switch ($eventName) {
            case 'screen_viewed':
                $screens = ['HomeScreen', 'ProfileScreen', 'SettingsScreen', 'SearchScreen', 'DetailScreen'];
                $properties['screen_name'] = $screens[array_rand($screens)];
                break;

            case 'button_clicked':
                $buttons = ['submit', 'cancel', 'next', 'back', 'menu', 'share', 'like'];
                $properties['button_name'] = $buttons[array_rand($buttons)];
                break;

            case 'purchase_completed':
                $properties['amount'] = rand(1, 100) + (rand(0, 99) / 100);
                $properties['currency'] = 'USD';
                $properties['product_id'] = 'prod_' . rand(100, 999);
                break;

            case 'feature_used':
                $features = ['dark_mode', 'notifications', 'sync', 'export', 'import'];
                $properties['feature_name'] = $features[array_rand($features)];
                break;

            case 'content_shared':
                $platforms = ['twitter', 'facebook', 'instagram', 'email', 'copy_link'];
                $properties['platform'] = $platforms[array_rand($platforms)];
                break;
        }

        return $properties;
    }

    /**
     * Select a random item based on weights.
     */
    private function weightedRandom(array $items): string
    {
        $totalWeight = array_sum($items);
        $random = rand(1, $totalWeight);
        $current = 0;

        foreach ($items as $item => $weight) {
            $current += $weight;
            if ($random <= $current) {
                return (string) $item;
            }
        }

        return (string) array_key_first($items);
    }
}
