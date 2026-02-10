<?php

namespace App\Jobs;

use App\Events\NewEventLogged;
use App\Models\Event;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessEventBatch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected array $batchData
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $projectId = $this->batchData['project_id'];
        $events = $this->batchData['events'];
        $context = $this->batchData['context'] ?? [];
        $geo = $this->batchData['geo'] ?? [];
        $receivedAt = Carbon::parse($this->batchData['received_at']);

        $rows = [];

        foreach ($events as $event) {
            $rows[] = [
                'project_id' => $projectId,
                'event_id' => $event['event_id'],
                'event_name' => $event['name'],
                'user_id' => $context['user_id'] ?? null,
                'device_id' => $context['device_id'] ?? '',
                'session_id' => $context['session_id'] ?? '',
                'properties' => isset($event['properties']) ? json_encode($event['properties']) : null,
                'os_version' => $context['os_version'] ?? null,
                'device_model' => $context['device_model'] ?? null,
                'app_version' => $context['app_version'] ?? null,
                'country_code' => $geo['country_code'] ?? null,
                'event_timestamp' => Carbon::createFromTimestamp($event['timestamp']),
                'received_at' => $receivedAt,
            ];
        }

        // Bulk insert for performance
        try {
            Event::insert($rows);

            // Broadcast update
            NewEventLogged::dispatch(
                $projectId,
                count($rows),
                array_slice($rows, 0, 10)
            );

            Log::info("Processed batch of " . count($rows) . " events for project {$projectId}");
        } catch (\Exception $e) {
            Log::error("Failed to process event batch: " . $e->getMessage());
            throw $e;
        }
    }
}
