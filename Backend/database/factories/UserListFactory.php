<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserList;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserList>
 */
class UserListFactory extends Factory
{
    protected $model = UserList::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->unique()->sentence(3),
            'description' => null,
            'visibility' => UserList::VISIBILITY_PRIVATE,
            'is_ranked' => false,
            'is_editorial' => false,
        ];
    }

    /**
     * Lista editorial: pública y creada por el equipo.
     */
    public function editorial(): static
    {
        return $this->state(fn () => [
            'is_editorial' => true,
            'visibility' => UserList::VISIBILITY_PUBLIC,
        ]);
    }
}
