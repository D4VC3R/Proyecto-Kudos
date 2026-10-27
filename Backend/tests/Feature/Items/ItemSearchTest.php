<?php

namespace Tests\Feature\Items;

use App\Models\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\CreatesItems;
use Tests\TestCase;

class ItemSearchTest extends TestCase
{
    use RefreshDatabase, CreatesItems;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
    }

    public function test_la_busqueda_encuentra_por_nombre_parcial_sin_distinguir_mayusculas(): void
    {
        $category = $this->createCategory('videojuegos', 'Videojuegos');
        $this->createItem($category, ['name' => 'The Legend of Zelda']);
        $this->createItem($category, ['name' => 'Zelda: Tears of the Kingdom']);
        $this->createItem($category, ['name' => 'Elden Ring']);
        $this->createItem($category, ['name' => 'Zelda Inactiva', 'status' => Item::STATUS_INACTIVE]);

        $response = $this->getJson('/api/items?search=zELDa');

        $response->assertOk();
        $this->assertEqualsCanonicalizing(
            ['The Legend of Zelda', 'Zelda: Tears of the Kingdom'],
            collect($response->json('data'))->pluck('name')->all()
        );

        $response = $this->getJson('/api/items?search=legend');

        $response->assertOk();
        $this->assertSame(['The Legend of Zelda'], collect($response->json('data'))->pluck('name')->all());
    }

    public function test_buscar_mat_sin_categoria_encuentra_matrix_y_los_items_de_otras_categorias(): void
    {
        $peliculas = $this->createCategory('peliculas', 'Películas');
        $series = $this->createCategory('series', 'Series');
        $libros = $this->createCategory('libros', 'Libros');
        $this->createItem($peliculas, ['name' => 'Matrix']);
        $this->createItem($peliculas, ['name' => 'Titanic']);
        $this->createItem($series, ['name' => 'Matador']);
        $this->createItem($libros, ['name' => 'Matilda']);

        $response = $this->getJson('/api/items?search=mat&per_page=10');

        $response->assertOk();
        $data = collect($response->json('data'));
        $this->assertEqualsCanonicalizing(['Matrix', 'Matador', 'Matilda'], $data->pluck('name')->all());
        $this->assertEquals(
            ['Matrix' => 'peliculas', 'Matador' => 'series', 'Matilda' => 'libros'],
            $data->mapWithKeys(fn ($item) => [$item['name'] => $item['category']['slug']])->all()
        );
    }

    public function test_la_busqueda_global_respeta_per_page(): void
    {
        $peliculas = $this->createCategory('peliculas', 'Películas');
        $series = $this->createCategory('series', 'Series');
        $this->createItem($peliculas, ['name' => 'Matrix']);
        $this->createItem($series, ['name' => 'Matador']);
        $this->createItem($series, ['name' => 'Matilda']);

        $response = $this->getJson('/api/items?search=mat&per_page=2');

        $response->assertOk();
        $this->assertCount(2, $response->json('data'));
        $response->assertJsonPath('meta.per_page', 2);
        $response->assertJsonPath('meta.has_more', true);
    }

    public function test_la_busqueda_global_carga_la_categoria_con_una_sola_consulta(): void
    {
        $this->createItem($this->createCategory('peliculas', 'Películas'), ['name' => 'Matrix']);
        $this->createItem($this->createCategory('series', 'Series'), ['name' => 'Matador']);
        $this->createItem($this->createCategory('libros', 'Libros'), ['name' => 'Matilda']);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $response = $this->getJson('/api/items?search=mat&per_page=10');

        $response->assertOk();
        $this->assertCount(3, $response->json('data'));

        $categoryQueries = collect(DB::getQueryLog())
            ->filter(fn ($q) => str_contains($q['query'], 'from "categories"'));
        $this->assertCount(1, $categoryQueries);
    }

    public function test_una_busqueda_de_un_caracter_da_422(): void
    {
        $this->createItem($this->createCategory('peliculas', 'Películas'), ['name' => 'Matrix']);

        $response = $this->getJson('/api/items?search=m');

        $response->assertStatus(422);
        $response->assertJsonPath('error.code', 'validation_error');
        $response->assertJsonStructure(['error' => ['details' => ['search']]]);
    }

    public function test_una_busqueda_de_dos_caracteres_es_valida(): void
    {
        $this->createItem($this->createCategory('peliculas', 'Películas'), ['name' => 'Matrix']);

        $response = $this->getJson('/api/items?search=ma');

        $response->assertOk();
        $this->assertSame(['Matrix'], collect($response->json('data'))->pluck('name')->all());
    }
}
