<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware para verificar que el usuario no esté actualmente baneado antes de permitir el acceso a ciertas rutas o acciones.
 */
class EnsureUserIsNotBanned
{
    /**
     * Maneja una solicitud entrante y verifica si el usuario está actualmente baneado.
     *
     * @param Closure(Request): (Response) $next
     */
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
            'error' => [
                'code' => 'forbidden',
                'message' => 'Tu cuenta esta suspendida y no puede acceder a esta funcionalidad.',
                'details' => [
                    'banned_until' => $user->banned_until?->toIso8601String(),
                    'ban_reason' => $user->ban_reason,
                ],
            ],
        ], 403);
    }
}

