<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Item;
use App\Models\User;
use App\Models\Vote;
use App\Services\KudosRules;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class VoteValidationAndLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_vote_type_requires_score(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $item = Item::factory()->forCategory($category)->create([
            'status' => Item::STATUS_ACTIVE,
        ]);

        Sanctum::actingAs($user);

        $this->postJson('/api/votes', [
            'item_id' => $item->id,
            'type' => Vote::TYPE_VOTE,
            'score' => null,
        ])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'validation_error')
            ->assertJsonStructure([
                'error' => [
                    'details' => ['score'],
                ],
            ]);
    }

    public function test_skip_type_rejects_score_payload(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $item = Item::factory()->forCategory($category)->create([
            'status' => Item::STATUS_ACTIVE,
        ]);

        Sanctum::actingAs($user);

        $this->postJson('/api/votes', [
            'item_id' => $item->id,
            'type' => Vote::TYPE_SKIP,
            'score' => 3,
        ])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'validation_error')
            ->assertJsonStructure([
                'error' => [
                    'details' => ['score'],
                ],
            ]);
    }

    public function test_post_vote_idempotent_hit_for_existing_vote_returns_already_voted_reason(): void
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
            'item_id' => $item->id,
            'type' => Vote::TYPE_VOTE,
            'score' => 6,
        ])->assertStatus(201);

        $this->postJson('/api/votes', [
            'item_id' => $item->id,
            'type' => Vote::TYPE_VOTE,
            'score' => 9,
        ])
            ->assertOk()
            ->assertJsonPath('meta.was_existing', true)
            ->assertJsonPath('meta.reason', 'already_voted')
            ->assertJsonPath('data.score', 6);

        $item->refresh();
        $user->refresh();

        $this->assertSame(1, $item->vote_count);
        $this->assertSame(6.0, (float) $item->vote_avg);
        $this->assertSame(KudosRules::rewardForVoteFirstTimeItem(), $user->total_kudos);
    }

    public function test_user_cannot_update_a_skip_interaction_score(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $item = Item::factory()->forCategory($category)->create([
            'status' => Item::STATUS_ACTIVE,
        ]);

        $vote = Vote::create([
            'user_id' => $user->id,
            'item_id' => $item->id,
            'type' => Vote::TYPE_SKIP,
            'score' => null,
        ]);

        Sanctum::actingAs($user);

        $this->putJson("/api/votes/{$vote->id}", [
            'score' => 7,
        ])->assertStatus(422);
    }

    public function test_delete_vote_recalculates_item_average_and_count(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $category = Category::factory()->create();

        $item = Item::factory()->forCategory($category)->create([
            'status' => Item::STATUS_ACTIVE,
            'vote_count' => 2,
            'vote_avg' => 6,
        ]);

        $ownerVote = Vote::create([
            'user_id' => $owner->id,
            'item_id' => $item->id,
            'type' => Vote::TYPE_VOTE,
            'score' => 8,
        ]);

        Vote::create([
            'user_id' => $other->id,
            'item_id' => $item->id,
            'type' => Vote::TYPE_VOTE,
            'score' => 4,
        ]);

        Sanctum::actingAs($owner);

        $this->deleteJson("/api/votes/{$ownerVote->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Voto eliminado correctamente.');

        $item->refresh();

        $this->assertSame(1, $item->vote_count);
        $this->assertSame(4.0, (float) $item->vote_avg);
        $this->assertDatabaseMissing('votes', ['id' => $ownerVote->id]);
    }

    public function test_delete_skip_does_not_affect_item_aggregates(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $category = Category::factory()->create();

        $item = Item::factory()->forCategory($category)->create([
            'status' => Item::STATUS_ACTIVE,
            'vote_count' => 1,
            'vote_avg' => 7,
        ]);

        Vote::create([
            'user_id' => $other->id,
            'item_id' => $item->id,
            'type' => Vote::TYPE_VOTE,
            'score' => 7,
        ]);

        $skipVote = Vote::create([
            'user_id' => $user->id,
            'item_id' => $item->id,
            'type' => Vote::TYPE_SKIP,
            'score' => null,
        ]);

        Sanctum::actingAs($user);

        $this->deleteJson("/api/votes/{$skipVote->id}")->assertOk();

        $item->refresh();

        $this->assertSame(1, $item->vote_count);
        $this->assertSame(7.0, (float) $item->vote_avg);
    }

    public function test_unverified_user_is_blocked_from_authenticated_verified_routes(): void
    {
        $user = User::factory()->unverified()->create();

        Sanctum::actingAs($user);

        $this->getJson('/api/profile')
            ->assertStatus(403)
            ->assertJsonPath('error.code', 'forbidden')
            ->assertJsonPath('error.message', 'No tienes permisos para realizar esta acción.');
    }

    public function test_banned_user_is_blocked_from_authenticated_routes(): void
    {
        $user = User::factory()->create([
            'is_banned' => true,
            'banned_until' => now()->addDay(),
            'ban_reason' => 'incumplimiento de normas',
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/profile')
            ->assertStatus(403)
            ->assertJsonPath('error.code', 'forbidden')
            ->assertJsonPath('error.message', 'Tu cuenta esta suspendida y no puede acceder a esta funcionalidad.')
            ->assertJsonPath('error.details.ban_reason', 'incumplimiento de normas');
    }

    public function test_non_admin_user_cannot_access_admin_users_endpoint(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $this->getJson('/api/admin/users')
            ->assertStatus(403)
            ->assertJsonPath('error.code', 'forbidden');
    }

    public function test_public_ranking_without_auth_has_null_personal_position(): void
    {
        User::factory()->count(12)->create();

        $this->getJson('/api/users/ranking')
            ->assertOk()
            ->assertJsonPath('meta.top_pagination.per_page', 10)
            ->assertJsonPath('meta.my_position', null)
            ->assertJsonPath('data.my_page_data', null);
    }

    public function test_route_not_found_uses_standard_error_contract(): void
    {
        $this->getJson('/api/ruta-que-no-existe')
            ->assertStatus(404)
            ->assertJsonPath('error.code', 'route_not_found');
    }

    public function test_user_cannot_update_someone_elses_vote(): void
    {
        $owner = User::factory()->create();
        $attacker = User::factory()->create();
        $category = Category::factory()->create();
        $item = Item::factory()->forCategory($category)->create([
            'status' => Item::STATUS_ACTIVE,
        ]);

        $vote = Vote::create([
            'user_id' => $owner->id,
            'item_id' => $item->id,
            'type' => Vote::TYPE_VOTE,
            'score' => 6,
        ]);

        Sanctum::actingAs($attacker);

        $this->putJson("/api/votes/{$vote->id}", ['score' => 9])
            ->assertStatus(403)
            ->assertJsonPath('error.code', 'forbidden');
    }

    public function test_user_cannot_delete_someone_elses_vote(): void
    {
        $owner = User::factory()->create();
        $attacker = User::factory()->create();
        $category = Category::factory()->create();
        $item = Item::factory()->forCategory($category)->create([
            'status' => Item::STATUS_ACTIVE,
        ]);

        $vote = Vote::create([
            'user_id' => $owner->id,
            'item_id' => $item->id,
            'type' => Vote::TYPE_VOTE,
            'score' => 6,
        ]);

        Sanctum::actingAs($attacker);

        $this->deleteJson("/api/votes/{$vote->id}")
            ->assertStatus(403)
            ->assertJsonPath('error.code', 'forbidden');
    }

    public function test_delete_last_vote_resets_item_aggregates_to_zero(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $item = Item::factory()->forCategory($category)->create([
            'status' => Item::STATUS_ACTIVE,
            'vote_count' => 1,
            'vote_avg' => 9,
        ]);

        $vote = Vote::create([
            'user_id' => $user->id,
            'item_id' => $item->id,
            'type' => Vote::TYPE_VOTE,
            'score' => 9,
        ]);

        Sanctum::actingAs($user);

        $this->deleteJson("/api/votes/{$vote->id}")->assertOk();

        $item->refresh();

        $this->assertSame(0, $item->vote_count);
        $this->assertSame(0.0, (float) $item->vote_avg);
    }
}


