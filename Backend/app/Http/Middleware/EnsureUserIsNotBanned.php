<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsNotBanned
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        if (!$user->isCurrentlyBanned()) {
            return $next($request);
        }

        return response()->json([
            'message' => 'Tu cuenta está suspendida y no puede acceder a esta funcionalidad.',
            'meta' => [
                'banned_until' => $user->banned_until,
                'ban_reason' => $user->ban_reason,
            ],
        ], 403);
    }
}

