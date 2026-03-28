<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Item;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AdminItemModerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_cannot_moderate_item_status(): void
    {
        $user = User::factory()->create();
        $item = $this->createItem();

        Sanctum::actingAs($user);

        $this->patchJson("/api/admin/items/{$item->id}/moderate", [
            'status' => Item::STATUS_INACTIVE,
            'reason' => 'Incumple criterios de moderacion',
        ])
            ->assertStatus(403)
            ->assertJsonPath('error.code', 'forbidden');
    }

    public function test_admin_can_inactivate_item_with_reason(): void
    {
        $admin = $this->createAdminUser();
        $item = $this->createItem();

        Sanctum::actingAs($admin);

        $this->patchJson("/api/admin/items/{$item->id}/moderate", [
            'status' => Item::STATUS_INACTIVE,
            'reason' => 'Contenido duplicado',
        ])
            ->assertOk()
            ->assertJsonPath('data.id', $item->id)
            ->assertJsonPath('data.status', Item::STATUS_INACTIVE);

        $item->refresh();
        $this->assertSame(Item::STATUS_INACTIVE, $item->status);
    }

    public function test_inactivate_item_requires_reason(): void
    {
        $admin = $this->createAdminUser();
        $item = $this->createItem();

        Sanctum::actingAs($admin);

        $this->patchJson("/api/admin/items/{$item->id}/moderate", [
            'status' => Item::STATUS_INACTIVE,
        ])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'validation_error')
            ->assertJsonStructure([
                'error' => [
                    'details' => ['reason'],
                ],
            ]);
    }

    public function test_admin_can_reactivate_item_without_reason(): void
    {
        $admin = $this->createAdminUser();
        $item = $this->createItem(status: Item::STATUS_INACTIVE);

        Sanctum::actingAs($admin);

        $this->patchJson("/api/admin/items/{$item->id}/moderate", [
            'status' => Item::STATUS_ACTIVE,
        ])
            ->assertOk()
            ->assertJsonPath('data.id', $item->id)
            ->assertJsonPath('data.status', Item::STATUS_ACTIVE);

        $item->refresh();
        $this->assertSame(Item::STATUS_ACTIVE, $item->status);
    }

    private function createItem(string $status = Item::STATUS_ACTIVE): Item
    {
        $creator = User::factory()->create();
        $category = Category::factory()->create();

        return Item::factory()->forCategory($category)->create([
            'creator_id' => $creator->id,
            'status' => $status,
            'vote_avg' => 0,
            'vote_count' => 0,
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
