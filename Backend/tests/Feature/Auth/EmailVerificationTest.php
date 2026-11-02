<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\Auth\QueuedVerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * Caracterización (F5-01): verify-email y email/verification-notification de la API heredada.
 */
class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
    }

    private function verificationUrl(User $user, ?string $hash = null): string
    {
        return URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
            'id' => $user->id,
            'hash' => $hash ?? sha1($user->getEmailForVerification()),
        ]);
    }

    public function test_verify_email_con_una_firma_valida_marca_el_correo_como_verificado(): void
    {
        $user = User::factory()->unverified()->create();

        $this->getJson($this->verificationUrl($user))
            ->assertOk()
            ->assertJsonPath('data.status', 'verified');

        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_verify_email_con_un_hash_incorrecto_da_403_y_no_verifica(): void
    {
        $user = User::factory()->unverified()->create();

        // No se fija el código `invalid-hash`: F5-04 lo normaliza (BE-R1).
        $this->getJson($this->verificationUrl($user, sha1('otro@kudos.test')))
            ->assertForbidden();

        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_verify_email_sin_firma_da_403(): void
    {
        $user = User::factory()->unverified()->create();

        $this->getJson("/api/verify-email/{$user->id}/".sha1($user->email))
            ->assertForbidden()
            ->assertJsonPath('error.code', 'invalid_signature');

        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_verify_email_con_la_firma_caducada_da_403(): void
    {
        $user = User::factory()->unverified()->create();
        $url = $this->verificationUrl($user);

        $this->travel(61)->minutes();

        $this->getJson($url)->assertForbidden();

        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_verify_email_de_un_correo_ya_verificado_responde_already_verified(): void
    {
        $user = User::factory()->create();

        $this->getJson($this->verificationUrl($user))
            ->assertOk()
            ->assertJsonPath('data.status', 'already-verified');
    }

    public function test_verification_notification_envia_el_correo_a_un_usuario_sin_verificar(): void
    {
        $user = $this->actingAsUser(['email_verified_at' => null]);

        $this->postJson('/api/email/verification-notification')
            ->assertOk()
            ->assertJsonPath('data.status', 'verification-link-sent');

        Notification::assertSentTo($user, QueuedVerifyEmail::class);
    }

    public function test_verification_notification_no_envia_nada_si_ya_esta_verificado(): void
    {
        $this->actingAsUser();

        $this->postJson('/api/email/verification-notification')
            ->assertOk()
            ->assertJsonPath('data.status', 'already-verified');

        Notification::assertNothingSent();
    }

    public function test_verification_notification_sin_sesion_da_401(): void
    {
        $this->postJson('/api/email/verification-notification')
            ->assertUnauthorized()
            ->assertJsonPath('error.code', 'unauthenticated');
    }
}
