<?php

namespace Tests\Feature\Catalog;

use App\Jobs\Catalog\PurgeStaleCatalogItemsJob;
use App\Models\Category;
use App\Models\Item;
use App\Models\ItemSource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesItems;
use Tests\TestCase;

class PurgeStaleCommandTest extends TestCase
{
    use CreatesItems, RefreshDatabase;

    private Category $peliculas;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->peliculas = $this->createCategory('peliculas', 'Películas');
    }

    /** @return array<string, mixed> entrada de images con sus archivos en el disco falso */
    private function imageEntry(Item $item, string $hash, ?string $sourceUrl, int $order = 0): array
    {
        $directory = "items/peliculas/{$item->id}";
        $variants = ['thumb' => "{$directory}/{$hash}-thumb.webp", 'banner' => "{$directory}/{$hash}-banner.webp"];

        foreach ($variants as $path) {
            Storage::disk('public')->put($path, 'x');
        }

        return [
            'disk' => 'public',
            'variants' => $variants,
            'meta' => [],
            'alt' => $item->name,
            'order' => $order,
            'role' => 'poster',
            'license' => $sourceUrl === null ? null : '© titular de derechos',
            'author' => null,
            'source_url' => $sourceUrl,
        ];
    }

    /** Ítem de TMDB sincronizado hace $days días, con una imagen de TMDB cuyos archivos existen. */
    private function tmdbItem(int $number, int $days, array $attrs = []): Item
    {
        $syncedAt = now()->subDays($days);
        $item = $this->createItem($this->peliculas, $attrs + [
            'name' => "Película {$number}",
            'last_synced_at' => $syncedAt,
            'description_source' => 'tmdb',
            'description_url' => 'https://www.themoviedb.org/movie/'.$number,
            'popularity' => 50,
        ]);
        $item->setAttribute('attributes', ['year' => 1999, 'genres' => ['Drama']]);
        $item->images = [$this->imageEntry($item, 'abc', 'https://www.themoviedb.org/movie/1')];
        $item->save();

        ItemSource::create([
            'item_id' => $item->id,
            'provider' => 'tmdb',
            'external_id' => "movie:{$number}",
            'url' => null,
            'is_primary' => true,
            'last_synced_at' => $syncedAt,
        ]);

        return $item;
    }

    private function assertTmdbFilesExist(Item $item, bool $exist): void
    {
        foreach (['thumb', 'banner'] as $variant) {
            $path = "items/peliculas/{$item->id}/abc-{$variant}.webp";
            $exist ? Storage::disk('public')->assertExists($path) : Storage::disk('public')->assertMissing($path);
        }
    }

    public function test_desactiva_los_items_de_tmdb_con_mas_de_180_dias_y_borra_sus_imagenes(): void
    {
        $item = $this->tmdbItem(1, 181);
        $slug = $item->slug;

        $this->artisan('catalog:purge-stale')->assertSuccessful();

        $fresh = $item->fresh();
        $this->assertSame(Item::STATUS_INACTIVE, $fresh->status);
        $this->assertSame([], $fresh->images);
        $this->assertTmdbFilesExist($item, false);
        $this->assertNull($fresh->description);
        $this->assertNull($fresh->description_source);
        $this->assertNull($fresh->description_url);
        $this->assertSame([], $fresh->getAttribute('attributes'));
        $this->assertSame(0.0, $fresh->popularity);
        $this->assertLessThan((int) config('catalog.min_quality_for_discovery'), $fresh->quality_score);
        $this->assertSame('Película 1', $fresh->name);
        $this->assertSame($slug, $fresh->slug);
        $this->assertTrue($fresh->sources()->where('provider', 'tmdb')->exists());
    }

    public function test_no_toca_los_items_refrescados_hace_menos_de_180_dias(): void
    {
        $item = $this->tmdbItem(1, 179);

        $this->artisan('catalog:purge-stale')->assertSuccessful();

        $fresh = $item->fresh();
        $this->assertSame(Item::STATUS_ACTIVE, $fresh->status);
        $this->assertCount(1, $fresh->images);
        $this->assertSame('tmdb', $fresh->description_source);
        $this->assertSame(['year' => 1999, 'genres' => ['Drama']], $fresh->getAttribute('attributes'));
        $this->assertSame(50.0, $fresh->popularity);
        $this->assertTmdbFilesExist($item, true);
    }

    public function test_no_toca_los_items_sin_fuente_de_tmdb(): void
    {
        $item = $this->createItem($this->peliculas, ['name' => 'Sembrado', 'last_synced_at' => null]);
        $item->images = [$this->imageEntry($item, 'abc', 'https://www.themoviedb.org/movie/1')];
        $item->save();

        $this->artisan('catalog:purge-stale')->assertSuccessful();

        $fresh = $item->fresh();
        $this->assertSame(Item::STATUS_ACTIVE, $fresh->status);
        $this->assertCount(1, $fresh->images);
        $this->assertTmdbFilesExist($item, true);
    }

    public function test_conserva_la_descripcion_de_otra_fuente_y_las_imagenes_que_no_son_de_tmdb(): void
    {
        $item = $this->tmdbItem(1, 181, [
            'description_source' => 'wikipedia_es',
            'description_url' => 'https://es.wikipedia.org/wiki/Matrix',
        ]);
        $item->images = [
            $this->imageEntry($item, 'abc', 'https://www.themoviedb.org/movie/1', 0),
            $this->imageEntry($item, 'propia', null, 1),
        ];
        $item->save();
        $description = $item->description;

        $this->artisan('catalog:purge-stale')->assertSuccessful();

        $fresh = $item->fresh();
        $this->assertSame(Item::STATUS_INACTIVE, $fresh->status);
        $this->assertSame($description, $fresh->description);
        $this->assertSame('wikipedia_es', $fresh->description_source);
        $this->assertCount(1, $fresh->images);
        $this->assertSame("items/peliculas/{$item->id}/propia-thumb.webp", $fresh->images[0]['variants']['thumb']);
        $this->assertSame(0, $fresh->images[0]['order']);
        $this->assertTmdbFilesExist($item, false);
        Storage::disk('public')->assertExists("items/peliculas/{$item->id}/propia-thumb.webp");
        Storage::disk('public')->assertExists("items/peliculas/{$item->id}/propia-banner.webp");
    }

    public function test_tambien_limpia_los_items_borrados(): void
    {
        $item = $this->tmdbItem(1, 181);
        $item->delete();

        $this->artisan('catalog:purge-stale')->assertSuccessful();

        $fresh = Item::withTrashed()->findOrFail($item->id);
        $this->assertTrue($fresh->trashed());
        $this->assertSame([], $fresh->images);
        $this->assertTmdbFilesExist($item, false);
    }

    public function test_solo_encola_en_la_cola_catalog(): void
    {
        $old1 = $this->tmdbItem(1, 181);
        $old2 = $this->tmdbItem(2, 400);
        $recent = $this->tmdbItem(3, 10);
        Queue::fake();

        $this->artisan('catalog:purge-stale')->assertSuccessful();

        Queue::assertPushedOn('catalog', PurgeStaleCatalogItemsJob::class);
        Queue::assertPushed(PurgeStaleCatalogItemsJob::class, 1);
        $ids = Queue::pushed(PurgeStaleCatalogItemsJob::class)->first()->itemIds;
        $this->assertEqualsCanonicalizing([$old1->id, $old2->id], $ids);
        $this->assertNotContains($recent->id, $ids);
        $this->assertSame(Item::STATUS_ACTIVE, $old1->fresh()->status);
    }

    public function test_el_job_no_toca_un_item_refrescado_entretanto(): void
    {
        $recent = $this->tmdbItem(1, 10);

        PurgeStaleCatalogItemsJob::dispatchSync([$recent->id]);

        $fresh = $recent->fresh();
        $this->assertSame(Item::STATUS_ACTIVE, $fresh->status);
        $this->assertCount(1, $fresh->images);
        $this->assertTmdbFilesExist($recent, true);
    }

    public function test_es_idempotente(): void
    {
        $item = $this->tmdbItem(1, 181);
        $this->artisan('catalog:purge-stale')->assertSuccessful();
        $updatedAt = $item->fresh()->updated_at;

        $this->travel(1)->hours();
        Log::spy();
        $this->artisan('catalog:purge-stale')->assertSuccessful();

        Log::shouldNotHaveReceived('warning');
        $this->assertEquals($updatedAt, $item->fresh()->updated_at);
    }
}
