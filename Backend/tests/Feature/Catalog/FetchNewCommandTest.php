<?php

namespace Tests\Feature\Catalog;

use App\Catalog\Providers\TmdbMovieProvider;
use App\Jobs\Catalog\DownloadItemImagesJob;
use App\Jobs\Catalog\FetchNewCatalogItemsJob;
use App\Models\Category;
use App\Models\Item;
use App\Models\ItemSource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Sleep;
use Tests\Concerns\CreatesItems;
use Tests\Concerns\LoadsFixtures;
use Tests\TestCase;

class FetchNewCommandTest extends TestCase
{
    use CreatesItems, LoadsFixtures, RefreshDatabase;

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

    /** Página de un listado de TMDB sin genre_ids (así no se pide la lista de géneros). */
    private function listPage(array $ids, string $titleField = 'title'): array
    {
        return [
            'page' => 1,
            'total_pages' => 1,
            'total_results' => count($ids),
            'results' => array_map(fn (int $id) => ['id' => $id, $titleField => "Título {$id}", 'genre_ids' => [], 'popularity' => 1.0], $ids),
        ];
    }

    private function fakeMovies(): void
    {
        Http::fake([
            'api.themoviedb.org/3/movie/now_playing*' => Http::response($this->listPage([603])),
            'api.themoviedb.org/3/movie/upcoming*' => Http::response($this->listPage([550])),
            'api.themoviedb.org/3/movie/popular*' => Http::response($this->listPage([603])),
            'api.themoviedb.org/3/movie/603*' => Http::response($this->fixture('catalog/tmdb/movie-603-es.json')),
            'api.themoviedb.org/3/movie/550*' => Http::response($this->fixture('catalog/tmdb/movie-550-overview-vacio-es.json')),
        ]);
    }

    public function test_crea_items_de_peliculas_a_partir_de_las_fixtures(): void
    {
        Bus::fake([DownloadItemImagesJob::class]);
        $this->fakeMovies();

        $this->artisan('catalog:fetch-new', ['--category' => 'peliculas', '--limit' => 5])->assertSuccessful();

        $items = Item::query()->whereHas('category', fn ($q) => $q->where('slug', 'peliculas'))->get();
        $this->assertCount(2, $items);
        $this->assertEqualsCanonicalizing(['Matrix', 'El club de la lucha'], $items->pluck('name')->all());
        $this->assertEqualsCanonicalizing(
            ['movie:603', 'movie:550'],
            ItemSource::query()->where('provider', 'tmdb')->pluck('external_id')->all()
        );
        foreach ($items as $item) {
            $this->assertSame(Item::STATUS_ACTIVE, $item->status);
            $this->assertNotNull($item->last_synced_at);
        }
        Bus::assertDispatchedTimes(DownloadItemImagesJob::class, 2);
    }

    public function test_respeta_el_limite(): void
    {
        Bus::fake([DownloadItemImagesJob::class]);
        $this->fakeMovies();

        $this->artisan('catalog:fetch-new', ['--category' => 'peliculas', '--limit' => 1])->assertSuccessful();

        $this->assertSame(['Matrix'], Item::query()->pluck('name')->all());
        Http::assertNotSent(fn (Request $request) => str_contains($request->url(), '/movie/550'));
    }

    public function test_con_all_importa_peliculas_y_series(): void
    {
        Bus::fake([DownloadItemImagesJob::class]);
        Http::fake([
            'api.themoviedb.org/3/movie/now_playing*' => Http::response($this->listPage([603])),
            'api.themoviedb.org/3/movie/upcoming*' => Http::response($this->listPage([])),
            'api.themoviedb.org/3/movie/popular*' => Http::response($this->listPage([])),
            'api.themoviedb.org/3/movie/603*' => Http::response($this->fixture('catalog/tmdb/movie-603-es.json')),
            'api.themoviedb.org/3/tv/on_the_air*' => Http::response($this->listPage([1399], 'name')),
            'api.themoviedb.org/3/tv/popular*' => Http::response($this->listPage([], 'name')),
            'api.themoviedb.org/3/tv/1399*' => Http::response($this->fixture('catalog/tmdb/tv-1399-es.json')),
        ]);

        $this->artisan('catalog:fetch-new', ['--all' => true, '--limit' => 5])->assertSuccessful();

        $this->assertSame(2, Item::query()->count());
        $this->assertSame(1, Item::query()->whereHas('category', fn ($q) => $q->where('slug', 'peliculas'))->count());
        $show = Item::query()->whereHas('category', fn ($q) => $q->where('slug', 'series'))->firstOrFail();
        $this->assertSame('tv:1399', $show->sources()->where('provider', 'tmdb')->value('external_id'));
    }

    public function test_reimportar_no_duplica(): void
    {
        Bus::fake([DownloadItemImagesJob::class]);
        $this->fakeMovies();

        $this->artisan('catalog:fetch-new', ['--category' => 'peliculas', '--limit' => 5])->assertSuccessful();
        $this->artisan('catalog:fetch-new', ['--category' => 'peliculas', '--limit' => 5])->assertSuccessful();

        $this->assertSame(2, Item::query()->count());
        $this->assertSame(2, ItemSource::query()->where('provider', 'tmdb')->count());
    }

    public function test_solo_encola_en_la_cola_catalog_sin_llamar_a_tmdb(): void
    {
        Queue::fake();
        Http::fake();

        $this->artisan('catalog:fetch-new', ['--all' => true])->assertSuccessful();

        Queue::assertPushedOn('catalog', FetchNewCatalogItemsJob::class);
        Queue::assertPushed(FetchNewCatalogItemsJob::class, 2);
        $jobs = Queue::pushed(FetchNewCatalogItemsJob::class);
        $this->assertSame(['peliculas', 'series'], $jobs->pluck('categorySlug')->all());
        foreach ($jobs as $job) {
            $this->assertSame((int) config('catalog.import_limit'), $job->limit);
        }
        Http::assertNothingSent();
    }

    public function test_all_omite_las_categorias_inactivas(): void
    {
        Queue::fake();
        Category::query()->where('slug', 'series')->update(['is_active' => false]);

        $this->artisan('catalog:fetch-new', ['--all' => true])->assertSuccessful();

        Queue::assertPushed(FetchNewCatalogItemsJob::class, 1);
        $this->assertSame('peliculas', Queue::pushed(FetchNewCatalogItemsJob::class)->first()->categorySlug);
    }

    public function test_falla_sin_category_ni_all(): void
    {
        Queue::fake();

        $this->artisan('catalog:fetch-new')->assertFailed();

        Queue::assertNothingPushed();
    }

    public function test_falla_con_category_y_all(): void
    {
        Queue::fake();

        $this->artisan('catalog:fetch-new', ['--category' => 'peliculas', '--all' => true])->assertFailed();

        Queue::assertNothingPushed();
    }

    public function test_falla_si_la_categoria_no_tiene_proveedor(): void
    {
        Queue::fake();
        $this->createCategory('libros', 'Libros');

        $this->artisan('catalog:fetch-new', ['--category' => 'libros'])->assertFailed();

        Queue::assertNothingPushed();
    }

    public function test_falla_si_la_categoria_no_existe(): void
    {
        Queue::fake();
        config(['catalog.categories.documentales' => ['provider' => TmdbMovieProvider::class]]);

        $this->artisan('catalog:fetch-new', ['--category' => 'documentales'])->assertFailed();

        Queue::assertNothingPushed();
    }

    public function test_falla_con_un_limite_no_positivo(): void
    {
        Queue::fake();

        $this->artisan('catalog:fetch-new', ['--category' => 'peliculas', '--limit' => 0])->assertFailed();

        Queue::assertNothingPushed();
    }
}
