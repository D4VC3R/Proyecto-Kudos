<?php

namespace Tests\Feature\Catalog;

use App\Catalog\Providers\WikidataPlaceProvider;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * F4-02: la migración de datos que activa `destinos` en las bases ya sembradas.
 */
class ActivateDestinosCategoryMigrationTest extends TestCase
{
    use RefreshDatabase;

    private function migration(): object
    {
        return require database_path('migrations/2026_11_05_000000_activate_destinos_category.php');
    }

    public function test_activa_destinos_con_su_esquema_y_el_down_lo_revierte(): void
    {
        Category::factory()->create(['slug' => 'destinos', 'name' => 'Destinos', 'is_active' => false, 'attribute_schema' => []]);
        $migration = $this->migration();

        $migration->up();

        $places = Category::query()->where('slug', 'destinos')->firstOrFail();
        $this->assertTrue($places->is_active);
        // assertEquals: jsonb no conserva el orden de las claves (F1-01).
        $this->assertEquals(WikidataPlaceProvider::ATTRIBUTE_SCHEMA, $places->attribute_schema);

        $migration->down();

        $places = Category::query()->where('slug', 'destinos')->firstOrFail();
        $this->assertFalse($places->is_active);
        $this->assertSame([], $places->attribute_schema);
    }

    public function test_sin_la_categoria_no_hace_nada(): void
    {
        $this->migration()->up();

        $this->assertSame(0, Category::query()->count());
    }
}
