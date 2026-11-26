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
 * La macro Http::catalog() lee catalog.http.timeout_seconds y catalog.http.retry_times en cada llamada, y
 * ThrottlesRequests lee catalog.http.max_throttle_wait_seconds, así que basta con sustituirlos durante la
 * petición (por catalog.on_demand.*) para acortar el timeout, eliminar los reintentos y no esperar más de
 * unos segundos al RateLimiter en todos los clientes del catálogo (TMDB, IGDB, Steam, Wikidata, Wikipedia
 * y Commons). Se restauran siempre al terminar.
 */
trait CallsProvidersOnDemand
{
    /** Ejecuta $callback con el timeout corto, un solo intento y la espera al RateLimiter acotada, y restaura la configuración después. */
    protected function withShortHttpTimeout(callable $callback): mixed
    {
        $timeout = config('catalog.http.timeout_seconds');
        $retries = config('catalog.http.retry_times');
        $maxWait = config('catalog.http.max_throttle_wait_seconds');

        config([
            'catalog.http.timeout_seconds' => (int) config('catalog.on_demand.timeout_seconds', 5),
            'catalog.http.retry_times' => (int) config('catalog.on_demand.retry_times', 1),
            'catalog.http.max_throttle_wait_seconds' => (int) config('catalog.on_demand.max_throttle_wait_seconds', 5),
        ]);

        try {
            return $callback();
        } finally {
            config([
                'catalog.http.timeout_seconds' => $timeout,
                'catalog.http.retry_times' => $retries,
                'catalog.http.max_throttle_wait_seconds' => $maxWait,
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
