<?php

namespace App\Jobs;

use App\Models\Item;
use App\Services\ItemQualityScorer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;

/**
 * Recalcula quality_score de un bloque de ítems. Lo encola items:recalculate-quality en la cola 'catalog'.
 */
class RecalculateItemQualityJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    /**
     * @param array<int, string> $itemIds IDs de los ítems del bloque.
     */
    public function __construct(public array $itemIds)
    {
        $this->onQueue('catalog');
    }

    public function handle(ItemQualityScorer $scorer): void
    {
        // Se ignoran los ítems borrados (soft delete) entre el encolado y la ejecución.
        $items = Item::query()->whereKey($this->itemIds)->get();

        foreach ($items as $item) {
            $score = $scorer->score($item);

            if ($score !== (int) $item->quality_score) {
                // Escritura directa: sin eventos y sin tocar updated_at, porque es un recálculo y no una edición.
                DB::table('items')->where('id', $item->id)->update(['quality_score' => $score]);
            }
        }
    }
}
