<?php

namespace Tests\Feature\Items;

use App\Models\Item;
use App\Models\User;
use App\Models\Vote;
use App\Services\ItemStatsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\CreatesItems;
use Tests\TestCase;

class ItemVoteDistributionTest extends TestCase
{
    use CreatesItems, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
    }

    /**
     * Lista esperada de la ficha a partir de los recuentos por nivel (los niveles que faltan, a 0).
     *
     * @param  array<int, int>  $counts
     * @return list<array{score: int, count: int}>
     */
    private function distribution(array $counts): array
    {
        return array_map(
            fn (int $score) => ['score' => $score, 'count' => $counts[$score] ?? 0],
            [2, 4, 6, 8, 10],
        );
    }

    /** @param  list<float>  $scores */
    private function rateAs(Item $item, array $scores): void
    {
        foreach ($scores as $score) {
            Vote::factory()->byUser(User::factory()->create())->forItem($item)->rated($score)->create();
        }
    }

    public function test_la_ficha_reparte_los_votos_por_nivel(): void
    {
        $category = $this->createCategory();
        $item = $this->createItem($category);
        $other = $this->createItem($category);

        $this->rateAs($item, [10, 9.5, 8, 7.5, 6, 2, 0]);
        $this->rateAs($other, [4]);
        // La factoría no actualiza vote_count: se recalcula como al votar.
        app(ItemStatsService::class)->recalculate($item->id);

        $response = $this->getJson("/api/items/{$item->id}")->assertOk();

        $this->assertSame(
            $this->distribution([2 => 2, 4 => 0, 6 => 1, 8 => 2, 10 => 2]),
            $response->json('data.vote_distribution'),
        );
        $this->assertSame(7, $response->json('data.vote_count'));
        $this->assertSame(7, array_sum(array_column($response->json('data.vote_distribution'), 'count')));
    }

    public function test_las_notas_intermedias_van_al_nivel_mas_cercano_y_el_empate_sube(): void
    {
        $category = $this->createCategory();
        $item = $this->createItem($category);
        $other = $this->createItem($category);

        $this->rateAs($item, [1, 3, 5, 7, 9]);
        $this->rateAs($other, [4.5, 5.5]);

        $this->assertSame(
            $this->distribution([2 => 1, 4 => 1, 6 => 1, 8 => 1, 10 => 1]),
            $this->getJson("/api/items/{$item->id}")->assertOk()->json('data.vote_distribution'),
        );
        $this->assertSame(
            $this->distribution([4 => 1, 6 => 1]),
            $this->getJson("/api/items/{$other->id}")->assertOk()->json('data.vote_distribution'),
        );
    }

    public function test_sin_votos_todos_los_niveles_estan_a_cero(): void
    {
        $item = $this->createItem($this->createCategory());

        $distribution = $this->getJson("/api/items/{$item->id}")->assertOk()->json('data.vote_distribution');

        $this->assertCount(5, $distribution);
        $this->assertSame($this->distribution([]), $distribution);
    }

    public function test_excluye_los_votos_sin_nota(): void
    {
        $item = $this->createItem($this->createCategory());

        $this->rateAs($item, [8]);
        foreach ([Vote::TYPE_WANT, Vote::TYPE_NOT_INTERESTED, Vote::TYPE_SKIP] as $type) {
            Vote::factory()->byUser(User::factory()->create())->forItem($item)->create(['type' => $type, 'score' => null]);
        }

        $this->assertSame(
            $this->distribution([8 => 1]),
            $this->getJson("/api/items/{$item->id}")->assertOk()->json('data.vote_distribution'),
        );
    }

    public function test_el_reparto_se_calcula_con_una_sola_consulta_a_votes(): void
    {
        $item = $this->createItem($this->createCategory());

        $this->rateAs($item, [8]);
        [$votesQueries, $total] = $this->queriesOfDetail($item);
        $this->assertSame(1, $votesQueries);

        $this->rateAs($item, [2, 2, 4, 4, 6, 6, 8, 10, 10]);
        [$votesQueries, $totalMany] = $this->queriesOfDetail($item);
        $this->assertSame(1, $votesQueries);
        $this->assertSame($total, $totalMany);
    }

    /**
     * Consultas a votes y consultas totales de GET /api/items/{id} como invitado.
     *
     * @return array{int, int}
     */
    private function queriesOfDetail(Item $item): array
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->getJson("/api/items/{$item->id}")->assertOk();

        $log = DB::getQueryLog();
        DB::disableQueryLog();

        $votes = array_filter($log, fn (array $query) => str_contains($query['query'], 'from "votes"'));

        return [count($votes), count($log)];
    }

    public function test_el_reparto_se_actualiza_al_votar(): void
    {
        $item = $this->createItem($this->createCategory());
        $this->actingAsUser();

        $this->assertSame(
            $this->distribution([]),
            $this->getJson("/api/items/{$item->id}")->assertOk()->json('data.vote_distribution'),
        );

        $this->postJson('/api/votes', ['item_id' => $item->id, 'type' => 'vote', 'score' => 8])->assertCreated();

        $this->assertSame(
            $this->distribution([8 => 1]),
            $this->getJson("/api/items/{$item->id}")->assertOk()->json('data.vote_distribution'),
        );
    }
}
