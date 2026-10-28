<?php

namespace Tests\Feature\Catalog;

use App\Jobs\Catalog\DownloadItemImagesJob;
use App\Jobs\Catalog\ImportCatalogItemJob;
use App\Models\Category;
use App\Models\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Sleep;
use Tests\Concerns\CreatesItems;
use Tests\Concerns\LoadsFixtures;
use Tests\TestCase;

class ImportOneCommandTest extends TestCase
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

    public function test_importa_una_pelicula_por_proveedor_y_external_id(): void
    {
        Bus::fake([DownloadItemImagesJob::class]);
        Http::fake(['api.themoviedb.org/3/movie/603*' => Http::response($this->fixture('catalog/tmdb/movie-603-es.json'))]);

        $this->artisan('catalog:import', ['provider' => 'tmdb', 'externalId' => 'movie:603'])->assertSuccessful();

        $item = Item::query()->firstOrFail();
        $this->assertSame('Matrix', $item->name);
        $this->assertSame('peliculas', $item->category->slug);
    }

    public function test_traduce_tv_a_la_categoria_series(): void
    {
        Queue::fake();
        Http::fake();

        $this->artisan('catalog:import', ['provider' => 'tmdb', 'externalId' => 'tv:1399'])->assertSuccessful();

        Queue::assertPushedOn('catalog', ImportCatalogItemJob::class);
        Queue::assertPushed(ImportCatalogItemJob::class, fn ($job) => $job->categorySlug === 'series' && $job->externalId === 'tv:1399');
        Http::assertNothingSent();
    }

    public function test_falla_con_un_proveedor_desconocido(): void
    {
        Queue::fake();

        $this->artisan('catalog:import', ['provider' => 'igdb', 'externalId' => '123'])->assertFailed();

        Queue::assertNothingPushed();
    }

    public function test_falla_con_un_external_id_que_no_reconoce_ningun_proveedor(): void
    {
        Queue::fake();

        $this->artisan('catalog:import', ['provider' => 'tmdb', 'externalId' => 'person:1'])->assertFailed();
        $this->artisan('catalog:import', ['provider' => 'tmdb', 'externalId' => '603'])->assertFailed();

        Queue::assertNothingPushed();
    }

    public function test_falla_si_la_categoria_no_existe(): void
    {
        Queue::fake();
        Category::query()->where('slug', 'series')->delete();

        $this->artisan('catalog:import', ['provider' => 'tmdb', 'externalId' => 'tv:1399'])->assertFailed();

        Queue::assertNothingPushed();
    }
}
