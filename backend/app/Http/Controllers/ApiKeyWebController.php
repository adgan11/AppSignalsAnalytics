<?php

namespace App\Http\Controllers;

use App\Models\ApiKey;
use App\Models\Project;
use Illuminate\Http\Request;

class ApiKeyWebController extends Controller
{
    /**
     * Store a newly created API key.
     */
    public function store(Request $request, Project $project)
    {
        $this->authorize('update', $project);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $result = ApiKey::generate($project->id, $validated['name']);

        if ($request->wantsJson()) {
            return response()->json([
                'data' => [
                    'id' => $result['model']->id,
                    'name' => $result['model']->name,
                    'key' => $result['key'],
                    'key_prefix' => $result['model']->key_prefix,
                ],
                'message' => 'API key created successfully.',
            ], 201);
        }

        return back()
            ->with('success', 'API key created!')
            ->with('new_api_key', $result['key']);
    }

    /**
     * Remove the specified API key.
     */
    public function destroy(Request $request, ApiKey $apiKey)
    {
        $this->authorize('update', $apiKey->project);

        $apiKey->delete();

        if ($request->wantsJson()) {
            return response()->json(['message' => 'API key deleted.']);
        }

        return back()->with('success', 'API key revoked.');
    }
}

