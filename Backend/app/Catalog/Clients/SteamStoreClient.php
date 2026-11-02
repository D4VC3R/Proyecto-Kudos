<?php

namespace App\Catalog\Clients;

use App\Catalog\Clients\Concerns\ThrottlesRequests;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use UnexpectedValueException;

/**
 * Cliente de la Steam Store (appdetails, sin clave, endpoint no documentado; fuentes-externas §4). Solo se usa
 * la descripción corta. Una sola petición por juego, con l=spanish y cc=es, y 1 petición cada
 * catalog.providers.steam.per_seconds segundos.
 */
final class SteamStoreClient
{
    use ThrottlesRequests;

    public const APPDETAILS_URL = 'https://store.steampowered.com/api/appdetails';

    public const STORE_URL = 'https://store.steampowered.com/app/';

    /**
     * Descripción corta y lenguajes de un appid, o null si Steam no lo tiene ({"<appid>": {"success": false}}
     * o un 404, que no se reintenta).
     *
     * @return array{short_description: ?string, supported_languages: ?string}|null
     *
     * @throws InvalidArgumentException si el appid no es numérico
     * @throws RequestException ante un 429/5xx persistente u otro estado de error
     * @throws ConnectionException si no hay conexión tras los reintentos
     * @throws UnexpectedValueException si la respuesta no trae la clave del appid
     */
    public function appDetails(string $appId): ?array
    {
        if (preg_match('/^[1-9]\d*$/', $appId) !== 1) {
            throw new InvalidArgumentException("Appid de Steam no válido: '{$appId}'.");
        }

        $response = Http::catalog()
            ->beforeSending(fn () => $this->throttle(
                'catalog:steam',
                (int) config('catalog.providers.steam.max_requests', 1),
                (int) config('catalog.providers.steam.per_seconds', 2),
            ))
            ->get(self::APPDETAILS_URL, ['appids' => $appId, 'l' => 'spanish', 'cc' => 'es']);

        if ($response->notFound()) {
            return null;
        }

        $response->throw();

        $entry = $response->json($appId);

        if (! is_array($entry)) {
            throw new UnexpectedValueException("Steam devolvió una respuesta sin la clave del appid '{$appId}'.");
        }

        if (($entry['success'] ?? false) !== true) {
            return null;
        }

        $languages = $entry['data']['supported_languages'] ?? null;

        return [
            'short_description' => self::plainText($entry['data']['short_description'] ?? null),
            'supported_languages' => is_string($languages) ? $languages : null,
        ];
    }

    /** true si la lista de idiomas de la tienda (HTML, escrita con l=spanish) incluye el español. */
    public static function supportsSpanish(?string $supportedLanguages): bool
    {
        return mb_stripos(html_entity_decode(strip_tags((string) $supportedLanguages), ENT_QUOTES | ENT_HTML5, 'UTF-8'), 'español') !== false;
    }

    public static function storeUrl(string $appId): string
    {
        return self::STORE_URL."{$appId}/";
    }

    /** Texto plano: sin etiquetas, con las entidades HTML decodificadas y los espacios colapsados. */
    private static function plainText(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $text = html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = trim((string) preg_replace('/\s+/u', ' ', $text));

        return $text !== '' ? $text : null;
    }
}
