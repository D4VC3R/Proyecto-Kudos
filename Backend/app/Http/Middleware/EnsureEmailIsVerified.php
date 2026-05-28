<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware para verificar que el usuario autenticado haya verificado su correo electrónico antes de permitir el acceso a ciertas rutas o acciones.
 */
class EnsureEmailIsVerified
{
    /**
     * Maneja una solicitud entrante y verifica si el usuario ha verificado su correo electrónico.
     *
     * @param Closure(Request): (Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() ||
            ($request->user() instanceof MustVerifyEmail &&
            ! $request->user()->hasVerifiedEmail())) {
            return response()->json([
                'error' => [
                    'code' => 'forbidden',
                    'message' => 'No tienes permisos para realizar esta acción.',
                ],
            ], 403);
        }

        return $next($request);
    }
}
