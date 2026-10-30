<?php

namespace Tests\Feature\Seeders;

use App\Catalog\Providers\TmdbMovieProvider;
use App\Catalog\Providers\TmdbTvProvider;
use App\Models\Category;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\FakesRemoteImages;
use Tests\TestCase;

class CategorySeederTest extends TestCase
{
    use FakesRemoteImages, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Storage::fake('local');
        $this->fakeHostResolver();
        $this->fakeHttp();
    }

    /** Falsea las portadas que descarga CategorySeeder (un PNG nuevo por petición), más las reglas extra. */
    private function fakeHttp(array $extra = []): void
    {
        $png = $this->fakePng();
        $cover = fn () => Http::response($png, 200, ['Content-Type' => 'image/png']);

        Http::fake($extra + [
            'sm.ign.com/*' => $cover,               // videojuegos
            'm.media-amazon.com/*' => $cover,       // peliculas
            'images.bauerhosting.com/*' => $cover,  // series
        ]);
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

    public function test_videojuegos_y_destinos_quedan_inactivas_hasta_la_f4(): void
    {
        $this->seed(CategorySeeder::class);

        $games = Category::query()->where('slug', 'videojuegos')->firstOrFail();
        $destinations = Category::query()->where('slug', 'destinos')->firstOrFail();

        $this->assertFalse($games->is_active);
        $this->assertFalse($destinations->is_active);
        $this->assertSame([], $games->attribute_schema);
        $this->assertSame([], $destinations->attribute_schema);
        $this->assertSame('gamepad-2', $games->icon);
        $this->assertSame('map-pin', $destinations->icon);
        $this->assertNull($destinations->image);
    }

    public function test_guarda_las_portadas_en_el_disco_public(): void
    {
        $this->seed(CategorySeeder::class);

        foreach (['peliculas', 'series', 'videojuegos'] as $slug) {
            $image = Category::query()->where('slug', $slug)->value('image');

            $this->assertStringStartsWith("categories/{$slug}/seed/cover-", $image);
            Storage::disk('public')->assertExists($image);
        }
    }

    public function test_las_categorias_inactivas_no_salen_en_la_api(): void
    {
        $this->seed(CategorySeeder::class);

        $response = $this->getJson('/api/categories')->assertOk();

        $this->assertSame(['peliculas', 'series'], collect($response->json('data'))->pluck('slug')->all());
        $this->assertSame('clapperboard', $response->json('data.0.icon'));
    }

    public function test_volver_a_sembrar_no_duplica_categorias(): void
    {
        $this->seed(CategorySeeder::class);
        $this->seed(CategorySeeder::class);

        $this->assertSame(4, Category::query()->count());
    }
}
