<?php

namespace App\Jobs;

use App\Models\Item;
use App\Services\ItemStatsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;

/**
 * Recalcula vote_count y vote_avg de un bloque de ítems. Lo encola items:recalculate-stats en la cola 'catalog'.
 */
class RecalculateItemStatsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    /**
     * @param array<int, string> $itemIds IDs de los ítems del bloque.
     */
    public function __construct(public array $itemIds)
    {
        $this->onQueue('catalog');
    }

    public function handle(ItemStatsService $itemStatsService): void
    {
        // Se ignoran los ítems borrados (soft delete) entre el encolado y la ejecución.
        $ids = Item::query()->whereKey($this->itemIds)->pluck('id');

        foreach ($ids as $id) {
            // Una transacción corta por ítem: el bloqueo de la fila dura solo su recálculo.
            DB::transaction(fn () => $itemStatsService->recalculate($id));
        }
    }
}
