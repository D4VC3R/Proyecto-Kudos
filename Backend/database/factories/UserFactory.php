<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Profile;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Factory para generar datos de prueba para el modelo User.
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * Define el estado por defecto de los atributos del usuario.
     *
     * @return array Los atributos del usuario con datos generados.
     */
    public function definition(): array
    {
        return [
            'name'              => fake()->name(),
            'email'             => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password'          => Hash::make('password'),
            'remember_token'    => Str::random(10),
        ];
    }

    /**
     * Configura la fábrica para crear un perfil asociado cada vez que se crea un usuario.
     *
     * @return $this La instancia de la fábrica configurada para crear perfiles automáticamente.
     */
    public function configure()
    {
        return $this->afterCreating(function (User $user) {
            Profile::factory()->create(['user_id' => $user->id]);
        });
    }

    /**
     * Define un estado para un usuario no verificado.
     *
     * @return static La instancia de la fábrica con el estado actualizado a no verificado.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
