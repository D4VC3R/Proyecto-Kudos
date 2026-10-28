<?php

namespace Tests\Feature\Items;

use App\Models\Item;
use App\Models\ItemSource;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\CreatesItems;
use Tests\TestCase;

/**
 * Campos nuevos del catálogo en items, item_sources y categories (F1-01).
 */
class ItemCatalogFieldsTest extends TestCase
{
    use RefreshDatabase, CreatesItems;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
    }

    public function test_un_item_puede_guardarse_sin_descripcion(): void
    {
        $item = $this->createItem($this->createCategory(), ['description' => null, 'images' => []]);

        $item = $item->fresh();
        $this->assertNull($item->description);
        $this->assertSame(0, $item->quality_score);
    }

    public function test_los_campos_del_catalogo_se_castean(): void
    {
        $category = $this->createCategory();
        $item = $this->createItem($category, [
            'released_at' => '1999-03-31',
            'attributes' => ['year' => 1999, 'genres' => ['Ciencia ficción']],
            'last_synced_at' => '2026-10-01 12:00:00',
            'popularity' => 123.5,
        ])->fresh();

        $this->assertInstanceOf(Carbon::class, $item->released_at);
        $this->assertSame('1999-03-31', $item->released_at->toDateString());
        $this->assertEquals(['year' => 1999, 'genres' => ['Ciencia ficción']], $item->getAttribute('attributes'));
        $this->assertInstanceOf(Carbon::class, $item->last_synced_at);
        $this->assertIsFloat($item->popularity);
        $this->assertSame(123.5, $item->popularity);

        $plain = $this->createItem($category, ['name' => 'Sin atributos'])->fresh();
        $this->assertSame([], $plain->getAttribute('attributes'));
    }

    public function test_released_at_suma_20_al_quality_score_al_guardar(): void
    {
        $item = $this->createItem($this->createCategory(), [
            'images' => [],
            'description' => 'Item importado.',
            'released_at' => '2001-05-05',
        ]);

        $this->assertSame(20, $item->fresh()->quality_score);
    }

    public function test_sources_devuelve_las_fuentes_del_item(): void
    {
        $item = $this->createItem($this->createCategory());
        ItemSource::query()->create([
            'item_id' => $item->id,
            'provider' => 'tmdb',
            'external_id' => 'movie:603',
            'is_primary' => true,
        ]);
        ItemSource::query()->create([
            'item_id' => $item->id,
            'provider' => 'wikidata',
            'external_id' => 'Q83495',
        ]);

        $sources = $item->fresh()->sources;

        $this->assertCount(2, $sources);
        $this->assertTrue($sources->first()->item->is($item));
    }

    public function test_provider_y_external_id_son_unicos(): void
    {
        $category = $this->createCategory();
        $first = $this->createItem($category, ['name' => 'Matrix']);
        $second = $this->createItem($category, ['name' => 'Otra']);
        ItemSource::query()->create(['item_id' => $first->id, 'provider' => 'tmdb', 'external_id' => 'movie:603']);

        $this->expectException(UniqueConstraintViolationException::class);

        ItemSource::query()->create(['item_id' => $second->id, 'provider' => 'tmdb', 'external_id' => 'movie:603']);
    }

    public function test_borrar_definitivamente_un_item_borra_sus_fuentes(): void
    {
        $item = $this->createItem($this->createCategory());
        ItemSource::query()->create(['item_id' => $item->id, 'provider' => 'tmdb', 'external_id' => 'movie:603']);
        $this->assertSame(1, ItemSource::query()->count());

        $item->forceDelete();

        $this->assertSame(0, ItemSource::query()->count());
    }

    public function test_los_campos_nuevos_de_categoria_tienen_valores_por_defecto(): void
    {
        $category = $this->createCategory()->fresh();

        $this->assertSame([], $category->attribute_schema);
        $this->assertTrue($category->is_active);
        $this->assertSame(0, $category->sort_order);
        $this->assertNull($category->icon);

        $schema = [
            ['key' => 'year', 'label' => 'Año', 'type' => 'integer', 'filterable' => true, 'show_in_card' => true],
            ['key' => 'genres', 'label' => 'Géneros', 'type' => 'tags', 'filterable' => true, 'show_in_card' => true],
        ];
        $category->update(['attribute_schema' => $schema, 'icon' => 'clapperboard']);

        // jsonb no conserva el orden de las claves de cada objeto, así que se compara sin orden.
        $this->assertEquals($schema, $category->fresh()->attribute_schema);
        $this->assertSame('clapperboard', $category->fresh()->icon);
    }

    public function test_los_states_de_la_factory_rellenan_los_campos_del_catalogo(): void
    {
        User::factory()->create();
        $category = $this->createCategory();

        $imported = Item::factory()->imported()->create(['category_id' => $category->id])->fresh();

        $this->assertNull($imported->creator_id);
        $this->assertNotNull($imported->original_title);
        $this->assertNotNull($imported->released_at);
        $this->assertNotNull($imported->wikidata_id);
        $this->assertNotNull($imported->last_synced_at);
        $this->assertSame($imported->released_at->year, $imported->getAttribute('attributes')['year']);

        $bare = Item::factory()->withoutDescription()->create(['category_id' => $category->id])->fresh();
        $this->assertNull($bare->description);
    }
}
