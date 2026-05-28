<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware para verificar que el usuario autenticado tenga el rol de administrador antes de permitir el acceso a ciertas rutas o acciones.
 */
class EnsureUserIsAdmin
{
    /**
     * Maneja una solicitud entrante y verifica si el usuario es un administrador.
     *
     * @param Closure(Request): (Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
	    // Verificamos que el usuario esté autenticado
	    if (!$request->user()) {
		    return response()->json([
                'error' => [
                    'code' => 'unauthenticated',
                    'message' => 'No autorizado, se necesita login para esta acción.',
                ],
            ], 401);
	    }

	    // Y que el usuario sea admin
	    if (!$request->user()->hasRole('admin')) {
		    return response()->json([
                'error' => [
                    'code' => 'forbidden',
                    'message' => 'Acceso denegado, se necesitan permisos de administrador para esta acción.',
                ],
            ], 403);
	    }

        return $next($request);
    }
}
