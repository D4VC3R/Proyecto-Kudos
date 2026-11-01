<?php

namespace Tests\Feature\Lists;

use App\Models\Item;
use App\Models\ListItem;
use App\Models\User;
use App\Models\UserList;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\CreatesItems;
use Tests\Concerns\InteractsWithUsers;
use Tests\TestCase;

/**
 * CRUD de las listas propias del usuario (F3-01): /api/profile/lists.
 */
class OwnListCrudTest extends TestCase
{
    use CreatesItems, InteractsWithUsers, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
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

    public function test_sin_sesion_da_401_y_sin_verificar_403(): void
    {
        $this->getJson('/api/profile/lists')->assertStatus(401);
        $this->postJson('/api/profile/lists', ['title' => 'Una'])->assertStatus(401);

        $this->actingAsUser(['email_verified_at' => null]);

        $this->getJson('/api/profile/lists')->assertForbidden();
        $this->postJson('/api/profile/lists', ['title' => 'Una'])->assertForbidden();
        $this->assertDatabaseCount('lists', 0);
    }

    public function test_crea_una_lista_privada_por_defecto(): void
    {
        $user = $this->actingAsUser();

        $this->postJson('/api/profile/lists', ['title' => 'Mis favoritas', 'description' => 'Para el finde'])
            ->assertCreated()
            ->assertJsonPath('data.visibility', 'private')
            ->assertJsonPath('data.is_editorial', false)
            ->assertJsonPath('data.slug', 'mis-favoritas')
            ->assertJsonPath('data.items_count', 0)
            ->assertJsonPath('data.can_edit', true);

        $this->assertDatabaseHas('lists', [
            'title' => 'Mis favoritas',
            'user_id' => $user->id,
            'is_editorial' => false,
            'visibility' => 'private',
        ]);
    }

    public function test_crea_una_lista_con_la_visibilidad_elegida_y_rechaza_valores_invalidos(): void
    {
        $this->actingAsUser();

        $this->postJson('/api/profile/lists', ['title' => 'Con enlace', 'visibility' => 'unlisted'])
            ->assertCreated()
            ->assertJsonPath('data.visibility', 'unlisted');

        $this->postJson('/api/profile/lists', ['title' => 'Rara', 'visibility' => 'secreta'])
            ->assertStatus(422)
            ->assertJsonStructure(['error' => ['details' => ['visibility']]]);

        $this->assertDatabaseCount('lists', 1);
    }

    public function test_ignora_is_editorial_user_id_y_likes_count_al_crear(): void
    {
        $user = $this->actingAsUser();
        $other = User::factory()->create();

        $this->postJson('/api/profile/lists', [
            'title' => 'Intento de colarse',
            'is_editorial' => true,
            'user_id' => $other->id,
            'likes_count' => 99,
            'slug' => 'otro-slug',
        ])->assertCreated();

        $list = UserList::query()->firstOrFail();
        $this->assertFalse($list->is_editorial);
        $this->assertSame($user->id, $list->user_id);
        $this->assertSame(0, $list->likes_count);
        $this->assertSame('intento-de-colarse', $list->slug);
    }

    public function test_lista_solo_las_listas_propias_de_usuario(): void
    {
        $admin = $this->actingAsAdmin();
        $mine = UserList::factory()->create(['user_id' => $admin->id, 'title' => 'Mía']);
        UserList::factory()->editorial()->create(['user_id' => $admin->id, 'title' => 'Editorial del admin']);
        UserList::factory()->create(['user_id' => $admin->id, 'title' => 'Borrada'])->delete();
        UserList::factory()->create(['title' => 'De otro']);

        $this->getJson('/api/profile/lists')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $mine->id)
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('meta.max_lists', 50)
            ->assertJsonPath('meta.max_items', 100);
    }

    public function test_con_item_id_indica_que_listas_contienen_el_item(): void
    {
        $user = $this->actingAsUser();
        $category = $this->createCategory();
        $item = $this->createItem($category);
        $with = UserList::factory()->create(['user_id' => $user->id, 'title' => 'Con el ítem']);
        $without = UserList::factory()->create(['user_id' => $user->id, 'title' => 'Sin el ítem']);
        $this->addEntry($with, $item, 1);

        $response = $this->getJson("/api/profile/lists?item_id={$item->id}")->assertOk();
        $byId = collect($response->json('data'))->keyBy('id');
        $this->assertTrue($byId[$with->id]['contains_item']);
        $this->assertFalse($byId[$without->id]['contains_item']);

        $plain = $this->getJson('/api/profile/lists')->assertOk();
        $this->assertArrayNotHasKey('contains_item', $plain->json('data.0'));

        $this->getJson('/api/profile/lists?item_id=nada')->assertStatus(422);
    }

    public function test_el_dueno_edita_titulo_y_visibilidad_y_el_slug_no_cambia(): void
    {
        $user = $this->actingAsUser();
        $list = UserList::factory()->create(['user_id' => $user->id, 'title' => 'Título viejo']);
        $slug = $list->slug;

        $this->putJson("/api/profile/lists/{$list->id}", [
            'title' => 'Título nuevo',
            'visibility' => 'public',
            'is_ranked' => true,
            'description' => 'Descripción',
        ])
            ->assertOk()
            ->assertJsonPath('data.title', 'Título nuevo')
            ->assertJsonPath('data.visibility', 'public')
            ->assertJsonPath('data.is_ranked', true);

        $fresh = $list->fresh();
        $this->assertSame($slug, $fresh->slug);
        $this->assertSame('Título nuevo', $fresh->title);
        $this->assertSame('public', $fresh->visibility);

        $this->putJson("/api/profile/lists/{$list->id}", ['visibility' => 'secreta'])->assertStatus(422);
        $this->putJson("/api/profile/lists/{$list->id}", ['title' => ''])->assertStatus(422);
    }

    public function test_borrar_es_borrado_suave_y_despues_da_404(): void
    {
        $user = $this->actingAsUser();
        $list = UserList::factory()->create(['user_id' => $user->id]);

        $this->deleteJson("/api/profile/lists/{$list->id}")->assertOk();

        $this->assertSoftDeleted('lists', ['id' => $list->id]);
        $this->getJson("/api/profile/lists/{$list->id}")->assertNotFound();
    }

    public function test_anade_quita_y_reordena_items_de_varias_categorias(): void
    {
        $user = $this->actingAsUser();
        $list = UserList::factory()->create(['user_id' => $user->id]);
        $movie = $this->createItem($this->createCategory('peliculas', 'Películas'), ['name' => 'Peli']);
        $game = $this->createItem($this->createCategory('videojuegos', 'Videojuegos'), ['name' => 'Juego']);
        $book = $this->createItem($this->createCategory('libros', 'Libros'), ['name' => 'Libro']);

        foreach ([$movie, $game, $book] as $item) {
            $this->postJson("/api/profile/lists/{$list->id}/items", ['item_id' => $item->id])
                ->assertCreated();
        }

        $this->assertSame(3, $list->fresh()->items_count);
        $this->assertSame([1, 2, 3], ListItem::where('list_id', $list->id)->orderBy('position')->pluck('position')->all());

        $this->putJson("/api/profile/lists/{$list->id}/items/order", ['item_ids' => [$book->id, $movie->id, $game->id]])
            ->assertOk();

        $this->assertSame(
            [$book->id, $movie->id, $game->id],
            ListItem::where('list_id', $list->id)->orderBy('position')->pluck('item_id')->all()
        );

        $this->deleteJson("/api/profile/lists/{$list->id}/items/{$movie->id}")->assertOk();

        $this->assertSame(2, $list->fresh()->items_count);
        $this->assertSame(
            [$book->id => 1, $game->id => 2],
            ListItem::where('list_id', $list->id)->pluck('position', 'item_id')->all()
        );

        // Hay que enviar todos los ítems
        $this->putJson("/api/profile/lists/{$list->id}/items/order", ['item_ids' => [$book->id]])->assertStatus(422);
    }

    public function test_guarda_la_nota_de_un_item_y_rechaza_mas_de_280_caracteres(): void
    {
        $user = $this->actingAsUser();
        $list = UserList::factory()->create(['user_id' => $user->id]);
        $item = $this->createItem($this->createCategory());

        $this->postJson("/api/profile/lists/{$list->id}/items", ['item_id' => $item->id, 'note' => str_repeat('a', 280)])
            ->assertCreated();
        $this->postJson("/api/profile/lists/{$list->id}/items", ['item_id' => $item->id, 'note' => str_repeat('a', 281)])
            ->assertStatus(422);

        $this->patchJson("/api/profile/lists/{$list->id}/items/{$item->id}", ['note' => 'Mi nota'])
            ->assertOk()
            ->assertJsonPath('data.items.0.note', 'Mi nota');
        $this->assertSame('Mi nota', ListItem::where('list_id', $list->id)->first()->note);

        $this->patchJson("/api/profile/lists/{$list->id}/items/{$item->id}", ['note' => str_repeat('a', 281)])
            ->assertStatus(422)
            ->assertJsonStructure(['error' => ['details' => ['note']]]);
        $this->patchJson("/api/profile/lists/{$list->id}/items/{$item->id}", [])->assertStatus(422);

        $this->patchJson("/api/profile/lists/{$list->id}/items/{$item->id}", ['note' => null])->assertOk();
        $this->assertNull(ListItem::where('list_id', $list->id)->first()->note);
    }

    public function test_no_se_pueden_anadir_items_inactivos_borrados_o_repetidos(): void
    {
        $user = $this->actingAsUser();
        $list = UserList::factory()->create(['user_id' => $user->id]);
        $category = $this->createCategory();
        $active = $this->createItem($category);
        $inactive = $this->createItem($category, ['status' => Item::STATUS_INACTIVE]);
        $deleted = $this->createItem($category);
        $deleted->delete();

        $this->postJson("/api/profile/lists/{$list->id}/items", ['item_id' => $active->id])->assertCreated();

        foreach ([$active, $inactive, $deleted] as $item) {
            $this->postJson("/api/profile/lists/{$list->id}/items", ['item_id' => $item->id])
                ->assertStatus(422)
                ->assertJsonStructure(['error' => ['details' => ['item_id']]]);
        }

        $this->assertSame(1, $list->fresh()->items_count);
    }

    public function test_el_detalle_de_edicion_incluye_items_inactivos(): void
    {
        $user = $this->actingAsUser();
        $list = UserList::factory()->create(['user_id' => $user->id, 'visibility' => UserList::VISIBILITY_PUBLIC]);
        $category = $this->createCategory();
        $active = $this->createItem($category, ['name' => 'Activo']);
        $inactive = $this->createItem($category, ['name' => 'Inactivo', 'status' => Item::STATUS_INACTIVE]);
        $this->addEntry($list, $active, 1);
        $this->addEntry($list, $inactive, 2);

        $own = $this->getJson("/api/profile/lists/{$list->id}")->assertOk();
        $this->assertSame([$active->id, $inactive->id], $own->json('data.items.*.item.id'));

        $public = $this->getJson("/api/lists/{$list->slug}")->assertOk();
        $this->assertSame([$active->id], $public->json('data.items.*.item.id'));
    }
}
