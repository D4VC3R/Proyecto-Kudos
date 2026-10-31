<?php

namespace Tests\Feature\Lists;

use App\Models\Item;
use App\Models\ListItem;
use App\Models\UserList;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\CreatesItems;
use Tests\TestCase;

/**
 * Gestión de las listas editoriales por el admin (F2-09): /api/admin/lists.
 */
class EditorialListAdminTest extends TestCase
{
    use CreatesItems, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
    }

    private function editorialList(array $attrs = []): UserList
    {
        return UserList::factory()->editorial()->create($attrs);
    }

    private function addEntry(UserList $list, Item $item, int $position, ?string $note = null): ListItem
    {
        $entry = ListItem::factory()->create([
            'list_id' => $list->id,
            'item_id' => $item->id,
            'position' => $position,
            'note' => $note,
        ]);
        $list->update(['items_count' => $list->listItems()->count()]);

        return $entry;
    }

    /**
     * Lista editorial con $count ítems en orden. Devuelve [lista, ítems].
     *
     * @return array{0: UserList, 1: Collection<int, Item>}
     */
    private function listWithItems(int $count): array
    {
        $category = $this->createCategory();
        $list = $this->editorialList(['title' => 'Mi lista']);
        $items = collect(range(1, $count))->map(fn (int $i) => $this->createItem($category, ['name' => "Ítem {$i}"]));
        foreach ($items as $index => $item) {
            $this->addEntry($list, $item, $index + 1);
        }

        return [$list->fresh(), $items];
    }

    public function test_un_invitado_recibe_401_y_un_usuario_sin_rol_403(): void
    {
        $this->getJson('/api/admin/lists')->assertUnauthorized();
        $this->postJson('/api/admin/lists', ['title' => 'X'])->assertUnauthorized();

        $this->actingAsUser();

        $this->getJson('/api/admin/lists')->assertForbidden();
        $this->postJson('/api/admin/lists', ['title' => 'X'])->assertForbidden();
    }

    public function test_el_admin_crea_una_lista_editorial_publica(): void
    {
        $admin = $this->actingAsAdmin();

        $response = $this->postJson('/api/admin/lists', [
            'title' => 'Cine de los 80',
            'description' => 'Lo mejor de la década.',
            'is_ranked' => true,
            // Se ignoran: los fija el servidor
            'visibility' => 'private',
            'is_editorial' => false,
            'user_id' => 'otro',
            'slug' => 'otro-slug',
        ])->assertCreated();

        $response->assertJsonPath('data.is_editorial', true)
            ->assertJsonPath('data.visibility', 'public')
            ->assertJsonPath('data.slug', 'cine-de-los-80')
            ->assertJsonPath('data.is_ranked', true)
            ->assertJsonPath('data.items_count', 0);

        $this->assertDatabaseHas('lists', [
            'id' => $response->json('data.id'),
            'user_id' => $admin->id,
            'is_editorial' => true,
            'visibility' => 'public',
        ]);
    }

    public function test_el_slug_es_unico_aunque_se_repita_el_titulo(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/api/admin/lists', ['title' => 'Imprescindibles'])->assertCreated()->assertJsonPath('data.slug', 'imprescindibles');
        $this->postJson('/api/admin/lists', ['title' => 'Imprescindibles'])->assertCreated()->assertJsonPath('data.slug', 'imprescindibles-2');
    }

    public function test_crear_sin_titulo_o_con_mas_de_120_caracteres_da_422(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/api/admin/lists', ['description' => 'Sin título'])->assertUnprocessable()->assertJsonStructure(['error' => ['details' => ['title']]]);
        $this->postJson('/api/admin/lists', ['title' => str_repeat('a', 121)])->assertUnprocessable()->assertJsonStructure(['error' => ['details' => ['title']]]);
        $this->postJson('/api/admin/lists', ['title' => str_repeat('a', 120)])->assertCreated();
    }

    public function test_editar_cambia_titulo_y_descripcion_pero_no_el_slug(): void
    {
        $this->actingAsAdmin();
        $list = $this->editorialList(['title' => 'Título viejo']);
        $slug = $list->slug;

        $this->putJson("/api/admin/lists/{$list->id}", ['title' => 'Título nuevo', 'description' => 'Nueva', 'is_ranked' => true])
            ->assertOk()
            ->assertJsonPath('data.title', 'Título nuevo')
            ->assertJsonPath('data.description', 'Nueva')
            ->assertJsonPath('data.is_ranked', true)
            ->assertJsonPath('data.slug', $slug);

        $this->putJson("/api/admin/lists/{$list->id}", ['title' => ''])->assertUnprocessable();
    }

    public function test_borrar_una_lista_la_oculta_del_admin_y_de_la_ruta_publica(): void
    {
        $this->actingAsAdmin();
        $list = $this->editorialList(['title' => 'Para borrar']);

        $this->getJson("/api/lists/{$list->slug}")->assertOk();

        $this->deleteJson("/api/admin/lists/{$list->id}")->assertOk();

        $this->assertSoftDeleted('lists', ['id' => $list->id]);
        $this->getJson("/api/lists/{$list->slug}")->assertNotFound();
        $this->getJson('/api/admin/lists')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson("/api/admin/lists/{$list->id}")->assertNotFound();
    }

    public function test_anade_items_al_final_y_actualiza_items_count(): void
    {
        $this->actingAsAdmin();
        $category = $this->createCategory();
        $list = $this->editorialList();
        $items = collect(range(1, 3))->map(fn (int $i) => $this->createItem($category, ['name' => "Ítem {$i}"]));

        foreach ($items as $index => $item) {
            $response = $this->postJson("/api/admin/lists/{$list->id}/items", ['item_id' => $item->id, 'note' => "Nota {$index}"])
                ->assertCreated()
                ->assertJsonPath('data.items_count', $index + 1);
        }

        $this->assertSame($items->pluck('id')->all(), $response->json('data.items.*.item.id'));
        $this->assertSame([1, 2, 3], $response->json('data.items.*.position'));
        $this->assertSame('Nota 1', $response->json('data.items.1.note'));
        $this->assertSame(3, $list->fresh()->items_count);
        $this->assertSame([1, 2, 3], ListItem::where('list_id', $list->id)->orderBy('position')->pluck('position')->all());
    }

    public function test_no_se_puede_anadir_dos_veces_el_mismo_item(): void
    {
        $this->actingAsAdmin();
        [$list, $items] = $this->listWithItems(1);

        $this->postJson("/api/admin/lists/{$list->id}/items", ['item_id' => $items[0]->id])
            ->assertUnprocessable()
            ->assertJsonStructure(['error' => ['details' => ['item_id']]]);

        $this->assertSame(1, ListItem::where('list_id', $list->id)->count());
    }

    public function test_no_se_puede_anadir_un_item_borrado_o_inexistente(): void
    {
        $this->actingAsAdmin();
        $list = $this->editorialList();
        $deleted = $this->createItem($this->createCategory());
        $deleted->delete();

        $this->postJson("/api/admin/lists/{$list->id}/items", ['item_id' => $deleted->id])->assertUnprocessable()->assertJsonStructure(['error' => ['details' => ['item_id']]]);
        $this->postJson("/api/admin/lists/{$list->id}/items", ['item_id' => '8f14e45f-ceea-467a-9575-a1b2c3d4e5f6'])->assertUnprocessable()->assertJsonStructure(['error' => ['details' => ['item_id']]]);
        $this->postJson("/api/admin/lists/{$list->id}/items", ['item_id' => 'no-es-uuid'])->assertUnprocessable()->assertJsonStructure(['error' => ['details' => ['item_id']]]);
    }

    public function test_quitar_un_item_renumera_las_posiciones(): void
    {
        $this->actingAsAdmin();
        [$list, $items] = $this->listWithItems(3);

        $response = $this->deleteJson("/api/admin/lists/{$list->id}/items/{$items[1]->id}")
            ->assertOk()
            ->assertJsonPath('data.items_count', 2);

        $this->assertSame([$items[0]->id, $items[2]->id], $response->json('data.items.*.item.id'));
        $this->assertSame([1, 2], $response->json('data.items.*.position'));
        $this->assertSame(2, $list->fresh()->items_count);
    }

    public function test_quitar_un_item_que_no_esta_en_la_lista_da_404(): void
    {
        $this->actingAsAdmin();
        [$list] = $this->listWithItems(1);
        $other = $this->createItem($this->createCategory('series', 'Series'));

        $this->deleteJson("/api/admin/lists/{$list->id}/items/{$other->id}")->assertNotFound();
    }

    public function test_reordena_los_items(): void
    {
        $this->actingAsAdmin();
        [$list, $items] = $this->listWithItems(3);

        $order = [$items[2]->id, $items[0]->id, $items[1]->id];
        $response = $this->putJson("/api/admin/lists/{$list->id}/items/order", ['item_ids' => $order])->assertOk();

        $this->assertSame($order, $response->json('data.items.*.item.id'));
        $this->assertSame([1, 2, 3], $response->json('data.items.*.position'));
        $this->assertSame($order, ListItem::where('list_id', $list->id)->orderBy('position')->pluck('item_id')->all());
    }

    public function test_reordenar_con_ids_que_no_coinciden_da_422(): void
    {
        $this->actingAsAdmin();
        [$list, $items] = $this->listWithItems(3);
        $foreign = $this->createItem($this->createCategory('series', 'Series'));

        // Uno de menos
        $this->putJson("/api/admin/lists/{$list->id}/items/order", ['item_ids' => [$items[0]->id, $items[1]->id]])
            ->assertUnprocessable()->assertJsonStructure(['error' => ['details' => ['item_ids']]]);
        // Uno de más (ajeno)
        $this->putJson("/api/admin/lists/{$list->id}/items/order", ['item_ids' => [$items[0]->id, $items[1]->id, $items[2]->id, $foreign->id]])
            ->assertUnprocessable()->assertJsonStructure(['error' => ['details' => ['item_ids']]]);
        // Uno repetido
        $this->putJson("/api/admin/lists/{$list->id}/items/order", ['item_ids' => [$items[0]->id, $items[0]->id, $items[1]->id]])
            ->assertUnprocessable()->assertJsonStructure(['error' => ['details' => ['item_ids.1']]]);

        $this->assertSame($items->pluck('id')->all(), ListItem::where('list_id', $list->id)->orderBy('position')->pluck('item_id')->all());
    }

    public function test_no_gestiona_listas_que_no_son_editoriales(): void
    {
        $this->actingAsAdmin();
        $userList = UserList::factory()->create(['title' => 'De un usuario', 'visibility' => UserList::VISIBILITY_PUBLIC]);
        $item = $this->createItem($this->createCategory());

        $this->getJson("/api/admin/lists/{$userList->id}")->assertForbidden();
        $this->putJson("/api/admin/lists/{$userList->id}", ['title' => 'Cambiado'])->assertForbidden();
        $this->deleteJson("/api/admin/lists/{$userList->id}")->assertForbidden();
        $this->postJson("/api/admin/lists/{$userList->id}/items", ['item_id' => $item->id])->assertForbidden();

        $this->assertSame('De un usuario', $userList->fresh()->title);
    }

    public function test_el_listado_del_admin_solo_devuelve_las_editoriales(): void
    {
        $this->actingAsAdmin();
        $editorial = $this->editorialList(['title' => 'Editorial']);
        UserList::factory()->create(['title' => 'De usuario']);

        $this->getJson('/api/admin/lists')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $editorial->id);
    }
}
