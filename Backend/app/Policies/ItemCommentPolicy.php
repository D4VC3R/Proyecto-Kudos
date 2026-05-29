<?php

namespace App\Policies;

use App\Models\Item;
use App\Models\ItemComment;
use App\Models\User;

/**
 * Política de permisos para los comentarios de items.
 */
class ItemCommentPolicy
{
	/**
	 * Indica si se pueden listar los comentarios de un item.
	 *
	 * @param User|null $user Usuario autenticado o invitado.
	 * @param Item $item Item asociado a los comentarios.
	 * @return bool
	 */
	public function viewAny(?User $user, Item $item): bool
	{
		if ($item->status === Item::STATUS_ACTIVE) {
			return true;
		}
		return $user?->hasRole('admin') ?? false;
	}

	/**
	 * Indica si se puede crear un comentario en un item.
	 *
	 * @param User $user Usuario autenticado.
	 * @param Item $item Item sobre el que se comenta.
	 * @return bool
	 */
	public function create(User $user, Item $item): bool
	{
		return $item->status === Item::STATUS_ACTIVE;
	}

	/**
	 * Indica si se puede actualizar un comentario.
	 *
	 * @param User $user Usuario autenticado.
	 * @param ItemComment $comment Comentario a modificar.
	 * @return bool
	 */
	public function update(User $user, ItemComment $comment): bool
	{
		if ($user->hasRole('admin')) return true;

		return $user->id === $comment->user_id && !$comment->is_hidden;
	}

	/**
	 * Indica si se puede eliminar un comentario.
	 *
	 * @param User $user Usuario autenticado.
	 * @param ItemComment $comment Comentario a eliminar.
	 * @return bool
	 */
	public function delete(User $user, ItemComment $comment): bool
	{
		return $user->hasRole('admin');
	}

	/**
	 * Indica si se puede ocultar un comentario.
	 *
	 * @param User $user Usuario autenticado.
	 * @param ItemComment $comment Comentario a ocultar.
	 * @return bool
	 */
	public function hide(User $user, ItemComment $comment): bool
	{
		return $user->hasRole('admin');
	}

	/**
	 * Indica si se puede mostrar un comentario oculto.
	 *
	 * @param User $user Usuario autenticado.
	 * @param ItemComment $comment Comentario a mostrar.
	 * @return bool
	 */
	public function unhide(User $user, ItemComment $comment): bool
	{
		return $user->hasRole('admin');
	}
}
