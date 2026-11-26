<?php

namespace App\Jobs\Catalog;

use App\Catalog\CatalogImporter;
use App\Catalog\CatalogProviderRegistry;
use App\Catalog\Enrichment\DescriptionResolver;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use InvalidArgumentException;
use UnexpectedValueException;

/**
 * Importa o refresca un ítem del catálogo: ficha del proveedor, descripción en español, upsert y
 * encolado de la descarga de imágenes (pipeline-catalogo §4). Cola 'catalog'.
 * Único por (categoría, external_id) mientras está en la cola.
 */
class ImportCatalogItemJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    /** Intentos en total; solo los fallos transitorios (red, 5xx) llegan a reintentarse. */
    public int $tries = 3;

    /** Segundos antes del 2.º y del 3.er intento. */
    public array $backoff = [30, 120];

    /** Una ficha de IGDB son hasta 7 peticiones en serie más las esperas del RateLimiter: 60 s no bastan. */
    public int $timeout = 120;

    /** El candado de unicidad dura como mucho una hora aunque el worker muera a mitad (caché: Redis en producción). */
    public int $uniqueFor = 3600;

    /** La categoría elige el proveedor: películas y series comparten la clave 'tmdb' (F1-03). */
    public function __construct(public string $categorySlug, public string $externalId)
    {
        $this->onQueue('catalog');
    }

    /** fetch-new y refresh pueden encolar el mismo título a la vez: solo uno espera en la cola. */
    public function uniqueId(): string
    {
        return $this->categorySlug.':'.$this->externalId;
    }

    public function handle(CatalogProviderRegistry $registry, DescriptionResolver $resolver, CatalogImporter $importer): void
    {
        try {
            $this->import($registry, $resolver, $importer);
        } catch (UnexpectedValueException|InvalidArgumentException $e) {
            // Una respuesta que no se entiende o una categoría/external_id no válidos no se arreglan
            // reintentando: el job pasa directamente a failed_jobs.
            $this->fail($e);
        }
    }

    private function import(CatalogProviderRegistry $registry, DescriptionResolver $resolver, CatalogImporter $importer): void
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
