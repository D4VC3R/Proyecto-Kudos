<?php

namespace Tests\Feature\Votes;

use App\Models\User;
use App\Models\Vote;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\CreatesItems;
use Tests\TestCase;

/**
 * Las medias se recalculan con AVG sobre los votos reales: los medios puntos no se truncan.
 */
class ExactAverageTest extends TestCase
{
    use RefreshDatabase, CreatesItems;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
    }

    /**
     * Autentica un usuario nuevo y vota; devuelve el id del voto.
     */
    private function voteAsNewUser(string $itemId, float $score): string
    {
        $this->actingAsUser();
        $response = $this->postJson('/api/votes', ['item_id' => $itemId, 'type' => 'vote', 'score' => $score]);
        $response->assertCreated();

        return $response->json('data.id');
    }

    public function test_votar_medios_puntos_da_una_media_exacta(): void
    {
        $item = $this->createItem($this->createCategory());

        $this->voteAsNewUser($item->id, 8.0);
        $this->voteAsNewUser($item->id, 7.5);

        $item = $item->fresh();
        $this->assertEquals(7.75, (float) $item->vote_avg);
        $this->assertSame(2, (int) $item->vote_count);
        $this->assertDatabaseHas('votes', ['score' => 7.5]);
    }

    public function test_cambiar_un_medio_punto_guarda_la_nota_exacta_y_recalcula(): void
    {
        $item = $this->createItem($this->createCategory());

        $this->voteAsNewUser($item->id, 8.0);
        $voteId = $this->voteAsNewUser($item->id, 7.5);

        $response = $this->putJson("/api/votes/{$voteId}", ['score' => 6.5]);

        $response->assertOk();
        $this->assertEquals(6.5, $response->json('data.score'));
        $this->assertDatabaseHas('votes', ['id' => $voteId, 'score' => 6.5]);

        $item = $item->fresh();
        $this->assertEquals(7.25, (float) $item->vote_avg);
        $this->assertSame(2, (int) $item->vote_count);
    }

    public function test_borrar_un_voto_recalcula_la_media_de_forma_exacta(): void
    {
        $item = $this->createItem($this->createCategory());

        $this->voteAsNewUser($item->id, 7.5);
        $this->voteAsNewUser($item->id, 8.0);
        $voteId = $this->voteAsNewUser($item->id, 6.5);

        $item = $item->fresh();
        $this->assertEquals(7.33, (float) $item->vote_avg);
        $this->assertSame(3, (int) $item->vote_count);

        $this->deleteJson("/api/votes/{$voteId}")->assertOk();

        $item = $item->fresh();
        $this->assertEquals(7.75, (float) $item->vote_avg);
        $this->assertSame(2, (int) $item->vote_count);
    }

    public function test_la_media_se_recalcula_desde_los_votos_aunque_el_item_este_desfasado(): void
    {
        // Estadísticas desfasadas a propósito: no coinciden con los votos reales.
        $item = $this->createItem($this->createCategory(), ['vote_avg' => 3, 'vote_count' => 10]);

        $other = User::factory()->create();
        Vote::factory()->byUser($other)->forItem($item)->rated(9.0)->create();

        $this->voteAsNewUser($item->id, 6.5);

        $item = $item->fresh();
        $this->assertSame(2, (int) $item->vote_count);
        $this->assertEquals(7.75, (float) $item->vote_avg);
    }
}
