<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureConfigured
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $envPath = base_path('.env');
        $hasEnv = file_exists($envPath);
        $hasAppKey = !empty(config('app.key'));
        $hasDatabase = $this->hasDatabaseConfig();

        if (!$hasEnv || !$hasAppKey || !$hasDatabase) {
            $payload = [
                'hasEnv' => $hasEnv,
                'hasAppKey' => $hasAppKey,
                'hasDatabase' => $hasDatabase,
                'steps' => [
                    'Copy .env.example to .env',
                    'Set database credentials (DB_DATABASE, DB_USERNAME, DB_PASSWORD)',
                    'Run: php artisan appsignals:install --seed',
                    'Build assets: npm install && npm run build',
                ],
            ];

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'error' => 'AppSignals is not configured yet',
                    'message' => 'Your environment is missing required configuration.',
                    'checks' => [
                        'hasEnv' => $hasEnv,
                        'hasAppKey' => $hasAppKey,
                        'hasDatabase' => $hasDatabase,
                    ],
                    'steps' => $payload['steps'],
                ], 503);
            }

            return response()->view('setup.missing-config', $payload, 503);
        }

        return $next($request);
    }

    private function hasDatabaseConfig(): bool
    {
        $defaultConnection = config('database.default');
        $dbConfig = config("database.connections.$defaultConnection");

        if (empty($dbConfig) || !is_array($dbConfig)) {
            return false;
        }

        $database = $dbConfig['database'] ?? null;

        if ($defaultConnection !== 'sqlite' && empty($database)) {
            return false;
        }

        return true;
    }
}
