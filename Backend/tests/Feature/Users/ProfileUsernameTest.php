<?php

namespace Tests\Feature\Users;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\InteractsWithUsers;
use Tests\TestCase;

/**
 * Nombre de usuario y visibilidad del perfil (F3-03): PUT|GET /api/profile.
 */
class ProfileUsernameTest extends TestCase
{
    use InteractsWithUsers, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
    }

    public function test_un_perfil_nuevo_es_privado_y_sin_nombre_de_usuario(): void
    {
        // Usuario creado por el registro (no por la factoría); se registra antes de autenticar a nadie.
        Notification::fake();
        $this->postJson('/api/register', [
            'name' => 'Usuario Nuevo',
            'email' => 'nuevo@kudos.test',
            'password' => 'Secreta123!',
            'password_confirmation' => 'Secreta123!',
            'accept_privacy' => true,
            'age_confirmation' => true,
        ])->assertCreated();

        $registered = User::query()->where('email', 'nuevo@kudos.test')->firstOrFail();
        Sanctum::actingAs($registered);

        $this->getJson('/api/profile')
            ->assertOk()
            ->assertJsonPath('data.is_public', false)
            ->assertJsonPath('data.username', null);
        $this->assertDatabaseHas('profiles', ['user_id' => $registered->id, 'is_public' => false]);
        $this->assertNull($registered->fresh()->username);

        // Usuario de la factoría
        $user = $this->actingAsUser();

        $this->getJson('/api/profile')
            ->assertOk()
            ->assertJsonPath('data.is_public', false)
            ->assertJsonPath('data.username', null);
        $this->assertDatabaseHas('profiles', ['user_id' => $user->id, 'is_public' => false]);
    }

    public function test_put_profile_guarda_username_e_is_public(): void
    {
        $user = $this->actingAsUser();

        $this->putJson('/api/profile', ['username' => 'ana_99', 'is_public' => true])
            ->assertOk()
            ->assertJsonPath('data.username', 'ana_99')
            ->assertJsonPath('data.is_public', true);

        $this->assertSame('ana_99', $user->fresh()->username);
        $this->assertDatabaseHas('profiles', ['user_id' => $user->id, 'is_public' => true]);
    }

    public function test_el_username_se_guarda_en_minusculas_y_sin_espacios(): void
    {
        $user = $this->actingAsUser();

        $this->putJson('/api/profile', ['username' => '  Ana_99 '])
            ->assertOk()
            ->assertJsonPath('data.username', 'ana_99');

        $this->assertSame('ana_99', $user->fresh()->username);
    }

    public function test_un_username_invalido_da_422(): void
    {
        $this->actingAsUser();

        foreach (['ab', str_repeat('a', 31), 'ana-99', 'añ0_x', 'ana 99'] as $invalid) {
            $this->putJson('/api/profile', ['username' => $invalid])
                ->assertStatus(422)
                ->assertJsonPath('error.code', 'validation_error')
                ->assertJsonStructure(['error' => ['details' => ['username']]]);
        }
    }

    public function test_un_username_repetido_da_422_y_el_propio_se_acepta(): void
    {
        User::factory()->create(['email_verified_at' => now()])->forceFill(['username' => 'ana_99'])->save();
        $user = $this->actingAsUser();

        $this->putJson('/api/profile', ['username' => 'ana_99'])
            ->assertStatus(422)
            ->assertJsonPath('error.details.username.0', 'Ese nombre de usuario ya está en uso.');

        $user->forceFill(['username' => 'propio_1'])->save();

        $this->putJson('/api/profile', ['username' => 'propio_1'])
            ->assertOk()
            ->assertJsonPath('data.username', 'propio_1');
    }

    public function test_un_username_reservado_da_422(): void
    {
        $this->actingAsUser();

        foreach (['admin', 'ADMIN', 'buscar', 'ranking'] as $reserved) {
            $this->putJson('/api/profile', ['username' => $reserved])
                ->assertStatus(422)
                ->assertJsonPath('error.code', 'validation_error')
                ->assertJsonStructure(['error' => ['details' => ['username']]]);
        }
    }

    public function test_no_se_puede_hacer_publico_un_perfil_sin_username(): void
    {
        $user = $this->actingAsUser();

        $this->putJson('/api/profile', ['is_public' => true])
            ->assertStatus(422)
            ->assertJsonStructure(['error' => ['details' => ['is_public']]]);

        $this->assertFalse((bool) $user->profile->fresh()->is_public);
    }

    public function test_no_se_puede_quitar_el_username_de_un_perfil_publico(): void
    {
        $user = $this->actingAsUser();
        $user->forceFill(['username' => 'ana_99'])->save();
        $user->profile->update(['is_public' => true]);

        $this->putJson('/api/profile', ['username' => null])
            ->assertStatus(422)
            ->assertJsonStructure(['error' => ['details' => ['username']]]);

        $this->putJson('/api/profile', ['username' => null, 'is_public' => false])
            ->assertOk()
            ->assertJsonPath('data.username', null)
            ->assertJsonPath('data.is_public', false);

        $this->assertNull($user->fresh()->username);
        $this->assertFalse((bool) $user->profile->fresh()->is_public);
    }

    public function test_sin_las_claves_no_se_tocan(): void
    {
        $user = $this->actingAsUser();
        $user->forceFill(['username' => 'ana_99'])->save();
        $user->profile->update(['is_public' => true]);

        $this->putJson('/api/profile', ['biography' => 'Hola'])
            ->assertOk()
            ->assertJsonPath('data.username', 'ana_99')
            ->assertJsonPath('data.is_public', true);

        $this->assertSame('ana_99', $user->fresh()->username);
        $this->assertTrue((bool) $user->profile->fresh()->is_public);
    }
}
