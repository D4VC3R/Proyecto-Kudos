<?php

namespace Tests\Feature\Auth;

use App\Models\Item;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesItems;
use Tests\Concerns\InteractsWithUsers;
use Tests\TestCase;

/**
 * Sesión opcional en las rutas públicas, con tokens reales: Sanctum::actingAs() ya cambia
 * el guard por defecto a sanctum y taparía el fallo. Una sola petición con token por test.
 */
class OptionalAuthTest extends TestCase
{
    use CreatesItems, InteractsWithUsers, RefreshDatabase;

    public function test_un_admin_con_token_lista_los_comentarios_de_un_item_inactivo(): void
    {
        $this->seedRolesAndPermissions();
        $admin = User::factory()->create(['email_verified_at' => now(), 'is_banned' => false]);
        $admin->assignRole('admin');
        $item = $this->createItem($this->createCategory(), ['status' => Item::STATUS_INACTIVE]);

        $token = $admin->createToken('test')->plainTextToken;

        $this->withToken($token)->getJson("/api/items/{$item->id}/comments")->assertOk();
    }

    public function test_un_invitado_no_lista_los_comentarios_de_un_item_inactivo(): void
    {
        $item = $this->createItem($this->createCategory(), ['status' => Item::STATUS_INACTIVE]);

        $this->getJson("/api/items/{$item->id}/comments")->assertForbidden();
    }

    public function test_con_token_el_listado_de_items_trae_el_voto_del_usuario(): void
    {
        $user = User::factory()->create(['email_verified_at' => now(), 'is_banned' => false]);
        $item = $this->createItem($this->createCategory());
        Vote::factory()->byUser($user)->forItem($item)->rated(8)->create();

        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)->getJson('/api/items')
            ->assertOk()
            ->assertJsonPath('data.0.id', $item->id)
            ->assertJsonStructure(['data' => [['user_vote' => ['id']]]]);
    }

    public function test_con_token_el_ranking_de_usuarios_trae_mi_posicion(): void
    {
        $user = User::factory()->create(['email_verified_at' => now(), 'is_banned' => false]);

        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)->getJson('/api/users/ranking')
            ->assertOk()
            ->assertJsonPath('meta.my_position.user_id', $user->id);
    }

    public function test_un_token_no_valido_se_trata_como_invitado(): void
    {
        $this->createItem($this->createCategory());

        $this->withToken('1|no-valido')->getJson('/api/items')
            ->assertOk()
            ->assertJsonMissingPath('data.0.user_vote')
            ->assertJsonMissingPath('data.0.can_edit');
    }
}
