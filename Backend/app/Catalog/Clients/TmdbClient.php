<?php

namespace App\Catalog\Clients;

use App\Catalog\Clients\Concerns\ThrottlesRequests;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Cliente de la API v3 de TMDB (fuentes-externas §4): Bearer con el token v4, siempre en es-ES,
 * timeout, reintentos solo ante 429/5xx y limitación a catalog.providers.tmdb.rate_per_second.
 */
final class TmdbClient
{
    use ThrottlesRequests;

    public const BASE_URL = 'https://api.themoviedb.org/3';

    public const LANGUAGE = 'es-ES';

    /**
     * GET a TMDB. No lanza por el estado HTTP: quien llama decide (un 404 = no existe, y no se reintenta).
     * Tras agotar los reintentos devuelve la última respuesta (429 o 5xx).
     *
     * @param  array<string, scalar>  $query  el idioma siempre es es-ES, aunque se pida otro
     *
     * @throws RuntimeException si falta TMDB_API_TOKEN
     * @throws ConnectionException si no hay conexión tras los reintentos
     */
    public function get(string $path, array $query = []): Response
    {
        $token = config('catalog.providers.tmdb.token');

        if (blank($token)) {
            throw new RuntimeException('Falta TMDB_API_TOKEN: lo da de alta el humano en Backend/.env (F1-00).');
        }

        return Http::catalog()
            ->baseUrl(self::BASE_URL)
            ->withToken($token)
            ->beforeSending(fn () => $this->throttle(
                'catalog:tmdb',
                (int) config('catalog.providers.tmdb.rate_per_second', 20),
                1,
            ))
            ->get(ltrim($path, '/'), array_merge($query, ['language' => self::LANGUAGE]));
    }
}
