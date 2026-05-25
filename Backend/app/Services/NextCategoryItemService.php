<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Item;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class NextCategoryItemService
{
	private const CACHE_TTL_SECONDS = 21600; // 6 horas
	private const BATCH_SIZE = 50; // Cuántos ítems calculamos de golpe

	/**
	 * @return array{item: Item, remaining: int}|null
	 */
	public function getNextItem(User $user, Category $category): ?array
	{
		$cacheKey = $this->cacheKey($user->id, $category->id);


		$queue = Cache::get($cacheKey, []);

		if (empty($queue)) {
			$queue = $this->generateNewBatch($user, $category);
			// No queda nada por votar
			if (empty($queue)) {
				return null;
			}
		}
		// Extrae el primer ítem de la cola
		$nextItemId = array_shift($queue);

		//  Actualiza la caché con la cola restante
		if (empty($queue)) {
			Cache::forget($cacheKey);
		} else {
			Cache::put($cacheKey, $queue, now()->addSeconds(self::CACHE_TTL_SECONDS));
		}

		$item = Item::query()
			->with(['category', 'creator'])
			->find($nextItemId);

		// Por si el ítem fue borrado por un admin mientras estaba en la cola de alguien
		if (!$item) {
			return $this->getNextItem($user, $category);
		}

		return [
			'item' => $item,
			'remaining' => count($queue),
		];
	}

	/**
	 * Busca en la BD un nuevo lote de ítems aleatorios que el usuario no haya votado.
	 */
	private function generateNewBatch(User $user, Category $category): array
	{
		$eligibleItemIds = Item::query()
			->where('category_id', $category->id)
			->where('status', Item::STATUS_ACTIVE)
			->whereDoesntHave('votes', function ($query) use ($user) {
				$query->where('user_id', $user->id);
			})
			->inRandomOrder() // La BD se encarga del orden aleatorio
			->limit(self::BATCH_SIZE) // Solo traemos 50
			->pluck('id')
			->all();

		return $eligibleItemIds;
	}

	private function cacheKey(string $userId, string $categoryId): string
	{
		return "next-item-queue:{$userId}:{$categoryId}";
	}
}