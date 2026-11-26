<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;

/**
 * Política de permisos para las categorías.
 */
class CategoryPolicy
{
    /**
     * Indica si se pueden listar las categorías.
     *
     * @param  User|null  $user  Usuario autenticado o invitado.
     */
    public function viewAny(?User $user): bool
    {
        return true;
    }

    /**
     * Indica si se puede ver una categoría.
     *
     * @param  User|null  $user  Usuario autenticado o invitado.
     * @param  Category  $category  Categoría a consultar.
     */
    public function view(?User $user, Category $category): bool
    {
        return true;
    }

    /**
     * Indica si se puede crear una categoría.
     *
     * @param  User  $user  Usuario autenticado.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Indica si se puede actualizar una categoría.
     *
     * @param  User  $user  Usuario autenticado.
     * @param  Category  $category  Categoría a modificar.
     */
    public function update(User $user, Category $category): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Indica si se puede eliminar una categoría.
     *
     * @param  User  $user  Usuario autenticado.
     * @param  Category  $category  Categoría a eliminar.
     */
    public function delete(User $user, Category $category): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Indica si se puede restaurar una categoría.
     *
     * @param  User  $user  Usuario autenticado.
     * @param  Category  $category  Categoría a restaurar.
     */
    public function restore(User $user, Category $category): bool
    {
        return false;
    }

    /**
     * Indica si se puede borrar definitivamente una categoría.
     *
     * @param  User  $user  Usuario autenticado.
     * @param  Category  $category  Categoría a borrar definitivamente.
     */
    public function forceDelete(User $user, Category $category): bool
    {
        return false;
    }
}
