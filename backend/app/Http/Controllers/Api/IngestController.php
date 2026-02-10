<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessEventBatch;
use App\Models\Crash;
use App\Models\Event;
use App\Models\SessionReplay;
use App\Models\ReplayFrame;
use App\Services\GeoIpService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class IngestController extends Controller
{
    public function __construct(
        protected GeoIpService $geoIpService
    ) {
    }

    /**
     * Ingest a batch of events from the SDK.
     * 
     * POST /api/v1/ingest
     */
    public function ingest(Request $request): Response
    {
        $validated = $request->validate([
            'batch_id' => 'required|uuid',
            'sent_at' => 'required|date',
            'events' => 'required|array|max:500',
            'events.*.event_id' => 'required|uuid',
            'events.*.name' => 'required|string|max:100',
            'events.*.timestamp' => 'required|numeric',
            'events.*.properties' => 'nullable|array',
            // Context
            'context.os_version' => 'nullable|string|max:20',
            'context.device_model' => 'nullable|string|max:100',
            'context.app_version' => 'nullable|string|max:50',
            'context.device_id' => 'required|string|max:255',
            'context.user_id' => 'nullable|string|max:255',
            'context.session_id' => 'required|string|max:255',
        ]);

        $projectId = $request->attributes->get('project_id');
        $receivedAt = now();

        // Enrich with GeoIP
        $geoData = $this->geoIpService->lookup($request->ip());

        // Dispatch to queue for processing
        ProcessEventBatch::dispatch([
            'project_id' => $projectId,
            'batch_id' => $validated['batch_id'],
            'events' => $validated['events'],
            'context' => $validated['context'] ?? [],
            'geo' => $geoData,
            'received_at' => $receivedAt->toISOString(),
        ]);

        return response()->noContent();
    }

    /**
     * Submit a crash report from the SDK.
     * 
     * POST /api/v1/crash
     */
    public function crash(Request $request): Response
    {
        $validated = $request->validate([
            'crash_id' => 'required|uuid',
            'timestamp' => 'required|numeric',
            'exception_type' => 'required|string|max:100',
            'exception_message' => 'nullable|string|max:1000',
            'stack_trace' => 'required|string',
            // Context
            'context.os_version' => 'nullable|string|max:20',
            'context.device_model' => 'nullable|string|max:100',
            'context.app_version' => 'required|string|max:50',
            'context.app_build' => 'nullable|string|max:50',
            'context.device_id' => 'required|string|max:255',
            'context.user_id' => 'nullable|string|max:255',
            'context.session_id' => 'nullable|string|max:255',
        ]);

        $projectId = $request->attributes->get('project_id');
        $context = $validated['context'] ?? [];

        $crash = Crash::create([
            'project_id' => $projectId,
            'crash_id' => $validated['crash_id'],
            'crash_group_hash' => Crash::generateGroupHash(
                $validated['exception_type'],
                $validated['stack_trace']
            ),
            'user_id' => $context['user_id'] ?? null,
            'device_id' => $context['device_id'],
            'session_id' => $context['session_id'] ?? null,
            'exception_type' => $validated['exception_type'],
            'exception_message' => $validated['exception_message'] ?? null,
            'stack_trace' => $validated['stack_trace'],
            'os_version' => $context['os_version'] ?? null,
            'device_model' => $context['device_model'] ?? null,
            'app_version' => $context['app_version'],
            'app_build' => $context['app_build'] ?? null,
            'occurred_at' => \Carbon\Carbon::createFromTimestamp($validated['timestamp']),
        ]);

        // Broadcast to live dashboard if enabled
        \App\Events\NewCrashLogged::dispatch($crash);

        return response()->noContent();
    }

    /**
     * Submit session replay frames from the SDK.
     * 
     * POST /api/v1/replay
     */
    public function replay(Request $request): Response
    {
        $validated = $request->validate([
            'session_id' => 'required|string|max:255',
            'frames' => 'required|array|max:100',
            'frames.*.chunk_index' => 'required|integer|min:0',
            'frames.*.frame_type' => 'required|in:full,delta',
            'frames.*.timestamp' => 'required|numeric',
            'frames.*.wireframe' => 'required|array',
            // Context
            'context.user_id' => 'nullable|string|max:255',
        ]);

        $projectId = $request->attributes->get('project_id');
        $context = $validated['context'] ?? [];

        // Get or create the session replay record
        $sessionReplay = SessionReplay::firstOrCreate(
            [
                'project_id' => $projectId,
                'session_id' => $validated['session_id'],
            ],
            [
                'user_id' => $context['user_id'] ?? null,
                'started_at' => now(),
            ]
        );

        // Insert frames
        foreach ($validated['frames'] as $frame) {
            ReplayFrame::updateOrCreate(
                [
                    'session_replay_id' => $sessionReplay->id,
                    'chunk_index' => $frame['chunk_index'],
                ],
                [
                    'frame_type' => $frame['frame_type'],
                    'wireframe_data' => gzencode(json_encode($frame['wireframe']), 9),
                    'timestamp' => \Carbon\Carbon::createFromTimestamp($frame['timestamp']),
                ]
            );
        }

        // Update session metadata
        $sessionReplay->update([
            'ended_at' => now(),
            'screen_count' => $sessionReplay->frames()->count(),
        ]);

        return response()->noContent();
    }
}
