<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Limitador `auth` (por IP, compartido) de POST /api/register y POST /api/forgot-password.
 */
class AuthRateLimitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
    }

    public function test_register_responde_429_con_el_formato_de_la_api_al_superar_el_limite_auth(): void
    {
        config(['kudos.auth.per_minute' => 2]);

        $this->postJson('/api/register', [])->assertStatus(422);
        $this->postJson('/api/register', [])->assertStatus(422);

        $this->postJson('/api/register', [])
            ->assertStatus(429)
            ->assertJsonPath('error.code', 'too_many_requests')
            ->assertJsonPath('error.message', 'Has hecho demasiados intentos. Espera un minuto y vuelve a intentarlo.')
            ->assertHeader('Retry-After');
    }

    public function test_forgot_password_responde_429_con_el_formato_de_la_api_al_superar_el_limite_auth(): void
    {
        config(['kudos.auth.per_minute' => 2]);

        $this->postJson('/api/forgot-password', ['email' => 'nadie@kudos.test'])->assertOk();
        $this->postJson('/api/forgot-password', ['email' => 'nadie@kudos.test'])->assertOk();

        $this->postJson('/api/forgot-password', ['email' => 'nadie@kudos.test'])
            ->assertStatus(429)
            ->assertJsonPath('error.code', 'too_many_requests')
            ->assertJsonPath('error.message', 'Has hecho demasiados intentos. Espera un minuto y vuelve a intentarlo.')
            ->assertHeader('Retry-After');
    }

    public function test_el_limite_auth_se_cuenta_por_ip(): void
    {
        config(['kudos.auth.per_minute' => 1]);

        $this->postJson('/api/forgot-password', ['email' => 'nadie@kudos.test'])->assertOk();
        $this->postJson('/api/forgot-password', ['email' => 'nadie@kudos.test'])->assertStatus(429);

        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.2'])
            ->postJson('/api/forgot-password', ['email' => 'nadie@kudos.test'])
            ->assertOk();
    }
}
