<?php

namespace Tests\Feature\Proposals;

use App\Models\Category;
use App\Models\Item;
use App\Models\KudosTransaction;
use App\Models\Proposal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesItems;
use Tests\TestCase;

/**
 * Caracterización (F5-01): propuestas de ítems, desde el CRUD del dueño hasta la revisión del admin.
 * No se envía `image_path` (evita lanzar el job de imágenes). Por HTTP la segunda revisión la para
 * ReviewProposalRequest (solo `pending`); la carrera dentro de la Action (BE-R16) es de F5-03.
 */
class ProposalFlowTest extends TestCase
{
    use CreatesItems, RefreshDatabase;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = $this->createCategory();
    }

    private function proposalBy(User $creator, array $attrs = [], ?string $state = null): Proposal
    {
        $factory = Proposal::factory();
        if ($state !== null) {
            $factory = $factory->{$state}();
        }

        return $factory->create(array_merge([
            'creator_id' => $creator->id,
            'category_id' => $this->category->id,
        ], $attrs));
    }

    private function validPayload(): array
    {
        return [
            'name' => 'Película propuesta',
            'description' => 'Una descripción de al menos veinte caracteres.',
            'category_id' => $this->category->id,
        ];
    }

    public function test_un_usuario_crea_una_propuesta_pendiente(): void
    {
        $user = $this->actingAsUser();

        $this->postJson('/api/proposals', $this->validPayload())
            ->assertCreated()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.creator_id', $user->id);

        $this->assertDatabaseHas('proposals', [
            'name' => 'Película propuesta',
            'creator_id' => $user->id,
            'category_id' => $this->category->id,
            'status' => 'pending',
        ]);
    }

    public function test_crear_una_propuesta_con_datos_invalidos_da_422(): void
    {
        $this->actingAsUser();

        $this->postJson('/api/proposals', ['name' => 'Película propuesta', 'description' => 'Muy corta'])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'validation_error')
            ->assertJsonStructure(['error' => ['details' => ['description', 'category_id']]]);

        $this->assertSame(0, Proposal::count());
    }

    public function test_my_proposals_lista_solo_las_propuestas_propias(): void
    {
        // No se fija `reviewer` (BE-R8, F5-03).
        $user = $this->actingAsUser();
        $pending = $this->proposalBy($user);
        $changes = $this->proposalBy($user, [], 'changesRequested');
        $this->proposalBy(User::factory()->create());

        $response = $this->getJson('/api/proposals/my-proposals')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('meta.pending', 1)
            ->assertJsonPath('meta.changes_requested', 1);

        $this->assertEqualsCanonicalizing(
            [$pending->id, $changes->id],
            collect($response->json('data'))->pluck('id')->all()
        );
    }

    public function test_el_dueno_ve_su_propuesta_y_una_ajena_da_403(): void
    {
        $user = $this->actingAsUser();
        $own = $this->proposalBy($user);
        $foreign = $this->proposalBy(User::factory()->create());

        $this->getJson("/api/proposals/{$own->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $own->id)
            ->assertJsonPath('data.category.slug', 'peliculas');

        $this->getJson("/api/proposals/{$foreign->id}")->assertForbidden();
    }

    public function test_el_dueno_edita_su_propuesta_pendiente(): void
    {
        $user = $this->actingAsUser();
        $proposal = $this->proposalBy($user);

        $this->putJson("/api/proposals/{$proposal->id}", ['name' => 'Nombre nuevo'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Nombre nuevo')
            ->assertJsonPath('data.status', 'pending');
    }

    public function test_editar_una_propuesta_con_cambios_solicitados_la_reenvia_a_pending(): void
    {
        $user = $this->actingAsUser();
        $proposal = $this->proposalBy($user, [], 'changesRequested');

        $this->putJson("/api/proposals/{$proposal->id}", ['name' => 'Nombre corregido'])
            ->assertOk();

        $this->assertDatabaseHas('proposals', ['id' => $proposal->id, 'name' => 'Nombre corregido', 'status' => 'pending']);
    }

    public function test_editar_una_propuesta_ajena_o_ya_aceptada_da_403(): void
    {
        $user = $this->actingAsUser();
        $foreign = $this->proposalBy(User::factory()->create(), ['name' => 'Propuesta ajena']);
        $accepted = $this->proposalBy($user, ['name' => 'Propuesta aceptada'], 'accepted');

        $this->putJson("/api/proposals/{$foreign->id}", ['name' => 'Nombre nuevo'])->assertForbidden();
        $this->putJson("/api/proposals/{$accepted->id}", ['name' => 'Nombre nuevo'])->assertForbidden();

        $this->assertSame('Propuesta ajena', $foreign->fresh()->name);
        $this->assertSame('Propuesta aceptada', $accepted->fresh()->name);
        $this->assertSame('accepted', $accepted->fresh()->status);
    }

    public function test_el_dueno_borra_su_propuesta_pendiente(): void
    {
        $user = $this->actingAsUser();
        $proposal = $this->proposalBy($user);

        $this->deleteJson("/api/proposals/{$proposal->id}")->assertOk();

        $this->assertSoftDeleted($proposal);
    }

    public function test_borrar_una_propuesta_ajena_o_ya_revisada_da_403(): void
    {
        $user = $this->actingAsUser();
        $foreign = $this->proposalBy(User::factory()->create());
        $accepted = $this->proposalBy($user, [], 'accepted');

        $this->deleteJson("/api/proposals/{$foreign->id}")->assertForbidden();
        $this->deleteJson("/api/proposals/{$accepted->id}")->assertForbidden();

        $this->assertNotSoftDeleted($foreign);
        $this->assertNotSoftDeleted($accepted);
    }

    public function test_aceptar_una_propuesta_crea_el_item_y_da_la_recompensa_una_sola_vez(): void
    {
        $creator = User::factory()->create();
        $proposal = $this->proposalBy($creator);
        $admin = $this->actingAsAdmin();
        $reward = config('kudos.rewards.proposal_accepted');

        $this->patchJson("/api/admin/proposals/{$proposal->id}/review", ['status' => 'accepted'])
            ->assertOk()
            ->assertJsonPath('data.status', 'accepted')
            ->assertJsonPath('data.reviewed_by', $admin->id);

        $itemsOfProposal = fn () => Item::where('name', $proposal->name)
            ->where('category_id', $this->category->id)
            ->where('creator_id', $creator->id)
            ->count();
        $rewardTransactions = fn () => KudosTransaction::where('user_id', $creator->id)
            ->where('reason', 'proposal_accepted')
            ->count();

        $this->assertSame(1, $itemsOfProposal());
        $this->assertSame($reward, $creator->fresh()->total_kudos);
        $this->assertSame(1, $creator->fresh()->creations_accepted);
        $this->assertSame(1, $rewardTransactions());
        $this->assertDatabaseHas('kudos_transactions', ['action_key' => "proposal_accepted:{$proposal->id}"]);

        // Por HTTP la segunda revisión la para ReviewProposalRequest (solo `pending`); la carrera
        // dentro de la Action (BE-R16) es de F5-03.
        $this->patchJson("/api/admin/proposals/{$proposal->id}/review", ['status' => 'accepted'])
            ->assertStatus(422)
            ->assertJsonStructure(['error' => ['details' => ['proposal']]]);

        $this->assertSame(1, $itemsOfProposal());
        $this->assertSame(1, $rewardTransactions());
        $this->assertSame($reward, $creator->fresh()->total_kudos);
        $this->assertSame(1, $creator->fresh()->creations_accepted);
    }

    public function test_rechazar_una_propuesta_no_tiene_efectos(): void
    {
        $creator = User::factory()->create();
        $proposal = $this->proposalBy($creator);
        $this->actingAsAdmin();

        $this->patchJson("/api/admin/proposals/{$proposal->id}/review", [
            'status' => 'rejected',
            'admin_notes' => 'No encaja en el catálogo.',
        ])
            ->assertOk()
            ->assertJsonPath('data.status', 'rejected');

        $this->assertSame(0, Item::count());
        $this->assertSame(0, $creator->fresh()->total_kudos);
        $this->assertSame(0, $creator->fresh()->creations_accepted);
        $this->assertSame(0, KudosTransaction::where('user_id', $creator->id)->count());
    }

    public function test_rechazar_sin_notas_da_422(): void
    {
        $proposal = $this->proposalBy(User::factory()->create());
        $this->actingAsAdmin();

        $this->patchJson("/api/admin/proposals/{$proposal->id}/review", ['status' => 'rejected'])
            ->assertStatus(422)
            ->assertJsonStructure(['error' => ['details' => ['admin_notes']]]);

        $this->assertSame('pending', $proposal->fresh()->status);
    }

    public function test_un_admin_no_puede_revisar_su_propia_propuesta(): void
    {
        $admin = $this->actingAsAdmin();
        $proposal = $this->proposalBy($admin);

        $this->patchJson("/api/admin/proposals/{$proposal->id}/review", ['status' => 'accepted'])
            ->assertForbidden();

        $this->assertSame('pending', $proposal->fresh()->status);
        $this->assertSame(0, Item::count());
    }

    public function test_el_panel_lista_las_propuestas_y_las_pendientes(): void
    {
        $other = User::factory()->create();
        $pending = $this->proposalBy($other);
        $this->proposalBy($other, [], 'accepted');
        $this->actingAsAdmin();

        $this->getJson('/api/admin/proposals')
            ->assertOk()
            ->assertJsonPath('meta.total', 2);

        $this->getJson('/api/admin/proposals/pending')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $pending->id);

        $this->getJson('/api/admin/proposals?status=accepted')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_un_usuario_normal_recibe_403_en_el_panel_de_propuestas(): void
    {
        $proposal = $this->proposalBy(User::factory()->create());
        $this->seedRolesAndPermissions();
        $this->actingAsUser();

        $routes = [
            ['GET', '/api/admin/proposals', []],
            ['GET', '/api/admin/proposals/pending', []],
            ['PATCH', "/api/admin/proposals/{$proposal->id}/review", ['status' => 'accepted']],
        ];

        foreach ($routes as [$method, $uri, $payload]) {
            $this->json($method, $uri, $payload)
                ->assertForbidden()
                ->assertJsonPath('error.code', 'forbidden');
        }

        $this->assertSame('pending', $proposal->fresh()->status);
    }

    public function test_un_usuario_baneado_no_puede_proponer(): void
    {
        $this->actingAsUser(['is_banned' => true, 'banned_until' => null]);

        $this->postJson('/api/proposals', $this->validPayload())
            ->assertForbidden()
            ->assertJsonPath('error.code', 'forbidden');

        $this->assertSame(0, Proposal::count());
    }

    public function test_un_invitado_recibe_401_en_las_rutas_de_propuestas(): void
    {
        $proposal = $this->proposalBy(User::factory()->create());

        $routes = [
            ['POST', '/api/proposals', $this->validPayload()],
            ['GET', '/api/proposals/my-proposals', []],
            ['GET', "/api/proposals/{$proposal->id}", []],
            ['PUT', "/api/proposals/{$proposal->id}", ['name' => 'Nombre nuevo']],
            ['DELETE', "/api/proposals/{$proposal->id}", []],
            ['GET', '/api/admin/proposals', []],
            ['GET', '/api/admin/proposals/pending', []],
            ['PATCH', "/api/admin/proposals/{$proposal->id}/review", ['status' => 'accepted']],
        ];

        foreach ($routes as [$method, $uri, $payload]) {
            $this->json($method, $uri, $payload)
                ->assertUnauthorized()
                ->assertJsonPath('error.code', 'unauthenticated');
        }
    }
}
