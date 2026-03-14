<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Item;
use App\Models\User;
use Illuminate\Database\Seeder;

class ItemSeeder extends Seeder
{
    public function run(): void
    {
        $systemUser = User::query()
            ->where('email', 'admin@kudos.com')
            ->firstOrFail();

        $categories = Category::all();

        foreach ($categories as $category) {
            Item::factory()
                ->forCategory($category)
                ->count(5)
                ->create([
                    'creator_id' => $systemUser->id,
                    'status' => Item::STATUS_ACTIVE,
                ]);
        }
    }
}
