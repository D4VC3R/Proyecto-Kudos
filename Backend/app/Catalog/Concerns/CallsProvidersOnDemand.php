<?php

namespace App\Catalog\Concerns;

use App\Catalog\Exceptions\CatalogUnavailableException;
use Illuminate\Http\Client\HttpClientException;
use Illuminate\Http\Client\StrayRequestException;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Llamadas a los proveedores desde una petición del usuario (F1-07, pipeline-catalogo §7).
 *
 * La macro Http::catalog() lee catalog.http.timeout_seconds y catalog.http.retry_times en cada llamada,
 * así que basta con sustituirlos durante la petición (por catalog.on_demand.*) para acortar el timeout
 * y eliminar los reintentos de TMDB, Wikidata y Wikipedia a la vez. Se restauran siempre al terminar.
 */
trait CallsProvidersOnDemand
{
    /** Ejecuta $callback con el timeout corto y un solo intento, y restaura la configuración después. */
    protected function withShortHttpTimeout(callable $callback): mixed
    {
        $timeout = config('catalog.http.timeout_seconds');
        $retries = config('catalog.http.retry_times');

        config([
            'catalog.http.timeout_seconds' => (int) config('catalog.on_demand.timeout_seconds', 5),
            'catalog.http.retry_times' => (int) config('catalog.on_demand.retry_times', 1),
        ]);

        try {
            return $callback();
        } finally {
            config([
                'catalog.http.timeout_seconds' => $timeout,
                'catalog.http.retry_times' => $retries,
            ]);
        }
    }

    /**
     * Llama al proveedor (search/fetch) con el timeout corto y traduce sus fallos a CatalogUnavailableException.
     * Solo debe envolver llamadas al proveedor, nunca escrituras en base de datos.
     *
     * @throws CatalogUnavailableException
     */
    protected function callProvider(callable $callback): mixed
    {
        try {
            return $this->withShortHttpTimeout($callback);
        } catch (StrayRequestException $e) {
            throw $e; // R4: en los tests, una petición sin falsear nunca se convierte en 503
        } catch (HttpClientException|RuntimeException $e) {
            Log::warning('Catálogo: la fuente externa no responde (bajo demanda).', ['exception' => $e::class, 'message' => $e->getMessage()]);

            throw new CatalogUnavailableException($e->getMessage(), previous: $e);
        }
    }
}
