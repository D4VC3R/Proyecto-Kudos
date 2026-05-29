<?php

namespace App\Policies;

use App\Models\Item;
use App\Models\User;
use App\Models\Vote;

class VotePolicy
{

    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(User $user, Vote $vote): bool
    {
        return false;
    }

    public function create(User $user, Item $item): bool
    {
        return $item->status === Item::STATUS_ACTIVE;
    }

    public function update(User $user, Vote $vote): bool
    {
      return $user->id === $vote->user_id;
    }

    public function delete(User $user, Vote $vote): bool
    {
	    return $user->id === $vote->user_id;
    }

    public function restore(User $user, Vote $vote): bool
    {
        return false;
    }

    public function forceDelete(User $user, Vote $vote): bool
    {
        return false;
    }
}
