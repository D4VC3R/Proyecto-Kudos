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
}
