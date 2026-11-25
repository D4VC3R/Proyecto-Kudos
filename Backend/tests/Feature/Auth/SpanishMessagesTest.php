<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Mensajes de autenticación, validación y restablecimiento de contraseña en español.
 */
class SpanishMessagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
    }

    private function resetWithFakeToken(string $email)
    {
        return $this->postJson('/api/reset-password', [
            'token' => 'token-falso',
            'email' => $email,
            'password' => 'NuevaClave123!',
            'password_confirmation' => 'NuevaClave123!',
        ]);
    }

    public function test_el_mensaje_de_auth_failed_sale_en_espanol(): void
    {
        // El login responde 401 "Credenciales inválidas." y no deja pasar este texto: se comprueba la traducción.
        $this->assertSame('Estas credenciales no coinciden con nuestros registros.', __('auth.failed'));
    }

    public function test_los_mensajes_de_validacion_por_defecto_salen_en_espanol(): void
    {
        $this->postJson('/api/forgot-password', ['email' => 'no-es-un-correo'])
            ->assertStatus(422)
            ->assertJsonPath('error.details.email.0', 'El campo correo electrónico debe ser una dirección de correo válida.');
    }

    public function test_un_token_de_restablecimiento_invalido_da_el_mensaje_en_espanol(): void
    {
        $user = User::factory()->create();

        $this->resetWithFakeToken($user->email)
            ->assertStatus(422)
            ->assertJsonPath('error.details.email.0', 'El enlace para restablecer la contraseña no es válido o ha caducado.');
    }

    public function test_reset_password_no_distingue_un_correo_inexistente_de_un_token_invalido(): void
    {
        $user = User::factory()->create();

        $inexistente = $this->resetWithFakeToken('nadie@kudos.test')->assertStatus(422);
        $conCuenta = $this->resetWithFakeToken($user->email)->assertStatus(422);

        $this->assertSame($conCuenta->json('error.message'), $inexistente->json('error.message'));
    }
}
