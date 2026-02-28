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
			    'message' => 'Unauthenticated.',
		    ], 401);
	    }

	    // Y que el usuario sea admin
	    if ($request->user()->role !== 'admin') {
		    return response()->json([
			    'message' => 'Forbidden. Admin access required.',
		    ], 403);
	    }

        return $next($request);
    }
}
