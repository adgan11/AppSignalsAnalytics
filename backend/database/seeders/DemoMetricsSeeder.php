<?php

namespace Database\Seeders;

use App\Models\Crash;
use App\Models\Event;
use App\Models\Project;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoMetricsSeeder extends Seeder
{
    public function run(): void
    {
        if (!config('appsignals.demo_seed')) {
            return;
        }

        $project = Project::where('bundle_id', 'com.appsignals.demo')->first();

        if (!$project) {
            $this->command?->warn('DemoMetricsSeeder: Demo project not found.');
            return;
        }

        $this->seedEvents($project);
        $this->seedCrashes($project);
    }

    private function seedEvents(Project $project): void
    {
        if (Event::where('project_id', $project->id)->exists()) {
            $this->command?->info('DemoMetricsSeeder: Events already exist, skipping event seed.');
            return;
        }

        $totalUsers = 120;
        $daysBack = 60;
        $eventsPerUserPerDay = 4;
        $batchSize = 500;

        $deviceModels = [
            'iPhone 15 Pro' => 20,
            'iPhone 15' => 18,
            'iPhone 14 Pro' => 14,
            'iPhone 14' => 10,
            'iPhone 13' => 9,
            'iPhone 12' => 6,
            'iPad Pro' => 5,
            'iPad Air' => 4,
            'iPhone SE' => 3,
        ];

        $osVersions = [
            '17.2' => 26,
            '17.1.2' => 18,
            '17.0' => 12,
            '16.7' => 10,
            '16.6' => 8,
            '16.5' => 6,
        ];

        $appVersions = [
            '2.2.0' => 35,
            '2.1.1' => 25,
            '2.1.0' => 20,
            '2.0.5' => 12,
            '2.0.0' => 8,
        ];

        $appBuilds = [
            '220' => 35,
            '211' => 25,
            '210' => 20,
            '205' => 12,
            '200' => 8,
        ];

        $sdkVersions = [
            '1.0.0' => 70,
            '1.1.0' => 30,
        ];

        $countryCodes = [
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

        $screenSizes = [
            ['width' => 1170, 'height' => 2532, 'weight' => 30],
            ['width' => 1290, 'height' => 2796, 'weight' => 20],
            ['width' => 1125, 'height' => 2436, 'weight' => 15],
            ['width' => 828, 'height' => 1792, 'weight' => 10],
            ['width' => 1536, 'height' => 2048, 'weight' => 8],
            ['width' => 2048, 'height' => 2732, 'weight' => 7],
            ['width' => 750, 'height' => 1334, 'weight' => 5],
            ['width' => 1284, 'height' => 2778, 'weight' => 5],
        ];

        $colorSchemes = [
            'light' => 60,
            'dark' => 40,
        ];

        $orientations = [
            'portrait' => 80,
            'landscape' => 20,
        ];

        $layoutDirections = [
            'ltr' => 92,
            'rtl' => 8,
        ];

        $contentSizes = [
            'UICTContentSizeCategoryM' => 20,
            'UICTContentSizeCategoryL' => 30,
            'UICTContentSizeCategoryXL' => 25,
            'UICTContentSizeCategoryXXL' => 15,
            'UICTContentSizeCategoryXXXL' => 10,
        ];

        $languages = [
            'en' => 40,
            'es' => 15,
            'fr' => 12,
            'de' => 10,
            'pt' => 8,
            'ja' => 6,
            'ar' => 5,
            'hi' => 4,
        ];

        $preferredLanguages = [
            'en-US' => 35,
            'en-GB' => 10,
            'es-ES' => 12,
            'fr-FR' => 10,
            'de-DE' => 8,
            'pt-BR' => 8,
            'ja-JP' => 7,
            'ar-SA' => 5,
            'hi-IN' => 5,
        ];

        $eventTypes = [
            'session_start' => 18,
            'app_open' => 15,
            'screen_viewed' => 18,
            'button_clicked' => 14,
            'purchase_completed' => 6,
            'signup_completed' => 4,
            'feature_used' => 10,
            'settings_changed' => 5,
            'content_shared' => 4,
            'device_context' => 4,
            'user_input_error' => 1,
            'app_state_error' => 1,
            'exception' => 1,
        ];

        $events = [];
        $totalEvents = 0;

        for ($i = 1; $i <= $totalUsers; $i++) {
            $userId = 'demo_user_' . str_pad((string) $i, 4, '0', STR_PAD_LEFT);
            $deviceId = 'demo_device_' . substr(md5($userId), 0, 10);

            $firstSeenDaysAgo = rand(1, $daysBack);
            $firstSeen = Carbon::now()->subDays($firstSeenDaysAgo);
            $isReturning = rand(1, 100) <= 65;
            $activeDays = $isReturning ? rand(2, min(12, $firstSeenDaysAgo)) : 1;

            $screen = $this->weightedRandomFromList($screenSizes);
            $country = $this->weightedRandom($countryCodes);
            $profile = [
                'user_id' => $userId,
                'device_id' => $deviceId,
                'device_model' => $this->weightedRandom($deviceModels),
                'os_version' => $this->weightedRandom($osVersions),
                'app_version' => $this->weightedRandom($appVersions),
                'app_build' => $this->weightedRandom($appBuilds),
                'sdk_version' => $this->weightedRandom($sdkVersions),
                'country_code' => $country,
                'screen_width' => $screen['width'],
                'screen_height' => $screen['height'],
                'orientation' => $this->weightedRandom($orientations),
                'color_scheme' => $this->weightedRandom($colorSchemes),
                'layout_direction' => $this->weightedRandom($layoutDirections),
                'preferred_content_size' => $this->weightedRandom($contentSizes),
                'app_language' => $this->weightedRandom($languages),
                'preferred_language' => $this->weightedRandom($preferredLanguages),
                'region' => $country,
            ];

            $activeDates = [$firstSeen->format('Y-m-d')];
            if ($activeDays > 1) {
                $daysAgoFirstSeen = $firstSeen->diffInDays(Carbon::now());
                if ($daysAgoFirstSeen > 1) {
                    $possibleDays = [];
                    for ($day = 0; $day < $daysAgoFirstSeen; $day++) {
                        $possibleDays[] = $day;
                    }
                    shuffle($possibleDays);
                    for ($d = 0; $d < min($activeDays - 1, count($possibleDays)); $d++) {
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

                    if ($timestamp->isFuture()) {
                        continue;
                    }

                    $properties = $this->generateEventProperties($eventName, $profile);

                    $events[] = [
                        'project_id' => $project->id,
                        'event_id' => (string) Str::uuid(),
                        'event_name' => $eventName,
                        'user_id' => $profile['user_id'],
                        'device_id' => $profile['device_id'],
                        'session_id' => 'sess_' . substr(md5($profile['user_id'] . $dateStr), 0, 12),
                        'event_timestamp' => $timestamp,
                        'received_at' => $timestamp,
                        'device_model' => $profile['device_model'],
                        'os_version' => $profile['os_version'],
                        'app_version' => $profile['app_version'],
                        'country_code' => $profile['country_code'],
                        'properties' => json_encode($properties),
                    ];

                    if (count($events) >= $batchSize) {
                        Event::insert($events);
                        $totalEvents += count($events);
                        $events = [];
                    }
                }
            }
        }

        if (!empty($events)) {
            Event::insert($events);
            $totalEvents += count($events);
        }

        $this->command?->info("DemoMetricsSeeder: Created {$totalEvents} events for {$totalUsers} users.");
    }

    private function seedCrashes(Project $project): void
    {
        $existing = Crash::where('project_id', $project->id)->count();
        if ($existing > 0) {
            $this->command?->info('DemoMetricsSeeder: Crashes already exist, skipping crash seed.');
            return;
        }

        $exceptionTypes = [
            'FatalError',
            'IndexOutOfRange',
            'NullReference',
            'NetworkTimeout',
            'InvalidState',
        ];

        $deviceModels = ['iPhone 15 Pro', 'iPhone 14', 'iPhone 13', 'iPad Pro'];
        $osVersions = ['17.2', '17.1.2', '16.7', '16.5'];
        $appVersions = ['2.2.0', '2.1.1', '2.1.0', '2.0.5'];
        $appBuilds = ['220', '211', '210', '205'];

        $crashes = [];
        $totalCrashes = 0;

        for ($i = 0; $i < 40; $i++) {
            $exceptionType = $exceptionTypes[array_rand($exceptionTypes)];
            $stackTrace = "0  AppSignalsDemo 0x0000000100a1b2c3 \n1  UIKitCore 0x00000001a2b3c4d5 \n2  SwiftUI 0x00000001b2c3d4e5";
            $occurredAt = Carbon::now()->subDays(rand(0, 29))->addMinutes(rand(0, 1440));

            $crashes[] = [
                'project_id' => $project->id,
                'crash_id' => (string) Str::uuid(),
                'crash_group_hash' => Crash::generateGroupHash($exceptionType, $stackTrace),
                'user_id' => 'demo_user_' . str_pad((string) rand(1, 120), 4, '0', STR_PAD_LEFT),
                'device_id' => 'demo_device_' . substr(md5((string) rand(1, 120)), 0, 10),
                'session_id' => 'sess_' . substr(md5((string) rand(1, 120)), 0, 12),
                'exception_type' => $exceptionType,
                'exception_message' => 'Demo crash for metrics charts',
                'stack_trace' => $stackTrace,
                'is_symbolicated' => false,
                'symbolicated_trace' => null,
                'os_version' => $osVersions[array_rand($osVersions)],
                'device_model' => $deviceModels[array_rand($deviceModels)],
                'app_version' => $appVersions[array_rand($appVersions)],
                'app_build' => $appBuilds[array_rand($appBuilds)],
                'occurred_at' => $occurredAt,
                'created_at' => $occurredAt,
                'updated_at' => $occurredAt,
            ];
        }

        Crash::insert($crashes);
        $totalCrashes += count($crashes);

        $this->command?->info("DemoMetricsSeeder: Created {$totalCrashes} crashes.");
    }

    private function generateEventProperties(string $eventName, array $profile): array
    {
        $properties = [
            'screen_width' => $profile['screen_width'],
            'screen_height' => $profile['screen_height'],
            'orientation' => $profile['orientation'],
            'color_scheme' => $profile['color_scheme'],
            'platform' => 'iOS',
            'os_name' => 'iOS',
            'app_language' => $profile['app_language'],
            'preferred_language' => $profile['preferred_language'],
            'layout_direction' => $profile['layout_direction'],
            'preferred_content_size' => $profile['preferred_content_size'],
            'bold_text' => rand(0, 1) === 1,
            'reduce_motion' => rand(0, 1) === 1,
            'reduce_transparency' => rand(0, 1) === 1,
            'darker_system_colors' => rand(0, 1) === 1,
            'differentiate_without_color' => rand(0, 1) === 1,
            'invert_colors' => rand(0, 1) === 1,
            'sdk_version' => $profile['sdk_version'],
            'app_build' => $profile['app_build'],
            'region' => $profile['region'],
        ];

        switch ($eventName) {
            case 'screen_viewed':
                $screens = ['Home', 'Profile', 'Settings', 'Search', 'Detail'];
                $properties['screen_name'] = $screens[array_rand($screens)];
                break;
            case 'button_clicked':
                $properties['button_id'] = 'demo_button_' . rand(1, 5);
                $properties['screen'] = 'home';
                break;
            case 'purchase_completed':
                $properties['price'] = rand(5, 50) + (rand(0, 99) / 100);
                $properties['currency'] = 'USD';
                $properties['product_id'] = 'prod_' . rand(100, 999);
                break;
            case 'signup_completed':
                $properties['method'] = 'email';
                break;
            case 'feature_used':
                $features = ['dark_mode', 'notifications', 'sync', 'export'];
                $properties['feature_name'] = $features[array_rand($features)];
                break;
            case 'content_shared':
                $platforms = ['twitter', 'facebook', 'email', 'copy_link'];
                $properties['share_platform'] = $platforms[array_rand($platforms)];
                break;
            case 'user_input_error':
                $properties['error_category'] = 'user_input';
                $properties['error_type'] = 'invalid_email';
                $properties['field'] = 'email';
                break;
            case 'app_state_error':
                $properties['error_category'] = 'app_state';
                $properties['error_type'] = 'missing_state';
                $properties['state'] = 'checkout';
                break;
            case 'exception':
                $properties['error_category'] = 'exception';
                $properties['error_type'] = 'nil_reference';
                break;
        }

        return $properties;
    }

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

    private function weightedRandomFromList(array $items): array
    {
        $totalWeight = array_sum(array_column($items, 'weight'));
        $random = rand(1, $totalWeight);
        $current = 0;

        foreach ($items as $item) {
            $current += $item['weight'];
            if ($random <= $current) {
                return $item;
            }
        }

        return $items[0];
    }
}
