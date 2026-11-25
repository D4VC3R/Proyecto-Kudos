<?php

namespace Tests\Feature\Items;

use App\Models\Category;
use App\Models\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesItems;
use Tests\Concerns\InteractsWithUsers;
use Tests\TestCase;

/**
 * Visibilidad de los ítems de una categoría inactiva en el listado y en la ficha.
 */
class ItemVisibilityTest extends TestCase
{
    use CreatesItems, InteractsWithUsers, RefreshDatabase;

    private Item $visible;

    private Item $hidden;

    protected function setUp(): void
    {
        parent::setUp();

        $active = $this->createCategory('peliculas', 'Películas');
        $inactive = Category::factory()->create(['slug' => 'ocultas', 'name' => 'Ocultas', 'is_active' => false]);

        $this->visible = $this->createItem($active, ['name' => 'Matrix visible']);
        $this->hidden = $this->createItem($inactive, ['name' => 'Matrix oculta']);
    }

    public function test_el_listado_de_items_no_incluye_los_de_una_categoria_inactiva(): void
    {
        $ids = $this->getJson('/api/items')->assertOk()->json('data.*.id');

        $this->assertContains($this->visible->id, $ids);
        $this->assertNotContains($this->hidden->id, $ids);
    }

    public function test_la_busqueda_no_devuelve_items_de_una_categoria_inactiva(): void
    {
        $ids = $this->getJson('/api/items?search=Matrix')->assertOk()->json('data.*.id');

        $this->assertSame([$this->visible->id], $ids);
    }

    public function test_filtrar_por_una_categoria_inactiva_no_devuelve_items(): void
    {
        $this->getJson('/api/items?category_slug=ocultas')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_la_ficha_de_un_item_de_una_categoria_inactiva_da_404_a_un_invitado(): void
    {
        $response = $this->getJson("/api/items/{$this->hidden->id}")
            ->assertNotFound()
            ->assertJsonPath('error.code', 'not_found')
            ->assertJsonPath('error.message', 'El ítem no existe.');

        $missing = $this->getJson('/api/items/00000000-0000-0000-0000-000000000000')->assertNotFound();

        $this->assertSame($missing->getContent(), $response->getContent());
    }

    public function test_la_ficha_de_un_item_de_una_categoria_inactiva_da_404_a_un_usuario(): void
    {
        $this->actingAsUser();

        $this->getJson("/api/items/{$this->hidden->id}")
            ->assertNotFound()
            ->assertJsonPath('error.code', 'not_found');
    }

    public function test_el_admin_ve_la_ficha_de_un_item_de_una_categoria_inactiva(): void
    {
        $this->actingAsAdmin();

        $this->getJson("/api/items/{$this->hidden->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $this->hidden->id);
    }

    public function test_un_item_inactivo_de_una_categoria_activa_sigue_dando_403_al_invitado(): void
    {
        $inactive = $this->createItem($this->visible->category, ['name' => 'Inactivo', 'status' => Item::STATUS_INACTIVE]);

        $this->getJson("/api/items/{$inactive->id}")
            ->assertForbidden()
            ->assertJsonPath('error.code', 'forbidden');
    }
}
