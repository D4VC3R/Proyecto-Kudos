<?php

namespace Tests\Feature\Performance;

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\CreatesItems;
use Tests\TestCase;

/**
 * Los listados no lanzan una consulta por ítem: el número de consultas no depende de cuántos ítems salgan.
 */
class ListingQueryCountTest extends TestCase
{
    use CreatesItems, RefreshDatabase;

    /**
     * Número de consultas de una petición, con la caché vacía para que todas las mediciones sigan el mismo camino.
     */
    private function countQueries(callable $request): int
    {
        Cache::flush();
        DB::flushQueryLog();
        DB::enableQueryLog();

        $request();

        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $count;
    }

    private function createReleases(Category $category, int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            $this->createItem($category, ['released_at' => now()->toDateString()]);
        }
    }

    public function test_la_portada_hace_las_mismas_consultas_con_pocos_y_con_muchos_items(): void
    {
        $category = $this->createCategory('peliculas', 'Películas');

        $this->createReleases($category, 2);
        $few = $this->countQueries(function () {
            $this->getJson('/api/home')->assertOk()->assertJsonCount(2, 'data.new_releases');
        });

        $this->createReleases($category, 8);
        $many = $this->countQueries(function () {
            $this->getJson('/api/home')->assertOk()->assertJsonCount(10, 'data.new_releases');
        });

        $this->assertSame($few, $many);
    }

    public function test_el_listado_de_items_hace_las_mismas_consultas_con_pocos_y_con_muchos_items(): void
    {
        $category = $this->createCategory('peliculas', 'Películas');

        $this->createReleases($category, 3);
        $few = $this->countQueries(function () {
            $this->getJson('/api/items?per_page=50')->assertOk()->assertJsonCount(3, 'data');
        });

        $this->createReleases($category, 9);
        $many = $this->countQueries(function () {
            $this->getJson('/api/items?per_page=50')->assertOk()->assertJsonCount(12, 'data');
        });

        $this->assertSame($few, $many);
    }

    public function test_la_categoria_anidada_en_los_items_mantiene_la_clave_items_count(): void
    {
        $this->createItem($this->createCategory('peliculas', 'Películas'));

        $this->getJson('/api/items')
            ->assertOk()
            ->assertJsonStructure(['data' => [['category' => ['items_count']]]])
            ->assertJsonPath('data.0.category.items_count', null);
    }
}
