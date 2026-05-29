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
	 * @param User $user Usuario autenticado.
	 * @return bool
	 */
	public function viewAny(User $user): bool
	{
		return $user->hasRole('admin');
	}

	/**
	 * Indica si se puede ver un item.
	 *
	 * @param User|null $user Usuario autenticado o invitado.
	 * @param Item $item Item a consultar.
	 * @return bool
	 */
	public function view(?User $user, Item $item): bool
	{

		if ($item->status === Item::STATUS_ACTIVE) {
			return true;
		}

		if ($user) {
			return $user->hasRole('admin');
		}

		return false;
	}

	/**
	 * Indica si se puede crear un item.
	 *
	 * @param User $user Usuario autenticado.
	 * @return bool
	 */
	public function create(User $user): bool
	{
		return $user->hasRole('admin');
	}

	/**
	 * Indica si se puede actualizar un item.
	 *
	 * @param User $user Usuario autenticado.
	 * @param Item $item Item a modificar.
	 * @return bool
	 */
	public function update(User $user, Item $item): bool
	{
		return $user->hasRole('admin');
	}

	/**
	 * Indica si se puede eliminar un item.
	 *
	 * @param User $user Usuario autenticado.
	 * @param Item $item Item a eliminar.
	 * @return bool
	 */
	public function delete(User $user, Item $item): bool
	{
		return $user->hasRole('admin');
	}

	/**
	 * Indica si se puede borrar definitivamente un item.
	 *
	 * @param User $user Usuario autenticado.
	 * @param Item $item Item a borrar definitivamente.
	 * @return bool
	 */
	public function forceDelete(User $user, Item $item): bool
	{
		return $user->hasRole('admin');
	}

	/**
	 * Indica si se puede moderar items.
	 *
	 * @param User|null $user Usuario autenticado o invitado.
	 * @return bool
	 */
	public function moderate(?User $user): bool
	{
		return $user && $user->hasRole('admin');
	}

	/**
	 * Indica si se puede restaurar un item.
	 *
	 * @param User $user Usuario autenticado.
	 * @param Item $item Item a restaurar.
	 * @return bool
	 */
	public function restore(User $user, Item $item): bool
	{
		return false;
	}

}
