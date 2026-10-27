<?php

namespace Tests\Feature\Items;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\CreatesItems;
use Tests\TestCase;

/**
 * Los comodines de LIKE (%, _ y \) que escribe el usuario en la búsqueda se tratan como texto literal (F0-15).
 * En /api/items "search" exige al menos 2 caracteres (F0-09), así que ahí se prueba con "0%" y "_1";
 * los comodines sueltos se prueban en /api/admin/items, que no tiene mínimo.
 */
class ItemSearchWildcardsTest extends TestCase
{
    use RefreshDatabase, CreatesItems;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
    }

    public function test_buscar_un_porcentaje_en_items_solo_devuelve_los_que_lo_contienen(): void
    {
        $category = $this->createCategory('videojuegos', 'Videojuegos');
        $this->createItem($category, ['name' => 'Descuento del 100%']);
        $this->createItem($category, ['name' => 'Episodio 1000']);
        $this->createItem($category, ['name' => 'Elden Ring']);

        $response = $this->getJson('/api/items?' . http_build_query(['search' => '0%']));

        $response->assertOk();
        $this->assertSame(['Descuento del 100%'], collect($response->json('data'))->pluck('name')->all());
    }

    public function test_buscar_un_guion_bajo_en_items_solo_devuelve_los_que_lo_contienen(): void
    {
        $category = $this->createCategory('videojuegos', 'Videojuegos');
        $this->createItem($category, ['name' => 'Pack_1']);
        $this->createItem($category, ['name' => 'Pack 1']);
        $this->createItem($category, ['name' => 'Elden Ring']);

        $response = $this->getJson('/api/items?' . http_build_query(['search' => '_1']));

        $response->assertOk();
        $this->assertSame(['Pack_1'], collect($response->json('data'))->pluck('name')->all());
    }

    public function test_buscar_un_porcentaje_en_admin_items_solo_devuelve_los_que_lo_contienen(): void
    {
        $this->actingAsAdmin();
        $category = $this->createCategory('peliculas', 'Películas');
        $this->createItem($category, ['name' => 'Rebajas 100%']);
        $this->createItem($category, ['name' => 'Matrix']);

        $response = $this->getJson('/api/admin/items?' . http_build_query(['search' => '%']));

        $response->assertOk();
        $this->assertSame(['Rebajas 100%'], collect($response->json('data'))->pluck('name')->all());
    }

    public function test_buscar_un_guion_bajo_en_admin_items_solo_devuelve_los_que_lo_contienen(): void
    {
        $this->actingAsAdmin();
        $category = $this->createCategory('peliculas', 'Películas');
        $this->createItem($category, ['name' => 'snake_case']);
        $this->createItem($category, ['name' => 'Matrix']);

        $response = $this->getJson('/api/admin/items?' . http_build_query(['search' => '_']));

        $response->assertOk();
        $this->assertSame(['snake_case'], collect($response->json('data'))->pluck('name')->all());
    }

    public function test_buscar_una_barra_invertida_en_admin_items_solo_devuelve_los_que_la_contienen(): void
    {
        $this->actingAsAdmin();
        $category = $this->createCategory('peliculas', 'Películas');
        $this->createItem($category, ['name' => 'C:\\Juegos']);
        $this->createItem($category, ['name' => 'Rebajas 100%']);
        $this->createItem($category, ['name' => 'Matrix']);

        $response = $this->getJson('/api/admin/items?' . http_build_query(['search' => '\\']));

        $response->assertOk();
        $this->assertSame(['C:\\Juegos'], collect($response->json('data'))->pluck('name')->all());
    }
}
