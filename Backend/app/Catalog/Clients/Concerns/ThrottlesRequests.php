<?php

namespace App\Catalog\Clients\Concerns;

use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Sleep;

/**
 * Limitación de peticiones por proveedor con el RateLimiter de la caché (Redis en producción,
 * compartido entre workers): espera a que haya hueco en la ventana en lugar de fallar.
 */
trait ThrottlesRequests
{
    protected function throttle(string $key, int $maxAttempts, int $decaySeconds): void
    {
        while (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            Sleep::for(max(RateLimiter::availableIn($key), 1))->seconds();
        }

        RateLimiter::hit($key, $decaySeconds);
    }
}
