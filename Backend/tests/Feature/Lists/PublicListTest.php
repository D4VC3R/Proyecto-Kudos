<?php

namespace Tests\Feature\Lists;

use App\Models\Category;
use App\Models\Item;
use App\Models\ListItem;
use App\Models\UserList;
use App\Models\Vote;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\CreatesItems;
use Tests\TestCase;

/**
 * Página pública de una lista (F2-09): GET /api/lists/{slug}.
 */
class PublicListTest extends TestCase
{
    use CreatesItems, RefreshDatabase;

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

    public function test_muestra_una_lista_publica_por_slug_con_sus_items_en_orden(): void
    {
        $category = $this->createCategory();
        $list = UserList::factory()->editorial()->create(['title' => 'Mi selección', 'description' => 'Descripción', 'is_ranked' => true]);
        $first = $this->createItem($category, ['name' => 'Primera']);
        $second = $this->createItem($category, ['name' => 'Segunda']);
        // Se inserta al revés para comprobar que manda position
        $this->addEntry($list, $second, 2, 'Nota de la segunda');
        $this->addEntry($list, $first, 1);

        $response = $this->getJson('/api/lists/mi-seleccion')
            ->assertOk()
            ->assertJsonPath('data.title', 'Mi selección')
            ->assertJsonPath('data.slug', 'mi-seleccion')
            ->assertJsonPath('data.is_ranked', true)
            ->assertJsonPath('data.items_count', 2);

        $this->assertSame([$first->id, $second->id], $response->json('data.items.*.item.id'));
        $this->assertSame([1, 2], $response->json('data.items.*.position'));
        $this->assertNull($response->json('data.items.0.note'));
        $this->assertSame('Nota de la segunda', $response->json('data.items.1.note'));
        $response->assertJsonPath('data.items.0.item.category.slug', $category->slug);
    }

    public function test_oculta_items_inactivos_borrados_y_de_categorias_inactivas(): void
    {
        $category = $this->createCategory();
        $inactiveCategory = Category::factory()->create(['slug' => 'ocultas', 'name' => 'Ocultas', 'is_active' => false]);
        $list = UserList::factory()->editorial()->create(['title' => 'Filtrada']);

        $ok = $this->createItem($category, ['name' => 'Visible']);
        $inactive = $this->createItem($category, ['name' => 'Inactivo', 'status' => Item::STATUS_INACTIVE]);
        $deleted = $this->createItem($category, ['name' => 'Borrado']);
        $hidden = $this->createItem($inactiveCategory, ['name' => 'Categoría oculta']);
        $deleted->delete();

        foreach ([$inactive, $ok, $deleted, $hidden] as $index => $item) {
            $this->addEntry($list, $item, $index + 1);
        }

        $this->getJson("/api/lists/{$list->slug}")
            ->assertOk()
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.item.id', $ok->id);
    }

    public function test_una_lista_privada_da_404_y_una_no_listada_se_ve(): void
    {
        $private = UserList::factory()->create(['title' => 'Privada', 'visibility' => UserList::VISIBILITY_PRIVATE]);
        $unlisted = UserList::factory()->create(['title' => 'No listada', 'visibility' => UserList::VISIBILITY_UNLISTED]);

        $this->getJson("/api/lists/{$private->slug}")->assertNotFound();
        $this->getJson("/api/lists/{$unlisted->slug}")->assertOk()->assertJsonPath('data.visibility', 'unlisted');
    }

    public function test_un_slug_inexistente_da_404(): void
    {
        $this->getJson('/api/lists/no-existe')->assertNotFound();
    }

    public function test_con_sesion_los_items_llevan_el_voto_del_usuario(): void
    {
        $category = $this->createCategory();
        $list = UserList::factory()->editorial()->create(['title' => 'Con voto']);
        $voted = $this->createItem($category, ['name' => 'Votada']);
        $free = $this->createItem($category, ['name' => 'Libre']);
        $this->addEntry($list, $voted, 1);
        $this->addEntry($list, $free, 2);

        // Sin sesión: sin voto y no se puede votar
        $this->getJson("/api/lists/{$list->slug}")
            ->assertOk()
            ->assertJsonMissingPath('data.items.0.item.user_vote')
            ->assertJsonPath('data.items.0.item.can_vote', false);

        $user = $this->actingAsUser();
        Vote::factory()->byUser($user)->forItem($voted)->rated(7.0)->create();

        $this->getJson("/api/lists/{$list->slug}")
            ->assertOk()
            ->assertJsonPath('data.items.0.item.user_vote.score', 7)
            ->assertJsonPath('data.items.0.item.can_vote', false)
            ->assertJsonMissingPath('data.items.1.item.user_vote')
            ->assertJsonPath('data.items.1.item.can_vote', true);
    }
}
