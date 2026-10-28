<?php

namespace Tests\Unit\Catalog\Clients;

use App\Catalog\Clients\WikidataClient;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use InvalidArgumentException;
use Tests\TestCase;

class WikidataClientTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'catalog.user_agent' => 'KudosTest/1.0 (+https://kudos.test)',
            'catalog.providers.tmdb.token' => 'token-de-prueba',
        ]);
        Sleep::fake(); // anula las esperas de los reintentos y del RateLimiter
    }

    /** @return array<string, mixed> */
    private function matrixEntity(): array
    {
        return ['entities' => ['Q83495' => [
            'id' => 'Q83495',
            'sitelinks' => ['eswiki' => ['site' => 'eswiki', 'title' => 'Matrix']],
        ]]];
    }

    public function test_get_entity_envia_el_user_agent_y_devuelve_la_entidad(): void
    {
        Http::fake(['www.wikidata.org/w/api.php*' => Http::response($this->matrixEntity())]);

        $entity = app(WikidataClient::class)->getEntity('q83495');

        $this->assertSame('Matrix', $entity['sitelinks']['eswiki']['title']);
        Http::assertSent(function (Request $request) {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            return $request->hasHeader('User-Agent', 'KudosTest/1.0 (+https://kudos.test)')
                && $query['action'] === 'wbgetentities'
                && $query['ids'] === 'Q83495'
                && $query['languages'] === 'es|mul|en';
        });
    }

    public function test_get_entity_devuelve_null_si_la_entidad_no_existe(): void
    {
        Http::fake(['www.wikidata.org/w/api.php*' => Http::response([
            'entities' => ['Q999999999' => ['id' => 'Q999999999', 'missing' => '']],
        ])]);

        $this->assertNull(app(WikidataClient::class)->getEntity('Q999999999'));
    }

    public function test_get_entity_rechaza_un_qid_mal_formado(): void
    {
        Http::fake();

        $this->assertThrows(
            fn () => app(WikidataClient::class)->getEntity('tt0133093'),
            InvalidArgumentException::class,
        );

        Http::assertNothingSent();
    }

    public function test_get_entity_reintenta_ante_un_429(): void
    {
        Http::fake(['www.wikidata.org/w/api.php*' => Http::sequence()
            ->push([], 429)
            ->push($this->matrixEntity()),
        ]);

        $entity = app(WikidataClient::class)->getEntity('Q83495');

        $this->assertSame('Q83495', $entity['id']);
        Http::assertSentCount(2);
    }

    public function test_get_entity_no_reintenta_ante_un_404(): void
    {
        Http::fake(['www.wikidata.org/w/api.php*' => Http::response([], 404)]);

        $this->assertNull(app(WikidataClient::class)->getEntity('Q83495'));
        Http::assertSentCount(1);
    }

    public function test_sparql_devuelve_las_filas_y_envia_el_user_agent(): void
    {
        $sparql = 'SELECT ?item WHERE { ?item wdt:P4947 "603" }';
        Http::fake(['query.wikidata.org/sparql*' => Http::response([
            'head' => ['vars' => ['item']],
            'results' => ['bindings' => [
                ['item' => ['type' => 'uri', 'value' => 'http://www.wikidata.org/entity/Q83495']],
            ]],
        ])]);

        $rows = app(WikidataClient::class)->sparql($sparql);

        $this->assertCount(1, $rows);
        $this->assertSame('http://www.wikidata.org/entity/Q83495', $rows[0]['item']['value']);
        Http::assertSent(function (Request $request) use ($sparql) {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            return $request->hasHeader('User-Agent', 'KudosTest/1.0 (+https://kudos.test)')
                && $request->hasHeader('Accept', 'application/sparql-results+json')
                && $query['query'] === $sparql;
        });
    }
}
