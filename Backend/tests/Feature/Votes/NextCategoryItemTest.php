<?php

namespace Tests\Feature\Votes;

use App\Models\Category;
use App\Models\Item;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\Concerns\CreatesItems;
use Tests\TestCase;

class NextCategoryItemTest extends TestCase
{
    use CreatesItems, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
    }

    public function test_la_cola_no_devuelve_items_votados_ni_inactivos(): void
    {
        $user = $this->actingAsUser();
        $peliculas = $this->createCategory('peliculas', 'Películas');
        $series = $this->createCategory('series', 'Series');

        $eligible = [
            $this->createItem($peliculas, ['name' => 'Elegible 1']),
            $this->createItem($peliculas, ['name' => 'Elegible 2']),
            $this->createItem($peliculas, ['name' => 'Elegible 3']),
        ];
        $voted = $this->createItem($peliculas, ['name' => 'Votado']);
        $inactive = $this->createItem($peliculas, ['name' => 'Inactivo', 'status' => Item::STATUS_INACTIVE]);
        $otherCategory = $this->createItem($series, ['name' => 'De otra categoría']);

        Vote::factory()->byUser($user)->forItem($voted)->withScore(7)->create();

        Cache::flush();

        $returnedIds = [];
        foreach (range(1, 3) as $attempt) {
            $response = $this->getJson('/api/categories/peliculas/next-item');
            $response->assertOk();
            $returnedIds[] = $response->json('data.id');

            if ($attempt === 1) {
                $response->assertJsonPath('meta.remaining', 2);
            }
        }

        $eligibleIds = array_map(fn (Item $item) => $item->id, $eligible);
        $this->assertEqualsCanonicalizing($eligibleIds, $returnedIds);
        $this->assertNotContains($voted->id, $returnedIds);
        $this->assertNotContains($inactive->id, $returnedIds);
        $this->assertNotContains($otherCategory->id, $returnedIds);
    }

    public function test_la_cola_devuelve_204_cuando_no_quedan_items_por_votar(): void
    {
        $user = $this->actingAsUser();
        $peliculas = $this->createCategory('peliculas', 'Películas');

        $eligible = $this->createItem($peliculas, ['name' => 'Elegible']);
        $voted = $this->createItem($peliculas, ['name' => 'Votado']);
        $this->createItem($peliculas, ['name' => 'Inactivo', 'status' => Item::STATUS_INACTIVE]);

        Vote::factory()->byUser($user)->forItem($voted)->withScore(7)->create();

        Cache::flush();

        $this->getJson('/api/categories/peliculas/next-item')
            ->assertOk()
            ->assertJsonPath('data.id', $eligible->id)
            ->assertJsonPath('meta.remaining', 0);

        $this->postJson('/api/votes', ['item_id' => $eligible->id, 'type' => 'vote', 'score' => 7])
            ->assertCreated();

        // La ficha dice "null o vacío", pero el código responde 204 No Content (sin cuerpo).
        $this->getJson('/api/categories/peliculas/next-item')->assertNoContent();
    }

    public function test_la_cola_no_ofrece_un_item_del_lote_votado_desde_la_ficha(): void
    {
        [$votedId, $otherId] = $this->cachedQueueAfterFirstItem();

        $this->postJson('/api/votes', ['item_id' => $votedId, 'type' => 'vote', 'score' => 7])
            ->assertCreated();

        $this->getJson('/api/categories/peliculas/next-item')
            ->assertOk()
            ->assertJsonPath('data.id', $otherId)
            ->assertJsonPath('meta.remaining', 0);
    }

    public function test_la_cola_no_ofrece_un_item_del_lote_omitido_desde_la_ficha(): void
    {
        [$votedId, $otherId] = $this->cachedQueueAfterFirstItem();

        $this->postJson('/api/votes', ['item_id' => $votedId, 'type' => 'skip'])
            ->assertCreated();

        $this->getJson('/api/categories/peliculas/next-item')
            ->assertOk()
            ->assertJsonPath('data.id', $otherId)
            ->assertJsonPath('meta.remaining', 0);
    }

    public function test_la_cola_no_ofrece_un_item_del_lote_marcado_como_want(): void
    {
        [$votedId, $otherId] = $this->cachedQueueAfterFirstItem();

        $this->postJson('/api/votes', ['item_id' => $votedId, 'type' => 'want'])
            ->assertCreated();

        $this->getJson('/api/categories/peliculas/next-item')
            ->assertOk()
            ->assertJsonPath('data.id', $otherId)
            ->assertJsonPath('meta.remaining', 0);
    }

    public function test_la_cola_no_ofrece_un_item_del_lote_que_pasa_a_inactivo(): void
    {
        [$changedId, $otherId] = $this->cachedQueueAfterFirstItem();

        Item::findOrFail($changedId)->update(['status' => Item::STATUS_INACTIVE]);

        $this->getJson('/api/categories/peliculas/next-item')
            ->assertOk()
            ->assertJsonPath('data.id', $otherId)
            ->assertJsonPath('meta.remaining', 0);
    }

    public function test_la_cola_no_ofrece_un_item_del_lote_que_pasa_a_ficha_pobre(): void
    {
        [$changedId, $otherId] = $this->cachedQueueAfterFirstItem();

        $item = Item::findOrFail($changedId);
        $item->update(['images' => [], 'description' => 'Item importado.']);

        $this->assertLessThan((int) config('catalog.min_quality_for_discovery'), $item->fresh()->quality_score);

        $this->getJson('/api/categories/peliculas/next-item')
            ->assertOk()
            ->assertJsonPath('data.id', $otherId)
            ->assertJsonPath('meta.remaining', 0);
    }

    public function test_el_lote_ponderado_no_incluye_votados_inactivos_ni_de_baja_calidad(): void
    {
        $user = $this->actingAsUser();
        $peliculas = $this->createCategory('peliculas', 'Películas');
        $series = $this->createCategory('series', 'Series');

        $eligible = [
            $this->createItem($peliculas, ['name' => 'Elegible 1', 'popularity' => 10]),
            $this->createItem($peliculas, ['name' => 'Elegible 2', 'popularity' => 20]),
            $this->createItem($peliculas, ['name' => 'Elegible 3', 'popularity' => 30]),
        ];

        // Todos con popularidad alta: quedarían arriba del lote si se colaran
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

        Cache::flush();

        $batch = $this->batchOrder($user, $peliculas);

        $this->assertEqualsCanonicalizing(array_map(fn (Item $item) => $item->id, $eligible), $batch);
        foreach ([$voted, $wanted, $notInterested, $inactive, $poor, $deleted, $otherCategory] as $excluded) {
            $this->assertNotContains($excluded->id, $batch);
        }
    }

    public function test_con_semilla_fija_el_orden_favorece_la_popularidad(): void
    {
        $user = $this->actingAsUser();
        $peliculas = $this->createCategory('peliculas', 'Películas');

        $ids = [];
        foreach (range(1, 20) as $n) {
            // Misma fecha antigua (recencia 0) y calidad por defecto: solo cambia la popularidad
            $ids[$n] = $this->createItem($peliculas, [
                'name' => "Película {$n}",
                'popularity' => $n * 10,
                'released_at' => '2000-01-01',
            ])->id;
        }

        // Después de crear los datos: Faker también consume mt_rand()
        Cache::flush();
        mt_srand(42);
        $batch = $this->batchOrder($user, $peliculas);
        mt_srand();

        $this->assertCount(20, $batch);

        $position = fn (int $n) => array_search($ids[$n], $batch, true);
        $topAverage = array_sum(array_map($position, range(16, 20))) / 5;
        $bottomAverage = array_sum(array_map($position, range(1, 5))) / 5;

        $this->assertLessThan($bottomAverage, $topAverage);
        $this->assertLessThan($position(1), $position(20));
    }

    public function test_con_la_misma_semilla_el_lote_sale_en_el_mismo_orden(): void
    {
        $user = $this->actingAsUser();
        $peliculas = $this->createCategory('peliculas', 'Películas');

        foreach (range(1, 15) as $n) {
            $this->createItem($peliculas, ['name' => "Película {$n}", 'popularity' => $n * 7 % 11 + $n]);
        }

        $orders = [];
        foreach (range(1, 2) as $attempt) {
            Cache::flush();
            mt_srand(7);
            $orders[] = $this->batchOrder($user, $peliculas);
        }
        mt_srand();

        $this->assertCount(15, $orders[0]);
        $this->assertSame($orders[0], $orders[1]);
    }

    public function test_sin_azar_un_estreno_reciente_va_antes_que_uno_antiguo_igual_de_popular(): void
    {
        config(['catalog.queue_weights.rand' => 0]);

        $user = $this->actingAsUser();
        $peliculas = $this->createCategory('peliculas', 'Películas');

        $antiguo = $this->createItem($peliculas, ['name' => 'Antigua', 'popularity' => 50, 'released_at' => '2000-01-01']);
        $reciente = $this->createItem($peliculas, ['name' => 'Estreno', 'popularity' => 50, 'released_at' => now()->subDays(10)->toDateString()]);

        Cache::flush();

        $this->assertSame([$reciente->id, $antiguo->id], $this->batchOrder($user, $peliculas));
    }

    public function test_los_candidatos_son_los_mas_populares_mas_unos_al_azar(): void
    {
        config(['catalog.queue_candidates.popular' => 3, 'catalog.queue_candidates.random' => 1]);

        $user = $this->actingAsUser();
        $peliculas = $this->createCategory('peliculas', 'Películas');

        $ids = [];
        foreach (range(1, 6) as $n) {
            $ids[$n] = $this->createItem($peliculas, ['name' => "Película {$n}", 'popularity' => $n * 10])->id;
        }

        Cache::flush();

        $batch = $this->batchOrder($user, $peliculas);

        $this->assertContains(count($batch), [3, 4]);
        $this->assertEmpty(array_diff([$ids[4], $ids[5], $ids[6]], $batch));
    }

    public function test_genera_el_lote_con_5000_items_en_una_sola_consulta(): void
    {
        $this->actingAsUser();
        $peliculas = $this->createCategory('peliculas', 'Películas');

        $now = now();
        $rows = [];
        foreach (range(1, 5000) as $n) {
            $rows[] = [
                'id' => (string) Str::uuid(),
                'name' => "Película {$n}",
                'slug' => "pelicula-rendimiento-{$n}",
                'description' => 'Descripción de prueba lo bastante larga para que la ficha cuente como completa.',
                'status' => Item::STATUS_ACTIVE,
                'category_id' => $peliculas->id,
                'quality_score' => 80,
                'popularity' => ($n * 37) % 1000 / 10,
                'released_at' => $now->copy()->subDays($n)->toDateString(),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('items')->insert($chunk);
        }

        Cache::flush();
        DB::enableQueryLog();
        $start = microtime(true);

        $this->getJson('/api/categories/peliculas/next-item')
            ->assertOk()
            ->assertJsonPath('meta.remaining', 49);

        $elapsedMs = (microtime(true) - $start) * 1000;
        $queries = collect(DB::getQueryLog());

        $this->assertSame(1, $queries->filter(fn ($q) => str_contains($q['query'], ' union '))->count());
        $this->assertSame(1, $queries->filter(fn ($q) => str_contains($q['query'], '"popularity"'))->count());
        $this->assertLessThan(2000, $elapsedMs);
    }

    /**
     * Crea tres ítems elegibles, pide el primero de la cola y devuelve el lote que queda en la caché (2 IDs, en orden).
     */
    private function cachedQueueAfterFirstItem(): array
    {
        $user = $this->actingAsUser();
        $peliculas = $this->createCategory('peliculas', 'Películas');

        foreach (range(1, 3) as $n) {
            $this->createItem($peliculas, ['name' => "Elegible {$n}"]);
        }

        Cache::flush();

        $this->getJson('/api/categories/peliculas/next-item')
            ->assertOk()
            ->assertJsonPath('meta.remaining', 2);

        $queue = Cache::get("next-item-queue:{$user->id}:{$peliculas->id}");
        $this->assertCount(2, $queue);

        return $queue;
    }

    /**
     * Pide el primer ítem de la cola y devuelve el lote completo en orden: el ítem servido más lo que queda en la caché.
     */
    private function batchOrder(User $user, Category $category): array
    {
        $first = $this->getJson("/api/categories/{$category->slug}/next-item")
            ->assertOk()
            ->json('data.id');

        return array_merge([$first], Cache::get("next-item-queue:{$user->id}:{$category->id}", []));
    }
}
