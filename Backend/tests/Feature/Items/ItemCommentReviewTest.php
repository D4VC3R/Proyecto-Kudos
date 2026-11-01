<?php

namespace Tests\Feature\Items;

use App\Models\Item;
use App\Models\ItemComment;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\Concerns\CreatesItems;
use Tests\Concerns\InteractsWithUsers;
use Tests\TestCase;

class ItemCommentReviewTest extends TestCase
{
    use CreatesItems, InteractsWithUsers, RefreshDatabase;

    private function newItem(): Item
    {
        return $this->createItem($this->createCategory('peliculas'));
    }

    private function voteOf(User $user, Item $item, float $score = 8.5): Vote
    {
        return Vote::factory()->forItem($item)->byUser($user)->rated($score)->create();
    }

    private function postComment(Item $item, array $payload): TestResponse
    {
        return $this->postJson("/api/items/{$item->id}/comments", $payload);
    }

    public function test_con_un_voto_vote_publica_una_resena(): void
    {
        $item = $this->newItem();
        $user = $this->actingAsUser();
        $vote = $this->voteOf($user, $item);

        $this->postComment($item, ['content' => 'Una gran película', 'is_review' => true])
            ->assertStatus(201)
            ->assertJsonPath('data.is_review', true)
            ->assertJsonPath('data.score', 8.5);

        $this->assertDatabaseHas('item_comments', ['content' => 'Una gran película', 'vote_id' => $vote->id]);
    }

    public function test_sin_voto_no_puede_publicar_una_resena(): void
    {
        $item = $this->newItem();
        $this->actingAsUser();

        $this->postComment($item, ['content' => 'Reseña sin nota', 'is_review' => true])
            ->assertStatus(422)
            ->assertJsonStructure(['error' => ['details' => ['is_review']]]);

        $this->assertDatabaseCount('item_comments', 0);
    }

    public function test_con_un_voto_want_no_puede_publicar_una_resena(): void
    {
        $item = $this->newItem();
        $user = $this->actingAsUser();
        Vote::factory()->forItem($item)->byUser($user)->create(['type' => Vote::TYPE_WANT, 'score' => null]);

        $this->postComment($item, ['content' => 'Reseña con un want', 'is_review' => true])
            ->assertStatus(422)
            ->assertJsonStructure(['error' => ['details' => ['is_review']]]);

        $this->assertDatabaseCount('item_comments', 0);
    }

    public function test_solo_una_resena_por_usuario_e_item(): void
    {
        $item = $this->newItem();
        $user = $this->actingAsUser();
        $this->voteOf($user, $item);

        $this->postComment($item, ['content' => 'Primera reseña', 'is_review' => true])->assertStatus(201);

        $this->postComment($item, ['content' => 'Segunda reseña', 'is_review' => true])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'validation_error')
            ->assertJsonPath('error.message', 'Ya has escrito una reseña de este ítem.');

        $this->assertSame(1, ItemComment::query()->whereNotNull('vote_id')->count());

        $this->postComment($item, ['content' => 'Un comentario normal'])->assertStatus(201);
    }

    public function test_el_indice_unico_impide_dos_resenas_vivas(): void
    {
        $item = $this->newItem();
        $user = User::factory()->create();
        $vote = $this->voteOf($user, $item);

        ItemComment::create(['item_id' => $item->id, 'user_id' => $user->id, 'content' => 'Primera', 'vote_id' => $vote->id]);

        $this->expectException(UniqueConstraintViolationException::class);

        ItemComment::create(['item_id' => $item->id, 'user_id' => $user->id, 'content' => 'Segunda', 'vote_id' => $vote->id]);
    }

    public function test_una_resena_borrada_no_impide_escribir_otra(): void
    {
        $item = $this->newItem();
        $user = $this->actingAsUser();
        $vote = $this->voteOf($user, $item);
        $old = ItemComment::factory()->create(['item_id' => $item->id, 'user_id' => $user->id, 'vote_id' => $vote->id]);
        $old->delete();

        $this->postComment($item, ['content' => 'Nueva reseña', 'is_review' => true])->assertStatus(201);
    }

    public function test_la_resena_de_otro_usuario_no_cuenta(): void
    {
        $item = $this->newItem();
        $other = User::factory()->create();
        $otherVote = $this->voteOf($other, $item, 6);
        ItemComment::factory()->create(['item_id' => $item->id, 'user_id' => $other->id, 'vote_id' => $otherVote->id]);

        $user = $this->actingAsUser();
        $this->voteOf($user, $item);

        $this->postComment($item, ['content' => 'Mi propia reseña', 'is_review' => true])->assertStatus(201);
    }

    public function test_el_listado_muestra_primero_las_resenas_con_su_nota(): void
    {
        $item = $this->newItem();
        $user = User::factory()->create();
        $vote = $this->voteOf($user, $item);
        ItemComment::factory()->create(['item_id' => $item->id, 'user_id' => $user->id, 'vote_id' => $vote->id, 'created_at' => now()->subDays(2)]);
        ItemComment::factory()->create(['item_id' => $item->id, 'user_id' => User::factory()->create()->id, 'created_at' => now()->subHour()]);

        $this->getJson("/api/items/{$item->id}/comments")
            ->assertOk()
            ->assertJsonPath('data.0.is_review', true)
            ->assertJsonPath('data.0.score', 8.5)
            ->assertJsonPath('data.1.is_review', false)
            ->assertJsonPath('data.1.score', null);
    }

    public function test_al_borrar_el_voto_la_resena_pasa_a_comentario_normal(): void
    {
        $item = $this->newItem();
        $user = User::factory()->create();
        $vote = $this->voteOf($user, $item);
        $comment = ItemComment::factory()->create(['item_id' => $item->id, 'user_id' => $user->id, 'vote_id' => $vote->id]);

        $vote->delete();

        $this->assertNull($comment->fresh()->vote_id);
        $this->getJson("/api/items/{$item->id}/comments")
            ->assertOk()
            ->assertJsonPath('data.0.is_review', false)
            ->assertJsonPath('data.0.score', null);
    }

    public function test_is_review_false_crea_un_comentario_normal(): void
    {
        $item = $this->newItem();
        $this->actingAsUser();

        $this->postComment($item, ['content' => 'Solo un comentario', 'is_review' => false])
            ->assertStatus(201)
            ->assertJsonPath('data.is_review', false);

        $this->assertDatabaseHas('item_comments', ['content' => 'Solo un comentario', 'vote_id' => null]);
    }
}
