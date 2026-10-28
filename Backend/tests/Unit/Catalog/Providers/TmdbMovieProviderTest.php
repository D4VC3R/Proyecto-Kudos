<?php

namespace Tests\Unit\Catalog\Providers;

use App\Catalog\Providers\TmdbMovieProvider;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use InvalidArgumentException;
use Tests\Concerns\LoadsFixtures;
use Tests\TestCase;

class TmdbMovieProviderTest extends TestCase
{
    use LoadsFixtures;

    private const DESCRIPTION = 'Thomas Anderson lleva una doble vida: de día es programador y de noche, un hacker llamado Neo. Un desconocido le revela la verdad sobre el mundo en el que vive.';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'catalog.user_agent' => 'KudosTest/1.0 (+https://kudos.test)',
            'catalog.providers.tmdb.token' => 'token-de-prueba',
        ]);
        Sleep::fake(); // anula las esperas de los reintentos y del RateLimiter
    }

    private function provider(): TmdbMovieProvider
    {
        return app(TmdbMovieProvider::class);
    }

    /** @return array<string, mixed> */
    private function queryOf(Request $request): array
    {
        parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

        return $query;
    }

    /**
     * @param  list<int>  $ids
     * @return array<string, mixed>
     */
    private function listPage(array $ids, int $totalPages = 1): array
    {
        return [
            'page' => 1,
            'total_pages' => $totalPages,
            'total_results' => count($ids),
            'results' => array_map(fn (int $id) => [
                'id' => $id,
                'title' => "Título {$id}",
                'original_title' => "Original {$id}",
                'genre_ids' => [18],
                'release_date' => '2026-01-01',
                'popularity' => 1.0,
            ], $ids),
        ];
    }

    /** Falsea un listado paginado: $pages es página => ids. */
    private function fakeList(array $pages, int $totalPages = 1): \Closure
    {
        return fn (Request $request) => Http::response(
            isset($pages[$this->queryOf($request)['page']])
                ? $this->listPage($pages[$this->queryOf($request)['page']], $totalPages)
                : $this->listPage([], $totalPages)
        );
    }

    public function test_la_clave_del_proveedor_es_tmdb(): void
    {
        $this->assertSame('tmdb', $this->provider()->key());
    }

    public function test_fetch_mapea_la_pelicula_completa(): void
    {
        Http::fake(['api.themoviedb.org/3/movie/603*' => Http::response($this->fixture('catalog/tmdb/movie-603-es.json'))]);

        $data = $this->provider()->fetch('movie:603');

        $this->assertNotNull($data);
        $this->assertSame('tmdb', $data->provider);
        $this->assertSame('movie:603', $data->externalId);
        $this->assertSame('peliculas', $data->categorySlug);
        $this->assertSame('Matrix', $data->name);
        $this->assertSame('The Matrix', $data->originalTitle);
        $this->assertSame(self::DESCRIPTION, $data->description);
        $this->assertSame('tmdb', $data->descriptionSource);
        $this->assertSame('https://www.themoviedb.org/movie/603', $data->descriptionUrl);
        $this->assertSame('https://www.themoviedb.org/movie/603', $data->sourceUrl);
        $this->assertSame('1999-03-31', $data->releasedAt);
        $this->assertSame(85.123, $data->popularity);
        $this->assertSame('Q83495', $data->wikidataId);
        $this->assertSame([
            'year' => 1999,
            'genres' => ['Acción', 'Ciencia ficción'],
            'runtime' => 136,
            'directors' => ['Lana Wachowski', 'Lilly Wachowski'],
            'countries' => ['Estados Unidos'],
            'original_language' => 'Inglés',
        ], $data->attributes);
        $this->assertSame([
            [
                'url' => 'https://image.tmdb.org/t/p/w780/qK76PKQLd6zlMn0u83Ej9YQOqPL.jpg',
                'role' => 'poster',
                'license' => '© titular de derechos',
                'author' => null,
                'source_url' => 'https://www.themoviedb.org/movie/603',
            ],
            [
                'url' => 'https://image.tmdb.org/t/p/w1280/fNG7i7RqMErkcqhohV2a6cV1Ehy.jpg',
                'role' => 'banner',
                'license' => '© titular de derechos',
                'author' => null,
                'source_url' => 'https://www.themoviedb.org/movie/603',
            ],
        ], $data->images);
    }

    public function test_fetch_pide_el_detalle_en_espanol_con_external_ids_y_credits(): void
    {
        Http::fake(['api.themoviedb.org/3/movie/603*' => Http::response($this->fixture('catalog/tmdb/movie-603-es.json'))]);

        $this->provider()->fetch('movie:603');

        Http::assertSent(function (Request $request) {
            $query = $this->queryOf($request);

            return str_starts_with($request->url(), 'https://api.themoviedb.org/3/movie/603?')
                && $query['language'] === 'es-ES'
                && $query['append_to_response'] === 'external_ids,credits';
        });
        Http::assertSentCount(1); // el detalle no pide la lista de géneros
    }

    public function test_fetch_con_overview_vacio_deja_la_descripcion_a_null(): void
    {
        Http::fake(['api.themoviedb.org/3/movie/550*' => Http::response($this->fixture('catalog/tmdb/movie-550-overview-vacio-es.json'))]);

        $data = $this->provider()->fetch('movie:550');

        $this->assertNotNull($data);
        $this->assertNull($data->description);
        $this->assertNull($data->descriptionSource);
        $this->assertNull($data->descriptionUrl);
        $this->assertNull($data->wikidataId);
        $this->assertCount(1, $data->images);
        $this->assertSame('poster', $data->images[0]['role']);
        $this->assertSame([
            'year' => 1999,
            'genres' => ['Drama'],
            'runtime' => 139,
            'directors' => ['David Fincher'],
            'countries' => ['Estados Unidos', 'Alemania'],
            'original_language' => 'Inglés',
        ], $data->attributes);

        // Nunca se pide otro idioma para rellenar la sinopsis.
        Http::assertNotSent(fn (Request $request) => ($this->queryOf($request)['language'] ?? null) !== 'es-ES');
        Http::assertSentCount(1);
    }

    public function test_fetch_de_un_404_devuelve_null(): void
    {
        Http::fake(['api.themoviedb.org/3/movie/999999999*' => Http::response($this->fixture('catalog/tmdb/not-found.json'), 404)]);

        $this->assertNull($this->provider()->fetch('movie:999999999'));
        Http::assertSentCount(1); // un 404 no se reintenta
    }

    public function test_fetch_lanza_si_tmdb_falla_con_un_error_que_no_es_404(): void
    {
        Http::fake(['api.themoviedb.org/3/movie/603*' => Http::response([], 503)]);

        $this->assertThrows(fn () => $this->provider()->fetch('movie:603'), RequestException::class);
        Http::assertSentCount(3);
    }

    public function test_fetch_rechaza_un_external_id_que_no_es_de_pelicula(): void
    {
        Http::fake();

        foreach (['tv:1399', '603', 'movie:abc', 'movie:0'] as $externalId) {
            $this->assertThrows(fn () => $this->provider()->fetch($externalId), InvalidArgumentException::class);
        }

        Http::assertNothingSent();
    }

    public function test_fetch_limita_la_direccion_a_tres_nombres_sin_repetir(): void
    {
        $payload = $this->fixture('catalog/tmdb/movie-603-es.json');
        $payload['credits']['crew'] = array_map(
            fn (string $name) => ['name' => $name, 'job' => 'Director'],
            ['A', 'B', 'A', 'C', 'D']
        );
        Http::fake(['api.themoviedb.org/3/movie/603*' => Http::response($payload)]);

        $data = $this->provider()->fetch('movie:603');

        $this->assertSame(['A', 'B', 'C'], $data->attributes['directors']);
    }

    public function test_fetch_omite_los_atributos_vacios(): void
    {
        $payload = $this->fixture('catalog/tmdb/movie-603-es.json');
        $payload['release_date'] = '';
        $payload['runtime'] = 0;
        $payload['production_countries'] = [];
        $payload['original_language'] = 'xx';
        $payload['credits']['crew'] = [];
        Http::fake(['api.themoviedb.org/3/movie/603*' => Http::response($payload)]);

        $data = $this->provider()->fetch('movie:603');

        $this->assertNull($data->releasedAt);
        $this->assertSame(['genres' => ['Acción', 'Ciencia ficción']], $data->attributes);
    }

    public function test_search_devuelve_candidatos_con_los_generos_en_espanol(): void
    {
        Http::fake([
            'api.themoviedb.org/3/search/movie*' => Http::response($this->fixture('catalog/tmdb/search-movie-matrix-es.json')),
            'api.themoviedb.org/3/genre/movie/list*' => Http::response($this->fixture('catalog/tmdb/genre-movie-list-es.json')),
        ]);

        $results = $this->provider()->search('  matrix ');

        $this->assertCount(2, $results);

        $this->assertSame('movie:603', $results[0]->externalId);
        $this->assertSame('Matrix', $results[0]->name);
        $this->assertSame(['year' => 1999, 'genres' => ['Acción', 'Ciencia ficción']], $results[0]->attributes);
        $this->assertSame('tmdb', $results[0]->descriptionSource);
        $this->assertNull($results[0]->wikidataId);
        $this->assertCount(2, $results[0]->images);

        $this->assertSame('movie:604', $results[1]->externalId);
        $this->assertNull($results[1]->description);
        $this->assertSame(['Acción', 'Ciencia ficción', 'Aventura'], $results[1]->attributes['genres']);
        $this->assertCount(1, $results[1]->images);

        Http::assertSent(function (Request $request) {
            $query = $this->queryOf($request);

            return str_starts_with($request->url(), 'https://api.themoviedb.org/3/search/movie?')
                && $query['query'] === 'matrix'
                && $query['include_adult'] === 'false'
                && $query['language'] === 'es-ES';
        });
    }

    public function test_search_limita_a_diez_candidatos(): void
    {
        Http::fake([
            'api.themoviedb.org/3/search/movie*' => Http::response($this->listPage(range(1, 12))),
            'api.themoviedb.org/3/genre/movie/list*' => Http::response($this->fixture('catalog/tmdb/genre-movie-list-es.json')),
        ]);

        $this->assertCount(10, $this->provider()->search('titulo'));
    }

    public function test_search_con_texto_vacio_no_hace_peticiones(): void
    {
        Http::fake();

        $this->assertSame([], $this->provider()->search('   '));
        Http::assertNothingSent();
    }

    public function test_la_lista_de_generos_se_cachea_siete_dias(): void
    {
        Http::fake([
            'api.themoviedb.org/3/search/movie*' => Http::response($this->fixture('catalog/tmdb/search-movie-matrix-es.json')),
            'api.themoviedb.org/3/genre/movie/list*' => Http::response($this->fixture('catalog/tmdb/genre-movie-list-es.json')),
        ]);
        $genreRequests = fn () => Http::recorded(fn (Request $request) => str_contains($request->url(), '/genre/movie/list'))->count();

        $this->provider()->search('matrix');
        $this->provider()->search('matrix');

        $this->assertSame(1, $genreRequests());

        $this->travel(8)->days();
        $this->provider()->search('matrix');

        $this->assertSame(2, $genreRequests());
    }

    public function test_fetch_new_combina_los_listados_sin_duplicados(): void
    {
        Http::fake([
            'api.themoviedb.org/3/movie/now_playing*' => $this->fakeList([1 => [1, 2, 3]]),
            'api.themoviedb.org/3/movie/upcoming*' => $this->fakeList([1 => [3, 4]]),
            'api.themoviedb.org/3/movie/popular*' => $this->fakeList([1 => [1, 5, 6]]),
            'api.themoviedb.org/3/genre/movie/list*' => Http::response($this->fixture('catalog/tmdb/genre-movie-list-es.json')),
        ]);

        $items = iterator_to_array($this->provider()->fetchNew(10), false);

        $this->assertSame(
            ['movie:1', 'movie:2', 'movie:3', 'movie:4', 'movie:5', 'movie:6'],
            array_map(fn ($item) => $item->externalId, $items)
        );

        foreach (['now_playing', 'upcoming', 'popular'] as $list) {
            Http::assertSent(function (Request $request) use ($list) {
                $query = $this->queryOf($request);

                return str_starts_with($request->url(), "https://api.themoviedb.org/3/movie/{$list}?")
                    && $query['region'] === 'ES'
                    && $query['page'] === '1';
            });
        }
    }

    public function test_fetch_new_pagina_hasta_el_limite_y_para(): void
    {
        Http::fake([
            'api.themoviedb.org/3/movie/now_playing*' => $this->fakeList([1 => [1, 2]]),
            'api.themoviedb.org/3/movie/upcoming*' => Http::response($this->listPage([], 0)),
            'api.themoviedb.org/3/movie/popular*' => $this->fakeList([1 => [10, 11], 2 => [12, 13], 3 => [14, 15]], 5),
            'api.themoviedb.org/3/genre/movie/list*' => Http::response($this->fixture('catalog/tmdb/genre-movie-list-es.json')),
        ]);

        $items = iterator_to_array($this->provider()->fetchNew(5), false);

        $this->assertSame(
            ['movie:1', 'movie:2', 'movie:10', 'movie:11', 'movie:12'],
            array_map(fn ($item) => $item->externalId, $items)
        );
        Http::assertSent(fn (Request $request) => str_contains($request->url(), '/movie/popular?') && $this->queryOf($request)['page'] === '2');
        Http::assertNotSent(fn (Request $request) => str_contains($request->url(), '/movie/popular?') && $this->queryOf($request)['page'] === '3');
    }

    public function test_fetch_new_se_detiene_al_agotar_los_listados(): void
    {
        Http::fake([
            'api.themoviedb.org/3/movie/now_playing*' => $this->fakeList([1 => [1, 2]]),
            'api.themoviedb.org/3/movie/upcoming*' => $this->fakeList([1 => [3, 4]]),
            'api.themoviedb.org/3/movie/popular*' => $this->fakeList([1 => [5, 6]]),
            'api.themoviedb.org/3/genre/movie/list*' => Http::response($this->fixture('catalog/tmdb/genre-movie-list-es.json')),
        ]);

        $this->assertCount(6, iterator_to_array($this->provider()->fetchNew(100), false));

        foreach (['now_playing', 'upcoming', 'popular'] as $list) {
            $count = Http::recorded(fn (Request $request) => str_contains($request->url(), "/movie/{$list}?"))->count();
            $this->assertSame(1, $count, "El listado {$list} se pide una sola vez.");
        }
    }

    public function test_fetch_new_con_limite_cero_no_hace_peticiones(): void
    {
        Http::fake();

        $this->assertSame([], iterator_to_array($this->provider()->fetchNew(0)));
        Http::assertNothingSent();
    }

    public function test_los_atributos_cumplen_el_attribute_schema(): void
    {
        foreach (TmdbMovieProvider::ATTRIBUTE_SCHEMA as $entry) {
            $this->assertSame(['key', 'label', 'type', 'filterable', 'show_in_card'], array_keys($entry));
            $this->assertContains($entry['type'], ['string', 'integer', 'minutes', 'date', 'tags', 'coordinates', 'url']);
        }

        Http::fake(['api.themoviedb.org/3/movie/603*' => Http::response($this->fixture('catalog/tmdb/movie-603-es.json'))]);

        $data = $this->provider()->fetch('movie:603');

        $this->assertSame(
            array_column(TmdbMovieProvider::ATTRIBUTE_SCHEMA, 'key'),
            array_keys($data->attributes)
        );
    }

    public function test_reconoce_solo_sus_external_ids(): void
    {
        Http::fake();
        $provider = $this->provider();

        $this->assertTrue($provider->recognizes('movie:603'));
        $this->assertFalse($provider->recognizes('tv:1399'));
        $this->assertFalse($provider->recognizes('movie:0'));
        $this->assertFalse($provider->recognizes('603'));

        Http::assertNothingSent();
    }
}
