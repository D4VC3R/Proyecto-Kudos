<?php

namespace Database\Factories;

use App\Models\Item;
use App\Models\ItemComment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ItemComment>
 */
class ItemCommentFactory extends Factory
{
    protected $model = ItemComment::class;

    public function definition(): array
    {
        return [
            'item_id' => fn () => Item::query()->inRandomOrder()->value('id'),
            'user_id' => User::factory(),
            'content' => fake()->sentence(12),
            'is_hidden' => false,
        ];
    }
}
