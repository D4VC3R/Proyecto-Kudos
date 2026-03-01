<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class AuthenticatedSessionController extends Controller
{
	/**
	 * Handle an incoming authentication request.
	 * La función es nativa de Breeze pero se ha adaptado para usar Bearer Tokens..
	 */
	public function store(LoginRequest $request): JsonResponse
	{
		$request->validate([
			'email' => ['required', 'string', 'email'],
			'password' => ['required', 'string'],
		]);

		if (!Auth::attempt($request->only('email', 'password'))) {
			return response()->json(['message' => 'Email o contraseña incorrectos.'], 401);
		}

		$user = Auth::user();
		$token = $user->createToken('auth_token')->plainTextToken;

		return response()->json([
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
				'role' => $user->role,
				'created_at' => $user->created_at,
				'updated_at' => $user->updated_at,
			],
		], 200);
	}

	/**
	 * Destroy an authenticated session.
	 */
	public function destroy(Request $request): JsonResponse
	{
		$request->user()->currentAccessToken()->delete();

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
