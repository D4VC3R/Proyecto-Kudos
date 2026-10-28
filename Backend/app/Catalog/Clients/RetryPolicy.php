<?php

namespace App\Catalog\Clients;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Throwable;

/**
 * Cuándo y cuánto esperar para reintentar una petición a una fuente externa (fuentes-externas §1.5):
 * solo ante 429, 5xx o fallo de conexión (timeout incluido). Un 404 —ni ningún otro 4xx— no se reintenta.
 */
final class RetryPolicy
{
    /** Recibe $response->toException(), que puede ser null. */
    public static function shouldRetry(?Throwable $exception): bool
    {
        if ($exception instanceof ConnectionException) {
            return true;
        }

        if ($exception instanceof RequestException) {
            $status = $exception->response->status();

            return $status === 429 || $status >= 500;
        }

        return false;
    }

    /** Espera lineal antes del reintento: intento × catalog.http.retry_sleep_ms. */
    public static function backoffMilliseconds(int $attempt): int
    {
        return $attempt * (int) config('catalog.http.retry_sleep_ms', 500);
    }
}
