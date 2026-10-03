<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Orquesta el alta de nuevos usuarios en el sistema.
 */
class RegisteredUserController extends Controller
{
    /**
     * Crea un usuario, le asigna el rol base y genera su perfil asociado de forma atómica.
     */
    public function store(RegisterRequest $request): JsonResponse
    {
        // Envolvemos en transacción para evitar "Usuarios Fantasma" si falla la creación del perfil.
        $user = DB::transaction(function () use ($request) {

            $newUser = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            // Nos aseguramos de que el rol 'user' exista
            Role::query()->firstOrCreate([
                'name' => 'user',
                'guard_name' => 'web',
            ]);

            $newUser->syncRoles(['user']);

            // Si esto falla, se hace rollback automático de la creación del usuario.
            $newUser->profile()->create();

            // Con la verificación de email apagada, la cuenta nace verificada: el middleware 'verified'
            // la deja pasar y el listener de Registered no envía el correo (solo lo hace si falta verificar).
            if (! config('auth.verification.enabled')) {
                $newUser->markEmailAsVerified();
            }

            return $newUser;
        });

        event(new Registered($user));

        return $this->respondMutation(
            message: 'Usuario creado correctamente.',
            data: [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
                // El frontend decide con esto si muestra "revisa tu correo" o manda directamente al login.
                'requires_email_verification' => ! $user->hasVerifiedEmail(),
            ],
            status: 201,
        );
    }
}
