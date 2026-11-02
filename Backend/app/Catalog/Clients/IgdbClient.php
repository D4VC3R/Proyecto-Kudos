<?php

namespace App\Catalog\Clients;

use App\Catalog\Clients\Concerns\ThrottlesRequests;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Cliente de la API v4 de IGDB (fuentes-externas §4): token de aplicación de Twitch (client_credentials)
 * cacheado hasta que caduca, consultas Apicalypse por POST y limitación a catalog.providers.igdb.rate_per_second.
 */
final class IgdbClient
{
    use ThrottlesRequests;

    public const BASE_URL = 'https://api.igdb.com/v4';

    public const TOKEN_URL = 'https://id.twitch.tv/oauth2/token';

    public const TOKEN_CACHE_KEY = 'catalog:igdb:token';

    /**
     * POST de una consulta Apicalypse a un endpoint (games, external_game_sources…). No lanza por el estado HTTP,
     * como TmdbClient: quien llama decide. Ante un 401 (token revocado o caducado) pide otro token y repite una vez.
     *
     * @throws RuntimeException si faltan IGDB_CLIENT_ID o IGDB_CLIENT_SECRET, o Twitch no devuelve token
     * @throws RequestException si Twitch rechaza las credenciales
     * @throws ConnectionException si no hay conexión tras los reintentos
     */
    public function query(string $endpoint, string $body): Response
    {
        $response = $this->send($endpoint, $body, $this->token());

        if ($response->status() === 401) {
            Cache::forget(self::TOKEN_CACHE_KEY);
            $response = $this->send($endpoint, $body, $this->token());
        }

        return $response;
    }

    private function send(string $endpoint, string $body, string $token): Response
    {
        [$clientId] = $this->credentials();

        return Http::catalog()
            ->baseUrl(self::BASE_URL)
            ->withHeaders(['Client-ID' => $clientId])
            ->withToken($token)
            ->withBody($body, 'text/plain')
            ->beforeSending(fn () => $this->throttle(
                'catalog:igdb',
                (int) config('catalog.providers.igdb.rate_per_second', 3),
                1,
            ))
            ->post(ltrim($endpoint, '/'));
    }

    /** Token de aplicación de Twitch, cacheado hasta un minuto antes de que caduque. */
    private function token(): string
    {
        [$clientId, $clientSecret] = $this->credentials();

        $cached = Cache::get(self::TOKEN_CACHE_KEY);

        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        // Las credenciales van en el cuerpo del formulario, nunca en la URL.
        $response = Http::catalog()
            ->asForm()
            ->post(self::TOKEN_URL, [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'grant_type' => 'client_credentials',
            ])
            ->throw();

        $token = $response->json('access_token');

        if (! is_string($token) || $token === '') {
            throw new RuntimeException('Twitch no ha devuelto un token de aplicación para IGDB.');
        }

        Cache::put(
            self::TOKEN_CACHE_KEY,
            $token,
            now()->addSeconds(max((int) $response->json('expires_in') - 60, 60)),
        );

        return $token;
    }

    /** @return array{0: string, 1: string} */
    private function credentials(): array
    {
        $clientId = config('catalog.providers.igdb.client_id');
        $clientSecret = config('catalog.providers.igdb.client_secret');

        if (blank($clientId) || blank($clientSecret)) {
            throw new RuntimeException('Faltan IGDB_CLIENT_ID o IGDB_CLIENT_SECRET: los da de alta el humano en Backend/.env (F4-01).');
        }

        return [(string) $clientId, (string) $clientSecret];
    }
}
