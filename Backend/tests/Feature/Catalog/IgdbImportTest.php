<?php

namespace Tests\Feature\Catalog;

use App\Jobs\Catalog\DownloadItemImagesJob;
use App\Jobs\Catalog\ImportCatalogItemJob;
use App\Models\Category;
use App\Models\Item;
use App\Models\ItemSource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Sleep;
use Tests\Concerns\CreatesItems;
use Tests\Concerns\LoadsFixtures;
use Tests\TestCase;

/**
 * F4-01: importación de un videojuego de punta a punta (IGDB + descripción de Steam + Wikipedia/Wikidata).
 */
class IgdbImportTest extends TestCase
{
    use CreatesItems, LoadsFixtures, RefreshDatabase;

    private const SPANISH_TEXT = 'Desafía al dios de los muertos mientras te abres paso a golpes para salir del inframundo en este "roguelike" de los creadores de Bastion, Transistor y Pyre.';

    private const ENGLISH_TEXT = 'Defy the god of the dead as you hack and slash out of the Underworld in this rogue-like dungeon crawler from the creators of Bastion, Transistor, and Pyre.';

    private const STEAM_URL = 'https://store.steampowered.com/app/1145360/';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'catalog.user_agent' => 'KudosTest/1.0 (+https://kudos.test)',
            'catalog.providers.igdb.client_id' => 'id-de-prueba',
            'catalog.providers.igdb.client_secret' => 'secreto-de-prueba',
        ]);
        // Tiempo congelado y sincronizado con las esperas: el límite de Steam avanza el reloj en lugar de girar en vacío.
        $this->freezeTime();
        Sleep::fake(syncWithCarbon: true);
        Cache::flush();
        Bus::fake([DownloadItemImagesJob::class]);

        $this->createCategory('videojuegos', 'Videojuegos');
    }

    /** Wikidata: la búsqueda haswbstatement responde según srsearch; wbgetentities, con $entity (o "missing"). */
    private function fakeWikidata(array $searchBySrsearch, ?array $entity): \Closure
    {
        return function (Request $request) use ($searchBySrsearch, $entity) {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            if (($query['list'] ?? null) === 'search') {
                return Http::response($searchBySrsearch[$query['srsearch']] ?? $this->fixture('catalog/wikidata/search-sin-resultados.json'));
            }

            return Http::response($entity ?? ['entities' => ['Q60745929' => ['id' => 'Q60745929', 'missing' => '']]]);
        };
    }

    /**
     * @param  mixed  $steam  fixture de Steam (es, en, success-false) o una respuesta ya hecha
     * @param  mixed  $wikidata  closure de fakeWikidata()
     */
    private function fakeGame(array $games, mixed $steam, mixed $wikidata, bool $withWikipedia = false): void
    {
        $steamResponse = is_string($steam)
            ? Http::response($this->fixture("catalog/steam/appdetails-1145360-{$steam}.json"))
            : $steam;

        $fakes = [
            'id.twitch.tv/oauth2/token*' => Http::response(['access_token' => 'token-igdb-de-prueba', 'expires_in' => 5000000, 'token_type' => 'bearer']),
            'api.igdb.com/v4/games' => Http::response($games),
            'store.steampowered.com/api/appdetails*' => $steamResponse,
            'www.wikidata.org/w/api.php*' => $wikidata,
        ];

        if ($withWikipedia) {
            $fakes['es.wikipedia.org/api/rest_v1/page/summary/*'] = Http::response($this->fixture('catalog/wikipedia/summary-hades-videojuego.json'));
        }

        Http::fake($fakes);
    }

    private function hades(): array
    {
        return $this->fixture('catalog/igdb/game-113112-hades.json');
    }

    private function hadesQid(): array
    {
        return ['haswbstatement:P5794=hades--1' => $this->fixture('catalog/wikidata/search-Q60745929.json')];
    }

    private function steamRequests(): int
    {
        return Http::recorded(fn (Request $request) => str_starts_with($request->url(), 'https://store.steampowered.com/api/appdetails'))->count();
    }

    private function wikipediaRequests(): int
    {
        return Http::recorded(fn (Request $request) => str_contains($request->url(), 'wikipedia.org'))->count();
    }

    private function import(string $externalId = '113112'): void
    {
        ImportCatalogItemJob::dispatchSync('videojuegos', $externalId);
    }

    public function test_importa_un_juego_con_la_descripcion_de_steam_en_espanol(): void
    {
        $this->fakeGame($this->hades(), 'es', $this->fakeWikidata($this->hadesQid(), null));

        $this->import();

        $item = Item::query()->firstOrFail();
        $this->assertSame('Hades', $item->name);
        $this->assertSame(self::SPANISH_TEXT, $item->description);
        $this->assertSame('steam', $item->description_source);
        $this->assertSame(self::STEAM_URL, $item->description_url);
        $this->assertSame('Q60745929', $item->wikidata_id);
        $this->assertSame(['year' => 2020, 'genres' => ['Rol', "Hack and slash / Beat 'em up", 'Aventura', 'Indie', 'Roguelite'],
            'platforms' => ['PC', 'Nintendo Switch', 'PlayStation 5'], 'developers' => ['Supergiant Games']], $item->attributes);

        $igdb = $item->sources()->where('provider', 'igdb')->firstOrFail();
        $this->assertSame('113112', $igdb->external_id);
        $this->assertTrue($igdb->is_primary);
        $this->assertSame('https://www.igdb.com/games/hades--1', $igdb->url);
        $steam = $item->sources()->where('provider', 'steam')->firstOrFail();
        $this->assertSame('1145360', $steam->external_id);
        $this->assertFalse($steam->is_primary);
        $this->assertSame(self::STEAM_URL, $steam->url);
        $wikidata = $item->sources()->where('provider', 'wikidata')->firstOrFail();
        $this->assertSame('Q60745929', $wikidata->external_id);
        $this->assertFalse($wikidata->is_primary);

        $this->assertSame(0, $this->wikipediaRequests());
        $this->assertSame(1, $this->steamRequests());
        Bus::assertDispatched(DownloadItemImagesJob::class, fn ($job) => $job->itemId === $item->id && count($job->images) === 2);
    }

    public function test_steam_en_ingles_y_wikipedia_es_gana_wikipedia(): void
    {
        $this->fakeGame(
            $this->hades(),
            'en',
            $this->fakeWikidata($this->hadesQid(), $this->fixture('catalog/wikidata/Q60745929-eswiki.json')),
            withWikipedia: true,
        );

        $this->import();

        $item = Item::query()->firstOrFail();
        $this->assertSame('wikipedia_es', $item->description_source);
        $this->assertSame('https://es.wikipedia.org/wiki/Hades_(videojuego)', $item->description_url);
        $this->assertStringNotContainsString('Defy the god', (string) $item->description);
        $this->assertSame(1, $this->steamRequests());
    }

    public function test_steam_en_ingles_sin_wikipedia_ni_wikidata_usa_el_ingles(): void
    {
        $this->fakeGame(
            $this->hades(),
            'en',
            $this->fakeWikidata($this->hadesQid(), $this->fixture('catalog/wikidata/Q60745929-sin-eswiki-ni-descripcion-es.json')),
        );

        $this->import();

        $item = Item::query()->firstOrFail();
        $this->assertSame('steam', $item->description_source);
        $this->assertSame(self::STEAM_URL.'?l=english', $item->description_url);
        $this->assertSame(self::ENGLISH_TEXT, $item->description);
        $this->assertSame('Q60745929', $item->wikidata_id);
        $this->assertSame(1, $this->steamRequests());
    }

    public function test_steam_en_ingles_sin_qid_usa_el_ingles(): void
    {
        $this->fakeGame($this->hades(), 'en', $this->fakeWikidata([], null));

        $this->import();

        $item = Item::query()->firstOrFail();
        $this->assertSame('steam', $item->description_source);
        $this->assertSame(self::STEAM_URL.'?l=english', $item->description_url);
        $this->assertSame(self::ENGLISH_TEXT, $item->description);
        $this->assertNull($item->wikidata_id);
        $this->assertSame(1, $this->steamRequests());
    }

    public function test_juego_sin_wikipedia_es_usa_la_descripcion_de_wikidata(): void
    {
        $this->fakeGame(
            $this->fixture('catalog/igdb/game-119388-sin-steam.json'),
            'es',
            $this->fakeWikidata(
                ['haswbstatement:P5794=the-legend-of-zelda-tears-of-the-kingdom' => $this->fixture('catalog/wikidata/search-Q60745929.json')],
                $this->fixture('catalog/wikidata/Q60745929-sin-eswiki.json'),
            ),
        );

        $this->import('119388');

        $item = Item::query()->firstOrFail();
        $this->assertSame('wikidata', $item->description_source);
        $this->assertSame('Videojuego de 2020. Géneros: Aventura. Desarrollo: Nintendo EPD. Plataformas: Nintendo Switch.', $item->description);
        $this->assertSame(0, $this->steamRequests());
        $this->assertSame(0, $item->sources()->where('provider', 'steam')->count());
    }

    public function test_juego_sin_appid_ni_descripcion_queda_sin_descripcion(): void
    {
        $this->fakeGame($this->fixture('catalog/igdb/game-119388-sin-steam.json'), 'es', $this->fakeWikidata([], null));

        $this->import('119388');

        $item = Item::query()->firstOrFail();
        $this->assertNull($item->description);
        $this->assertNull($item->description_source);
        $this->assertLessThan((int) config('catalog.min_quality_for_discovery'), $item->quality_score);
        $this->assertSame(0, $this->steamRequests());
    }

    public function test_success_false_de_steam_sigue_la_cadena(): void
    {
        $this->fakeGame(
            $this->hades(),
            'success-false',
            $this->fakeWikidata($this->hadesQid(), $this->fixture('catalog/wikidata/Q60745929-eswiki.json')),
            withWikipedia: true,
        );

        $this->import();

        $this->assertSame('wikipedia_es', Item::query()->firstOrFail()->description_source);
        $this->assertSame(1, $this->steamRequests());
    }

    public function test_un_fallo_de_red_de_steam_sigue_la_cadena(): void
    {
        Log::spy();
        $this->fakeGame(
            $this->hades(),
            Http::sequence()->pushFailedConnection()->pushFailedConnection()->pushFailedConnection(),
            $this->fakeWikidata($this->hadesQid(), $this->fixture('catalog/wikidata/Q60745929-eswiki.json')),
            withWikipedia: true,
        );

        $this->import();

        $this->assertSame('wikipedia_es', Item::query()->firstOrFail()->description_source);
        Log::shouldHaveReceived('warning')
            ->withArgs(fn (string $message, array $context = []) => str_contains($message, 'no se pudo consultar Steam'))
            ->once();
    }

    public function test_un_404_de_igdb_desactiva_el_item(): void
    {
        $item = $this->createItem(Category::query()->where('slug', 'videojuegos')->firstOrFail(), ['name' => 'Hades']);
        $item->sources()->create(['provider' => 'igdb', 'external_id' => '113112', 'url' => 'https://www.igdb.com/games/hades--1', 'is_primary' => true]);
        $this->fakeGame([], 'es', $this->fakeWikidata([], null));

        $this->import();

        $item = Item::withTrashed()->findOrFail($item->id);
        $this->assertSame(Item::STATUS_INACTIVE, $item->status);
        $this->assertFalse($item->trashed());
        $this->assertSame(0, $this->steamRequests());
    }

    public function test_reimportar_no_duplica_la_fuente_de_steam(): void
    {
        $this->fakeGame($this->hades(), 'es', $this->fakeWikidata($this->hadesQid(), null));

        $this->import();
        $this->import();

        $this->assertSame(1, Item::query()->count());
        $this->assertSame(1, ItemSource::query()->where('provider', 'steam')->count());
        $this->assertSame(3, ItemSource::query()->count());
    }

    public function test_reimportar_encola_las_imagenes_con_el_tamano_nuevo(): void
    {
        $this->fakeGame($this->hades(), 'es', $this->fakeWikidata($this->hadesQid(), null));

        $this->import();
        $this->import();

        Bus::assertDispatched(DownloadItemImagesJob::class, 2);
        Bus::assertDispatched(DownloadItemImagesJob::class, function ($job) {
            $poster = $job->images[0];

            return str_contains($poster['url'], '/t_1080p/')
                && $poster['role'] === 'poster'
                && $poster['license'] === '© titular de derechos';
        });
    }
}
