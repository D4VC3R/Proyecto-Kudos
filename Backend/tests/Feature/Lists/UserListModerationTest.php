<?php

namespace Tests\Feature\Lists;

use App\Models\User;
use App\Models\UserList;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesItems;
use Tests\Concerns\InteractsWithUsers;
use Tests\TestCase;

/**
 * Una lista ocultada por moderación no puede volver a publicarse (F3-01, riesgo anotado en la F3-04).
 */
class UserListModerationTest extends TestCase
{
    use CreatesItems, InteractsWithUsers, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
    }

    public function test_una_lista_ocultada_por_moderacion_no_puede_volver_a_publicarse(): void
    {
        $owner = User::factory()->create(['email_verified_at' => now(), 'is_banned' => false]);
        $list = UserList::factory()->create(['user_id' => $owner->id, 'visibility' => UserList::VISIBILITY_PUBLIC]);

        // Otro usuario la reporta
        $this->actingAsUser();
        $reportId = $this->postJson('/api/reports', [
            'reportable_type' => 'list',
            'reportable_id' => $list->id,
            'reason' => 'spam',
        ])->assertCreated()->json('data.id');

        // El admin la oculta
        $this->actingAsAdmin();
        $this->postJson("/api/admin/reports/{$reportId}/resolve", ['action' => 'hide', 'note' => 'No es apta'])->assertOk();

        $hidden = $list->fresh();
        $this->assertSame(UserList::VISIBILITY_PRIVATE, $hidden->visibility);
        $this->assertNotNull($hidden->hidden_by_moderation_at);

        // El dueño no puede volver a publicarla ni dejarla con enlace
        Sanctum::actingAs($owner);
        $this->putJson("/api/profile/lists/{$list->id}", ['visibility' => 'public'])
            ->assertStatus(422)
            ->assertJsonStructure(['error' => ['details' => ['visibility']]]);
        $this->putJson("/api/profile/lists/{$list->id}", ['visibility' => 'unlisted'])->assertStatus(422);
        $this->assertSame(UserList::VISIBILITY_PRIVATE, $list->fresh()->visibility);

        // Sí puede dejarla privada o editar otros campos
        $this->putJson("/api/profile/lists/{$list->id}", ['visibility' => 'private'])->assertOk();
        $this->putJson("/api/profile/lists/{$list->id}", ['title' => 'Título corregido'])->assertOk();

        $this->getJson("/api/profile/lists/{$list->id}")
            ->assertOk()
            ->assertJsonPath('data.is_hidden_by_moderation', true)
            ->assertJsonPath('data.title', 'Título corregido');
    }

    public function test_una_lista_no_moderada_se_puede_publicar(): void
    {
        $owner = $this->actingAsUser();
        $list = UserList::factory()->create(['user_id' => $owner->id]);

        $this->putJson("/api/profile/lists/{$list->id}", ['visibility' => 'public'])
            ->assertOk()
            ->assertJsonPath('data.visibility', 'public')
            ->assertJsonPath('data.is_hidden_by_moderation', false);
    }
}
