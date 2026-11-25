<?php

namespace Tests\Feature\Users;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * La edad mínima del perfil (14 años) coincide con la del registro.
 */
class ProfileBirthdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_perfil_rechaza_una_fecha_de_nacimiento_de_13_anos(): void
    {
        $this->actingAsUser();

        $this->putJson('/api/profile', ['birthdate' => now()->subYears(13)->subMonths(6)->toDateString()])
            ->assertStatus(422)
            ->assertJsonPath('error.details.birthdate.0', 'Debes tener al menos 14 años para registrarte.');
    }

    public function test_el_perfil_acepta_una_fecha_de_nacimiento_de_14_anos(): void
    {
        $this->actingAsUser();

        $this->putJson('/api/profile', ['birthdate' => now()->subYears(14)->subMonth()->toDateString()])
            ->assertOk();
    }
}
