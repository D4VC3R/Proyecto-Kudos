<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * Controlador para manejar las sesiones autenticadas usando Bearer Tokens.
 * Este controlador se ha adaptado de la implementación nativa de Laravel Breeze.
 */
class AuthenticatedSessionController extends Controller
{
	/**
	 * Función para autenticar al usuario y crear un token de acceso. Responde con los detalles del usuario y el token generado.
	 * @param LoginRequest $request - La solicitud de inicio de sesión que contiene las credenciales del usuario.
	 * @return JsonResponse - Respuesta JSON con el resultado de la petición.
	 */
    public function store(LoginRequest $request): JsonResponse
    {
        try {
            // Si supera el Rate Limit, el propio Request lanzará un HTTP 429 automáticamente.
            $request->authenticate();
        } catch (ValidationException $e) {
            return $this->respondError(
                code: 'unauthenticated',
                message: 'Credenciales inválidas.',
                status: 401,
            );
        }

        // Como la autenticación es stateless, buscamos al usuario ya validado.
        $user = User::where('email', $request->email)->first();

        if ($user->isCurrentlyBanned()) {
            return $this->respondError(
                code: 'forbidden',
                message: 'Tu cuenta está suspendida y no puedes iniciar sesión.',
                details: [
                    'banned_until' => $user->banned_until?->toIso8601String(),
                    'ban_reason' => $user->ban_reason,
                ],
                status: 403,
            );
        }

        $user->refresh();
        $role = $user->hasRole('admin') ? 'admin' : 'user';
        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->respondData([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'status' => 'success',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at,
                'total_kudos' => $user->total_kudos,
                'creations_accepted' => $user->creations_accepted,
                'login_streak_count' => $user->login_streak_count,
                'last_login_streak_date' => $user->last_login_streak_date,
                'role' => $role,
                'is_admin' => $role === 'admin',
            ],
        ]);
    }

    /**
     * Revoca el token actual (Cierre de sesión del dispositivo actual).
     */
    public function destroy(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();
        return $this->respondMutation('Sesión cerrada con éxito.');
    }

    /**
     * Revoca todos los tokens (Cierre de sesión global).
     */
    public function destroyAll(Request $request): JsonResponse
    {
        $request->user()?->tokens()->delete();
        return $this->respondMutation('Sesión cerrada en todos los dispositivos.');
    }
}
