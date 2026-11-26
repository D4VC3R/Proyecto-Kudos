<?php

namespace App\Policies;

use App\Models\Item;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Política de permisos para los items.
 */
class ItemPolicy
{
    /**
     * Indica si se pueden listar los items.
     *
     * @param  User  $user  Usuario autenticado.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Indica si se puede ver un item.
     * Un ítem de una categoría inactiva (o sin categoría) no existe para el público, igual que su categoría:
     * da 404 y no 403, para no revelar que existe. El admin lo sigue viendo. Un ítem inactivo sigue dando 403.
     *
     * @param  User|null  $user  Usuario autenticado o invitado.
     * @param  Item  $item  Item a consultar.
     */
    public function view(?User $user, Item $item): Response|bool
    {
        $isAdmin = $user?->hasRole('admin') ?? false;

        if (! $isAdmin && ! ($item->category?->is_active ?? false)) {
            return Response::denyAsNotFound('El ítem no existe.');
        }

        if ($item->status === Item::STATUS_ACTIVE) {
            return true;
        }

        return $isAdmin;
    }

    /**
     * Indica si se puede crear un item.
     *
     * @param  User  $user  Usuario autenticado.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Indica si se puede actualizar un item.
     *
     * @param  User  $user  Usuario autenticado.
     * @param  Item  $item  Item a modificar.
     */
    public function update(User $user, Item $item): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Indica si se puede eliminar un item.
     *
     * @param  User  $user  Usuario autenticado.
     * @param  Item  $item  Item a eliminar.
     */
    public function delete(User $user, Item $item): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Indica si se puede borrar definitivamente un item.
     *
     * @param  User  $user  Usuario autenticado.
     * @param  Item  $item  Item a borrar definitivamente.
     */
    public function forceDelete(User $user, Item $item): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Indica si se puede moderar items.
     *
     * @param  User|null  $user  Usuario autenticado o invitado.
     */
    public function moderate(?User $user): bool
    {
        return $user && $user->hasRole('admin');
    }

    /**
     * Indica si se puede restaurar un item.
     *
     * @param  User  $user  Usuario autenticado.
     * @param  Item  $item  Item a restaurar.
     */
    public function restore(User $user, Item $item): bool
    {
        return false;
    }
}
