<?php

namespace Tests\Feature\Categories;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\CreatesItems;
use Tests\TestCase;

class ReservedCategorySlugTest extends TestCase
{
    use RefreshDatabase, CreatesItems;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
    }

    public function test_crear_una_categoria_con_slug_reservado_da_422(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson('/api/categories', [
            'name' => 'Búsquedas',
            'description' => 'Descripción',
            'slug' => 'buscar',
        ]);

        $response->assertStatus(422);
        $response->assertJsonStructure(['error' => ['details' => ['slug']]]);
        $this->assertDatabaseMissing('categories', ['slug' => 'buscar']);
    }

    public function test_crear_una_categoria_cuyo_nombre_genera_un_slug_reservado_da_422(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson('/api/categories', [
            'name' => 'Buscar',
            'description' => 'Descripción',
        ]);

        $response->assertStatus(422);
        $response->assertJsonStructure(['error' => ['details' => ['slug']]]);
        $this->assertDatabaseMissing('categories', ['slug' => 'buscar']);
    }

    public function test_el_slug_reservado_no_distingue_mayusculas(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson('/api/categories', [
            'name' => 'Búsquedas',
            'description' => 'Descripción',
            'slug' => 'Buscar',
        ]);

        $response->assertStatus(422);
        $response->assertJsonStructure(['error' => ['details' => ['slug']]]);
    }

    public function test_todos_los_slugs_reservados_se_rechazan(): void
    {
        $this->actingAsAdmin();

        $reserved = config('catalog.reserved_slugs');
        $this->assertNotEmpty($reserved);
        $this->assertContains('buscar', $reserved);

        foreach ($reserved as $slug) {
            $response = $this->postJson('/api/categories', [
                'name' => "Categoría {$slug}",
                'description' => 'Descripción',
                'slug' => $slug,
            ]);

            $response->assertStatus(422);
        }
    }

    public function test_un_slug_no_reservado_se_sigue_aceptando(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson('/api/categories', [
            'name' => 'Documentales',
            'description' => 'Descripción',
            'slug' => 'documentales',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('categories', ['slug' => 'documentales']);
    }

    public function test_actualizar_una_categoria_a_un_slug_reservado_da_422(): void
    {
        $this->actingAsAdmin();
        $this->createCategory('peliculas', 'Películas');

        $response = $this->putJson('/api/categories/peliculas', ['slug' => 'ranking']);

        $response->assertStatus(422);
        $response->assertJsonStructure(['error' => ['details' => ['slug']]]);
        $this->assertDatabaseHas('categories', ['slug' => 'peliculas']);
        $this->assertDatabaseMissing('categories', ['slug' => 'ranking']);
    }

    public function test_actualizar_el_nombre_a_uno_que_genera_un_slug_reservado_da_422(): void
    {
        $this->actingAsAdmin();
        $this->createCategory('peliculas', 'Películas');

        $response = $this->putJson('/api/categories/peliculas', ['name' => 'Admin']);

        $response->assertStatus(422);
        $this->assertDatabaseHas('categories', ['slug' => 'peliculas']);
    }

    public function test_actualizar_una_categoria_sin_tocar_el_slug_sigue_funcionando(): void
    {
        $this->actingAsAdmin();
        $this->createCategory('peliculas', 'Películas');

        $response = $this->putJson('/api/categories/peliculas', ['description' => 'Nueva descripción']);

        $response->assertOk();
        $this->assertDatabaseHas('categories', ['slug' => 'peliculas', 'description' => 'Nueva descripción']);
    }
}
