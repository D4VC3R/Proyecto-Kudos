<?php

namespace App\Policies;

use App\Models\User;
use App\Models\UserList;

/**
 * Política de permisos para las listas.
 * En la F2-09 solo el admin gestiona listas editoriales; la F3-01 añadirá al dueño de la lista.
 */
class UserListPolicy
{
    /**
     * Indica si se pueden listar las listas editoriales en el panel.
     *
     * @param  User  $user  Usuario autenticado.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Indica si se puede crear una lista editorial.
     *
     * @param  User  $user  Usuario autenticado.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Indica si se puede modificar una lista (y sus ítems): solo las editoriales y solo el admin.
     *
     * @param  User  $user  Usuario autenticado.
     * @param  UserList  $list  Lista a modificar.
     */
    public function update(User $user, UserList $list): bool
    {
        return $user->hasRole('admin') && $list->is_editorial;
    }

    /**
     * Indica si se puede eliminar una lista: solo las editoriales y solo el admin.
     *
     * @param  User  $user  Usuario autenticado.
     * @param  UserList  $list  Lista a eliminar.
     */
    public function delete(User $user, UserList $list): bool
    {
        return $user->hasRole('admin') && $list->is_editorial;
    }
}
