<?php

namespace Tests\Unit\Catalog\Enrichment;

use App\Catalog\Data\CatalogItemData;
use App\Catalog\Enrichment\DescriptionResolver;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Sleep;
use Tests\Concerns\LoadsFixtures;
use Tests\TestCase;

class DescriptionResolverTest extends TestCase
{
    use LoadsFixtures;

    private const WIKIDATA = 'www.wikidata.org/w/api.php*';

    private const WIKIPEDIA_REST = 'es.wikipedia.org/api/rest_v1/page/summary/*';

    private const WIKIPEDIA_ACTION = 'es.wikipedia.org/w/api.php*';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'catalog.user_agent' => 'KudosTest/1.0 (+https://kudos.test)',
            'catalog.providers.tmdb.token' => 'token-de-prueba',
        ]);
        Sleep::fake(); // anula las esperas de los reintentos y del RateLimiter
        Cache::flush();
    }

    private function resolver(): DescriptionResolver
    {
        return app(DescriptionResolver::class);
    }

    /** @param array<string, mixed> $overrides */
    private function data(array $overrides = []): CatalogItemData
    {
        $defaults = [
            'provider' => 'tmdb',
            'externalId' => 'movie:550',
            'categorySlug' => 'peliculas',
            'name' => 'El club de la lucha',
            'originalTitle' => 'Fight Club',
            'description' => null,
            'descriptionSource' => null,
            'descriptionUrl' => null,
            'releasedAt' => '1999-10-15',
            'attributes' => ['year' => 1999, 'genres' => ['Drama'], 'directors' => ['David Fincher'], 'runtime' => 139],
            'popularity' => 61.4,
            'wikidataId' => 'Q190050',
            'sourceUrl' => 'https://www.themoviedb.org/movie/550',
            'images' => [],
        ];

        return new CatalogItemData(...array_merge($defaults, $overrides));
    }

    private function fakeWithEswiki(): void
    {
        Http::fake([
            self::WIKIDATA => Http::response($this->fixture('catalog/wikidata/Q190050-eswiki.json')),
            self::WIKIPEDIA_REST => Http::response($this->fixture('catalog/wikipedia/summary-el-club-de-la-lucha.json')),
        ]);
    }

    private function assertWarningLogged(string $fragment): void
    {
        Log::shouldHaveReceived('warning')
            ->withArgs(fn (string $message, array $context = []) => str_contains($message, $fragment))
            ->once();
    }

    public function test_conserva_la_descripcion_del_proveedor_sin_hacer_peticiones(): void
    {
        Http::fake();
        $data = $this->data([
            'description' => 'Sinopsis de TMDB.',
            'descriptionSource' => 'tmdb',
            'descriptionUrl' => 'https://www.themoviedb.org/movie/550',
        ]);

        $resolved = $this->resolver()->resolve($data);

        $this->assertSame('Sinopsis de TMDB.', $resolved->description);
        $this->assertSame('tmdb', $resolved->descriptionSource);
        $this->assertSame('https://www.themoviedb.org/movie/550', $resolved->descriptionUrl);
        Http::assertNothingSent();
    }

    public function test_sin_descripcion_ni_wikidata_devuelve_null_sin_hacer_peticiones(): void
    {
        Http::fake();

        $resolved = $this->resolver()->resolve($this->data(['wikidataId' => null]));

        $this->assertNull($resolved->description);
        $this->assertNull($resolved->descriptionSource);
        $this->assertNull($resolved->descriptionUrl);
        Http::assertNothingSent();
    }

    public function test_usa_el_resumen_de_wikipedia_es_del_sitelink_eswiki(): void
    {
        $this->fakeWithEswiki();

        $resolved = $this->resolver()->resolve($this->data());

        $extract = $this->fixture('catalog/wikipedia/summary-el-club-de-la-lucha.json')['extract'];
        $this->assertSame($extract, $resolved->description);
        $this->assertSame('wikipedia_es', $resolved->descriptionSource);
        $this->assertSame('https://es.wikipedia.org/wiki/El_club_de_la_lucha', $resolved->descriptionUrl);
        Http::assertSent(fn (Request $request) => str_starts_with($request->url(), 'https://es.wikipedia.org/api/rest_v1/page/summary/El_club_de_la_lucha'));
    }

    public function test_sin_eswiki_usa_la_descripcion_es_de_wikidata_con_los_atributos(): void
    {
        Http::fake([self::WIKIDATA => Http::response($this->fixture('catalog/wikidata/Q190050-sin-eswiki.json'))]);

        $resolved = $this->resolver()->resolve($this->data());

        $this->assertSame(
            'Película de 1999 dirigida por David Fincher. Géneros: Drama. Dirección: David Fincher. Duración: 139 min.',
            $resolved->description,
        );
        $this->assertSame('wikidata', $resolved->descriptionSource);
        $this->assertSame('https://www.wikidata.org/wiki/Q190050', $resolved->descriptionUrl);
        Http::assertNotSent(fn (Request $request) => str_contains($request->url(), 'es.wikipedia.org'));
    }

    public function test_sin_eswiki_ni_descripcion_es_la_descripcion_queda_null(): void
    {
        Http::fake([self::WIKIDATA => Http::response($this->fixture('catalog/wikidata/Q190050-sin-eswiki-ni-descripcion-es.json'))]);

        $resolved = $this->resolver()->resolve($this->data());

        $this->assertNull($resolved->description);
        $this->assertNull($resolved->descriptionSource);
        $this->assertNull($resolved->descriptionUrl);
    }

    public function test_si_wikipedia_no_tiene_entrada_usa_la_descripcion_de_wikidata(): void
    {
        Http::fake([
            self::WIKIDATA => Http::response($this->fixture('catalog/wikidata/Q190050-eswiki.json')),
            self::WIKIPEDIA_REST => Http::response([], 404),
            self::WIKIPEDIA_ACTION => Http::response(['batchcomplete' => true, 'query' => ['pages' => [
                ['ns' => 0, 'title' => 'El club de la lucha', 'missing' => true],
            ]]]),
        ]);

        $resolved = $this->resolver()->resolve($this->data());

        $this->assertSame('wikidata', $resolved->descriptionSource);
        $this->assertStringStartsWith('Película de 1999 dirigida por David Fincher.', $resolved->description);
    }

    public function test_si_wikipedia_falla_usa_la_descripcion_de_wikidata(): void
    {
        Log::spy();
        Http::fake([
            self::WIKIDATA => Http::response($this->fixture('catalog/wikidata/Q190050-eswiki.json')),
            self::WIKIPEDIA_REST => Http::response([], 503),
            self::WIKIPEDIA_ACTION => Http::response([], 503),
        ]);

        $resolved = $this->resolver()->resolve($this->data());

        $this->assertSame('wikidata', $resolved->descriptionSource);
        $this->assertWarningLogged('no se pudo consultar Wikipedia');
    }

    public function test_si_wikidata_falla_la_descripcion_queda_null(): void
    {
        Log::spy();
        Http::fake([self::WIKIDATA => Http::response([], 503)]);

        $resolved = $this->resolver()->resolve($this->data());

        $this->assertNull($resolved->description);
        $this->assertNull($resolved->descriptionSource);
        $this->assertWarningLogged('no se pudo consultar Wikidata');
    }

    public function test_cachea_las_respuestas_de_wikidata_y_wikipedia(): void
    {
        $this->fakeWithEswiki();

        $this->resolver()->resolve($this->data());
        $this->resolver()->resolve($this->data());

        Http::assertSentCount(2);
    }

    public function test_cachea_tambien_una_entidad_que_no_existe(): void
    {
        Http::fake([self::WIKIDATA => Http::response(['entities' => ['Q190050' => ['id' => 'Q190050', 'missing' => '']]])]);

        $this->assertNull($this->resolver()->resolve($this->data())->description);
        $this->assertNull($this->resolver()->resolve($this->data())->description);

        Http::assertSentCount(1);
    }

    public function test_la_cache_caduca_a_los_siete_dias(): void
    {
        $this->fakeWithEswiki();

        $this->resolver()->resolve($this->data());
        $this->travel(8)->days();
        $this->resolver()->resolve($this->data());

        Http::assertSentCount(4);
    }
}
