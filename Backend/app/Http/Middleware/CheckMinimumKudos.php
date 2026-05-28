<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware para verificar que el usuario tenga un mínimo de Kudos antes de acceder a ciertas rutas o acciones.
 * (No se está utilizando activamente)
 */
class CheckMinimumKudos
{
	/**
	 * Verifica que el usuario tenga al menos la cantidad mínima de Kudos requerida.
	 * @param int $minKudos La cantidad mínima de Kudos requerida para acceder a la ruta o acción.
	 * @return Response Si el usuario no cumple con el requisito, se aborta con un error 403.
	 */
	public function handle(Request $request, Closure $next, int $minKudos = 0): Response
	{
		$user = $request->user();

		if ($user->total_kudos < $minKudos) {
			abort(403, "Necesitas al menos {$minKudos} Kudos para realizar esta acción. Actualmente tienes {$user->total_kudos}.");
		}

		return $next($request);
	}
}
