<?php

namespace App\Providers;

use App\Catalog\Clients\RetryPolicy;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;

/**
 * Registra el cliente HTTP común del catálogo (docs/integraciones/fuentes-externas.md §1).
 */
class CatalogServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Http::catalog(): User-Agent identificativo, JSON, timeout y reintentos con espera solo ante
        // 429, 5xx o fallo de conexión. Con throw: false, un 404 (o el último 429 tras agotar los
        // intentos) se devuelve como respuesta y decide quien llama; un fallo de conexión sí lanza.
        Http::macro('catalog', function (): PendingRequest {
            /** @var Factory $this */
            return $this->withUserAgent((string) config('catalog.user_agent'))
                ->acceptJson()
                ->timeout((int) config('catalog.http.timeout_seconds', 10))
                ->retry(
                    (int) config('catalog.http.retry_times', 3),
                    RetryPolicy::backoffMilliseconds(...),
                    RetryPolicy::shouldRetry(...),
                    throw: false,
                );
        });
    }
}
