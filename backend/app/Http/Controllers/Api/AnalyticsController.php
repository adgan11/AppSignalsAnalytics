<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DailyStat;
use App\Models\Event;
use App\Models\Project;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    /**
     * Get overview stats for the dashboard.
     */
    public function overview(Request $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        $validated = $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $startDate = Carbon::parse($validated['start_date'] ?? now()->subDays(30))->startOfDay();
        $endDate = Carbon::parse($validated['end_date'] ?? now())->endOfDay();

        // Get aggregated stats from daily_stats table
        $stats = DailyStat::where('project_id', $project->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->whereNull('country_code') // Get non-dimensional aggregates
            ->whereNull('device_model')
            ->whereNull('app_version')
            ->selectRaw('
                SUM(event_count) as total_events,
                SUM(unique_users) as total_users,
                SUM(unique_devices) as total_devices,
                SUM(unique_sessions) as total_sessions
            ')
            ->first();

        // Get daily breakdown for chart
        $dailyData = DailyStat::where('project_id', $project->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->whereNull('country_code')
            ->whereNull('device_model')
            ->whereNull('app_version')
            ->selectRaw('date, SUM(event_count) as events, SUM(unique_users) as users')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Get top events
        $topEvents = DailyStat::where('project_id', $project->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->whereNull('country_code')
            ->selectRaw('event_name, SUM(event_count) as count')
            ->groupBy('event_name')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        return response()->json([
            'data' => [
                'totals' => [
                    'events' => (int) ($stats->total_events ?? 0),
                    'users' => (int) ($stats->total_users ?? 0),
                    'devices' => (int) ($stats->total_devices ?? 0),
                    'sessions' => (int) ($stats->total_sessions ?? 0),
                ],
                'daily' => $dailyData,
                'top_events' => $topEvents,
                'period' => [
                    'start' => $startDate->toDateString(),
                    'end' => $endDate->toDateString(),
                ],
            ],
        ]);
    }

    /**
     * Query raw events with filters.
     */
    public function events(Request $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        $validated = $request->validate([
            'event_name' => 'nullable|string|max:100',
            'user_id' => 'nullable|string|max:255',
            'session_id' => 'nullable|string|max:255',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = Event::where('project_id', $project->id);

        if (isset($validated['event_name'])) {
            $query->where('event_name', $validated['event_name']);
        }

        if (isset($validated['user_id'])) {
            $query->where('user_id', $validated['user_id']);
        }

        if (isset($validated['session_id'])) {
            $query->where('session_id', $validated['session_id']);
        }

        if (isset($validated['start_date'])) {
            $query->where('event_timestamp', '>=', Carbon::parse($validated['start_date']));
        }

        if (isset($validated['end_date'])) {
            $query->where('event_timestamp', '<=', Carbon::parse($validated['end_date'])->endOfDay());
        }

        $events = $query
            ->orderByDesc('event_timestamp')
            ->paginate($validated['per_page'] ?? 50);

        return response()->json($events);
    }

    /**
     * Run a funnel analysis.
     */
    public function funnel(Request $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        $validated = $request->validate([
            'steps' => 'required|array|min:2|max:10',
            'steps.*' => 'required|string|max:100',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $startDate = Carbon::parse($validated['start_date'] ?? now()->subDays(30))->startOfDay();
        $endDate = Carbon::parse($validated['end_date'] ?? now())->endOfDay();
        $steps = $validated['steps'];

        $results = [];
        $previousSessionIds = null;

        foreach ($steps as $index => $eventName) {
            $query = Event::where('project_id', $project->id)
                ->where('event_name', $eventName)
                ->whereBetween('event_timestamp', [$startDate, $endDate]);

            if ($previousSessionIds !== null) {
                $query->whereIn('session_id', $previousSessionIds);
            }

            $sessionIds = $query->distinct()->pluck('session_id')->toArray();
            $count = count($sessionIds);

            $results[] = [
                'step' => $index + 1,
                'event_name' => $eventName,
                'count' => $count,
                'conversion_rate' => $index === 0
                    ? 100
                    : ($results[0]['count'] > 0 ? round(($count / $results[0]['count']) * 100, 2) : 0),
            ];

            $previousSessionIds = $sessionIds;
        }

        return response()->json([
            'data' => [
                'funnel' => $results,
                'period' => [
                    'start' => $startDate->toDateString(),
                    'end' => $endDate->toDateString(),
                ],
            ],
        ]);
    }
}
