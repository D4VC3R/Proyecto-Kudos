<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_can_be_verified(): void
    {
        $user = User::factory()->unverified()->create();

        Event::fake();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $response = $this->actingAs($user)->getJson($verificationUrl);

        Event::assertDispatched(Verified::class);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $response
            ->assertOk()
            ->assertJsonPath('message', 'Email verificado correctamente.')
            ->assertJsonPath('data.status', 'verified');
    }

    public function test_email_is_not_verified_with_invalid_hash(): void
    {
        $user = User::factory()->unverified()->create();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1('wrong-email')]
        );

        $this->actingAs($user)->getJson($verificationUrl)
            ->assertStatus(403);

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_email_verification_notification_returns_json_when_already_verified(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/email/verification-notification')
            ->assertOk()
            ->assertJsonPath('message', 'El email ya estaba verificado.')
            ->assertJsonPath('data.status', 'already-verified');
    }
}
