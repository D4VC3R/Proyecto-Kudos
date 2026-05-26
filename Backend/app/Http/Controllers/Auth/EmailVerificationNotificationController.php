<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controlador API para re-enviar el correo de verificación si el usuario lo solicita.
 */
class EmailVerificationNotificationController extends Controller
{
    /**
     * Envía una nueva notificación de verificación al usuario autenticado.
     */
    public function store(Request $request): JsonResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return $this->respondMutation('Tu cuenta ya está verificada. No es necesario realizar más acciones.', [
                'status' => 'already-verified',
            ]);
        }

        $request->user()->sendEmailVerificationNotification();

        return $this->respondMutation('Hemos enviado un nuevo enlace de verificación a tu correo.', [
            'status' => 'verification-link-sent',
        ]);
    }
}
