<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Item;
use App\Models\Role;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AdminUsersListTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_users_without_server_error(): void
    {
        $admin = $this->createAdminUser();

        User::factory()->count(3)->create();

        Sanctum::actingAs($admin);

        $this->getJson('/api/admin/users?per_page=10')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'email',
                        'role',
                        'is_banned',
                        'ban_state',
                        'ban_state_label',
                        'banned_until',
                    ],
                ],
                'meta' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                    'summary',
                ],
            ]);
    }

    public function test_admin_can_view_user_detail_with_activity_counters(): void
    {
        $admin = $this->createAdminUser();
        $target = User::factory()->create([
            'total_kudos' => 42,
            'creations_accepted' => 3,
        ]);

        $category = Category::factory()->create();
        $item = Item::factory()->forCategory($category)->create([
            'creator_id' => $target->id,
            'status' => Item::STATUS_ACTIVE,
        ]);
        Vote::factory()->create([
            'user_id' => $target->id,
            'item_id' => $item->id,
            'type' => Vote::TYPE_VOTE,
            'score' => 8,
        ]);

        Sanctum::actingAs($admin);

        $this->getJson("/api/admin/users/{$target->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $target->id)
            ->assertJsonPath('data.total_kudos', 42)
            ->assertJsonPath('data.items_count', 1)
            ->assertJsonPath('data.votes_count', 1)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'email',
                    'role',
                    'is_banned',
                    'ban_state',
                    'ban_state_label',
                    'total_kudos',
                    'creations_accepted',
                    'proposals_count',
                    'votes_count',
                    'comments_count',
                    'items_count',
                    'reviewed_proposals_count',
                    'sessions_count',
                    'profile',
                ],
            ]);
    }

    public function test_admin_users_sorting_is_applied_before_pagination(): void
    {
        $admin = $this->createAdminUser('ZZZ Admin');

        User::factory()->create(['name' => 'Charlie']);
        User::factory()->create(['name' => 'Bravo']);
        User::factory()->create(['name' => 'Alpha']);

        Sanctum::actingAs($admin);

        $this->getJson('/api/admin/users?per_page=2&sort_by=name&sort_direction=asc&page=1')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Alpha')
            ->assertJsonPath('data.1.name', 'Bravo');

        $this->getJson('/api/admin/users?per_page=2&sort_by=name&sort_direction=asc&page=2')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Charlie');
    }

    public function test_admin_users_name_sorting_is_case_and_accent_insensitive(): void
    {
        $admin = $this->createAdminUser('zzzzzz-admin');

        User::factory()->create(['name' => 'zorro']);
        User::factory()->create(['name' => 'alfa']);
        User::factory()->create(['name' => 'Árbol']);
        User::factory()->create(['name' => 'Bravo']);

        Sanctum::actingAs($admin);

        $this->getJson('/api/admin/users?per_page=10&sort_by=name&sort_direction=asc&page=1')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'alfa')
            ->assertJsonPath('data.1.name', 'Árbol')
            ->assertJsonPath('data.2.name', 'Bravo')
            ->assertJsonPath('data.3.name', 'zorro');
    }

    private function createAdminUser(?string $name = null): User
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Role::query()->firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'web',
        ]);

        $admin = User::factory()->create([
            'name' => $name ?? fake()->name(),
        ]);
        $admin->syncRoles(['admin']);

        return $admin;
    }
}

