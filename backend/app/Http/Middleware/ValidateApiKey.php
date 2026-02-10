<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateApiKey
{
    /**
     * Handle an incoming request by validating the API key.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $key = $request->header('X-API-Key') ?? $request->bearerToken();

        if (!$key) {
            return response()->json([
                'error' => 'API key required',
                'message' => 'Please provide an API key via X-API-Key header or Bearer token.',
            ], 401);
        }

        $apiKey = ApiKey::validate($key);

        if (!$apiKey) {
            return response()->json([
                'error' => 'Invalid API key',
                'message' => 'The provided API key is invalid or expired.',
            ], 401);
        }

        // Attach project_id to the request for use in controllers
        $request->attributes->set('project_id', $apiKey->project_id);
        $request->attributes->set('api_key', $apiKey);

        return $next($request);
    }
}
