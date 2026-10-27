<?php

namespace Tests\Feature\Votes;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\CreatesItems;
use Tests\TestCase;

/**
 * La nota debe ir en pasos de medio punto (multiple_of:0.5) al votar y al cambiar el voto.
 */
class VoteScoreValidationTest extends TestCase
{
    use RefreshDatabase, CreatesItems;

    private const MESSAGE = 'La puntuación debe ir en pasos de medio punto (por ejemplo, 7 o 7,5).';

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
    }

    public function test_emitir_una_nota_que_no_es_multiplo_de_medio_punto_devuelve_422(): void
    {
        $item = $this->createItem($this->createCategory());
        $this->actingAsUser();

        $this->postJson('/api/votes', ['item_id' => $item->id, 'type' => 'vote', 'score' => 7.3])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'validation_error')
            ->assertJsonPath('error.message', self::MESSAGE);

        $this->assertDatabaseCount('votes', 0);
    }

    public function test_cambiar_a_una_nota_que_no_es_multiplo_de_medio_punto_devuelve_422(): void
    {
        $item = $this->createItem($this->createCategory());
        $this->actingAsUser();

        $created = $this->postJson('/api/votes', ['item_id' => $item->id, 'type' => 'vote', 'score' => 7.5]);
        $created->assertCreated();
        $voteId = $created->json('data.id');

        $this->putJson("/api/votes/{$voteId}", ['score' => 6.25])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'validation_error');

        $this->assertDatabaseHas('votes', ['id' => $voteId, 'score' => 7.5]);
    }

    public function test_se_aceptan_notas_enteras_y_medios_puntos(): void
    {
        $category = $this->createCategory();
        $first = $this->createItem($category);
        $second = $this->createItem($category);
        $this->actingAsUser();

        $this->postJson('/api/votes', ['item_id' => $first->id, 'type' => 'vote', 'score' => 10])
            ->assertCreated();
        $this->postJson('/api/votes', ['item_id' => $second->id, 'type' => 'vote', 'score' => 0.5])
            ->assertCreated();
    }
}
