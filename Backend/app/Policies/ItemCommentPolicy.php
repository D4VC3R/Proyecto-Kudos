<?php

namespace App\Policies;

use App\Models\Item;
use App\Models\ItemComment;
use App\Models\User;

class ItemCommentPolicy
{
    public function viewAny(?User $user, Item $item): bool
    {
        if ($item->status === Item::STATUS_ACTIVE) {
            return true;
        }

        return $user?->hasRole('admin') ?? false;
    }

    public function create(User $user, Item $item): bool
    {
        return $item->status === Item::STATUS_ACTIVE;
    }

    public function update(User $user, ItemComment $comment): bool
    {
        return $user->hasRole('admin') || $user->id === $comment->user_id;
    }

    public function delete(User $user, ItemComment $comment): bool
    {
        return $user->hasRole('admin') || $user->id === $comment->user_id;
    }

    public function hide(User $user, ItemComment $comment): bool
    {
        return $user->hasRole('admin');
    }

    public function unhide(User $user, ItemComment $comment): bool
    {
        return $user->hasRole('admin');
    }
}

