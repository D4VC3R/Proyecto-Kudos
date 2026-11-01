<?php

namespace Tests\Feature\Lists;

use App\Models\User;
use App\Models\UserList;
use App\Notifications\ListLikedNotification;
use App\Services\UsageMetricsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesItems;
use Tests\Concerns\InteractsWithUsers;
use Tests\TestCase;

/**
 * "Me gusta" de listas (F3-01): POST|DELETE /api/lists/{id}/like.
 */
class ListLikeTest extends TestCase
{
    use CreatesItems, InteractsWithUsers, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
    }

    private function publicList(?User $owner = null, array $attrs = []): UserList
    {
        return UserList::factory()->create(array_merge([
            'user_id' => ($owner ?? User::factory()->create())->id,
            'visibility' => UserList::VISIBILITY_PUBLIC,
        ], $attrs));
    }

    private function likedNotifications(User $owner)
    {
        return $owner->notifications()->where('type', ListLikedNotification::class)->get();
    }

    public function test_sin_sesion_da_401(): void
    {
        $list = $this->publicList();

        $this->postJson("/api/lists/{$list->id}/like")->assertStatus(401);
        $this->deleteJson("/api/lists/{$list->id}/like")->assertStatus(401);
    }

    public function test_dar_me_gusta_suma_uno_y_es_idempotente(): void
    {
        $list = $this->publicList();
        $user = $this->actingAsUser();

        foreach ([1, 2] as $attempt) {
            $this->postJson("/api/lists/{$list->id}/like")
                ->assertOk()
                ->assertJsonPath('data.liked', true)
                ->assertJsonPath('data.likes_count', 1);
        }

        $this->assertDatabaseCount('list_likes', 1);
        $this->assertDatabaseHas('list_likes', ['user_id' => $user->id, 'list_id' => $list->id]);
        $this->assertSame(1, $list->fresh()->likes_count);
    }

    public function test_quitar_me_gusta_resta_y_es_idempotente(): void
    {
        $list = $this->publicList();
        $this->actingAsUser();
        $this->postJson("/api/lists/{$list->id}/like")->assertOk();

        foreach ([1, 2] as $attempt) {
            $this->deleteJson("/api/lists/{$list->id}/like")
                ->assertOk()
                ->assertJsonPath('data.liked', false)
                ->assertJsonPath('data.likes_count', 0);
        }

        $this->assertDatabaseCount('list_likes', 0);
        $this->assertSame(0, $list->fresh()->likes_count);
    }

    public function test_no_se_puede_dar_me_gusta_a_una_privada_ajena_ni_a_la_propia(): void
    {
        $user = $this->actingAsUser();
        $foreignPrivate = $this->publicList(null, ['visibility' => UserList::VISIBILITY_PRIVATE]);
        $own = $this->publicList($user);

        $this->postJson("/api/lists/{$foreignPrivate->id}/like")->assertNotFound();
        $this->deleteJson("/api/lists/{$foreignPrivate->id}/like")->assertNotFound();
        $this->postJson("/api/lists/{$own->id}/like")->assertForbidden();

        $this->assertDatabaseCount('list_likes', 0);
        $this->assertSame(0, $own->fresh()->likes_count);
    }

    public function test_se_puede_dar_me_gusta_a_una_no_listada_y_a_una_editorial(): void
    {
        $this->actingAsUser();
        $unlisted = $this->publicList(null, ['visibility' => UserList::VISIBILITY_UNLISTED]);
        $editorial = UserList::factory()->editorial()->create();

        $this->postJson("/api/lists/{$unlisted->id}/like")->assertOk()->assertJsonPath('data.likes_count', 1);
        $this->postJson("/api/lists/{$editorial->id}/like")->assertOk()->assertJsonPath('data.likes_count', 1);
    }

    public function test_avisa_al_dueno_como_mucho_una_vez_al_dia_por_lista(): void
    {
        $this->travelTo('2026-03-10 09:00');
        $owner = User::factory()->create();
        $list = $this->publicList($owner);

        $first = $this->actingAsUser();
        $this->postJson("/api/lists/{$list->id}/like")->assertOk();
        $second = $this->actingAsUser();
        $this->postJson("/api/lists/{$list->id}/like")->assertOk();

        // Quita y vuelve a dar me gusta el mismo día: sin aviso nuevo
        Sanctum::actingAs($first);
        $this->deleteJson("/api/lists/{$list->id}/like")->assertOk();
        $this->postJson("/api/lists/{$list->id}/like")->assertOk();

        $notifications = $this->likedNotifications($owner);
        $this->assertCount(1, $notifications);
        $this->assertSame($list->id, $notifications->first()->data['list_id']);

        $this->travelTo('2026-03-11 08:00');
        $this->actingAsUser();
        $this->postJson("/api/lists/{$list->id}/like")->assertOk();

        $this->assertCount(2, $this->likedNotifications($owner));
        $this->assertSame(3, $list->fresh()->likes_count);
    }

    public function test_los_avisos_de_listas_distintas_son_independientes(): void
    {
        $this->travelTo('2026-03-10 09:00');
        $owner = User::factory()->create();
        $a = $this->publicList($owner);
        $b = $this->publicList($owner);
        $this->actingAsUser();

        $this->postJson("/api/lists/{$a->id}/like")->assertOk();
        $this->postJson("/api/lists/{$b->id}/like")->assertOk();

        $this->assertCount(2, $this->likedNotifications($owner));
    }

    public function test_no_avisa_por_listas_editoriales(): void
    {
        $owner = User::factory()->create();
        $editorial = UserList::factory()->editorial()->create(['user_id' => $owner->id]);
        $this->actingAsUser();

        $this->postJson("/api/lists/{$editorial->id}/like")->assertOk();

        $this->assertCount(0, $this->likedNotifications($owner));
    }

    public function test_la_pagina_publica_indica_si_te_gusta(): void
    {
        $list = $this->publicList();

        $anonymous = $this->getJson("/api/lists/{$list->slug}")->assertOk();
        $this->assertArrayNotHasKey('liked_by_me', $anonymous->json('data'));

        $this->actingAsUser();
        $this->getJson("/api/lists/{$list->slug}")->assertOk()->assertJsonPath('data.liked_by_me', false);

        $this->postJson("/api/lists/{$list->id}/like")->assertOk();
        $this->getJson("/api/lists/{$list->slug}")
            ->assertOk()
            ->assertJsonPath('data.liked_by_me', true)
            ->assertJsonPath('data.likes_count', 1);
    }

    public function test_el_me_gusta_no_cambia_updated_at_de_la_lista(): void
    {
        $this->travelTo('2026-03-10 09:00');
        $list = $this->publicList();
        $before = $list->fresh()->updated_at->toIso8601String();

        $this->travelTo('2026-03-12 09:00');
        $this->actingAsUser();
        $this->postJson("/api/lists/{$list->id}/like")->assertOk();
        $this->deleteJson("/api/lists/{$list->id}/like")->assertOk();

        $this->assertSame($before, $list->fresh()->updated_at->toIso8601String());
    }

    public function test_la_metrica_de_listas_con_me_gusta_cuenta_la_lista(): void
    {
        $list = $this->publicList();
        $this->actingAsUser();
        $metrics = app(UsageMetricsService::class);

        $this->postJson("/api/lists/{$list->id}/like")->assertOk();
        $this->assertSame(1, $metrics->listCounts(now()->subDay(), now())['liked']);

        $this->deleteJson("/api/lists/{$list->id}/like")->assertOk();
        $this->assertSame(0, $metrics->listCounts(now()->subDay(), now())['liked']);
    }
}
