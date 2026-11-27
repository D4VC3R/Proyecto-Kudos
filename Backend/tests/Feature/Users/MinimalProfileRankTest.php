<?php

namespace Tests\Feature\Users;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithUsers;
use Tests\TestCase;

/**
 * La posición de la cabecera (/profile/minimal) usa el mismo orden que el ranking de usuarios.
 */
class MinimalProfileRankTest extends TestCase
{
    use InteractsWithUsers, RefreshDatabase;

    public function test_la_posicion_de_la_cabecera_coincide_con_la_del_ranking_con_empates(): void
    {
        User::factory()->create(['total_kudos' => 50, 'created_at' => now()->subDays(2)]);
        User::factory()->create(['total_kudos' => 50, 'created_at' => now()->subDay()]);
        $this->actingAsUser(['total_kudos' => 50]);

        $position = $this->getJson('/api/profile/minimal')->assertOk()->json('data.ranking_position');
        $rank = $this->getJson('/api/users/ranking')->assertOk()->json('meta.my_position.rank');

        $this->assertSame(3, $position);
        $this->assertSame($rank, $position);
    }

    public function test_la_posicion_de_la_cabecera_cuenta_a_los_que_tienen_mas_kudos(): void
    {
        User::factory()->count(2)->create(['total_kudos' => 100]);
        User::factory()->create(['total_kudos' => 10]);
        $this->actingAsUser(['total_kudos' => 50]);

        $this->getJson('/api/profile/minimal')
            ->assertOk()
            ->assertJsonPath('data.ranking_position', 3);
    }

    public function test_tras_la_recompensa_diaria_la_cabecera_y_el_ranking_dan_la_misma_posicion(): void
    {
        User::factory()->create(['total_kudos' => 50, 'created_at' => now()->subDays(2)]);
        User::factory()->create(['total_kudos' => 50, 'created_at' => now()->subDay()]);
        $user = $this->actingAsUser(['total_kudos' => 50]);

        $this->assertSame(3, $this->getJson('/api/profile/minimal')->assertOk()->json('data.ranking_position'));

        $this->postJson('/api/profile/daily-claim')->assertOk()->assertJsonPath('data.status', 'claimed');
        // Sanctum::actingAs reutiliza la misma instancia entre peticiones; en producción cada petición carga el usuario de nuevo.
        $user->refresh();

        $position = $this->getJson('/api/profile/minimal')->assertOk()->json('data.ranking_position');
        $ranking = $this->getJson('/api/users/ranking')->assertOk();

        $this->assertSame(1, $position);
        $ranking->assertJsonPath('meta.my_position.rank', 1)
            ->assertJsonPath('data.my_page_data.0.id', $user->id)
            ->assertJsonPath('data.my_page_data.0.rank', 1);
    }

    public function test_con_la_misma_fecha_de_alta_el_desempate_por_id_coincide(): void
    {
        $at = now()->subDay()->startOfSecond();
        User::factory()->count(2)->create(['total_kudos' => 50, 'created_at' => $at]);
        $user = $this->actingAsUser(['total_kudos' => 50, 'created_at' => $at]);

        $position = $this->getJson('/api/profile/minimal')->assertOk()->json('data.ranking_position');
        $ranking = $this->getJson('/api/users/ranking')->assertOk();
        $row = collect($ranking->json('data.my_page_data'))->firstWhere('id', $user->id);

        $this->assertSame($position, $ranking->json('meta.my_position.rank'));
        $this->assertNotNull($row);
        $this->assertSame($position, $row['rank']);
    }
}
