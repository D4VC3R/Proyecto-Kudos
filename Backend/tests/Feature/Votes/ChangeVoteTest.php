<?php

namespace Tests\Feature\Votes;

use App\Models\User;
use App\Models\Vote;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\CreatesItems;
use Tests\TestCase;

class ChangeVoteTest extends TestCase
{
    use RefreshDatabase, CreatesItems;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
    }

    public function test_cambiar_la_nota_actualiza_el_voto_y_la_media(): void
    {
        $item = $this->createItem($this->createCategory());

        // Voto ajeno de 6 (la media y el contador se fijan a mano).
        $other = User::factory()->create();
        Vote::factory()->byUser($other)->forItem($item)->withScore(6)->create();
        $item->update(['vote_count' => 1, 'vote_avg' => 6]);

        // Voto propio de 8 por HTTP: media 7, contador 2.
        $this->actingAsUser();
        $created = $this->postJson('/api/votes', ['item_id' => $item->id, 'type' => 'vote', 'score' => 8]);
        $created->assertCreated();
        $voteId = $created->json('data.id');

        $this->assertEquals(7.0, (float) $item->fresh()->vote_avg);
        $this->assertSame(2, (int) $item->fresh()->vote_count);

        $response = $this->putJson("/api/votes/{$voteId}", ['score' => 4]);

        $response->assertOk()
            ->assertJsonPath('message', 'Voto actualizado correctamente.');
        $this->assertEquals(4, $response->json('data.score'));

        $this->assertDatabaseHas('votes', ['id' => $voteId, 'score' => 4]);

        $item = $item->fresh();
        $this->assertEquals(5.0, (float) $item->vote_avg);
        $this->assertSame(2, (int) $item->vote_count);
    }

    public function test_no_se_puede_cambiar_el_voto_de_otro_usuario(): void
    {
        $item = $this->createItem($this->createCategory());
        $other = User::factory()->create();
        $vote = Vote::factory()->byUser($other)->forItem($item)->withScore(6)->create();
        $item->update(['vote_count' => 1, 'vote_avg' => 6]);

        $this->actingAsUser();

        $this->putJson("/api/votes/{$vote->id}", ['score' => 2])->assertForbidden();

        $this->assertDatabaseHas('votes', ['id' => $vote->id, 'user_id' => $other->id, 'score' => 6]);
        $item = $item->fresh();
        $this->assertEquals(6.0, (float) $item->vote_avg);
        $this->assertSame(1, (int) $item->vote_count);
    }
}
