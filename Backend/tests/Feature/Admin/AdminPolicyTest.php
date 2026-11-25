<?php

namespace Tests\Feature\Admin;

use App\Http\Middleware\EnsureUserIsAdmin;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\Concerns\InteractsWithUsers;
use Tests\TestCase;

/**
 * UserPolicy y ReportPolicy: solo el admin gestiona usuarios y reportes.
 */
class AdminPolicyTest extends TestCase
{
    use InteractsWithUsers, RefreshDatabase;

    private function makeAdmin(): User
    {
        $this->seedRolesAndPermissions();
        $admin = User::factory()->create(['email_verified_at' => now(), 'is_banned' => false]);
        $admin->assignRole('admin');

        return $admin;
    }

    public function test_la_policy_de_usuarios_solo_autoriza_al_admin(): void
    {
        $admin = $this->makeAdmin();
        $user = User::factory()->create();
        $target = User::factory()->create();

        foreach (['view', 'ban', 'unban', 'revokeTokens'] as $ability) {
            $this->assertTrue(Gate::forUser($admin)->allows($ability, $target), "$ability admin");
            $this->assertFalse(Gate::forUser($user)->allows($ability, $target), "$ability usuario");
        }

        $this->assertTrue(Gate::forUser($admin)->allows('viewAny', User::class));
        $this->assertFalse(Gate::forUser($user)->allows('viewAny', User::class));
    }

    public function test_la_policy_de_reportes_solo_autoriza_al_admin(): void
    {
        $admin = $this->makeAdmin();
        $user = User::factory()->create();
        $report = Report::factory()->create();

        $this->assertTrue(Gate::forUser($admin)->allows('viewAny', Report::class));
        $this->assertFalse(Gate::forUser($user)->allows('viewAny', Report::class));
        $this->assertTrue(Gate::forUser($admin)->allows('resolve', $report));
        $this->assertFalse(Gate::forUser($user)->allows('resolve', $report));
    }

    public function test_la_policy_solo_deja_borrar_su_propia_cuenta_a_quien_no_es_admin(): void
    {
        $admin = $this->makeAdmin();
        $user = User::factory()->create();
        $other = User::factory()->create();

        $this->assertTrue(Gate::forUser($user)->allows('deleteOwnAccount', $user));
        $this->assertFalse(Gate::forUser($user)->allows('deleteOwnAccount', $other));
        $this->assertFalse(Gate::forUser($admin)->allows('deleteOwnAccount', $admin));
    }

    public function test_admin_users_responde_403_por_la_policy_a_un_usuario_sin_rol(): void
    {
        // Se quita el middleware admin para comprobar que la policy también lo deniega por sí sola.
        $this->withoutMiddleware(EnsureUserIsAdmin::class);
        $this->seedRolesAndPermissions();
        $this->actingAsUser();
        $target = User::factory()->create(['is_banned' => false]);

        $this->getJson('/api/admin/users')->assertForbidden()->assertJsonPath('error.code', 'forbidden');
        $this->getJson("/api/admin/users/{$target->id}")->assertForbidden()->assertJsonPath('error.code', 'forbidden');
        $this->patchJson("/api/admin/users/{$target->id}/ban", ['reason' => 'Spam', 'days' => 1])
            ->assertForbidden()->assertJsonPath('error.code', 'forbidden');
        $this->patchJson("/api/admin/users/{$target->id}/unban")->assertForbidden()->assertJsonPath('error.code', 'forbidden');
        $this->postJson("/api/admin/users/{$target->id}/sessions/revoke")->assertForbidden()->assertJsonPath('error.code', 'forbidden');

        $this->assertFalse((bool) $target->fresh()->is_banned);
    }

    public function test_admin_reports_responde_403_por_la_policy_a_un_usuario_sin_rol(): void
    {
        // Se quita el middleware admin para comprobar que la policy también lo deniega por sí sola.
        $this->withoutMiddleware(EnsureUserIsAdmin::class);
        $this->seedRolesAndPermissions();
        $this->actingAsUser();
        $report = Report::factory()->create();

        $this->getJson('/api/admin/reports')->assertForbidden()->assertJsonPath('error.code', 'forbidden');
        $this->postJson("/api/admin/reports/{$report->id}/resolve", ['action' => 'dismiss'])
            ->assertForbidden()->assertJsonPath('error.code', 'forbidden');

        $this->assertSame(Report::STATUS_PENDING, $report->fresh()->status);
    }

    public function test_un_admin_sigue_accediendo_a_admin_users_y_admin_reports(): void
    {
        $this->actingAsAdmin();

        $this->getJson('/api/admin/users')->assertOk();
        $this->getJson('/api/admin/reports')->assertOk();
    }
}
