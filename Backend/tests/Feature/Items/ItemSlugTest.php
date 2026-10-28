<?php

namespace Tests\Feature\Items;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\CreatesItems;
use Tests\TestCase;

/**
 * El slug del ítem se genera al crearlo (F1-01): nombre en slug + año, con sufijo si colisiona.
 */
class ItemSlugTest extends TestCase
{
    use RefreshDatabase, CreatesItems;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
    }

    public function test_dos_items_con_el_mismo_nombre_y_anio_tienen_slugs_distintos(): void
    {
        $category = $this->createCategory();
        $first = $this->createItem($category, ['name' => 'El Padrino', 'released_at' => '1972-03-24']);
        $second = $this->createItem($category, ['name' => 'El Padrino', 'released_at' => '1972-03-24']);

        $this->assertSame('el-padrino-1972', $first->fresh()->slug);
        $this->assertSame('el-padrino-1972-2', $second->fresh()->slug);
        $this->assertNotSame($first->slug, $second->slug);
    }

    public function test_el_slug_sin_anio_es_el_nombre_en_slug(): void
    {
        $category = $this->createCategory();
        $perpetua = $this->createItem($category, ['name' => 'Cadena Perpetua']);
        $amelie = $this->createItem($category, ['name' => 'Amélie']);

        $this->assertSame('cadena-perpetua', $perpetua->fresh()->slug);
        $this->assertSame('amelie', $amelie->fresh()->slug);
    }

    public function test_el_anio_del_slug_sale_de_attributes_si_no_hay_released_at(): void
    {
        $category = $this->createCategory();
        $item = $this->createItem($category, ['name' => 'Matrix', 'attributes' => ['year' => 1999]]);

        $this->assertSame('matrix-1999', $item->fresh()->slug);
    }

    public function test_no_sobrescribe_un_slug_explicito(): void
    {
        $category = $this->createCategory();
        $item = $this->createItem($category, ['name' => 'Matrix', 'slug' => 'mi-slug']);

        $this->assertSame('mi-slug', $item->fresh()->slug);
    }

    public function test_el_slug_tiene_en_cuenta_los_items_borrados(): void
    {
        $category = $this->createCategory();
        $first = $this->createItem($category, ['name' => 'Matrix']);
        $first->delete();

        $second = $this->createItem($category, ['name' => 'Matrix']);

        $this->assertSame('matrix', $first->slug);
        $this->assertSame('matrix-2', $second->fresh()->slug);
    }

    public function test_cambiar_el_nombre_no_cambia_el_slug(): void
    {
        $category = $this->createCategory();
        $item = $this->createItem($category, ['name' => 'Matrix']);

        $item->update(['name' => 'Otro']);

        $this->assertSame('matrix', $item->fresh()->slug);
    }
}
