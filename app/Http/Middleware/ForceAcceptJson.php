<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Force JSON responses for API routes.
 *
 * Spatie/Laravel's `auth:sanctum` only emits a JSON 401 when the request
 * indicates it expects JSON (`Accept: application/json`). Without this header
 * an unauthenticated API call falls through to a web-style redirect/login
 * (HTML 200/302) which breaks non-browser clients (Flutter). This middleware
 * guarantees every request entering the `api` group is treated as JSON.
 */
class ForceAcceptJson
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->headers->has('Accept')) {
            $request->headers->set('Accept', 'application/json');
        }

        return $next($request);
    }
}
