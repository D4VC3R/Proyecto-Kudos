<?php

namespace Database\Factories;

use App\Models\Item;
use App\Models\ListItem;
use App\Models\UserList;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ListItem>
 */
class ListItemFactory extends Factory
{
    protected $model = ListItem::class;

    public function definition(): array
    {
        return [
            'list_id' => UserList::factory(),
            'item_id' => fn () => Item::query()->inRandomOrder()->value('id'),
            'position' => 1,
            'note' => null,
        ];
    }
}
