<?php

namespace App\Console\Commands\Catalog;

use App\Catalog\Providers\AbstractTmdbProvider;
use App\Jobs\Catalog\PurgeStaleCatalogItemsJob;
use App\Models\Item;
use Illuminate\Console\Command;

/**
 * Encola la desactivación y limpieza de los ítems de TMDB sin refrescar en más de catalog.tmdb_max_age_days días
 * (términos de TMDB, pipeline §5). Incluye los ítems borrados. Los ítems sin fuente de TMDB no se tocan.
 * Solo encola: el trabajo lo hace el worker en la cola 'catalog' (el scheduler tiene 128M, ver docs/infra/produccion.md §2).
 */
class PurgeStaleCommand extends Command
{
    protected $signature = 'catalog:purge-stale';

    protected $description = 'Encola la desactivación y limpieza de los ítems de TMDB sin refrescar en más de 180 días';

    private const CHUNK = 200;

    public function handle(): int
    {
        $maxAge = (int) config('catalog.tmdb_max_age_days', 180);
        $cutoff = now()->subDays($maxAge);
        $jobs = 0;
        $items = 0;

        Item::withTrashed()
            ->select('id')
            ->whereHas('sources', fn ($q) => $q->where('provider', AbstractTmdbProvider::PROVIDER_KEY))
            ->where(fn ($q) => $q->whereNull('last_synced_at')->orWhere('last_synced_at', '<', $cutoff))
            ->chunkById(self::CHUNK, function ($chunk) use (&$jobs, &$items) {
                PurgeStaleCatalogItemsJob::dispatch($chunk->pluck('id')->all());
                $jobs++;
                $items += $chunk->count();
            });

        $this->info("Encolados {$jobs} jobs con {$items} ítems de TMDB sin refrescar en más de {$maxAge} días en la cola catalog.");

        return self::SUCCESS;
    }
}
