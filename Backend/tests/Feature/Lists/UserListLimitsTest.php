<?php

namespace Tests\Feature\Lists;

use App\Models\UserList;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\CreatesItems;
use Tests\Concerns\InteractsWithUsers;
use Tests\TestCase;

/**
 * Límites de listas por usuario y de ítems por lista (F3-01), configurables en kudos.lists.*.
 */
class UserListLimitsTest extends TestCase
{
    use CreatesItems, InteractsWithUsers, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
    }

    public function test_los_limites_por_defecto_son_50_listas_y_100_items(): void
    {
        $this->assertSame(50, config('kudos.lists.max_per_user'));
        $this->assertSame(100, config('kudos.lists.max_items'));
    }

    public function test_no_se_pueden_crear_mas_listas_que_el_maximo(): void
    {
        $user = $this->actingAsUser();
        UserList::factory()->count(50)->create(['user_id' => $user->id]);

        $this->postJson('/api/profile/lists', ['title' => 'La número 51'])
            ->assertStatus(422)
            ->assertJsonPath('error.details.title.0', 'Has llegado al máximo de 50 listas. Borra alguna para crear otra.');

        $this->assertSame(50, UserList::query()->where('user_id', $user->id)->count());
    }

    public function test_las_listas_borradas_y_editoriales_no_cuentan_para_el_limite(): void
    {
        config(['kudos.lists.max_per_user' => 2]);
        $user = $this->actingAsUser();
        UserList::factory()->create(['user_id' => $user->id])->delete();
        UserList::factory()->editorial()->create(['user_id' => $user->id]);

        $this->postJson('/api/profile/lists', ['title' => 'Una'])->assertCreated();
        $this->postJson('/api/profile/lists', ['title' => 'Dos'])->assertCreated();
        $this->postJson('/api/profile/lists', ['title' => 'Tres'])->assertStatus(422);

        // Borrar una libera hueco
        $this->deleteJson('/api/profile/lists/'.UserList::query()->where('title', 'Una')->value('id'))->assertOk();
        $this->postJson('/api/profile/lists', ['title' => 'Tres'])->assertCreated();
    }

    public function test_no_se_pueden_anadir_mas_items_que_el_maximo(): void
    {
        config(['kudos.lists.max_items' => 3]);
        $user = $this->actingAsUser();
        $list = UserList::factory()->create(['user_id' => $user->id]);
        $category = $this->createCategory();

        foreach (range(1, 3) as $i) {
            $this->postJson("/api/profile/lists/{$list->id}/items", ['item_id' => $this->createItem($category)->id])
                ->assertCreated();
        }

        $this->postJson("/api/profile/lists/{$list->id}/items", ['item_id' => $this->createItem($category)->id])
            ->assertStatus(422)
            ->assertJsonStructure(['error' => ['details' => ['item_id']]]);

        $this->assertSame(3, $list->fresh()->items_count);
    }

    public function test_el_limite_de_items_tambien_vale_para_las_editoriales(): void
    {
        config(['kudos.lists.max_items' => 2]);
        $admin = $this->actingAsAdmin();
        $list = UserList::factory()->editorial()->create(['user_id' => $admin->id]);
        $category = $this->createCategory();

        foreach (range(1, 2) as $i) {
            $this->postJson("/api/admin/lists/{$list->id}/items", ['item_id' => $this->createItem($category)->id])
                ->assertCreated();
        }

        $this->postJson("/api/admin/lists/{$list->id}/items", ['item_id' => $this->createItem($category)->id])
            ->assertStatus(422)
            ->assertJsonStructure(['error' => ['details' => ['item_id']]]);

        $this->assertSame(2, $list->fresh()->items_count);
    }
}
