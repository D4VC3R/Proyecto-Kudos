<?php

namespace Database\Factories;

use App\Models\Profile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Profile>
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
