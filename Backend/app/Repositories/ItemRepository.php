<?php

namespace App\Repositories;

use App\Models\Item;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ItemRepository
{
	public function getAcceptedItems(array $filters = [], int $perPage = 15): LengthAwarePaginator
	{
		$query = Item::query()
			->where('state', Item::STATE_ACCEPTED)
			->with(['category:id,name,slug', 'creator:id,name', 'tags:id,name']);

		// Filtro por categoría
		if (!empty($filters['category_id'])) {
			$query->where('category_id', $filters['category_id']);
		}

		// Filtro por búsqueda en nombre/descripción
		if (!empty($filters['search'])) {
			$search = $filters['search'];
			$query->where(function ($q) use ($search) {
				$q->where('name', 'ilike', "%{$search}%")
					->orWhere('description', 'ilike', "%{$search}%");
			});
		}

		// Filtro por tags
		if (!empty($filters['tag_ids'])) {
			$query->whereHas('tags', function ($q) use ($filters) {
				$q->whereIn('tags.id', $filters['tag_ids']);
			});
		}

		// Filtro para excluir items votados por un usuario específico
		if (!empty($filters['exclude_voted_by'])) {
			$userId = $filters['exclude_voted_by'];
			$query->whereDoesntHave('votes', function ($q) use ($userId) {
				$q->where('user_id', $userId);
			});
		}

		// Ordenamiento
		$sortBy = $filters['sort_by'] ?? 'vote_avg';
		$sortOrder = $filters['sort_order'] ?? 'desc';

		switch ($sortBy) {
			case 'vote_avg':
				$query->orderBy('vote_avg', $sortOrder)
					->orderBy('vote_count', $sortOrder);
				break;
			case 'recent':
				$query->orderBy('created_at', $sortOrder);
				break;
			case 'name':
				$query->orderBy('name', $sortOrder);
				break;
			case 'random':
				$query->inRandomOrder();
				break;
			default:
				$query->orderBy('vote_avg', 'desc');
		}

		return $query->paginate($perPage);
	}

	public function getPendingItems(int $perPage = 15): LengthAwarePaginator
	{
		return Item::query()
			->where('state', Item::STATE_PENDING)
			->with(['category:id,name,slug', 'creator:id,name,email', 'tags:id,name'])
			->orderBy('created_at', 'desc')
			->paginate($perPage);
	}

	public function getItemsByUser(User $user): Collection
	{
		return Item::query()
			->where('creator_id', $user->id)
			->with(['category:id,name,slug', 'tags:id,name'])
			->orderBy('created_at', 'desc')
			->get();
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

	/**
	 * Actualizar el estado de un item
	 */
	public function updateState(Item $item, string $state, User $admin): Item
	{
		$item->update([
			'state' => $state,
			'locked_at' => now(),
			'locked_by_admin_id' => $admin->id,
		]);

		return $item->fresh();
	}

	/**
	 * Buscar item con voto del usuario autenticado
	 */
	public function findWithUserVote(string $itemId, ?User $user = null): ?Item
	{
		$query = Item::query()
			->where('id', $itemId)
			->with(['category:id,name,slug', 'creator:id,name', 'tags:id,name']);

		if ($user) {
			$query->with(['votes' => function ($q) use ($user) {
				$q->where('user_id', $user->id);
			}]);
		}

		return $query->first();
	}

}