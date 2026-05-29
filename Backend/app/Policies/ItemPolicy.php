<?php

namespace App\Policies;

use App\Models\Item;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ItemPolicy
{

	public function viewAny(User $user): bool
	{
		return $user->hasRole('admin');
	}

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

	public function create(User $user): bool
	{
		return $user->hasRole('admin');
	}

	public function update(User $user, Item $item): bool
	{
		return $user->hasRole('admin');
	}

	public function delete(User $user, Item $item): bool
	{
		return $user->hasRole('admin');
	}

	public function forceDelete(User $user, Item $item): bool
	{
		return $user->hasRole('admin');
	}

	public function moderate(?User $user): bool
	{
		return $user && $user->hasRole('admin');
	}

	public function restore(User $user, Item $item): bool
	{
		return false;
	}

}
