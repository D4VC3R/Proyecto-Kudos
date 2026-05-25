<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

/*
 * Controlador nativo de Laravel Breeze para manejar las peticiones de reseteo de contraseña.
 */
class PasswordResetLinkController extends Controller
{
    /**
     * Mandar link de reseteo de contraseña.
     *
     * @throws ValidationException
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        // Enviar el link al email asociado al usuario.
        $status = Password::sendResetLink(
            $request->only('email')
        );
        // Si no se puede enviar, se comprueba e informa al usuario de la causa.
        if ($status != Password::RESET_LINK_SENT) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        return $this->respondMutation('Solicitud de restablecimiento enviada.', [
            'status' => __($status),
        ]);
    }
}
