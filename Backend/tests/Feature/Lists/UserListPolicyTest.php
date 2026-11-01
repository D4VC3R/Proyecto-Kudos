<?php

namespace Tests\Feature\Lists;

use App\Models\ListItem;
use App\Models\User;
use App\Models\UserList;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesItems;
use Tests\Concerns\InteractsWithUsers;
use Tests\TestCase;

/**
 * Permisos de las listas (F3-01): quién gestiona, quién ve y a quién se puede dar "me gusta".
 */
class UserListPolicyTest extends TestCase
{
    use CreatesItems, InteractsWithUsers, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
    }

    public function test_solo_el_dueno_puede_editar_borrar_y_gestionar_items(): void
    {
        $owner = User::factory()->create();
        $list = UserList::factory()->create(['user_id' => $owner->id, 'title' => 'Original']);
        $item = $this->createItem($this->createCategory());
        $other = $this->createItem($this->createCategory('libros', 'Libros'));
        $entry = ListItem::factory()->create(['list_id' => $list->id, 'item_id' => $item->id, 'position' => 1]);
        $list->update(['items_count' => 1]);
        $admin = $this->actingAsAdmin();

        $actors = [
            'otro usuario' => fn () => $this->actingAsUser(),
            'admin ajeno' => fn () => Sanctum::actingAs($admin),
        ];

        foreach ($actors as $label => $actAs) {
            $actAs();

            $this->getJson("/api/profile/lists/{$list->id}")->assertForbidden();
            $this->putJson("/api/profile/lists/{$list->id}", ['title' => 'Hackeada'])->assertForbidden();
            $this->deleteJson("/api/profile/lists/{$list->id}")->assertForbidden();
            $this->postJson("/api/profile/lists/{$list->id}/items", ['item_id' => $other->id])->assertForbidden();
            $this->patchJson("/api/profile/lists/{$list->id}/items/{$item->id}", ['note' => 'x'])->assertForbidden();
            $this->putJson("/api/profile/lists/{$list->id}/items/order", ['item_ids' => [$item->id]])->assertForbidden();
            $this->deleteJson("/api/profile/lists/{$list->id}/items/{$item->id}")->assertForbidden();
        }

        $fresh = $list->fresh();
        $this->assertSame('Original', $fresh->title);
        $this->assertNull($fresh->deleted_at);
        $this->assertSame(1, $fresh->items_count);
        $this->assertDatabaseHas('list_items', ['id' => $entry->id, 'note' => null]);
    }

    public function test_las_rutas_de_usuario_no_sirven_para_listas_editoriales(): void
    {
        $admin = $this->actingAsAdmin();
        $editorial = UserList::factory()->editorial()->create(['user_id' => $admin->id]);

        $this->putJson("/api/profile/lists/{$editorial->id}", ['visibility' => 'private'])->assertForbidden();
        $this->deleteJson("/api/profile/lists/{$editorial->id}")->assertForbidden();

        $this->assertSame('public', $editorial->fresh()->visibility);
        $this->assertNull($editorial->fresh()->deleted_at);
    }

    public function test_una_lista_de_usuario_no_se_gestiona_desde_el_panel_admin(): void
    {
        $owner = User::factory()->create();
        $list = UserList::factory()->create(['user_id' => $owner->id, 'title' => 'De usuario']);
        $this->actingAsAdmin();

        $this->putJson("/api/admin/lists/{$list->id}", ['title' => 'Cambiada'])->assertForbidden();

        $this->assertSame('De usuario', $list->fresh()->title);
    }

    public function test_una_lista_privada_da_404_a_terceros_y_sin_sesion(): void
    {
        $list = UserList::factory()->create(['visibility' => UserList::VISIBILITY_PRIVATE]);

        $this->getJson("/api/lists/{$list->slug}")->assertNotFound();

        $this->actingAsUser();
        $this->getJson("/api/lists/{$list->slug}")->assertNotFound();
    }

    public function test_el_dueno_ve_su_lista_privada_por_slug(): void
    {
        $owner = $this->actingAsUser();
        $list = UserList::factory()->create(['user_id' => $owner->id, 'visibility' => UserList::VISIBILITY_PRIVATE]);

        $this->getJson("/api/lists/{$list->slug}")
            ->assertOk()
            ->assertJsonPath('data.id', $list->id)
            ->assertJsonPath('data.can_edit', true);
    }

    public function test_una_lista_no_listada_se_abre_con_el_enlace_y_no_es_publicamente_visible(): void
    {
        $unlisted = UserList::factory()->create(['visibility' => UserList::VISIBILITY_UNLISTED]);
        $private = UserList::factory()->create(['visibility' => UserList::VISIBILITY_PRIVATE]);

        $response = $this->getJson("/api/lists/{$unlisted->slug}")->assertOk();
        $this->assertArrayNotHasKey('can_edit', $response->json('data'));
        $this->assertArrayNotHasKey('liked_by_me', $response->json('data'));

        $visible = UserList::publiclyVisible()->pluck('id')->all();
        $this->assertNotContains($unlisted->id, $visible);
        $this->assertNotContains($private->id, $visible);

        $home = $this->getJson('/api/home')->assertOk();
        $this->assertNotContains($unlisted->id, $home->json('data.editorial_lists.*.id') ?? []);
    }

    public function test_policy_view_manage_y_like(): void
    {
        $owner = User::factory()->create();
        $third = User::factory()->create();
        $public = UserList::factory()->create(['user_id' => $owner->id, 'visibility' => UserList::VISIBILITY_PUBLIC]);
        $unlisted = UserList::factory()->create(['user_id' => $owner->id, 'visibility' => UserList::VISIBILITY_UNLISTED]);
        $private = UserList::factory()->create(['user_id' => $owner->id, 'visibility' => UserList::VISIBILITY_PRIVATE]);
        $editorial = UserList::factory()->editorial()->create(['user_id' => $owner->id]);

        // view: invitado (null), tercero y dueño
        foreach ([$public, $unlisted, $editorial] as $list) {
            $this->assertTrue(Gate::forUser(null)->allows('view', $list));
            $this->assertTrue(Gate::forUser($third)->allows('view', $list));
            $this->assertTrue(Gate::forUser($owner)->allows('view', $list));
        }
        $this->assertFalse(Gate::forUser(null)->allows('view', $private));
        $this->assertFalse(Gate::forUser($third)->allows('view', $private));
        $this->assertTrue(Gate::forUser($owner)->allows('view', $private));

        // manage: solo el dueño y nunca las editoriales
        foreach ([$public, $unlisted, $private] as $list) {
            $this->assertTrue(Gate::forUser($owner)->allows('manage', $list));
            $this->assertFalse(Gate::forUser($third)->allows('manage', $list));
        }
        $this->assertFalse(Gate::forUser($owner)->allows('manage', $editorial));

        // like: lista visible y no propia
        foreach ([$public, $unlisted, $editorial] as $list) {
            $this->assertTrue(Gate::forUser($third)->allows('like', $list));
            $this->assertFalse(Gate::forUser($owner)->allows('like', $list));
        }
        $this->assertFalse(Gate::forUser($third)->allows('like', $private));
        $this->assertFalse(Gate::forUser($owner)->allows('like', $private));

        $this->assertTrue(Gate::forUser($third)->allows('createOwn', UserList::class));
    }
}
