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

class CategoryDeleteConflictTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_cannot_delete_category_with_items_and_gets_conflict_error_contract(): void
    {
        $admin = $this->createAdminUser();
        $category = Category::factory()->create();

        Item::factory()->forCategory($category)->create([
            'status' => Item::STATUS_ACTIVE,
        ]);

        Sanctum::actingAs($admin);

        $this->deleteJson("/api/categories/{$category->slug}")
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'conflict')
            ->assertJsonPath('error.message', 'No se puede eliminar la categoria porque tiene 1 items asociados.');
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

