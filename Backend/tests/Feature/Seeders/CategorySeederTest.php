<?php

namespace Tests\Feature\Seeders;

use App\Catalog\Providers\IgdbGameProvider;
use App\Catalog\Providers\TmdbMovieProvider;
use App\Catalog\Providers\TmdbTvProvider;
use App\Catalog\Providers\WikidataPlaceProvider;
use App\Models\Category;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CategorySeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Http::fake();
    }

    public function test_siembra_solo_las_cuatro_categorias_nuevas_en_orden(): void
    {
        $this->seed(CategorySeeder::class);

        $this->assertSame(
            ['peliculas', 'series', 'videojuegos', 'destinos'],
            Category::query()->orderBy('sort_order')->pluck('slug')->all()
        );
        foreach (['libros', 'albumes-musicales', 'artistas-musicales', 'paises', 'ciudades', 'politicos'] as $legacy) {
            $this->assertDatabaseMissing('categories', ['slug' => $legacy]);
        }
    }

    public function test_peliculas_y_series_quedan_activas_con_su_esquema_de_atributos(): void
    {
        $this->seed(CategorySeeder::class);

        $movies = Category::query()->where('slug', 'peliculas')->firstOrFail();
        $series = Category::query()->where('slug', 'series')->firstOrFail();

        $this->assertTrue($movies->is_active);
        $this->assertTrue($series->is_active);
        // assertEquals: jsonb no conserva el orden de las claves (F1-01).
        $this->assertEquals(TmdbMovieProvider::ATTRIBUTE_SCHEMA, $movies->attribute_schema);
        $this->assertEquals(TmdbTvProvider::ATTRIBUTE_SCHEMA, $series->attribute_schema);
        $this->assertSame('clapperboard', $movies->icon);
        $this->assertSame('tv', $series->icon);
        $this->assertSame(1, $movies->sort_order);
        $this->assertSame(2, $series->sort_order);
    }

    public function test_videojuegos_y_destinos_activas_con_su_esquema(): void
    {
        $this->seed(CategorySeeder::class);

        $games = Category::query()->where('slug', 'videojuegos')->firstOrFail();
        $destinations = Category::query()->where('slug', 'destinos')->firstOrFail();

        $this->assertTrue($games->is_active);
        $this->assertTrue($destinations->is_active);
        // assertEquals: jsonb no conserva el orden de las claves (F1-01).
        $this->assertEquals(IgdbGameProvider::ATTRIBUTE_SCHEMA, $games->attribute_schema);
        $this->assertEquals(WikidataPlaceProvider::ATTRIBUTE_SCHEMA, $destinations->attribute_schema);
        $this->assertSame('gamepad-2', $games->icon);
        $this->assertSame('map-pin', $destinations->icon);
        $this->assertNull($destinations->image);
    }

    public function test_siembra_las_categorias_sin_portada_y_sin_peticiones(): void
    {
        $this->seed(CategorySeeder::class);

        foreach (['peliculas', 'series', 'videojuegos', 'destinos'] as $slug) {
            $this->assertNull(Category::query()->where('slug', $slug)->value('image'));
        }
        Http::assertNothingSent();
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_las_categorias_inactivas_no_salen_en_la_api(): void
    {
        $this->seed(CategorySeeder::class);
        Category::query()->where('slug', 'destinos')->update(['is_active' => false]);

        $response = $this->getJson('/api/categories')->assertOk();

        $this->assertSame(['peliculas', 'series', 'videojuegos'], collect($response->json('data'))->pluck('slug')->all());
        $this->assertSame('clapperboard', $response->json('data.0.icon'));
    }

    public function test_volver_a_sembrar_no_duplica_categorias(): void
    {
        $this->seed(CategorySeeder::class);
        $this->seed(CategorySeeder::class);

        $this->assertSame(4, Category::query()->count());
    }

    public function test_las_descripciones_no_conservan_textos_de_la_version_anterior(): void
    {
        $this->seed(CategorySeeder::class);

        foreach (['peliculas', 'series', 'videojuegos', 'destinos'] as $slug) {
            $description = (string) Category::query()->where('slug', $slug)->value('description');

            $this->assertNotSame('', $description);
            foreach (['compiten', 'ránking', 'luchando', 'batalla'] as $old) {
                $this->assertStringNotContainsString($old, $description);
            }
        }
    }
}
