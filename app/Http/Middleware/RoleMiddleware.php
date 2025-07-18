<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 401);
        }

        if (!$user->hasAnyRole($roles)) {
            return response()->json([
                'message' => 'Forbidden. Required role(s): ' . implode(', ', $roles)
            ], 403);
        }

        return $next($request);
    }
}