<?php

namespace Tests\Feature\Votes;

use App\Models\Item;
use App\Models\KudosTransaction;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\CreatesItems;
use Tests\TestCase;

/**
 * Transiciones entre tipos de voto por PUT /api/votes/{id} (F2-02).
 */
class VoteTransitionsTest extends TestCase
{
    use RefreshDatabase, CreatesItems;

    private const INVALID_CONVERSION = 'Una valoración no se puede convertir en "me interesa" ni en "no me interesa": bórrala y vuelve a crearla.';

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();

        // Congela el tiempo: el contador diario de kudos caduca con endOfDay().
        $this->travelTo(Carbon::parse('2026-01-15 12:00:00'));
    }

    /**
     * Ítem con un voto ajeno de 6 (contador 1, media 6) para comprobar el recálculo.
     */
    private function itemWithOtherVote(): Item
    {
        $item = $this->createItem($this->createCategory());
        $other = User::factory()->create();
        Vote::factory()->byUser($other)->forItem($item)->withScore(6)->create();
        $item->update(['vote_count' => 1, 'vote_avg' => 6]);

        return $item;
    }

    /**
     * Ítem con medias falsas, para comprobar que no se recalculan.
     */
    private function itemWithFakeStats(): Item
    {
        return $this->createItem($this->createCategory(), ['vote_avg' => 7, 'vote_count' => 2]);
    }

    /**
     * Emite un voto del tipo indicado por HTTP y devuelve su id.
     */
    private function emit(Item $item, string $type, ?float $score = null): string
    {
        $payload = ['item_id' => $item->id, 'type' => $type];

        if ($score !== null) {
            $payload['score'] = $score;
        }

        $response = $this->postJson('/api/votes', $payload);
        $response->assertCreated();

        return $response->json('data.id');
    }

    private function assertNoKudosTransactions(): void
    {
        $this->assertSame(0, KudosTransaction::count());
    }

    public function test_un_want_pasa_a_valoracion_recalcula_y_da_kudos(): void
    {
        $reward = config('kudos.voting.reward');
        $item = $this->itemWithOtherVote();
        $user = $this->actingAsUser();
        $voteId = $this->emit($item, 'want');

        $this->putJson("/api/votes/{$voteId}", ['type' => 'vote', 'score' => 8])
            ->assertOk()
            ->assertJsonPath('message', 'Voto actualizado correctamente.')
            ->assertJsonPath('data.type', 'vote')
            ->assertJsonPath('data.score', 8)
            ->assertJsonPath('meta.kudos_awarded', $reward)
            ->assertJsonPath('meta.total_kudos', $reward)
            ->assertJsonPath('meta.vote_type', 'vote');

        $this->assertDatabaseHas('votes', ['id' => $voteId, 'type' => 'vote', 'score' => 8]);

        $item = $item->fresh();
        $this->assertSame(2, (int) $item->vote_count);
        $this->assertEquals(7.0, (float) $item->vote_avg);

        $this->assertDatabaseHas('kudos_transactions', [
            'user_id'    => $user->id,
            'reason'     => 'vote_first_time_item',
            'action_key' => "vote_first_time_item:{$user->id}:{$item->id}",
        ]);
    }

    public function test_un_not_interested_pasa_a_valoracion_recalcula_y_da_kudos(): void
    {
        $reward = config('kudos.voting.reward');
        $item = $this->itemWithOtherVote();
        $user = $this->actingAsUser();
        $voteId = $this->emit($item, 'not_interested');

        $this->putJson("/api/votes/{$voteId}", ['type' => 'vote', 'score' => 8])
            ->assertOk()
            ->assertJsonPath('data.type', 'vote')
            ->assertJsonPath('data.score', 8)
            ->assertJsonPath('meta.kudos_awarded', $reward)
            ->assertJsonPath('meta.total_kudos', $reward)
            ->assertJsonPath('meta.vote_type', 'vote');

        $item = $item->fresh();
        $this->assertSame(2, (int) $item->vote_count);
        $this->assertEquals(7.0, (float) $item->vote_avg);

        $this->assertDatabaseHas('kudos_transactions', [
            'user_id'    => $user->id,
            'reason'     => 'vote_first_time_item',
            'action_key' => "vote_first_time_item:{$user->id}:{$item->id}",
        ]);
    }

    public function test_un_skip_heredado_pasa_a_valoracion(): void
    {
        $reward = config('kudos.voting.reward');
        $item = $this->itemWithOtherVote();
        $user = $this->actingAsUser();
        $vote = Vote::factory()->byUser($user)->forItem($item)->create(['type' => 'skip', 'score' => null]);

        $this->putJson("/api/votes/{$vote->id}", ['type' => 'vote', 'score' => 7.5])
            ->assertOk()
            ->assertJsonPath('data.type', 'vote')
            ->assertJsonPath('data.score', 7.5)
            ->assertJsonPath('meta.kudos_awarded', $reward);

        $this->assertDatabaseHas('votes', ['id' => $vote->id, 'type' => 'vote', 'score' => 7.5]);

        $item = $item->fresh();
        $this->assertSame(2, (int) $item->vote_count);
        $this->assertEquals(6.75, (float) $item->vote_avg);
    }

    public function test_los_kudos_de_valorar_se_dan_una_sola_vez(): void
    {
        $reward = config('kudos.voting.reward');
        $user = $this->actingAsUser();
        $item = $this->createItem($this->createCategory());

        $voteId = $this->emit($item, 'vote', 8);
        $this->assertSame($reward, (int) $user->fresh()->total_kudos);

        $this->deleteJson("/api/votes/{$voteId}")->assertOk();

        $wantId = $this->emit($item, 'want');

        $this->putJson("/api/votes/{$wantId}", ['type' => 'vote', 'score' => 6])
            ->assertOk()
            ->assertJsonPath('meta.kudos_awarded', 0)
            ->assertJsonPath('meta.total_kudos', $reward);

        $this->assertSame($reward, (int) $user->fresh()->total_kudos);
        $this->assertSame(1, KudosTransaction::where('user_id', $user->id)
            ->where('action_key', "vote_first_time_item:{$user->id}:{$item->id}")
            ->count());
        $this->assertSame(1, KudosTransaction::where('user_id', $user->id)->count());
    }

    public function test_un_want_pasa_a_not_interested_sin_efectos(): void
    {
        $user = $this->actingAsUser();
        $item = $this->itemWithFakeStats();
        $voteId = $this->emit($item, 'want');

        $this->putJson("/api/votes/{$voteId}", ['type' => 'not_interested'])
            ->assertOk()
            ->assertJsonPath('message', 'Ítem marcado como "no me interesa".')
            ->assertJsonPath('data.type', 'not_interested')
            ->assertJsonPath('data.score', null)
            ->assertJsonPath('meta.kudos_awarded', 0)
            ->assertJsonPath('meta.vote_type', 'not_interested');

        $this->assertDatabaseHas('votes', ['id' => $voteId, 'type' => 'not_interested', 'score' => null]);

        $item = $item->fresh();
        $this->assertEquals(7.0, (float) $item->vote_avg);
        $this->assertSame(2, (int) $item->vote_count);
        $this->assertNoKudosTransactions();
        $this->assertSame(0, (int) $user->fresh()->total_kudos);
    }

    public function test_un_not_interested_pasa_a_want_sin_efectos(): void
    {
        $user = $this->actingAsUser();
        $item = $this->itemWithFakeStats();
        $voteId = $this->emit($item, 'not_interested');

        $this->putJson("/api/votes/{$voteId}", ['type' => 'want'])
            ->assertOk()
            ->assertJsonPath('message', 'Ítem añadido a tu wishlist.')
            ->assertJsonPath('data.type', 'want')
            ->assertJsonPath('data.score', null)
            ->assertJsonPath('meta.kudos_awarded', 0)
            ->assertJsonPath('meta.vote_type', 'want');

        $this->assertDatabaseHas('votes', ['id' => $voteId, 'type' => 'want', 'score' => null]);

        $item = $item->fresh();
        $this->assertEquals(7.0, (float) $item->vote_avg);
        $this->assertSame(2, (int) $item->vote_count);
        $this->assertNoKudosTransactions();
        $this->assertSame(0, (int) $user->fresh()->total_kudos);
    }

    public function test_un_want_a_want_es_un_no_op(): void
    {
        $this->actingAsUser();
        $item = $this->itemWithFakeStats();
        $voteId = $this->emit($item, 'want');

        $this->putJson("/api/votes/{$voteId}", ['type' => 'want'])
            ->assertOk()
            ->assertJsonPath('data.type', 'want')
            ->assertJsonPath('meta.kudos_awarded', 0);

        $this->assertDatabaseHas('votes', ['id' => $voteId, 'type' => 'want', 'score' => null]);
        $this->assertNoKudosTransactions();
    }

    public function test_un_skip_heredado_se_normaliza_al_cambiar_de_tipo(): void
    {
        $user = $this->actingAsUser();
        $category = $this->createCategory();

        $skipA = Vote::factory()->byUser($user)->forItem($this->createItem($category))
            ->create(['type' => 'skip', 'score' => null]);
        $skipB = Vote::factory()->byUser($user)->forItem($this->createItem($category))
            ->create(['type' => 'skip', 'score' => null]);
        $skipC = Vote::factory()->byUser($user)->forItem($this->createItem($category))
            ->create(['type' => 'skip', 'score' => null]);

        $this->putJson("/api/votes/{$skipA->id}", ['type' => 'skip'])
            ->assertOk()
            ->assertJsonPath('data.type', 'not_interested');
        $this->assertDatabaseHas('votes', ['id' => $skipA->id, 'type' => 'not_interested']);

        $this->putJson("/api/votes/{$skipB->id}", [])
            ->assertOk()
            ->assertJsonPath('data.type', 'not_interested');
        $this->assertDatabaseHas('votes', ['id' => $skipB->id, 'type' => 'not_interested']);

        $this->putJson("/api/votes/{$skipC->id}", ['type' => 'want'])
            ->assertOk()
            ->assertJsonPath('data.type', 'want');
        $this->assertDatabaseHas('votes', ['id' => $skipC->id, 'type' => 'want']);
    }

    public function test_una_valoracion_no_puede_pasar_a_want(): void
    {
        $this->actingAsUser();
        $item = $this->itemWithOtherVote();
        $voteId = $this->emit($item, 'vote', 8);
        $avg = (float) $item->fresh()->vote_avg;

        $this->putJson("/api/votes/{$voteId}", ['type' => 'want'])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'validation_error')
            ->assertJsonPath('error.message', self::INVALID_CONVERSION);

        $this->assertDatabaseHas('votes', ['id' => $voteId, 'type' => 'vote', 'score' => 8]);

        $item = $item->fresh();
        $this->assertSame(2, (int) $item->vote_count);
        $this->assertEquals($avg, (float) $item->vote_avg);
    }

    public function test_una_valoracion_no_puede_pasar_a_not_interested_ni_a_skip(): void
    {
        $this->actingAsUser();
        $item = $this->itemWithOtherVote();
        $voteId = $this->emit($item, 'vote', 8);

        $this->putJson("/api/votes/{$voteId}", ['type' => 'not_interested'])
            ->assertStatus(422)
            ->assertJsonPath('error.message', self::INVALID_CONVERSION);

        // Antes devolvía 200 y solo cambiaba la nota: ahora se rechaza y la nota no cambia.
        $this->putJson("/api/votes/{$voteId}", ['type' => 'skip', 'score' => 5])
            ->assertStatus(422);

        $this->assertDatabaseHas('votes', ['id' => $voteId, 'type' => 'vote', 'score' => 8]);
        $this->assertEquals(7.0, (float) $item->fresh()->vote_avg);
    }

    public function test_una_valoracion_cambia_de_nota_sin_dar_kudos(): void
    {
        $reward = config('kudos.voting.reward');
        $user = $this->actingAsUser();
        $item = $this->itemWithOtherVote();
        $voteId = $this->emit($item, 'vote', 8);
        $this->assertSame($reward, (int) $user->fresh()->total_kudos);

        $this->putJson("/api/votes/{$voteId}", ['type' => 'vote', 'score' => 4])
            ->assertOk()
            ->assertJsonPath('meta.kudos_awarded', 0)
            ->assertJsonPath('meta.total_kudos', $reward)
            ->assertJsonPath('meta.vote_type', 'vote');

        $this->assertEquals(5.0, (float) $item->fresh()->vote_avg);

        // Sin type (compatibilidad con el frontend actual) se mantiene el tipo vote.
        $this->putJson("/api/votes/{$voteId}", ['score' => 5])
            ->assertOk()
            ->assertJsonPath('meta.kudos_awarded', 0);

        $this->assertDatabaseHas('votes', ['id' => $voteId, 'type' => 'vote', 'score' => 5]);
        $this->assertEquals(5.5, (float) $item->fresh()->vote_avg);
        $this->assertSame($reward, (int) $user->fresh()->total_kudos);
        $this->assertSame(1, KudosTransaction::where('user_id', $user->id)->count());
    }

    public function test_pasar_a_valoracion_sin_puntuacion_devuelve_422(): void
    {
        $user = $this->actingAsUser();
        $item = $this->createItem($this->createCategory());
        $voteId = $this->emit($item, 'want');

        $this->putJson("/api/votes/{$voteId}", ['type' => 'vote'])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'validation_error');

        $this->assertDatabaseHas('votes', ['id' => $voteId, 'type' => 'want', 'score' => null]);
        $this->assertNoKudosTransactions();
        $this->assertSame(0, (int) $user->fresh()->total_kudos);
    }

    public function test_un_want_con_puntuacion_y_sin_tipo_devuelve_422(): void
    {
        $this->actingAsUser();
        $item = $this->createItem($this->createCategory());
        $voteId = $this->emit($item, 'want');

        $this->putJson("/api/votes/{$voteId}", ['score' => 8])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'validation_error');

        $this->assertDatabaseHas('votes', ['id' => $voteId, 'type' => 'want', 'score' => null]);
        $this->assertNoKudosTransactions();
    }

    public function test_no_se_puede_cambiar_el_tipo_del_voto_de_otro_usuario(): void
    {
        $item = $this->createItem($this->createCategory());
        $other = User::factory()->create();
        $vote = Vote::factory()->byUser($other)->forItem($item)->create(['type' => 'want', 'score' => null]);

        $this->actingAsUser();

        $this->putJson("/api/votes/{$vote->id}", ['type' => 'vote', 'score' => 8])->assertForbidden();

        $this->assertDatabaseHas('votes', ['id' => $vote->id, 'user_id' => $other->id, 'type' => 'want', 'score' => null]);
        $this->assertNoKudosTransactions();
    }

    public function test_no_se_puede_valorar_un_want_de_un_item_borrado(): void
    {
        $this->actingAsUser();
        $item = $this->createItem($this->createCategory());
        $voteId = $this->emit($item, 'want');

        $item->delete();

        $this->putJson("/api/votes/{$voteId}", ['type' => 'vote', 'score' => 8])
            ->assertStatus(422);

        $this->assertDatabaseHas('votes', ['id' => $voteId, 'type' => 'want', 'score' => null]);
        $this->assertNoKudosTransactions();
    }

    public function test_la_auditoria_de_kudos_queda_limpia_tras_las_transiciones(): void
    {
        $category = $this->createCategory();
        $itemA = $this->createItem($category);
        $itemB = $this->createItem($category);
        $itemC = $this->createItem($category);

        // Usuario 1: want -> vote, not_interested -> vote, want <-> not_interested, vote -> vote, vote -> want rechazado
        $this->actingAsUser();
        $voteA = $this->emit($itemA, 'want');
        $this->putJson("/api/votes/{$voteA}", ['type' => 'vote', 'score' => 8])->assertOk();
        $this->putJson("/api/votes/{$voteA}", ['type' => 'vote', 'score' => 3])->assertOk();
        $this->putJson("/api/votes/{$voteA}", ['type' => 'want'])->assertStatus(422);

        $voteB = $this->emit($itemB, 'not_interested');
        $this->putJson("/api/votes/{$voteB}", ['type' => 'want'])->assertOk();
        $this->putJson("/api/votes/{$voteB}", ['type' => 'not_interested'])->assertOk();
        $this->putJson("/api/votes/{$voteB}", ['type' => 'vote', 'score' => 9])->assertOk();

        // Usuario 2: valora, borra y vuelve a valorar pasando por want
        $this->actingAsUser();
        $voteC = $this->emit($itemC, 'vote', 7);
        $this->deleteJson("/api/votes/{$voteC}")->assertOk();
        $voteC2 = $this->emit($itemC, 'want');
        $this->putJson("/api/votes/{$voteC2}", ['type' => 'vote', 'score' => 6])->assertOk();

        $this->artisan('kudos:audit-consistency')
            ->expectsOutputToContain('Desajustes detectados: 0')
            ->assertSuccessful();
    }
}
