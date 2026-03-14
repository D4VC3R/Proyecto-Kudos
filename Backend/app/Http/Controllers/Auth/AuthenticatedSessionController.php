<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Services\DailyLoginKudosService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthenticatedSessionController extends Controller
{
	public function __construct(protected DailyLoginKudosService $dailyLoginKudosService)
	{
	}

	/**
	 * Handle an incoming authentication request.
	 * La función es nativa de Breeze pero se ha adaptado para usar Bearer Tokens..
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

			return response()->json(['message' => $message], $status);
		}

		$user = Auth::user();
		if (!$user instanceof User) {
			return response()->json(['message' => 'No se pudo obtener el usuario autenticado.'], 500);
		}

		if ($user->isCurrentlyBanned()) {
			return response()->json([
				'message' => 'Tu cuenta está suspendida y no puede iniciar sesión.',
				'meta' => [
					'banned_until' => $user->banned_until,
					'ban_reason' => $user->ban_reason,
				],
			], 403);
		}

		$dailyLoginResult = $this->dailyLoginKudosService->handleSuccessfulLogin($user);
		$user->refresh();
		$token = $user->createToken('auth_token')->plainTextToken;

		return response()->json([
			'access_token' => $token,
			'token_type' => 'Bearer',
			'status' => 'success',
			'meta' => [
				'daily_login_awarded' => $dailyLoginResult['awarded'],
				'daily_login_streak' => $dailyLoginResult['streak'],
				'daily_login_kudos_awarded' => $dailyLoginResult['kudos_awarded'],
				'daily_login_date' => $dailyLoginResult['date'],
			],
			'user' => [
				'id' => $user->id,
				'name' => $user->name,
				'email' => $user->email,
				'email_verified_at' => $user->email_verified_at,
				'total_kudos' => $user->total_kudos,
				'creations_accepted' => $user->creations_accepted,
				'login_streak_count' => $user->login_streak_count,
				'last_login_streak_date' => $user->last_login_streak_date,
			],
		], 200);
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

		return response()->json([
			'message' => 'Sesión cerrada con éxito.',
		]);
	}

	public function destroyAll(Request $request): JsonResponse
	{
		// Eliminar TODOS los tokens del usuario
		$request->user()->tokens()->delete();

		return response()->json([
			'message' => 'Sesión cerrada en todos los dispositivos.',
		]);
	}
}
