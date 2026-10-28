<?php

namespace App\Jobs\Catalog;

use App\Catalog\CatalogImporter;
use App\Catalog\CatalogProviderRegistry;
use App\Catalog\Enrichment\DescriptionResolver;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Importa o refresca un ítem del catálogo: ficha del proveedor, descripción en español, upsert y
 * encolado de la descarga de imágenes (pipeline-catalogo §4). Cola 'catalog'.
 */
class ImportCatalogItemJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    /** La categoría elige el proveedor: películas y series comparten la clave 'tmdb' (F1-03). */
    public function __construct(public string $categorySlug, public string $externalId)
    {
        $this->onQueue('catalog');
    }

    public function handle(CatalogProviderRegistry $registry, DescriptionResolver $resolver, CatalogImporter $importer): void
    {
        $provider = $registry->forCategory($this->categorySlug);
        $data = $provider->fetch($this->externalId);

        if ($data === null) {
            $importer->deactivate($provider->key(), $this->externalId);

            return;
        }

        $item = $importer->import($resolver->resolve($data));

        if (! $item->trashed()) {
            // Siempre: el job solo descarga lo que cambia o falta.
            DownloadItemImagesJob::dispatch($item->id, $data->images);
        }
    }
}
