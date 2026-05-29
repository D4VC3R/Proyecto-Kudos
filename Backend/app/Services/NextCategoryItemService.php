<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Item;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

/**
 * Servicio para obtener el siguiente ítem de una categoría que un usuario no haya votado.
 * Utiliza caché para mejorar el rendimiento y reducir la carga en la base de datos.
 */
class NextCategoryItemService
{
	private const CACHE_TTL_SECONDS = 21600; // 6 horas
	private const BATCH_SIZE = 50; // Cuántos ítems calculamos de golpe


    /**
     * Obtiene el siguiente ítem de una categoría para un usuario, asegurando que no haya votado por él.
     * Si el usuario ya ha votado por todos los ítems de la categoría, retorna null.
     * Utiliza caché para almacenar una cola de ítems pendientes de votar por el usuario en esa categoría, y la va actualizando a medida que se van obteniendo ítems.
     *
     * @param User $user Usuario para el que se obtiene el ítem.
     * @param Category $category Categoría de la que se obtiene el ítem.
     * @return array|null Retorna un array con el ítem y la cantidad restante, o null si no hay más ítems.
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
     * Genera un nuevo lote de ítems elegibles para votar por el usuario en la categoría, excluyendo aquellos por los que ya ha votado.
     * Selecciona aleatoriamente hasta 50 ítems activos de la categoría que el usuario no haya votado, y retorna sus IDs.
     *
     * @param User $user Usuario para el que se generan los ítems.
     * @param Category $category Categoría de la que se generan los ítems.
     * @return array Retorna un array con los IDs de los ítems elegibles.
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

    /**
     * Genera la clave de caché para almacenar la cola de ítems de un usuario en una categoría.
     *
     * @param string $userId ID del usuario.
     * @param string $categoryId ID de la categoría.
     * @return string Retorna la clave de caché.
     */
	private function cacheKey(string $userId, string $categoryId): string
	{
		return "next-item-queue:{$userId}:{$categoryId}";
	}
}
