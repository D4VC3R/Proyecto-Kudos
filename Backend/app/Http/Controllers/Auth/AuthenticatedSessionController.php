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
			$request->authenticate();
		} catch (ValidationException $e) {
			$message = $e->errors()['email'][0] ?? 'Credenciales inválidas.';
			$status = str_contains(mb_strtolower($message), 'seconds') || str_contains(mb_strtolower($message), 'segundos')
				? 429
				: 401;

			if ($status === 429) {
				return $this->respondError(
					code: 'too_many_requests',
					message: 'Demasiados intentos de inicio de sesión. Intentalo de nuevo en un rato.',
					status: 429,
				);
			}

			return $this->respondError(
				code: 'unauthenticated',
				message: 'Credenciales invalidas.',
				status: 401,
			);
		}

		$user = Auth::user();
		if (!$user instanceof User) {
			return $this->respondMutation('No se pudo obtener al usuario autenticado.', status: 500);
		}

		if ($user->isCurrentlyBanned()) {
			return $this->respondError(
				code: 'forbidden',
				message: 'Tu cuenta esta suspendida y no puede iniciar sesión.',
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

		return $this->respondData(
			data: [
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
			]
		);
	}

	/**
	 * Cerrar sesión eliminando el token de acceso actual.
	 */
	public function destroy(Request $request): JsonResponse
	{
		$token = $request->user()?->currentAccessToken();
        if ($token) {
            $token->delete();
        }

		return $this->respondMutation('Sesión cerrada con éxito.');
	}

	/*
	 * Eliminar todos los tokens de un usuario.
	 * */
	public function destroyAll(Request $request): JsonResponse
	{
		// Eliminar TODOS los tokens del usuario
		$request->user()->tokens()->delete();

		return $this->respondMutation('Sesión cerrada en todos los dispositivos.');
	}
}
