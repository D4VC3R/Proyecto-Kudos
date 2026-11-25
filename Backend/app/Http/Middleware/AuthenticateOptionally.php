<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sesión opcional en las rutas públicas: hace de sanctum el guard por defecto, así que $request->user(),
 * los Form Requests y las policies reconocen el token Bearer si lo hay. Sin token, o con uno no válido,
 * la petición sigue como invitada: no exige autenticación.
 */
class AuthenticateOptionally
{
    public function handle(Request $request, Closure $next): Response
    {
        Auth::shouldUse('sanctum');

        return $next($request);
    }
}
