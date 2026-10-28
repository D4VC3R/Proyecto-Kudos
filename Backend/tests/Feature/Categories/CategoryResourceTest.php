<?php

namespace Tests\Feature\Categories;

use App\Catalog\Providers\TmdbMovieProvider;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesItems;
use Tests\Concerns\InteractsWithUsers;
use Tests\TestCase;

/**
 * F1-06: CategoryResource y CategoryWithItemsResource exponen attribute_schema, icon y sort_order.
 */
class CategoryResourceTest extends TestCase
{
    use RefreshDatabase, CreatesItems, InteractsWithUsers;

    private function createMovieCategory(): Category
    {
        return Category::factory()->create([
            'slug' => 'peliculas',
            'name' => 'Películas',
            'attribute_schema' => TmdbMovieProvider::ATTRIBUTE_SCHEMA,
            'icon' => 'clapperboard',
            'sort_order' => 1,
        ]);
    }

    public function test_el_listado_expone_attribute_schema_icon_y_sort_order(): void
    {
        $this->createMovieCategory();

        $response = $this->getJson('/api/categories')
            ->assertOk()
            ->assertJsonStructure(['data' => [[
                'id', 'name', 'description', 'slug', 'image', 'items_count', 'attribute_schema', 'icon', 'sort_order',
            ]]])
            ->assertJsonPath('data.0.icon', 'clapperboard')
            ->assertJsonPath('data.0.sort_order', 1);

        // jsonb no conserva el orden de las claves: assertEquals, no assertSame.
        $this->assertEquals(TmdbMovieProvider::ATTRIBUTE_SCHEMA, $response->json('data.0.attribute_schema'));
    }

    public function test_el_detalle_de_la_categoria_expone_los_campos_nuevos(): void
    {
        $this->createMovieCategory();

        $response = $this->getJson('/api/categories/peliculas')
            ->assertOk()
            ->assertJsonStructure(['data' => [
                'id', 'name', 'description', 'slug', 'image', 'items_count',
                'attribute_schema', 'icon', 'sort_order', 'created_at', 'updated_at',
            ]])
            ->assertJsonPath('data.icon', 'clapperboard')
            ->assertJsonPath('data.sort_order', 1);

        $this->assertEquals(TmdbMovieProvider::ATTRIBUTE_SCHEMA, $response->json('data.attribute_schema'));
    }

    public function test_el_ranking_expone_los_campos_nuevos_de_la_categoria(): void
    {
        $this->createMovieCategory();

        $this->getJson('/api/categories/peliculas/ranking')
            ->assertOk()
            ->assertJsonStructure(['data' => ['category' => ['attribute_schema', 'icon', 'sort_order']]])
            ->assertJsonPath('data.category.icon', 'clapperboard')
            ->assertJsonPath('data.category.sort_order', 1);
    }

    public function test_una_categoria_sin_datos_de_catalogo_devuelve_los_valores_por_defecto(): void
    {
        $this->createCategory();

        $this->getJson('/api/categories')
            ->assertOk()
            ->assertJsonPath('data.0.attribute_schema', [])
            ->assertJsonPath('data.0.icon', null)
            ->assertJsonPath('data.0.sort_order', 0);
    }

    public function test_crear_una_categoria_devuelve_los_valores_por_defecto(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/api/categories', [
            'name' => 'Documentales',
            'description' => 'Descripción',
            'slug' => 'documentales',
        ])
            ->assertCreated()
            ->assertJsonPath('data.attribute_schema', [])
            ->assertJsonPath('data.sort_order', 0)
            ->assertJsonPath('data.icon', null);
    }
}
