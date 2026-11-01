<?php

namespace Tests\Feature\Items;

use App\Models\Item;
use App\Models\ItemComment;
use App\Models\User;
use App\Models\Vote;
use App\Notifications\CommentRepliedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesItems;
use Tests\Concerns\InteractsWithUsers;
use Tests\TestCase;

class ItemCommentReplyTest extends TestCase
{
    use CreatesItems, InteractsWithUsers, RefreshDatabase;

    private function newItem(string $slug = 'peliculas', string $name = 'Películas'): Item
    {
        return $this->createItem($this->createCategory($slug, $name));
    }

    private function commentOn(Item $item, User $author, array $attrs = []): ItemComment
    {
        return ItemComment::factory()->create(array_merge(['item_id' => $item->id, 'user_id' => $author->id], $attrs));
    }

    private function postComment(Item $item, array $payload): TestResponse
    {
        return $this->postJson("/api/items/{$item->id}/comments", $payload);
    }

    public function test_responde_a_un_comentario_principal(): void
    {
        $item = $this->newItem();
        $parent = $this->commentOn($item, User::factory()->create());
        $this->actingAsUser();

        $this->postComment($item, ['content' => 'Totalmente de acuerdo', 'parent_id' => $parent->id])
            ->assertStatus(201)
            ->assertJsonPath('data.parent_id', $parent->id)
            ->assertJsonPath('data.is_review', false);

        $this->assertDatabaseHas('item_comments', ['content' => 'Totalmente de acuerdo', 'parent_id' => $parent->id]);
    }

    public function test_un_tercer_nivel_da_422(): void
    {
        $item = $this->newItem();
        $parent = $this->commentOn($item, User::factory()->create());
        $reply = $this->commentOn($item, User::factory()->create(), ['parent_id' => $parent->id]);
        $this->actingAsUser();

        $this->postComment($item, ['content' => 'Una respuesta a la respuesta', 'parent_id' => $reply->id])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'validation_error')
            ->assertJsonStructure(['error' => ['details' => ['parent_id']]]);

        $this->assertDatabaseCount('item_comments', 2);
    }

    public function test_un_padre_de_otro_item_da_422(): void
    {
        $item = $this->newItem();
        $other = $this->newItem('series', 'Series');
        $parent = $this->commentOn($other, User::factory()->create());
        $this->actingAsUser();

        $this->postComment($item, ['content' => 'Respuesta cruzada', 'parent_id' => $parent->id])
            ->assertStatus(422)
            ->assertJsonStructure(['error' => ['details' => ['parent_id']]]);

        $this->assertDatabaseCount('item_comments', 1);
    }

    public function test_un_padre_inexistente_o_borrado_da_422(): void
    {
        $item = $this->newItem();
        $deleted = $this->commentOn($item, User::factory()->create());
        $deleted->delete();
        $this->actingAsUser();

        $this->postComment($item, ['content' => 'Respuesta al vacío', 'parent_id' => (string) Str::uuid()])
            ->assertStatus(422)
            ->assertJsonStructure(['error' => ['details' => ['parent_id']]]);

        $this->postComment($item, ['content' => 'Respuesta a un borrado', 'parent_id' => $deleted->id])
            ->assertStatus(422)
            ->assertJsonStructure(['error' => ['details' => ['parent_id']]]);

        $this->postComment($item, ['content' => 'Respuesta mal formada', 'parent_id' => 'no-es-uuid'])
            ->assertStatus(422)
            ->assertJsonStructure(['error' => ['details' => ['parent_id']]]);
    }

    public function test_no_se_puede_responder_a_un_comentario_oculto(): void
    {
        $item = $this->newItem();
        $parent = $this->commentOn($item, User::factory()->create(), ['is_hidden' => true]);
        $this->actingAsUser();

        $this->postComment($item, ['content' => 'Respuesta a un oculto', 'parent_id' => $parent->id])
            ->assertStatus(422)
            ->assertJsonStructure(['error' => ['details' => ['parent_id']]]);
    }

    public function test_una_respuesta_no_puede_ser_resena(): void
    {
        $item = $this->newItem();
        $parent = $this->commentOn($item, User::factory()->create());
        $user = $this->actingAsUser();
        Vote::factory()->forItem($item)->byUser($user)->rated(8.5)->create();

        $this->postComment($item, ['content' => 'Respuesta con reseña', 'parent_id' => $parent->id, 'is_review' => true])
            ->assertStatus(422)
            ->assertJsonStructure(['error' => ['details' => ['is_review']]]);

        $this->assertDatabaseCount('item_comments', 1);
    }

    public function test_notifica_al_autor_del_comentario_padre(): void
    {
        Notification::fake();
        $item = $this->newItem();
        $parentAuthor = User::factory()->create();
        $parent = $this->commentOn($item, $parentAuthor);
        $replier = $this->actingAsUser();

        $replyId = $this->postComment($item, ['content' => 'Te respondo', 'parent_id' => $parent->id])
            ->assertStatus(201)
            ->json('data.id');

        Notification::assertSentTo($parentAuthor, CommentRepliedNotification::class, fn ($n) => $n->reply->id === $replyId);
        Notification::assertNotSentTo($replier, CommentRepliedNotification::class);
    }

    public function test_no_notifica_al_responderse_a_si_mismo(): void
    {
        Notification::fake();
        $item = $this->newItem();
        $user = $this->actingAsUser();
        $parent = $this->commentOn($item, $user);

        $this->postComment($item, ['content' => 'Me respondo a mí mismo', 'parent_id' => $parent->id])
            ->assertStatus(201);

        Notification::assertNothingSent();
    }

    public function test_la_notificacion_de_respuesta_se_guarda_en_la_base_de_datos(): void
    {
        $item = $this->newItem();
        $parentAuthor = User::factory()->create();
        $parent = $this->commentOn($item, $parentAuthor);
        $this->actingAsUser();

        $replyId = $this->postComment($item, ['content' => 'Te respondo', 'parent_id' => $parent->id])
            ->assertStatus(201)
            ->json('data.id');

        $this->assertSame(1, $parentAuthor->notifications()->count());
        $data = $parentAuthor->notifications()->first()->data;
        $this->assertSame('comment_replied', $data['kind']);
        $this->assertSame($replyId, $data['comment_id']);
    }

    public function test_el_listado_anida_las_respuestas(): void
    {
        $item = $this->newItem();
        $author = User::factory()->create();
        $parent = $this->commentOn($item, $author, ['created_at' => now()->subHours(3)]);
        $this->commentOn($item, $author, ['created_at' => now()->subHours(2)]);
        $first = $this->commentOn($item, $author, ['parent_id' => $parent->id, 'created_at' => now()->subHour()]);
        $second = $this->commentOn($item, $author, ['parent_id' => $parent->id, 'created_at' => now()->subMinutes(10)]);

        $response = $this->getJson("/api/items/{$item->id}/comments")
            ->assertOk()
            ->assertJsonPath('meta.total', 2)
            ->assertJsonCount(2, 'data');

        // Más reciente primero: el segundo principal; el padre con respuestas queda el último.
        $this->assertSame([], $response->json('data.0.replies'));
        $this->assertSame($parent->id, $response->json('data.1.id'));
        $this->assertSame([$first->id, $second->id], array_column($response->json('data.1.replies'), 'id'));
        $this->assertArrayNotHasKey('replies', $response->json('data.1.replies.0'));
    }

    public function test_las_respuestas_ocultas_solo_las_ven_su_autor_y_el_admin(): void
    {
        $item = $this->newItem();
        $parent = $this->commentOn($item, User::factory()->create());
        $replyAuthor = User::factory()->create(['email_verified_at' => now()]);
        $hidden = $this->commentOn($item, $replyAuthor, ['parent_id' => $parent->id, 'is_hidden' => true]);
        $url = "/api/items/{$item->id}/comments";

        $ids = fn (TestResponse $r) => array_column($r->json('data.0.replies'), 'id');

        // Invitado.
        $this->assertSame([], $ids($this->getJson($url)->assertOk()));

        // Tercero.
        $this->actingAsUser();
        $this->assertSame([], $ids($this->getJson($url)->assertOk()));

        // Su autor.
        Sanctum::actingAs($replyAuthor);
        $this->assertSame([$hidden->id], $ids($this->getJson($url)->assertOk()));

        // Admin.
        $this->actingAsAdmin();
        $this->assertSame([$hidden->id], $ids($this->getJson($url)->assertOk()));
    }
}
