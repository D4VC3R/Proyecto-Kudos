<?php

namespace App\Policies;

use App\Models\Item;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ItemPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
		public function view(?User $user, Item $item): bool
		{
			// Items aceptados son públicos
			if ($item->state === Item::STATE_ACCEPTED) {
				return true;
			}

			// Items pending/rejected solo los ve el creador o admin
			if ($user) {
				return $user->id === $item->creator_id || $user->role === 'admin';
			}

			return false;
		}

		/**
		 * Determine if the user can create items.
		 */
		public function create(User $user): bool
		{
			return true;
		}

		/**
		 * Determine if the user can update the item.
		 */
		public function update(User $user, Item $item): bool
		{
			// Solo el creador puede editar su item
			if ($user->id !== $item->creator_id) {
				return false;
			}

			// Solo se puede editar si está pending
			return $item->state === Item::STATE_PENDING;
		}

		/**
		 * Determine if the user can delete the item.
		 */
		public function delete(User $user, Item $item): bool
		{
			// Solo el creador puede eliminar su item
			if ($user->id !== $item->creator_id) {
				return false;
			}

			// Solo se puede eliminar si está pending
			return $item->state === Item::STATE_PENDING;
		}

		/**
		 * Determine if the user can force delete the item (admin only).
		 */
		public function forceDelete(User $user, Item $item): bool
		{
			return $user->role === 'admin';
		}

		/**
		 * Determine if the user can accept/reject items (admin only).
		 */
		public function moderate(?User $user): bool
		{
			return $user && $user->role === 'admin';
		}

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Item $item): bool
    {
        return false;
    }

}
