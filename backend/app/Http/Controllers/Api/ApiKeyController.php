<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiKeyController extends Controller
{
    /**
     * List all API keys for a project.
     */
    public function index(Request $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        $apiKeys = $project->apiKeys()
            ->select(['id', 'name', 'key_prefix', 'last_used_at', 'expires_at', 'created_at'])
            ->latest()
            ->get();

        return response()->json([
            'data' => $apiKeys,
        ]);
    }

    /**
     * Create a new API key for a project.
     */
    public function store(Request $request, Project $project): JsonResponse
    {
        $this->authorize('update', $project);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'expires_at' => 'nullable|date|after:now',
        ]);

        $result = ApiKey::generate($project->id, $validated['name']);

        if (isset($validated['expires_at'])) {
            $result['model']->update(['expires_at' => $validated['expires_at']]);
        }

        return response()->json([
            'data' => [
                'id' => $result['model']->id,
                'name' => $result['model']->name,
                'key' => $result['key'], // Only shown once!
                'key_prefix' => $result['model']->key_prefix,
                'expires_at' => $result['model']->expires_at,
            ],
            'message' => 'API key created. Please copy it now - it won\'t be shown again.',
        ], 201);
    }

    /**
     * Delete an API key.
     */
    public function destroy(Request $request, ApiKey $apiKey): JsonResponse
    {
        $this->authorize('update', $apiKey->project);

        $apiKey->delete();

        return response()->json([
            'message' => 'API key deleted successfully.',
        ]);
    }
}
