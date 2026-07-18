<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * API permission middleware.
 *
 * Resolves the authenticated user from the Sanctum guard (token), then
 * checks Spatie permissions using the application's default guard
 * (where permissions are seeded). This avoids the Spatie gotcha where
 * `permission:ability,sanctum` fails because seeded permissions carry
 * the `web` guard_name.
 */
class ApiPermissionMiddleware
{
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user('sanctum');

        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        foreach ($permissions as $permission) {
            // Permissions are seeded under the default `web` guard even though
            // the user authenticates via the `sanctum` guard. Force the guard so
            // Spatie resolves the permission row correctly.
            if (! $user->hasPermissionTo($permission, 'web')) {
                abort(403, 'Forbidden.');
            }
        }

        return $next($request);
    }
}
