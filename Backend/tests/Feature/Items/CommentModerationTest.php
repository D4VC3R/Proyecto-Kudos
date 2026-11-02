<?php

namespace Tests\Feature\Items;

use App\Models\Item;
use App\Models\ItemComment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesItems;
use Tests\TestCase;

/**
 * Caracterización (F5-01): edición, borrado y moderación de comentarios.
 * No se fija la ausencia de aviso ni de registro al ocultar o borrar (BE-B2, F5-03),
 * ni `can_edit`/`can_delete` (BE-R13, F5-05).
 */
class CommentModerationTest extends TestCase
{
    use CreatesItems, RefreshDatabase;

    private Item $item;

    protected function setUp(): void
    {
        parent::setUp();

        $this->item = $this->createItem($this->createCategory());
    }

    private function commentBy(User $author, array $attrs = []): ItemComment
    {
        return ItemComment::factory()->create(array_merge([
            'item_id' => $this->item->id,
            'user_id' => $author->id,
            'content' => 'Comentario original',
        ], $attrs));
    }

    public function test_el_autor_edita_su_comentario(): void
    {
        $author = $this->actingAsUser();
        $comment = $this->commentBy($author);

        $this->putJson("/api/comments/{$comment->id}", ['content' => 'Texto editado'])
            ->assertOk()
            ->assertJsonPath('data.content', 'Texto editado');

        $this->assertDatabaseHas('item_comments', ['id' => $comment->id, 'content' => 'Texto editado']);
    }

    public function test_editar_un_comentario_ajeno_da_403(): void
    {
        $comment = $this->commentBy(User::factory()->create());
        $this->actingAsUser();

        $this->putJson("/api/comments/{$comment->id}", ['content' => 'Texto editado'])
            ->assertForbidden();

        $this->assertDatabaseHas('item_comments', ['id' => $comment->id, 'content' => 'Comentario original']);
    }

    public function test_el_autor_no_puede_editar_su_comentario_oculto(): void
    {
        $author = $this->actingAsUser();
        $comment = $this->commentBy($author, ['is_hidden' => true]);

        $this->putJson("/api/comments/{$comment->id}", ['content' => 'Texto editado'])
            ->assertForbidden();

        $this->assertDatabaseHas('item_comments', ['id' => $comment->id, 'content' => 'Comentario original']);
    }

    public function test_un_admin_puede_editar_cualquier_comentario(): void
    {
        $comment = $this->commentBy(User::factory()->create());
        $this->actingAsAdmin();

        $this->putJson("/api/comments/{$comment->id}", ['content' => 'Texto editado por moderación'])
            ->assertOk();

        $this->assertDatabaseHas('item_comments', ['id' => $comment->id, 'content' => 'Texto editado por moderación']);
    }

    public function test_editar_un_comentario_con_contenido_vacio_da_422(): void
    {
        $author = $this->actingAsUser();
        $comment = $this->commentBy($author);

        $this->putJson("/api/comments/{$comment->id}", ['content' => ''])
            ->assertStatus(422)
            ->assertJsonStructure(['error' => ['details' => ['content']]]);

        $this->assertDatabaseHas('item_comments', ['id' => $comment->id, 'content' => 'Comentario original']);
    }

    public function test_el_autor_no_puede_borrar_su_comentario(): void
    {
        // Fija ItemCommentPolicy::delete (solo admin, app/Policies/ItemCommentPolicy.php:197-200).
        $author = $this->actingAsUser();
        $comment = $this->commentBy($author);

        $this->deleteJson("/api/comments/{$comment->id}")->assertForbidden();

        $this->assertNotSoftDeleted($comment);
    }

    public function test_un_admin_borra_un_comentario(): void
    {
        $comment = $this->commentBy(User::factory()->create());
        $this->actingAsAdmin();

        $this->deleteJson("/api/comments/{$comment->id}")->assertOk();

        $this->assertSoftDeleted($comment);
    }

    public function test_un_admin_oculta_y_restaura_un_comentario(): void
    {
        $comment = $this->commentBy(User::factory()->create());
        $admin = $this->actingAsAdmin();

        $this->patchJson("/api/admin/comments/{$comment->id}/hide", ['reason' => 'Spam'])
            ->assertOk()
            ->assertJsonPath('data.is_hidden', true);

        $hidden = $comment->fresh();
        $this->assertTrue($hidden->is_hidden);
        $this->assertSame($admin->id, $hidden->hidden_by);
        $this->assertSame('Spam', $hidden->hidden_reason);

        $this->patchJson("/api/admin/comments/{$comment->id}/unhide")
            ->assertOk()
            ->assertJsonPath('data.is_hidden', false);

        $restored = $comment->fresh();
        $this->assertFalse($restored->is_hidden);
        $this->assertNull($restored->hidden_by);
        $this->assertNull($restored->hidden_reason);
    }

    public function test_un_usuario_normal_recibe_403_al_ocultar_o_restaurar(): void
    {
        $author = User::factory()->create();
        $visible = $this->commentBy($author);
        $hidden = $this->commentBy($author, ['is_hidden' => true]);
        $this->seedRolesAndPermissions();
        $this->actingAsUser();

        $this->patchJson("/api/admin/comments/{$visible->id}/hide", ['reason' => 'Spam'])
            ->assertForbidden()
            ->assertJsonPath('error.code', 'forbidden');

        $this->patchJson("/api/admin/comments/{$hidden->id}/unhide")
            ->assertForbidden()
            ->assertJsonPath('error.code', 'forbidden');

        $this->assertFalse($visible->fresh()->is_hidden);
        $this->assertTrue($hidden->fresh()->is_hidden);
    }

    public function test_un_usuario_baneado_no_puede_editar_su_comentario(): void
    {
        $author = $this->actingAsUser(['is_banned' => true, 'banned_until' => null]);
        $comment = $this->commentBy($author);

        $this->putJson("/api/comments/{$comment->id}", ['content' => 'Texto editado'])
            ->assertForbidden()
            ->assertJsonPath('error.code', 'forbidden');

        $this->assertDatabaseHas('item_comments', ['id' => $comment->id, 'content' => 'Comentario original']);
    }

    public function test_un_invitado_recibe_401_en_las_rutas_de_comentarios(): void
    {
        $comment = $this->commentBy(User::factory()->create());

        $routes = [
            ['PUT', "/api/comments/{$comment->id}", ['content' => 'Texto editado']],
            ['DELETE', "/api/comments/{$comment->id}", []],
            ['PATCH', "/api/admin/comments/{$comment->id}/hide", ['reason' => 'Spam']],
            ['PATCH', "/api/admin/comments/{$comment->id}/unhide", []],
        ];

        foreach ($routes as [$method, $uri, $payload]) {
            $this->json($method, $uri, $payload)
                ->assertUnauthorized()
                ->assertJsonPath('error.code', 'unauthenticated');
        }
    }
}
