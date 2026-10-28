<?php

namespace App\Catalog\Clients;

use App\Catalog\Clients\Concerns\ThrottlesRequests;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

/**
 * Cliente de Wikipedia en español (fuentes-externas §4), con el límite compartido de Wikimedia.
 */
final class WikipediaClient
{
    use ThrottlesRequests;

    public const REST_URL = 'https://es.wikipedia.org/api/rest_v1/page/summary/';

    public const ACTION_URL = 'https://es.wikipedia.org/w/api.php';

    public const PAGE_URL = 'https://es.wikipedia.org/wiki/';

    /**
     * Resumen en español de una entrada de Wikipedia ES, o null si no existe o no tiene texto.
     * Usa la API REST y, si falla (cualquier respuesta que no sea 2xx o un fallo de conexión), la Action API:
     * RESTBase se está deprecando (fuentes-externas §4). El título debe salir de sitelinks.eswiki.title
     * de Wikidata; nunca se adivina.
     *
     * @return array{title: string, extract: string, url: string}|null
     *
     * @throws RequestException si la Action API falla con 429/5xx persistente
     */
    public function summary(string $title): ?array
    {
        $title = trim($title);

        if ($title === '') {
            return null;
        }

        try {
            $response = $this->request()->get(self::REST_URL.self::encodeTitle($title));

            if ($response->successful()) {
                return self::fromRest((array) $response->json(), $title);
            }
        } catch (ConnectionException) {
            // REST no responde tras los reintentos: se prueba la Action API.
        }

        return $this->fromActionApi($title);
    }

    /**
     * @return array{title: string, extract: string, url: string}|null
     */
    private function fromActionApi(string $title): ?array
    {
        $response = $this->request()->get(self::ACTION_URL, [
            'action' => 'query',
            'prop' => 'extracts|info',
            'exintro' => 1,
            'explaintext' => 1,
            'redirects' => 1,
            'inprop' => 'url',
            'format' => 'json',
            'formatversion' => 2,
            'titles' => $title,
        ]);

        if ($response->notFound()) {
            return null;
        }

        $response->throw();

        $page = $response->json('query.pages.0');

        if (! is_array($page) || ! empty($page['missing']) || ! empty($page['invalid'])) {
            return null;
        }

        return self::summaryArray($page['title'] ?? $title, $page['extract'] ?? null, $page['fullurl'] ?? null);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{title: string, extract: string, url: string}|null
     */
    private static function fromRest(array $data, string $title): ?array
    {
        return self::summaryArray($data['title'] ?? $title, $data['extract'] ?? null, $data['content_urls']['desktop']['page'] ?? null);
    }

    /**
     * @return array{title: string, extract: string, url: string}|null
     */
    private static function summaryArray(string $title, ?string $extract, ?string $url): ?array
    {
        $extract = trim((string) $extract);

        if ($extract === '') {
            return null;
        }

        return ['title' => $title, 'extract' => $extract, 'url' => $url ?: self::PAGE_URL.self::encodeTitle($title)];
    }

    /** Codifica también `/`, paréntesis y tildes. */
    private static function encodeTitle(string $title): string
    {
        return rawurlencode(str_replace(' ', '_', $title));
    }

    private function request(): PendingRequest
    {
        return Http::catalog()->beforeSending(fn () => $this->throttle(
            'catalog:wikimedia',
            (int) config('catalog.providers.wikimedia.rate_per_minute', 150),
            60,
        ));
    }
}
