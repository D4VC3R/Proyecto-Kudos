<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Item;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Cache;

/**
 * Servicio para obtener el siguiente ítem de una categoría que un usuario no haya votado.
 * Utiliza caché para mejorar el rendimiento y reducir la carga en la base de datos.
 * La cola se ordena por un peso (popularidad, recencia, calidad y azar), no al azar (F2-06).
 */
class NextCategoryItemService
{
	private const CACHE_TTL_SECONDS = 21600; // 6 horas
	private const BATCH_SIZE = 50; // Cuántos ítems calculamos de golpe


    /**
     * Obtiene el siguiente ítem de una categoría para un usuario, asegurando que no haya votado por él.
     * Si el usuario ya ha votado por todos los ítems de la categoría, retorna null.
     * Utiliza caché para almacenar una cola de ítems pendientes de votar por el usuario en esa categoría, y la va actualizando a medida que se van obteniendo ítems; antes de servir descarta los ítems del lote que ya no sean elegibles (F0-17).
     *
     * @param User $user Usuario para el que se obtiene el ítem.
     * @param Category $category Categoría de la que se obtiene el ítem.
     * @return array|null Retorna un array con el ítem y la cantidad restante, o null si no hay más ítems.
     */
	public function getNextItem(User $user, Category $category): ?array
	{
		$cacheKey = $this->cacheKey($user->id, $category->id);


		$queue = Cache::get($cacheKey, []);

		// Descarta los ítems del lote que ya no son elegibles: votados u omitidos por otra vía (p. ej. desde la ficha),
		// desactivados, con ficha pobre o borrados mientras estaban en la caché (F0-17)
		if (!empty($queue)) {
			$queue = $this->stillEligibleItemIds($user, $category, $queue);
		}

		if (empty($queue)) {
			$queue = $this->generateNewBatch($user, $category);
			// No queda nada por votar: borra el lote antiguo, que ya no tenía ítems elegibles
			if (empty($queue)) {
				Cache::forget($cacheKey);
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
     * Filtra la cola cacheada con los mismos criterios que generateNewBatch(): solo deja los ítems de la categoría
     * que siguen activos, descubribles (quality_score suficiente), sin borrar y sin voto (ni skip) del usuario.
     * Así la cola no ofrece ítems votados desde la ficha ni ítems desactivados o empobrecidos después de generarse el lote (F0-17).
     * Mantiene el orden del lote.
     *
     * @param User $user Usuario dueño de la cola.
     * @param Category $category Categoría de la cola.
     * @param array $queue IDs de los ítems pendientes en la caché.
     * @return array Retorna los IDs que siguen siendo elegibles, en el mismo orden.
     */
	private function stillEligibleItemIds(User $user, Category $category, array $queue): array
	{
		$eligibleIds = Item::query()
			->whereIn('id', $queue)
			->where('category_id', $category->id)
			->where('status', Item::STATUS_ACTIVE)
			->discoverable()
			->whereDoesntHave('votes', function ($query) use ($user) {
				$query->where('user_id', $user->id);
			})
			->pluck('id')
			->all();

		return array_values(array_intersect($queue, $eligibleIds));
	}

    /**
     * Genera un nuevo lote de ítems elegibles para votar por el usuario en la categoría, excluyendo aquellos por los que ya ha votado.
     * Candidatos (una sola consulta UNION): ≈300 ítems activos y con calidad suficiente (quality_score por encima del umbral) por popularidad descendente más ≈100 al azar, todos sin voto del usuario.
     * Cada candidato recibe un peso: pop·popularidad normalizada + new·recencia + qual·quality_score/100 + rand·azar (catalog.queue_weights).
     * Se ordenan por peso descendente y se toman 50; retorna sus IDs.
     *
     * @param User $user Usuario para el que se generan los ítems.
     * @param Category $category Categoría de la que se generan los ítems.
     * @return array Retorna un array con los IDs de los ítems elegibles, del más al menos pesado.
     */
	private function generateNewBatch(User $user, Category $category): array
	{
		$eligible = Item::query()
			->select(['id', 'popularity', 'released_at', 'quality_score'])
			->where('category_id', $category->id)
			->where('status', Item::STATUS_ACTIVE)
			->discoverable() // Sin fichas pobres (F0-05)
			->whereDoesntHave('votes', function ($query) use ($user) {
				$query->where('user_id', $user->id);
			});

		// Una sola consulta: los más populares más unos cuantos al azar. UNION (sin ALL) quita los repetidos.
		$popular = (clone $eligible)
			->orderByDesc('popularity')
			->orderBy('id')
			->limit((int) config('catalog.queue_candidates.popular', 300))
			->toBase();
		$random = (clone $eligible)
			->inRandomOrder()
			->limit((int) config('catalog.queue_candidates.random', 100))
			->toBase();

		// Orden estable antes de sortear: con mt_srand() el lote es reproducible (tests)
		$candidates = $popular->union($random)->get()
			->sortBy('id', SORT_STRING)
			->values();

		if ($candidates->isEmpty()) {
			return [];
		}

		$weights = config('catalog.queue_weights');
		$maxPopularity = (float) $candidates->max('popularity');
		$today = CarbonImmutable::today();

		return $candidates
			->map(fn ($candidate) => [
				'id' => $candidate->id,
				'weight' => (float) $weights['pop'] * $this->popularityScore((float) $candidate->popularity, $maxPopularity)
					+ (float) $weights['new'] * $this->recencyScore(
						$candidate->released_at !== null ? CarbonImmutable::parse($candidate->released_at) : null,
						$today
					)
					+ (float) $weights['qual'] * ((int) $candidate->quality_score / 100)
					+ (float) $weights['rand'] * (mt_rand() / mt_getrandmax()),
			])
			->sortByDesc('weight')
			->take(self::BATCH_SIZE)
			->pluck('id')
			->values()
			->all();
	}

    /**
     * Popularidad normalizada (0–1) en escala logarítmica respecto a la más alta de los candidatos:
     * la popularidad de TMDB tiene una cola larga y en escala lineal casi todo quedaría en 0.
     */
	public function popularityScore(float $popularity, float $maxPopularity): float
	{
		if ($maxPopularity <= 0) {
			return 0.0;
		}

		return min(1.0, log1p(max(0.0, $popularity)) / log1p($maxPopularity));
	}

    /**
     * Recencia (0–1): 1 si el lanzamiento es de los últimos catalog.queue_recency.full_days días o futuro;
     * baja en línea recta hasta 0 a los catalog.queue_recency.zero_years años. Sin fecha, 0.
     */
	public function recencyScore(?CarbonInterface $releasedAt, CarbonInterface $today): float
	{
		if ($releasedAt === null) {
			return 0.0;
		}

		$fullFrom = $today->copy()->subDays((int) config('catalog.queue_recency.full_days', 90));
		$zeroAt = $today->copy()->subYears((int) config('catalog.queue_recency.zero_years', 5));

		if ($releasedAt->greaterThanOrEqualTo($fullFrom)) {
			return 1.0;
		}
		if ($releasedAt->lessThanOrEqualTo($zeroAt)) {
			return 0.0;
		}

		return abs($zeroAt->diffInDays($releasedAt)) / abs($zeroAt->diffInDays($fullFrom));
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
