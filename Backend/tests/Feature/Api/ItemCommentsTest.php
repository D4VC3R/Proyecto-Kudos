<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Item;
use App\Models\ItemComment;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ItemCommentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_can_list_visible_comments_for_active_item(): void
    {
        /** @var User $author */
        $author = User::factory()->create();
        /** @var Category $category */
        $category = Category::factory()->create();
        /** @var Item $item */
        $item = Item::factory()->forCategory($category)->create([
            'status' => Item::STATUS_ACTIVE,
            'creator_id' => $author->id,
        ]);

        ItemComment::create([
            'item_id' => $item->id,
            'user_id' => $author->id,
            'content' => 'Comentario visible del item.',
            'is_hidden' => false,
        ]);

        ItemComment::create([
            'item_id' => $item->id,
            'user_id' => $author->id,
            'content' => 'Comentario oculto del item.',
            'is_hidden' => true,
        ]);

        $this->getJson("/api/items/{$item->id}/comments")
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.content', 'Comentario visible del item.');
    }

    public function test_authenticated_user_can_create_comment(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        /** @var Category $category */
        $category = Category::factory()->create();
        /** @var Item $item */
        $item = Item::factory()->forCategory($category)->create([
            'status' => Item::STATUS_ACTIVE,
            'creator_id' => $user->id,
        ]);

        Sanctum::actingAs($user);

        $this->postJson("/api/items/{$item->id}/comments", [
            'content' => 'Mi comentario inicial sobre este item.',
        ])
            ->assertStatus(201)
            ->assertJsonPath('data.content', 'Mi comentario inicial sobre este item.');

        $this->assertDatabaseHas('item_comments', [
            'item_id' => $item->id,
            'user_id' => $user->id,
            'content' => 'Mi comentario inicial sobre este item.',
        ]);
    }

    public function test_comment_requires_authentication(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        /** @var Category $category */
        $category = Category::factory()->create();
        /** @var Item $item */
        $item = Item::factory()->forCategory($category)->create([
            'status' => Item::STATUS_ACTIVE,
            'creator_id' => $user->id,
        ]);

        $this->postJson("/api/items/{$item->id}/comments", [
            'content' => 'Intento sin login.',
        ])
            ->assertStatus(401)
            ->assertJsonPath('error.code', 'unauthenticated');
    }

    public function test_only_owner_or_admin_can_update_comment(): void
    {
        /** @var User $owner */
        $owner = User::factory()->create();
        /** @var User $other */
        $other = User::factory()->create();

        /** @var Category $category */
        $category = Category::factory()->create();
        /** @var Item $item */
        $item = Item::factory()->forCategory($category)->create([
            'status' => Item::STATUS_ACTIVE,
            'creator_id' => $owner->id,
        ]);

        /** @var ItemComment $comment */
        $comment = ItemComment::create([
            'item_id' => $item->id,
            'user_id' => $owner->id,
            'content' => 'Contenido original.',
            'is_hidden' => false,
        ]);

        Sanctum::actingAs($other);

        $this->putJson("/api/comments/{$comment->id}", [
            'content' => 'Intento de editar comentario ajeno.',
        ])
            ->assertStatus(403)
            ->assertJsonPath('error.code', 'forbidden');

        Sanctum::actingAs($owner);

        $this->putJson("/api/comments/{$comment->id}", [
            'content' => 'Contenido actualizado por el autor.',
        ])
            ->assertOk()
            ->assertJsonPath('data.content', 'Contenido actualizado por el autor.');
    }

    public function test_admin_can_hide_and_unhide_comment(): void
    {
        /** @var User $author */
        $author = User::factory()->create();
        $admin = $this->createAdminUser();

        /** @var Category $category */
        $category = Category::factory()->create();
        /** @var Item $item */
        $item = Item::factory()->forCategory($category)->create([
            'status' => Item::STATUS_ACTIVE,
            'creator_id' => $author->id,
        ]);

        /** @var ItemComment $comment */
        $comment = ItemComment::create([
            'item_id' => $item->id,
            'user_id' => $author->id,
            'content' => 'Comentario a moderar.',
            'is_hidden' => false,
        ]);

        Sanctum::actingAs($admin);

        $this->patchJson("/api/admin/comments/{$comment->id}/hide", [
            'reason' => 'Incumple las normas de moderacion.',
        ])
            ->assertOk()
            ->assertJsonPath('data.is_hidden', true)
            ->assertJsonPath('data.hidden_reason', 'Incumple las normas de moderacion.');

        $this->getJson("/api/items/{$item->id}/comments")
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $comment->id);

        Sanctum::actingAs($author);

        $this->getJson("/api/items/{$item->id}/comments")
            ->assertOk()
            ->assertJsonPath('meta.total', 0);

        Sanctum::actingAs($admin);

        $this->patchJson("/api/admin/comments/{$comment->id}/unhide")
            ->assertOk()
            ->assertJsonPath('data.is_hidden', false);

        Sanctum::actingAs($author);

        $this->getJson("/api/items/{$item->id}/comments")
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $comment->id);
    }

    public function test_cannot_comment_on_inactive_item(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        /** @var Category $category */
        $category = Category::factory()->create();
        /** @var Item $item */
        $item = Item::factory()->forCategory($category)->create([
            'status' => Item::STATUS_INACTIVE,
            'creator_id' => $user->id,
        ]);

        Sanctum::actingAs($user);

        $this->postJson("/api/items/{$item->id}/comments", [
            'content' => 'No deberia poder comentar.',
        ])
            ->assertStatus(403)
            ->assertJsonPath('error.code', 'forbidden');
    }

    private function createAdminUser(): User
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Role::query()->firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'web',
        ]);

        /** @var User $admin */
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }
}

