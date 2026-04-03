<?php

namespace App\Services;

use App\Models\Item;
use App\Models\ItemComment;
use App\Models\User;
use App\Repositories\ItemCommentRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ItemCommentService
{
    public function __construct(protected ItemCommentRepository $itemCommentRepository)
    {
    }

    public function listForItem(Item $item, ?User $viewer, int $perPage = 15): LengthAwarePaginator
    {
        $includeHidden = $viewer?->hasRole('admin') ?? false;

        return $this->itemCommentRepository->paginateForItem($item, $perPage, $includeHidden);
    }

    public function create(User $user, Item $item, array $payload): ItemComment
    {
        return $this->itemCommentRepository->create([
            'item_id' => $item->id,
            'user_id' => $user->id,
            'content' => $payload['content'],
            'is_hidden' => false,
        ])->fresh(['user:id,name']);
    }

    public function update(ItemComment $comment, array $payload): ItemComment
    {
        return $this->itemCommentRepository->update($comment, [
            'content' => $payload['content'],
        ]);
    }

    public function delete(ItemComment $comment): bool
    {
        return $this->itemCommentRepository->delete($comment);
    }

    public function hide(ItemComment $comment, User $admin, ?string $reason = null): ItemComment
    {
        return $this->itemCommentRepository->update($comment, [
            'is_hidden' => true,
            'hidden_reason' => $reason,
            'hidden_by' => $admin->id,
        ]);
    }

    public function unhide(ItemComment $comment): ItemComment
    {
        return $this->itemCommentRepository->update($comment, [
            'is_hidden' => false,
            'hidden_reason' => null,
            'hidden_by' => null,
        ]);
    }
}

