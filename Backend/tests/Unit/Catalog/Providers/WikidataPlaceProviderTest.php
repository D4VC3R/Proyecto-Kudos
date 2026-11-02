<?php

namespace Tests\Unit\Catalog\Providers;

use App\Catalog\Providers\WikidataPlaceProvider;
use Carbon\CarbonInterval;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Sleep;
use InvalidArgumentException;
use Tests\Concerns\LoadsFixtures;
use Tests\TestCase;

class WikidataPlaceProviderTest extends TestCase
{
    use LoadsFixtures;

    private const WIKIDATA = 'www.wikidata.org/w/api.php*';

    private const SPARQL = 'query.wikidata.org/sparql*';

    private const COMMONS = 'commons.wikimedia.org/w/api.php*';

    protected function setUp(): void
    {
        parent::setUp();

        config(['catalog.user_agent' => 'KudosTest/1.0 (+https://kudos.test)']);
        // Tiempo congelado y sincronizado con las esperas: el límite de Wikimedia avanza el reloj en lugar de girar en vacío.
        $this->freezeTime();
        Sleep::fake(syncWithCarbon: true);
        Cache::flush();
    }

    private function provider(): WikidataPlaceProvider
    {
        return app(WikidataPlaceProvider::class);
    }

    /**
     * Wikidata: con props=labels devuelve las etiquetas de $labels; si no, la entidad según ids ($entities).
     *
     * @param  array<string, string>  $entities  QID => fixture
     */
    private function fakeWikidata(array $entities, string $labels = 'labels-Q29'): \Closure
    {
        return function (Request $request) use ($entities, $labels) {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            if (($query['props'] ?? null) === 'labels') {
                return Http::response($this->fixture("catalog/wikidata/{$labels}.json"));
            }

            $fixture = $entities[$query['ids'] ?? ''] ?? null;

            return Http::response($fixture !== null
                ? $this->fixture("catalog/wikidata/{$fixture}.json")
                : ['entities' => [($query['ids'] ?? 'Q1') => ['id' => $query['ids'] ?? 'Q1', 'missing' => '']]]);
        };
    }

    private function sparqlFake(?string $failCountry = null): \Closure
    {
        return function (Request $request) use ($failCountry) {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $params);
            $query = (string) ($params['query'] ?? '');

            if (str_contains($query, 'wd:Q162620')) {
                return Http::response($this->fixture('catalog/wikidata/sparql-capitales-provincia.json'));
            }

            if (preg_match('/wdt:P17 wd:(Q\d+)/', $query, $m) === 1) {
                if ($m[1] === $failCountry) {
                    return Http::response([], 500);
                }

                return Http::response($this->fixture("catalog/wikidata/sparql-ciudades-{$m[1]}.json"));
            }

            return Http::response($this->fixture('catalog/wikidata/sparql-paises.json'));
        };
    }

    private function fakeMadrid(string $commons = 'imageinfo-con-licencia', string $entity = 'Q2807-madrid'): void
    {
        Http::fake([
            self::WIKIDATA => $this->fakeWikidata(['Q2807' => $entity, 'Q29' => 'Q29-espana']),
            self::COMMONS => Http::response($this->fixture("catalog/commons/{$commons}.json")),
        ]);
    }

    private function sparqlRequests(): int
    {
        return Http::recorded(fn (Request $request) => str_contains($request->url(), 'query.wikidata.org'))->count();
    }

    private function commonsRequests(): int
    {
        return Http::recorded(fn (Request $request) => str_contains($request->url(), 'commons.wikimedia.org'))->count();
    }

    public function test_key_y_recognizes(): void
    {
        $this->assertSame('wikidata', $this->provider()->key());
        $this->assertTrue($this->provider()->recognizes('Q2807'));
        $this->assertFalse($this->provider()->recognizes('movie:603'));
        $this->assertFalse($this->provider()->recognizes('2807'));
        $this->assertFalse($this->provider()->recognizes('q2807'));
    }

    public function test_fetch_mapea_una_ciudad(): void
    {
        $this->fakeMadrid();

        $data = $this->provider()->fetch('Q2807');

        $this->assertSame('wikidata', $data->provider);
        $this->assertSame('Q2807', $data->externalId);
        $this->assertSame('Q2807', $data->wikidataId);
        $this->assertSame('destinos', $data->categorySlug);
        $this->assertSame('Madrid', $data->name);
        $this->assertSame([
            'kind' => 'ciudad',
            'country' => 'España',
            'population' => 3332035,
            'coordinates' => ['lat' => 40.416944, 'lng' => -3.703333],
        ], $data->attributes);
        $this->assertSame(3332035.0, $data->popularity);
        $this->assertSame('https://www.wikidata.org/wiki/Q2807', $data->sourceUrl);
        $this->assertNull($data->description);
        $this->assertSame([[
            'url' => 'https://commons.wikimedia.org/wiki/Special:FilePath/Madrid_-_Palacio_Real.jpg?width=1280',
            'role' => 'poster',
            'license' => 'CC BY-SA 4.0',
            'author' => 'Ejemplo & otros',
            'source_url' => 'https://commons.wikimedia.org/wiki/File:Madrid_-_Palacio_Real.jpg',
        ]], $data->images);
    }

    public function test_fetch_elige_la_poblacion_preferida_y_si_no_la_mas_reciente(): void
    {
        $this->fakeMadrid();

        $this->assertSame(3332035, $this->provider()->fetch('Q2807')->attributes['population']);
        $this->assertSame(48085361, $this->provider()->fetch('Q29')->attributes['population']);
    }

    public function test_fetch_mapea_un_pais_sin_repetir_su_pais(): void
    {
        $this->fakeMadrid();

        $data = $this->provider()->fetch('Q29');

        $this->assertSame('España', $data->name);
        $this->assertSame('país', $data->attributes['kind']);
        $this->assertArrayNotHasKey('country', $data->attributes);
        $this->assertCount(0, Http::recorded(function (Request $request) {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            return ($query['props'] ?? null) === 'labels';
        }));
    }

    public function test_cachea_la_etiqueta_del_pais(): void
    {
        $this->fakeMadrid();

        $this->provider()->fetch('Q2807');
        $this->provider()->fetch('Q2807');

        $labelRequests = Http::recorded(function (Request $request) {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            return ($query['props'] ?? null) === 'labels';
        });
        $this->assertCount(1, $labelRequests);
    }

    public function test_la_etiqueta_del_pais_usa_mul_y_en_si_no_hay_es(): void
    {
        Http::fake([
            self::WIKIDATA => $this->fakeWikidata(['Q2807' => 'Q2807-madrid'], 'labels-Q29-sin-es'),
            self::COMMONS => Http::response($this->fixture('catalog/commons/imageinfo-con-licencia.json')),
        ]);

        $this->assertSame('Spain', $this->provider()->fetch('Q2807')->attributes['country']);
    }

    public function test_fetch_devuelve_null_si_la_entidad_no_existe(): void
    {
        Http::fake([self::WIKIDATA => Http::response(['entities' => ['Q2807' => ['id' => 'Q2807', 'missing' => '']]])]);

        $this->assertNull($this->provider()->fetch('Q2807'));
    }

    public function test_fetch_rechaza_un_external_id_que_no_es_un_qid(): void
    {
        Http::fake();

        $this->assertThrows(fn () => $this->provider()->fetch('movie:603'), InvalidArgumentException::class);

        Http::assertNothingSent();
    }

    public function test_una_foto_sin_autor_no_se_guarda(): void
    {
        Log::spy();
        $this->fakeMadrid('imageinfo-sin-autor');

        $data = $this->provider()->fetch('Q2807');

        $this->assertSame([], $data->images);
        $this->assertSame('Madrid', $data->name);
        $this->assertSame('ciudad', $data->attributes['kind']);
        Log::shouldHaveReceived('warning')
            ->withArgs(fn (string $message, array $context = []) => str_contains($message, 'no tiene licencia o autor'))
            ->once();
    }

    public function test_una_foto_sin_licencia_no_se_guarda(): void
    {
        Log::spy();
        $this->fakeMadrid('imageinfo-sin-licencia');

        $data = $this->provider()->fetch('Q2807');

        $this->assertSame([], $data->images);
        $this->assertSame('Madrid', $data->name);
        Log::shouldHaveReceived('warning')
            ->withArgs(fn (string $message, array $context = []) => str_contains($message, 'no tiene licencia o autor'))
            ->once();
    }

    public function test_una_foto_que_no_existe_en_commons_no_se_guarda(): void
    {
        Log::spy();
        $this->fakeMadrid('imageinfo-no-existe');

        $data = $this->provider()->fetch('Q2807');

        $this->assertSame([], $data->images);
        $this->assertSame('Madrid', $data->name);
        Log::shouldHaveReceived('info')
            ->withArgs(fn (string $message, array $context = []) => str_contains($message, 'la foto de Commons no existe'))
            ->once();
    }

    public function test_sin_p18_no_pide_nada_a_commons(): void
    {
        $this->fakeMadrid(entity: 'Q2807-madrid-sin-foto');

        $data = $this->provider()->fetch('Q2807');

        $this->assertSame([], $data->images);
        $this->assertSame(0, $this->commonsRequests());
    }

    public function test_si_commons_falla_lanza_la_excepcion(): void
    {
        Http::fake([
            self::WIKIDATA => $this->fakeWikidata(['Q2807' => 'Q2807-madrid']),
            self::COMMONS => Http::response([], 500),
        ]);

        $this->assertThrows(fn () => $this->provider()->fetch('Q2807'), RequestException::class);
    }

    public function test_search_devuelve_vacio_sin_peticiones(): void
    {
        Http::fake();

        $this->assertSame([], $this->provider()->search('madrid'));

        Http::assertNothingSent();
    }

    public function test_fetch_new_empieza_por_espana_y_sigue_pais_a_pais(): void
    {
        Http::fake([self::SPARQL => $this->sparqlFake()]);

        $items = iterator_to_array($this->provider()->fetchNew(50), false);

        $this->assertSame(['Q29', 'Q2807', 'Q5836', 'Q1492', 'Q142', 'Q90'], array_map(fn ($d) => $d->externalId, $items));
        $this->assertSame(['España', 'Madrid', 'Toledo', 'Barcelona', 'Francia', 'París'], array_map(fn ($d) => $d->name, $items));
        $this->assertSame([48085361.0, 3332035.0, 85000.0, 1620343.0, 68000000.0, 2133111.0], array_map(fn ($d) => $d->popularity, $items));
        $this->assertSame('wikidata', $items[0]->provider);
        $this->assertSame('destinos', $items[0]->categorySlug);
        $this->assertSame('Q2807', $items[1]->wikidataId);
    }

    public function test_fetch_new_respeta_el_limite_y_no_hace_consultas_de_mas(): void
    {
        Http::fake([self::SPARQL => $this->sparqlFake()]);

        $items = iterator_to_array($this->provider()->fetchNew(2), false);

        $this->assertSame(['Q29', 'Q2807'], array_map(fn ($d) => $d->externalId, $items));
        $this->assertSame(2, $this->sparqlRequests()); // países y capitales; no las ciudades de España
    }

    public function test_fetch_new_sigue_si_falla_la_consulta_de_un_pais(): void
    {
        Log::spy();
        Http::fake([self::SPARQL => $this->sparqlFake(failCountry: 'Q142')]);

        $items = iterator_to_array($this->provider()->fetchNew(50), false);

        $this->assertSame(['Q29', 'Q2807', 'Q5836', 'Q1492', 'Q142'], array_map(fn ($d) => $d->externalId, $items));
        Log::shouldHaveReceived('warning')
            ->withArgs(fn (string $message, array $context = []) => str_contains($message, 'falló una consulta SPARQL') && ($context['country'] ?? null) === 'Q142')
            ->once();
    }

    public function test_fetch_new_repite_al_final_la_consulta_de_un_pais_que_fallo(): void
    {
        config(['catalog.http.retry_times' => 1]); // un solo intento por petición: el fallo llega al proveedor
        $calls = 0;
        $sparql = $this->sparqlFake();
        Http::fake([self::SPARQL => function (Request $request) use (&$calls, $sparql) {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $params);

            if (str_contains((string) ($params['query'] ?? ''), 'wdt:P17 wd:Q142') && $calls++ === 0) {
                return Http::response([], 504);
            }

            return $sparql($request);
        }]);

        $items = iterator_to_array($this->provider()->fetchNew(50), false);

        $this->assertSame(['Q29', 'Q2807', 'Q5836', 'Q1492', 'Q142', 'Q90'], array_map(fn ($d) => $d->externalId, $items));
        $this->assertSame(2, $calls); // falló una vez y se repitió al final
        Sleep::assertSlept(fn (CarbonInterval $duration) => (int) $duration->totalSeconds === 60);
    }

    public function test_si_la_consulta_vuelve_a_fallar_queda_un_error_con_los_paises(): void
    {
        Log::spy();
        Http::fake([self::SPARQL => $this->sparqlFake(failCountry: 'Q142')]);

        iterator_to_array($this->provider()->fetchNew(50), false);

        Log::shouldHaveReceived('error')
            ->withArgs(fn (string $message, array $context = []) => str_contains($message, 'no se pudieron listar') && ($context['countries'] ?? null) === ['Q142'])
            ->once();
    }

    public function test_fetch_new_pausa_tras_cada_consulta_de_ciudades(): void
    {
        Http::fake([self::SPARQL => $this->sparqlFake()]);

        iterator_to_array($this->provider()->fetchNew(50), false);

        // Capitales y ciudades de España, y ciudades de Francia; la de países no lleva pausa.
        Sleep::assertSleptTimes(3);
    }

    public function test_recorre_los_paises_sin_foto_y_el_p17_de_paises_bajos(): void
    {
        $sparql = $this->sparqlFake();
        Http::fake([self::SPARQL => function (Request $request) use ($sparql) {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $params);

            if (str_contains((string) ($params['query'] ?? ''), 'wd:Q3624078')) {
                return Http::response($this->fixture('catalog/wikidata/sparql-paises-casos-especiales.json'));
            }

            return $sparql($request);
        }]);

        $items = iterator_to_array($this->provider()->fetchNew(50), false);

        // Argentina (sin P18) no entra, pero Buenos Aires sí; Ámsterdam sale de P17 = Q55, no de Q29999.
        $this->assertSame(
            ['Q29', 'Q2807', 'Q5836', 'Q1492', 'Q142', 'Q90', 'Q1486', 'Q29999', 'Q727'],
            array_map(fn ($d) => $d->externalId, $items),
        );
    }

    public function test_las_consultas_sparql_aplican_el_alcance(): void
    {
        Http::fake([self::SPARQL => $this->sparqlFake()]);

        iterator_to_array($this->provider()->fetchNew(50), false);

        $queries = Http::recorded(fn (Request $request) => str_contains($request->url(), 'query.wikidata.org'))
            ->map(function ($pair) {
                parse_str((string) parse_url($pair[0]->url(), PHP_URL_QUERY), $params);

                return (string) $params['query'];
            });

        $countries = $queries->first(fn (string $q) => str_contains($q, 'wd:Q3624078'));
        $capitals = $queries->first(fn (string $q) => str_contains($q, 'wd:Q162620'));
        $cities = $queries->first(fn (string $q) => str_contains($q, 'wd:Q515'));

        $this->assertNotNull($countries);
        $this->assertStringContainsString('wdt:P576', $countries);
        $this->assertStringContainsString('wd:Q3024240', $countries);
        $this->assertStringNotContainsString('wdt:P17 wd:', $countries);
        $this->assertStringContainsString('AS ?hasImage', $countries);
        $this->assertStringContainsString('AS ?hasEswiki', $countries);
        $this->assertNotNull($capitals);
        $this->assertStringContainsString('wdt:P36', $capitals);
        $this->assertStringContainsString('wdt:P17 wd:Q29', $capitals); // la clase incluye provincias históricas (La Habana, Santa Fe…)
        $this->assertNotNull($cities);
        $this->assertStringContainsString('> 300000', $cities);
        // Decimal: con un literal entero, rangeSafe no usa el índice (P1082 es xsd:decimal).
        $this->assertStringContainsString('> 300000.0)', $cities);
        $this->assertStringContainsString('wd:Q515', $cities);
        $this->assertStringContainsString('wd:Q15284', $cities);
        // La población (con rangeSafe) va antes que el país: si no, WDQS agota los 60 s en EE. UU. o Alemania.
        $this->assertStringContainsString('hint:rangeSafe true', $cities);
        $this->assertLessThan(strpos($cities, 'wdt:P17 wd:'), strpos($cities, 'wdt:P1082'));

        foreach ($queries as $query) {
            $this->assertStringContainsString('schema:isPartOf <https://es.wikipedia.org/>', $query);
            $this->assertStringContainsString('wdt:P18', $query);
        }
    }

    public function test_el_esquema_de_atributos(): void
    {
        $schema = collect(WikidataPlaceProvider::ATTRIBUTE_SCHEMA)->keyBy('key');

        $this->assertSame(['kind', 'country', 'population', 'coordinates'], $schema->keys()->all());
        $this->assertFalse($schema['coordinates']['show_in_card']);
        $this->assertSame('coordinates', $schema['coordinates']['type']);
    }
}
