<?php

namespace App\Console\Commands\Catalog;

use App\Catalog\CatalogProviderRegistry;
use App\Jobs\Catalog\ImportCatalogItemJob;
use App\Models\Category;
use App\Models\Item;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Encola el refresco de los ítems del catálogo sincronizados hace más de N días, los más antiguos primero.
 * Solo encola: el trabajo lo hace el worker en la cola 'catalog' (el scheduler tiene 128M, ver docs/infra/produccion.md §2).
 * Incluye los ítems inactivos (siguen inactivos: el importador no toca el status) y excluye los borrados.
 */
class RefreshCommand extends Command
{
    protected $signature = 'catalog:refresh
        {--older-than= : Días desde la última sincronización (por defecto, catalog.refresh_older_than_days = 150)}
        {--batch=200 : Máximo de ítems que se encolan en esta ejecución}';

    protected $description = 'Encola el refresco de los ítems del catálogo sincronizados hace más de N días en la cola catalog';

    public function handle(CatalogProviderRegistry $registry): int
    {
        $olderThan = filter_var(
            $this->option('older-than') ?? config('catalog.refresh_older_than_days', 150),
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );
        $batch = filter_var($this->option('batch'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        if ($olderThan === false || $batch === false) {
            $this->error('--older-than y --batch deben ser enteros positivos.');

            return self::FAILURE;
        }

        // Aquí no se filtra por is_active: los términos de TMDB obligan a refrescar también lo que no se muestra.
        $slugs = array_values(array_filter($registry->categorySlugs(), fn ($slug) => $registry->has($slug)));
        $categories = Category::query()->whereIn('slug', $slugs)->pluck('slug', 'id');

        $cutoff = now()->subDays($olderThan);

        $stale = Item::query()
            ->whereIn('category_id', $categories->keys())
            ->whereHas('sources', fn ($q) => $q->where('is_primary', true))
            ->where(fn ($q) => $q->whereNull('last_synced_at')->orWhere('last_synced_at', '<', $cutoff));

        $pending = (clone $stale)->count();

        $items = $stale
            ->with(['sources' => fn ($q) => $q->where('is_primary', true)])
            ->orderByRaw('last_synced_at asc nulls first')
            ->orderBy('id')
            ->limit($batch)
            ->get(['id', 'category_id', 'last_synced_at']);

        $queued = 0;

        foreach ($items as $item) {
            $source = $item->sources->first();

            if ($source === null) {
                continue;
            }

            ImportCatalogItemJob::dispatch($categories[$item->category_id], $source->external_id);
            $queued++;
        }

        $this->info("Encolados {$queued} ítems para refrescar en la cola catalog (sincronizados hace más de {$olderThan} días).");

        if ($pending > $batch) {
            $left = $pending - $batch;

            Log::warning('Catálogo: quedan ítems sin refrescar tras este lote; sube --batch si el número crece.', [
                'pending' => $left,
                'batch' => $batch,
                'older_than_days' => $olderThan,
            ]);
            $this->warn("Quedan {$left} ítems pendientes de refrescar.");
        }

        return self::SUCCESS;
    }
}
