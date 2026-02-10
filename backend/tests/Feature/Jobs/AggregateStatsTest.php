<?php

namespace Tests\Feature\Jobs;

use App\Jobs\AggregateStats;
use App\Models\DailyStat;
use App\Models\Event;
use App\Models\Project;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AggregateStatsTest extends TestCase
{
    use RefreshDatabase;

    public function test_aggregate_stats_job_creates_daily_stats(): void
    {
        $user = User::factory()->create();
        $project = Project::create([
            'user_id' => $user->id,
            'name' => 'Analytics Project',
            'bundle_id' => 'com.test.' . Str::lower(Str::random(8)),
            'platform' => 'ios',
            'timezone' => 'UTC',
        ]);

        $date = Carbon::parse('2026-01-05');
        $timestamp = $date->copy()->addHours(10);

        Event::factory()->create([
            'project_id' => $project->id,
            'event_name' => 'signup',
            'user_id' => 'user_1',
            'device_id' => 'device_1',
            'session_id' => 'session_1',
            'country_code' => 'US',
            'event_timestamp' => $timestamp,
            'received_at' => $timestamp,
        ]);

        Event::factory()->create([
            'project_id' => $project->id,
            'event_name' => 'signup',
            'user_id' => 'user_2',
            'device_id' => 'device_2',
            'session_id' => 'session_2',
            'country_code' => 'US',
            'event_timestamp' => $timestamp,
            'received_at' => $timestamp,
        ]);

        (new AggregateStats($project->id, $date->toDateString()))->handle();

        $stat = DailyStat::where('project_id', $project->id)
            ->where('event_name', 'signup')
            ->whereDate('date', $date->toDateString())
            ->whereNull('country_code')
            ->first();

        $this->assertNotNull($stat);
        $this->assertSame(2, $stat->event_count);
        $this->assertSame(2, $stat->unique_users);

        $countryStat = DailyStat::where('project_id', $project->id)
            ->where('event_name', 'signup')
            ->whereDate('date', $date->toDateString())
            ->where('country_code', 'US')
            ->first();

        $this->assertNotNull($countryStat);
        $this->assertSame(2, $countryStat->event_count);
    }
}
