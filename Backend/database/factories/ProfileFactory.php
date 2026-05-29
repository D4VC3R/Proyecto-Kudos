<?php

namespace Database\Factories;

use App\Models\Profile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory para generar datos de prueba para el modelo Profile.
 * @extends Factory<Profile>
 */
class ProfileFactory extends Factory
{
    /**
     * Define el estado por defecto de los atributos del perfil.
     * Genera datos aleatorios para la biografía, enlaces sociales, ciudad y fecha de nacimiento.
     *
     * @return array Los atributos del perfil con datos generados.
     */
    public function definition(): array
    {
        return [
            'avatar' => null,
            'biography' => fake()->paragraph(3),
            'social_links' => [
                'twitter' => fake()->optional()->url(),
                'linkedin' => fake()->optional()->url(),
                'github' => fake()->optional()->url(),
            ],
            'city' => fake()->city(),
            'birthdate' => fake()->dateTimeBetween('-60 years', '-18 years'),
        ];
    }
}
