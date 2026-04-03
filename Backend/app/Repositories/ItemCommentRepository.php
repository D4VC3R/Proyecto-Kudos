<?php

namespace App\Repositories;

use App\Models\Item;
use App\Models\ItemComment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ItemCommentRepository
{
	public function paginateForItem(Item $item, int $perPage = 15, bool $includeHidden = false): LengthAwarePaginator
	{
		$query = ItemComment::query()
			->where('item_id', $item->id)
			->with(['user:id,name'])
			->orderByDesc('created_at');

		if (!$includeHidden) {
			$query->where('is_hidden', false);
		}

		return $query->paginate(min(max($perPage, 1), 100));
	}

	public function create(array $data): ItemComment
	{
		return ItemComment::create($data);
	}

	public function update(ItemComment $comment, array $data): ItemComment
	{
		$comment->update($data);

		return $comment->fresh(['user:id,name']);
	}

	public function delete(ItemComment $comment): bool
	{
		return $comment->delete();
	}
}

