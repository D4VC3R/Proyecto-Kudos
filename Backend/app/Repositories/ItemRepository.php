<?php

namespace App\Repositories;

use App\Models\Item;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ItemRepository
{
    public function getActiveItems(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Item::query()
            ->where('status', Item::STATUS_ACTIVE)
            ->with(['category:id,name,slug,image,description,created_at,updated_at', 'creator:id,name']);

        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (!empty($filters['search'])) {
            $query->where('name', 'ilike', "%{$filters['search']}%");
        }

        if (!empty($filters['exclude_voted_by'])) {
            $userId = $filters['exclude_voted_by'];
            $query->whereDoesntHave('votes', fn ($q) => $q->where('user_id', $userId));
        }

        $sortBy = $filters['sort_by'] ?? 'vote_avg';
        $sortOrder = $filters['sort_order'] ?? 'desc';

        match ($sortBy) {
            'vote_avg' => $query->orderBy('vote_avg', $sortOrder)->orderBy('vote_count', $sortOrder),
            'recent' => $query->orderBy('created_at', $sortOrder),
            'name' => $query->orderBy('name', $sortOrder),
            'random' => $query->inRandomOrder(),
            default => $query->orderBy('vote_avg', 'desc')->orderBy('vote_count', 'desc'),
        };

        return $query->paginate($perPage);
    }

    public function getItemsByUser(User $user): Collection
    {
        $query = Item::query()
            ->where('creator_id', $user->id)
            ->with(['category:id,name,slug,image,description,created_at,updated_at'])
            ->orderBy('created_at', 'desc');

        if (!$user->hasRole('admin')) {
            $query->where('status', Item::STATUS_ACTIVE);
        }

        return $query->get();
    }

    public function create(array $data): Item
    {
        return Item::create($data);
    }

    public function update(Item $item, array $data): Item
    {
        $item->update($data);
        return $item->fresh();
    }

    public function delete(Item $item): bool
    {
        return $item->delete();
    }

    public function findWithUserVote(string $itemId, ?User $user = null): ?Item
    {
        $query = Item::query()
            ->where('id', $itemId)
            ->with(['category:id,name,slug', 'creator:id,name']);

        if ($user) {
            $query->with(['userVote' => fn ($q) => $q->where('user_id', $user->id)]);
        }

        return $query->first();
    }

    public function loadUserVotes(Collection $items, User $user): void
    {
        if ($items->isEmpty()) {
            return;
        }

        $userVotes = Vote::where('user_id', $user->id)
            ->whereIn('item_id', $items->pluck('id'))
            ->get()
            ->keyBy('item_id');

        $items->each(function (Item $item) use ($userVotes) {
            $item->setRelation('userVote', $userVotes->get($item->id));
        });
    }
}