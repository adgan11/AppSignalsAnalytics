<?php

namespace App\Http\Controllers;

use App\Models\Crash;
use App\Models\DailyStat;
use App\Models\Event;
use App\Models\Project;
use App\Models\SessionReplay;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DashboardController extends Controller
{
    /**
     * Show project selection or redirect to first project.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $projects = $user->projects()->orderBy('name')->get();

        if ($projects->isEmpty()) {
            return view('dashboard.no-projects');
        }

        $projectIds = $projects->pluck('id');

        $eventStats = Event::whereIn('project_id', $projectIds)
            ->selectRaw('project_id, COUNT(*) as total_events, COUNT(DISTINCT user_id) as unique_users, COUNT(DISTINCT session_id) as sessions')
            ->groupBy('project_id')
            ->get()
            ->keyBy('project_id');

        $crashStats = Crash::whereIn('project_id', $projectIds)
            ->selectRaw('project_id, COUNT(*) as crashes')
            ->groupBy('project_id')
            ->get()
            ->keyBy('project_id');

        $projectCards = $projects->map(function ($project) use ($eventStats, $crashStats) {
            $eventStat = $eventStats->get($project->id);
            $crashStat = $crashStats->get($project->id);

            return [
                'project' => $project,
                'total_events' => $eventStat?->total_events ?? 0,
                'unique_users' => $eventStat?->unique_users ?? 0,
                'sessions' => $eventStat?->sessions ?? 0,
                'crashes' => $crashStat?->crashes ?? 0,
            ];
        });

        $orgName = $user->name . ' org';

        return view('dashboard.index', compact('projects', 'projectCards', 'orgName'));
    }

    /**
     * Dashboard overview with stats and charts.
     */
    public function overview(Request $request, Project $project)
    {
        $this->authorize('view', $project);

        $days = $request->input('days', 30);
        $startDate = now()->subDays($days)->startOfDay();
        $endDate = now()->endOfDay();

        // Summary stats
        $stats = [
            'total_events' => Event::where('project_id', $project->id)
                ->whereBetween('event_timestamp', [$startDate, $endDate])
                ->count(),
            'unique_users' => Event::where('project_id', $project->id)
                ->whereBetween('event_timestamp', [$startDate, $endDate])
                ->whereNotNull('user_id')
                ->distinct('user_id')
                ->count('user_id'),
            'unique_sessions' => Event::where('project_id', $project->id)
                ->whereBetween('event_timestamp', [$startDate, $endDate])
                ->distinct('session_id')
                ->count('session_id'),
            'crash_count' => Crash::where('project_id', $project->id)
                ->whereBetween('occurred_at', [$startDate, $endDate])
                ->count(),
        ];

        // Daily events for chart
        $dailyEvents = Event::where('project_id', $project->id)
            ->whereBetween('event_timestamp', [$startDate, $endDate])
            ->selectRaw('DATE(event_timestamp) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date')
            ->toArray();

        // Fill in missing dates with zeros
        $chartData = [];
        $current = $startDate->copy();
        while ($current <= $endDate) {
            $dateStr = $current->format('Y-m-d');
            $chartData[$dateStr] = $dailyEvents[$dateStr] ?? 0;
            $current->addDay();
        }

        // Top events
        $topEvents = Event::where('project_id', $project->id)
            ->whereBetween('event_timestamp', [$startDate, $endDate])
            ->selectRaw('event_name, COUNT(*) as count')
            ->groupBy('event_name')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        // Recent crashes
        $recentCrashes = Crash::where('project_id', $project->id)
            ->orderByDesc('occurred_at')
            ->limit(5)
            ->get();

        // Get all user projects for sidebar
        $projects = $request->user()->projects()->get();

        return view('dashboard.overview', compact(
            'project',
            'projects',
            'stats',
            'chartData',
            'topEvents',
            'recentCrashes',
            'days'
        ));
    }

    /**
     * Events explorer.
     */
    public function events(Request $request, Project $project)
    {
        $this->authorize('view', $project);

        $query = Event::where('project_id', $project->id);

        // Filters
        if ($request->filled('event_name')) {
            $query->where('event_name', $request->event_name);
        }
        if ($request->filled('user_id')) {
            $query->where('user_id', 'like', '%' . $request->user_id . '%');
        }
        if ($request->filled('session_id')) {
            $query->where('session_id', $request->session_id);
        }
        if ($request->filled('date_from')) {
            $query->where('event_timestamp', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('event_timestamp', '<=', Carbon::parse($request->date_to)->endOfDay());
        }

        $events = $query->orderByDesc('event_timestamp')->paginate(50);

        // Get unique event names for filter dropdown
        $eventNames = Event::where('project_id', $project->id)
            ->distinct('event_name')
            ->pluck('event_name');

        $projects = $request->user()->projects()->get();

        return view('dashboard.events', compact('project', 'projects', 'events', 'eventNames'));
    }

    /**
     * Crash reports.
     */
    public function crashes(Request $request, Project $project)
    {
        $this->authorize('view', $project);

        // Get crash groups
        $crashGroups = Crash::where('project_id', $project->id)
            ->selectRaw('crash_group_hash, exception_type, exception_message, MAX(occurred_at) as last_occurred, MIN(occurred_at) as first_occurred, COUNT(*) as crash_count, COUNT(DISTINCT device_id) as affected_devices')
            ->groupBy('crash_group_hash', 'exception_type', 'exception_message')
            ->orderByDesc('last_occurred')
            ->paginate(25);

        $projects = $request->user()->projects()->get();

        return view('dashboard.crashes', compact('project', 'projects', 'crashGroups'));
    }

    /**
     * Crash detail view.
     */
    public function crashDetail(Request $request, Project $project, string $crashGroupHash)
    {
        $this->authorize('view', $project);

        $crashes = Crash::where('project_id', $project->id)
            ->where('crash_group_hash', $crashGroupHash)
            ->orderByDesc('occurred_at')
            ->limit(100)
            ->get();

        if ($crashes->isEmpty()) {
            abort(404);
        }

        $representative = $crashes->first();
        $affectedVersions = $crashes->pluck('app_version')->unique()->values();
        $affectedDevices = $crashes->pluck('device_id')->unique()->count();

        $projects = $request->user()->projects()->get();

        return view('dashboard.crash-detail', compact(
            'project',
            'projects',
            'crashes',
            'representative',
            'affectedVersions',
            'affectedDevices',
            'crashGroupHash'
        ));
    }

    /**
     * Session replays list.
     */
    public function replays(Request $request, Project $project)
    {
        $this->authorize('view', $project);

        $query = SessionReplay::where('project_id', $project->id)
            ->withCount('frames');

        if ($request->filled('user_id')) {
            $query->where('user_id', 'like', '%' . $request->user_id . '%');
        }

        $replays = $query->orderByDesc('started_at')->paginate(25);

        $projects = $request->user()->projects()->get();

        return view('dashboard.replays', compact('project', 'projects', 'replays'));
    }

    /**
     * Session replay player.
     */
    public function replayPlayer(Request $request, Project $project, string $sessionId)
    {
        $this->authorize('view', $project);

        $replay = SessionReplay::where('project_id', $project->id)
            ->where('session_id', $sessionId)
            ->with(['frames' => fn($q) => $q->orderBy('chunk_index')])
            ->firstOrFail();

        $projects = $request->user()->projects()->get();

        return view('dashboard.replay-player', compact('project', 'projects', 'replay'));
    }

    /**
     * Project settings.
     */
    public function settings(Request $request, Project $project)
    {
        $this->authorize('update', $project);

        $apiKeys = $project->apiKeys()
            ->select(['id', 'name', 'key_prefix', 'last_used_at', 'expires_at', 'created_at'])
            ->latest()
            ->get();

        $projects = $request->user()->projects()->get();

        return view('dashboard.settings', compact('project', 'projects', 'apiKeys'));
    }

    /**
     * Customers - Acquisition metrics.
     */
    public function acquisition(Request $request, Project $project)
    {
        $this->authorize('view', $project);
        $projects = $request->user()->projects()->get();

        $metrics = $this->buildAcquisitionMetrics($project);

        return view('dashboard.customers.acquisition', array_merge(
            compact('project', 'projects'),
            $metrics,
            ['metrics' => $metrics]
        ));
    }

    public function acquisitionData(Request $request, Project $project)
    {
        $this->authorize('view', $project);

        return response()->json([
            'data' => $this->buildAcquisitionMetrics($project),
        ]);
    }

    /**
     * Customers - Activation metrics.
     */
    public function activation(Request $request, Project $project)
    {
        $this->authorize('view', $project);
        $projects = $request->user()->projects()->get();

        $metrics = $this->buildActivationMetrics($project);

        return view('dashboard.customers.activation', array_merge(
            compact('project', 'projects'),
            $metrics,
            ['metrics' => $metrics]
        ));
    }

    public function activationData(Request $request, Project $project)
    {
        $this->authorize('view', $project);

        return response()->json([
            'data' => $this->buildActivationMetrics($project),
        ]);
    }

    /**
     * Customers - Retention metrics.
     */
    public function retention(Request $request, Project $project)
    {
        $this->authorize('view', $project);
        $projects = $request->user()->projects()->get();

        $metrics = $this->buildRetentionMetrics($project);

        return view('dashboard.customers.retention', array_merge(
            compact('project', 'projects'),
            $metrics,
            ['metrics' => $metrics]
        ));
    }

    public function retentionData(Request $request, Project $project)
    {
        $this->authorize('view', $project);

        return response()->json([
            'data' => $this->buildRetentionMetrics($project),
        ]);
    }

    private function buildAcquisitionMetrics(Project $project): array
    {
        $last24h = now()->subHours(24);
        $last30d = now()->subDays(30);
        $last3m = now()->subMonths(3);
        $lastYear = now()->subYear();
        $hourBucket = $this->hourBucketExpression('first_seen');
        $monthBucket = $this->monthBucketExpression('first_seen');
        $weekBucket = $this->weekBucketExpression('first_seen');
        $hourOfDay = $this->hourOfDayExpression('first_seen');
        $dayOfWeek = $this->dayOfWeekExpression('first_seen');

        $newUsersSubquery = Event::where('project_id', $project->id)
            ->whereNotNull('user_id')
            ->selectRaw('user_id, MIN(event_timestamp) as first_seen')
            ->groupBy('user_id');

        $hourlyNewUsers = DB::table(DB::raw("({$newUsersSubquery->toSql()}) as first_events"))
            ->mergeBindings($newUsersSubquery->getQuery())
            ->where('first_seen', '>=', $last24h)
            ->selectRaw("{$hourBucket} as hour, COUNT(*) as count")
            ->groupBy('hour')
            ->orderBy('hour')
            ->pluck('count', 'hour')
            ->toArray();

        $dailyNewUsers = DB::table(DB::raw("({$newUsersSubquery->toSql()}) as first_events"))
            ->mergeBindings($newUsersSubquery->getQuery())
            ->where('first_seen', '>=', $last30d)
            ->selectRaw('DATE(first_seen) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date')
            ->toArray();

        $weeklyNewUsers = DB::table(DB::raw("({$newUsersSubquery->toSql()}) as first_events"))
            ->mergeBindings($newUsersSubquery->getQuery())
            ->where('first_seen', '>=', $last3m)
            ->selectRaw("{$weekBucket} as week_key, MIN(DATE(first_seen)) as week_start, COUNT(*) as count")
            ->groupBy('week_key')
            ->orderBy('week_key')
            ->get()
            ->pluck('count', 'week_start')
            ->toArray();

        $monthlyNewUsers = DB::table(DB::raw("({$newUsersSubquery->toSql()}) as first_events"))
            ->mergeBindings($newUsersSubquery->getQuery())
            ->where('first_seen', '>=', $lastYear)
            ->selectRaw("{$monthBucket} as month, COUNT(*) as count")
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('count', 'month')
            ->toArray();

        $usersByHour = DB::table(DB::raw("({$newUsersSubquery->toSql()}) as first_events"))
            ->mergeBindings($newUsersSubquery->getQuery())
            ->where('first_seen', '>=', $last30d)
            ->selectRaw("{$hourOfDay} as hour, COUNT(*) as count")
            ->groupBy('hour')
            ->orderBy('hour')
            ->pluck('count', 'hour')
            ->toArray();

        $usersByDayOfWeek = DB::table(DB::raw("({$newUsersSubquery->toSql()}) as first_events"))
            ->mergeBindings($newUsersSubquery->getQuery())
            ->where('first_seen', '>=', $last30d)
            ->selectRaw("{$dayOfWeek} as dow, COUNT(*) as count")
            ->groupBy('dow')
            ->orderBy('dow')
            ->pluck('count', 'dow')
            ->toArray();

        $deviceDistribution = $this->userFirstEventDistribution(
            $newUsersSubquery,
            $project,
            $last30d,
            'first_seen',
            'device_model'
        );

        $osDistribution = $this->userFirstEventDistribution(
            $newUsersSubquery,
            $project,
            $last30d,
            'first_seen',
            'os_version'
        );
        $osDistribution = $this->prefixLabels($osDistribution, 'OS ');

        $countryDistribution = $this->userFirstEventDistribution(
            $newUsersSubquery,
            $project,
            $last30d,
            'first_seen',
            'country_code'
        );

        $appVersionDistribution = $this->userFirstEventDistribution(
            $newUsersSubquery,
            $project,
            $last30d,
            'first_seen',
            'app_version'
        );
        $appVersionDistribution = $this->prefixLabels($appVersionDistribution, 'v');

        $totalUsers = Event::where('project_id', $project->id)
            ->whereNotNull('user_id')
            ->distinct('user_id')
            ->count('user_id');

        $usersLastHour = DB::table(DB::raw("({$newUsersSubquery->toSql()}) as first_events"))
            ->mergeBindings($newUsersSubquery->getQuery())
            ->where('first_seen', '>=', now()->subHour())
            ->count();

        $usersToday = DB::table(DB::raw("({$newUsersSubquery->toSql()}) as first_events"))
            ->mergeBindings($newUsersSubquery->getQuery())
            ->where('first_seen', '>=', now()->startOfDay())
            ->count();

        return [
            'hourlyNewUsers' => $hourlyNewUsers,
            'dailyNewUsers' => $dailyNewUsers,
            'weeklyNewUsers' => $weeklyNewUsers,
            'monthlyNewUsers' => $monthlyNewUsers,
            'usersByHour' => $usersByHour,
            'usersByDayOfWeek' => $usersByDayOfWeek,
            'deviceDistribution' => $deviceDistribution,
            'osDistribution' => $osDistribution,
            'countryDistribution' => $countryDistribution,
            'appVersionDistribution' => $appVersionDistribution,
            'totalUsers' => $totalUsers,
            'usersLastHour' => $usersLastHour,
            'usersToday' => $usersToday,
        ];
    }

    private function buildActivationMetrics(Project $project): array
    {
        $last24h = now()->subHours(24);
        $last30d = now()->subDays(30);
        $last3m = now()->subMonths(3);
        $lastYear = now()->subYear();
        $hourBucket = $this->hourBucketExpression('activated_at');
        $monthBucket = $this->monthBucketExpression('activated_at');
        $weekBucket = $this->weekBucketExpression('activated_at');
        $hourOfDay = $this->hourOfDayExpression('activated_at');
        $dayOfWeek = $this->dayOfWeekExpression('activated_at');

        $activatedUsersSubquery = Event::where('project_id', $project->id)
            ->whereNotNull('user_id')
            ->where('event_name', '!=', 'session_start')
            ->where('event_name', '!=', 'app_open')
            ->selectRaw('user_id, MIN(event_timestamp) as activated_at')
            ->groupBy('user_id');

        $hourlyActivated = DB::table(DB::raw("({$activatedUsersSubquery->toSql()}) as activated"))
            ->mergeBindings($activatedUsersSubquery->getQuery())
            ->where('activated_at', '>=', $last24h)
            ->selectRaw("{$hourBucket} as hour, COUNT(*) as count")
            ->groupBy('hour')
            ->orderBy('hour')
            ->pluck('count', 'hour')
            ->toArray();

        $dailyActivated = DB::table(DB::raw("({$activatedUsersSubquery->toSql()}) as activated"))
            ->mergeBindings($activatedUsersSubquery->getQuery())
            ->where('activated_at', '>=', $last30d)
            ->selectRaw('DATE(activated_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date')
            ->toArray();

        $weeklyActivated = DB::table(DB::raw("({$activatedUsersSubquery->toSql()}) as activated"))
            ->mergeBindings($activatedUsersSubquery->getQuery())
            ->where('activated_at', '>=', $last3m)
            ->selectRaw("{$weekBucket} as week_key, MIN(DATE(activated_at)) as week_start, COUNT(*) as count")
            ->groupBy('week_key')
            ->orderBy('week_key')
            ->get()
            ->pluck('count', 'week_start')
            ->toArray();

        $monthlyActivated = DB::table(DB::raw("({$activatedUsersSubquery->toSql()}) as activated"))
            ->mergeBindings($activatedUsersSubquery->getQuery())
            ->where('activated_at', '>=', $lastYear)
            ->selectRaw("{$monthBucket} as month, COUNT(*) as count")
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('count', 'month')
            ->toArray();

        $activationByHour = DB::table(DB::raw("({$activatedUsersSubquery->toSql()}) as activated"))
            ->mergeBindings($activatedUsersSubquery->getQuery())
            ->where('activated_at', '>=', $last30d)
            ->selectRaw("{$hourOfDay} as hour, COUNT(*) as count")
            ->groupBy('hour')
            ->orderBy('hour')
            ->pluck('count', 'hour')
            ->toArray();

        $activationByDayOfWeek = DB::table(DB::raw("({$activatedUsersSubquery->toSql()}) as activated"))
            ->mergeBindings($activatedUsersSubquery->getQuery())
            ->where('activated_at', '>=', $last30d)
            ->selectRaw("{$dayOfWeek} as dow, COUNT(*) as count")
            ->groupBy('dow')
            ->orderBy('dow')
            ->pluck('count', 'dow')
            ->toArray();

        $deviceDistribution = $this->userFirstEventDistribution(
            $activatedUsersSubquery,
            $project,
            $last30d,
            'activated_at',
            'device_model'
        );

        $countryDistribution = $this->userFirstEventDistribution(
            $activatedUsersSubquery,
            $project,
            $last30d,
            'activated_at',
            'country_code'
        );

        $totalActivated = DB::table(DB::raw("({$activatedUsersSubquery->toSql()}) as activated"))
            ->mergeBindings($activatedUsersSubquery->getQuery())
            ->distinct('user_id')
            ->count('user_id');

        $totalUsers = Event::where('project_id', $project->id)
            ->whereNotNull('user_id')
            ->distinct('user_id')
            ->count('user_id');

        $activationRate = $totalUsers > 0 ? round(($totalActivated / $totalUsers) * 100, 1) : 0;

        return [
            'hourlyActivated' => $hourlyActivated,
            'dailyActivated' => $dailyActivated,
            'weeklyActivated' => $weeklyActivated,
            'monthlyActivated' => $monthlyActivated,
            'activationByHour' => $activationByHour,
            'activationByDayOfWeek' => $activationByDayOfWeek,
            'deviceDistribution' => $deviceDistribution,
            'countryDistribution' => $countryDistribution,
            'totalActivated' => $totalActivated,
            'totalUsers' => $totalUsers,
            'activationRate' => $activationRate,
        ];
    }

    private function buildRetentionMetrics(Project $project): array
    {
        $last24h = now()->subHours(24);
        $last30d = now()->subDays(30);
        $last3m = now()->subMonths(3);
        $lastYear = now()->subYear();
        $hourBucket = $this->hourBucketExpression('returned_at');
        $monthBucket = $this->monthBucketExpression('returned_at');
        $weekBucket = $this->weekBucketExpression('returned_at');
        $hourOfDay = $this->hourOfDayExpression('returned_at');
        $dayOfWeek = $this->dayOfWeekExpression('returned_at');

        $firstEventsSubquery = Event::where('project_id', $project->id)
            ->whereNotNull('user_id')
            ->selectRaw('user_id, DATE(MIN(event_timestamp)) as first_date')
            ->groupBy('user_id');

        $returningUsersSubquery = Event::where('events.project_id', $project->id)
            ->whereNotNull('events.user_id')
            ->joinSub($firstEventsSubquery, 'first_events', function ($join) {
                $join->on('events.user_id', '=', 'first_events.user_id');
            })
            ->whereRaw('DATE(events.event_timestamp) > first_events.first_date')
            ->selectRaw('events.user_id, MIN(events.event_timestamp) as returned_at')
            ->groupBy('events.user_id');

        $hourlyReturning = DB::table(DB::raw("({$returningUsersSubquery->toSql()}) as returning_events"))
            ->mergeBindings($returningUsersSubquery->getQuery())
            ->where('returned_at', '>=', $last24h)
            ->selectRaw("{$hourBucket} as hour, COUNT(*) as count")
            ->groupBy('hour')
            ->orderBy('hour')
            ->pluck('count', 'hour')
            ->toArray();

        $dailyReturning = DB::table(DB::raw("({$returningUsersSubquery->toSql()}) as returning_events"))
            ->mergeBindings($returningUsersSubquery->getQuery())
            ->where('returned_at', '>=', $last30d)
            ->selectRaw('DATE(returned_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date')
            ->toArray();

        $weeklyReturning = DB::table(DB::raw("({$returningUsersSubquery->toSql()}) as returning_events"))
            ->mergeBindings($returningUsersSubquery->getQuery())
            ->where('returned_at', '>=', $last3m)
            ->selectRaw("{$weekBucket} as week_key, MIN(DATE(returned_at)) as week_start, COUNT(*) as count")
            ->groupBy('week_key')
            ->orderBy('week_key')
            ->get()
            ->pluck('count', 'week_start')
            ->toArray();

        $monthlyReturning = DB::table(DB::raw("({$returningUsersSubquery->toSql()}) as returning_events"))
            ->mergeBindings($returningUsersSubquery->getQuery())
            ->where('returned_at', '>=', $lastYear)
            ->selectRaw("{$monthBucket} as month, COUNT(*) as count")
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('count', 'month')
            ->toArray();

        $returnByHour = DB::table(DB::raw("({$returningUsersSubquery->toSql()}) as returning_events"))
            ->mergeBindings($returningUsersSubquery->getQuery())
            ->where('returned_at', '>=', $last30d)
            ->selectRaw("{$hourOfDay} as hour, COUNT(*) as count")
            ->groupBy('hour')
            ->orderBy('hour')
            ->pluck('count', 'hour')
            ->toArray();

        $returnByDayOfWeek = DB::table(DB::raw("({$returningUsersSubquery->toSql()}) as returning_events"))
            ->mergeBindings($returningUsersSubquery->getQuery())
            ->where('returned_at', '>=', $last30d)
            ->selectRaw("{$dayOfWeek} as dow, COUNT(*) as count")
            ->groupBy('dow')
            ->orderBy('dow')
            ->pluck('count', 'dow')
            ->toArray();

        $deviceDistribution = $this->userFirstEventDistribution(
            $returningUsersSubquery,
            $project,
            $last30d,
            'returned_at',
            'device_model'
        );

        $countryDistribution = $this->userFirstEventDistribution(
            $returningUsersSubquery,
            $project,
            $last30d,
            'returned_at',
            'country_code'
        );

        $totalReturning = DB::table(DB::raw("({$returningUsersSubquery->toSql()}) as returning_events"))
            ->mergeBindings($returningUsersSubquery->getQuery())
            ->distinct('user_id')
            ->count('user_id');

        $totalUsers = Event::where('project_id', $project->id)
            ->whereNotNull('user_id')
            ->distinct('user_id')
            ->count('user_id');

        $retentionRate = $totalUsers > 0 ? round(($totalReturning / $totalUsers) * 100, 1) : 0;

        return [
            'hourlyReturning' => $hourlyReturning,
            'dailyReturning' => $dailyReturning,
            'weeklyReturning' => $weeklyReturning,
            'monthlyReturning' => $monthlyReturning,
            'returnByHour' => $returnByHour,
            'returnByDayOfWeek' => $returnByDayOfWeek,
            'deviceDistribution' => $deviceDistribution,
            'countryDistribution' => $countryDistribution,
            'totalReturning' => $totalReturning,
            'totalUsers' => $totalUsers,
            'retentionRate' => $retentionRate,
        ];
    }

    private function isSqliteDriver(): bool
    {
        return DB::connection()->getDriverName() === 'sqlite';
    }

    private function hourBucketExpression(string $column): string
    {
        if ($this->isSqliteDriver()) {
            return "strftime('%Y-%m-%d %H:00:00', {$column})";
        }

        return "DATE_FORMAT({$column}, \"%Y-%m-%d %H:00:00\")";
    }

    private function monthBucketExpression(string $column): string
    {
        if ($this->isSqliteDriver()) {
            return "strftime('%Y-%m', {$column})";
        }

        return "DATE_FORMAT({$column}, \"%Y-%m\")";
    }

    private function weekBucketExpression(string $column): string
    {
        if ($this->isSqliteDriver()) {
            return "strftime('%Y-%W', {$column})";
        }

        return "YEARWEEK({$column}, 1)";
    }

    private function hourOfDayExpression(string $column): string
    {
        if ($this->isSqliteDriver()) {
            return "CAST(strftime('%H', {$column}) AS INTEGER)";
        }

        return "HOUR({$column})";
    }

    private function dayOfWeekExpression(string $column): string
    {
        if ($this->isSqliteDriver()) {
            return "(CAST(strftime('%w', {$column}) AS INTEGER) + 1)";
        }

        return "DAYOFWEEK({$column})";
    }

    private function timeseriesBucketExpression(string $granularity, string $column): string
    {
        return match ($granularity) {
            'hour' => $this->hourBucketExpression($column),
            'month' => $this->monthBucketExpression($column),
            default => "DATE({$column})",
        };
    }

    private function buildMetricsSectionData(Project $project, string $section): array
    {
        $last30d = now()->subDays(30);

        if ($section === 'devices') {
            $deviceModels = Event::where('project_id', $project->id)
                ->where('event_timestamp', '>=', $last30d)
                ->whereNotNull('device_model')
                ->selectRaw('device_model, COUNT(DISTINCT COALESCE(user_id, device_id)) as users')
                ->groupBy('device_model')
                ->orderByDesc('users')
                ->limit(10)
                ->pluck('users', 'device_model')
                ->toArray();

            $systemVersions = Event::where('project_id', $project->id)
                ->where('event_timestamp', '>=', $last30d)
                ->whereNotNull('os_version')
                ->selectRaw('os_version, COUNT(DISTINCT COALESCE(user_id, device_id)) as users')
                ->groupBy('os_version')
                ->orderByDesc('users')
                ->limit(10)
                ->pluck('users', 'os_version')
                ->toArray();
            $systemVersions = $this->prefixLabels($systemVersions, 'OS ');

            $deviceTypes = $this->normalizeValueCounts($deviceModels, function ($value) {
                return $this->deviceTypeFromModel((string) $value);
            });

            $platformsRaw = $this->propertyCounts($project, $last30d, [
                'platform',
                'os_name',
                'osName',
                'os',
                'os_name_raw',
            ], 10);
            $platforms = $this->normalizeValueCounts($platformsRaw, function ($value) {
                return $this->platformLabel((string) $value);
            });

            if (empty($platforms) && !empty($deviceModels)) {
                $platforms = $this->normalizeValueCounts($deviceModels, function ($value) {
                    return $this->platformLabel((string) $value);
                });
            }

            $screenWidths = $this->propertyCounts($project, $last30d, [
                'screen_width',
                'screenWidth',
                'screen_width_px',
                'screenWidthPx',
                'display_width',
                'displayWidth',
            ], 10);
            $screenWidths = $this->prefixLabels($screenWidths, '', ' px');
            $screenHeights = $this->propertyCounts($project, $last30d, [
                'screen_height',
                'screenHeight',
                'screen_height_px',
                'screenHeightPx',
                'display_height',
                'displayHeight',
            ], 10);
            $screenHeights = $this->prefixLabels($screenHeights, '', ' px');

            $colorSchemeRaw = $this->propertyCounts($project, $last30d, [
                'color_scheme',
                'appearance',
                'user_interface_style',
                'userInterfaceStyle',
                'colorScheme',
            ], 10);
            $colorSchemes = $this->normalizeValueCounts($colorSchemeRaw, function ($value) {
                $normalized = strtolower(trim((string) $value));
                if ($normalized === '' || $normalized === 'null') {
                    return null;
                }
                if (str_contains($normalized, 'dark')) {
                    return 'Dark';
                }
                if (str_contains($normalized, 'light')) {
                    return 'Light';
                }
                return ucfirst($normalized);
            });

            $orientationRaw = $this->propertyCounts($project, $last30d, ['orientation', 'device_orientation', 'deviceOrientation'], 10);
            $orientations = $this->normalizeValueCounts($orientationRaw, function ($value) {
                $normalized = strtolower(trim((string) $value));
                if ($normalized === '' || $normalized === 'null') {
                    return null;
                }
                if (str_contains($normalized, 'portrait')) {
                    return 'Portrait';
                }
                if (str_contains($normalized, 'landscape')) {
                    return 'Landscape';
                }
                return ucfirst($normalized);
            });

            return [
                'deviceModels' => $deviceModels,
                'systemVersions' => $systemVersions,
                'deviceTypes' => $deviceTypes,
                'platforms' => $platforms,
                'screenWidths' => $screenWidths,
                'screenHeights' => $screenHeights,
                'colorSchemes' => $colorSchemes,
                'orientations' => $orientations,
                'updatedAt' => now()->toIso8601String(),
            ];
        }

        if ($section === 'versions') {
            $appVersions = Event::where('project_id', $project->id)
                ->where('event_timestamp', '>=', $last30d)
                ->whereNotNull('app_version')
                ->selectRaw('app_version, COUNT(DISTINCT COALESCE(user_id, device_id)) as users')
                ->groupBy('app_version')
                ->orderByDesc('users')
                ->limit(10)
                ->pluck('users', 'app_version')
                ->toArray();
            $appVersions = $this->prefixLabels($appVersions, 'v');

            $buildNumbers = $this->propertyCounts($project, $last30d, [
                'app_build',
                'appBuild',
                'build_number',
                'buildNumber',
            ], 10);
            if (empty($buildNumbers)) {
                $buildNumbers = Crash::where('project_id', $project->id)
                    ->where('occurred_at', '>=', $last30d)
                    ->whereNotNull('app_build')
                    ->selectRaw('app_build, COUNT(*) as crashes')
                    ->groupBy('app_build')
                    ->orderByDesc('crashes')
                    ->limit(10)
                    ->pluck('crashes', 'app_build')
                    ->toArray();
            }
            $buildNumbers = $this->prefixLabels($buildNumbers, 'Build ');

            $sdkVersions = $this->propertyCounts($project, $last30d, [
                'sdk_version',
                'sdkVersion',
                'sdk',
            ], 10);
            $sdkVersions = $this->prefixLabels($sdkVersions, 'SDK ');

            return [
                'appVersions' => $appVersions,
                'buildNumbers' => $buildNumbers,
                'sdkVersions' => $sdkVersions,
                'updatedAt' => now()->toIso8601String(),
            ];
        }

        if ($section === 'errors') {
            $crashTop = Crash::where('project_id', $project->id)
                ->where('occurred_at', '>=', $last30d)
                ->selectRaw('exception_type, COUNT(*) as count')
                ->groupBy('exception_type')
                ->orderByDesc('count')
                ->limit(10)
                ->pluck('count', 'exception_type')
                ->toArray();

            $crashHistory = Crash::where('project_id', $project->id)
                ->where('occurred_at', '>=', $last30d)
                ->selectRaw('DATE(occurred_at) as date, COUNT(*) as count')
                ->groupBy('date')
                ->orderBy('date')
                ->pluck('count', 'date')
                ->toArray();

            $eventErrorsTop = Event::where('project_id', $project->id)
                ->where('event_timestamp', '>=', $last30d)
                ->whereIn('event_name', ['error', 'exception', 'user_input_error', 'app_state_error'])
                ->selectRaw('event_name, COUNT(*) as count')
                ->groupBy('event_name')
                ->orderByDesc('count')
                ->limit(10)
                ->pluck('count', 'event_name')
                ->toArray();

            $eventErrorsHistory = Event::where('project_id', $project->id)
                ->where('event_timestamp', '>=', $last30d)
                ->whereIn('event_name', ['error', 'exception', 'user_input_error', 'app_state_error'])
                ->selectRaw('DATE(event_timestamp) as date, COUNT(*) as count')
                ->groupBy('date')
                ->orderBy('date')
                ->pluck('count', 'date')
                ->toArray();

            $overallTop = $this->mergeCountMaps([$crashTop, $eventErrorsTop], 10);
            $overallHistory = $this->mergeDateCounts([$crashHistory, $eventErrorsHistory]);

            $thrownTop = $crashTop;
            $thrownHistory = $crashHistory;

            $userInputTop = $this->errorCategoryCounts($project, $last30d, 'user_input');
            $userInputHistory = $this->errorCategoryHistory($project, $last30d, 'user_input');

            $appStateTop = $this->errorCategoryCounts($project, $last30d, 'app_state');
            $appStateHistory = $this->errorCategoryHistory($project, $last30d, 'app_state');

            return [
                'overallTop' => $overallTop,
                'overallHistory' => $overallHistory,
                'thrownTop' => $thrownTop,
                'thrownHistory' => $thrownHistory,
                'userInputTop' => $userInputTop,
                'userInputHistory' => $userInputHistory,
                'appStateTop' => $appStateTop,
                'appStateHistory' => $appStateHistory,
                'updatedAt' => now()->toIso8601String(),
            ];
        }

        if ($section === 'localization') {
            $preferredLanguage = $this->propertyCounts($project, $last30d, [
                'preferred_language',
                'preferredLanguage',
                'preferred_locale',
                'preferredLocale',
                'locale',
                'language',
                'preferredLanguageCode',
                'preferred_language_code',
            ], 10);

            $appLanguage = $this->propertyCounts($project, $last30d, [
                'app_language',
                'appLanguage',
                'app_locale',
                'appLocale',
                'appLanguageCode',
                'app_language_code',
            ], 10);

            $region = Event::where('project_id', $project->id)
                ->where('event_timestamp', '>=', $last30d)
                ->whereNotNull('country_code')
                ->selectRaw('country_code, COUNT(DISTINCT COALESCE(user_id, device_id)) as users')
                ->groupBy('country_code')
                ->orderByDesc('users')
                ->limit(10)
                ->pluck('users', 'country_code')
                ->mapWithKeys(function ($count, $code) {
                    return [strtoupper($code) => $count];
                })
                ->toArray();
            $regionProperties = $this->propertyCounts($project, $last30d, [
                'region',
                'region_code',
                'regionCode',
                'country',
                'country_code',
                'countryCode',
            ], 10);
            $regionProperties = $this->normalizeValueCounts($regionProperties, function ($value) {
                $normalized = strtoupper(trim((string) $value));
                return $normalized === '' ? null : $normalized;
            });
            $region = $this->mergeCountMaps([$region, $regionProperties], 10);

            $layoutDirectionRaw = $this->propertyCounts($project, $last30d, [
                'layout_direction',
                'layoutDirection',
                'text_direction',
                'textDirection',
                'direction',
                'layoutDirectionRaw',
            ], 10);
            $layoutDirection = $this->normalizeValueCounts($layoutDirectionRaw, function ($value) {
                $normalized = strtolower(trim((string) $value));
                if ($normalized === '' || $normalized === 'null') {
                    return null;
                }
                if (str_contains($normalized, 'rtl')) {
                    return 'RTL';
                }
                if (str_contains($normalized, 'ltr')) {
                    return 'LTR';
                }
                return strtoupper($normalized);
            });

            return [
                'preferredLanguage' => $preferredLanguage,
                'appLanguage' => $appLanguage,
                'region' => $region,
                'layoutDirection' => $layoutDirection,
                'updatedAt' => now()->toIso8601String(),
            ];
        }

        if ($section === 'accessibility') {
            $preferredContentSize = $this->propertyCounts($project, $last30d, [
                'preferred_content_size',
                'preferredContentSize',
                'content_size',
                'dynamic_type',
                'dynamicType',
                'contentSizeCategory',
            ], 10);

            $boldText = $this->booleanBuckets($this->propertyCounts($project, $last30d, [
                'bold_text',
                'boldText',
                'boldTextEnabled',
            ], 10));
            $reduceMotion = $this->booleanBuckets($this->propertyCounts($project, $last30d, [
                'reduce_motion',
                'reduceMotion',
                'reduceMotionEnabled',
            ], 10));
            $reduceTransparency = $this->booleanBuckets($this->propertyCounts($project, $last30d, [
                'reduce_transparency',
                'reduceTransparency',
                'reduceTransparencyEnabled',
            ], 10));
            $darkerSystemColors = $this->booleanBuckets($this->propertyCounts($project, $last30d, [
                'darker_system_colors',
                'darkerSystemColors',
                'darkerSystemColorsEnabled',
            ], 10));
            $differentiateWithoutColor = $this->booleanBuckets($this->propertyCounts($project, $last30d, [
                'differentiate_without_color',
                'differentiateWithoutColor',
                'differentiateWithoutColorEnabled',
            ], 10));
            $invertColors = $this->booleanBuckets($this->propertyCounts($project, $last30d, [
                'invert_colors',
                'invertColors',
                'inverted_colors',
                'invertColorsEnabled',
            ], 10));

            return [
                'preferredContentSize' => $preferredContentSize,
                'boldText' => $boldText,
                'reduceMotion' => $reduceMotion,
                'reduceTransparency' => $reduceTransparency,
                'darkerSystemColors' => $darkerSystemColors,
                'differentiateWithoutColor' => $differentiateWithoutColor,
                'invertColors' => $invertColors,
                'updatedAt' => now()->toIso8601String(),
            ];
        }

        return [
            'updatedAt' => now()->toIso8601String(),
        ];
    }

    private function userFirstEventDistribution($subquery, Project $project, Carbon $since, string $timestampColumn, string $dimension): array
    {
        return DB::table(DB::raw("({$subquery->toSql()}) as user_events"))
            ->mergeBindings($subquery->getQuery())
            ->join('events as e', function ($join) use ($timestampColumn) {
                $join->on('e.user_id', '=', 'user_events.user_id')
                    ->on('e.event_timestamp', '=', "user_events.{$timestampColumn}");
            })
            ->where('e.project_id', $project->id)
            ->where("user_events.{$timestampColumn}", '>=', $since)
            ->whereNotNull("e.{$dimension}")
            ->selectRaw("e.{$dimension} as dimension, COUNT(DISTINCT e.user_id) as users")
            ->groupBy('dimension')
            ->orderByDesc('users')
            ->limit(10)
            ->pluck('users', 'dimension')
            ->toArray();
    }

    private function jsonExtract(string $column, string $path): string
    {
        $driver = DB::connection()->getDriverName();
        $path = '$.' . $path;

        if ($driver === 'sqlite') {
            return "json_extract({$column}, '{$path}')";
        }

        if ($driver === 'pgsql') {
            $pathParts = implode(',', explode('.', ltrim($path, '$.')));
            return $column . " #>> '{" . $pathParts . "}'";
        }

        return "JSON_UNQUOTE(JSON_EXTRACT({$column}, '{$path}'))";
    }

    private function jsonCoalesce(string $column, array $paths): string
    {
        $expressions = array_map(fn($path) => $this->jsonExtract($column, $path), $paths);
        $expression = count($expressions) === 1
            ? $expressions[0]
            : 'COALESCE(' . implode(', ', $expressions) . ')';

        return "NULLIF({$expression}, '')";
    }

    private function propertyCounts(Project $project, Carbon $since, array $paths, int $limit = 10): array
    {
        $expression = $this->jsonCoalesce('properties', $paths);

        return Event::where('project_id', $project->id)
            ->where('event_timestamp', '>=', $since)
            ->whereNotNull('properties')
            ->selectRaw("{$expression} as value, COUNT(DISTINCT COALESCE(user_id, device_id)) as users")
            ->whereRaw("{$expression} is not null")
            ->groupBy('value')
            ->orderByDesc('users')
            ->limit($limit)
            ->pluck('users', 'value')
            ->toArray();
    }

    private function normalizeValueCounts(array $counts, callable $normalizer): array
    {
        $normalized = [];

        foreach ($counts as $value => $count) {
            $key = $normalizer($value);
            if ($key === null || $key === '') {
                continue;
            }
            $normalized[$key] = ($normalized[$key] ?? 0) + $count;
        }

        arsort($normalized);

        return $normalized;
    }

    private function booleanBuckets(array $counts): array
    {
        $enabled = 0;
        $disabled = 0;
        $unknown = 0;

        foreach ($counts as $value => $count) {
            $normalized = strtolower(trim((string) $value));
            if (in_array($normalized, ['1', 'true', 'yes', 'on', 'enabled'], true)) {
                $enabled += $count;
                continue;
            }
            if (in_array($normalized, ['0', 'false', 'no', 'off', 'disabled'], true)) {
                $disabled += $count;
                continue;
            }
            $unknown += $count;
        }

        $result = [];
        if ($enabled > 0) {
            $result['Enabled'] = $enabled;
        }
        if ($disabled > 0) {
            $result['Disabled'] = $disabled;
        }
        if ($unknown > 0) {
            $result['Unknown'] = $unknown;
        }

        return $result;
    }

    private function prefixLabels(array $counts, string $prefix, string $suffix = ''): array
    {
        $labeled = [];
        foreach ($counts as $value => $count) {
            $label = $prefix . $value . $suffix;
            $labeled[$label] = $count;
        }

        return $labeled;
    }

    private function deviceTypeFromModel(string $model): string
    {
        $normalized = strtolower($model);

        if (str_contains($normalized, 'ipad')) {
            return 'Tablet';
        }
        if (str_contains($normalized, 'iphone') || str_contains($normalized, 'phone')) {
            return 'Phone';
        }
        if (str_contains($normalized, 'watch')) {
            return 'Watch';
        }
        if (str_contains($normalized, 'tv')) {
            return 'TV';
        }
        if (str_contains($normalized, 'mac')) {
            return 'Desktop';
        }

        return 'Other';
    }

    private function platformLabel(string $value): string
    {
        $normalized = strtolower($value);

        if (str_contains($normalized, 'ios') || str_contains($normalized, 'iphone') || str_contains($normalized, 'ipad')) {
            return 'iOS';
        }
        if (str_contains($normalized, 'android')) {
            return 'Android';
        }
        if (str_contains($normalized, 'mac')) {
            return 'macOS';
        }
        if (str_contains($normalized, 'windows')) {
            return 'Windows';
        }
        if (str_contains($normalized, 'linux')) {
            return 'Linux';
        }

        return $value === '' ? 'Unknown' : ucfirst($value);
    }

    private function mergeCountMaps(array $maps, int $limit = 10): array
    {
        $merged = [];

        foreach ($maps as $map) {
            foreach ($map as $key => $count) {
                $merged[$key] = ($merged[$key] ?? 0) + $count;
            }
        }

        arsort($merged);

        return array_slice($merged, 0, $limit, true);
    }

    private function mergeDateCounts(array $maps): array
    {
        $merged = [];

        foreach ($maps as $map) {
            foreach ($map as $date => $count) {
                $merged[$date] = ($merged[$date] ?? 0) + $count;
            }
        }

        ksort($merged);

        return $merged;
    }

    private function errorCategoryCounts(Project $project, Carbon $since, string $category): array
    {
        $categoryKey = $this->jsonCoalesce('properties', ['error_category', 'errorCategory', 'category']);
        $typeKey = $this->jsonCoalesce('properties', ['error_type', 'errorType', 'type']);
        $eventName = $category . '_error';

        return Event::where('project_id', $project->id)
            ->where('event_timestamp', '>=', $since)
            ->where(function ($query) use ($categoryKey, $category, $eventName) {
                $query->where('event_name', $eventName)
                    ->orWhereRaw("{$categoryKey} = ?", [$category]);
            })
            ->selectRaw("COALESCE({$typeKey}, event_name) as label, COUNT(*) as count")
            ->groupBy('label')
            ->orderByDesc('count')
            ->limit(10)
            ->pluck('count', 'label')
            ->toArray();
    }

    private function errorCategoryHistory(Project $project, Carbon $since, string $category): array
    {
        $categoryKey = $this->jsonCoalesce('properties', ['error_category', 'errorCategory', 'category']);
        $eventName = $category . '_error';

        return Event::where('project_id', $project->id)
            ->where('event_timestamp', '>=', $since)
            ->where(function ($query) use ($categoryKey, $category, $eventName) {
                $query->where('event_name', $eventName)
                    ->orWhereRaw("{$categoryKey} = ?", [$category]);
            })
            ->selectRaw('DATE(event_timestamp) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date')
            ->toArray();
    }

    /**
     * Explore page with data discovery sections.
     */
    public function explore(Request $request, Project $project, ?string $section = null)
    {
        $this->authorize('view', $project);

        $section = $section ?? 'signal-types';
        $sections = ['signal-types', 'parameters', 'recent-signals', 'playground', 'export-ai'];

        if (!in_array($section, $sections, true)) {
            abort(404);
        }

        $projects = $request->user()->projects()->get();
        $viewData = compact('project', 'projects', 'section');

        if ($section === 'signal-types') {
            $range = $this->sanitizeExploreRange($request->input('range', '30d'));
            [$start, $end, $rangeLabel] = $this->exploreRangeBounds($range);

            $signalTypes = Event::where('project_id', $project->id)
                ->whereBetween('event_timestamp', [$start, $end])
                ->selectRaw('event_name, COUNT(*) as signal_count, COUNT(DISTINCT user_id) as user_count')
                ->groupBy('event_name')
                ->orderByDesc('signal_count')
                ->get();

            $totalSignals = Event::where('project_id', $project->id)
                ->whereBetween('event_timestamp', [$start, $end])
                ->count();

            $totalUsers = Event::where('project_id', $project->id)
                ->whereBetween('event_timestamp', [$start, $end])
                ->whereNotNull('user_id')
                ->distinct('user_id')
                ->count('user_id');

            $viewData = array_merge($viewData, [
                'signalTypes' => $signalTypes,
                'range' => $range,
                'rangeLabel' => $rangeLabel,
                'totalSignals' => $totalSignals,
                'totalUsers' => $totalUsers,
            ]);
        }

        if ($section === 'parameters') {
            $range = $this->sanitizeExploreRange($request->input('range', '30d'));
            [$start, $end, $rangeLabel] = $this->exploreRangeBounds($range);
            $limit = $this->clampExploreInt($request->input('limit'), 200, 5000, 2000);

            $events = Event::where('project_id', $project->id)
                ->whereBetween('event_timestamp', [$start, $end])
                ->whereNotNull('properties')
                ->orderByDesc('event_timestamp')
                ->limit($limit)
                ->get(['properties', 'event_timestamp']);

            $parameterMap = [];

            foreach ($events as $event) {
                if (!is_array($event->properties)) {
                    continue;
                }

                foreach ($event->properties as $key => $value) {
                    if (!isset($parameterMap[$key])) {
                        $parameterMap[$key] = [
                            'name' => $key,
                            'count' => 0,
                            'sample' => $this->formatParameterSample($value),
                            'last_seen' => $event->event_timestamp,
                        ];
                    }

                    $parameterMap[$key]['count'] += 1;

                    if ($event->event_timestamp > $parameterMap[$key]['last_seen']) {
                        $parameterMap[$key]['last_seen'] = $event->event_timestamp;
                    }
                }
            }

            $parameters = collect($parameterMap)
                ->sortByDesc('count')
                ->values();

            $viewData = array_merge($viewData, [
                'parameters' => $parameters,
                'parameterRange' => $range,
                'parameterRangeLabel' => $rangeLabel,
                'parameterLimit' => $limit,
                'parameterEventsScanned' => $events->count(),
            ]);
        }

        if ($section === 'recent-signals') {
            $query = Event::where('project_id', $project->id);

            if ($request->filled('event_name')) {
                $query->where('event_name', $request->event_name);
            }

            if ($request->filled('user_id')) {
                $query->where('user_id', 'like', '%' . $request->user_id . '%');
            }

            if ($request->filled('date_from')) {
                $query->where('event_timestamp', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->where('event_timestamp', '<=', Carbon::parse($request->date_to)->endOfDay());
            }

            $recentEvents = $query->orderByDesc('event_timestamp')->paginate(25)->withQueryString();

            $eventNames = Event::where('project_id', $project->id)
                ->distinct('event_name')
                ->pluck('event_name');

            $viewData = array_merge($viewData, [
                'recentEvents' => $recentEvents,
                'eventNames' => $eventNames,
            ]);
        }

        if ($section === 'playground') {
            $allowedQueryTypes = ['timeseries', 'events'];
            $allowedGranularities = ['hour', 'day', 'month'];
            $allowedRanges = ['24h', '7d', '30d', '90d'];

            $mode = $request->input('mode', 'visual');
            $mode = in_array($mode, ['visual', 'json'], true) ? $mode : 'visual';

            $payload = [
                'queryType' => 'timeseries',
                'granularity' => 'day',
                'range' => '30d',
                'eventName' => null,
                'userId' => null,
                'limit' => 100,
            ];

            $jsonError = null;
            $payloadJson = null;

            if ($mode === 'json' && $request->filled('payload')) {
                $payloadJson = $request->input('payload');
                $decoded = json_decode($payloadJson, true);

                if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
                    $jsonError = 'Invalid JSON payload.';
                } else {
                    $payload = array_merge($payload, array_intersect_key($decoded, $payload));
                }
            } else {
                $payload['queryType'] = $request->input('query_type', $payload['queryType']);
                $payload['granularity'] = $request->input('granularity', $payload['granularity']);
                $payload['range'] = $request->input('range', $payload['range']);
                $payload['eventName'] = $request->input('event_name');
                $payload['userId'] = $request->input('user_id');
                $payload['limit'] = $request->input('limit', $payload['limit']);
            }

            if (!in_array($payload['queryType'], $allowedQueryTypes, true)) {
                $payload['queryType'] = 'timeseries';
            }

            if (!in_array($payload['granularity'], $allowedGranularities, true)) {
                $payload['granularity'] = 'day';
            }

            if (!in_array($payload['range'], $allowedRanges, true)) {
                $payload['range'] = '30d';
            }

            $payload['limit'] = $this->clampExploreInt($payload['limit'], 10, 500, 100);

            [$start, $end, $rangeLabel] = $this->exploreRangeBounds($payload['range']);

            $baseQuery = Event::where('project_id', $project->id)
                ->whereBetween('event_timestamp', [$start, $end]);

            if (!empty($payload['eventName'])) {
                $baseQuery->where('event_name', $payload['eventName']);
            }

            if (!empty($payload['userId'])) {
                $baseQuery->where('user_id', 'like', '%' . $payload['userId'] . '%');
            }

            $playgroundResults = collect();

            if ($payload['queryType'] === 'timeseries') {
                $bucketExpression = $this->timeseriesBucketExpression($payload['granularity'], 'event_timestamp');

                $playgroundResults = (clone $baseQuery)
                    ->selectRaw("{$bucketExpression} as bucket, COUNT(*) as signal_count, COUNT(DISTINCT user_id) as user_count")
                    ->groupBy('bucket')
                    ->orderBy('bucket')
                    ->get();
            } else {
                $playgroundResults = (clone $baseQuery)
                    ->orderByDesc('event_timestamp')
                    ->limit($payload['limit'])
                    ->get();
            }

            if ($payloadJson === null) {
                $payloadJson = json_encode($payload, JSON_PRETTY_PRINT);
            }

            $eventNames = Event::where('project_id', $project->id)
                ->distinct('event_name')
                ->pluck('event_name');

            $viewData = array_merge($viewData, [
                'playgroundMode' => $mode,
                'playgroundPayload' => $payload,
                'playgroundJson' => $payloadJson,
                'playgroundError' => $jsonError,
                'playgroundResults' => $playgroundResults,
                'playgroundRangeLabel' => $rangeLabel,
                'eventNames' => $eventNames,
            ]);
        }

        if ($section === 'export-ai') {
            $range = $this->sanitizeExploreRange($request->input('range', '30d'));
            [$start, $end, $rangeLabel] = $this->exploreRangeBounds($range);
            $limit = $this->clampExploreInt($request->input('limit'), 50, 1000, 200);

            $exportPayload = $this->buildExploreExportPayload($project, $start, $end, $limit);

            if ($request->boolean('download')) {
                return response()
                    ->json($exportPayload)
                    ->header('Content-Disposition', 'attachment; filename="appsignals-export.json"');
            }

            $previewPayload = $exportPayload;
            $previewPayload['events'] = array_slice($exportPayload['events'], 0, 5);
            $previewJson = json_encode($previewPayload, JSON_PRETTY_PRINT);

            $viewData = array_merge($viewData, [
                'exportRange' => $range,
                'exportRangeLabel' => $rangeLabel,
                'exportLimit' => $limit,
                'exportPayload' => $exportPayload,
                'exportPreview' => $previewJson,
            ]);
        }

        return view('dashboard.explore', $viewData);
    }

    private function sanitizeExploreRange(string $range): string
    {
        $allowedRanges = ['24h', '7d', '30d', '90d'];

        return in_array($range, $allowedRanges, true) ? $range : '30d';
    }

    private function exploreRangeBounds(string $range): array
    {
        $end = now();

        $start = match ($range) {
            '24h' => now()->subHours(24),
            '7d' => now()->subDays(7),
            '90d' => now()->subDays(90),
            default => now()->subDays(30),
        };

        $label = match ($range) {
            '24h' => 'Last 24 hours',
            '7d' => 'Last 7 days',
            '90d' => 'Last 90 days',
            default => 'Last 30 days',
        };

        return [$start, $end, $label];
    }

    private function clampExploreInt($value, int $min, int $max, int $fallback): int
    {
        if (!is_numeric($value)) {
            return $fallback;
        }

        return (int) max($min, min($max, (int) $value));
    }

    private function formatParameterSample($value): string
    {
        if (is_array($value) || is_object($value)) {
            $value = json_encode($value);
        } elseif (is_bool($value)) {
            $value = $value ? 'true' : 'false';
        } elseif ($value === null) {
            $value = 'null';
        } else {
            $value = (string) $value;
        }

        return Str::limit($value, 60, '...');
    }

    private function buildExploreExportPayload(Project $project, Carbon $start, Carbon $end, int $limit): array
    {
        $events = Event::where('project_id', $project->id)
            ->whereBetween('event_timestamp', [$start, $end])
            ->orderByDesc('event_timestamp')
            ->limit($limit)
            ->get([
                'event_id',
                'event_name',
                'user_id',
                'session_id',
                'device_model',
                'app_version',
                'country_code',
                'event_timestamp',
                'properties',
            ])
            ->map(function ($event) {
                return [
                    'event_id' => $event->event_id,
                    'event_name' => $event->event_name,
                    'user_id' => $event->user_id,
                    'session_id' => $event->session_id,
                    'device_model' => $event->device_model,
                    'app_version' => $event->app_version,
                    'country_code' => $event->country_code,
                    'event_timestamp' => $event->event_timestamp?->toIso8601String(),
                    'properties' => $event->properties,
                ];
            })
            ->all();

        return [
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
            ],
            'range' => [
                'from' => $start->toIso8601String(),
                'to' => $end->toIso8601String(),
            ],
            'generated_at' => now()->toIso8601String(),
            'events' => $events,
        ];
    }

    /**
     * Metrics page with section tabs.
     */
    public function metrics(Request $request, Project $project)
    {
        $this->authorize('view', $project);
        $projects = $request->user()->projects()->get();
        $section = $request->route('section') ?? 'devices';
        $sections = ['devices', 'versions', 'errors', 'localization', 'accessibility'];

        if (!in_array($section, $sections, true)) {
            $section = 'devices';
        }

        $metricsData = $this->buildMetricsSectionData($project, $section);

        return view('dashboard.metrics', compact(
            'project',
            'projects',
            'section',
            'metricsData'
        ));
    }

    public function metricsData(Request $request, Project $project, string $section)
    {
        $this->authorize('view', $project);

        $sections = ['devices', 'versions', 'errors', 'localization', 'accessibility'];
        if (!in_array($section, $sections, true)) {
            abort(404);
        }

        return response()->json([
            'data' => $this->buildMetricsSectionData($project, $section),
        ]);
    }
}
