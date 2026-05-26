<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;

/**
 * Controlador API para manejar el establecimiento de una nueva contraseña.
 */
class NewPasswordController extends Controller
{
    /**
     * Procesa la solicitud para guardar una nueva contraseña verificando el token.
     *
     * @throws ValidationException
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user) use ($request) {
                $user->forceFill([
                    'password' => Hash::make($request->string('password')),
                ])->save();

                // Si la cuenta fue comprometida, expulsamos al atacante de todos sus dispositivos.
                $user->tokens()->delete();

                event(new PasswordReset($user));
            }
        );

        if ($status != Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        return $this->respondMutation('Contraseña restablecida correctamente. Por favor, inicia sesión con tus nuevas credenciales.', [
            'status' => __($status),
        ]);
    }
}
