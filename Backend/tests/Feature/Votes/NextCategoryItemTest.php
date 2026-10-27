<?php

namespace Tests\Feature\Votes;

use App\Models\Item;
use App\Models\Vote;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\CreatesItems;
use Tests\TestCase;

class NextCategoryItemTest extends TestCase
{
    use RefreshDatabase, CreatesItems;

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
}
