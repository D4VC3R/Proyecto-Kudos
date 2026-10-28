<?php

namespace Tests\Feature\Categories;

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesItems;
use Tests\Concerns\InteractsWithUsers;
use Tests\TestCase;

/**
 * F1-06: las categorías inactivas no se listan y sus rutas públicas dan 404.
 * El código de error es route_not_found, el mismo que un slug inexistente: el Handler de Laravel convierte
 * ModelNotFoundException en NotFoundHttpException antes de los render() de bootstrap/app.php.
 */
class CategoryVisibilityTest extends TestCase
{
    use RefreshDatabase, CreatesItems, InteractsWithUsers;

    private function createInactiveCategory(): Category
    {
        return Category::factory()->create([
            'slug' => 'videojuegos',
            'name' => 'Videojuegos',
            'is_active' => false,
        ]);
    }

    public function test_el_listado_solo_devuelve_las_categorias_activas(): void
    {
        $this->createCategory('peliculas', 'Películas');
        $this->createInactiveCategory();

        $response = $this->getJson('/api/categories')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.slug', 'peliculas');

        $this->assertNotContains('videojuegos', collect($response->json('data'))->pluck('slug')->all());
    }

    public function test_el_listado_se_ordena_por_sort_order_y_despues_por_nombre(): void
    {
        Category::factory()->create(['slug' => 'animacion', 'name' => 'Animación', 'sort_order' => 2]);
        Category::factory()->create(['slug' => 'series', 'name' => 'Series', 'sort_order' => 0]);
        Category::factory()->create(['slug' => 'peliculas', 'name' => 'Películas', 'sort_order' => 1]);
        Category::factory()->create(['slug' => 'documentales', 'name' => 'Documentales', 'sort_order' => 1]);

        $response = $this->getJson('/api/categories')->assertOk();

        $this->assertSame(
            ['series', 'documentales', 'peliculas', 'animacion'],
            collect($response->json('data'))->pluck('slug')->all()
        );
    }

    public function test_el_detalle_de_una_categoria_inactiva_da_404(): void
    {
        $this->createInactiveCategory();

        $this->getJson('/api/categories/videojuegos')
            ->assertNotFound()
            ->assertJsonPath('error.code', 'route_not_found');

        // Misma respuesta que un slug que no existe.
        $this->assertSame(
            $this->getJson('/api/categories/no-existe')->getContent(),
            $this->getJson('/api/categories/videojuegos')->getContent()
        );
    }

    public function test_el_ranking_de_una_categoria_inactiva_da_404(): void
    {
        $this->createInactiveCategory();

        $this->getJson('/api/categories/videojuegos/ranking')
            ->assertNotFound()
            ->assertJsonPath('error.code', 'route_not_found');
    }

    public function test_la_cola_de_una_categoria_inactiva_da_404(): void
    {
        $this->createInactiveCategory();
        $this->actingAsUser();

        $this->getJson('/api/categories/videojuegos/next-item')
            ->assertNotFound()
            ->assertJsonPath('error.code', 'route_not_found');
    }

    public function test_un_admin_tampoco_ve_el_detalle_de_una_categoria_inactiva(): void
    {
        $this->createInactiveCategory();
        $this->actingAsAdmin();

        $this->getJson('/api/categories/videojuegos')->assertNotFound();
    }

    public function test_una_categoria_activa_sigue_respondiendo(): void
    {
        $this->createCategory('peliculas', 'Películas');

        $this->getJson('/api/categories/peliculas')->assertOk();
        $this->getJson('/api/categories/peliculas/ranking')->assertOk();
    }

    public function test_un_admin_puede_editar_y_borrar_una_categoria_inactiva(): void
    {
        $this->createInactiveCategory();
        $this->actingAsAdmin();

        $this->putJson('/api/categories/videojuegos', ['description' => 'Nueva descripción'])->assertOk();
        $this->assertDatabaseHas('categories', [
            'slug' => 'videojuegos',
            'description' => 'Nueva descripción',
            'is_active' => false,
        ]);

        $this->deleteJson('/api/categories/videojuegos')->assertOk();
        $this->assertDatabaseMissing('categories', ['slug' => 'videojuegos']);
    }
}
