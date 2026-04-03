<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\CategoryFieldDefinition;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ItemExtraDataSchemaValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_item_rejects_unknown_extra_data_key_for_category(): void
    {
        $admin = $this->createAdminUser();
        /** @var Category $category */
        $category = Category::factory()->create();

        CategoryFieldDefinition::create([
            'category_id' => $category->id,
            'key' => 'developer',
            'label' => 'Desarrollador',
            'type' => 'string',
            'required' => true,
            'sort_order' => 1,
            'is_active' => true,
        ]);

        Sanctum::actingAs($admin);

        $this->postJson('/api/items', [
            'name' => 'Hollow Knight',
            'description' => 'Metroidvania de accion con exploracion desafiante y gran direccion artistica.',
            'category_id' => $category->id,
            'extra_data' => [
                'developer' => 'Team Cherry',
                'unknown_key' => 'valor no permitido',
            ],
        ])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'validation_error')
            ->assertJsonStructure([
                'error' => [
                    'details' => ['extra_data.unknown_key'],
                ],
            ]);
    }

    public function test_create_item_requires_required_schema_fields(): void
    {
        $admin = $this->createAdminUser();
        /** @var Category $category */
        $category = Category::factory()->create();

        CategoryFieldDefinition::create([
            'category_id' => $category->id,
            'key' => 'developer',
            'label' => 'Desarrollador',
            'type' => 'string',
            'required' => true,
            'sort_order' => 1,
            'is_active' => true,
        ]);

        Sanctum::actingAs($admin);

        $this->postJson('/api/items', [
            'name' => 'Celeste',
            'description' => 'Plataformas de precision con historia emotiva y excelente diseno de niveles.',
            'category_id' => $category->id,
            'extra_data' => [],
        ])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'validation_error')
            ->assertJsonStructure([
                'error' => [
                    'details' => ['extra_data.developer'],
                ],
            ]);
    }

    public function test_create_item_validates_numeric_rules_from_schema(): void
    {
        $admin = $this->createAdminUser();
        /** @var Category $category */
        $category = Category::factory()->create();

        CategoryFieldDefinition::create([
            'category_id' => $category->id,
            'key' => 'metacritic_score',
            'label' => 'Puntuacion Metacritic',
            'type' => 'integer',
            'required' => true,
            'rules' => ['min' => 0, 'max' => 100],
            'sort_order' => 1,
            'is_active' => true,
        ]);

        Sanctum::actingAs($admin);

        $this->postJson('/api/items', [
            'name' => 'Portal 2',
            'description' => 'Puzles en primera persona con narrativa memorable y coop excelente.',
            'category_id' => $category->id,
            'extra_data' => [
                'metacritic_score' => 140,
            ],
        ])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'validation_error')
            ->assertJsonStructure([
                'error' => [
                    'details' => ['extra_data.metacritic_score'],
                ],
            ]);
    }

    private function createAdminUser(): User
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Role::query()->firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'web',
        ]);

        /** @var User $admin */
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }
}

