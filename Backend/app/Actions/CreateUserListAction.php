<?php

namespace App\Actions;

use App\Models\User;
use App\Models\UserList;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Crea una lista de usuario (F3-01) respetando el máximo de listas por usuario.
 */
class CreateUserListAction
{
    /**
     * Bloquea al usuario (serializa dos altas simultáneas), cuenta sus listas vivas y crea la lista.
     * Las borradas y las editoriales no cuentan para el límite. Privada por defecto.
     *
     * @param  User  $user  Dueño de la lista.
     * @param  array<string, mixed>  $data  Datos validados (título, descripción, visibilidad, numeración, portada).
     *
     * @throws ValidationException Si el usuario ya tiene el máximo de listas.
     */
    public function execute(User $user, array $data): UserList
    {
        return DB::transaction(function () use ($user, $data) {
            User::query()->whereKey($user->getKey())->lockForUpdate()->first();

            $max = (int) config('kudos.lists.max_per_user', 50);
            $current = UserList::query()->where('user_id', $user->id)->where('is_editorial', false)->count();

            if ($current >= $max) {
                throw ValidationException::withMessages([
                    'title' => "Has llegado al máximo de {$max} listas. Borra alguna para crear otra.",
                ]);
            }

            $list = UserList::create([
                ...$data,
                'user_id' => $user->id,
                'is_editorial' => false,
                'visibility' => $data['visibility'] ?? UserList::VISIBILITY_PRIVATE,
            ]);

            return $list->fresh();
        });
    }
}
