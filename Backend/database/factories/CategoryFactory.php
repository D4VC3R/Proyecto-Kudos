<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Category>
 */
class CategoryFactory extends Factory
{
    private static $categories = [
        'videojuegos' => [
            'description' => 'Descubre y vota por los mejores videojuegos de todos los tiempos',
            'image_keyword' => 'gaming'
        ],
        'musica' => [
            'description' => 'Las mejores canciones, álbumes y artistas musicales',
            'image_keyword' => 'music'
        ],
        'peliculas' => [
            'description' => 'El cine que ha marcado historia y entretenimiento',
            'image_keyword' => 'movies'
        ],
        'series' => [
            'description' => 'Las series de televisión más populares y aclamadas',
            'image_keyword' => 'tv'
        ],
        'ciudades' => [
            'description' => 'Las ciudades más bellas e interesantes del mundo',
            'image_keyword' => 'city'
        ],
        'marcas' => [
            'description' => 'Las marcas más reconocidas e influyentes',
            'image_keyword' => 'business'
        ],
        'politicos' => [
            'description' => 'Figuras políticas destacadas en la historia',
            'image_keyword' => 'people'
        ],
        'paises' => [
            'description' => 'Los países más fascinantes del planeta',
            'image_keyword' => 'nature'
        ],
    ];

    private static $usedCategories = [];
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Obtener categorías disponibles
        $available = array_diff_key(self::$categories, array_flip(self::$usedCategories));

        if (empty($available)) {
            self::$usedCategories = [];
            $available = self::$categories;
        }

        // Seleccionar una categoría aleatoria
        $name = array_rand($available);
        $data = $available[$name];

        // Marcar como usada
        self::$usedCategories[] = $name;

        return [
            'name' => ucfirst($name),
            'description' => $data['description'],
            'slug' => Str::slug($name),
            'image' => fake()->imageUrl(640, 480, $data['image_keyword']),
        ];
    }
}
