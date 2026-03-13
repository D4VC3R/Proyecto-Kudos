<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
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
