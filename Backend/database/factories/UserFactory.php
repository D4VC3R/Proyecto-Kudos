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
     * Genera datos aleatorios para el nombre, correo electrónico, verificación de correo, contraseña y token de recuerdo.
     *
     * @return array Los atributos del usuario con datos generados.
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Configura la fábrica para crear un perfil asociado cada vez que se crea un usuario.
     * Utiliza la función afterCreating para generar un perfil con el user_id del usuario recién creado.
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
     * Define un estado para un usuario no verificado, estableciendo el campo email_verified_at como null.
     *
     * @return static La instancia de la fábrica con el estado actualizado a no verificado.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Define un estado para un usuario administrador, estableciendo un nombre, correo electrónico y contraseña específicos, y marcando el correo como verificado.
     *
     * @return static La instancia de la fábrica con el estado actualizado a administrador.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'kudosAdmin',
            'email' => 'admin@kudos.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
        ]);
    }
}
