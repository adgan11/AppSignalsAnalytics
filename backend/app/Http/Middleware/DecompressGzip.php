<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DecompressGzip
{
    /**
     * Handle an incoming request with GZIP compressed body.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $contentEncoding = $request->header('Content-Encoding');

        if ($contentEncoding === 'gzip') {
            $content = $request->getContent();

            if ($content) {
                $decompressed = @gzdecode($content);

                if ($decompressed === false) {
                    return response()->json([
                        'error' => 'Invalid gzip encoding',
                    ], 400);
                }

                // Replace the request content with decompressed data
                $request->merge(json_decode($decompressed, true) ?? []);
            }
        }

        return $next($request);
    }
}
