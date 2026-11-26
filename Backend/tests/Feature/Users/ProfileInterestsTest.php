<?php

namespace Tests\Feature\Users;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\CreatesItems;
use Tests\TestCase;

/**
 * Intereses del perfil (F2-07): PUT /api/profile con "interests", GET /api/profile y login.
 */
class ProfileInterestsTest extends TestCase
{
    use CreatesItems, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();

        $this->createCategory('peliculas', 'Películas');
        $this->createCategory('series', 'Series');
    }

    public function test_put_profile_guarda_los_intereses(): void
    {
        $user = $this->actingAsUser();

        $this->putJson('/api/profile', ['interests' => ['peliculas', 'series']])
            ->assertOk()
            ->assertJsonPath('data.interests', ['peliculas', 'series']);

        $this->assertSame(['peliculas', 'series'], $user->profile->fresh()->interests);
    }

    public function test_put_profile_acepta_una_lista_vacia(): void
    {
        $user = $this->actingAsUser();
        $user->profile->update(['interests' => ['peliculas']]);

        $this->putJson('/api/profile', ['interests' => []])
            ->assertOk()
            ->assertJsonPath('data.interests', []);

        $this->assertSame([], $user->profile->fresh()->interests);
    }

    public function test_put_profile_rechaza_categorias_inexistentes_o_inactivas(): void
    {
        $user = $this->actingAsUser();
        $user->profile->update(['interests' => ['peliculas']]);
        $this->createCategory('juegos', 'Juegos')->update(['is_active' => false]);

        $this->putJson('/api/profile', ['interests' => ['peliculas', 'no-existe']])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'validation_error')
            ->assertJsonStructure(['error' => ['details' => ['interests.1']]]);

        $this->putJson('/api/profile', ['interests' => ['juegos']])
            ->assertStatus(422)
            ->assertJsonStructure(['error' => ['details' => ['interests.0']]]);

        $this->assertSame(['peliculas'], $user->profile->fresh()->interests);
    }

    public function test_put_profile_rechaza_intereses_repetidos(): void
    {
        $user = $this->actingAsUser();

        $this->putJson('/api/profile', ['interests' => ['peliculas', 'peliculas']])
            ->assertStatus(422);

        $this->assertSame([], $user->profile->fresh()->interests);
    }

    public function test_put_profile_sin_interests_no_los_toca(): void
    {
        $user = $this->actingAsUser();
        $user->profile->update(['interests' => ['series'], 'city' => 'Madrid', 'biography' => 'Hola']);

        $this->putJson('/api/profile', ['city' => 'Valencia'])->assertOk();

        $profile = $user->profile->fresh();
        $this->assertSame(['series'], $profile->interests);
        $this->assertSame('Valencia', $profile->city);

        // Al revés: guardar intereses no cambia el resto
        $this->putJson('/api/profile', ['interests' => ['peliculas']])->assertOk();

        $profile = $user->profile->fresh();
        $this->assertSame(['peliculas'], $profile->interests);
        $this->assertSame('Valencia', $profile->city);
        $this->assertSame('Hola', $profile->biography);
    }

    public function test_get_profile_incluye_interests_vacio_por_defecto(): void
    {
        $this->actingAsUser();

        $this->getJson('/api/profile')
            ->assertOk()
            ->assertJsonPath('data.interests', []);
    }

    public function test_el_login_devuelve_los_intereses_del_usuario(): void
    {
        $user = User::factory()->create();
        $user->profile->update(['interests' => ['peliculas', 'series']]);
        $other = User::factory()->create();

        $this->postJson('/api/login', ['email' => $user->email, 'password' => 'password'])
            ->assertOk()
            ->assertJsonPath('data.user.interests', ['peliculas', 'series']);

        $this->postJson('/api/login', ['email' => $other->email, 'password' => 'password'])
            ->assertOk()
            ->assertJsonPath('data.user.interests', []);
    }
}
