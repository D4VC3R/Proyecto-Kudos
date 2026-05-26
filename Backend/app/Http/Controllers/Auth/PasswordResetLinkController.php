<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

/**
 * Controlador API encargado de despachar los correos de recuperación de contraseña.
 */
class PasswordResetLinkController extends Controller
{
    /**
     * Valida el email y envía un enlace de recuperación si el usuario existe.
     *
     * @throws ValidationException
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status != Password::RESET_LINK_SENT) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        return $this->respondMutation('Si el correo existe en nuestro sistema, recibirás un enlace de restablecimiento en breve.', [
            'status' => __($status),
        ]);
    }
}
