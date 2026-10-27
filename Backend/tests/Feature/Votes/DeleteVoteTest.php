<?php

namespace Tests\Feature\Votes;

use App\Models\User;
use App\Models\Vote;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\CreatesItems;
use Tests\TestCase;

class DeleteVoteTest extends TestCase
{
    use RefreshDatabase, CreatesItems;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
    }

    public function test_borrar_un_voto_lo_elimina_y_recalcula_la_media(): void
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

        $this->deleteJson("/api/votes/{$voteId}")
            ->assertOk()
            ->assertJsonPath('message', 'Voto eliminado correctamente.');

        $this->assertDatabaseMissing('votes', ['id' => $voteId]);

        $item = $item->fresh();
        $this->assertSame(1, (int) $item->vote_count);
        $this->assertEquals(6.0, (float) $item->vote_avg);
    }

    public function test_borrar_el_unico_voto_deja_el_item_sin_votos(): void
    {
        $item = $this->createItem($this->createCategory());

        $this->actingAsUser();
        $created = $this->postJson('/api/votes', ['item_id' => $item->id, 'type' => 'vote', 'score' => 8]);
        $created->assertCreated();
        $voteId = $created->json('data.id');

        $this->deleteJson("/api/votes/{$voteId}")->assertOk();

        $this->assertDatabaseMissing('votes', ['id' => $voteId]);

        $item = $item->fresh();
        $this->assertSame(0, (int) $item->vote_count);
        $this->assertEquals(0.0, (float) $item->vote_avg);
    }
}
