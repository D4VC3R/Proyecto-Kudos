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

class AdminItemsListTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_items_with_meta(): void
    {
        $admin = $this->createAdminUser();
        $this->seedItems();

        Sanctum::actingAs($admin);

        $this->getJson('/api/admin/items?per_page=2')
            ->assertOk()
            ->assertJsonStructure([
                'data',
                'meta' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                ],
            ]);
    }

    public function test_admin_items_ordering_is_global_across_pages(): void
    {
        $admin = $this->createAdminUser();
        $this->seedItems();

        Sanctum::actingAs($admin);

        $this->getJson('/api/admin/items?per_page=2&sort_by=name&sort_direction=asc&page=1')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Alpha')
            ->assertJsonPath('data.1.name', 'Bravo');

        $this->getJson('/api/admin/items?per_page=2&sort_by=name&sort_direction=asc&page=2')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Charlie')
            ->assertJsonPath('data.1.name', 'Zulu');
    }

    private function seedItems(): void
    {
        $category = Category::factory()->create();
        $creator = User::factory()->create();

        Item::factory()->forCategory($category)->create([
            'creator_id' => $creator->id,
            'name' => 'Zulu',
            'status' => Item::STATUS_ACTIVE,
        ]);

        Item::factory()->forCategory($category)->create([
            'creator_id' => $creator->id,
            'name' => 'Bravo',
            'status' => Item::STATUS_ACTIVE,
        ]);

        Item::factory()->forCategory($category)->create([
            'creator_id' => $creator->id,
            'name' => 'Alpha',
            'status' => Item::STATUS_INACTIVE,
        ]);

        Item::factory()->forCategory($category)->create([
            'creator_id' => $creator->id,
            'name' => 'Charlie',
            'status' => Item::STATUS_ACTIVE,
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

