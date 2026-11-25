<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\Auth\QueuedResetPassword;
use App\Notifications\Auth\QueuedVerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Correos de cuenta (restablecimiento y verificación) en español, con su URL personalizada.
 */
class AuthNotificationMailTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_correo_de_restablecimiento_esta_en_espanol(): void
    {
        $user = User::factory()->create();

        $mail = (new QueuedResetPassword('token-de-prueba'))->toMail($user);

        $this->assertSame('Restablece tu contraseña', $mail->subject);
        $this->assertSame('Restablecer contraseña', $mail->actionText);
        $this->assertStringContainsString('restablecer la contraseña', $mail->introLines[0]);
        $this->assertTrue(collect($mail->outroLines)->contains(fn ($line) => str_contains($line, 'minutos')));

        $html = (string) $mail->render();
        $this->assertStringContainsString('¡Hola!', $html);
        $this->assertStringContainsString('Un saludo,', $html);
        $this->assertStringContainsString('copia y pega esta dirección en tu navegador', $html);
    }

    public function test_el_enlace_de_restablecimiento_lleva_el_correo_codificado(): void
    {
        $user = User::factory()->create(['email' => 'a+b@x.com']);

        $mail = (new QueuedResetPassword('token-de-prueba'))->toMail($user);

        $this->assertStringContainsString('email=a%2Bb%40x.com', $mail->actionUrl);
        $this->assertStringNotContainsString('a+b@x.com', $mail->actionUrl);
    }

    public function test_el_correo_de_verificacion_esta_en_espanol(): void
    {
        $user = User::factory()->unverified()->create();

        $mail = (new QueuedVerifyEmail)->toMail($user);

        $this->assertSame('Confirma tu correo electrónico', $mail->subject);
        $this->assertSame('Confirmar correo', $mail->actionText);
        $this->assertStringStartsWith(config('app.frontend_url').'/verify-email?verify_url=', $mail->actionUrl);
    }
}
