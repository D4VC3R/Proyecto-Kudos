<?php

namespace App\Policies;

use App\Models\User;
use App\Models\UserList;

/**
 * Política de permisos para las listas.
 * Solo el admin gestiona listas editoriales (F2-09); con la F3-01 el dueño gestiona las suyas de usuario
 * (`manage`) y se puede ver y dar "me gusta" a las listas visibles (`view`, `like`).
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

    /**
     * Indica si se puede ver una lista: las públicas y no listadas, cualquiera (también sin sesión);
     * las privadas, solo su dueño (a terceros les da 404 el controlador).
     */
    public function view(?User $user, UserList $list): bool
    {
        return $list->visibility !== UserList::VISIBILITY_PRIVATE
            || ($user !== null && $user->id === $list->user_id);
    }

    /** Cualquier usuario verificado puede crear listas propias (el límite lo comprueba CreateUserListAction). */
    public function createOwn(User $user): bool
    {
        return true;
    }

    /** Solo el dueño gestiona (edita, borra, añade, quita, reordena) sus listas de usuario; nunca las editoriales. */
    public function manage(User $user, UserList $list): bool
    {
        return ! $list->is_editorial && $user->id === $list->user_id;
    }

    /** Dar o quitar "me gusta": a una lista que se puede ver y que no es propia. */
    public function like(User $user, UserList $list): bool
    {
        return $this->view($user, $list) && $user->id !== $list->user_id;
    }
}
