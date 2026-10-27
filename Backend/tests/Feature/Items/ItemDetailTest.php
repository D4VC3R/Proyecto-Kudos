<?php

namespace Tests\Feature\Items;

use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\CreatesItems;
use Tests\TestCase;

class ItemDetailTest extends TestCase
{
    use RefreshDatabase, CreatesItems;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
    }

    public function test_el_detalle_de_un_item_devuelve_la_estructura_esperada(): void
    {
        $item = $this->createItem($this->createCategory());

        $this->getJson("/api/items/{$item->id}")
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id', 'name', 'description', 'images', 'status', 'vote_avg', 'vote_count', 'can_vote',
                    'category' => ['id', 'name', 'description', 'slug', 'image', 'items_count'],
                    'creator'  => ['id', 'name'],
                ],
            ])
            ->assertJsonPath('data.can_vote', false)
            ->assertJsonMissingPath('data.user_vote')
            ->assertJsonMissingPath('data.can_edit')
            ->assertJsonMissingPath('data.can_delete');
    }

    public function test_el_detalle_incluye_el_voto_del_usuario_autenticado(): void
    {
        // El ítem se crea antes de autenticar: así su creador es otro usuario (can_edit es true para el creador).
        $item = $this->createItem($this->createCategory());
        $this->actingAsUser();

        $voteId = $this->postJson('/api/votes', ['item_id' => $item->id, 'type' => 'vote', 'score' => 8])
            ->assertCreated()
            ->json('data.id');

        $response = $this->getJson("/api/items/{$item->id}");

        $response->assertOk()
            ->assertJsonPath('data.user_vote.id', $voteId)
            ->assertJsonPath('data.can_vote', false)
            ->assertJsonPath('data.can_edit', false)
            ->assertJsonPath('data.can_delete', false);
        $this->assertEquals(8, $response->json('data.user_vote.score'));
        $this->assertNotNull($response->json('data.user_vote.voted_at'));
    }

    public function test_un_invitado_no_ve_el_detalle_de_un_item_inactivo(): void
    {
        $item = $this->createItem($this->createCategory(), ['status' => Item::STATUS_INACTIVE]);

        $response = $this->getJson("/api/items/{$item->id}");

        $response->assertForbidden();
    }

    public function test_un_admin_con_token_ve_el_detalle_de_un_item_inactivo(): void
    {
        // Token Bearer real (sin Sanctum::actingAs): la ruta es pública y el guard por defecto es 'web',
        // como en producción. Comprueba que authorize() resuelve el usuario con el guard 'sanctum'.
        $this->seedRolesAndPermissions();
        $admin = User::factory()->create(['email_verified_at' => now(), 'is_banned' => false]);
        $admin->assignRole('admin');
        $token = $admin->createToken('test')->plainTextToken;

        $item = $this->createItem($this->createCategory(), ['status' => Item::STATUS_INACTIVE]);

        $this->withToken($token)
            ->getJson("/api/items/{$item->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $item->id);
    }
}
