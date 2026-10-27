<?php

namespace App\Services;

use App\Models\Item;
use App\Models\Vote;
use Illuminate\Support\Facades\Cache;

/**
 * Recalcula las estadísticas de votos de un ítem (vote_count, vote_avg y bayes_score) a partir de la tabla votes.
 * Sustituye a los cálculos incrementales, que truncaban los medios puntos y acumulaban error.
 * bayes_score es la puntuación bayesiana del ranking (F0-04); la media de la categoría (C) se cachea 1 h.
 */
class ItemStatsService
{
    /** Segundos que se cachea la media de votos de una categoría (C del ranking bayesiano). */
    public const CATEGORY_MEAN_TTL = 3600;

    /**
     * Recalcula vote_count, vote_avg y bayes_score con COUNT(*) y AVG(score) de los votos de tipo 'vote'.
     * Se ejecuta dentro de la transacción del llamante: bloquea la fila del ítem con lockForUpdate().
     */
    public function recalculate(string $itemId): Item
    {
        $item = Item::lockForUpdate()->findOrFail($itemId);

        $stats = Vote::query()
            ->where('item_id', $itemId)
            ->where('type', Vote::TYPE_VOTE)
            ->toBase()
            ->selectRaw('COUNT(*) AS vote_count, AVG(score) AS vote_avg')
            ->first();

        $count = (int) $stats->vote_count;
        $avg = $count > 0 ? round((float) $stats->vote_avg, 2) : 0.0;

        $item->update([
            'vote_count' => $count,
            'vote_avg' => $avg,
            'bayes_score' => $this->bayesScore($count, $avg, $this->categoryMean($item->category_id)),
        ]);

        return $item;
    }

    /**
     * Puntuación bayesiana: (v·R + m·C) / (v + m) si v > 0; 0 si no hay votos (los ítems sin votos van al final).
     */
    public function bayesScore(int $voteCount, float $voteAvg, float $categoryMean): float
    {
        if ($voteCount <= 0) {
            return 0.0;
        }
        $m = (int) config('kudos.ranking.bayes_min_votes');

        return round(($voteCount * $voteAvg + $m * $categoryMean) / ($voteCount + $m), 4);
    }

    /**
     * Media de los votos 'vote' de la categoría (C), cacheada 1 h en category-mean:{id}.
     * Sin categoría o sin votos devuelve kudos.ranking.category_mean_fallback.
     */
    public function categoryMean(?string $categoryId): float
    {
        if ($categoryId === null) {
            return $this->fallbackMean();
        }

        return (float) Cache::remember(
            self::categoryMeanCacheKey($categoryId),
            self::CATEGORY_MEAN_TTL,
            fn () => $this->computeCategoryMean($categoryId)
        );
    }

    /** Invalida la media cacheada de la categoría y la vuelve a calcular. */
    public function refreshCategoryMean(string $categoryId): float
    {
        Cache::forget(self::categoryMeanCacheKey($categoryId));

        return $this->categoryMean($categoryId);
    }

    public static function categoryMeanCacheKey(string $categoryId): string
    {
        return "category-mean:{$categoryId}";
    }

    private function computeCategoryMean(string $categoryId): float
    {
        // Item::query() aplica SoftDeletes: no cuentan los votos de ítems borrados.
        $avg = Vote::query()
            ->where('type', Vote::TYPE_VOTE)
            ->whereIn('item_id', Item::query()->select('id')->where('category_id', $categoryId))
            ->avg('score');

        return $avg === null ? $this->fallbackMean() : (float) $avg;
    }

    private function fallbackMean(): float
    {
        return (float) config('kudos.ranking.category_mean_fallback');
    }
}
