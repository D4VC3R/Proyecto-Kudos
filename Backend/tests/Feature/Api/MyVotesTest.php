<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Item;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MyVotesTest extends TestCase
{
    use RefreshDatabase;

    public function test_my_votes_requires_authentication(): void
    {
        $this->getJson('/api/votes/my-votes')
            ->assertStatus(401);
    }

    public function test_my_votes_lists_only_authenticated_user_votes_with_pagination(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $category = Category::factory()->create();
        $itemA = Item::factory()->forCategory($category)->create(['status' => Item::STATUS_ACTIVE]);
        $itemB = Item::factory()->forCategory($category)->create(['status' => Item::STATUS_ACTIVE]);

        Vote::create([
            'user_id' => $user->id,
            'item_id' => $itemA->id,
            'type' => Vote::TYPE_VOTE,
            'score' => 7,
        ]);

        Vote::create([
            'user_id' => $user->id,
            'item_id' => $itemB->id,
            'type' => Vote::TYPE_SKIP,
            'score' => null,
        ]);

        Vote::create([
            'user_id' => $otherUser->id,
            'item_id' => $itemA->id,
            'type' => Vote::TYPE_VOTE,
            'score' => 9,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/votes/my-votes?per_page=1');

        $response->assertOk()
            ->assertJsonPath('meta.per_page', 1)
            ->assertJsonPath('meta.total', 2)
            ->assertJsonStructure([
                'data',
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
                'links' => ['first', 'last', 'prev', 'next'],
            ]);
    }

    public function test_my_votes_supports_type_and_search_filters(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $itemVote = Item::factory()->forCategory($category)->create([
            'status' => Item::STATUS_ACTIVE,
            'name' => 'Juego Alpha',
        ]);
        $itemSkip = Item::factory()->forCategory($category)->create([
            'status' => Item::STATUS_ACTIVE,
            'name' => 'Juego Beta',
        ]);

        Vote::create([
            'user_id' => $user->id,
            'item_id' => $itemVote->id,
            'type' => Vote::TYPE_VOTE,
            'score' => 8,
        ]);

        Vote::create([
            'user_id' => $user->id,
            'item_id' => $itemSkip->id,
            'type' => Vote::TYPE_SKIP,
            'score' => null,
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/votes/my-votes?type=vote&search=Alpha')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.type', Vote::TYPE_VOTE)
            ->assertJsonPath('data.0.item.name', 'Juego Alpha');
    }
}

