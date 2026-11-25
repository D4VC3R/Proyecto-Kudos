<?php

namespace Tests\Feature\Items;

use App\Models\ItemComment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesItems;
use Tests\Concerns\InteractsWithUsers;
use Tests\TestCase;

/**
 * can_edit y can_delete de ítems y comentarios salen de las policies.
 */
class PermissionFlagsTest extends TestCase
{
    use CreatesItems, InteractsWithUsers, RefreshDatabase;

    private function commentBy(User $author, $item, array $attrs = []): ItemComment
    {
        return ItemComment::factory()->create(array_merge([
            'item_id' => $item->id,
            'user_id' => $author->id,
            'content' => 'Un comentario',
        ], $attrs));
    }

    public function test_can_delete_de_un_comentario_es_false_para_su_autor_que_no_es_admin(): void
    {
        $item = $this->createItem($this->createCategory());
        $author = $this->actingAsUser();
        $this->commentBy($author, $item);

        $this->getJson("/api/items/{$item->id}/comments")
            ->assertOk()
            ->assertJsonPath('data.0.can_delete', false)
            ->assertJsonPath('data.0.can_edit', true);
    }

    public function test_can_edit_de_un_comentario_oculto_es_false_para_su_autor(): void
    {
        $item = $this->createItem($this->createCategory());
        $author = $this->actingAsUser();
        $this->commentBy($author, $item, ['is_hidden' => true]);

        $this->getJson("/api/items/{$item->id}/comments")
            ->assertOk()
            ->assertJsonPath('data.0.can_edit', false);
    }

    public function test_el_admin_puede_editar_y_borrar_cualquier_comentario(): void
    {
        $item = $this->createItem($this->createCategory());
        $author = User::factory()->create();
        $this->commentBy($author, $item);
        $this->actingAsAdmin();

        $this->getJson("/api/items/{$item->id}/comments")
            ->assertOk()
            ->assertJsonPath('data.0.can_edit', true)
            ->assertJsonPath('data.0.can_delete', true);
    }

    public function test_can_edit_de_un_item_es_false_para_su_creador_que_no_es_admin(): void
    {
        $category = $this->createCategory();
        $creator = $this->actingAsUser();
        $item = $this->createItem($category, ['creator_id' => $creator->id]);

        $this->getJson('/api/items')
            ->assertOk()
            ->assertJsonPath('data.0.can_edit', false)
            ->assertJsonPath('data.0.can_delete', false);

        $this->getJson("/api/items/{$item->id}")
            ->assertOk()
            ->assertJsonPath('data.can_edit', false)
            ->assertJsonPath('data.can_delete', false);
    }

    public function test_el_admin_tiene_can_edit_y_can_delete_en_los_items(): void
    {
        $category = $this->createCategory();
        $item = $this->createItem($category);
        $this->actingAsAdmin();

        $this->getJson('/api/items')
            ->assertOk()
            ->assertJsonPath('data.0.can_edit', true)
            ->assertJsonPath('data.0.can_delete', true);

        $this->getJson("/api/items/{$item->id}")
            ->assertOk()
            ->assertJsonPath('data.can_edit', true)
            ->assertJsonPath('data.can_delete', true);
    }
}
