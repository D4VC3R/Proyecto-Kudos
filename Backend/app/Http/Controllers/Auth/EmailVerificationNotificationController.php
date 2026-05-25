<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controlador nativo de Laravel Breeze para manejar las notificaciones de verificación de email.
 */
class EmailVerificationNotificationController extends Controller
{
    /**
     * Mandar notificación de email verificado.
     */
    public function store(Request $request): JsonResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return $this->respondMutation('El email ya estaba verificado.', [
                'status' => 'already-verified',
            ]);
        }
        $request->user()->sendEmailVerificationNotification();

        return $this->respondMutation('Enlace de verificación enviado.', [
            'status' => 'verification-link-sent',
        ]);
    }
}
