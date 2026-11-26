<?php

namespace Tests\Feature\Votes;

use App\Models\Vote;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\CreatesItems;
use Tests\TestCase;

/**
 * Tipos de voto de la F2-01: vote, want y not_interested (skip se normaliza a not_interested).
 */
class VoteTypesTest extends TestCase
{
    use CreatesItems, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
    }

    /**
     * Ítem con medias falsas: si algo las recalculase, quedarían a 0.
     */
    private function itemWithFakeStats()
    {
        return $this->createItem($this->createCategory(), ['vote_avg' => 7, 'vote_count' => 2]);
    }

    /**
     * El usuario autenticado emite un vote, un want, un not_interested y un skip heredado en cuatro ítems.
     */
    private function userWithOneOfEachType()
    {
        $category = $this->createCategory();
        $item1 = $this->createItem($category);
        $item2 = $this->createItem($category);
        $item3 = $this->createItem($category);
        $item4 = $this->createItem($category);
        $user = $this->actingAsUser();

        $this->postJson('/api/votes', ['item_id' => $item1->id, 'type' => 'vote', 'score' => 8])->assertCreated();
        $this->postJson('/api/votes', ['item_id' => $item2->id, 'type' => 'want'])->assertCreated();
        $this->postJson('/api/votes', ['item_id' => $item3->id, 'type' => 'not_interested'])->assertCreated();
        Vote::factory()->byUser($user)->forItem($item4)->create(['type' => 'skip', 'score' => null]);

        return $user;
    }

    public function test_un_voto_want_se_guarda_sin_puntuacion_y_no_cambia_la_media(): void
    {
        $user = $this->actingAsUser();
        $item = $this->itemWithFakeStats();

        $this->postJson('/api/votes', ['item_id' => $item->id, 'type' => 'want'])
            ->assertCreated()
            ->assertJsonPath('data.type', 'want')
            ->assertJsonPath('meta.vote_type', 'want')
            ->assertJsonPath('meta.kudos_awarded', 0);

        $this->assertDatabaseHas('votes', [
            'user_id' => $user->id,
            'item_id' => $item->id,
            'type' => 'want',
            'score' => null,
        ]);

        $item = $item->fresh();
        $this->assertSame(2, (int) $item->vote_count);
        $this->assertEquals(7.0, (float) $item->vote_avg);
    }

    public function test_un_voto_not_interested_se_guarda_y_no_cambia_la_media(): void
    {
        $user = $this->actingAsUser();
        $item = $this->itemWithFakeStats();

        $this->postJson('/api/votes', ['item_id' => $item->id, 'type' => 'not_interested'])
            ->assertCreated()
            ->assertJsonPath('data.type', 'not_interested')
            ->assertJsonPath('meta.vote_type', 'not_interested')
            ->assertJsonPath('meta.kudos_awarded', 0);

        $this->assertDatabaseHas('votes', [
            'user_id' => $user->id,
            'item_id' => $item->id,
            'type' => 'not_interested',
            'score' => null,
        ]);

        $item = $item->fresh();
        $this->assertSame(2, (int) $item->vote_count);
        $this->assertEquals(7.0, (float) $item->vote_avg);
    }

    public function test_un_skip_se_guarda_como_not_interested(): void
    {
        $this->actingAsUser();
        $item = $this->itemWithFakeStats();

        $this->postJson('/api/votes', ['item_id' => $item->id, 'type' => 'skip'])
            ->assertCreated()
            ->assertJsonPath('data.type', 'not_interested')
            ->assertJsonPath('meta.vote_type', 'not_interested');

        $this->assertDatabaseHas('votes', ['type' => 'not_interested']);
        $this->assertDatabaseMissing('votes', ['type' => 'skip']);

        $item = $item->fresh();
        $this->assertSame(2, (int) $item->vote_count);
        $this->assertEquals(7.0, (float) $item->vote_avg);
    }

    public function test_want_con_puntuacion_devuelve_422(): void
    {
        $this->actingAsUser();
        $item = $this->createItem($this->createCategory());

        $this->postJson('/api/votes', ['item_id' => $item->id, 'type' => 'want', 'score' => 8])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'validation_error')
            ->assertJsonStructure(['error' => ['details' => ['score']]]);

        $this->assertDatabaseCount('votes', 0);
    }

    public function test_not_interested_con_puntuacion_devuelve_422(): void
    {
        $this->actingAsUser();
        $item = $this->createItem($this->createCategory());

        $this->postJson('/api/votes', ['item_id' => $item->id, 'type' => 'not_interested', 'score' => 8])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'validation_error')
            ->assertJsonStructure(['error' => ['details' => ['score']]]);

        $this->assertDatabaseCount('votes', 0);
    }

    public function test_skip_con_puntuacion_devuelve_422(): void
    {
        $this->actingAsUser();
        $item = $this->createItem($this->createCategory());

        $this->postJson('/api/votes', ['item_id' => $item->id, 'type' => 'skip', 'score' => 8])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'validation_error')
            ->assertJsonStructure(['error' => ['details' => ['score']]]);

        $this->assertDatabaseCount('votes', 0);
    }

    public function test_vote_sin_puntuacion_devuelve_422(): void
    {
        $this->actingAsUser();
        $item = $this->createItem($this->createCategory());

        $this->postJson('/api/votes', ['item_id' => $item->id, 'type' => 'vote'])
            ->assertStatus(422)
            ->assertJsonStructure(['error' => ['details' => ['score']]]);
    }

    public function test_un_tipo_desconocido_devuelve_422(): void
    {
        $this->actingAsUser();
        $item = $this->createItem($this->createCategory());

        $this->postJson('/api/votes', ['item_id' => $item->id, 'type' => 'favorite'])
            ->assertStatus(422)
            ->assertJsonStructure(['error' => ['details' => ['type']]]);
    }

    public function test_want_y_not_interested_no_dan_kudos(): void
    {
        $user = $this->actingAsUser();
        $category = $this->createCategory();
        $wanted = $this->createItem($category);
        $discarded = $this->createItem($category);

        $this->postJson('/api/votes', ['item_id' => $wanted->id, 'type' => 'want'])
            ->assertCreated()
            ->assertJsonPath('meta.kudos_awarded', 0);
        $this->postJson('/api/votes', ['item_id' => $discarded->id, 'type' => 'not_interested'])
            ->assertCreated()
            ->assertJsonPath('meta.kudos_awarded', 0);

        $this->assertDatabaseCount('kudos_transactions', 0);
        $this->assertSame(0, (int) $user->fresh()->total_kudos);
    }

    public function test_un_want_repetido_devuelve_el_existente(): void
    {
        $this->actingAsUser();
        $item = $this->createItem($this->createCategory());

        $voteId = $this->postJson('/api/votes', ['item_id' => $item->id, 'type' => 'want'])
            ->assertCreated()
            ->json('data.id');

        $this->postJson('/api/votes', ['item_id' => $item->id, 'type' => 'want'])
            ->assertOk()
            ->assertJsonPath('data.id', $voteId)
            ->assertJsonPath('meta.was_existing', true)
            ->assertJsonPath('meta.reason', 'already_wanted');

        $this->assertDatabaseCount('votes', 1);
    }

    public function test_un_not_interested_repetido_mantiene_el_motivo_heredado(): void
    {
        $this->actingAsUser();
        $item = $this->createItem($this->createCategory());

        $this->postJson('/api/votes', ['item_id' => $item->id, 'type' => 'not_interested'])->assertCreated();

        $this->postJson('/api/votes', ['item_id' => $item->id, 'type' => 'not_interested'])
            ->assertOk()
            ->assertJsonPath('meta.reason', 'already_skipped');
    }

    public function test_las_medias_solo_cuentan_los_votos_valorados(): void
    {
        $item = $this->createItem($this->createCategory());

        $this->actingAsUser();
        $this->postJson('/api/votes', ['item_id' => $item->id, 'type' => 'vote', 'score' => 8])->assertCreated();

        $this->actingAsUser();
        $this->postJson('/api/votes', ['item_id' => $item->id, 'type' => 'want'])->assertCreated();

        $this->actingAsUser();
        $this->postJson('/api/votes', ['item_id' => $item->id, 'type' => 'not_interested'])->assertCreated();

        $item = $item->fresh();
        $this->assertSame(1, (int) $item->vote_count);
        $this->assertEquals(8.0, (float) $item->vote_avg);
    }

    public function test_borrar_un_want_no_recalcula_la_media(): void
    {
        $this->actingAsUser();
        $item = $this->itemWithFakeStats();

        $voteId = $this->postJson('/api/votes', ['item_id' => $item->id, 'type' => 'want'])
            ->assertCreated()
            ->json('data.id');

        $this->deleteJson("/api/votes/{$voteId}")->assertOk();

        $item = $item->fresh();
        $this->assertSame(2, (int) $item->vote_count);
        $this->assertEquals(7.0, (float) $item->vote_avg);
    }

    public function test_mis_votos_filtra_por_los_tipos_nuevos(): void
    {
        $this->userWithOneOfEachType();

        $this->getJson('/api/votes/my-votes?type=want')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.type', 'want');

        $this->getJson('/api/votes/my-votes?type=vote')
            ->assertOk()
            ->assertJsonPath('meta.total', 1);

        $this->getJson('/api/votes/my-votes?type=not_interested')
            ->assertOk()
            ->assertJsonPath('meta.total', 2);

        $this->getJson('/api/votes/my-votes?type=skip')
            ->assertOk()
            ->assertJsonPath('meta.total', 2);
    }

    public function test_mis_votos_rechaza_un_tipo_desconocido(): void
    {
        $this->actingAsUser();

        $this->getJson('/api/votes/my-votes?type=favorite')->assertStatus(422);
    }

    public function test_las_estadisticas_del_perfil_cuentan_cada_tipo(): void
    {
        $this->userWithOneOfEachType();

        $response = $this->getJson('/api/profile/statistics')
            ->assertOk()
            ->assertJsonPath('data.total_votes', 1)
            ->assertJsonPath('data.total_want', 1)
            ->assertJsonPath('data.total_not_interested', 2)
            ->assertJsonPath('data.total_skips', 2);

        $this->assertEquals(8, $response->json('data.average_score'));
    }

    public function test_user_vote_incluye_el_tipo_en_el_detalle(): void
    {
        // El ítem se crea antes de autenticar: así su creador es otro usuario.
        $item = $this->createItem($this->createCategory());
        $this->actingAsUser();

        $this->postJson('/api/votes', ['item_id' => $item->id, 'type' => 'want'])->assertCreated();

        $this->getJson("/api/items/{$item->id}")
            ->assertOk()
            ->assertJsonPath('data.user_vote.type', 'want')
            ->assertJsonPath('data.user_vote.score', null)
            ->assertJsonPath('data.can_vote', false);
    }

    public function test_user_vote_incluye_el_tipo_en_el_listado(): void
    {
        $item = $this->createItem($this->createCategory());
        $this->actingAsUser();

        $this->postJson('/api/votes', ['item_id' => $item->id, 'type' => 'not_interested'])->assertCreated();

        $this->getJson('/api/items?category_slug=peliculas')
            ->assertOk()
            ->assertJsonPath('data.0.user_vote.type', 'not_interested');
    }
}
