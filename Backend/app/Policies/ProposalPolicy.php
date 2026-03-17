<?php

namespace App\Policies;

use App\Models\Proposal;
use App\Models\User;

class ProposalPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Proposal $proposal): bool
    {
        return $user->hasRole('admin') || $proposal->creator_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function reviewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, Proposal $proposal): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return $proposal->creator_id === $user->id
            && $proposal->status === Proposal::STATUS_CHANGES_REQUESTED;
    }

    public function delete(User $user, Proposal $proposal): bool
    {
        if (!in_array($proposal->status, [Proposal::STATUS_PENDING, Proposal::STATUS_CHANGES_REQUESTED], true)) {
            return false;
        }

        return $user->hasRole('admin') || $proposal->creator_id === $user->id;
    }

    public function review(User $user, Proposal $proposal): bool
    {
        return $user->hasRole('admin') && $proposal->creator_id !== $user->id;
    }
}
