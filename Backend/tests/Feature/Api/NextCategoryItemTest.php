<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Item;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NextCategoryItemTest extends TestCase
{
    use RefreshDatabase;

    public function test_next_item_requires_authentication(): void
    {
        $category = Category::factory()->create();

        $this->getJson("/api/categories/{$category->slug}/next-item")
            ->assertStatus(401);
    }

    public function test_next_item_rotates_items_for_same_user_category(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $items = Item::factory()->count(3)->forCategory($category)->create([
            'status' => Item::STATUS_ACTIVE,
            'vote_avg' => 0,
            'vote_count' => 0,
        ]);

        Sanctum::actingAs($user);

        $first = $this->getJson("/api/categories/{$category->slug}/next-item")
            ->assertOk()
            ->json();

        $second = $this->getJson("/api/categories/{$category->slug}/next-item")
            ->assertOk()
            ->json();

        $this->assertNotSame($first['data']['id'], $second['data']['id']);
        $this->assertSame(2, $first['meta']['remaining']);
        $this->assertSame(1, $second['meta']['remaining']);

        $returnedIds = [$first['data']['id'], $second['data']['id']];
        $this->assertContains($returnedIds[0], $items->pluck('id')->all());
        $this->assertContains($returnedIds[1], $items->pluck('id')->all());
    }

    public function test_next_item_excludes_vote_and_skip_interactions(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $voteItem = Item::factory()->forCategory($category)->create([
            'status' => Item::STATUS_ACTIVE,
            'vote_avg' => 0,
            'vote_count' => 0,
        ]);
        $skipItem = Item::factory()->forCategory($category)->create([
            'status' => Item::STATUS_ACTIVE,
            'vote_avg' => 0,
            'vote_count' => 0,
        ]);
        $eligibleItem = Item::factory()->forCategory($category)->create([
            'status' => Item::STATUS_ACTIVE,
            'vote_avg' => 0,
            'vote_count' => 0,
        ]);

        Vote::create([
            'user_id' => $user->id,
            'item_id' => $voteItem->id,
            'type' => Vote::TYPE_VOTE,
            'score' => 7,
        ]);
        Vote::create([
            'user_id' => $user->id,
            'item_id' => $skipItem->id,
            'type' => Vote::TYPE_SKIP,
            'score' => null,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson("/api/categories/{$category->slug}/next-item")
            ->assertOk();

        $response->assertJsonPath('data.id', $eligibleItem->id);
    }

    public function test_next_item_returns_204_when_pool_is_exhausted(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $item = Item::factory()->forCategory($category)->create([
            'status' => Item::STATUS_ACTIVE,
            'vote_avg' => 0,
            'vote_count' => 0,
        ]);

        Vote::create([
            'user_id' => $user->id,
            'item_id' => $item->id,
            'type' => Vote::TYPE_SKIP,
            'score' => null,
        ]);

        Sanctum::actingAs($user);

        $this->getJson("/api/categories/{$category->slug}/next-item")
            ->assertNoContent();
    }
}

