<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Crash;
use App\Models\DsymFile;
use App\Models\Project;
use App\Services\DsymUuidExtractor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CrashController extends Controller
{
    /**
     * List crash groups for a project.
     */
    public function index(Request $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        $validated = $request->validate([
            'status' => 'nullable|in:all,unresolved,resolved',
            'app_version' => 'nullable|string|max:50',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = Crash::where('project_id', $project->id)
            ->grouped();

        if (isset($validated['app_version'])) {
            $query->where('app_version', $validated['app_version']);
        }

        // Get paginated crash groups
        $crashGroups = $query->paginate($validated['per_page'] ?? 25);

        return response()->json($crashGroups);
    }

    /**
     * Get details for a specific crash group.
     */
    public function show(Request $request, Project $project, string $crashGroupHash): JsonResponse
    {
        $this->authorize('view', $project);

        // Get all crashes in this group
        $crashes = Crash::where('project_id', $project->id)
            ->where('crash_group_hash', $crashGroupHash)
            ->orderByDesc('occurred_at')
            ->limit(100)
            ->get();

        if ($crashes->isEmpty()) {
            return response()->json(['message' => 'Crash group not found'], 404);
        }

        // Get the most recent crash as the representative
        $representative = $crashes->first();

        // Get affected versions
        $affectedVersions = $crashes->pluck('app_version')->unique()->values();

        // Get affected devices count
        $affectedDevices = $crashes->pluck('device_id')->unique()->count();

        return response()->json([
            'data' => [
                'crash_group_hash' => $crashGroupHash,
                'exception_type' => $representative->exception_type,
                'exception_message' => $representative->exception_message,
                'stack_trace' => $representative->is_symbolicated
                    ? $representative->symbolicated_trace
                    : $representative->stack_trace,
                'is_symbolicated' => $representative->is_symbolicated,
                'total_occurrences' => $crashes->count(),
                'affected_devices' => $affectedDevices,
                'affected_versions' => $affectedVersions,
                'first_seen' => $crashes->min('occurred_at'),
                'last_seen' => $representative->occurred_at,
                'recent_crashes' => $crashes->take(10)->map(fn($c) => [
                    'id' => $c->id,
                    'occurred_at' => $c->occurred_at,
                    'device_model' => $c->device_model,
                    'os_version' => $c->os_version,
                    'app_version' => $c->app_version,
                ]),
            ],
        ]);
    }

    /**
     * Upload a dSYM file for symbolication.
     */
    public function uploadDsym(Request $request, Project $project, DsymUuidExtractor $extractor): JsonResponse
    {
        $this->authorize('update', $project);

        $validated = $request->validate([
            'file' => 'required|file|max:512000', // 500MB max
            'app_version' => 'required|string|max:50',
            'build_number' => 'required|string|max:50',
        ]);

        $file = $request->file('file');

        // Store the file
        $path = $file->store("dsyms/{$project->id}", 'local');

        $absolutePath = Storage::disk('local')->path($path);
        $uuid = $extractor->extract($absolutePath);

        if (!$uuid) {
            Storage::disk('local')->delete($path);
            return response()->json([
                'message' => 'Unable to extract dSYM UUID. Please upload a valid .dSYM.zip file.',
            ], 422);
        }

        $dsymFile = DsymFile::updateOrCreate(
            [
                'project_id' => $project->id,
                'uuid' => $uuid,
            ],
            [
                'app_version' => $validated['app_version'],
                'build_number' => $validated['build_number'],
                'file_path' => $path,
                'file_size' => $file->getSize(),
            ]
        );

        return response()->json([
            'data' => $dsymFile,
            'message' => 'dSYM file uploaded successfully.',
        ], 201);
    }
}
