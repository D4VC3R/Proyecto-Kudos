<?php

namespace Tests\Feature\Kudos;

use App\Models\Item;
use App\Models\KudosTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\CreatesItems;
use Tests\TestCase;

class VoteKudosTest extends TestCase
{
    use RefreshDatabase, CreatesItems;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();

        // Congela el tiempo: el contador diario de votos caduca con endOfDay() y no debe hacerlo a mitad de un bucle.
        $this->travelTo(Carbon::parse('2026-01-15 12:00:00'));
    }

    /**
     * Vota un ítem por HTTP con la nota indicada y devuelve la respuesta.
     */
    private function vote(Item $item, int $score = 8)
    {
        return $this->postJson('/api/votes', ['item_id' => $item->id, 'type' => 'vote', 'score' => $score]);
    }

    public function test_el_primer_voto_a_un_item_da_kudos(): void
    {
        $reward = config('kudos.voting.reward');
        $user = $this->actingAsUser();
        $item = $this->createItem($this->createCategory());

        $this->vote($item)
            ->assertCreated()
            ->assertJsonPath('meta.kudos_awarded', $reward)
            ->assertJsonPath('meta.total_kudos', $reward);

        $this->assertSame($reward, (int) $user->fresh()->total_kudos);
        $this->assertDatabaseHas('kudos_transactions', [
            'user_id'        => $user->id,
            'reason'         => 'vote_first_time_item',
            'action_key'     => "vote_first_time_item:{$user->id}:{$item->id}",
            'reference_type' => Item::class,
            'reference_id'   => $item->id,
            'kudos_amount'   => $reward,
        ]);
    }

    public function test_repetir_el_voto_no_da_mas_kudos(): void
    {
        $reward = config('kudos.voting.reward');
        $user = $this->actingAsUser();
        $item = $this->createItem($this->createCategory());

        $first = $this->vote($item);
        $first->assertCreated()->assertJsonPath('meta.kudos_awarded', $reward);
        $voteId = $first->json('data.id');

        // Voto duplicado: devuelve el existente y no da kudos.
        $this->vote($item)
            ->assertOk()
            ->assertJsonPath('meta.kudos_awarded', 0);

        // Borrar el voto y volver a votar: se crea otro voto, pero la action_key impide repetir los kudos.
        $this->deleteJson("/api/votes/{$voteId}")->assertOk();
        $this->vote($item)
            ->assertCreated()
            ->assertJsonPath('meta.kudos_awarded', 0);

        $this->assertSame($reward, (int) $user->fresh()->total_kudos);
        $this->assertSame(1, KudosTransaction::where('user_id', $user->id)->count());
    }

    public function test_por_encima_de_20_votos_diarios_la_recompensa_decrece(): void
    {
        $reward = config('kudos.voting.reward');
        $threshold = config('kudos.voting.diminishing_returns.threshold');
        $newReward = config('kudos.voting.diminishing_returns.new_reward');

        $user = $this->actingAsUser();
        $category = $this->createCategory();

        $awarded = [];
        for ($i = 1; $i <= $threshold + 1; $i++) {
            $item = $this->createItem($category, ['name' => "Ítem {$i}"]);
            $response = $this->vote($item);
            $response->assertCreated();
            $awarded[] = $response->json('meta.kudos_awarded');
        }

        $this->assertEquals(array_fill(0, $threshold, $reward), array_slice($awarded, 0, $threshold));
        $this->assertSame($newReward, $awarded[$threshold]);
        $this->assertSame($threshold * $reward + $newReward, (int) $user->fresh()->total_kudos);
    }

    public function test_el_tope_diario_de_votos_con_kudos_es_50(): void
    {
        $reward = config('kudos.voting.reward');
        $threshold = config('kudos.voting.diminishing_returns.threshold');
        $newReward = config('kudos.voting.diminishing_returns.new_reward');
        $cap = config('kudos.voting.daily_cap');

        $user = $this->actingAsUser();
        $category = $this->createCategory();

        for ($i = 1; $i <= $cap; $i++) {
            $item = $this->createItem($category, ['name' => "Ítem {$i}"]);
            $this->vote($item)->assertCreated();
        }

        // El voto número cap + 1 supera el tope: se registra el voto pero no da kudos.
        $last = $this->createItem($category, ['name' => 'Ítem final']);
        $this->vote($last)
            ->assertCreated()
            ->assertJsonPath('meta.kudos_awarded', 0);

        $this->assertSame($threshold * $reward + ($cap - $threshold) * $newReward, (int) $user->fresh()->total_kudos);
        $this->assertSame($cap, KudosTransaction::where('user_id', $user->id)->count());
        $this->assertSame(1, (int) $last->fresh()->vote_count);
    }

    public function test_la_auditoria_de_kudos_no_detecta_desajustes_tras_votar(): void
    {
        $category = $this->createCategory();
        $items = [];
        foreach (range(1, 6) as $i) {
            $items[$i] = $this->createItem($category, ['name' => "Ítem {$i}"]);
        }

        $this->actingAsUser();

        // Tres votos, uno repetido, un skip, y borrar y volver a votar otro.
        foreach ([1, 2, 3] as $i) {
            $this->vote($items[$i])->assertCreated();
        }
        $this->vote($items[1], 3)->assertOk();
        $this->postJson('/api/votes', ['item_id' => $items[4]->id, 'type' => 'skip'])->assertCreated();

        $voteId = $this->vote($items[5])->assertCreated()->json('data.id');
        $this->deleteJson("/api/votes/{$voteId}")->assertOk();
        $this->vote($items[5])->assertCreated();

        // Otro usuario vota un ítem.
        $this->actingAsUser();
        $this->vote($items[6])->assertCreated();

        $this->artisan('kudos:audit-consistency')
            ->expectsOutputToContain('Desajustes detectados: 0')
            ->assertSuccessful();
    }

    public function test_la_auditoria_detecta_un_desajuste(): void
    {
        $user = $this->actingAsUser();
        $item = $this->createItem($this->createCategory());

        $this->vote($item)->assertCreated();

        // Control negativo: se rompe el total a propósito y la auditoría tiene que verlo (sin --fix).
        DB::table('users')->where('id', $user->id)->update(['total_kudos' => 999]);

        $this->artisan('kudos:audit-consistency')
            ->expectsOutputToContain('Desajustes detectados: 1')
            ->assertSuccessful();
    }
}
