<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Caracterización (F5-01): baneos, revocación de sesiones, listado y detalle de usuarios del panel de administración.
 */
class AdminUserBanTest extends TestCase
{
    use RefreshDatabase;

    /** Las cinco rutas de usuarios del panel para un usuario objetivo: [método, uri, payload]. */
    private function panelRoutes(User $target): array
    {
        return [
            ['GET', '/api/admin/users', []],
            ['GET', "/api/admin/users/{$target->id}", []],
            ['PATCH', "/api/admin/users/{$target->id}/ban", ['days' => 7, 'reason' => 'x']],
            ['PATCH', "/api/admin/users/{$target->id}/unban", []],
            ['POST', "/api/admin/users/{$target->id}/sessions/revoke", []],
        ];
    }

    public function test_un_ban_temporal_suspende_al_usuario_y_revoca_sus_tokens(): void
    {
        $this->freezeTime();
        $admin = $this->actingAsAdmin();
        $target = User::factory()->create();
        $target->createToken('a');
        $target->createToken('b');

        $this->patchJson("/api/admin/users/{$target->id}/ban", ['days' => 7, 'reason' => 'Spam reiterado'])
            ->assertOk()
            ->assertJsonPath('data.is_banned', true)
            ->assertJsonPath('data.ban_state', 'temporary');

        $fresh = $target->fresh();
        $this->assertTrue($fresh->is_banned);
        $this->assertSame(now()->addDays(7)->toDateTimeString(), $fresh->banned_until->toDateTimeString());
        $this->assertSame('Spam reiterado', $fresh->ban_reason);
        $this->assertSame($admin->id, $fresh->banned_by);
        $this->assertSame(0, $target->tokens()->count());
    }

    public function test_un_ban_permanente_no_tiene_fecha_de_fin(): void
    {
        $this->actingAsAdmin();
        $target = User::factory()->create();

        $this->patchJson("/api/admin/users/{$target->id}/ban", ['is_permanent' => true, 'reason' => 'Acoso'])
            ->assertOk()
            ->assertJsonPath('data.ban_state', 'permanent');

        $fresh = $target->fresh();
        $this->assertTrue($fresh->is_banned);
        $this->assertNull($fresh->banned_until);
    }

    public function test_banear_sin_motivo_o_sin_dias_da_422(): void
    {
        $this->actingAsAdmin();
        $target = User::factory()->create();

        $this->patchJson("/api/admin/users/{$target->id}/ban", ['days' => 7])
            ->assertStatus(422)
            ->assertJsonStructure(['error' => ['details' => ['reason']]]);

        $this->patchJson("/api/admin/users/{$target->id}/ban", ['reason' => 'Spam'])
            ->assertStatus(422)
            ->assertJsonStructure(['error' => ['details' => ['days']]]);

        $this->assertFalse($target->fresh()->is_banned);
    }

    public function test_un_admin_no_puede_banearse_a_si_mismo(): void
    {
        $admin = $this->actingAsAdmin();

        $this->patchJson("/api/admin/users/{$admin->id}/ban", ['days' => 7, 'reason' => 'Spam'])
            ->assertStatus(422)
            ->assertJsonStructure(['error' => ['details' => ['user']]]);

        $this->assertFalse($admin->fresh()->is_banned);
    }

    public function test_el_usuario_baneado_recibe_403_en_una_ruta_not_banned(): void
    {
        $this->actingAsAdmin();
        $target = User::factory()->create();

        $this->patchJson("/api/admin/users/{$target->id}/ban", ['days' => 7, 'reason' => 'Spam reiterado'])
            ->assertOk();

        Sanctum::actingAs($target->fresh());

        $this->getJson('/api/profile')
            ->assertForbidden()
            ->assertJsonPath('error.code', 'forbidden')
            ->assertJsonPath('error.details.ban_reason', 'Spam reiterado');

        $this->postJson('/api/profile/daily-claim')->assertForbidden();
    }

    public function test_unban_levanta_la_suspension(): void
    {
        $target = User::factory()->create([
            'is_banned' => true,
            'banned_at' => now(),
            'banned_until' => null,
            'ban_reason' => 'Spam',
        ]);
        $this->actingAsAdmin();

        $this->patchJson("/api/admin/users/{$target->id}/unban")
            ->assertOk()
            ->assertJsonPath('data.is_banned', false)
            ->assertJsonPath('data.ban_state', 'active');

        $fresh = $target->fresh();
        $this->assertFalse($fresh->is_banned);
        $this->assertNull($fresh->banned_at);
        $this->assertNull($fresh->banned_until);
        $this->assertNull($fresh->ban_reason);
        $this->assertNull($fresh->banned_by);

        Sanctum::actingAs($fresh);
        $this->getJson('/api/profile')->assertOk();
    }

    public function test_sessions_revoke_borra_todos_los_tokens_del_usuario(): void
    {
        $this->actingAsAdmin();
        $target = User::factory()->create();
        $target->createToken('a');
        $target->createToken('b');

        $this->postJson("/api/admin/users/{$target->id}/sessions/revoke")
            ->assertOk()
            ->assertJsonPath('meta.revoked_tokens', 2);

        $this->assertSame(0, $target->tokens()->count());
    }

    public function test_un_admin_no_puede_revocar_sus_propias_sesiones(): void
    {
        $admin = $this->actingAsAdmin();

        $this->postJson("/api/admin/users/{$admin->id}/sessions/revoke")
            ->assertStatus(422)
            ->assertJsonStructure(['error' => ['details' => ['user']]]);
    }

    public function test_el_admin_lista_y_ve_el_detalle_de_los_usuarios(): void
    {
        $this->actingAsAdmin();
        $target = User::factory()->create();

        $this->getJson('/api/admin/users')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [['id', 'email', 'role', 'ban_state']],
                'meta' => ['total', 'summary' => ['total_users']],
            ]);

        $this->getJson("/api/admin/users/{$target->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $target->id)
            ->assertJsonPath('data.email', $target->email);
    }

    public function test_un_usuario_normal_recibe_403_en_las_rutas_de_usuarios_del_panel(): void
    {
        $target = User::factory()->create();
        $this->seedRolesAndPermissions();
        $this->actingAsUser();

        foreach ($this->panelRoutes($target) as [$method, $uri, $payload]) {
            $this->json($method, $uri, $payload)
                ->assertForbidden()
                ->assertJsonPath('error.code', 'forbidden');
        }

        $this->assertFalse($target->fresh()->is_banned);
    }

    public function test_un_invitado_recibe_401_en_las_rutas_de_usuarios_del_panel(): void
    {
        $target = User::factory()->create();

        foreach ($this->panelRoutes($target) as [$method, $uri, $payload]) {
            $this->json($method, $uri, $payload)
                ->assertUnauthorized()
                ->assertJsonPath('error.code', 'unauthenticated');
        }
    }

    public function test_un_admin_baneado_recibe_403_en_el_panel(): void
    {
        $this->actingAsAdmin(['is_banned' => true, 'banned_until' => null]);

        $this->getJson('/api/admin/users')
            ->assertForbidden()
            ->assertJsonPath('error.code', 'forbidden');
    }
}
