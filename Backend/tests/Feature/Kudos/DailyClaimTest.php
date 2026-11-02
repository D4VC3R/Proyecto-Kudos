<?php

namespace Tests\Feature\Kudos;

use App\Models\KudosTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Caracterización (F5-01): recompensa diaria (POST /api/profile/daily-claim).
 * No se fija `meta.message` (en inglés, BE-R3, F5-04). La cuantía tiene un bonus aleatorio
 * (random_int, no se puede sembrar), así que se comprueba que sea uno de los valores posibles.
 */
class DailyClaimTest extends TestCase
{
    use RefreshDatabase;

    /** Valores posibles de la recompensa de una racha: la base o la base con el bonus (azar con random_int). */
    private function possibleRewards(int $streak): array
    {
        $effective = min($streak, (int) config('kudos.rules.daily_login_streak_cap'));
        $config = config("kudos.rewards.daily_login_streak.{$effective}");

        return [$config['base'], $config['base'] * $config['bonus_multiplier']];
    }

    private function dailyTransactions(User $user): int
    {
        return KudosTransaction::where('user_id', $user->id)->where('reason', 'daily_login_streak')->count();
    }

    public function test_el_primer_reclamo_inicia_la_racha_y_da_kudos(): void
    {
        $this->travelTo(Carbon::parse('2026-03-10 10:00:00'));
        $user = $this->actingAsUser();

        $response = $this->postJson('/api/profile/daily-claim')
            ->assertOk()
            ->assertJsonPath('data.status', 'claimed')
            ->assertJsonPath('data.streak', 1)
            ->assertJsonPath('meta.server_date', '2026-03-10');

        $awarded = $response->json('meta.kudos_awarded');
        $this->assertContains($awarded, $this->possibleRewards(1));
        $response->assertJsonPath('data.kudos', $awarded);

        $fresh = $user->fresh();
        $this->assertSame($awarded, $fresh->total_kudos);
        $this->assertSame(1, $fresh->login_streak_count);
        $this->assertSame(1, $fresh->max_login_streak_count);
        $this->assertSame('2026-03-10', $fresh->last_login_streak_date->toDateString());
        $this->assertSame(1, $this->dailyTransactions($user));
    }

    public function test_reclamar_dos_veces_el_mismo_dia_no_duplica_la_recompensa(): void
    {
        $this->travelTo(Carbon::parse('2026-03-10 10:00:00'));
        $user = $this->actingAsUser();

        $first = $this->postJson('/api/profile/daily-claim')->assertOk();
        $kudos = $first->json('data.kudos');

        $this->travelTo(Carbon::parse('2026-03-10 22:00:00'));

        $this->postJson('/api/profile/daily-claim')
            ->assertOk()
            ->assertJsonPath('data.status', 'already_claimed')
            ->assertJsonPath('data.streak', 1)
            ->assertJsonPath('data.kudos', $kudos);

        $this->assertSame(1, $this->dailyTransactions($user));
    }

    public function test_reclamar_al_dia_siguiente_suma_un_dia_a_la_racha(): void
    {
        $this->travelTo(Carbon::parse('2026-03-10 10:00:00'));
        $user = $this->actingAsUser();
        $this->postJson('/api/profile/daily-claim')->assertOk();

        $this->travelTo(Carbon::parse('2026-03-11 09:00:00'));

        $response = $this->postJson('/api/profile/daily-claim')
            ->assertOk()
            ->assertJsonPath('data.status', 'claimed')
            ->assertJsonPath('data.streak', 2)
            ->assertJsonPath('meta.is_new_record', true);

        $this->assertContains($response->json('meta.kudos_awarded'), $this->possibleRewards(2));
        $this->assertSame(2, $this->dailyTransactions($user));
        $this->assertSame(2, $user->fresh()->max_login_streak_count);
    }

    public function test_saltarse_un_dia_reinicia_la_racha(): void
    {
        $this->travelTo(Carbon::parse('2026-03-10 10:00:00'));
        $user = $this->actingAsUser([
            'login_streak_count' => 3,
            'max_login_streak_count' => 3,
            'last_login_streak_date' => '2026-03-08',
        ]);

        $this->postJson('/api/profile/daily-claim')
            ->assertOk()
            ->assertJsonPath('data.status', 'claimed')
            ->assertJsonPath('data.streak', 1)
            ->assertJsonPath('meta.is_new_record', false);

        $fresh = $user->fresh();
        $this->assertSame(1, $fresh->login_streak_count);
        $this->assertSame(3, $fresh->max_login_streak_count);
    }

    public function test_una_racha_por_encima_del_tope_usa_la_recompensa_del_tope(): void
    {
        $this->travelTo(Carbon::parse('2026-03-10 10:00:00'));
        $this->actingAsUser([
            'login_streak_count' => 7,
            'max_login_streak_count' => 7,
            'last_login_streak_date' => '2026-03-09',
        ]);

        $response = $this->postJson('/api/profile/daily-claim')
            ->assertOk()
            ->assertJsonPath('data.streak', 8);

        // possibleRewards() limita la racha al tope (5): la recompensa de la racha 8 es la del tope.
        $this->assertContains($response->json('meta.kudos_awarded'), $this->possibleRewards(8));
    }

    public function test_reclamar_sin_sesion_da_401(): void
    {
        $this->postJson('/api/profile/daily-claim')
            ->assertUnauthorized()
            ->assertJsonPath('error.code', 'unauthenticated');
    }

    public function test_un_usuario_baneado_recibe_403(): void
    {
        $user = $this->actingAsUser(['is_banned' => true, 'banned_until' => null]);

        $this->postJson('/api/profile/daily-claim')
            ->assertForbidden()
            ->assertJsonPath('error.code', 'forbidden');

        $this->assertSame(0, $this->dailyTransactions($user));
    }

    public function test_un_usuario_sin_verificar_recibe_403(): void
    {
        $user = $this->actingAsUser(['email_verified_at' => null]);

        $this->postJson('/api/profile/daily-claim')
            ->assertForbidden();

        $this->assertSame(0, $this->dailyTransactions($user));
    }
}
