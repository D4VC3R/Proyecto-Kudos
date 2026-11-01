<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Registro de usuarios (F3-07): casilla de privacidad y declaración de 14 años obligatorias.
 */
class RegisterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // El evento Registered nunca debe enviar nada real.
        Notification::fake();
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Usuario Nuevo',
            'email' => 'nuevo@kudos.test',
            'password' => 'Secreta123!',
            'password_confirmation' => 'Secreta123!',
            'accept_privacy' => true,
            'age_confirmation' => true,
        ], $overrides);
    }

    public function test_registro_con_las_casillas_crea_el_usuario_y_guarda_privacy_accepted_at(): void
    {
        $this->freezeTime();

        $this->postJson('/api/register', $this->validPayload())
            ->assertCreated()
            ->assertJsonStructure([
                'message',
                'data' => ['user' => ['id', 'name', 'email'], 'requires_email_verification'],
            ]);

        $user = User::query()->where('email', 'nuevo@kudos.test')->first();

        $this->assertNotNull($user);
        $this->assertNotNull($user->privacy_accepted_at);
        $this->assertSame(now()->toDateTimeString(), $user->privacy_accepted_at->toDateTimeString());
    }

    public function test_registro_sin_aceptar_la_privacidad_da_422(): void
    {
        $payload = $this->validPayload();
        unset($payload['accept_privacy']);

        $this->postJson('/api/register', $payload)
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'validation_error')
            ->assertJsonStructure(['error' => ['details' => ['accept_privacy']]])
            ->assertJsonPath('error.details.accept_privacy.0', 'Debes aceptar la política de privacidad.');

        $this->assertDatabaseMissing('users', ['email' => 'nuevo@kudos.test']);
    }

    public function test_registro_sin_confirmar_la_edad_da_422(): void
    {
        $payload = $this->validPayload();
        unset($payload['age_confirmation']);

        $this->postJson('/api/register', $payload)
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'validation_error')
            ->assertJsonStructure(['error' => ['details' => ['age_confirmation']]])
            ->assertJsonPath('error.details.age_confirmation.0', 'Debes confirmar que tienes 14 años o más.');

        $this->assertDatabaseMissing('users', ['email' => 'nuevo@kudos.test']);
    }

    public function test_registro_con_las_casillas_desmarcadas_da_422(): void
    {
        $payload = $this->validPayload(['accept_privacy' => false, 'age_confirmation' => false]);

        $this->postJson('/api/register', $payload)
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'validation_error')
            ->assertJsonStructure(['error' => ['details' => ['accept_privacy', 'age_confirmation']]]);

        $this->assertDatabaseMissing('users', ['email' => 'nuevo@kudos.test']);
    }
}
