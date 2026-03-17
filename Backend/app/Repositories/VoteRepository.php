<?php

namespace App\Repositories;

use App\Models\User;
use App\Models\Vote;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class VoteRepository
{
	/**
	 * Comprueba si un usuario ya ha votado un ítem concreto.
	 */
	public function findByUserAndItem(string $userId, string $itemId): ?Vote
	{
		return Vote::where('user_id', $userId)
			->where('item_id', $itemId)
			->first();
	}

	/**
	 * Inserta un nuevo voto en la base de datos.
	 */
	public function create(array $data): Vote
	{
		return Vote::create($data);
	}

			/**
			 * @param array{type?: ?string, category_id?: ?string, search?: ?string} $filters
			 */
			public function paginateByUser(User $user, array $filters = [], int $perPage = 15): LengthAwarePaginator
			{
				$query = Vote::query()
					->where('user_id', $user->id)
					->with(['item:id,name,category_id,status', 'item.category:id,name,slug']);

				if (!empty($filters['type'])) {
					$query->where('type', $filters['type']);
				}

				if (!empty($filters['category_id'])) {
					$categoryId = $filters['category_id'];
					$query->whereHas('item', function ($q) use ($categoryId) {
						$q->where('category_id', $categoryId);
					});
				}

				if (!empty($filters['search'])) {
					$search = $filters['search'];
					$query->whereHas('item', function ($q) use ($search) {
												$q->where('name', 'like', "%{$search}%");
					});
				}

				$safePerPage = min(max($perPage, 1), 100);

				return $query->orderByDesc('created_at')->paginate($safePerPage);
			}
}