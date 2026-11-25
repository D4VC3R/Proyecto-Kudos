<?php

namespace Tests\Feature\Categories;

use App\Models\Category;
use App\Models\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesItems;
use Tests\TestCase;

/**
 * Recuento de ítems de las categorías: se carga en la consulta y no se calcula al leerlo.
 */
class CategoryItemsCountTest extends TestCase
{
    use RefreshDatabase, CreatesItems;

    private function createCategoryWithItems(): Category
    {
        $category = $this->createCategory('peliculas', 'Películas');
        $this->createItem($category);
        $this->createItem($category);
        $this->createItem($category, ['status' => Item::STATUS_INACTIVE]);

        return $category;
    }

    public function test_el_listado_de_categorias_cuenta_solo_los_items_activos(): void
    {
        $this->createCategoryWithItems();

        $this->getJson('/api/categories')
            ->assertOk()
            ->assertJsonPath('data.0.items_count', 2);
    }

    public function test_crear_una_categoria_devuelve_items_count_a_cero(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/api/categories', [
            'name' => 'Series',
            'description' => 'Series de televisión',
            'slug' => 'series',
        ])
            ->assertCreated()
            ->assertJsonPath('data.items_count', 0);
    }

    public function test_actualizar_una_categoria_devuelve_su_recuento_de_items_activos(): void
    {
        $category = $this->createCategory('peliculas', 'Películas');
        $this->createItem($category);
        $this->createItem($category, ['status' => Item::STATUS_INACTIVE]);
        $this->actingAsAdmin();

        $this->putJson('/api/categories/peliculas', ['description' => 'Nueva descripción'])
            ->assertOk()
            ->assertJsonPath('data.items_count', 1);
    }

    public function test_borrar_una_categoria_con_items_da_409_con_el_numero_total_de_items(): void
    {
        $category = $this->createCategory('peliculas', 'Películas');
        $this->createItem($category);
        $this->createItem($category, ['status' => Item::STATUS_INACTIVE]);
        $this->actingAsAdmin();

        $this->deleteJson('/api/categories/peliculas')
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'CONFLICT_CATEGORY_HAS_ITEMS')
            ->assertJsonPath('error.message', 'No se puede eliminar la categoría porque tiene 2 ítems asociados.');

        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }
}
