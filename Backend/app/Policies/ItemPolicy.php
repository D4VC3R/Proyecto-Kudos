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
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
		public function view(?User $user, Item $item): bool
		{

			if ($item->status === Item::STATUS_ACTIVE) {
				return true;
			}
            if ($user) {
                return $user->id === $item->creator_id || $user->hasRole('admin');
            }

			return false;
		}

		/**
		 * Determine if the user can create items.
		 */
		public function create(User $user): bool
		{
            return $user->hasRole('admin');
		}

		/**
		 * Determine if the user can update the item.
		 */
		public function update(User $user, Item $item): bool
		{
           return $user->hasRole('admin');
		}

		/**
		 * Determine if the user can delete the item.
		 */
		public function delete(User $user, Item $item): bool
		{
            return $user->hasRole('admin');
		}

		/**
		 * Determine if the user can force delete the item (admin only).
		 */
		public function forceDelete(User $user, Item $item): bool
		{
			return $user->hasRole('admin');
		}

		/**
		 * Determine if the user can accept/reject items (admin only).
		 */
		public function moderate(?User $user): bool
		{
			return $user && $user->hasRole('admin');
		}

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Item $item): bool
    {
        return false;
    }

}
