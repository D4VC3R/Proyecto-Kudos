<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\User;

/**
 * Controlador API para procesar la verificación criptográfica del correo electrónico.
 */
class VerifyEmailController extends Controller
{
    /**
     * Valida el hash firmado y marca el correo del usuario como verificado.
     * La validación de caducidad y firma del enlace recae sobre el middleware 'signed'.
     */
    public function __invoke(Request $request, string $id, string $hash): JsonResponse
    {
        $user = User::findOrFail($id);

        // Doble validación de seguridad: Asegura de que el hash pertenece realmente al email del usuario.
        if (! hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            return $this->respondError('invalid-hash', 'El enlace de verificación está corrupto o es inválido.', [], 403);
        }

        if ($user->hasVerifiedEmail()) {
            return $this->respondMutation('El email ya estaba verificado.', [
                'status' => 'already-verified',
            ]);
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return $this->respondMutation('Tu cuenta ha sido verificada correctamente.', [
            'status' => 'verified',
        ]);
    }
}
