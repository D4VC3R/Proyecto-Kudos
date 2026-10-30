<?php

namespace Tests\Feature\Onboarding;

use App\Models\Item;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\CreatesItems;
use Tests\TestCase;

/**
 * Ítems del speed-rating del onboarding (F2-07): GET /api/onboarding/items?categories=a,b.
 */
class OnboardingItemsTest extends TestCase
{
    use RefreshDatabase, CreatesItems;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
    }

    /**
     * Crea $count ítems en la categoría con popularidades base + 1..$count.
     *
     * @return array<int, Item>
     */
    private function createItems($category, int $count, int $base = 0): array
    {
        $items = [];

        for ($i = 1; $i <= $count; $i++) {
            $items[] = $this->createItem($category, [
                'name' => "{$category->slug} {$i}",
                'popularity' => $base + $i,
            ]);
        }

        return $items;
    }

    public function test_devuelve_como_mucho_diez_items_por_popularidad_descendente(): void
    {
        $this->actingAsUser();
        $peliculas = $this->createCategory('peliculas', 'Películas');
        $items = $this->createItems($peliculas, 12);

        $response = $this->getJson('/api/onboarding/items?categories=peliculas')->assertOk();

        // Los 10 más populares, de mayor a menor
        $expected = collect($items)->sortByDesc('popularity')->take(10)->pluck('id')->values()->all();
        $this->assertCount(10, $response->json('data'));
        $this->assertSame($expected, collect($response->json('data'))->pluck('id')->all());
    }

    public function test_reparte_los_items_por_turnos_entre_las_categorias(): void
    {
        $this->actingAsUser();
        $peliculas = $this->createCategory('peliculas', 'Películas');
        $series = $this->createCategory('series', 'Series');
        $peliculas->update(['sort_order' => 1]);
        $series->update(['sort_order' => 2]);
        $moviesItems = $this->createItems($peliculas, 8);
        $seriesItems = $this->createItems($series, 8);

        // El orden de los slugs en la petición no importa: manda el sort_order
        $response = $this->getJson('/api/onboarding/items?categories=series,peliculas')->assertOk();

        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertCount(10, $ids);

        $topMovies = collect($moviesItems)->sortByDesc('popularity')->take(5)->pluck('id')->values()->all();
        $topSeries = collect($seriesItems)->sortByDesc('popularity')->take(5)->pluck('id')->values()->all();

        $evenPositions = array_values(array_filter($ids, fn ($id, $pos) => $pos % 2 === 0, ARRAY_FILTER_USE_BOTH));
        $oddPositions = array_values(array_filter($ids, fn ($id, $pos) => $pos % 2 === 1, ARRAY_FILTER_USE_BOTH));

        $this->assertSame($topMovies, $evenPositions);
        $this->assertSame($topSeries, $oddPositions);
    }

    public function test_si_una_categoria_tiene_pocos_items_las_demas_completan_el_cupo(): void
    {
        $this->actingAsUser();
        $peliculas = $this->createCategory('peliculas', 'Películas');
        $series = $this->createCategory('series', 'Series');
        $peliculas->update(['sort_order' => 1]);
        $series->update(['sort_order' => 2]);
        $moviesItems = $this->createItems($peliculas, 2);
        $this->createItems($series, 10);

        $response = $this->getJson('/api/onboarding/items?categories=peliculas,series')->assertOk();

        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertCount(10, $ids);
        $this->assertCount(2, array_intersect($ids, collect($moviesItems)->pluck('id')->all()));
        $this->assertSame(10, count(array_unique($ids)));
    }

    public function test_excluye_votados_inactivos_de_baja_calidad_borrados_y_de_otras_categorias(): void
    {
        $user = $this->actingAsUser();
        $peliculas = $this->createCategory('peliculas', 'Películas');
        $series = $this->createCategory('series', 'Series');

        $eligible = $this->createItems($peliculas, 3);

        // Todos con popularidad alta: saldrían los primeros si se colaran
        $voted = $this->createItem($peliculas, ['name' => 'Votado', 'popularity' => 1000]);
        $wanted = $this->createItem($peliculas, ['name' => 'Quiero verlo', 'popularity' => 1000]);
        $notInterested = $this->createItem($peliculas, ['name' => 'No me interesa', 'popularity' => 1000]);
        $inactive = $this->createItem($peliculas, ['name' => 'Inactivo', 'popularity' => 1000, 'status' => Item::STATUS_INACTIVE]);
        $poor = $this->createItem($peliculas, ['name' => 'Ficha pobre', 'popularity' => 1000, 'images' => [], 'description' => 'Item importado.']);
        $deleted = $this->createItem($peliculas, ['name' => 'Borrado', 'popularity' => 1000]);
        $otherCategory = $this->createItem($series, ['name' => 'De otra categoría', 'popularity' => 1000]);

        Vote::factory()->byUser($user)->forItem($voted)->withScore(7)->create();
        Vote::factory()->byUser($user)->forItem($wanted)->create(['type' => Vote::TYPE_WANT, 'score' => null]);
        Vote::factory()->byUser($user)->forItem($notInterested)->create(['type' => Vote::TYPE_NOT_INTERESTED, 'score' => null]);
        $this->assertLessThan((int) config('catalog.min_quality_for_discovery'), $poor->fresh()->quality_score);
        $deleted->delete();

        $ids = $this->getJson('/api/onboarding/items?categories=peliculas')->assertOk()->json('data.*.id');

        $this->assertEqualsCanonicalizing(collect($eligible)->pluck('id')->all(), $ids);
        foreach ([$voted, $wanted, $notInterested, $inactive, $poor, $deleted, $otherCategory] as $excluded) {
            $this->assertNotContains($excluded->id, $ids);
        }
    }

    public function test_el_voto_de_otro_usuario_no_excluye_el_item(): void
    {
        $this->actingAsUser();
        $peliculas = $this->createCategory('peliculas', 'Películas');
        $item = $this->createItem($peliculas, ['popularity' => 5]);
        $other = User::factory()->create();
        Vote::factory()->byUser($other)->forItem($item)->withScore(8)->create();

        $this->getJson('/api/onboarding/items?categories=peliculas')
            ->assertOk()
            ->assertJsonPath('data.0.id', $item->id);
    }

    public function test_incluye_los_datos_de_tarjeta_y_can_vote(): void
    {
        $this->actingAsUser();
        $peliculas = $this->createCategory('peliculas', 'Películas');
        $this->createItem($peliculas, ['name' => 'Una película', 'popularity' => 5, 'released_at' => '2020-05-01']);

        $this->getJson('/api/onboarding/items?categories=peliculas')
            ->assertOk()
            ->assertJsonStructure(['data' => [['id', 'name', 'images', 'released_at', 'category' => ['slug']]]])
            ->assertJsonPath('data.0.name', 'Una película')
            ->assertJsonPath('data.0.released_at', '2020-05-01')
            ->assertJsonPath('data.0.category.slug', 'peliculas')
            ->assertJsonPath('data.0.can_vote', true);
    }

    public function test_respeta_el_limite_configurado(): void
    {
        $this->actingAsUser();
        $peliculas = $this->createCategory('peliculas', 'Películas');
        $this->createItems($peliculas, 8);
        config(['catalog.onboarding_items' => 3]);

        $response = $this->getJson('/api/onboarding/items?categories=peliculas')->assertOk();

        $this->assertCount(3, $response->json('data'));
    }

    public function test_una_categoria_inexistente_o_inactiva_da_422(): void
    {
        $this->actingAsUser();
        $this->createCategory('peliculas', 'Películas');
        $series = $this->createCategory('series', 'Series');
        $series->update(['is_active' => false]);

        $this->getJson('/api/onboarding/items?categories=peliculas,no-existe')
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'validation_error');

        $this->getJson('/api/onboarding/items?categories=peliculas,series')
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'validation_error');
    }

    public function test_sin_categorias_da_422(): void
    {
        $this->actingAsUser();
        $this->createCategory('peliculas', 'Películas');

        $this->getJson('/api/onboarding/items')
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'validation_error');

        $this->getJson('/api/onboarding/items?categories=')
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'validation_error');
    }

    public function test_requiere_sesion(): void
    {
        $this->createCategory('peliculas', 'Películas');

        $this->getJson('/api/onboarding/items?categories=peliculas')->assertStatus(401);
    }
}
