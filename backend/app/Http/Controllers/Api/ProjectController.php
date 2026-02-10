<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    /**
     * List all projects for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $projects = $request->user()
            ->projects()
            ->withCount(['events', 'crashes', 'sessionReplays'])
            ->latest()
            ->get();

        return response()->json([
            'data' => $projects,
        ]);
    }

    /**
     * Create a new project.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'bundle_id' => 'required|string|max:255|regex:/^[a-zA-Z0-9.-]+$/',
            'platform' => 'in:ios,android',
            'timezone' => 'nullable|timezone',
            'data_retention_days' => 'nullable|integer|min:7|max:365',
        ]);

        $project = $request->user()->projects()->create($validated);

        return response()->json([
            'data' => $project,
            'message' => 'Project created successfully.',
        ], 201);
    }

    /**
     * Get a specific project.
     */
    public function show(Request $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        $project->loadCount(['events', 'crashes', 'sessionReplays', 'apiKeys']);

        return response()->json([
            'data' => $project,
        ]);
    }

    /**
     * Update a project.
     */
    public function update(Request $request, Project $project): JsonResponse
    {
        $this->authorize('update', $project);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'timezone' => 'nullable|timezone',
            'data_retention_days' => 'nullable|integer|min:7|max:365',
            'settings' => 'nullable|array',
        ]);

        $project->update($validated);

        return response()->json([
            'data' => $project->fresh(),
            'message' => 'Project updated successfully.',
        ]);
    }

    /**
     * Delete a project and all associated data.
     */
    public function destroy(Request $request, Project $project): JsonResponse
    {
        $this->authorize('delete', $project);

        $project->delete();

        return response()->json([
            'message' => 'Project deleted successfully.',
        ]);
    }
}
