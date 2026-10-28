<?php

namespace App\Catalog\Clients;

use App\Catalog\Clients\Concerns\ThrottlesRequests;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use RuntimeException;

/**
 * Cliente de Wikidata (fuentes-externas §4): API de entidades y SPARQL, con el límite compartido de Wikimedia.
 */
final class WikidataClient
{
    use ThrottlesRequests;

    public const API_URL = 'https://www.wikidata.org/w/api.php';

    public const SPARQL_URL = 'https://query.wikidata.org/sparql';

    /**
     * Entidad de Wikidata con etiquetas y descripciones (es, mul, en), el sitelink de eswiki y los claims;
     * null si no existe.
     *
     * @return array<string, mixed>|null
     *
     * @throws InvalidArgumentException si el QID está mal formado
     * @throws RequestException ante un 429/5xx persistente
     */
    public function getEntity(string $qid): ?array
    {
        $qid = strtoupper(trim($qid));

        if (! preg_match('/^Q[1-9]\d*$/', $qid)) {
            throw new InvalidArgumentException("QID de Wikidata no válido: '{$qid}'.");
        }

        $response = $this->request()->get(self::API_URL, [
            'action' => 'wbgetentities',
            'ids' => $qid,
            'props' => 'labels|descriptions|sitelinks|claims',
            'languages' => 'es|mul|en',
            'sitefilter' => 'eswiki',
            'format' => 'json',
        ]);

        if ($response->notFound()) {
            return null;
        }

        $response->throw();

        if ($response->json('error.code') === 'no-such-entity') {
            return null;
        }

        if ($response->json('error') !== null) {
            throw new RuntimeException('Wikidata devolvió un error: '.$response->json('error.code'));
        }

        // Si el QID es una redirección, la entidad puede venir con la clave del destino.
        $entities = (array) $response->json('entities', []);
        $entity = $entities[$qid] ?? (reset($entities) ?: null);

        if (! is_array($entity) || array_key_exists('missing', $entity)) {
            return null;
        }

        return $entity;
    }

    /**
     * Ejecuta una consulta SPARQL y devuelve las filas (results.bindings).
     *
     * @return list<array<string, array{type: string, value: string}>>
     *
     * @throws RequestException
     */
    public function sparql(string $query): array
    {
        $response = $this->request()
            ->timeout((int) config('catalog.providers.wikimedia.sparql_timeout_seconds', 60))
            ->accept('application/sparql-results+json')
            ->get(self::SPARQL_URL, ['query' => $query, 'format' => 'json']);

        $response->throw();

        return $response->json('results.bindings') ?? [];
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
