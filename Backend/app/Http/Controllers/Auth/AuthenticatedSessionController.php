<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Services\KudosService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthenticatedSessionController extends Controller
{
	public function __construct(protected KudosService $kudos)
	{
	}

	/**
	 * Handle an incoming authentication request.
	 * La función es nativa de Breeze pero se ha adaptado para usar Bearer Tokens y recompensa por login
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
					message: 'Demasiados intentos de inicio de sesion. Intentalo nuevamente en unos segundos.',
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
			return $this->respondMutation('No se pudo obtener el usuario autenticado.', status: 500);
		}

		if ($user->isCurrentlyBanned()) {
			return $this->respondError(
				code: 'forbidden',
				message: 'Tu cuenta esta suspendida y no puede iniciar sesion.',
				details: [
					'banned_until' => $user->banned_until?->toIso8601String(),
					'ban_reason' => $user->ban_reason,
				],
				status: 403,
			);
		}

		$dailyLoginResult = $this->kudos->processDailyLogin($user);
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
			],
			meta: [
				'daily_login_awarded' => $dailyLoginResult['awarded'],
				'daily_login_streak' => $dailyLoginResult['streak'],
				'daily_login_kudos_awarded' => $dailyLoginResult['kudos_awarded'],
				'daily_login_date' => $dailyLoginResult['date'],
			],
		);
	}

	/**
	 * Destroy an authenticated session.
	 */
	public function destroy(Request $request): JsonResponse
	{
		$token = $request->user()?->currentAccessToken();
        if ($token) {
            $token->delete();
        }

		return $this->respondMutation('Sesión cerrada con éxito.');
	}

	public function destroyAll(Request $request): JsonResponse
	{
		// Eliminar TODOS los tokens del usuario
		$request->user()->tokens()->delete();

		return $this->respondMutation('Sesión cerrada en todos los dispositivos.');
	}
}
