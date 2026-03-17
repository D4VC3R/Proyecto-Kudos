<?php

namespace Database\Factories;

use App\Models\Item;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Database\Eloquent\Factories\Factory;

class VoteFactory extends Factory
{
    protected $model = Vote::class;

    public function definition(): array
    {
        return [
            'user_id' => User::inRandomOrder()->first()->id,
            'item_id' => Item::where('status', Item::STATUS_ACTIVE)->inRandomOrder()->first()->id,
            'type' => Vote::TYPE_VOTE,
            'score' => fake()->numberBetween(0, 10),
        ];
    }

    public function forItem(Item $item): static
    {
        return $this->state(fn (array $attributes) => [
            'item_id' => $item->id,
        ]);
    }

    public function byUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

    public function withScore(int $score): static
    {
        return $this->state(fn (array $attributes) => [
            'score' => max(0, min(10, $score)),
        ]);
    }
}
