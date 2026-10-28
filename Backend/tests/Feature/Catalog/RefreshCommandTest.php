<?php

namespace Tests\Feature\Catalog;

use App\Jobs\Catalog\DownloadItemImagesJob;
use App\Jobs\Catalog\ImportCatalogItemJob;
use App\Models\Category;
use App\Models\Item;
use App\Models\ItemSource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Sleep;
use Tests\Concerns\CreatesItems;
use Tests\Concerns\LoadsFixtures;
use Tests\TestCase;

class RefreshCommandTest extends TestCase
{
    use CreatesItems, LoadsFixtures, RefreshDatabase;

    private Category $peliculas;

    private Category $series;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'catalog.user_agent' => 'KudosTest/1.0 (+https://kudos.test)',
            'catalog.providers.tmdb.token' => 'token-de-prueba',
        ]);
        Sleep::fake();
        Cache::flush();

        $this->peliculas = $this->createCategory('peliculas', 'Películas');
        $this->series = $this->createCategory('series', 'Series');
    }

    private function syncedItem(Category $category, string $externalId, ?int $daysAgo, array $attrs = []): Item
    {
        $syncedAt = $daysAgo === null ? null : now()->subDays($daysAgo);
        $item = $this->createItem($category, $attrs + ['last_synced_at' => $syncedAt]);

        ItemSource::create([
            'item_id' => $item->id,
            'provider' => 'tmdb',
            'external_id' => $externalId,
            'url' => null,
            'is_primary' => true,
            'last_synced_at' => $syncedAt,
        ]);

        return $item;
    }

    /** @return list<array{0: string, 1: string}> pares (categoría, external_id) encolados */
    private function queuedPairs(): array
    {
        return Queue::pushed(ImportCatalogItemJob::class)
            ->map(fn ($job) => [$job->categorySlug, $job->externalId])
            ->sortBy(fn ($pair) => $pair[1])
            ->values()
            ->all();
    }

    public function test_solo_encola_los_items_con_last_synced_at_antiguo(): void
    {
        Queue::fake();
        $this->syncedItem($this->peliculas, 'movie:1', 200);
        $this->syncedItem($this->peliculas, 'movie:2', 151);
        $this->syncedItem($this->peliculas, 'movie:3', 10);
        $this->createItem($this->peliculas, ['name' => 'Sembrado']); // sin fuentes ni last_synced_at

        $this->artisan('catalog:refresh')->assertSuccessful();

        Queue::assertPushedOn('catalog', ImportCatalogItemJob::class);
        Queue::assertPushed(ImportCatalogItemJob::class, 2);
        $this->assertSame([['peliculas', 'movie:1'], ['peliculas', 'movie:2']], $this->queuedPairs());
    }

    public function test_older_than_cambia_el_umbral(): void
    {
        Queue::fake();
        $this->syncedItem($this->peliculas, 'movie:1', 200);
        $this->syncedItem($this->peliculas, 'movie:3', 10);

        $this->artisan('catalog:refresh', ['--older-than' => 5])->assertSuccessful();

        $this->assertSame([['peliculas', 'movie:1'], ['peliculas', 'movie:3']], $this->queuedPairs());
    }

    public function test_el_lote_empieza_por_los_mas_antiguos_y_avisa_de_los_pendientes(): void
    {
        Queue::fake();
        Log::spy();
        $this->syncedItem($this->peliculas, 'movie:1', 300);
        $this->syncedItem($this->peliculas, 'movie:2', 250);
        $this->syncedItem($this->peliculas, 'movie:3', 200);

        $this->artisan('catalog:refresh', ['--batch' => 2])->assertSuccessful();

        $this->assertSame([['peliculas', 'movie:1'], ['peliculas', 'movie:2']], $this->queuedPairs());
        Log::shouldHaveReceived('warning')
            ->withArgs(fn ($message, $context = []) => ($context['pending'] ?? null) === 1)
            ->once();
    }

    public function test_sin_pendientes_no_avisa(): void
    {
        Queue::fake();
        Log::spy();
        $this->syncedItem($this->peliculas, 'movie:1', 200);

        $this->artisan('catalog:refresh', ['--batch' => 200])->assertSuccessful();

        Queue::assertPushed(ImportCatalogItemJob::class, 1);
        Log::shouldNotHaveReceived('warning');
    }

    public function test_la_categoria_sale_del_item(): void
    {
        Queue::fake();
        $this->syncedItem($this->series, 'tv:1399', 200);

        $this->artisan('catalog:refresh')->assertSuccessful();

        $this->assertSame([['series', 'tv:1399']], $this->queuedPairs());
    }

    public function test_no_encola_los_items_borrados_y_si_los_inactivos(): void
    {
        Queue::fake();
        $deleted = $this->syncedItem($this->peliculas, 'movie:1', 200);
        $deleted->delete();
        $this->syncedItem($this->peliculas, 'movie:2', 200, ['status' => Item::STATUS_INACTIVE]);

        $this->artisan('catalog:refresh')->assertSuccessful();

        $this->assertSame([['peliculas', 'movie:2']], $this->queuedPairs());
    }

    public function test_ignora_las_categorias_sin_proveedor(): void
    {
        Queue::fake();
        $libros = $this->createCategory('libros', 'Libros');
        $this->syncedItem($libros, 'libro:1', 200);

        $this->artisan('catalog:refresh')->assertSuccessful();

        Queue::assertNothingPushed();
    }

    public function test_refresca_el_item_sin_tocar_su_status(): void
    {
        Bus::fake([DownloadItemImagesJob::class]);
        Http::fake(['api.themoviedb.org/3/movie/603*' => Http::response($this->fixture('catalog/tmdb/movie-603-es.json'))]);
        $item = $this->syncedItem($this->peliculas, 'movie:603', 200, [
            'name' => 'Nombre antiguo',
            'status' => Item::STATUS_INACTIVE,
        ]);

        $this->artisan('catalog:refresh')->assertSuccessful();

        $item = $item->fresh();
        $this->assertSame('Matrix', $item->name);
        $this->assertTrue($item->last_synced_at->isToday());
        $this->assertSame(Item::STATUS_INACTIVE, $item->status); // decisión del humano en la F1-04
    }

    public function test_falla_con_opciones_no_positivas(): void
    {
        Queue::fake();

        $this->artisan('catalog:refresh', ['--batch' => 0])->assertFailed();
        $this->artisan('catalog:refresh', ['--older-than' => 'abc'])->assertFailed();

        Queue::assertNothingPushed();
    }
}
