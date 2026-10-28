<?php

namespace Tests\Feature\Catalog;

use App\Jobs\Catalog\DownloadItemImagesJob;
use App\Jobs\Catalog\ImportCatalogItemJob;
use App\Models\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Sleep;
use Tests\Concerns\CreatesItems;
use Tests\Concerns\FakesRemoteImages;
use Tests\Concerns\LoadsFixtures;
use Tests\TestCase;

class ImportCatalogItemJobTest extends TestCase
{
    use CreatesItems, FakesRemoteImages, LoadsFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'catalog.user_agent' => 'KudosTest/1.0 (+https://kudos.test)',
            'catalog.providers.tmdb.token' => 'token-de-prueba',
        ]);
        Sleep::fake();
        Cache::flush();

        $this->createCategory('peliculas', 'Películas');
        $this->createCategory('series', 'Series');
    }

    private function wikimediaRequests(): int
    {
        return Http::recorded(fn (Request $request) => str_contains($request->url(), 'wikidata.org') || str_contains($request->url(), 'wikipedia.org'))->count();
    }

    private function imageRequests(): int
    {
        return Http::recorded(fn (Request $request) => str_starts_with($request->url(), 'https://image.tmdb.org/'))->count();
    }

    public function test_va_a_la_cola_catalog(): void
    {
        $this->assertSame('catalog', (new ImportCatalogItemJob('peliculas', 'movie:603'))->queue);
        $this->assertSame('catalog', (new DownloadItemImagesJob('id', []))->queue);
    }

    public function test_importa_una_pelicula_de_tmdb_y_encola_sus_imagenes(): void
    {
        Bus::fake([DownloadItemImagesJob::class]);
        Http::fake(['api.themoviedb.org/3/movie/603*' => Http::response($this->fixture('catalog/tmdb/movie-603-es.json'))]);

        ImportCatalogItemJob::dispatchSync('peliculas', 'movie:603');

        $item = Item::query()->firstOrFail();
        $this->assertSame('Matrix', $item->name);
        $this->assertSame('tmdb', $item->description_source);
        $this->assertSame(0, $this->wikimediaRequests());
        Bus::assertDispatched(DownloadItemImagesJob::class, fn ($job) => $job->itemId === $item->id && count($job->images) === 2);
    }

    public function test_una_pelicula_sin_sinopsis_y_con_eswiki_recibe_la_descripcion_de_wikipedia(): void
    {
        Bus::fake([DownloadItemImagesJob::class]);
        $movie = $this->fixture('catalog/tmdb/movie-550-overview-vacio-es.json');
        $movie['external_ids']['wikidata_id'] = 'Q190050';
        Http::fake([
            'api.themoviedb.org/3/movie/550*' => Http::response($movie),
            'www.wikidata.org/w/api.php*' => Http::response($this->fixture('catalog/wikidata/Q190050-eswiki.json')),
            'es.wikipedia.org/api/rest_v1/page/summary/*' => Http::response($this->fixture('catalog/wikipedia/summary-el-club-de-la-lucha.json')),
        ]);

        ImportCatalogItemJob::dispatchSync('peliculas', 'movie:550');

        $item = Item::query()->firstOrFail();
        $this->assertSame('wikipedia_es', $item->description_source);
        $this->assertSame('https://es.wikipedia.org/wiki/El_club_de_la_lucha', $item->description_url);
        $this->assertStringStartsWith('El club de la lucha es una película', $item->description);
        $this->assertSame('Q190050', $item->wikidata_id);
    }

    public function test_sin_eswiki_ni_descripcion_la_descripcion_queda_null_y_la_calidad_por_debajo_del_umbral(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        $this->fakeHostResolver();
        $movie = $this->fixture('catalog/tmdb/movie-550-overview-vacio-es.json');
        $movie['external_ids']['wikidata_id'] = 'Q190050';
        Http::fake([
            'api.themoviedb.org/3/movie/550*' => Http::response($movie),
            'www.wikidata.org/w/api.php*' => Http::response($this->fixture('catalog/wikidata/Q190050-sin-eswiki-ni-descripcion-es.json')),
            'image.tmdb.org/*' => Http::response($this->fakePng(), 200, ['Content-Type' => 'image/png']),
        ]);

        ImportCatalogItemJob::dispatchSync('peliculas', 'movie:550');

        $item = Item::query()->firstOrFail();
        $this->assertNull($item->description);
        $this->assertNull($item->description_source);
        $this->assertCount(1, $item->images); // el póster sí se descarga; la 550 no tiene fondo
        $this->assertLessThan((int) config('catalog.min_quality_for_discovery'), $item->quality_score);
    }

    public function test_resuelve_el_proveedor_por_la_categoria_aunque_compartan_la_clave_tmdb(): void
    {
        Bus::fake([DownloadItemImagesJob::class]);
        Http::fake([
            'api.themoviedb.org/3/movie/603*' => Http::response($this->fixture('catalog/tmdb/movie-603-es.json')),
            'api.themoviedb.org/3/tv/1399*' => Http::response($this->fixture('catalog/tmdb/tv-1399-es.json')),
        ]);

        ImportCatalogItemJob::dispatchSync('peliculas', 'movie:603');
        ImportCatalogItemJob::dispatchSync('series', 'tv:1399');

        $this->assertSame(2, Item::query()->count());
        $movie = Item::query()->whereHas('category', fn ($q) => $q->where('slug', 'peliculas'))->firstOrFail();
        $show = Item::query()->whereHas('category', fn ($q) => $q->where('slug', 'series'))->firstOrFail();
        $this->assertSame('movie:603', $movie->sources()->where('provider', 'tmdb')->value('external_id'));
        $this->assertSame('tv:1399', $show->sources()->where('provider', 'tmdb')->value('external_id'));
    }

    public function test_si_fetch_devuelve_null_desactiva_el_item_existente(): void
    {
        Bus::fake([DownloadItemImagesJob::class]);
        Http::fake(['api.themoviedb.org/3/movie/603*' => Http::sequence()
            ->push($this->fixture('catalog/tmdb/movie-603-es.json'))
            ->push($this->fixture('catalog/tmdb/not-found.json'), 404),
        ]);
        ImportCatalogItemJob::dispatchSync('peliculas', 'movie:603');
        Log::spy();

        ImportCatalogItemJob::dispatchSync('peliculas', 'movie:603');

        $item = Item::withTrashed()->firstOrFail();
        $this->assertFalse($item->trashed());
        $this->assertSame(Item::STATUS_INACTIVE, $item->status);
        Bus::assertDispatchedTimes(DownloadItemImagesJob::class, 1);
        Log::shouldHaveReceived('warning')
            ->withArgs(fn (string $message, array $context = []) => str_contains($message, '404'))
            ->once();
    }

    public function test_un_item_desactivado_sigue_inactivo_al_refrescar(): void
    {
        Bus::fake([DownloadItemImagesJob::class]);
        Http::fake(['api.themoviedb.org/3/movie/603*' => Http::response($this->fixture('catalog/tmdb/movie-603-es.json'))]);
        ImportCatalogItemJob::dispatchSync('peliculas', 'movie:603');
        $item = Item::query()->firstOrFail();
        $item->update(['status' => Item::STATUS_INACTIVE]);
        $firstSync = $item->fresh()->last_synced_at;

        $this->travel(1)->days();
        ImportCatalogItemJob::dispatchSync('peliculas', 'movie:603');

        $item = $item->fresh();
        $this->assertSame(Item::STATUS_INACTIVE, $item->status);
        $this->assertTrue($item->last_synced_at->greaterThan($firstSync));
    }

    public function test_un_404_sin_item_existente_no_crea_nada(): void
    {
        Bus::fake([DownloadItemImagesJob::class]);
        Http::fake(['api.themoviedb.org/3/movie/603*' => Http::response($this->fixture('catalog/tmdb/not-found.json'), 404)]);

        ImportCatalogItemJob::dispatchSync('peliculas', 'movie:603');

        $this->assertSame(0, Item::withTrashed()->count());
        Bus::assertNotDispatched(DownloadItemImagesJob::class);
    }

    public function test_importar_dos_veces_no_duplica_ni_vuelve_a_descargar_las_imagenes(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        $this->fakeHostResolver();
        $png = $this->fakePng();
        Http::fake([
            'api.themoviedb.org/3/movie/603*' => Http::response($this->fixture('catalog/tmdb/movie-603-es.json')),
            // Una respuesta nueva por petición: el cuerpo en streaming de una respuesta reutilizada ya estaría consumido.
            'image.tmdb.org/*' => fn () => Http::response($png, 200, ['Content-Type' => 'image/png']),
        ]);

        ImportCatalogItemJob::dispatchSync('peliculas', 'movie:603');
        ImportCatalogItemJob::dispatchSync('peliculas', 'movie:603');

        $item = Item::query()->firstOrFail();
        $this->assertSame(1, Item::query()->count());
        $this->assertSame(2, $item->sources()->count());
        $this->assertCount(2, $item->images);
        $this->assertSame(4, $this->imageRequests()); // HEAD + GET de cada imagen, solo la primera vez
    }
}
