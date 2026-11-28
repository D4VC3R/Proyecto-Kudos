<?php

namespace Tests\Unit\Catalog\Providers;

use App\Catalog\Providers\IgdbGameProvider;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Sleep;
use InvalidArgumentException;
use Tests\Concerns\LoadsFixtures;
use Tests\TestCase;

class IgdbGameProviderTest extends TestCase
{
    use LoadsFixtures;

    private const TOKEN = 'id.twitch.tv/oauth2/token*';

    private const IGDB = 'api.igdb.com/v4/games';

    private const STEAM = 'store.steampowered.com/api/appdetails*';

    private const WIKIDATA = 'www.wikidata.org/w/api.php*';

    private const STEAM_URL = 'https://store.steampowered.com/app/1145360/';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'catalog.user_agent' => 'KudosTest/1.0 (+https://kudos.test)',
            'catalog.providers.igdb.client_id' => 'id-de-prueba',
            'catalog.providers.igdb.client_secret' => 'secreto-de-prueba',
        ]);
        // Tiempo congelado y sincronizado con las esperas: los límites de peticiones avanzan el reloj en lugar de girar en vacío.
        $this->freezeTime();
        Sleep::fake(syncWithCarbon: true);
        Cache::flush();
    }

    private function provider(): IgdbGameProvider
    {
        return app(IgdbGameProvider::class);
    }

    private function token(): mixed
    {
        return Http::response(['access_token' => 'token-igdb-de-prueba', 'expires_in' => 5000000, 'token_type' => 'bearer']);
    }

    /** Wikidata: la búsqueda haswbstatement responde según srsearch; wbgetentities, con $entity (o "missing"). */
    private function fakeWikidata(array $searchBySrsearch, ?array $entity = null): \Closure
    {
        return function (Request $request) use ($searchBySrsearch, $entity) {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            if (($query['list'] ?? null) === 'search') {
                return Http::response($searchBySrsearch[$query['srsearch']] ?? $this->fixture('catalog/wikidata/search-sin-resultados.json'));
            }

            return Http::response($entity ?? ['entities' => ['Q60745929' => ['id' => 'Q60745929', 'missing' => '']]]);
        };
    }

    /** Falsea IGDB (Hades por defecto), Steam (español por defecto) y Wikidata sin resultados. */
    private function fakeHades(?string $steamFixture = 'es', ?array $games = null, ?\Closure $wikidata = null): void
    {
        Http::fake([
            self::TOKEN => $this->token(),
            self::IGDB => Http::response($games ?? $this->fixture('catalog/igdb/game-113112-hades.json')),
            self::STEAM => Http::response($this->fixture($steamFixture === 'es'
                ? 'catalog/steam/appdetails-1145360-es.json'
                : ($steamFixture === 'en' ? 'catalog/steam/appdetails-1145360-en.json' : 'catalog/steam/appdetails-1145360-success-false.json'))),
            self::WIKIDATA => $wikidata ?? $this->fakeWikidata([]),
        ]);
    }

    private function igdbBodies(): array
    {
        return Http::recorded(fn (Request $request) => str_starts_with($request->url(), 'https://api.igdb.com/'))
            ->map(fn (array $pair) => $pair[0]->body())
            ->values()
            ->all();
    }

    private function countRequests(string $prefix): int
    {
        return Http::recorded(fn (Request $request) => str_starts_with($request->url(), $prefix))->count();
    }

    private function steamRequests(): int
    {
        return $this->countRequests('https://store.steampowered.com/api/appdetails');
    }

    private function wikidataRequests(): int
    {
        return $this->countRequests('https://www.wikidata.org/');
    }

    /** @return list<string> las consultas haswbstatement lanzadas a Wikidata */
    private function wikidataSearches(): array
    {
        return Http::recorded(fn (Request $request) => str_contains($request->url(), 'wikidata.org'))
            ->map(function (array $pair) {
                parse_str((string) parse_url($pair[0]->url(), PHP_URL_QUERY), $query);

                return ($query['list'] ?? null) === 'search' ? $query['srsearch'] : null;
            })
            ->filter()
            ->values()
            ->all();
    }

    private function assertWarningLogged(string $fragment): void
    {
        Log::shouldHaveReceived('warning')
            ->withArgs(fn (string $message, array $context = []) => str_contains($message, $fragment))
            ->atLeast()->once();
    }

    public function test_la_clave_del_proveedor_es_igdb(): void
    {
        $this->assertSame('igdb', $this->provider()->key());
    }

    public function test_reconoce_solo_ids_numericos(): void
    {
        Http::fake();

        $this->assertTrue($this->provider()->recognizes('113112'));

        foreach (['0', 'movie:603', 'abc', ''] as $id) {
            $this->assertFalse($this->provider()->recognizes($id), "'{$id}' no debería reconocerse");
        }

        Http::assertNothingSent();
    }

    public function test_fetch_mapea_el_juego_completo(): void
    {
        $this->fakeHades();

        $data = $this->provider()->fetch('113112');

        $this->assertSame('igdb', $data->provider);
        $this->assertSame('113112', $data->externalId);
        $this->assertSame('videojuegos', $data->categorySlug);
        $this->assertSame('Hades', $data->name);
        $this->assertNull($data->originalTitle);
        $this->assertSame('2020-09-17', $data->releasedAt);
        $this->assertSame([
            'year' => 2020,
            'genres' => ['Rol', "Hack and slash / Beat 'em up", 'Aventura', 'Indie', 'Roguelite'],
            'platforms' => ['PC', 'Nintendo Switch', 'PlayStation 5'],
            'developers' => ['Supergiant Games'],
        ], $data->attributes);
        $this->assertSame(1234.0, $data->popularity);
        $this->assertSame('https://www.igdb.com/games/hades--1', $data->sourceUrl);
        $this->assertSame([
            [
                'url' => 'https://images.igdb.com/igdb/image/upload/t_1080p/co39vc.jpg',
                'role' => 'poster',
                'license' => '© titular de derechos',
                'author' => null,
                'source_url' => 'https://www.igdb.com/games/hades--1',
            ],
            [
                'url' => 'https://images.igdb.com/igdb/image/upload/t_1080p/sc7v9a.jpg',
                'role' => 'banner',
                'license' => '© titular de derechos',
                'author' => null,
                'source_url' => 'https://www.igdb.com/games/hades--1',
            ],
        ], $data->images);
        $this->assertSame([['provider' => 'steam', 'external_id' => '1145360', 'url' => self::STEAM_URL]], $data->secondarySources);
    }

    public function test_fetch_envia_la_consulta_con_los_campos_y_sin_summary(): void
    {
        $this->fakeHades();

        $this->provider()->fetch('113112');

        $body = $this->igdbBodies()[0];
        $this->assertStringContainsString('fields '.IgdbGameProvider::FIELDS, $body);
        $this->assertStringContainsString('where id = 113112;', $body);
        $this->assertStringNotContainsString('summary', $body);
        $this->assertStringNotContainsString('storyline', $body);
    }

    public function test_fetch_nunca_guarda_el_summary_en_ingles(): void
    {
        Log::spy();
        $this->fakeHades('success-false');

        $data = $this->provider()->fetch('113112');

        $this->assertNull($data->description);
        $this->assertNull($data->fallbackDescription);
        $this->assertStringNotContainsString('Defy the god of the dead', json_encode($data));
    }

    public function test_fetch_con_steam_en_espanol_usa_su_descripcion(): void
    {
        $this->fakeHades('es');

        $data = $this->provider()->fetch('113112');

        $this->assertSame(
            'Desafía al dios de los muertos mientras te abres paso a golpes para salir del inframundo en este "roguelike" de los creadores de Bastion, Transistor y Pyre.',
            $data->description,
        );
        $this->assertSame('steam', $data->descriptionSource);
        $this->assertSame(self::STEAM_URL, $data->descriptionUrl);
        $this->assertNull($data->fallbackDescription);
        $this->assertNull($data->fallbackDescriptionSource);
        $this->assertNull($data->fallbackDescriptionUrl);
    }

    public function test_fetch_con_steam_en_ingles_lo_guarda_solo_como_respaldo(): void
    {
        $this->fakeHades('en');

        $data = $this->provider()->fetch('113112');

        $this->assertNull($data->description);
        $this->assertNull($data->descriptionSource);
        $this->assertNull($data->descriptionUrl);
        $this->assertStringStartsWith('Defy the god of the dead', (string) $data->fallbackDescription);
        $this->assertSame('steam', $data->fallbackDescriptionSource);
        $this->assertSame(self::STEAM_URL.'?l=english', $data->fallbackDescriptionUrl);
    }

    public function test_fetch_sin_appid_no_llama_a_steam(): void
    {
        $this->fakeHades(games: $this->fixture('catalog/igdb/game-119388-sin-steam.json'));

        $data = $this->provider()->fetch('119388');

        $this->assertSame(0, $this->steamRequests());
        $this->assertSame([], $data->secondarySources);
        $this->assertNull($data->description);
        $this->assertNull($data->fallbackDescription);
        $this->assertSame(['year' => 2023, 'genres' => ['Aventura'], 'platforms' => ['Nintendo Switch'], 'developers' => ['Nintendo EPD']], $data->attributes);
        $this->assertCount(1, $data->images); // sin capturas no hay banner
        $this->assertSame('poster', $data->images[0]['role']);
    }

    public function test_fetch_con_success_false_deja_ambas_descripciones_vacias(): void
    {
        $this->fakeHades('success-false');

        $data = $this->provider()->fetch('113112');

        $this->assertNull($data->description);
        $this->assertNull($data->fallbackDescription);
        $this->assertSame(1, $this->steamRequests());
    }

    public function test_fetch_con_un_fallo_de_red_en_steam_lo_registra_y_sigue(): void
    {
        Log::spy();
        Http::fake([
            self::TOKEN => $this->token(),
            self::IGDB => Http::response($this->fixture('catalog/igdb/game-113112-hades.json')),
            self::STEAM => Http::sequence()->pushFailedConnection()->pushFailedConnection()->pushFailedConnection(),
            self::WIKIDATA => $this->fakeWikidata([]),
        ]);

        $data = $this->provider()->fetch('113112');

        $this->assertNull($data->description);
        $this->assertNull($data->fallbackDescription);
        $this->assertSame('Hades', $data->name);
        $this->assertWarningLogged('no se pudo consultar Steam');
    }

    public function test_fetch_hace_una_sola_peticion_a_steam(): void
    {
        $this->fakeHades();

        $this->provider()->fetch('113112');

        $this->assertSame(1, $this->steamRequests());
    }

    public function test_fetch_obtiene_el_qid_por_el_slug_de_igdb(): void
    {
        $this->fakeHades(wikidata: $this->fakeWikidata([
            'haswbstatement:P5794=hades--1' => $this->fixture('catalog/wikidata/search-Q60745929.json'),
        ]));

        $data = $this->provider()->fetch('113112');

        $this->assertSame('Q60745929', $data->wikidataId);
        $this->assertSame(['haswbstatement:P5794=hades--1'], $this->wikidataSearches());
    }

    public function test_fetch_sin_qid_por_slug_lo_busca_por_el_appid_de_steam(): void
    {
        $this->fakeHades(wikidata: $this->fakeWikidata([
            'haswbstatement:P1733=1145360' => $this->fixture('catalog/wikidata/search-Q60745929.json'),
        ]));

        $data = $this->provider()->fetch('113112');

        $this->assertSame('Q60745929', $data->wikidataId);
        $this->assertSame(['haswbstatement:P5794=hades--1', 'haswbstatement:P1733=1145360'], $this->wikidataSearches());
    }

    public function test_fetch_cachea_la_busqueda_del_qid(): void
    {
        $this->fakeHades(wikidata: $this->fakeWikidata([
            'haswbstatement:P5794=hades--1' => $this->fixture('catalog/wikidata/search-Q60745929.json'),
        ]));

        $this->provider()->fetch('113112');
        $second = $this->provider()->fetch('113112');

        $this->assertSame('Q60745929', $second->wikidataId);
        $this->assertCount(1, $this->wikidataSearches());
    }

    public function test_fetch_si_wikidata_falla_sigue_sin_qid(): void
    {
        Log::spy();
        $this->fakeHades(wikidata: fn () => Http::response([], 500));

        $data = $this->provider()->fetch('113112');

        $this->assertNull($data->wikidataId);
        $this->assertSame('Hades', $data->name);
        $this->assertWarningLogged('no se pudo buscar el QID en Wikidata');
    }

    public function test_fetch_de_un_juego_inexistente_devuelve_null(): void
    {
        $this->fakeHades(games: $this->fixture('catalog/igdb/empty.json'));

        $this->assertNull($this->provider()->fetch('999999999'));
        $this->assertSame(0, $this->steamRequests());
    }

    public function test_fetch_de_un_404_devuelve_null(): void
    {
        Http::fake([self::TOKEN => $this->token(), self::IGDB => Http::response([], 404)]);

        $this->assertNull($this->provider()->fetch('113112'));
    }

    public function test_fetch_lanza_si_igdb_falla_con_un_error_que_no_es_404(): void
    {
        Http::fake([self::TOKEN => $this->token(), self::IGDB => Http::response(['message' => 'error'], 500)]);

        $this->assertThrows(fn () => $this->provider()->fetch('113112'), RequestException::class);
    }

    public function test_fetch_rechaza_un_external_id_que_no_es_de_igdb(): void
    {
        Http::fake();

        $this->assertThrows(fn () => $this->provider()->fetch('movie:603'), InvalidArgumentException::class);
        Http::assertNothingSent();
    }

    public function test_search_devuelve_candidatos_parciales_sin_llamar_a_steam_ni_a_wikidata(): void
    {
        $this->fakeHades();

        $results = $this->provider()->search('hades');

        $this->assertCount(1, $results);
        $this->assertSame('113112', $results[0]->externalId);
        $this->assertNull($results[0]->description);
        $this->assertNull($results[0]->wikidataId);
        $body = $this->igdbBodies()[0];
        $this->assertStringContainsString('search "hades";', $body);
        $this->assertStringContainsString('limit 10;', $body);
        $this->assertStringNotContainsString('summary', $body);
        $this->assertSame(0, $this->steamRequests());
        $this->assertSame(0, $this->wikidataRequests());
    }

    public function test_search_con_texto_vacio_no_hace_peticiones(): void
    {
        Http::fake();

        $this->assertSame([], $this->provider()->search('   '));
        $this->assertSame([], $this->provider()->search('"'));
        Http::assertNothingSent();
    }

    public function test_search_quita_las_comillas_de_la_consulta(): void
    {
        $this->fakeHades();

        $this->provider()->search('ha"des');

        $this->assertStringContainsString('search "ha des";', $this->igdbBodies()[0]);
    }

    public function test_fetch_new_pide_novedades_de_90_dias_y_populares(): void
    {
        $this->freezeTime();
        Http::fake([self::TOKEN => $this->token(), self::IGDB => Http::response([])]);

        iterator_to_array($this->provider()->fetchNew(10));

        $from = now()->subDays(90)->getTimestamp();
        $to = now()->addDays(90)->getTimestamp();
        $bodies = $this->igdbBodies();

        $this->assertCount(2, $bodies);
        $this->assertStringContainsString('game_type = (0,4,8,9,10) & version_parent = null', $bodies[0]);
        $this->assertStringContainsString("first_release_date >= {$from}", $bodies[0]);
        $this->assertStringContainsString("first_release_date <= {$to}", $bodies[0]);
        $this->assertStringContainsString('sort hypes desc', $bodies[0]);
        $this->assertStringContainsString('offset 0', $bodies[0]);
        $this->assertStringContainsString('game_type = (0,4,8,9,10) & version_parent = null', $bodies[1]);
        $this->assertStringContainsString('total_rating_count > 0; sort total_rating_count desc', $bodies[1]);
        $this->assertStringContainsString('offset 0', $bodies[1]);
    }

    /** Juegos de prueba con los ids dados. */
    private function games(array $ids): array
    {
        return array_map(fn (int $id) => ['id' => $id, 'name' => "Juego {$id}", 'slug' => "juego-{$id}", 'total_rating_count' => 1], $ids);
    }

    public function test_fetch_new_combina_los_listados_sin_duplicados_hasta_el_limite(): void
    {
        Http::fake([
            self::TOKEN => $this->token(),
            self::IGDB => fn (Request $request) => Http::response(
                str_contains($request->body(), 'sort hypes desc') ? $this->games([1, 2, 3]) : $this->games([3, 4, 5]),
            ),
        ]);

        $all = iterator_to_array($this->provider()->fetchNew(100), false);
        $this->assertSame(['1', '2', '3', '4', '5'], array_map(fn ($d) => $d->externalId, $all));
        $this->assertSame('igdb', $all[0]->provider);

        $limited = iterator_to_array($this->provider()->fetchNew(3), false);
        $this->assertSame(['1', '2', '3'], array_map(fn ($d) => $d->externalId, $limited));
    }

    public function test_fetch_new_con_limite_cero_no_hace_peticiones(): void
    {
        Http::fake();

        $this->assertSame([], iterator_to_array($this->provider()->fetchNew(0)));
        Http::assertNothingSent();
    }

    public function test_fetch_new_no_llama_a_steam_ni_a_wikidata(): void
    {
        $this->fakeHades();

        $items = iterator_to_array($this->provider()->fetchNew(5), false);

        $this->assertCount(1, $items);
        $this->assertSame(0, $this->steamRequests());
        $this->assertSame(0, $this->wikidataRequests());
        $this->assertNull($items[0]->description);
    }

    public function test_los_atributos_cumplen_el_attribute_schema(): void
    {
        $this->fakeHades();
        $keys = array_column(IgdbGameProvider::ATTRIBUTE_SCHEMA, 'key');

        $data = $this->provider()->fetch('113112');

        $this->assertSame([], array_values(array_diff(array_keys($data->attributes), $keys)));
    }
}
