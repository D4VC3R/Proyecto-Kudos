<?php

namespace Tests\Feature\Votes;

use App\Models\Category;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\CreatesItems;
use Tests\TestCase;

/**
 * Wishlist (F2-05): GET /api/votes/my-votes?type=want y las transiciones desde ella.
 */
class WishlistTest extends TestCase
{
    use CreatesItems, RefreshDatabase;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();

        $this->category = $this->createCategory();
    }

    /**
     * Crea un voto "want" del usuario sobre un ítem nuevo, con la fecha de alta indicada.
     */
    private function createWant(User $user, ?string $createdAt = null, array $itemAttrs = []): Vote
    {
        $item = $this->createItem($this->category, $itemAttrs);
        $attrs = ['type' => 'want', 'score' => null];

        if ($createdAt !== null) {
            $attrs['created_at'] = $createdAt;
        }

        return Vote::factory()->byUser($user)->forItem($item)->create($attrs);
    }

    private function wishlistItemIds($response): array
    {
        return collect($response->json('data'))->pluck('item_id')->all();
    }

    public function test_la_wishlist_solo_devuelve_los_want_del_usuario(): void
    {
        $user = $this->actingAsUser();
        $want = $this->createWant($user);
        $this->createWant(User::factory()->create());

        $voted = $this->createItem($this->category);
        Vote::factory()->byUser($user)->forItem($voted)->withScore(8)->create();
        $skipped = $this->createItem($this->category);
        Vote::factory()->byUser($user)->forItem($skipped)->create(['type' => 'not_interested', 'score' => null]);

        $this->getJson('/api/votes/my-votes?type=want')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.type', 'want')
            ->assertJsonPath('data.0.item_id', $want->item_id);
    }

    public function test_la_wishlist_ordena_por_fecha_de_alta_mas_reciente_primero(): void
    {
        $user = $this->actingAsUser();
        $a = $this->createWant($user, '2026-01-10 10:00:00');
        $b = $this->createWant($user, '2026-01-12 10:00:00');
        $c = $this->createWant($user, '2026-01-11 10:00:00');

        $response = $this->getJson('/api/votes/my-votes?type=want')->assertOk();

        $this->assertSame([$b->item_id, $c->item_id, $a->item_id], $this->wishlistItemIds($response));
    }

    public function test_la_wishlist_desempata_por_id_con_la_misma_fecha(): void
    {
        $user = $this->actingAsUser();
        $a = $this->createWant($user, '2026-01-10 10:00:00');
        $b = $this->createWant($user, '2026-01-10 10:00:00');

        $response = $this->getJson('/api/votes/my-votes?type=want')->assertOk();

        $expected = collect([$a->id, $b->id])->sortDesc()->values()->all();
        $this->assertSame($expected, collect($response->json('data'))->pluck('id')->all());
    }

    public function test_la_wishlist_incluye_la_fecha_de_lanzamiento_del_item(): void
    {
        $user = $this->actingAsUser();
        $this->createWant($user, '2026-01-10 10:00:00', ['released_at' => '2027-03-12']);
        $this->createWant($user, '2026-01-11 10:00:00', ['released_at' => null]);

        $response = $this->getJson('/api/votes/my-votes?type=want')
            ->assertOk()
            ->assertJsonStructure(['data' => [['item' => ['released_at']]]]);

        $releasedAt = collect($response->json('data'))->pluck('item.released_at')->all();
        $this->assertSame([null, '2027-03-12'], $releasedAt);
    }

    public function test_valorar_desde_la_wishlist_la_saca_de_la_lista(): void
    {
        $this->actingAsUser();
        $item = $this->createItem($this->category);

        $voteId = $this->postJson('/api/votes', ['item_id' => $item->id, 'type' => 'want'])
            ->assertCreated()
            ->json('data.id');

        $this->putJson("/api/votes/{$voteId}", ['type' => 'vote', 'score' => 8])
            ->assertOk()
            ->assertJsonPath('meta.vote_type', 'vote');

        $this->getJson('/api/votes/my-votes?type=want')->assertOk()->assertJsonPath('meta.total', 0);
        $this->getJson('/api/votes/my-votes?type=vote')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.score', 8);
    }

    public function test_quitar_de_la_wishlist_como_no_interesado_la_saca_de_la_lista(): void
    {
        $this->actingAsUser();
        $item = $this->createItem($this->category);

        $voteId = $this->postJson('/api/votes', ['item_id' => $item->id, 'type' => 'want'])
            ->assertCreated()
            ->json('data.id');

        $this->putJson("/api/votes/{$voteId}", ['type' => 'not_interested'])->assertOk();

        $this->getJson('/api/votes/my-votes?type=want')->assertOk()->assertJsonPath('meta.total', 0);
        $this->getJson('/api/votes/my-votes?type=not_interested')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.type', 'not_interested');
    }

    public function test_borrar_un_want_lo_saca_de_la_wishlist(): void
    {
        $this->actingAsUser();
        $item = $this->createItem($this->category);

        $voteId = $this->postJson('/api/votes', ['item_id' => $item->id, 'type' => 'want'])
            ->assertCreated()
            ->json('data.id');

        $this->deleteJson("/api/votes/{$voteId}")->assertOk();

        $this->getJson('/api/votes/my-votes?type=want')->assertOk()->assertJsonPath('meta.total', 0);
        $this->assertDatabaseMissing('votes', ['id' => $voteId]);
    }
}
