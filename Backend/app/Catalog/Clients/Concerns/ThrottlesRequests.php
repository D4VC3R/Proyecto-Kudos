<?php

namespace App\Catalog\Clients\Concerns;

use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Sleep;
use RuntimeException;

/**
 * Limitación de peticiones por proveedor con el RateLimiter de la caché (Redis en producción,
 * compartido entre workers): espera a que haya hueco en la ventana en lugar de fallar.
 *
 * Si `catalog.http.max_throttle_wait_seconds` tiene valor (solo durante una petición del usuario, ver
 * `CallsProvidersOnDemand`), no se espera más de esos segundos en total: se lanza una `RuntimeException`,
 * que `callProvider()` traduce en 503. En los jobs no hay límite.
 */
trait ThrottlesRequests
{
    /** @throws RuntimeException si hay que esperar más de catalog.http.max_throttle_wait_seconds */
    protected function throttle(string $key, int $maxAttempts, int $decaySeconds): void
    {
        $maxWait = config('catalog.http.max_throttle_wait_seconds');
        $waited = 0;

        while (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $wait = max(RateLimiter::availableIn($key), 1);

            if ($maxWait !== null && $waited + $wait > (int) $maxWait) {
                throw new RuntimeException("Límite de peticiones de '{$key}' agotado: habría que esperar {$wait} s y el máximo es {$maxWait} s.");
            }

            Sleep::for($wait)->seconds();
            $waited += $wait;
        }

        RateLimiter::hit($key, $decaySeconds);
    }
}
