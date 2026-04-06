<?php

namespace App\Services;

use App\Models\Item;
use App\Models\User;
use App\Repositories\ItemRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ItemService
{
    public function __construct(
        protected ItemRepository $itemRepository,
    ) {
    }

    public function getActiveItems(array $filters, int $perPage = 10, ?User $user = null): LengthAwarePaginator
    {
        $paginator = $this->itemRepository->getActiveItems($filters, $perPage);

        // Si hay usuario logueado, cargamos sus votos de forma eficiente
        if ($user) {
            $this->itemRepository->loadUserVotes($paginator->getCollection(), $user);
        }

        return $paginator;
    }

    public function getItemsByUser(User $user): Collection
    {
        $items = $this->itemRepository->getItemsByUser($user);

        // Cargamos los votos del usuario para estos items
        $this->itemRepository->loadUserVotes($items, $user);

        return $items;
    }

    public function createItem(array $data, User $user): Item
    {
        $itemData = [
            'id' => Str::uuid(),
            'name' => $data['name'],
            'description' => $data['description'],
            'images' => $data['images'] ?? null,
            'extra_data' => $data['extra_data'] ?? null,
            'status' => Item::STATUS_ACTIVE,
            'category_id' => $data['category_id'],
            'creator_id' => $user->id,
            'vote_avg' => 0,
            'vote_count' => 0,
        ];

        $item = $this->itemRepository->create($itemData);

        return $item->load(['category', 'creator']);
    }

    public function updateItem(Item $item, array $data): Item
    {
        $updateData = [
            'name' => $data['name'] ?? $item->name,
            'description' => $data['description'] ?? $item->description,
            'images' => array_key_exists('images', $data) ? $data['images'] : $item->images,
            'extra_data' => array_key_exists('extra_data', $data) ? $data['extra_data'] : $item->extra_data,
            'category_id' => $data['category_id'] ?? $item->category_id,
            'status' => $data['status'] ?? $item->status,
        ];

        $item = $this->itemRepository->update($item, $updateData);

        return $item->load(['category', 'creator']);
    }

    public function deleteItem(Item $item): bool
    {
        return $this->itemRepository->delete($item);
    }
}