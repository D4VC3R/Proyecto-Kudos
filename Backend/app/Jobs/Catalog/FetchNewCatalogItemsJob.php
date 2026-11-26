<?php

namespace App\Jobs\Catalog;

use App\Catalog\CatalogProviderRegistry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Pide al proveedor de una categoría sus novedades y populares (fetchNew) y encola un ImportCatalogItemJob
 * por cada external_id: fetchNew() devuelve fichas parciales y la completa la da fetch() (F1-03).
 * Lo encola catalog:fetch-new; va en el worker y no en el scheduler, que tiene 128M (pipeline §5). Cola 'catalog'.
 * Los ítems que ya existen también se encolan: ImportCatalogItemJob los refresca sin duplicar.
 */
class FetchNewCatalogItemsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    /** Valores explícitos para no depender de las opciones del worker (--tries, --backoff, --timeout). */
    public int $tries = 3;

    public array $backoff = [30, 120];

    public int $timeout = 120;

    public function __construct(public string $categorySlug, public int $limit)
    {
        $this->onQueue('catalog');
    }

    public function handle(CatalogProviderRegistry $registry): void
    {
        foreach ($registry->forCategory($this->categorySlug)->fetchNew($this->limit) as $data) {
            ImportCatalogItemJob::dispatch($this->categorySlug, $data->externalId);
        }
    }
}
