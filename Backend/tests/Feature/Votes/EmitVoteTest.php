<?php

namespace Tests\Feature\Votes;

use App\Models\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\CreatesItems;
use Tests\TestCase;

class EmitVoteTest extends TestCase
{
    use RefreshDatabase, CreatesItems;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
    }

    public function test_emitir_un_voto_devuelve_201_y_actualiza_el_item(): void
    {
        $user = $this->actingAsUser();
        $item = $this->createItem($this->createCategory());

        $response = $this->postJson('/api/votes', ['item_id' => $item->id, 'type' => 'vote', 'score' => 8]);

        $response->assertCreated()
            ->assertJsonPath('data.type', 'vote')
            ->assertJsonPath('data.item_id', $item->id)
            ->assertJsonPath('meta.was_existing', false)
            ->assertJsonPath('meta.vote_type', 'vote');

        $this->assertDatabaseHas('votes', [
            'user_id' => $user->id,
            'item_id' => $item->id,
            'type'    => 'vote',
            'score'   => 8,
        ]);

        $item = $item->fresh();
        $this->assertSame(1, (int) $item->vote_count);
        $this->assertEquals(8.0, (float) $item->vote_avg);
    }

    public function test_un_voto_duplicado_devuelve_el_existente_y_no_suma(): void
    {
        $user = $this->actingAsUser();
        $item = $this->createItem($this->createCategory());

        $first = $this->postJson('/api/votes', ['item_id' => $item->id, 'type' => 'vote', 'score' => 8]);
        $first->assertCreated();
        $voteId = $first->json('data.id');

        $second = $this->postJson('/api/votes', ['item_id' => $item->id, 'type' => 'vote', 'score' => 3]);

        $second->assertOk()
            ->assertJsonPath('data.id', $voteId)
            ->assertJsonPath('meta.was_existing', true)
            ->assertJsonPath('meta.reason', 'already_voted')
            ->assertJsonPath('meta.kudos_awarded', 0);

        $this->assertDatabaseCount('votes', 1);
        $this->assertDatabaseHas('votes', ['id' => $voteId, 'user_id' => $user->id, 'score' => 8]);

        $item = $item->fresh();
        $this->assertSame(1, (int) $item->vote_count);
        $this->assertEquals(8.0, (float) $item->vote_avg);
    }

    public function test_un_skip_no_cambia_la_media_ni_el_contador(): void
    {
        $user = $this->actingAsUser();
        $item = $this->createItem($this->createCategory(), ['vote_avg' => 7, 'vote_count' => 2]);

        $response = $this->postJson('/api/votes', ['item_id' => $item->id, 'type' => 'skip']);

        $response->assertCreated()
            ->assertJsonPath('meta.vote_type', 'skip')
            ->assertJsonPath('meta.kudos_awarded', 0);

        $item = $item->fresh();
        $this->assertSame(2, (int) $item->vote_count);
        $this->assertEquals(7.0, (float) $item->vote_avg);

        $this->assertDatabaseHas('votes', [
            'user_id' => $user->id,
            'item_id' => $item->id,
            'type'    => 'skip',
            'score'   => null,
        ]);
        $this->assertDatabaseCount('kudos_transactions', 0);
    }

    public function test_no_se_puede_votar_un_item_inactivo(): void
    {
        $this->actingAsUser();
        $item = $this->createItem($this->createCategory(), ['status' => Item::STATUS_INACTIVE]);

        $response = $this->postJson('/api/votes', ['item_id' => $item->id, 'type' => 'vote', 'score' => 8]);

        // Es 403 y no 422: StoreVoteRequest::authorize() llama a VotePolicy::create(), que lo niega
        // antes de que se ejecute la validación. El 422 de withValidator() es inalcanzable por esta ruta.
        $response->assertForbidden()
            ->assertJsonPath('error.code', 'forbidden');

        $this->assertDatabaseCount('votes', 0);
    }

    public function test_un_usuario_baneado_no_puede_votar(): void
    {
        $this->actingAsUser([
            'is_banned'    => true,
            'banned_at'    => now(),
            'banned_until' => null,
            'ban_reason'   => 'Spam',
        ]);
        $item = $this->createItem($this->createCategory());

        $response = $this->postJson('/api/votes', ['item_id' => $item->id, 'type' => 'vote', 'score' => 8]);

        $response->assertForbidden()
            ->assertJsonPath('error.code', 'forbidden')
            ->assertJsonPath('error.details.ban_reason', 'Spam');

        $this->assertDatabaseCount('votes', 0);
    }

    public function test_un_usuario_sin_verificar_no_puede_votar(): void
    {
        $this->actingAsUser(['email_verified_at' => null]);
        $item = $this->createItem($this->createCategory());

        $response = $this->postJson('/api/votes', ['item_id' => $item->id, 'type' => 'vote', 'score' => 8]);

        $response->assertForbidden()
            ->assertJsonPath('error.code', 'forbidden');

        $this->assertDatabaseCount('votes', 0);
    }
}
