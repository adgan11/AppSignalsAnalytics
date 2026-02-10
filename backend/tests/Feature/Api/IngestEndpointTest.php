<?php

namespace Tests\Feature\Api;

use App\Jobs\ProcessEventBatch;
use App\Models\ApiKey;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

class IngestEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_ingest_requires_api_key(): void
    {
        $response = $this->postJson('/api/v1/ingest', []);

        $response->assertStatus(401)
            ->assertJson([
                'error' => 'API key required',
            ]);
    }

    public function test_ingest_dispatches_job_with_valid_key(): void
    {
        Queue::fake();

        [$project, $apiKey] = $this->createProjectWithKey();

        $payload = [
            'batch_id' => (string) Str::uuid(),
            'sent_at' => now()->toISOString(),
            'context' => [
                'device_id' => 'device_123',
                'session_id' => 'session_123',
                'app_version' => '1.0.0',
                'os_version' => '17.2',
            ],
            'events' => [
                [
                    'event_id' => (string) Str::uuid(),
                    'name' => 'button_clicked',
                    'timestamp' => now()->timestamp,
                    'properties' => [
                        'button' => 'signup',
                    ],
                ],
            ],
        ];

        $response = $this->withHeader('X-API-Key', $apiKey)
            ->postJson('/api/v1/ingest', $payload);

        $response->assertNoContent();
        Queue::assertPushed(ProcessEventBatch::class);
    }

    public function test_ingest_rejects_invalid_api_key(): void
    {
        $payload = [
            'batch_id' => (string) Str::uuid(),
            'sent_at' => now()->toISOString(),
            'context' => [
                'device_id' => 'device_123',
                'session_id' => 'session_123',
                'app_version' => '1.0.0',
                'os_version' => '17.2',
            ],
            'events' => [
                [
                    'event_id' => (string) Str::uuid(),
                    'name' => 'button_clicked',
                    'timestamp' => now()->timestamp,
                ],
            ],
        ];

        $response = $this->withHeader('X-API-Key', 'ok_live_invalid_key')
            ->postJson('/api/v1/ingest', $payload);

        $response->assertStatus(401)
            ->assertJson([
                'error' => 'Invalid API key',
            ]);
    }

    private function createProjectWithKey(): array
    {
        $user = User::factory()->create();
        $project = Project::create([
            'user_id' => $user->id,
            'name' => 'Test Project',
            'bundle_id' => 'com.test.' . Str::lower(Str::random(8)),
            'platform' => 'ios',
            'timezone' => 'UTC',
        ]);

        $apiKey = ApiKey::generate($project->id, 'Test Key');

        return [$project, $apiKey['key']];
    }
}
