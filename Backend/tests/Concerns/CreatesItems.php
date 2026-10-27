<?php

namespace Tests\Concerns;

use App\Models\Category;
use App\Models\Item;
use App\Models\User;

/**
 * Helpers para crear categorías e ítems en los tests.
 * ItemFactory::definition() elige una categoría y un creador al azar de la base de datos,
 * así que antes de crear un ítem tiene que existir al menos un usuario.
 */
trait CreatesItems
{
    /**
     * Crea una categoría con nombre y slug explícitos (CategoryFactory puede repetirlos).
     */
    protected function createCategory(string $slug = 'peliculas', string $name = 'Películas'): Category
    {
        return Category::factory()->create(['slug' => $slug, 'name' => $name]);
    }

    /**
     * Crea un ítem activo y sin votos en la categoría indicada.
     */
    protected function createItem(Category $category, array $attrs = []): Item
    {
        if (! User::query()->exists()) {
            User::factory()->create();
        }

        return Item::factory()->create(array_merge([
            'category_id' => $category->id,
            'status'      => Item::STATUS_ACTIVE,
            'vote_avg'    => 0,
            'vote_count'  => 0,
        ], $attrs));
    }
}
