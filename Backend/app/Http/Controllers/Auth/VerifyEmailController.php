<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\User;

class VerifyEmailController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     */
    public function __invoke(Request $request, $id, $hash): JsonResponse
    {
        $user = User::findOrFail($id);

        if (! hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            return $this->respondError('invalid-hash', 'El enlace de verificación o hash es inválido.', [], 403);
        }

        if ($user->hasVerifiedEmail()) {
            return $this->respondMutation('El email ya estaba verificado.', [
                'status' => 'already-verified',
            ]);
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return $this->respondMutation('Email verificado correctamente.', [
            'status' => 'verified',
        ]);
    }
}
