<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Proposal;
use App\Models\Role;
use App\Models\User;
use App\Services\KudosRules;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AdminProposalModerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_cannot_review_proposals(): void
    {
        $user = User::factory()->create();
        $proposal = $this->createPendingProposal();

        Sanctum::actingAs($user);

        $this->patchJson("/api/admin/proposals/{$proposal->id}/review", [
            'status' => Proposal::STATUS_ACCEPTED,
        ])
            ->assertStatus(403)
            ->assertJsonPath('error.code', 'forbidden');
    }

    public function test_admin_cannot_review_own_proposal(): void
    {
        $admin = $this->createAdminUser();
        $category = Category::factory()->create();

        $proposal = Proposal::factory()->create([
            'creator_id' => $admin->id,
            'category_id' => $category->id,
            'status' => Proposal::STATUS_PENDING,
        ]);

        Sanctum::actingAs($admin);

        $this->patchJson("/api/admin/proposals/{$proposal->id}/review", [
            'status' => Proposal::STATUS_ACCEPTED,
        ])
            ->assertStatus(403)
            ->assertJsonPath('error.code', 'forbidden');
    }

    public function test_admin_can_accept_pending_proposal_and_creates_item_with_kudos(): void
    {
        $admin = $this->createAdminUser();
        $creator = User::factory()->create(['total_kudos' => 0, 'creations_accepted' => 0]);
        $category = Category::factory()->create();

        $proposal = Proposal::factory()->create([
            'name' => 'Nuevo Item Moderado',
            'description' => 'Descripcion de prueba para item generado por moderacion.',
            'creator_id' => $creator->id,
            'category_id' => $category->id,
            'status' => Proposal::STATUS_PENDING,
        ]);

        Sanctum::actingAs($admin);

        $this->patchJson("/api/admin/proposals/{$proposal->id}/review", [
            'status' => Proposal::STATUS_ACCEPTED,
        ])
            ->assertOk()
            ->assertJsonPath('data.status', Proposal::STATUS_ACCEPTED)
            ->assertJsonPath('data.reviewed_by', $admin->id);

        $proposal->refresh();
        $creator->refresh();

        $this->assertSame(Proposal::STATUS_ACCEPTED, $proposal->status);
        $this->assertDatabaseHas('items', [
            'name' => 'Nuevo Item Moderado',
            'creator_id' => $creator->id,
            'category_id' => $category->id,
            'status' => 'active',
        ]);
        $this->assertSame(KudosRules::rewardForAcceptedProposal(), $creator->total_kudos);
        $this->assertSame(1, $creator->creations_accepted);
    }

    public function test_review_requires_admin_notes_for_rejected_status(): void
    {
        $admin = $this->createAdminUser();
        $proposal = $this->createPendingProposal();

        Sanctum::actingAs($admin);

        $this->patchJson("/api/admin/proposals/{$proposal->id}/review", [
            'status' => Proposal::STATUS_REJECTED,
        ])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'validation_error')
            ->assertJsonStructure([
                'error' => [
                    'details' => ['admin_notes'],
                ],
            ]);
    }

    public function test_admin_cannot_review_non_pending_proposal(): void
    {
        $admin = $this->createAdminUser();
        $proposal = $this->createPendingProposal();

        $proposal->update([
            'status' => Proposal::STATUS_ACCEPTED,
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
        ]);

        Sanctum::actingAs($admin);

        $this->patchJson("/api/admin/proposals/{$proposal->id}/review", [
            'status' => Proposal::STATUS_REJECTED,
            'admin_notes' => 'Fuera de criterios',
        ])
            ->assertStatus(422);
    }

    private function createPendingProposal(): Proposal
    {
        $creator = User::factory()->create();
        $category = Category::factory()->create();

        return Proposal::factory()->create([
            'creator_id' => $creator->id,
            'category_id' => $category->id,
            'status' => Proposal::STATUS_PENDING,
        ]);
    }

    private function createAdminUser(): User
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Role::query()->firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'web',
        ]);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }
}
