<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// Middleware para proteger futuras funciones en base a los kudos del usuario.
class CheckMinimumKudos
{
	/**
	 * Handle an incoming request.
	 *
	 * @param int $minKudos La cantidad mínima de puntos requerida.
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
