<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Caracterización (F5-01): login, logout y logout-all de la API heredada.
 */
class LoginLogoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_correcto_devuelve_un_token_y_el_usuario(): void
    {
        $user = User::factory()->create(['email' => 'ana@kudos.test']);

        $response = $this->postJson('/api/login', ['email' => 'ana@kudos.test', 'password' => 'password']);

        $response->assertOk()
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonPath('data.user.email', 'ana@kudos.test')
            ->assertJsonPath('data.user.role', 'user')
            ->assertJsonPath('data.user.is_admin', false);
        $this->assertNotEmpty($response->json('data.access_token'));
        $this->assertSame(1, $user->tokens()->count());
    }

    public function test_login_de_un_admin_devuelve_el_rol_admin(): void
    {
        $this->seedRolesAndPermissions();
        $admin = User::factory()->create(['email' => 'admin@kudos.test']);
        $admin->assignRole('admin');

        $this->postJson('/api/login', ['email' => 'admin@kudos.test', 'password' => 'password'])
            ->assertOk()
            ->assertJsonPath('data.user.role', 'admin')
            ->assertJsonPath('data.user.is_admin', true);
    }

    public function test_login_con_credenciales_incorrectas_da_401_sin_token(): void
    {
        User::factory()->create(['email' => 'ana@kudos.test']);

        // La ficha decía 422; el código responde 401 (AuthenticatedSessionController:29-35).
        $this->postJson('/api/login', ['email' => 'ana@kudos.test', 'password' => 'incorrecta'])
            ->assertStatus(401)
            ->assertJsonPath('error.code', 'unauthenticated');

        $this->postJson('/api/login', ['email' => 'nadie@kudos.test', 'password' => 'password'])
            ->assertStatus(401)
            ->assertJsonPath('error.code', 'unauthenticated');

        $this->assertSame(0, DB::table('personal_access_tokens')->count());
    }

    public function test_login_sin_campos_da_422(): void
    {
        $this->postJson('/api/login', [])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'validation_error')
            ->assertJsonStructure(['error' => ['details' => ['email', 'password']]]);
    }

    public function test_login_de_una_cuenta_baneada_da_403_sin_token(): void
    {
        User::factory()->create([
            'email' => 'ana@kudos.test',
            'is_banned' => true,
            'banned_until' => null,
            'ban_reason' => 'Spam reiterado',
        ]);

        $this->postJson('/api/login', ['email' => 'ana@kudos.test', 'password' => 'password'])
            ->assertForbidden()
            ->assertJsonPath('error.code', 'forbidden')
            ->assertJsonPath('error.details.ban_reason', 'Spam reiterado')
            ->assertJsonPath('error.details.banned_until', null);

        $this->assertSame(0, DB::table('personal_access_tokens')->count());
    }

    public function test_login_con_un_baneo_temporal_vigente_da_403_con_la_fecha(): void
    {
        User::factory()->create([
            'email' => 'ana@kudos.test',
            'is_banned' => true,
            'banned_until' => now()->addDays(3),
            'ban_reason' => 'Insultos',
        ]);

        $response = $this->postJson('/api/login', ['email' => 'ana@kudos.test', 'password' => 'password'])
            ->assertForbidden()
            ->assertJsonPath('error.code', 'forbidden');

        $this->assertNotNull($response->json('error.details.banned_until'));
        $this->assertSame(0, DB::table('personal_access_tokens')->count());
    }

    public function test_login_con_un_baneo_caducado_entra(): void
    {
        User::factory()->create([
            'email' => 'ana@kudos.test',
            'is_banned' => true,
            'banned_until' => now()->subDay(),
            'ban_reason' => 'Insultos',
        ]);

        $this->postJson('/api/login', ['email' => 'ana@kudos.test', 'password' => 'password'])
            ->assertOk();
    }

    public function test_logout_revoca_solo_el_token_actual(): void
    {
        $user = User::factory()->create();
        $current = $user->createToken('movil')->plainTextToken;
        $other = $user->createToken('web')->plainTextToken;

        $this->withToken($current)->postJson('/api/logout')
            ->assertOk()
            ->assertJsonStructure(['message']);

        $this->assertSame(1, $user->tokens()->count());
        $this->assertSame('web', $user->tokens()->value('name'));

        $this->app['auth']->forgetGuards();
        $this->withToken($current)->getJson('/api/profile')->assertUnauthorized();

        $this->app['auth']->forgetGuards();
        $this->withToken($other)->getJson('/api/profile')->assertOk();
    }

    public function test_logout_all_revoca_todos_los_tokens(): void
    {
        $user = User::factory()->create();
        $first = $user->createToken('movil')->plainTextToken;
        $second = $user->createToken('web')->plainTextToken;

        $this->withToken($first)->postJson('/api/logout-all')->assertOk();

        $this->assertSame(0, $user->tokens()->count());

        $this->app['auth']->forgetGuards();
        $this->withToken($second)->getJson('/api/profile')->assertUnauthorized();
    }

    public function test_logout_y_logout_all_sin_sesion_dan_401(): void
    {
        $this->postJson('/api/logout')
            ->assertUnauthorized()
            ->assertJsonPath('error.code', 'unauthenticated');

        $this->postJson('/api/logout-all')
            ->assertUnauthorized()
            ->assertJsonPath('error.code', 'unauthenticated');
    }
}
