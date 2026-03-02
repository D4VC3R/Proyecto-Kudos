<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Profile>
 */
class ProfileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'avatar' => fake()->imageUrl(200, 200, 'people'),
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
