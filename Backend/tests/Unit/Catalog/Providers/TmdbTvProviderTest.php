<?php

namespace Tests\Unit\Catalog\Providers;

use App\Catalog\Providers\TmdbTvProvider;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use InvalidArgumentException;
use Tests\Concerns\LoadsFixtures;
use Tests\TestCase;

class TmdbTvProviderTest extends TestCase
{
    use LoadsFixtures;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'catalog.user_agent' => 'KudosTest/1.0 (+https://kudos.test)',
            'catalog.providers.tmdb.token' => 'token-de-prueba',
        ]);
        Sleep::fake(); // anula las esperas de los reintentos y del RateLimiter
    }

    private function provider(): TmdbTvProvider
    {
        return app(TmdbTvProvider::class);
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
                'name' => "Serie {$id}",
                'original_name' => "Original {$id}",
                'genre_ids' => [18],
                'first_air_date' => '2026-01-01',
                'popularity' => 1.0,
            ], $ids),
        ];
    }

    public function test_fetch_mapea_la_serie_completa(): void
    {
        Http::fake(['api.themoviedb.org/3/tv/1399*' => Http::response($this->fixture('catalog/tmdb/tv-1399-es.json'))]);

        $data = $this->provider()->fetch('tv:1399');

        $this->assertNotNull($data);
        $this->assertSame('tmdb', $data->provider);
        $this->assertSame('tv:1399', $data->externalId);
        $this->assertSame('series', $data->categorySlug);
        $this->assertSame('Juego de tronos', $data->name);
        $this->assertSame('Game of Thrones', $data->originalTitle);
        $this->assertSame('2011-04-17', $data->releasedAt);
        $this->assertSame('Q23572', $data->wikidataId);
        $this->assertSame('https://www.themoviedb.org/tv/1399', $data->sourceUrl);
        $this->assertSame('tmdb', $data->descriptionSource);
        $this->assertSame([
            'year' => 2011,
            'genres' => ['Sci-Fi & Fantasy', 'Drama'],
            'seasons' => 8,
            'episodes' => 73,
            'creators' => ['David Benioff', 'D. B. Weiss'],
            'networks' => ['HBO'],
            'status' => 'Finalizada',
        ], $data->attributes);
        $this->assertSame([
            [
                'url' => 'https://image.tmdb.org/t/p/w780/1XS1oqL89opfnbLl8WnZY1O1uJx.jpg',
                'role' => 'poster',
                'license' => '© titular de derechos',
                'author' => null,
                'source_url' => 'https://www.themoviedb.org/tv/1399',
            ],
            [
                'url' => 'https://image.tmdb.org/t/p/w1280/2OMB0ynKlyIenMJWI2Dy9IWT4c.jpg',
                'role' => 'banner',
                'license' => '© titular de derechos',
                'author' => null,
                'source_url' => 'https://www.themoviedb.org/tv/1399',
            ],
        ], $data->images);
    }

    public function test_fetch_pide_el_detalle_con_external_ids(): void
    {
        Http::fake(['api.themoviedb.org/3/tv/1399*' => Http::response($this->fixture('catalog/tmdb/tv-1399-es.json'))]);

        $this->provider()->fetch('tv:1399');

        Http::assertSent(function (Request $request) {
            $query = $this->queryOf($request);

            return str_starts_with($request->url(), 'https://api.themoviedb.org/3/tv/1399?')
                && $query['append_to_response'] === 'external_ids'
                && $query['language'] === 'es-ES';
        });
    }

    public function test_fetch_de_un_404_devuelve_null(): void
    {
        Http::fake(['api.themoviedb.org/3/tv/999999999*' => Http::response($this->fixture('catalog/tmdb/not-found.json'), 404)]);

        $this->assertNull($this->provider()->fetch('tv:999999999'));
        Http::assertSentCount(1);
    }

    public function test_fetch_rechaza_un_external_id_de_pelicula(): void
    {
        Http::fake();

        $this->assertThrows(fn () => $this->provider()->fetch('movie:603'), InvalidArgumentException::class);
        Http::assertNothingSent();
    }

    public function test_un_estado_desconocido_no_se_guarda_en_ingles(): void
    {
        $payload = $this->fixture('catalog/tmdb/tv-1399-es.json');
        $payload['status'] = 'Something New';
        Http::fake(['api.themoviedb.org/3/tv/1399*' => Http::response($payload)]);

        $data = $this->provider()->fetch('tv:1399');

        $this->assertArrayNotHasKey('status', $data->attributes);
    }

    public function test_search_devuelve_series_con_los_generos_en_espanol(): void
    {
        Http::fake([
            'api.themoviedb.org/3/search/tv*' => Http::response([
                'page' => 1,
                'total_pages' => 1,
                'results' => [[
                    'id' => 1399,
                    'name' => 'Juego de tronos',
                    'original_name' => 'Game of Thrones',
                    'genre_ids' => [10765, 18],
                    'first_air_date' => '2011-04-17',
                ]],
            ]),
            'api.themoviedb.org/3/genre/tv/list*' => Http::response($this->fixture('catalog/tmdb/genre-tv-list-es.json')),
        ]);

        $results = $this->provider()->search('juego de tronos');

        $this->assertCount(1, $results);
        $this->assertSame('tv:1399', $results[0]->externalId);
        $this->assertSame(['Sci-Fi & Fantasy', 'Drama'], $results[0]->attributes['genres']);
        $this->assertSame(2011, $results[0]->attributes['year']);
        Http::assertSent(fn (Request $request) => str_starts_with($request->url(), 'https://api.themoviedb.org/3/search/tv?'));
    }

    public function test_fetch_new_combina_on_the_air_y_popular(): void
    {
        Http::fake([
            'api.themoviedb.org/3/tv/on_the_air*' => Http::response($this->listPage([1, 2])),
            'api.themoviedb.org/3/tv/popular*' => Http::response($this->listPage([2, 3])),
            'api.themoviedb.org/3/genre/tv/list*' => Http::response($this->fixture('catalog/tmdb/genre-tv-list-es.json')),
        ]);

        $items = iterator_to_array($this->provider()->fetchNew(10), false);

        $this->assertSame(['tv:1', 'tv:2', 'tv:3'], array_map(fn ($item) => $item->externalId, $items));
    }

    public function test_los_atributos_cumplen_el_attribute_schema(): void
    {
        foreach (TmdbTvProvider::ATTRIBUTE_SCHEMA as $entry) {
            $this->assertSame(['key', 'label', 'type', 'filterable', 'show_in_card'], array_keys($entry));
            $this->assertContains($entry['type'], ['string', 'integer', 'minutes', 'date', 'tags', 'coordinates', 'url']);
        }

        Http::fake(['api.themoviedb.org/3/tv/1399*' => Http::response($this->fixture('catalog/tmdb/tv-1399-es.json'))]);

        $data = $this->provider()->fetch('tv:1399');

        $this->assertSame(
            array_column(TmdbTvProvider::ATTRIBUTE_SCHEMA, 'key'),
            array_keys($data->attributes)
        );
    }

    public function test_reconoce_solo_sus_external_ids(): void
    {
        Http::fake();
        $provider = $this->provider();

        $this->assertTrue($provider->recognizes('tv:1399'));
        $this->assertFalse($provider->recognizes('movie:603'));
        $this->assertFalse($provider->recognizes('tv:0'));
        $this->assertFalse($provider->recognizes('1399'));

        Http::assertNothingSent();
    }
}
