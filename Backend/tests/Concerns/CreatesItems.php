<?php

namespace Tests\Concerns;

use App\Models\Category;
use App\Models\Item;
use App\Models\User;
use App\Services\ItemStatsService;

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
     * Crea un ítem activo (por defecto sin votos) en la categoría indicada.
     * Calcula bayes_score a partir de vote_avg y vote_count, salvo que el test lo pase explícitamente.
     */
    protected function createItem(Category $category, array $attrs = []): Item
    {
        if (! User::query()->exists()) {
            User::factory()->create();
        }

        $attrs = array_merge([
            'category_id' => $category->id,
            'status'      => Item::STATUS_ACTIVE,
            'vote_avg'    => 0,
            'vote_count'  => 0,
        ], $attrs);

        // bayes_score coherente con vote_avg/vote_count, como si la categoría no tuviera votos (C = fallback).
        // No usa categoryMean() para no dejar nada en la caché.
        if (! array_key_exists('bayes_score', $attrs)) {
            $attrs['bayes_score'] = app(ItemStatsService::class)->bayesScore(
                (int) $attrs['vote_count'],
                (float) $attrs['vote_avg'],
                (float) config('kudos.ranking.category_mean_fallback')
            );
        }

        return Item::factory()->create($attrs);
    }
}
