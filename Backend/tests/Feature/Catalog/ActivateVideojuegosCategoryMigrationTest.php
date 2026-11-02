<?php

namespace Tests\Feature\Catalog;

use App\Catalog\Providers\IgdbGameProvider;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * F4-01: la migración de datos que activa `videojuegos` en las bases ya sembradas.
 */
class ActivateVideojuegosCategoryMigrationTest extends TestCase
{
    use RefreshDatabase;

    private function migration(): object
    {
        return require database_path('migrations/2026_11_04_000000_activate_videojuegos_category.php');
    }

    public function test_activa_videojuegos_con_su_esquema_y_el_down_lo_revierte(): void
    {
        Category::factory()->create(['slug' => 'videojuegos', 'name' => 'Videojuegos', 'is_active' => false, 'attribute_schema' => []]);
        $migration = $this->migration();

        $migration->up();

        $games = Category::query()->where('slug', 'videojuegos')->firstOrFail();
        $this->assertTrue($games->is_active);
        // assertEquals: jsonb no conserva el orden de las claves (F1-01).
        $this->assertEquals(IgdbGameProvider::ATTRIBUTE_SCHEMA, $games->attribute_schema);

        $migration->down();

        $games = Category::query()->where('slug', 'videojuegos')->firstOrFail();
        $this->assertFalse($games->is_active);
        $this->assertSame([], $games->attribute_schema);
    }

    public function test_sin_la_categoria_no_hace_nada(): void
    {
        $this->migration()->up();

        $this->assertSame(0, Category::query()->count());
    }
}
