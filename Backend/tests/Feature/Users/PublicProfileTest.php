<?php

namespace Tests\Feature\Users;

use App\Models\Category;
use App\Models\Item;
use App\Models\ItemComment;
use App\Models\User;
use App\Models\UserList;
use App\Models\Vote;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesItems;
use Tests\Concerns\InteractsWithUsers;
use Tests\TestCase;

/**
 * Perfil público (F3-03): GET /api/users/{username}.
 */
class PublicProfileTest extends TestCase
{
    use CreatesItems, InteractsWithUsers, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
    }

    private function profileUser(bool $public = true, string $username = 'ana_99'): User
    {
        $user = User::factory()->create(['name' => 'Ana', 'email_verified_at' => now()]);
        $user->forceFill(['username' => $username])->save();
        $user->profile->update(['is_public' => $public, 'biography' => 'Cinéfila', 'city' => 'Madrid']);

        return $user;
    }

    private function rate(User $user, Item $item, float $score): Vote
    {
        return Vote::factory()->forItem($item)->byUser($user)->rated($score)->create();
    }

    public function test_un_perfil_publico_se_ve_sin_sesion(): void
    {
        $user = $this->profileUser();
        $user->profile->update(['birthdate' => '1990-01-01', 'social_links' => ['https://example.test/ana'], 'interests' => []]);

        $this->getJson('/api/users/ana_99')
            ->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.username', 'ana_99')
            ->assertJsonPath('data.name', 'Ana')
            ->assertJsonPath('data.biography', 'Cinéfila')
            ->assertJsonPath('data.is_public', true)
            ->assertJsonPath('data.is_owner', false)
            ->assertJsonStructure(['data' => ['avatar', 'member_since', 'top_by_category', 'lists', 'reviews']])
            ->assertJsonMissingPath('data.email')
            ->assertJsonMissingPath('data.city')
            ->assertJsonMissingPath('data.birthdate')
            ->assertJsonMissingPath('data.social_links')
            ->assertJsonMissingPath('data.interests');
    }

    public function test_un_perfil_privado_da_404_a_terceros(): void
    {
        $this->profileUser(false);

        $this->getJson('/api/users/ana_99')
            ->assertNotFound()
            ->assertJsonPath('error.code', 'not_found');

        $this->actingAsUser();
        $this->getJson('/api/users/ana_99')
            ->assertNotFound()
            ->assertJsonPath('error.code', 'not_found');

        $this->actingAsAdmin();
        $this->getJson('/api/users/ana_99')
            ->assertNotFound()
            ->assertJsonPath('error.code', 'not_found');
    }

    public function test_un_perfil_privado_es_visible_para_su_dueno(): void
    {
        $owner = $this->profileUser(false);
        Sanctum::actingAs($owner);

        $this->getJson('/api/users/ana_99')
            ->assertOk()
            ->assertJsonPath('data.is_owner', true)
            ->assertJsonPath('data.is_public', false);
    }

    public function test_un_perfil_publico_sin_username_no_existe_y_un_nombre_inexistente_da_el_mismo_404(): void
    {
        $this->profileUser(false);
        $sinNombre = User::factory()->create();
        $sinNombre->profile->update(['is_public' => true]);

        $privado = $this->getJson('/api/users/ana_99')->assertNotFound();
        $inexistente = $this->getJson('/api/users/nadie')->assertNotFound();

        $this->assertSame($privado->json('error.message'), $inexistente->json('error.message'));
        $this->assertSame('not_found', $privado->json('error.code'));
        $this->assertSame('not_found', $inexistente->json('error.code'));
    }

    public function test_el_nombre_de_usuario_no_distingue_mayusculas(): void
    {
        $this->profileUser();

        $this->getJson('/api/users/Ana_99')
            ->assertOk()
            ->assertJsonPath('data.username', 'ana_99');
    }

    public function test_devuelve_el_top_5_por_categoria(): void
    {
        $user = $this->profileUser();
        $peliculas = $this->createCategory('peliculas', 'Películas');
        $libros = $this->createCategory('libros', 'Libros');
        $juegos = Category::factory()->create(['slug' => 'juegos', 'name' => 'Juegos', 'is_active' => false]);

        $scores = [10, 9, 8, 7, 6, 5];
        foreach ($scores as $score) {
            $this->rate($user, $this->createItem($peliculas, ['name' => "Peli {$score}"]), $score);
        }
        // Un want, un ítem inactivo y un ítem borrado no cuentan
        Vote::factory()->forItem($this->createItem($peliculas))->byUser($user)->create(['type' => Vote::TYPE_WANT, 'score' => null]);
        $this->rate($user, $this->createItem($peliculas, ['status' => Item::STATUS_INACTIVE]), 10);
        $borrado = $this->createItem($peliculas);
        $this->rate($user, $borrado, 10);
        $borrado->delete();

        $this->rate($user, $this->createItem($libros), 8);
        $this->rate($user, $this->createItem($libros), 6);
        $this->rate($user, $this->createItem($juegos), 9);

        $response = $this->getJson('/api/users/ana_99')->assertOk();

        $groups = $response->json('data.top_by_category');
        $this->assertCount(2, $groups);

        $byCategory = collect($groups)->keyBy('category.slug');
        $this->assertEquals([10, 9, 8, 7, 6], array_column($byCategory['peliculas']['items'], 'score'));
        $this->assertCount(5, $byCategory['peliculas']['items']);
        $this->assertCount(2, $byCategory['libros']['items']);
        $this->assertArrayNotHasKey('juegos', $byCategory->all());
    }

    public function test_solo_salen_las_listas_publicas_de_usuario(): void
    {
        $user = $this->profileUser();
        $public = UserList::factory()->create(['user_id' => $user->id, 'visibility' => UserList::VISIBILITY_PUBLIC]);
        UserList::factory()->create(['user_id' => $user->id, 'visibility' => UserList::VISIBILITY_UNLISTED]);
        UserList::factory()->create(['user_id' => $user->id, 'visibility' => UserList::VISIBILITY_PRIVATE]);
        UserList::factory()->editorial()->create(['user_id' => $user->id]);
        UserList::factory()->create(['user_id' => $user->id, 'visibility' => UserList::VISIBILITY_PUBLIC])->delete();

        $response = $this->getJson('/api/users/ana_99')->assertOk();

        $this->assertSame([$public->id], $response->json('data.lists.*.id'));
    }

    public function test_salen_las_resenas_recientes_sin_ocultas_ni_respuestas(): void
    {
        $user = $this->profileUser();
        $category = $this->createCategory();
        $reviewed = $this->createItem($category, ['name' => 'Reseñada']);
        $hiddenItem = $this->createItem($category, ['name' => 'Oculta']);
        $plainItem = $this->createItem($category, ['name' => 'Solo comentario']);

        $review = ItemComment::factory()->create([
            'item_id' => $reviewed->id, 'user_id' => $user->id, 'content' => 'Genial',
            'vote_id' => $this->rate($user, $reviewed, 9)->id,
        ]);
        ItemComment::factory()->create([
            'item_id' => $hiddenItem->id, 'user_id' => $user->id, 'is_hidden' => true,
            'vote_id' => $this->rate($user, $hiddenItem, 4)->id,
        ]);
        $plain = ItemComment::factory()->create(['item_id' => $plainItem->id, 'user_id' => $user->id]);
        ItemComment::factory()->create([
            'item_id' => $plainItem->id, 'user_id' => $user->id, 'parent_id' => $plain->id,
            'vote_id' => $this->rate($user, $plainItem, 5)->id,
        ]);

        $response = $this->getJson('/api/users/ana_99')->assertOk();

        $this->assertSame([$review->id], $response->json('data.reviews.*.id'));
        $response->assertJsonPath('data.reviews.0.item.id', $reviewed->id)
            ->assertJsonPath('data.reviews.0.content', 'Genial');
        $this->assertEquals(9, $response->json('data.reviews.0.score'));
    }

    public function test_el_ranking_de_usuarios_sigue_respondiendo(): void
    {
        $this->getJson('/api/users/ranking')->assertOk();
    }
}
