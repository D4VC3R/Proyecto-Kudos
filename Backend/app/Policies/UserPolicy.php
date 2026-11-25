<?php

namespace App\Policies;

use App\Models\User;

/**
 * Política de permisos para los usuarios.
 * Solo el admin consulta y modera usuarios desde el panel; el borrado de la cuenta es del propio usuario.
 * Que un admin no pueda banearse ni revocarse sus propias sesiones no está aquí: es un error de validación (422).
 */
class UserPolicy
{
    /**
     * Indica si se puede listar a los usuarios en el panel.
     *
     * @param  User  $user  Usuario autenticado.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Indica si se puede ver la ficha de un usuario en el panel.
     *
     * @param  User  $user  Usuario autenticado.
     * @param  User  $model  Usuario consultado.
     */
    public function view(User $user, User $model): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Indica si se puede banear a un usuario.
     *
     * @param  User  $user  Usuario autenticado.
     * @param  User  $model  Usuario a banear.
     */
    public function ban(User $user, User $model): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Indica si se puede levantar el baneo de un usuario.
     *
     * @param  User  $user  Usuario autenticado.
     * @param  User  $model  Usuario a desbanear.
     */
    public function unban(User $user, User $model): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Indica si se pueden revocar las sesiones (tokens) de un usuario.
     *
     * @param  User  $user  Usuario autenticado.
     * @param  User  $model  Usuario al que se revocan las sesiones.
     */
    public function revokeTokens(User $user, User $model): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Indica si se puede borrar la cuenta: solo la propia, y no la de un administrador.
     * Las cuentas de administrador no se borran desde el perfil: sus listas editoriales desaparecerían de la portada.
     *
     * @param  User  $user  Usuario autenticado.
     * @param  User  $model  Cuenta a borrar.
     */
    public function deleteOwnAccount(User $user, User $model): bool
    {
        return $user->is($model) && ! $user->hasRole('admin');
    }
}
