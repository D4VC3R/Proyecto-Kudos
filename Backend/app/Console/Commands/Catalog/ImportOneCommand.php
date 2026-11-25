<?php

namespace App\Console\Commands\Catalog;

use App\Catalog\CatalogProviderRegistry;
use App\Jobs\Catalog\ImportCatalogItemJob;
use App\Models\Category;
use Illuminate\Console\Command;

/**
 * Encola la importación (o el refresco) de un ítem concreto: la categoría sale del proveedor y del external_id.
 * Es manual (no se programa). Con QUEUE_CONNECTION=sync se ejecuta al momento.
 */
class ImportOneCommand extends Command
{
    protected $signature = 'catalog:import
        {provider : Clave del proveedor (tmdb, igdb o wikidata)}
        {externalId : ID en el proveedor: movie:603 o tv:1399 (tmdb), 113112 (igdb) o el QID Q2807 (wikidata)}';

    protected $description = 'Encola la importación (o el refresco) de un ítem concreto del catálogo en la cola catalog';

    public function handle(CatalogProviderRegistry $registry): int
    {
        $provider = (string) $this->argument('provider');
        $externalId = (string) $this->argument('externalId');

        $slugs = $registry->categoriesFor($provider, $externalId);

        if (count($slugs) === 0) {
            $this->error("Ningún proveedor '{$provider}' configurado reconoce '{$externalId}'.");

            return self::FAILURE;
        }

        if (count($slugs) > 1) {
            $this->error("'{$externalId}' es ambiguo: lo reconocen las categorías ".implode(', ', $slugs).'.');

            return self::FAILURE;
        }

        $slug = $slugs[0];

        if (! Category::query()->where('slug', $slug)->exists()) {
            $this->error("No existe la categoría '{$slug}'.");

            return self::FAILURE;
        }

        ImportCatalogItemJob::dispatch($slug, $externalId);
        $this->info("Encolada la importación de {$provider} {$externalId} en '{$slug}' (cola catalog).");

        return self::SUCCESS;
    }
}
