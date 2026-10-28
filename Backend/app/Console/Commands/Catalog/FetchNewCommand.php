<?php

namespace App\Console\Commands\Catalog;

use App\Catalog\CatalogProviderRegistry;
use App\Jobs\Catalog\FetchNewCatalogItemsJob;
use App\Models\Category;
use Illuminate\Console\Command;

/**
 * Encola la importación de novedades y populares de una o de todas las categorías con proveedor.
 * Solo encola: no llama a TMDB; el trabajo lo hace el worker en la cola 'catalog' (el scheduler tiene 128M, ver docs/infra/produccion.md §2).
 */
class FetchNewCommand extends Command
{
    protected $signature = 'catalog:fetch-new
        {--category= : Slug de la categoría (peliculas, series…)}
        {--all : Todas las categorías activas con proveedor}
        {--limit= : Máximo de ítems por categoría (por defecto, CATALOG_IMPORT_LIMIT)}';

    protected $description = 'Encola la importación de novedades y populares del catálogo en la cola catalog';

    public function handle(CatalogProviderRegistry $registry): int
    {
        $slug = $this->option('category');
        $all = (bool) $this->option('all');
        $hasSlug = is_string($slug) && $slug !== '';

        if ($all === $hasSlug) {
            $this->error('Indica --category=<slug> o --all (solo una de las dos).');

            return self::FAILURE;
        }

        $raw = $this->option('limit') ?? config('catalog.import_limit');
        $limit = filter_var($raw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        if ($limit === false) {
            $this->error('--limit debe ser un entero positivo.');

            return self::FAILURE;
        }

        $queued = [];

        if ($hasSlug) {
            if (! $registry->has($slug)) {
                $this->error("La categoría '{$slug}' no tiene proveedor de catálogo.");

                return self::FAILURE;
            }

            $category = Category::query()->where('slug', $slug)->first();

            if ($category === null) {
                $this->error("No existe la categoría '{$slug}'.");

                return self::FAILURE;
            }

            if (! $category->is_active) {
                // Petición explícita: se encola igualmente (útil para preparar una categoría antes de activarla).
                $this->warn("La categoría '{$slug}' está inactiva; se encola igualmente.");
            }

            $queued[] = $slug;
        } else {
            foreach ($registry->categorySlugs() as $candidate) {
                if (! $registry->has($candidate)) {
                    $this->warn("Se omite '{$candidate}': no tiene proveedor de catálogo.");

                    continue;
                }

                $category = Category::query()->where('slug', $candidate)->first();

                if ($category === null) {
                    $this->warn("Se omite '{$candidate}': no existe la categoría.");

                    continue;
                }

                if (! $category->is_active) {
                    $this->warn("Se omite '{$candidate}': la categoría está inactiva.");

                    continue;
                }

                $queued[] = $candidate;
            }

            if ($queued === []) {
                $this->error('Ninguna categoría con proveedor existe y está activa.');

                return self::FAILURE;
            }
        }

        foreach ($queued as $categorySlug) {
            FetchNewCatalogItemsJob::dispatch($categorySlug, $limit);
            $this->info("Encolada la importación de novedades de '{$categorySlug}' (hasta {$limit} ítems) en la cola catalog.");
        }

        return self::SUCCESS;
    }
}
