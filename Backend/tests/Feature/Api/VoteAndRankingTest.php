<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Item;
use App\Models\User;
use App\Models\Vote;
use App\Services\KudosRules;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class VoteAndRankingTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_skip_without_affecting_item_totals_or_kudos(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $item = Item::factory()->forCategory($category)->create([
            'status' => Item::STATUS_ACTIVE,
            'vote_avg' => 0,
            'vote_count' => 0,
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/votes', [
            'item_id' => $item->id,
            'type' => Vote::TYPE_SKIP,
            'score' => null,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('meta.vote_type', Vote::TYPE_SKIP)
            ->assertJsonPath('meta.total_kudos', 0);

        $item->refresh();
        $user->refresh();

        $this->assertSame(0, $item->vote_count);
        $this->assertSame(0.0, (float) $item->vote_avg);
        $this->assertSame(0, $user->total_kudos);
        $this->assertDatabaseHas('votes', [
            'user_id' => $user->id,
            'item_id' => $item->id,
            'type' => Vote::TYPE_SKIP,
        ]);
    }

    public function test_vote_update_edits_score_and_keeps_kudos_unchanged(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $item = Item::factory()->forCategory($category)->create([
            'status' => Item::STATUS_ACTIVE,
            'vote_avg' => 0,
            'vote_count' => 0,
        ]);

        Sanctum::actingAs($user);

        $this->postJson('/api/votes', [
            'type' => Vote::TYPE_VOTE,
            'item_id' => $item->id,
            'score' => 6,
        ])->assertStatus(201);

        $vote = Vote::query()->where('user_id', $user->id)->where('item_id', $item->id)->firstOrFail();

        $this->putJson("/api/votes/{$vote->id}", [
            'score' => 8,
        ])->assertOk();

        $item->refresh();
        $user->refresh();

        $this->assertSame(1, $item->vote_count);
        $this->assertSame(8.0, (float) $item->vote_avg);
        $this->assertSame(KudosRules::rewardForVoteFirstTimeItem(), $user->total_kudos);
    }

    public function test_post_vote_is_idempotent_and_does_not_mutate_existing_interaction(): void
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

        $this->postJson('/api/votes', [
            'item_id' => $item->id,
            'type' => Vote::TYPE_VOTE,
            'score' => 9,
        ])
            ->assertOk()
            ->assertJsonPath('meta.was_existing', true)
            ->assertJsonPath('meta.idempotent_hit', true)
            ->assertJsonPath('meta.vote_type', Vote::TYPE_SKIP);

        $item->refresh();
        $user->refresh();

        $this->assertSame(0, $item->vote_count);
        $this->assertSame(0.0, (float) $item->vote_avg);
        $this->assertSame(0, $user->total_kudos);
        $this->assertDatabaseHas('votes', [
            'user_id' => $user->id,
            'item_id' => $item->id,
            'type' => Vote::TYPE_SKIP,
            'score' => null,
        ]);
    }

    public function test_store_vote_with_invalid_item_id_returns_422_not_403(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $this->postJson('/api/votes', [
            'item_id' => 'invalido',
            'type' => Vote::TYPE_VOTE,
            'score' => 5,
        ])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'validation_error')
            ->assertJsonStructure([
                'error' => [
                    'code',
                    'message',
                    'details' => ['item_id'],
                ],
            ]);
    }

    public function test_public_ranking_returns_top_page_and_my_position_with_tie_breaker(): void
    {
        $baseDate = CarbonImmutable::parse('2026-01-01 10:00:00');

        for ($i = 0; $i < 9; $i++) {
            $user = User::factory()->create([
                'created_at' => $baseDate->addMinutes($i),
            ]);
            $user->forceFill(['total_kudos' => 1000 - ($i * 50)])->save();
        }

        $olderTie = User::factory()->create([
            'name' => 'Older Tie',
            'created_at' => $baseDate->addDay(),
        ]);
        $olderTie->forceFill(['total_kudos' => 500])->save();

        $authenticatedUser = User::factory()->create([
            'name' => 'Current User',
            'created_at' => $baseDate->addDays(2),
        ]);
        $authenticatedUser->forceFill(['total_kudos' => 500])->save();

        for ($i = 0; $i < 5; $i++) {
            $user = User::factory()->create([
                'created_at' => $baseDate->addDays(3)->addMinutes($i),
            ]);
            $user->forceFill(['total_kudos' => 100])->save();
        }

        Sanctum::actingAs($authenticatedUser);

        $response = $this->getJson('/api/users/ranking');

        $response->assertOk()
            ->assertJsonPath('meta.top_pagination.per_page', 10)
            ->assertJsonPath('meta.my_position.rank', 11)
            ->assertJsonPath('meta.my_position.page', 2)
            ->assertJsonPath('data.top_page.9.id', $olderTie->id)
            ->assertJsonPath('data.my_page_data.0.id', $authenticatedUser->id);
    }
}

