<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\SessionReplay;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReplayController extends Controller
{
    /**
     * List session replays for a project.
     */
    public function index(Request $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        $validated = $request->validate([
            'user_id' => 'nullable|string|max:255',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'min_duration' => 'nullable|integer|min:0',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = SessionReplay::where('project_id', $project->id)
            ->withCount('frames');

        if (isset($validated['user_id'])) {
            $query->where('user_id', $validated['user_id']);
        }

        if (isset($validated['start_date'])) {
            $query->where('started_at', '>=', $validated['start_date']);
        }

        if (isset($validated['end_date'])) {
            $query->where('started_at', '<=', $validated['end_date']);
        }

        if (isset($validated['min_duration'])) {
            $query->where('duration_seconds', '>=', $validated['min_duration']);
        }

        $replays = $query
            ->orderByDesc('started_at')
            ->paginate($validated['per_page'] ?? 25);

        return response()->json($replays);
    }

    /**
     * Get session replay details and frames.
     */
    public function show(Request $request, Project $project, string $sessionId): JsonResponse
    {
        $this->authorize('view', $project);

        $replay = SessionReplay::where('project_id', $project->id)
            ->where('session_id', $sessionId)
            ->with([
                'frames' => function ($query) {
                    $query->orderBy('chunk_index');
                }
            ])
            ->first();

        if (!$replay) {
            return response()->json(['message' => 'Session replay not found'], 404);
        }

        // Decompress wireframe data for playback
        $frames = $replay->frames->map(function ($frame) {
            return [
                'chunk_index' => $frame->chunk_index,
                'frame_type' => $frame->frame_type,
                'timestamp' => $frame->timestamp,
                'wireframe' => $frame->wireframe, // Uses accessor to decompress
            ];
        });

        return response()->json([
            'data' => [
                'session_id' => $replay->session_id,
                'user_id' => $replay->user_id,
                'started_at' => $replay->started_at,
                'ended_at' => $replay->ended_at,
                'duration_seconds' => $replay->duration_seconds,
                'screen_count' => $replay->screen_count,
                'frames' => $frames,
            ],
        ]);
    }
}
