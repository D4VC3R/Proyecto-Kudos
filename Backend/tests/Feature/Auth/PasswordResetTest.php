<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\Auth\QueuedResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

/**
 * Caracterización (F5-01): forgot-password y reset-password de la API heredada.
 */
class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
    }

    public function test_forgot_password_con_un_correo_existente_envia_el_enlace(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/forgot-password', ['email' => $user->email])
            ->assertOk()
            ->assertJsonStructure(['message']);

        Notification::assertSentTo($user, QueuedResetPassword::class);
        $this->assertDatabaseHas('password_reset_tokens', ['email' => $user->email]);
    }

    public function test_forgot_password_con_un_correo_mal_formado_da_422(): void
    {
        $this->postJson('/api/forgot-password', ['email' => 'no-es-un-correo'])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'validation_error')
            ->assertJsonStructure(['error' => ['details' => ['email']]]);

        Notification::assertNothingSent();
    }

    public function test_reset_password_con_un_token_valido_cambia_la_contrasena_y_revoca_los_tokens(): void
    {
        $user = User::factory()->create();
        $user->createToken('web');
        $token = Password::broker()->createToken($user);

        $this->postJson('/api/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'NuevaClave123!',
            'password_confirmation' => 'NuevaClave123!',
        ])->assertOk();

        $this->assertTrue(Hash::check('NuevaClave123!', $user->fresh()->password));
        $this->assertSame(0, $user->tokens()->count());
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);
    }

    public function test_el_token_del_correo_sirve_para_restablecer_la_contrasena(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/forgot-password', ['email' => $user->email])->assertOk();

        $token = null;
        Notification::assertSentTo($user, QueuedResetPassword::class, function (QueuedResetPassword $notification) use (&$token) {
            $token = $notification->token;

            return true;
        });

        $this->postJson('/api/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'NuevaClave123!',
            'password_confirmation' => 'NuevaClave123!',
        ])->assertOk();

        $this->postJson('/api/login', ['email' => $user->email, 'password' => 'NuevaClave123!'])
            ->assertOk();
    }

    public function test_reset_password_con_un_token_invalido_da_422_y_no_cambia_la_contrasena(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/reset-password', [
            'token' => 'token-falso',
            'email' => $user->email,
            'password' => 'NuevaClave123!',
            'password_confirmation' => 'NuevaClave123!',
        ])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'validation_error')
            ->assertJsonStructure(['error' => ['details' => ['email']]]);

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_reset_password_sin_confirmar_la_contrasena_da_422(): void
    {
        $user = User::factory()->create();
        $token = Password::broker()->createToken($user);

        $this->postJson('/api/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'NuevaClave123!',
            'password_confirmation' => 'OtraDistinta123!',
        ])
            ->assertStatus(422)
            ->assertJsonStructure(['error' => ['details' => ['password']]]);

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_forgot_password_responde_igual_con_un_correo_existente_y_con_uno_inexistente(): void
    {
        $user = User::factory()->create();

        $existente = $this->postJson('/api/forgot-password', ['email' => $user->email])->assertOk();
        $inexistente = $this->postJson('/api/forgot-password', ['email' => 'nadie@kudos.test'])->assertOk();

        $this->assertSame($existente->getContent(), $inexistente->getContent());

        Notification::assertSentTo($user, QueuedResetPassword::class);
        Notification::assertSentTimes(QueuedResetPassword::class, 1);
    }

    public function test_forgot_password_responde_igual_al_repetir_la_peticion_enseguida(): void
    {
        $user = User::factory()->create();

        $primera = $this->postJson('/api/forgot-password', ['email' => $user->email])->assertOk();
        $segunda = $this->postJson('/api/forgot-password', ['email' => $user->email])->assertOk();

        $this->assertSame($primera->getContent(), $segunda->getContent());
    }
}
