<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmailVerificationNotificationController extends Controller
{
    /**
     * Send a new email verification notification.
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
