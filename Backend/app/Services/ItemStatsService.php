<?php

namespace App\Services;

use App\Models\Item;
use App\Models\Vote;

/**
 * Recalcula las estadísticas de votos de un ítem (vote_count y vote_avg) a partir de la tabla votes.
 * Sustituye a los cálculos incrementales, que truncaban los medios puntos y acumulaban error.
 */
class ItemStatsService
{
    /**
     * Recalcula vote_count y vote_avg con COUNT(*) y AVG(score) de los votos de tipo 'vote'.
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

        $item->update([
            'vote_count' => $count,
            'vote_avg' => $count > 0 ? round((float) $stats->vote_avg, 2) : 0,
        ]);

        return $item;
    }
}
