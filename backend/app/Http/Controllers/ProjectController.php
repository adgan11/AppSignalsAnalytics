<?php

namespace App\Http\Controllers;

use App\Models\ApiKey;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class ProjectController extends Controller
{
    /**
     * Show the form for creating a new project.
     */
    public function create()
    {
        return view('dashboard.projects.create');
    }

    /**
     * Store a newly created project.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'bundle_id' => 'required|string|max:255|regex:/^[a-zA-Z0-9.-]+$/',
            'platform' => 'in:ios,android',
            'timezone' => 'nullable|timezone',
        ]);

        $project = $request->user()->projects()->create($validated);

        // Auto-create first API key
        $apiKeyData = ApiKey::generate($project->id, 'Default Key');

        return redirect()
            ->route('dashboard.settings', $project)
            ->with('success', 'Project created successfully!')
            ->with('new_api_key', $apiKeyData['key']);
    }

    /**
     * Update the specified project.
     */
    public function update(Request $request, Project $project)
    {
        $this->authorize('update', $project);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'platform' => 'nullable|in:ios,android',
            'timezone' => 'nullable|timezone',
            'data_retention_days' => 'nullable|integer|min:7|max:365',
            'settings.description' => 'nullable|string|max:1000',
            'settings.app_store_link' => 'nullable|url|max:255',
            'settings.website_url' => 'nullable|url|max:255',
            'settings.app_icon_url' => 'nullable|url|max:255',
            'settings.app_store_category' => 'nullable|string|max:100',
        ]);

        $project->fill(Arr::except($validated, ['settings']));

        if ($request->has('settings')) {
            $incomingSettings = $validated['settings'] ?? [];
            foreach ($incomingSettings as $key => $value) {
                if (is_string($value) && trim($value) === '') {
                    $incomingSettings[$key] = null;
                }
            }

            $project->settings = array_merge($project->settings ?? [], $incomingSettings);
        }

        $project->save();

        return back()->with('success', 'Project settings saved!');
    }

    /**
     * Remove the specified project.
     */
    public function destroy(Request $request, Project $project)
    {
        $this->authorize('delete', $project);

        $project->delete();

        return redirect()->route('dashboard')->with('success', 'Project deleted.');
    }
}
