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

class ItemExtraDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_item_with_extra_data(): void
    {
        $admin = $this->createAdminUser();
        /** @var Category $category */
        $category = Category::factory()->create();

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/items', [
            'name' => 'The Legend of Zelda: Tears of the Kingdom',
            'description' => 'Aventura en mundo abierto con exploracion vertical y sistemas emergentes.',
            'category_id' => $category->id,
            'extra_data' => [
                'developer' => 'Nintendo EPD',
                'publisher' => 'Nintendo',
                'platforms' => ['Nintendo Switch'],
                'metacritic_score' => 96,
            ],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.extra_data.developer', 'Nintendo EPD')
            ->assertJsonPath('data.extra_data.metacritic_score', 96);

        $createdItemId = $response->json('data.id');
        $this->assertNotNull($createdItemId);

        $createdItem = Item::findOrFail($createdItemId);
        $this->assertSame('Nintendo EPD', $createdItem->extra_data['developer']);
        $this->assertSame(96, $createdItem->extra_data['metacritic_score']);
    }

    public function test_admin_can_update_item_extra_data(): void
    {
        $admin = $this->createAdminUser();
        /** @var Category $category */
        $category = Category::factory()->create();

        $item = Item::factory()->forCategory($category)->create([
            'status' => Item::STATUS_ACTIVE,
            'creator_id' => $admin->id,
            'extra_data' => [
                'developer' => 'Nintendo EPD',
            ],
        ]);

        Sanctum::actingAs($admin);

        $this->putJson("/api/admin/items/{$item->id}", [
            'extra_data' => [
                'developer' => 'Nintendo EPD',
                'publisher' => 'Nintendo',
                'release_date' => '2023-05-12',
            ],
            'moderation_reason' => 'Completar metadatos del item.',
        ])
            ->assertOk()
            ->assertJsonPath('data.extra_data.publisher', 'Nintendo');

        $item->refresh();

        $this->assertSame('Nintendo', $item->extra_data['publisher']);
        $this->assertSame('2023-05-12', $item->extra_data['release_date']);
    }

    public function test_public_items_listing_includes_extra_data_without_breaking_contract(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        /** @var Category $category */
        $category = Category::factory()->create();

        Item::factory()->forCategory($category)->create([
            'status' => Item::STATUS_ACTIVE,
            'creator_id' => $user->id,
            'extra_data' => [
                'director' => 'Christopher Nolan',
                'release_year' => 2010,
            ],
        ]);

        $this->getJson('/api/items')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'description',
                        'images',
                        'extra_data',
                        'status',
                        'vote_avg',
                        'vote_count',
                    ],
                ],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
                'links' => ['first', 'last', 'prev', 'next'],
            ])
            ->assertJsonPath('data.0.extra_data.director', 'Christopher Nolan')
            ->assertJsonPath('data.0.extra_data.release_year', 2010);
    }

    public function test_items_listing_resolves_can_vote_from_authenticated_user_context(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $votedItem = Item::factory()->forCategory($category)->create([
            'status' => Item::STATUS_ACTIVE,
            'creator_id' => $user->id,
        ]);

        $availableItem = Item::factory()->forCategory($category)->create([
            'status' => Item::STATUS_ACTIVE,
            'creator_id' => $user->id,
        ]);

        Vote::create([
            'user_id' => $user->id,
            'item_id' => $votedItem->id,
            'type' => Vote::TYPE_VOTE,
            'score' => 8,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/items?sort_by=name&sort_order=asc');

        $responseData = collect($response->json('data'));
        $availableRow = $responseData->firstWhere('id', $availableItem->id);
        $votedRow = $responseData->firstWhere('id', $votedItem->id);

        $response->assertOk();

        $this->assertIsArray($availableRow);
        $this->assertIsArray($votedRow);
        $this->assertTrue($availableRow['can_vote']);
        $this->assertFalse($votedRow['can_vote']);
        $this->assertSame(8, $votedRow['user_vote']['score']);
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


