<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Item;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ItemSeeder extends Seeder
{
    public function run(): void
    {
        $categories = Category::all();

        foreach ($categories as $category) {
            // Crear 5 items aceptados por categoría
            $acceptedItems = Item::factory()
                ->forCategory($category)
                ->count(5)
                ->create();

            foreach ($acceptedItems as $item) {
                $item->creator->increment('creations_accepted');

                $item->kudosTransactions()->create([
                    'id' => Str::uuid(),
                    'user_id' => $item->creator_id,
                    'kudos_amount' => 10,
                    'reason' => 'item_accepted',
                ]);
                $item->creator->increment('total_kudos', 10);
            }
        }
    }
}
